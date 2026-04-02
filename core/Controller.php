<?php
/**
 * CLASS Controller
 * 
 * Classe base para todos os controllers
 * Fornece métodos úteis para resposta JSON e acesso a dados da requisição
 */

namespace Core;

use Core\Database;

class Controller
{
    protected Database $db;
    protected array $input = [];
    protected ?int $tenantId = null;
    protected ?int $userId = null;
    protected ?string $userRole = null;

    public function __construct()
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        $this->db = Database::getInstance();
        $this->parseInput();
        $this->resolveTenantAndUser();
        $this->generateCsrfToken();
    }

    /**
     * Gera e armazena token CSRF na sessão se não existir
     */
    protected function generateCsrfToken(): void
    {
        if (empty($_SESSION['csrf_token'])) {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        }
    }

    /**
     * Verifica se o token CSRF enviado é válido
     */
    protected function validateCsrfToken(): bool
    {
        // Ignorar em requisições GET
        if ($_SERVER['REQUEST_METHOD'] === 'GET') {
            return true;
        }

        $token = $this->input('csrf_token') ?? $_SERVER['HTTP_X_CSRF_TOKEN'] ?? null;

        if (!$token || $token !== ($_SESSION['csrf_token'] ?? null)) {
            $this->responseError('Token CSRF inválido ou ausente.', 403);
            return false;
        }

        return true;
    }

    /**
     * Parse input from REQUEST (GET, POST, JSON)
     */
    protected function parseInput(): void
    {
        // GET
        $this->input = array_merge($this->input, $_GET ?? []);

        // POST
        if ($_SERVER['REQUEST_METHOD'] === 'POST' || $_SERVER['REQUEST_METHOD'] === 'PUT') {
            $this->input = array_merge($this->input, $_POST ?? []);
        }

        // JSON
        $json = file_get_contents('php://input');
        if ($json) {
            $decoded = json_decode($json, true);
            if (is_array($decoded)) {
                $this->input = array_merge($this->input, $decoded);
            }
        }
    }

    /**
     * Resolve tenant_id e user_id from session
     */
    protected function resolveTenantAndUser(): void
    {
        if (isset($_SESSION['user_id'])) {
            $this->userId = (int) $_SESSION['user_id'];
            $this->tenantId = (int) ($_SESSION['tenant_id'] ?? null);
            $this->userRole = $_SESSION['role'] ?? null;
        }
    }

    /**
     * Retorna valor do input
     */
    protected function input(string $key, $default = null)
    {
        return $this->input[$key] ?? $default;
    }

    /**
     * Todos os inputs
     */
    protected function allInputs(): array
    {
        return $this->input;
    }

    /**
     * Renderiza uma view PHP
     */
    protected function render(string $view, array $data = []): void
    {
        $viewPath = BASE_PATH . '/app/views/' . $view . '.php';

        if (!file_exists($viewPath)) {
            die("View não encontrada: {$view}");
        }

        // Adicionar base_path para o JS
        $scriptName = $_SERVER['SCRIPT_NAME'];
        $basePath = str_replace('\\', '/', dirname($scriptName));
        if ($basePath === '/') $basePath = '';
        $data['baseUrl'] = $basePath;
        $data['csrfToken'] = $_SESSION['csrf_token'] ?? '';

        // Extrair dados para variáveis
        extract($data);

        // Header padrão para HTML
        header('Content-Type: text/html; charset=utf-8');

        require $viewPath;
        exit;
    }

    /**
     * Response JSON com sucesso
     */
    protected function responseSuccess($data = null, string $message = 'Sucesso', int $statusCode = 200): void
    {
        http_response_code($statusCode);
        echo json_encode([
            'status' => 'success',
            'message' => $message,
            'data' => $data
        ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
        exit;
    }

    /**
     * Response JSON com erro
     */
    protected function responseError(string $message, int $statusCode = 400, $data = null): void
    {
        http_response_code($statusCode);
        echo json_encode([
            'status' => 'error',
            'message' => $message,
            'data' => $data
        ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
        exit;
    }

    /**
     * Validar se campo é obrigatório
     */
    protected function validate(array $fields): array
    {
        $errors = [];
        
        foreach ($fields as $field => $rules) {
            $rules = explode('|', $rules);
            
            foreach ($rules as $rule) {
                $value = $this->input($field);
                
                if (str_contains($rule, ':')) {
                    [$ruleName, $param] = explode(':', $rule);
                } else {
                    $ruleName = $rule;
                    $param = null;
                }

                switch ($ruleName) {
                    case 'required':
                        if (empty($value) && $value !== '0') {
                            $errors[$field][] = "{$field} é obrigatório";
                        }
                        break;

                    case 'email':
                        if ($value && !filter_var($value, FILTER_VALIDATE_EMAIL)) {
                            $errors[$field][] = "{$field} deve ser um email válido";
                        }
                        break;

                    case 'min':
                        if ($value && strlen((string)$value) < (int)$param) {
                            $errors[$field][] = "{$field} deve ter no mínimo {$param} caracteres";
                        }
                        break;

                    case 'max':
                        if ($value && strlen((string)$value) > (int)$param) {
                            $errors[$field][] = "{$field} deve ter no máximo {$param} caracteres";
                        }
                        break;

                    case 'unique':
                        // Implementado em services específicos
                        break;
                }
            }
        }

        return $errors;
    }

    /**
     * Log de ação
     */
    protected function log(string $acao, string $modulo, string $descricao = '', array $dados = []): void
    {
        $query = "
            INSERT INTO logs (user_id, tenant_id, acao, modulo, descricao, ip, user_agent, dados_novos)
            VALUES (:user_id, :tenant_id, :acao, :modulo, :descricao, :ip, :user_agent, :dados_novos)
        ";

        $this->db->execute($query, [
            ':user_id' => $this->userId,
            ':tenant_id' => $this->tenantId,
            ':acao' => $acao,
            ':modulo' => $modulo,
            ':descricao' => $descricao,
            ':ip' => $this->getClientIp(),
            ':user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? '',
            ':dados_novos' => json_encode($dados)
        ]);
    }

    /**
     * Get client IP
     */
    protected function getClientIp(): string
    {
        if (!empty($_SERVER['HTTP_CLIENT_IP'])) {
            $ip = $_SERVER['HTTP_CLIENT_IP'];
        } elseif (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
            $ip = explode(',', $_SERVER['HTTP_X_FORWARDED_FOR'])[0];
        } else {
            $ip = $_SERVER['REMOTE_ADDR'] ?? '';
        }

        return htmlspecialchars($ip);
    }

    /**
     * Verificar autorização (role-based)
     */
    protected function authorize(string ...$roles): bool
    {
        if (!in_array($this->userRole, $roles)) {
            $this->responseError('Acesso negado', 403);
        }
        return true;
    }

    /**
     * Escape HTML output
     */
    protected function escape(string $str): string
    {
        return htmlspecialchars($str, ENT_QUOTES, 'UTF-8');
    }
}
