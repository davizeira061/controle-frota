<?php
/**
 * CLASS VeiculoRepository
 * 
 * Repository específico para Veículos
 */

namespace App\Repositories;

class VeiculoRepository extends Repository
{
    protected string $table = 'veiculos';

    /**
     * Buscar veículo por placa
     */
    public function getByPlaca(string $placa, int $tenantId)
    {
        $query = "
            SELECT * FROM {$this->table}
            WHERE placa = :placa AND tenant_id = :tenant_id AND deleted_at IS NULL
            LIMIT 1
        ";

        return $this->db->query($query, [
            ':placa' => $placa,
            ':tenant_id' => $tenantId
        ], true);
    }

    /**
     * Buscar veículos ativos
     */
    public function getAtivos(int $tenantId): array
    {
        $query = "
            SELECT * FROM {$this->table}
            WHERE tenant_id = :tenant_id AND ativo = 1 AND deleted_at IS NULL
            ORDER BY placa ASC
        ";

        return $this->db->query($query, [':tenant_id' => $tenantId]);
    }

    /**
     * Buscar por status
     */
    public function getByStatus(int $tenantId, string $status): array
    {
        $query = "
            SELECT * FROM {$this->table}
            WHERE tenant_id = :tenant_id AND status = :status AND deleted_at IS NULL
            ORDER BY placa ASC
        ";

        return $this->db->query($query, [
            ':tenant_id' => $tenantId,
            ':status' => $status
        ]);
    }

    /**
     * Atualizar quilometragem
     */
    public function updateQuilometragem(int $id, int $tenantId, int $novaQuilometragem): int
    {
        $query = "
            UPDATE {$this->table}
            SET quilometragem_total = :quilometragem, updated_at = NOW()
            WHERE id = :id AND tenant_id = :tenant_id
        ";

        return $this->db->execute($query, [
            ':quilometragem' => $novaQuilometragem,
            ':id' => $id,
            ':tenant_id' => $tenantId
        ]);
    }
}
