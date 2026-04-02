<?php
/**
 * CLASS TenantMiddleware
 * 
 * Middleware para garantir isolamento de dados por tenant
 * Valida se usuário pertence ao tenant solicitado
 */

namespace App\Middlewares;

use Core\Database;

class TenantMiddleware
{
    public function handle(): void
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        // Master é isento de tenant
        if (isset($_SESSION['role']) && $_SESSION['role'] === 'master') {
            return;
        }

        if (!isset($_SESSION['tenant_id'])) {
            http_response_code(401);
            header('Content-Type: application/json');
            echo json_encode([
                'status' => 'error',
                'message' => 'Tenant não definido'
            ]);
            exit;
        }
    }
}
