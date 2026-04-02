# 🛠️ EXEMPLO: Criar Novo Módulo (Manutenção)

Este documento mostra passo a passo como estender o sistema com um novo módulo.

**Vamos criar CRUD completo para MANUTENÇÕES**

---

## PASSO 1: Adicionar Tabela ao Banco

Já existe em `database.sql`, mas se precisasse criar:

```sql
CREATE TABLE IF NOT EXISTS manutencoes (
    id INT PRIMARY KEY AUTO_INCREMENT,
    tenant_id INT NOT NULL,
    veiculo_id INT NOT NULL,
    tipo ENUM('preventiva', 'corretiva') DEFAULT 'preventiva',
    descricao TEXT NOT NULL,
    data_inicio DATE NOT NULL,
    data_conclusao DATE,
    custo DECIMAL(10, 2),
    status ENUM('agendada', 'em_andamento', 'concluida', 'cancelada') DEFAULT 'agendada',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (tenant_id) REFERENCES empresas(id) ON DELETE CASCADE,
    FOREIGN KEY (veiculo_id) REFERENCES veiculos(id) ON DELETE CASCADE,
    INDEX idx_tenant_id (tenant_id),
    INDEX idx_veiculo_id (veiculo_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
```

---

## PASSO 2: Criar Repository

Arquivo: `app/repositories/ManutencaoRepository.php`

```php
<?php

namespace App\Repositories;

class ManutencaoRepository extends Repository
{
    protected string $table = 'manutencoes';

    /**
     * Manutenções por veículo
     */
    public function getByVeiculo(int $veiculoId, int $tenantId): array
    {
        $query = "
            SELECT m.*, v.placa, v.modelo
            FROM {$this->table} m
            JOIN veiculos v ON m.veiculo_id = v.id
            WHERE m.veiculo_id = :veiculo_id 
            AND m.tenant_id = :tenant_id
            ORDER BY m.data_inicio DESC
        ";

        return $this->db->query($query, [
            ':veiculo_id' => $veiculoId,
            ':tenant_id' => $tenantId
        ]);
    }

    /**
     * Manutenções em andamento
     */
    public function getEmAndamento(int $tenantId): array
    {
        $query = "
            SELECT m.*, v.placa FROM {$this->table} m
            JOIN veiculos v ON m.veiculo_id = v.id
            WHERE m.tenant_id = :tenant_id 
            AND m.status = 'em_andamento'
            ORDER BY m.data_inicio ASC
        ";

        return $this->db->query($query, [':tenant_id' => $tenantId]);
    }

    /**
     * Custo total por veículo
     */
    public function getCustoTotalVeiculo(int $veiculoId, int $tenantId)
    {
        $query = "
            SELECT SUM(custo) as total FROM {$this->table}
            WHERE veiculo_id = :veiculo_id 
            AND tenant_id = :tenant_id
            AND status = 'concluida'
        ";

        return $this->db->query($query, [
            ':veiculo_id' => $veiculoId,
            ':tenant_id' => $tenantId
        ], true);
    }
}
```

---

## PASSO 3: Criar Service

Arquivo: `app/services/ManutencaoService.php`

```php
<?php

namespace App\Services;

use App\Repositories\ManutencaoRepository;

class ManutencaoService
{
    private ManutencaoRepository $repository;

    public function __construct()
    {
        $this->repository = new ManutencaoRepository();
    }

    /**
     * Agendar manutenção
     */
    public function agendar(int $tenantId, array $dados): array
    {
        $errors = $this->validar($dados);
        if (!empty($errors)) {
            return ['success' => false, 'errors' => $errors];
        }

        $id = $this->repository->create($tenantId, [
            'veiculo_id' => $dados['veiculo_id'],
            'tipo' => $dados['tipo'],
            'descricao' => $dados['descricao'],
            'data_inicio' => $dados['data_inicio'],
            'custo' => $dados['custo'] ?? null,
            'status' => 'agendada'
        ]);

        return ['success' => true, 'id' => $id];
    }

    /**
     * Iniciar manutenção
     */
    public function iniciar(int $id, int $tenantId): array
    {
        $this->repository->update($id, $tenantId, [
            'status' => 'em_andamento'
        ]);

        return ['success' => true];
    }

    /**
     * Concluir manutenção
     */
    public function concluir(int $id, int $tenantId, array $dados): array
    {
        $this->repository->update($id, $tenantId, [
            'status' => 'concluida',
            'data_conclusao' => $dados['data_conclusao'] ?? date('Y-m-d'),
            'custo' => $dados['custo'] ?? null
        ]);

        return ['success' => true];
    }

    private function validar(array $dados): array
    {
        $errors = [];

        if (empty($dados['veiculo_id'])) {
            $errors['veiculo_id'] = 'Veículo obrigatório';
        }

        if (empty($dados['tipo'])) {
            $errors['tipo'] = 'Tipo obrigatório';
        } elseif (!in_array($dados['tipo'], ['preventiva', 'corretiva'])) {
            $errors['tipo'] = 'Tipo inválido';
        }

        if (empty($dados['descricao'])) {
            $errors['descricao'] = 'Descrição obrigatória';
        }

        return $errors;
    }
}
```

---

## PASSO 4: Criar Controller

Arquivo: `app/controllers/ManutencaoController.php`

```php
<?php

namespace App\Controllers;

use Core\Controller;
use App\Services\ManutencaoService;
use App\Repositories\ManutencaoRepository;

class ManutencaoController extends Controller
{
    private ManutencaoService $service;
    private ManutencaoRepository $repository;

    public function __construct()
    {
        parent::__construct();
        $this->service = new ManutencaoService();
        $this->repository = new ManutencaoRepository();
    }

    /**
     * GET /manutencoes
     */
    public function index(array $params = []): void
    {
        session_start();

        if ($this->tenantId === null) {
            $this->responseError('Não autenticado', 401);
        }

        $page = $this->input('page', 1);
        $resultado = $this->repository->getAllByTenant($this->tenantId, $page);

        $this->responseSuccess($resultado, 'Manutenções listadas');
    }

    /**
     * GET /manutencoes/em-andamento
     */
    public function emAndamento(array $params = []): void
    {
        session_start();

        if ($this->tenantId === null) {
            $this->responseError('Não autenticado', 401);
        }

        $this->authorize('admin', 'operador');

        $resultado = $this->repository->getEmAndamento($this->tenantId);
        $this->responseSuccess($resultado, 'Manutenções em andamento');
    }

    /**
     * POST /manutencoes
     */
    public function store(array $params = []): void
    {
        session_start();

        if ($this->tenantId === null) {
            $this->responseError('Não autenticado', 401);
        }

        $this->authorize('admin', 'operador');

        $resultado = $this->service->agendar($this->tenantId, $this->allInputs());

        if ($resultado['success']) {
            $this->log('AGENDAR_MANUTENCAO', 'MANUTENCOES', 'Manutenção agendada', $this->allInputs());
            $this->responseSuccess($resultado, 'Manutenção agendada', 201);
        } else {
            $this->responseError($resultado['errors'][0] ?? 'Erro', 400);
        }
    }

    /**
     * PUT /manutencoes/{id}/iniciar
     */
    public function iniciar(array $params = []): void
    {
        session_start();

        if ($this->tenantId === null) {
            $this->responseError('Não autenticado', 401);
        }

        $this->authorize('admin', 'operador');

        $id = $params['id'] ?? null;
        if (!$id) {
            $this->responseError('ID não fornecido', 400);
        }

        $resultado = $this->service->iniciar((int)$id, $this->tenantId);

        if ($resultado['success']) {
            $this->log('INICIAR_MANUTENCAO', 'MANUTENCOES', "Manutenção {$id} iniciada");
            $this->responseSuccess(null, 'Manutenção iniciada');
        } else {
            $this->responseError('Erro ao iniciar', 400);
        }
    }

    /**
     * PUT /manutencoes/{id}/concluir
     */
    public function concluir(array $params = []): void
    {
        session_start();

        if ($this->tenantId === null) {
            $this->responseError('Não autenticado', 401);
        }

        $this->authorize('admin', 'operador');

        $id = $params['id'] ?? null;
        if (!$id) {
            $this->responseError('ID não fornecido', 400);
        }

        $resultado = $this->service->concluir((int)$id, $this->tenantId, $this->allInputs());

        if ($resultado['success']) {
            $this->log('CONCLUIR_MANUTENCAO', 'MANUTENCOES', "Manutenção {$id} concluída");
            $this->responseSuccess(null, 'Manutenção concluída');
        } else {
            $this->responseError('Erro', 400);
        }
    }

    /**
     * GET /manutencoes/veiculo/{id}
     */
    public function porVeiculo(array $params = []): void
    {
        session_start();

        if ($this->tenantId === null) {
            $this->responseError('Não autenticado', 401);
        }

        $veiculoId = $params['id'] ?? null;
        if (!$veiculoId) {
            $this->responseError('ID do veículo não fornecido', 400);
        }

        $resultado = $this->repository->getByVeiculo((int)$veiculoId, $this->tenantId);
        $this->responseSuccess($resultado, 'Manutenções do veículo');
    }
}
```

---

## PASSO 5: Adicionar Rotas

Editar `routes/api.php`:

```php
// Manutenções
$router->get('/manutencoes', 'ManutencaoController@index', ['auth']);
$router->get('/manutencoes/em-andamento', 'ManutencaoController@emAndamento', ['auth']);
$router->get('/manutencoes/veiculo/{id}', 'ManutencaoController@porVeiculo', ['auth']);
$router->post('/manutencoes', 'ManutencaoController@store', ['auth', 'admin']);
$router->put('/manutencoes/{id}/iniciar', 'ManutencaoController@iniciar', ['auth', 'admin']);
$router->put('/manutencoes/{id}/concluir', 'ManutencaoController@concluir', ['auth', 'admin']);
```

---

## PASSO 6: Testar

```bash
# Registrar empresa e fazer login (como antes)

# Criar manutenção
curl -X POST http://localhost/controle-frota/public/manutencoes \
  -H "Content-Type: application/json" \
  -b cookies.txt \
  -d '{
    "veiculo_id": 1,
    "tipo": "preventiva",
    "descricao": "Manutenção preventiva 10k km",
    "data_inicio": "2024-06-01"
  }'

# Listar em andamento
curl http://localhost/controle-frota/public/manutencoes/em-andamento \
  -b cookies.txt

# Iniciar
curl -X PUT http://localhost/controle-frota/public/manutencoes/1/iniciar \
  -b cookies.txt

# Concluir
curl -X PUT http://localhost/controle-frota/public/manutencoes/1/concluir \
  -H "Content-Type: application/json" \
  -b cookies.txt \
  -d '{
    "data_conclusao": "2024-06-02",
    "custo": 500.00
  }'
```

---

## ✨ Padrão Seguido

```
1. Tabela SQL (com tenant_id + índices)
2. Repository (queries específicas)
3. Service (validação + lógica)
4. Controller (API endpoints)
5. Rotas (registrar em api.php)
6. Teste (cURL)
```

**Este é o padrão para qualquer novo módulo!**

---

Qualquer novo CRUD segue exatamente este fluxo. Copiar e adaptar! 🚀
