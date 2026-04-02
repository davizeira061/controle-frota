<?php
/**
 * VeiculoController
 * 
 * Endpoints de veículos:
 * GET /veiculos - Listar veículos (com paginação)
 * POST /veiculos - Criar veículo
 * GET /veiculos/{id} - Detalhes do veículo
 * PUT /veiculos/{id} - Atualizar veículo
 * DELETE /veiculos/{id} - Deletar veículo
 */

namespace App\Controllers;

use Core\Controller;
use App\Services\VeiculoService;
use App\Repositories\VeiculoRepository;

class VeiculoController extends Controller
{
    private VeiculoService $service;
    private VeiculoRepository $repository;

    public function __construct()
    {
        parent::__construct();
        $this->service = new VeiculoService();
        $this->repository = new VeiculoRepository();
    }

    /**
     * GET /veiculos
     * Listar veículos com paginação
     */
    public function index(array $params = []): void
    {
        // Se a requisição espera JSON (API)
        if (strpos($_SERVER['HTTP_ACCEPT'] ?? '', 'application/json') !== false || $this->input('api')) {
            $page = $this->input('page', 1);
            
            if ($this->userRole === 'master') {
                $offset = ($page - 1) * 15;
                $results = $this->db->query("SELECT v.*, e.nome as empresa_nome FROM veiculos v JOIN empresas e ON v.tenant_id = e.id WHERE v.deleted_at IS NULL ORDER BY v.created_at DESC LIMIT 15 OFFSET :offset", [
                    ':offset' => $offset
                ]);
                $total = $this->db->query("SELECT COUNT(*) as total FROM veiculos WHERE deleted_at IS NULL", [], true)['total'];
                $resulting = [
                    'data' => $results,
                    'total' => $total,
                    'page' => $page,
                    'pages' => ceil($total / 15)
                ];
            } else {
                $resulting = $this->repository->getAllByTenant($this->tenantId, $page);
            }
            
            $this->responseSuccess($resulting, 'Veículos listados com sucesso');
        }

        // Caso contrário, renderiza a view
        $this->render('veiculos/index', [
            'title' => 'Listagem de Veículos'
        ]);
    }

    /**
     * POST /veiculos
     * Criar novo veículo
     */
    public function store(array $params = []): void
    {
        if (!$this->validateCsrfToken()) {
            return;
        }

        if ($this->tenantId === null) {
            $this->responseError('Tenant não identificado', 401);
        }

        $this->authorize('admin', 'operador');

        $resultado = $this->service->criar($this->tenantId, $this->allInputs());

        if ($resultado['success']) {
            $this->log('CRIAR_VEICULO', 'VEICULOS', "Veículo criado: {$this->input('placa')}", $this->allInputs());
            $this->responseSuccess($resultado, $resultado['message'], 201);
        } else {
            $this->responseError($resultado['message'] ?? 'Erro ao criar veículo', 400);
        }
    }

    /**
     * GET /veiculos/{id}
     * Detalhes do veículo
     */
    public function show(array $params = []): void
    {
        if ($this->tenantId === null && $this->userRole !== 'master') {
            $this->responseError('Tenant não identificado', 401);
        }

        $id = $params['id'] ?? null;
        if (!$id) {
            $this->responseError('ID do veículo não fornecido', 400);
        }

        if ($this->userRole === 'master') {
            $veiculo = $this->db->query("SELECT * FROM veiculos WHERE id = :id", [':id' => $id], true);
        } else {
            $veiculo = $this->repository->getByIdAndTenant((int)$id, $this->tenantId);
        }

        if (!$veiculo) {
            $this->responseError('Veículo não encontrado', 404);
        }

        $this->responseSuccess($veiculo, 'Veículo encontrado');
    }

    /**
     * PUT /veiculos/{id}
     * Atualizar veículo
     */
    public function update(array $params = []): void
    {
        if ($this->tenantId === null) {
            $this->responseError('Tenant não identificado', 401);
        }

        $this->authorize('admin', 'operador');

        $id = $params['id'] ?? null;
        if (!$id) {
            $this->responseError('ID do veículo não fornecido', 400);
        }

        $resultado = $this->service->atualizar((int)$id, $this->tenantId, $this->allInputs());

        if ($resultado['success']) {
            $this->log('ATUALIZAR_VEICULO', 'VEICULOS', "Veículo {$id} atualizado", $this->allInputs());
            $this->responseSuccess(null, $resultado['message']);
        } else {
            $this->responseError($resultado['message'], 400);
        }
    }

    /**
     * DELETE /veiculos/{id}
     * Deletar veículo (soft delete)
     */
    public function destroy(array $params = []): void
    {
        if ($this->tenantId === null) {
            $this->responseError('Tenant não identificado', 401);
        }

        $this->authorize('admin');

        $id = $params['id'] ?? null;
        if (!$id) {
            $this->responseError('ID não fornecido', 400);
        }

        $veiculo = $this->repository->getByIdAndTenant((int)$id, $this->tenantId);
        if (!$veiculo) {
            $this->responseError('Veículo não encontrado', 404);
        }

        $this->repository->softDelete((int)$id, $this->tenantId);
        $this->log('DELETAR_VEICULO', 'VEICULOS', "Veículo {$id} deletado");

        $this->responseSuccess(null, 'Veículo deletado com sucesso');
    }
}
