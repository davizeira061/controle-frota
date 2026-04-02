<?php
/**
 * Définition des routes de l'application
 * 
 * Routes disponibles:
 */

use Core\Router;

$router = new Router();

// ==========================================
// ROUTES PUBLIQUES (sem autenticação)
// ==========================================

// Página inicial
$router->get('/', 'AuthController@welcome');

// Autenticação
$router->post('/register', 'AuthController@register');
$router->post('/login', 'AuthController@login');
$router->post('/logout', 'AuthController@logout', ['auth']);

// Dashboard unificado
$router->get('/dashboard', 'DashboardController@index', ['auth']);

// ==========================================
// ROUTES MASTER (Apenas Super Admin)
// ==========================================

// Empresas
$router->get('/admin/empresas', 'EmpresaController@index', ['auth', 'master']);
$router->post('/admin/empresas', 'EmpresaController@store', ['auth', 'master']);
$router->post('/admin/empresas/{id}/reset-password', 'EmpresaController@resetAdminPassword', ['auth', 'master']);
$router->put('/admin/empresas/{id}/status', 'EmpresaController@toggleStatus', ['auth', 'master']);

// Logs Globais
$router->get('/admin/logs', 'LogController@index', ['auth', 'master']);

// ==========================================
// ROUTES PROTEGIDAS (require autenticação)
// ==========================================

// Usuários (admin only)
$router->get('/usuarios', 'UsuarioController@index', ['auth', 'admin']);
$router->post('/usuarios', 'UsuarioController@store', ['auth', 'admin']);
$router->put('/usuarios/{id}', 'UsuarioController@update', ['auth', 'admin']);
$router->delete('/usuarios/{id}', 'UsuarioController@destroy', ['auth', 'admin']);

// Veículos
$router->get('/veiculos', 'VeiculoController@index', ['auth']);
$router->post('/veiculos', 'VeiculoController@store', ['auth', 'admin']);
$router->get('/veiculos/{id}', 'VeiculoController@show', ['auth']);
$router->put('/veiculos/{id}', 'VeiculoController@update', ['auth', 'admin']);
$router->delete('/veiculos/{id}', 'VeiculoController@destroy', ['auth', 'admin']);

// Motoristas
$router->get('/motoristas', 'MotoristaController@index', ['auth']);
$router->post('/motoristas', 'MotoristaController@store', ['auth', 'admin']);
$router->get('/motoristas/{id}', 'MotoristaController@show', ['auth']);

// Registros de Uso
$router->get('/registros', 'RegistroUsoController@index', ['auth']);
$router->post('/registros', 'RegistroUsoController@store', ['auth']);
$router->get('/registros/{id}', 'RegistroUsoController@show', ['auth']);
$router->put('/registros/{id}', 'RegistroUsoController@update', ['auth']);
$router->get('/registros/com-avarias', 'RegistroUsoController@comAvarias', ['auth', 'admin']);

// Alterar senha
$router->get('/change-password', 'AuthController@changePassword', ['auth']);
$router->post('/change-password', 'AuthController@changePassword', ['auth']);

// ==========================================
// INICIAR ROUTER
// ==========================================
$router->dispatch();
