<?php
/**
 * CLASS RegistroUsoRepository
 * 
 * Repository para Registros de Uso (Checklists)
 */

namespace App\Repositories;

class RegistroUsoRepository extends Repository
{
    protected string $table = 'registros_uso';

    /**
     * Buscar registros por veículo
     */
    public function getByVeiculo(int $veiculoId, int $tenantId): array
    {
        $query = "
            SELECT ru.*, v.placa, v.modelo, m.nome as motorista_nome
            FROM {$this->table} ru
            JOIN veiculos v ON ru.veiculo_id = v.id
            JOIN motoristas m ON ru.motorista_id = m.id
            WHERE ru.veiculo_id = :veiculo_id AND ru.tenant_id = :tenant_id AND ru.ativo = 1
            ORDER BY ru.data_hora_inicio DESC
        ";

        return $this->db->query($query, [
            ':veiculo_id' => $veiculoId,
            ':tenant_id' => $tenantId
        ]);
    }

    /**
     * Buscar registros por motorista
     */
    public function getByMotorista(int $motoristaId, int $tenantId): array
    {
        $query = "
            SELECT ru.*, v.placa, v.modelo, u.nome as usuario_nome
            FROM {$this->table} ru
            JOIN veiculos v ON ru.veiculo_id = v.id
            JOIN usuarios u ON ru.usuario_id = u.id
            WHERE ru.motorista_id = :motorista_id AND ru.tenant_id = :tenant_id AND ru.ativo = 1
            ORDER BY ru.data_hora_inicio DESC
        ";

        return $this->db->query($query, [
            ':motorista_id' => $motoristaId,
            ':tenant_id' => $tenantId
        ]);
    }

    /**
     * Buscar por data
     */
    public function getByPeriod(int $tenantId, string $dataInicio, string $dataFim): array
    {
        $query = "
            SELECT ru.*, v.placa, v.modelo, m.nome as motorista_nome, u.nome as usuario_nome
            FROM {$this->table} ru
            JOIN veiculos v ON ru.veiculo_id = v.id
            JOIN motoristas m ON ru.motorista_id = m.id
            JOIN usuarios u ON ru.usuario_id = u.id
            WHERE ru.tenant_id = :tenant_id 
            AND DATE(ru.data_hora_inicio) BETWEEN :data_inicio AND :data_fim
            AND ru.ativo = 1
            ORDER BY ru.data_hora_inicio DESC
        ";

        return $this->db->query($query, [
            ':tenant_id' => $tenantId,
            ':data_inicio' => $dataInicio,
            ':data_fim' => $dataFim
        ]);
    }

    /**
     * Verificar se há registro aberto (sem data_hora_fim)
     */
    public function hasOpenRecord(int $veiculoId, int $tenantId)
    {
        $query = "
            SELECT * FROM {$this->table}
            WHERE veiculo_id = :veiculo_id 
            AND tenant_id = :tenant_id 
            AND data_hora_fim IS NULL 
            AND ativo = 1
            LIMIT 1
        ";

        return $this->db->query($query, [
            ':veiculo_id' => $veiculoId,
            ':tenant_id' => $tenantId
        ], true);
    }

    /**
     * Registros com avarias
     */
    public function getComAvarias(int $tenantId): array
    {
        $query = "
            SELECT ru.*, v.placa, v.modelo, m.nome as motorista_nome
            FROM {$this->table} ru
            JOIN veiculos v ON ru.veiculo_id = v.id
            JOIN motoristas m ON ru.motorista_id = m.id
            WHERE ru.tenant_id = :tenant_id 
            AND ru.status_veiculo IN ('avarias', 'critico')
            AND ru.ativo = 1
            ORDER BY ru.data_hora_inicio DESC
        ";

        return $this->db->query($query, [':tenant_id' => $tenantId]);
    }
}
