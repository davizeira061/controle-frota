<?php
/**
 * CLASS MotoristaRepository
 * 
 * Repository específico para Motoristas
 */

namespace App\Repositories;

class MotoristaRepository extends Repository
{
    protected string $table = 'motoristas';

    /**
     * Buscar motorista por CPF
     */
    public function getByCpf(string $cpf, int $tenantId)
    {
        $query = "
            SELECT * FROM {$this->table}
            WHERE cpf = :cpf AND tenant_id = :tenant_id AND deleted_at IS NULL
            LIMIT 1
        ";

        return $this->db->query($query, [
            ':cpf' => $cpf,
            ':tenant_id' => $tenantId
        ], true);
    }

    /**
     * Buscar motoristas ativos
     */
    public function getAtivos(int $tenantId): array
    {
        $query = "
            SELECT * FROM {$this->table}
            WHERE tenant_id = :tenant_id AND ativo = 1 AND deleted_at IS NULL
            ORDER BY nome ASC
        ";

        return $this->db->query($query, [':tenant_id' => $tenantId]);
    }
}
