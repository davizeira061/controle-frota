<?php
/**
 * LogController
 * 
 * Auditoria de logs (Master e Empresa)
 */

namespace App\Controllers;

use Core\Controller;

class LogController extends Controller
{
    /**
     * GET /admin/logs
     * Listar logs (Master vê tudo, Admin vê apenas o seu)
     */
    public function index(): void
    {
        $this->authorize('admin', 'master');
        
        $page = $this->input('page', 1);
        $perPage = 20;
        $offset = ($page - 1) * $perPage;

        if (strpos($_SERVER['HTTP_ACCEPT'] ?? '', 'application/json') !== false || $this->input('api')) {
            if ($this->userRole === 'master') {
                $query = "
                    SELECT l.*, u.nome as usuario_nome, e.nome as empresa_nome
                    FROM logs l
                    LEFT JOIN usuarios u ON l.user_id = u.id
                    LEFT JOIN empresas e ON l.tenant_id = e.id
                    ORDER BY l.created_at DESC
                    LIMIT :limit OFFSET :offset
                ";
                $params = [':limit' => $perPage, ':offset' => $offset];
                $countQuery = "SELECT COUNT(*) as total FROM logs";
                $countParams = [];
            } else {
                $query = "
                    SELECT l.*, u.nome as usuario_nome
                    FROM logs l
                    LEFT JOIN usuarios u ON l.user_id = u.id
                    WHERE l.tenant_id = :tenant_id
                    ORDER BY l.created_at DESC
                    LIMIT :limit OFFSET :offset
                ";
                $params = [':tenant_id' => $this->tenantId, ':limit' => $perPage, ':offset' => $offset];
                $countQuery = "SELECT COUNT(*) as total FROM logs WHERE tenant_id = :tenant_id";
                $countParams = [':tenant_id' => $this->tenantId];
            }

            $logs = $this->db->query($query, $params);
            $total = $this->db->query($countQuery, $countParams, true)['total'];

            $this->responseSuccess([
                'data' => $logs,
                'total' => $total,
                'page' => $page,
                'pages' => ceil($total / $perPage)
            ], 'Logs recuperados com sucesso');
        }

        $this->render('admin/logs', [
            'title' => 'Auditoria Global de Logs - MASTER'
        ]);
    }
}
