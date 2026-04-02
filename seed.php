<?php
require_once __DIR__ . '/bootstrap/autoload.php';

// Mock $_ENV for Database
if (file_exists(__DIR__ . '/.env')) {
    $env_lines = file(__DIR__ . '/.env', FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($env_lines as $line) {
        if (strpos($line, '=') !== false && strpos($line, '#') === false) {
            [$key, $value] = explode('=', $line, 2);
            $_ENV[trim($key)] = trim($value);
        }
    }
}

use App\Services\AuthService;

$authService = new AuthService();

$nome = 'Empresa Teste';
$email = 'admin@empresa.test';
$senha = 'senha123';

echo "Tentando criar usuário: $email...\n";

$resultado = $authService->registerCompany($nome, $email, $senha);

if ($resultado['success']) {
    echo "Sucesso! Usuário criado com Tenant ID: " . $resultado['tenant_id'] . "\n";
} else {
    echo "Erro: " . $resultado['message'] . "\n";
}
