<?php
/**
 * UsuarioController
 * 
 * Gerenciar usuários da empresa
 */

namespace App\Controllers;

use Core\Controller;
use App\Services\AuthService;
use App\Repositories\Repository;

class UsuarioController extends Controller
{
    private AuthService $authService;

    public function __construct()
    {
        parent::__construct();
        $this->authService = new AuthService();
    }

    /**
     * GET /usuarios
     * Listar usuários da empresa
     */
    public function index(array $params = []): void
    {
        if ($this->tenantId === null && $this->userRole !== 'master') {
            $this->responseError('Tenant não identificado', 401);
        }

        $this->authorize('admin', 'master');

        if (strpos($_SERVER['HTTP_ACCEPT'] ?? '', 'application/json') !== false || $this->input('api')) {
            if ($this->userRole === 'master') {
                $query = "
                    SELECT u.id, u.nome, u.email, u.role, u.ativo, u.senha_temporaria, u.ultimo_login, u.created_at, e.nome as empresa_nome
                    FROM usuarios u
                    LEFT JOIN empresas e ON u.tenant_id = e.id
                    WHERE u.deleted_at IS NULL
                    ORDER BY u.created_at DESC
                ";
                $usuarios = $this->db->query($query);
            } else {
                $query = "
                    SELECT id, nome, email, role, ativo, senha_temporaria, ultimo_login, created_at
                    FROM usuarios
                    WHERE tenant_id = :tenant_id AND deleted_at IS NULL
                    ORDER BY created_at DESC
                ";
                $usuarios = $this->db->query($query, [':tenant_id' => $this->tenantId]);
            }
            
            $this->responseSuccess($usuarios, 'Usuários listados');
        }

        $this->render('usuarios/index', [
            'title' => 'Gestão de Usuários - Controle de Frota'
        ]);
    }

    /**
     * POST /usuarios
     * Criar novo usuário na empresa
     */
    public function store(array $params = []): void
    {
        if (!$this->validateCsrfToken()) {
            return;
        }

        if ($this->tenantId === null && $this->userRole !== 'master') {
            $this->responseError('Tenant não identificado', 401);
        }

        $this->authorize('admin', 'master');

        $nome = $this->input('nome');
        $email = $this->input('email');
        $role = $this->input('role', 'operador');
        $targetTenantId = ($this->userRole === 'master') ? $this->input('tenant_id') : $this->tenantId;

        if (!$nome || !$email) {
            $this->responseError('Nome e email são obrigatórios', 400);
        }

        if (!$targetTenantId && $role !== 'master') {
            $this->responseError('Tenant ID é obrigatório para usuários não-master', 400);
        }

        $resultado = $this->authService->createUser($targetTenantId, $nome, $email, $role);

        if ($resultado['success']) {
            if ($targetTenantId) {
                $this->log('CRIAR_USUARIO', 'USUARIOS', "Usuário criado: {$email}", [
                    'nome' => $nome,
                    'role' => $role,
                    'tenant_id' => $targetTenantId
                ]);
            }
            $this->responseSuccess($resultado, $resultado['message'], 201);
        } else {
            $this->responseError($resultado['message'], 400);
        }
    }

    /**
     * PUT /usuarios/{id}
     * Atualizar usuário
     */
    public function update(array $params = []): void
    {
        if ($this->tenantId === null && $this->userRole !== 'master') {
            $this->responseError('Tenant não identificado', 401);
        }

        $this->authorize('admin', 'master');

        $id = $params['id'] ?? null;
        if (!$id) {
            $this->responseError('ID não fornecido', 400);
        }

        // Verificar se usuário existe e permissão
        if ($this->userRole === 'master') {
            $query = "SELECT * FROM usuarios WHERE id = :id";
            $usuario = $this->db->query($query, [':id' => $id], true);
        } else {
            $query = "SELECT * FROM usuarios WHERE id = :id AND tenant_id = :tenant_id";
            $usuario = $this->db->query($query, [':id' => $id, ':tenant_id' => $this->tenantId], true);
        }

        if (!$usuario) {
            $this->responseError('Usuário não encontrado', 404);
        }

        $updateData = [];
        if ($this->input('nome')) $updateData['nome'] = $this->input('nome');
        if ($this->input('role')) $updateData['role'] = $this->input('role');
        if ($this->input('ativo') !== null) $updateData['ativo'] = $this->input('ativo');

        if (!empty($updateData)) {
            $sets = [];
            $bindParams = [':id' => $id];

            foreach ($updateData as $key => $value) {
                $sets[] = "{$key} = :{$key}";
                $bindParams[":{$key}"] = $value;
            }

            $query = "UPDATE usuarios SET " . implode(', ', $sets) . " WHERE id = :id";
            if ($this->userRole !== 'master') {
                $query .= " AND tenant_id = :tenant_id";
                $bindParams[':tenant_id'] = $this->tenantId;
            }

            $this->db->execute($query, $bindParams);

            if ($this->tenantId) {
                $this->log('ATUALIZAR_USUARIO', 'USUARIOS', "Usuário {$id} atualizado", $updateData);
            }
        }

        $this->responseSuccess(null, 'Usuário atualizado com sucesso');
    }

    /**
     * DELETE /usuarios/{id}
     * Deletar usuário (soft delete)
     */
    public function destroy(array $params = []): void
    {
        if ($this->tenantId === null && $this->userRole !== 'master') {
            $this->responseError('Tenant não identificado', 401);
        }

        $this->authorize('admin', 'master');

        $id = $params['id'] ?? null;
        if (!$id) {
            $this->responseError('ID não fornecido', 400);
        }

        // Verificar se usuário existe e permissão
        if ($this->userRole === 'master') {
            $query = "SELECT * FROM usuarios WHERE id = :id";
            $usuario = $this->db->query($query, [':id' => $id], true);
        } else {
            $query = "SELECT * FROM usuarios WHERE id = :id AND tenant_id = :tenant_id";
            $usuario = $this->db->query($query, [':id' => $id, ':tenant_id' => $this->tenantId], true);
        }

        if (!$usuario) {
            $this->responseError('Usuário não encontrado', 404);
        }

        // Impedir deletar o último admin do tenant
        if ($usuario['tenant_id'] && $usuario['role'] === 'admin') {
            $countAdmins = $this->db->query(
                "SELECT COUNT(*) as total FROM usuarios WHERE tenant_id = :tenant_id AND role = 'admin' AND deleted_at IS NULL",
                [':tenant_id' => $usuario['tenant_id']],
                true
            );

            if ($countAdmins['total'] <= 1) {
                $this->responseError('Não é possível deletar o último admin da empresa', 400);
            }
        }

        // Soft delete
        $query = "UPDATE usuarios SET deleted_at = NOW() WHERE id = :id";
        $bindParams = [':id' => $id];
        if ($this->userRole !== 'master') {
            $query .= " AND tenant_id = :tenant_id";
            $bindParams[':tenant_id'] = $this->tenantId;
        }
        
        $this->db->execute($query, $bindParams);

        if ($this->tenantId) {
            $this->log('DELETAR_USUARIO', 'USUARIOS', "Usuário {$id} deletado");
        }
        $this->responseSuccess(null, 'Usuário deletado com sucesso');
    }
}
