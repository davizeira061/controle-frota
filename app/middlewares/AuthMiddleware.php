<?php
/**
 * CLASS AuthMiddleware
 * 
 * Middleware de autenticação
 * Verifica se usuário está autenticado via sessão
 * Retorna erro 401 se não autenticado
 */

namespace App\Middlewares;

class AuthMiddleware
{
    public function handle(): void
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        if (!isset($_SESSION['user_id'])) {
            // Se for requisição API
            if (strpos($_SERVER['HTTP_ACCEPT'] ?? '', 'application/json') !== false || isset($_GET['api'])) {
                http_response_code(401);
                header('Content-Type: application/json');
                echo json_encode([
                    'status' => 'error',
                    'message' => 'Não autenticado'
                ]);
                exit;
            }

            // Se for requisição de página, redireciona para login
            header('Location: ' . $this->getBaseUrl() . '/');
            exit;
        }

        // Forçar troca de senha se necessário (exceto se já estiver na rota de troca ou for logout)
        $currentUri = $_SERVER['REQUEST_URI'];
        $baseUrl = $this->getBaseUrl();
        if (isset($_SESSION['senha_temporaria']) && $_SESSION['senha_temporaria'] == 1) {
            if (strpos($currentUri, '/change-password') === false && strpos($currentUri, '/logout') === false) {
                header('Location: ' . $baseUrl . '/change-password');
                exit;
            }
        }
    }

    private function getBaseUrl(): string
    {
        $scriptName = $_SERVER['SCRIPT_NAME'];
        $basePath = str_replace('\\', '/', dirname($scriptName));
        return ($basePath === '/') ? '' : $basePath;
    }
}
