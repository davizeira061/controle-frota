<?php
/**
 * Arquivo de configuração principal
 */

return [
    'app' => [
        'name' => 'Controle Frota SaaS',
        'version' => '1.0.0',
        'debug' => (bool) ($_ENV['DEBUG'] ?? false),
        'timezone' => 'America/Sao_Paulo'
    ],

    'database' => [
        'host' => $_ENV['DB_HOST'] ?? 'localhost',
        'name' => $_ENV['DB_NAME'] ?? 'controle_frota',
        'user' => $_ENV['DB_USER'] ?? 'root',
        'pass' => $_ENV['DB_PASS'] ?? ''
    ],

    'session' => [
        'lifetime' => 86400 * 30, // 30 dias
        'cookie_secure' => (bool) ($_ENV['SESSION_SECURE'] ?? false),
        'cookie_httponly' => true,
        'cookie_samesite' => 'Strict'
    ],

    'security' => [
        'password_bcrypt_cost' => 10,
        'max_login_attempts' => 5,
        'lockout_duration' => 900 // 15 minutos
    ]
];
