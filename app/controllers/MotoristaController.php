<?php
/**
 * MotoristaController
 * 
 * Endpoints de motoristas
 */

namespace App\Controllers;

use Core\Controller;
use App\Services\MotoristaService;
use App\Repositories\MotoristaRepository;

class MotoristaController extends Controller
{
    private MotoristaService $service;
    private MotoristaRepository $repository;

    public function __construct()
    {
        parent::__construct();
        $this->service = new MotoristaService();
        $this->repository = new MotoristaRepository();
    }

    /**
     * GET /motoristas
     */
    public function index(array $params = []): void
    {
        if ($this->tenantId === null && $this->userRole !== 'master') {
            $this->responseError('Tenant não identificado', 401);
        }

        $page = $this->input('page', 1);
        
        if (strpos($_SERVER['HTTP_ACCEPT'] ?? '', 'application/json') !== false || $this->input('api')) {
            if ($this->userRole === 'master') {
                $offset = ($page - 1) * 15;
                $results = $this->db->query("SELECT m.*, e.nome as empresa_nome FROM motoristas m JOIN empresas e ON m.tenant_id = e.id WHERE m.deleted_at IS NULL ORDER BY m.created_at DESC LIMIT 15 OFFSET :offset", [
                    ':offset' => $offset
                ]);
                $total = $this->db->query("SELECT COUNT(*) as total FROM motoristas WHERE deleted_at IS NULL", [], true)['total'];
                $resultado = [
                    'data' => $results,
                    'total' => $total,
                    'page' => $page,
                    'pages' => ceil($total / 15)
                ];
            } else {
                $resultado = $this->repository->getAllByTenant($this->tenantId, $page);
            }

            $this->responseSuccess($resultado, 'Motoristas listados');
        }

        $this->render('motoristas/index', [
            'title' => 'Gestão de Motoristas - Controle de Frota'
        ]);
    }

    /**
     * POST /motoristas
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
            $this->log('CRIAR_MOTORISTA', 'MOTORISTAS', "Motorista criado: {$this->input('nome')}", $this->allInputs());
            $this->responseSuccess($resultado, $resultado['message'], 201);
        } else {
            $this->responseError($resultado['message'] ?? 'Erro', 400);
        }
    }

    /**
     * GET /motoristas/{id}
     */
    public function show(array $params = []): void
    {
        if ($this->tenantId === null && $this->userRole !== 'master') {
            $this->responseError('Tenant não identificado', 401);
        }

        $id = $params['id'] ?? null;
        if (!$id) {
            $this->responseError('ID não fornecido', 400);
        }

        if ($this->userRole === 'master') {
            $motorista = $this->db->query("SELECT * FROM motoristas WHERE id = :id", [':id' => $id], true);
        } else {
            $motorista = $this->repository->getByIdAndTenant((int)$id, $this->tenantId);
        }

        if (!$motorista) {
            $this->responseError('Motorista não encontrado', 404);
        }

        $this->responseSuccess($motorista);
    }
}
