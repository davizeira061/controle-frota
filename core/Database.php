<?php
/**
 * CLASS Database
 * 
 * Gerencia conexões PDO com o banco de dados
 * Implementa Singleton para garantir uma única conexão
 */

namespace Core;

use PDO;
use PDOException;

class Database
{
    private static ?Database $instance = null;
    private ?PDO $connection = null;
    private string $host;
    private string $dbname;
    private string $username;
    private string $password;
    private string $charset = 'utf8mb4';

    /**
     * Construtor privado - Singleton
     */
    private function __construct()
    {
        $this->host = $_ENV['DB_HOST'] ?? 'localhost';
        $this->dbname = $_ENV['DB_NAME'] ?? 'controle_frota';
        $this->username = $_ENV['DB_USER'] ?? 'root';
        $this->password = $_ENV['DB_PASS'] ?? '';
    }

    /**
     * Retorna instância única da classe
     */
    public static function getInstance(): Database
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Estabelece conexão com banco de dados
     */
    public function connect(): PDO
    {
        if ($this->connection !== null) {
            return $this->connection;
        }

        try {
            $dsn = "mysql:host={$this->host};dbname={$this->dbname};charset={$this->charset}";
            
            $this->connection = new PDO(
                $dsn,
                $this->username,
                $this->password,
                [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                    PDO::ATTR_EMULATE_PREPARES => false,
                    PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8mb4"
                ]
            );

            return $this->connection;
        } catch (PDOException $e) {
            throw new \Exception('Erro de conexão com o banco de dados: ' . $e->getMessage());
        }
    }

    /**
     * Retorna a conexão PDO
     */
    public function getConnection(): PDO
    {
        return $this->connect();
    }

    /**
     * Executa query preparada com segurança
     * 
     * @param string $query SQL query com placeholders :name
     * @param array $params Parâmetros para bind
     * @return \PDOStatement
     */
    public function prepare(string $query): \PDOStatement
    {
        return $this->connect()->prepare($query);
    }

    /**
     * Executa SELECT com prepared statement
     * 
     * @param string $query SQL query
     * @param array $params Parâmetros
     * @param bool $fetchOne Se true, retorna um registro. Se false, retorna array
     * @return array|null
     */
    public function query(string $query, array $params = [], bool $fetchOne = false)
    {
        $stmt = $this->prepare($query);
        
        foreach ($params as $key => $value) {
            $type = is_int($value) ? PDO::PARAM_INT : PDO::PARAM_STR;
            $stmt->bindValue($key, $value, $type);
        }

        $stmt->execute();

        if ($fetchOne) {
            return $stmt->fetch();
        }

        return $stmt->fetchAll();
    }

    /**
     * Executa INSERT, UPDATE ou DELETE
     * 
     * @param string $query SQL query
     * @param array $params Parâmetros
     * @return int Número de linhas afetadas
     */
    public function execute(string $query, array $params = []): int
    {
        $stmt = $this->prepare($query);
        
        foreach ($params as $key => $value) {
            $type = is_int($value) ? PDO::PARAM_INT : PDO::PARAM_STR;
            $stmt->bindValue($key, $value, $type);
        }

        $stmt->execute();

        return $stmt->rowCount();
    }

    /**
     * Insere registro e retorna ID gerado
     * 
     * @param string $query SQL INSERT
     * @param array $params Parâmetros
     * @return string|false ID do registro ou false
     */
    public function lastInsertId(string $query, array $params = [])
    {
        $this->execute($query, $params);
        return $this->connect()->lastInsertId();
    }

    /**
     * Inicia transação
     */
    public function beginTransaction(): bool
    {
        return $this->connect()->beginTransaction();
    }

    /**
     * Commit da transação
     */
    public function commit(): bool
    {
        return $this->connect()->commit();
    }

    /**
     * Rollback da transação
     */
    public function rollback(): bool
    {
        return $this->connect()->rollback();
    }

    /**
     * Fecha conexão
     */
    public function disconnect(): void
    {
        $this->connection = null;
    }
}
