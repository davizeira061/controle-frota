<?php
/**
 * EmpresaController
 * 
 * Gerenciamento de empresas (Apenas Master)
 */

namespace App\Controllers;

use Core\Controller;
use App\Repositories\Repository;
use App\Services\AuthService;

class EmpresaController extends Controller
{
    private $repository;
    private AuthService $authService;

    public function __construct()
    {
        parent::__construct();
        $this->authService = new AuthService();
        // Usando o repositório genérico para empresas
        $this->repository = new class extends Repository {
            protected string $table = 'empresas';
        };
    }

    /**
     * GET /admin/empresas
     * Listar todas as empresas (Master)
     */
    public function index(): void
    {
        $this->authorize('master');
        
        $page = $this->input('page', 1);

        if (strpos($_SERVER['HTTP_ACCEPT'] ?? '', 'application/json') !== false || $this->input('api')) {
            // Para master, não usamos filtro de tenant
            $offset = ($page - 1) * 15;
            $results = $this->db->query("SELECT * FROM empresas ORDER BY created_at DESC LIMIT 15 OFFSET :offset", [
                ':offset' => $offset
            ]);
            
            $total = $this->db->query("SELECT COUNT(*) as total FROM empresas", [], true)['total'];

            $this->responseSuccess([
                'data' => $results,
                'total' => $total,
                'page' => $page,
                'pages' => ceil($total / 15)
            ], 'Empresas listadas com sucesso');
        }

        $this->render('admin/empresas', [
            'title' => 'Gestão de Empresas - MASTER'
        ]);
    }

    /**
     * POST /admin/empresas
     * Criar nova empresa (Master)
     */
    public function store(): void
    {
        if (!$this->validateCsrfToken()) {
            return;
        }

        $this->authorize('master');
        
        $nome = $this->input('nome');
        $email = $this->input('email');

        if (!$nome || !$email) {
            $this->responseError('Nome e email são obrigatórios');
        }

        try {
            $this->db->beginTransaction();

            // 1. Criar a empresa
            $query = "INSERT INTO empresas (nome, email) VALUES (:nome, :email)";
            $empresaId = $this->db->lastInsertId($query, [
                ':nome' => $nome,
                ':email' => $email
            ]);

            // 2. Criar o usuário administrador da empresa
            $usuarioResult = $this->authService->createUser(
                (int)$empresaId,
                "Admin " . $nome,
                $email,
                'admin'
            );

            if (!$usuarioResult['success']) {
                throw new \Exception($usuarioResult['message']);
            }

            $this->db->commit();

            $this->responseSuccess([
                'id' => $empresaId,
                'admin_email' => $email,
                'temporary_password' => $usuarioResult['temporary_password']
            ], 'Empresa e administrador criados com sucesso', 201);

        } catch (\Exception $e) {
            $this->db->rollback();
            $this->responseError('Erro ao criar empresa: ' . $e->getMessage(), 400);
        }
    }

    /**
     * PUT /admin/empresas/{id}/status
     * Ativar/Desativar empresa (Master)
     */
    /**
     * POST /admin/empresas/{id}/reset-password
     * Reseta a senha do administrador da empresa (Master)
     */
    public function resetAdminPassword(array $params): void
    {
        if (!$this->validateCsrfToken()) {
            return;
        }

        $this->authorize('master');
        
        $empresaId = $params['id'];

        // Buscar o administrador da empresa
        $admin = $this->db->query(
            "SELECT id, email FROM usuarios WHERE tenant_id = :tid AND role = 'admin' AND deleted_at IS NULL LIMIT 1",
            [':tid' => $empresaId],
            true
        );

        if (!$admin) {
            $this->responseError('Administrador não encontrado para esta empresa', 404);
        }

        $result = $this->authService->resetUserPassword((int)$admin['id']);

        if ($result['success']) {
            $this->responseSuccess([
                'email' => $admin['email'],
                'temporary_password' => $result['temporary_password']
            ], 'Senha do administrador resetada com sucesso');
        } else {
            $this->responseError('Erro ao resetar senha: ' . $result['message'], 400);
        }
    }

    /**
     * PUT /admin/empresas/{id}/status
     * Ativar/Desativar empresa (Master)
     */
    public function toggleStatus(array $params): void
    {
        if (!$this->validateCsrfToken()) {
            return;
        }

        $this->authorize('master');
        
        $id = $params['id'];
        $status = $this->input('ativo'); // 1 ou 0

        $this->db->execute("UPDATE empresas SET deleted_at = :deleted WHERE id = :id", [
            ':deleted' => ($status ? null : date('Y-m-d H:i:s')),
            ':id' => $id
        ]);

        $this->responseSuccess(null, 'Status da empresa atualizado');
    }
}
