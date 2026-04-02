# 📚 DOCUMENTAÇÃO TÉCNICA - CLASSES PRINCIPAIS

## Core\Router

Sistema de rotas flexível com suporte a middlewares.

### Métodos Principais

```php
// Registrar rotas
$router->get($path, $target, $middlewares = []);
$router->post($path, $target, $middlewares = []);
$router->put($path, $target, $middlewares = []);
$router->delete($path, $target, $middlewares = []);

// Despaechar (executar router)
$router->dispatch();

// Debug
$router->getRoutes(); // Retorna todas as rotas
```

### Exemplo
```php
$router = new Router();
$router->get('/veiculos', 'VeiculoController@index', ['auth']);
$router->post('/veiculos/{id}', 'VeiculoController@update', ['auth', 'admin']);
$router->dispatch();
```

### Parâmetros Dinâmicos
```php
// URL: /veiculos/123
$router->get('/veiculos/{id}', 'VeiculoController@show');

// Controller recebe $params
public function show(array $params = []) {
    $id = $params['id']; // "123"
}
```

---

## Core\Database

Singleton para gerenciar conexão PDO.

### Métodos

```php
// Obter instância
$db = Database::getInstance();

// Executar query preparada
$stmt = $db->prepare($query);

// Query SELECT
$results = $db->query($query, $params, $fetchOne = false);

// Executar INSERT/UPDATE/DELETE
$affectedRows = $db->execute($query, $params);

// Última ID (INSERT)
$id = $db->lastInsertId($query, $params);

// Transações
$db->beginTransaction();
$db->commit();
$db->rollback();
```

### Exemplo
```php
$db = Database::getInstance();

// Query com resultado
$user = $db->query(
    "SELECT * FROM usuarios WHERE id = :id",
    [':id' => 1],
    true
);

// INSERT
$id = $db->lastInsertId(
    "INSERT INTO usuarios (name, email) VALUES (:name, :email)",
    [':name' => 'João', ':email' => 'joao@test.com']
);
```

### Prepared Statements
**SEMPRE** usar placeholders `:nome`

```php
// ✅ CORRETO
$db->query("WHERE email = :email", [':email' => $email]);

// ❌ ERRADO (SQL Injection!)
$db->query("WHERE email = '$email'");
```

---

## Core\Controller

Classe base com utilitários para responses e validação.

### Propriedades

```php
protected Database $db;        // Instância do banco
protected array $input = [];   // Todos os inputs (GET, POST, JSON)
protected ?int $tenantId;      // ID da empresa (multi-tenant)
protected ?int $userId;        // ID do usuário logado
protected ?string $userRole;   // Role: admin, operador, motorista
```

### Métodos

```php
// Input
protected function input(string $key, $default = null);
protected function allInputs(): array;

// Response JSON
protected function responseSuccess($data, string $message, int $code = 200);
protected function responseError(string $message, int $code = 400);

// Validação
protected function validate(array $fields): array;

// Autorização
protected function authorize(string ...$roles);

// Logging
protected function log(string $acao, string $modulo, string $descricao, array $dados);

// Segurança
protected function escape(string $str): string;
protected function getClientIp(): string;
```

### Exemplo de Controller

```php
class VeiculoController extends Controller {
    public function store(array $params = []): void {
        // Validar input
        $errors = $this->validate([
            'placa' => 'required|min:7|max:8',
            'modelo' => 'required'
        ]);
        if ($errors) {
            $this->responseError('Validação falhou', 400, $errors);
        }

        // Autorizar
        $this->authorize('admin');

        // Processar
        $service = new VeiculoService();
        $result = $service->criar($this->tenantId, $this->allInputs());

        // Registrar log
        $this->log('CRIAR_VEICULO', 'VEICULOS', 'Veículo criado', $result);

        // Responder
        if ($result['success']) {
            $this->responseSuccess($result, 'Veículo criado', 201);
        } else {
            $this->responseError($result['message']);
        }
    }
}
```

---

## App\Repositories\Repository

Padrão Repository genérico com operações CRUD multi-tenant.

### Métodos

```php
public function getAllByTenant(int $tenantId, int $page = 1, int $perPage = 15): array;
public function getByIdAndTenant(int $id, int $tenantId);
public function create(int $tenantId, array $data): int|false;
public function update(int $id, int $tenantId, array $data): int;
public function softDelete(int $id, int $tenantId): int;
```

### Criar Repository Específico

```php
class NovoRepository extends Repository {
    protected string $table = 'novos';
    
    public function getByName(string $name, int $tenantId) {
        $query = "SELECT * FROM {$this->table} 
                  WHERE name = :name AND tenant_id = :tenant_id 
                  AND deleted_at IS NULL LIMIT 1";
        return $this->db->query($query, [
            ':name' => $name,
            ':tenant_id' => $tenantId
        ], true);
    }
}
```

---

## App\Services (Lógica de Negócios)

Implementa validação e processamento.

### Padrão

```php
class VeiculoService {
    private VeiculoRepository $repository;
    
    public function __construct() {
        $this->repository = new VeiculoRepository();
    }
    
    public function criar(int $tenantId, array $dados): array {
        // 1. Validar
        $errors = $this->validar($dados);
        if (!empty($errors)) {
            return ['success' => false, 'errors' => $errors];
        }
        
        // 2. Processar
        $id = $this->repository->create($tenantId, [
            'placa' => strtoupper($dados['placa']),
            'modelo' => $dados['modelo']
        ]);
        
        // 3. Retornar
        return ['success' => true, 'id' => $id];
    }
}
```

---

## App\Middlewares

Executam antes do controller.

### AuthMiddleware
```php
// Verifica se usuário está autenticado
// Retorna 401 se não
```

### AdminMiddleware
```php
// Verifica se role === 'admin'
// Retorna 403 se não
```

Usar: `$router->delete('/item/{id}', 'Controller@destroy', ['auth', 'admin']);`

---

## Multi-Tenant - Como Funciona

### Isolamento de Dados

```php
// ✅ CORRETO - Sempre filtrar por tenant_id
SELECT * FROM veiculos 
WHERE tenant_id = :tenant_id 
AND deleted_at IS NULL;

// ❌ ERRADO - Vaza dados de outras empresas!
SELECT * FROM veiculos 
WHERE deleted_at IS NULL;
```

### No Controller

```php
public function index() {
    // $this->tenantId vem de $_SESSION
    $veiculos = $repo->getAllByTenant($this->tenantId);
}
```

### Super Admin

```sql
-- Super admin em tabela separada
SELECT * FROM logs; -- Pode ver TODOS os logs

-- Usuários normais com tenant_id
SELECT * FROM logs 
WHERE tenant_id = :tenant_id; -- Apenas seus logs
```

---

## Validação

### Em Controller

```php
$errors = $this->validate([
    'email' => 'required|email',
    'nome' => 'required|min:3|max:100',
    'idade' => 'required'
]);

if (! empty($errors)) {
    $this->responseError('Validação falhou', 400, $errors);
}
```

### Regras Suportadas

- `required` - Campo obrigatório
- `email` - Deve ser email válido
- `min:n` - Mínimo de caracteres
- `max:n` - Máximo de caracteres

Estender em Controller::validate()

---

## Logging

```php
$this->log($acao, $modulo, $descricao, $dados);

// Exemplos
$this->log('LOGIN', 'AUTH', "Login do usuário {$email}", []);
$this->log('CRIAR_VEICULO', 'VEICULOS', "Placa: {$placa}", ['placa' => $placa]);
$this->log('DELETE', 'USUARIOS', "Deletado usuário 123", null);

// Query registrada automaticamente
```

### Consultar Logs
```sql
SELECT * FROM logs 
WHERE tenant_id = :tenant_id 
ORDER BY created_at DESC;
```

---

## Respostas JSON

### Success
```json
{
  "status": "success",
  "message": "Operação realizada com sucesso",
  "data": { "id": 1, "nome": "Teste" }
}
```

### Error
```json
{
  "status": "error",
  "message": "Descrição do erro",
  "data": { "field1": "mensagem erro" }
}
```

### Códigos HTTP
- **201** - Created (POST sucesso)
- **200** - OK (sucesso geral)
- **400** - Bad Request (validação)
- **401** - Unauthorized (não autenticado)
- **403** - Forbidden (autorização falhou)
- **404** - Not Found (recurso não existe)
- **500** - Server Error

---

## Segurança Checklist

- ✅ PDO prepared statements (SEMPRE)
- ✅ password_hash() / password_verify()
- ✅ Validar input em Service
- ✅ Escapar output (htmlspecialchars)
- ✅ Middleware de autenticação
- ✅ Verificar tenant_id em queries
- ✅ Soft delete (não remover dados)
- ✅ Rate limiting (via .htaccess)
- ✅ HTTPS (em produção)
- ✅ CORS configurable

---

Documentação completa! Ref para desenvolvimento. 📖
