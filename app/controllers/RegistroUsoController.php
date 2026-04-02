<?php
/**
 * RegistroUsoController
 * 
 * Endpoints para Registros de Uso (Checklists)
 */

namespace App\Controllers;

use Core\Controller;
use App\Services\RegistroUsoService;
use App\Repositories\RegistroUsoRepository;

class RegistroUsoController extends Controller
{
    private RegistroUsoService $service;
    private RegistroUsoRepository $repository;

    public function __construct()
    {
        parent::__construct();
        $this->service = new RegistroUsoService();
        $this->repository = new RegistroUsoRepository();
    }

    /**
     * GET /registros
     * Listar registros de uso
     */
    public function index(array $params = []): void
    {
        if ($this->tenantId === null && $this->userRole !== 'master') {
            $this->responseError('Tenant não identificado', 401);
        }

        $page = $this->input('page', 1);
        
        if (strpos($_SERVER['HTTP_ACCEPT'] ?? '', 'application/json') !== false || $this->input('api')) {
            if ($this->userRole === 'master') {
                // Master vê tudo
                $offset = ($page - 1) * 15;
                $results = $this->db->query("SELECT ru.*, e.nome as empresa_nome, v.placa FROM registros_uso ru JOIN empresas e ON ru.tenant_id = e.id JOIN veiculos v ON ru.veiculo_id = v.id ORDER BY ru.created_at DESC LIMIT 15 OFFSET :offset", [
                    ':offset' => $offset
                ]);
                $total = $this->db->query("SELECT COUNT(*) as total FROM registros_uso", [], true)['total'];
                $resultado = [
                    'data' => $results,
                    'total' => $total,
                    'page' => $page,
                    'pages' => ceil($total / 15)
                ];
            } else {
                $resultado = $this->repository->getAllByTenant($this->tenantId, $page);
            }

            $this->responseSuccess($resultado, 'Registros listados');
        }

        $this->render('registros/index', [
            'title' => 'Registros de Uso - Controle de Frota',
            'role' => $this->userRole
        ]);
    }

    /**
     * POST /registros
     * Iniciar novo registro de uso (checklist)
     */
    public function store(array $params = []): void
    {
        if (!$this->validateCsrfToken()) {
            return;
        }

        if ($this->tenantId === null || $this->userId === null) {
            $this->responseError('Não autenticado ou sem tenant', 401);
        }

        $resultado = $this->service->iniciar($this->tenantId, $this->userId, $this->allInputs());

        if ($resultado['success']) {
            $this->log('INICIAR_REGISTRO', 'REGISTROS', 'Novo registro iniciado', $this->allInputs());
            $this->responseSuccess($resultado, $resultado['message'], 201);
        } else {
            $this->responseError($resultado['message'] ?? 'Erro ao criar registro', 400);
        }
    }

    /**
     * GET /registros/{id}
     * Detalhes de um registro
     */
    public function show(array $params = []): void
    {
        $id = $params['id'] ?? null;
        if (!$id) {
            $this->responseError('ID não fornecido', 400);
        }

        if ($this->userRole === 'master') {
            $registro = $this->db->query("SELECT * FROM registros_uso WHERE id = :id", [':id' => $id], true);
        } else {
            $registro = $this->repository->getByIdAndTenant((int)$id, $this->tenantId);
        }

        if (!$registro) {
            $this->responseError('Registro não encontrado', 404);
        }

        $this->responseSuccess($registro);
    }

    /**
     * PUT /registros/{id}
     * Finalizar registro de uso
     */
    public function update(array $params = []): void
    {
        if (!$this->validateCsrfToken()) {
            return;
        }

        if ($this->tenantId === null || $this->userId === null) {
            $this->responseError('Não autenticado', 401);
        }

        $id = $params['id'] ?? null;
        if (!$id) {
            $this->responseError('ID não fornecido', 400);
        }

        $resultado = $this->service->finalizar((int)$id, $this->tenantId, $this->userId, $this->allInputs());

        if ($resultado['success']) {
            $this->log('FINALIZAR_REGISTRO', 'REGISTROS', "Registro {$id} finalizado", $this->allInputs());
            $this->responseSuccess(null, $resultado['message']);
        } else {
            $this->responseError($resultado['message'] ?? 'Erro', 400);
        }
    }

    /**
     * GET /registros/com-avarias
     * Registros com avarias
     */
    public function comAvarias(array $params = []): void
    {
        if ($this->tenantId === null && $this->userRole !== 'master') {
            $this->responseError('Tenant não identificado', 401);
        }

        $this->authorize('admin', 'operador', 'master');

        if ($this->userRole === 'master') {
            $registros = $this->db->query("SELECT * FROM registros_uso WHERE status_veiculo IN ('avarias', 'critico')");
        } else {
            $registros = $this->repository->getComAvarias($this->tenantId);
        }
        $this->responseSuccess($registros, 'Registros com avarias');
    }
}
