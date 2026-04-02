<?php
require_once __DIR__ . '/bootstrap/autoload.php';

// Mock $_ENV para o Database
if (file_exists(__DIR__ . '/.env')) {
    $env_lines = file(__DIR__ . '/.env', FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($env_lines as $line) {
        if (strpos($line, '=') !== false && strpos($line, '#') === false) {
            [$key, $value] = explode('=', $line, 2);
            $_ENV[trim($key)] = trim($value);
        }
    }
}

use Core\Database;

$db = Database::getInstance();

// Credenciais solicitadas para o nível MASTER
$email = 'master@sistema.com.br';
$senha = 'master@2026';
$nome = 'Administrador Geral';

try {
    echo "Configurando acesso MASTER...\n";

    // Verificar se o usuário já existe
    $exists = $db->query("SELECT id FROM usuarios WHERE email = :email", [':email' => $email], true);

    $hash = password_hash($senha, PASSWORD_BCRYPT);

    if ($exists) {
        // Atualizar usuário existente para garantir que seja MASTER e tenha a senha correta
        $db->execute("UPDATE usuarios SET nome = :nome, senha = :senha, role = 'master', tenant_id = NULL, ativo = 1, senha_temporaria = 0 WHERE id = :id", [
            ':nome' => $nome,
            ':senha' => $hash,
            ':id' => $exists['id']
        ]);
        echo "Usuário MASTER atualizado com sucesso!\n";
    } else {
        // Criar novo usuário MASTER
        $db->execute("INSERT INTO usuarios (nome, email, senha, role, tenant_id, ativo, senha_temporaria) VALUES (:nome, :email, :senha, 'master', NULL, 1, 0)", [
            ':nome' => $nome,
            ':email' => $email,
            ':senha' => $hash
        ]);
        echo "Usuário MASTER criado com sucesso!\n";
    }

    echo "\n-------------------------------------------\n";
    echo "DADOS DE ACESSO MASTER:\n";
    echo "E-mail: $email\n";
    echo "Senha:  $senha\n";
    echo "-------------------------------------------\n";

} catch (Exception $e) {
    echo "ERRO: " . $e->getMessage() . "\n";
}
