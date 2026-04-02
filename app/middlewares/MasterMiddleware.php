<?php
/**
 * CLASS MasterMiddleware
 * 
 * Middleware para verificar se usuário é Super Admin (Master)
 * Retorna erro 403 se não for master
 */

namespace App\Middlewares;

class MasterMiddleware
{
    public function handle(): void
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'master') {
            http_response_code(403);
            header('Content-Type: application/json');
            echo json_encode([
                'status' => 'error',
                'message' => 'Acesso negado. Requer permissão MASTER.'
            ]);
            exit;
        }
    }
}
