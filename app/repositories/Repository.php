<?php
/**
 * CLASS Repository
 * 
 * Padrão Repository - Base abstrata para todos os repositórios
 * Implementa operações CRUD genéricas com filtro por tenant
 */

namespace App\Repositories;

use Core\Database;

abstract class Repository
{
    protected Database $db;
    protected string $table;

    public function __construct()
    {
        $this->db = Database::getInstance();
    }

    /**
     * Buscar todos os registros por tenant (com paginação)
     */
    public function getAllByTenant(int $tenantId, int $page = 1, int $perPage = 15): array
    {
        $offset = ($page - 1) * $perPage;

        $query = "
            SELECT *
            FROM {$this->table}
            WHERE tenant_id = :tenant_id AND deleted_at IS NULL
            ORDER BY created_at DESC
            LIMIT :limit OFFSET :offset
        ";

        $results = $this->db->query($query, [
            ':tenant_id' => $tenantId,
            ':limit' => $perPage,
            ':offset' => $offset
        ]);

        // Total de registros
        $countQuery = "SELECT COUNT(*) as total FROM {$this->table} WHERE tenant_id = :tenant_id AND deleted_at IS NULL";
        $countResult = $this->db->query($countQuery, [':tenant_id' => $tenantId], true);

        return [
            'data' => $results,
            'total' => $countResult['total'],
            'page' => $page,
            'per_page' => $perPage,
            'pages' => ceil($countResult['total'] / $perPage)
        ];
    }

    /**
     * Buscar registro por ID e tenant
     */
    public function getByIdAndTenant(int $id, int $tenantId)
    {
        $query = "SELECT * FROM {$this->table} WHERE id = :id AND tenant_id = :tenant_id AND deleted_at IS NULL LIMIT 1";

        return $this->db->query($query, [
            ':id' => $id,
            ':tenant_id' => $tenantId
        ], true);
    }

    /**
     * Criar novo registro
     */
    public function create(int $tenantId, array $data): int|false
    {
        $data['tenant_id'] = $tenantId;
        $data['created_at'] = date('Y-m-d H:i:s');

        $columns = array_keys($data);
        $placeholders = ':' . implode(', :', $columns);

        $query = "INSERT INTO {$this->table} (" . implode(', ', $columns) . ") VALUES (" . $placeholders . ")";

        return $this->db->lastInsertId($query, $this->preparePlaceholders($data));
    }

    /**
     * Atualizar registro
     */
    public function update(int $id, int $tenantId, array $data): int
    {
        $data['updated_at'] = date('Y-m-d H:i:s');

        $sets = [];
        foreach ($data as $key => $value) {
            $sets[] = "{$key} = :{$key}";
        }

        $query = "UPDATE {$this->table} SET " . implode(', ', $sets) . " WHERE id = :id AND tenant_id = :tenant_id";

        $data[':id'] = $id;
        $data[':tenant_id'] = $tenantId;

        return $this->db->execute($query, $this->preparePlaceholders($data));
    }

    /**
     * Deletar soft delete
     */
    public function softDelete(int $id, int $tenantId): int
    {
        $query = "UPDATE {$this->table} SET deleted_at = NOW() WHERE id = :id AND tenant_id = :tenant_id";

        return $this->db->execute($query, [
            ':id' => $id,
            ':tenant_id' => $tenantId
        ]);
    }

    /**
     * Preparar placeholders com ':' prefix
     */
    private function preparePlaceholders(array $data): array
    {
        $result = [];
        foreach ($data as $key => $value) {
            if (!str_starts_with($key, ':')) {
                $result[':' . $key] = $value;
            } else {
                $result[$key] = $value;
            }
        }
        return $result;
    }
}
