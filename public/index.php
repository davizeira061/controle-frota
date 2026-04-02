<?php
/**
 * PUBLIC INDEX.PHP
 * 
 * Ponto de entrada da aplicação
 * Todos os requests devem ser redirecionados para este arquivo
 * 
 * Configuração recomendada no Apache (.htaccess):
 * <IfModule mod_rewrite.c>
 *     RewriteEngine On
 *     RewriteCond %{REQUEST_FILENAME} !-f
 *     RewriteCond %{REQUEST_FILENAME} !-d
 *     RewriteRule ^(.*)$ index.php [QSA,L]
 * </IfModule>
 */

// Definir timezone
date_default_timezone_set('America/Sao_Paulo');

// Caminho base da aplicação
define('BASE_PATH', dirname(__DIR__));
define('APP_PATH', BASE_PATH . '/app');
define('CORE_PATH', BASE_PATH . '/core');

// Carregar arquivo .env
if (file_exists(BASE_PATH . '/.env')) {
    $env_lines = file(BASE_PATH . '/.env', FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($env_lines as $line) {
        if (strpos($line, '=') !== false && strpos($line, '#') === false) {
            [$key, $value] = explode('=', $line, 2);
            $_ENV[trim($key)] = trim($value);
        }
    }
}

// Autoloader
require BASE_PATH . '/bootstrap/autoload.php';

// Global Error Handler
set_exception_handler(function ($e) {
    error_log($e->getMessage());
    $isJson = strpos($_SERVER['HTTP_ACCEPT'] ?? '', 'application/json') !== false || 
              strpos($_SERVER['CONTENT_TYPE'] ?? '', 'application/json') !== false;

    if ($isJson) {
        http_response_code(500);
        echo json_encode([
            'status' => 'error',
            'message' => 'Erro interno do servidor: ' . $e->getMessage(),
            'file' => $e->getFile(),
            'line' => $e->getLine()
        ]);
    } else {
        echo "<h1>Erro Interno</h1><p>{$e->getMessage()}</p><p>Arquivo: {$e->getFile()} na linha {$e->getLine()}</p>";
    }
    exit;
});

// Iniciar sessão
if (session_status() === PHP_SESSION_NONE) {
    session_start([
        'cookie_secure' => (bool) ($_ENV['SESSION_SECURE'] ?? false),
        'cookie_httponly' => true,
        'cookie_samesite' => 'Strict',
        'use_strict_mode' => true
    ]);
}

// Headers de segurança (removido Content-Type fixo para suportar HTML)
header('X-Content-Type-Options: nosniff');
header('X-Frame-Options: DENY');
header('X-XSS-Protection: 1; mode=block');

// Permitir CORS se necessário
if (isset($_ENV['ALLOWED_ORIGINS'])) {
    $allowed = explode(',', $_ENV['ALLOWED_ORIGINS']);
    $origin = $_SERVER['HTTP_ORIGIN'] ?? '';
    
    if (in_array($origin, $allowed)) {
        header('Access-Control-Allow-Origin: ' . $origin);
        header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
        header('Access-Control-Allow-Headers: Content-Type, Authorization');
        header('Access-Control-Allow-Credentials: true');
    }
}

// Responder a preflight
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

// Carregar rotas
require BASE_PATH . '/routes/api.php';
