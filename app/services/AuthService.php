<?php
/**
 * CLASS AuthService
 * 
 * Gerencia autenticação, login, registro de empresas e usuários
 */

namespace App\Services;

use Core\Database;

class AuthService
{
    private Database $db;

    public function __construct()
    {
        $this->db = Database::getInstance();
    }

    /**
     * Registar nova empresa (auto-signup)
     * Cria empresa e usuário admin vinculado
     */
    public function registerCompany(string $nome, string $email, string $senha): array
    {
        // Validações
        if (strlen($nome) < 3) {
            return ['success' => false, 'message' => 'Nome deve ter no mínimo 3 caracteres'];
        }

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return ['success' => false, 'message' => 'Email inválido'];
        }

        if (strlen($senha) < 6) {
            return ['success' => false, 'message' => 'Senha deve ter no mínimo 6 caracteres'];
        }

        // Verificar se email já existe
        $existente = $this->db->query(
            "SELECT id FROM usuarios WHERE email = :email",
            [':email' => $email],
            true
        );

        if ($existente) {
            return ['success' => false, 'message' => 'Email já cadastrado'];
        }

        try {
            // Iniciar transação
            $this->db->beginTransaction();

            // 1. Criar empresa
            $queryEmpresa = "
                INSERT INTO empresas (nome, email)
                VALUES (:nome, :email)
            ";

            $tenantId = $this->db->lastInsertId($queryEmpresa, [
                ':nome' => $nome,
                ':email' => $email
            ]);

            // 2. Criar usuário admin da empresa
            $senhaHash = password_hash($senha, PASSWORD_BCRYPT);

            $queryUsuario = "
                INSERT INTO usuarios (tenant_id, nome, email, senha, role, senha_temporaria)
                VALUES (:tenant_id, :nome, :email, :senha, 'admin', 0)
            ";

            $this->db->execute($queryUsuario, [
                ':tenant_id' => $tenantId,
                ':nome' => $nome,
                ':email' => $email,
                ':senha' => $senhaHash
            ]);

            // 3. Registrar log
            $this->db->execute(
                "INSERT INTO logs (user_id, tenant_id, acao, modulo, descricao, ip) 
                VALUES (:user_id, :tenant_id, :acao, :modulo, :descricao, :ip)",
                [
                    ':user_id' => null,
                    ':tenant_id' => $tenantId,
                    ':acao' => 'EMPRESA_CRIADA',
                    ':modulo' => 'EMPRESAS',
                    ':descricao' => "Empresa criada: {$nome}",
                    ':ip' => $this->getClientIp()
                ]
            );

            $this->db->commit();

            return [
                'success' => true,
                'message' => 'Empresa registrada com sucesso',
                'tenant_id' => $tenantId
            ];
        } catch (\Exception $e) {
            $this->db->rollback();
            return ['success' => false, 'message' => 'Erro ao registrar empresa: ' . $e->getMessage()];
        }
    }

    /**
     * Login de usuário
     */
    public function login(string $email, string $senha): array
    {
        // 1. Tentar buscar no tenant-based
        $query = "
            SELECT u.id, u.tenant_id, u.nome, u.email, u.senha, u.role, u.senha_temporaria, e.nome as empresa_nome
            FROM usuarios u
            LEFT JOIN empresas e ON u.tenant_id = e.id
            WHERE u.email = :email AND u.ativo = 1
            LIMIT 1
        ";

        $usuario = $this->db->query($query, [':email' => $email], true);

        if (!$usuario) {
            return ['success' => false, 'message' => 'Usuário ou senha incorretos'];
        }

        if (!password_verify($senha, $usuario['senha'])) {
            // Registrar tentativa falha (apenas se tiver tenant)
            if ($usuario['tenant_id']) {
                $this->db->execute(
                    "INSERT INTO logs (user_id, tenant_id, acao, modulo, descricao, ip) 
                    VALUES (:user_id, :tenant_id, :acao, :modulo, :descricao, :ip)",
                    [
                        ':user_id' => $usuario['id'],
                        ':tenant_id' => $usuario['tenant_id'],
                        ':acao' => 'FALHA_LOGIN',
                        ':modulo' => 'AUTH',
                        ':descricao' => "Tentativa de login falhou para {$email}",
                        ':ip' => $this->getClientIp()
                    ]
                );
            }

            return ['success' => false, 'message' => 'Usuário ou senha incorretos'];
        }

        // Registrar login bem-sucedido e atualizar último login
        $this->db->execute("UPDATE usuarios SET ultimo_login = NOW() WHERE id = :id", [':id' => $usuario['id']]);

        if ($usuario['tenant_id']) {
            $this->db->execute(
                "INSERT INTO logs (user_id, tenant_id, acao, modulo, descricao, ip) 
                VALUES (:user_id, :tenant_id, :acao, :modulo, :descricao, :ip)",
                [
                    ':user_id' => $usuario['id'],
                    ':tenant_id' => $usuario['tenant_id'],
                    ':acao' => 'LOGIN',
                    ':modulo' => 'AUTH',
                    ':descricao' => "Login bem-sucedido: {$email}",
                    ':ip' => $this->getClientIp()
                ]
            );
        }

        // Iniciar sessão
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        $_SESSION['user_id'] = $usuario['id'];
        $_SESSION['tenant_id'] = $usuario['tenant_id'];
        $_SESSION['nome'] = $usuario['nome'];
        $_SESSION['email'] = $usuario['email'];
        $_SESSION['role'] = $usuario['role'];
        $_SESSION['senha_temporaria'] = $usuario['senha_temporaria'];
        $_SESSION['empresa_nome'] = $usuario['empresa_nome'] ?? 'SISTEMA MASTER';

        return [
            'success' => true,
            'message' => 'Login realizado com sucesso',
            'user' => [
                'id' => $usuario['id'],
                'nome' => $usuario['nome'],
                'email' => $usuario['email'],
                'role' => $usuario['role'],
                'empresa_nome' => $_SESSION['empresa_nome'],
                'senha_temporaria' => (bool) $usuario['senha_temporaria']
            ]
        ];
    }

    /**
     * Logout
     */
    public function logout(): bool
    {
        session_start();

        if (isset($_SESSION['user_id'])) {
            $this->db->execute(
                "INSERT INTO logs (user_id, tenant_id, acao, modulo, descricao, ip) 
                VALUES (:user_id, :tenant_id, :acao, :modulo, :descricao, :ip)",
                [
                    ':user_id' => $_SESSION['user_id'],
                    ':tenant_id' => $_SESSION['tenant_id'],
                    ':acao' => 'LOGOUT',
                    ':modulo' => 'AUTH',
                    ':descricao' => "Logout: {$_SESSION['email']}",
                    ':ip' => $this->getClientIp()
                ]
            );
        }

        session_destroy();
        return true;
    }

    /**
     * Criar novo usuário na empresa
     */
    public function createUser(int $tenantId, string $nome, string $email, string $role): array
    {
        // Validações
        if (!in_array($role, ['admin', 'operador', 'motorista'])) {
            return ['success' => false, 'message' => 'Role inválido'];
        }

        // Verificar se email já existe neste tenant
        $existe = $this->db->query(
            "SELECT id FROM usuarios WHERE email = :email AND tenant_id = :tenant_id",
            [':email' => $email, ':tenant_id' => $tenantId],
            true
        );

        if ($existe) {
            return ['success' => false, 'message' => 'Email já existe nesta empresa'];
        }

        // Gerar senha temporária aleatória
        $senhaTemporaria = $this->generateRandomPassword();
        $senhaHash = password_hash($senhaTemporaria, PASSWORD_BCRYPT);

        try {
            $query = "
                INSERT INTO usuarios (tenant_id, nome, email, senha, role, senha_temporaria)
                VALUES (:tenant_id, :nome, :email, :senha, :role, 1)
            ";

            $this->db->execute($query, [
                ':tenant_id' => $tenantId,
                ':nome' => $nome,
                ':email' => $email,
                ':senha' => $senhaHash,
                ':role' => $role
            ]);

            return [
                'success' => true,
                'message' => 'Usuário criado com sucesso',
                'temporary_password' => $senhaTemporaria,
                'note' => 'Compartilhe esta senha temporária com o usuário'
            ];
        } catch (\Exception $e) {
            return ['success' => false, 'message' => 'Erro ao criar usuário: ' . $e->getMessage()];
        }
    }

    /**
     * Atualizar senha
     */
    public function changePassword(int $userId, string $novaSenha): array
    {
        if (strlen($novaSenha) < 6) {
            return ['success' => false, 'message' => 'Senha deve ter no mínimo 6 caracteres'];
        }

        $senhaHash = password_hash($novaSenha, PASSWORD_BCRYPT);

        $query = "
            UPDATE usuarios 
            SET senha = :senha, senha_temporaria = 0
            WHERE id = :id
        ";

        $this->db->execute($query, [
            ':senha' => $senhaHash,
            ':id' => $userId
        ]);

        return ['success' => true, 'message' => 'Senha alterada com sucesso'];
    }

    /**
     * Reseta a senha de um usuário para uma senha temporária
     */
    public function resetUserPassword(int $userId): array
    {
        $senhaTemporaria = $this->generateRandomPassword();
        $senhaHash = password_hash($senhaTemporaria, PASSWORD_BCRYPT);

        try {
            $query = "
                UPDATE usuarios 
                SET senha = :senha, senha_temporaria = 1
                WHERE id = :id
            ";

            $this->db->execute($query, [
                ':senha' => $senhaHash,
                ':id' => $userId
            ]);

            return [
                'success' => true,
                'temporary_password' => $senhaTemporaria
            ];
        } catch (\Exception $e) {
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }

    /**
     * Gera senha aleatória
     */
    private function generateRandomPassword(int $length = 10): string
    {
        $chars = 'ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789';
        $password = '';

        for ($i = 0; $i < $length; $i++) {
            $password .= $chars[random_int(0, strlen($chars) - 1)];
        }

        return $password;
    }

    /**
     * Get client IP
     */
    private function getClientIp(): string
    {
        if (!empty($_SERVER['HTTP_CLIENT_IP'])) {
            $ip = $_SERVER['HTTP_CLIENT_IP'];
        } elseif (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
            $ip = explode(',', $_SERVER['HTTP_X_FORWARDED_FOR'])[0];
        } else {
            $ip = $_SERVER['REMOTE_ADDR'] ?? '';
        }

        return htmlspecialchars($ip);
    }
}
