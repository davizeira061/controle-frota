<?php
/**
 * CLASS AdminMiddleware
 * 
 * Middleware para verificar se usuário é admin
 * Retorna erro 403 se não for admin
 */

namespace App\Middlewares;

class AdminMiddleware
{
    public function handle(): void
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        // Master também tem permissão de admin
        if (!isset($_SESSION['role']) || !in_array($_SESSION['role'], ['admin', 'master'])) {
            http_response_code(403);
            header('Content-Type: application/json');
            echo json_encode([
                'status' => 'error',
                'message' => 'Acesso negado. Requer permissão de admin'
            ]);
            exit;
        }
    }
}
