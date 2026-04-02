<?php
/**
 * DashboardController
 * 
 * Gerencia a exibição das estatísticas e visões gerais por perfil
 */

namespace App\Controllers;

use Core\Controller;

class DashboardController extends Controller
{
    public function index(): void
    {
        // Se for requisição JSON (API)
        if (strpos($_SERVER['HTTP_ACCEPT'] ?? '', 'application/json') !== false || $this->input('api')) {
            $stats = $this->getStatsByRole();
            $this->responseSuccess($stats, 'Estatísticas do dashboard');
        }

        // Renderiza a view baseada no papel do usuário
        $view = 'dashboard/default';
        if ($this->userRole === 'master') {
            $view = 'dashboard/master';
        } elseif ($this->userRole === 'admin') {
            $view = 'dashboard/admin';
        } elseif ($this->userRole === 'operador') {
            $view = 'dashboard/operador';
        } elseif ($this->userRole === 'motorista') {
            $view = 'dashboard/motorista';
        }

        $this->render($view, [
            'title' => 'Dashboard - Controle de Frota',
            'role' => $this->userRole
        ]);
    }

    private function getStatsByRole(): array
    {
        $stats = [];

        if ($this->userRole === 'master') {
            $stats['empresas_total'] = $this->db->query("SELECT COUNT(*) as total FROM empresas", [], true)['total'];
            $stats['empresas_ativas'] = $this->db->query("SELECT COUNT(*) as total FROM empresas WHERE deleted_at IS NULL", [], true)['total'];
            $stats['logs_total'] = $this->db->query("SELECT COUNT(*) as total FROM logs", [], true)['total'];
        } else {
            // Stats para tenant
            $stats['veiculos_total'] = $this->db->query(
                "SELECT COUNT(*) as total FROM veiculos WHERE tenant_id = :tid AND deleted_at IS NULL", 
                [':tid' => $this->tenantId], 
                true
            )['total'];
            
            $stats['motoristas_total'] = $this->db->query(
                "SELECT COUNT(*) as total FROM motoristas WHERE tenant_id = :tid AND deleted_at IS NULL", 
                [':tid' => $this->tenantId], 
                true
            )['total'];

            $stats['usos_ativos'] = $this->db->query(
                "SELECT COUNT(*) as total FROM registros_uso WHERE tenant_id = :tid AND data_hora_fim IS NULL", 
                [':tid' => $this->tenantId], 
                true
            )['total'];

            if ($this->userRole === 'motorista') {
                $stats['meus_usos'] = $this->db->query(
                    "SELECT COUNT(*) as total FROM registros_uso WHERE tenant_id = :tid AND motorista_id = (SELECT id FROM motoristas WHERE email = :email LIMIT 1)", 
                    [':tid' => $this->tenantId, ':email' => $_SESSION['email']], 
                    true
                )['total'];
            }
        }

        return $stats;
    }
}
