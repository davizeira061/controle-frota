<?php
/**
 * AuthController
 * 
 * Endpoints de autenticação:
 * POST /register - Registrar nova empresa
 * POST /login - Login
 * POST /logout - Logout
 * POST /change-password - Alterar senha
 */

namespace App\Controllers;

use Core\Controller;
use App\Services\AuthService;

class AuthController extends Controller
{
    private AuthService $authService;

    public function __construct()
    {
        parent::__construct();
        $this->authService = new AuthService();
    }

    /**
     * GET /
     * Página de boas-vindas / Login
     */
    public function welcome(array $params = []): void
    {
        // Se a requisição espera JSON (API)
        if (strpos($_SERVER['HTTP_ACCEPT'] ?? '', 'application/json') !== false || $this->input('api')) {
            $this->responseSuccess([
                'message' => 'Bem-vindo à API Controle Frota SaaS',
                'version' => '1.0.0'
            ]);
        }

        $this->render('auth/login', [
            'title' => 'Login - Controle de Frota'
        ]);
    }

    /**
     * POST /register
     * Registrar nova empresa (auto-signup)
     */
    public function register(array $params = []): void
    {
        $nome = $this->input('nome');
        $email = $this->input('email');
        $senha = $this->input('senha');

        if (!$nome || !$email || !$senha) {
            $this->responseError('Nome, email e senha são obrigatórios', 400);
        }

        $resultado = $this->authService->registerCompany($nome, $email, $senha);

        if ($resultado['success']) {
            $this->responseSuccess(
                ['tenant_id' => $resultado['tenant_id']],
                $resultado['message'],
                201
            );
        } else {
            $this->responseError($resultado['message'], 400);
        }
    }

    /**
     * POST /login
     * Login de usuário
     */
    public function login(array $params = []): void
    {
        if (!$this->validateCsrfToken()) {
            return;
        }

        $email = $this->input('email');
        $senha = $this->input('senha');

        if (!$email || !$senha) {
            $this->responseError('Email e senha são obrigatórios', 400);
        }

        $resultado = $this->authService->login($email, $senha);

        if ($resultado['success']) {
            $this->responseSuccess($resultado['user'], $resultado['message']);
        } else {
            $this->responseError($resultado['message'], 401);
        }
    }

    /**
     * POST /logout
     * Sair do sistema
     */
    public function logout(array $params = []): void
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        if (!isset($_SESSION['user_id'])) {
            $this->responseError('Usuário não está autenticado', 401);
        }

        $this->authService->logout();
        $this->responseSuccess(null, 'Logout realizado com sucesso');
    }

    /**
     * POST /change-password
     * Alterar senha (requer autenticação)
     */
    public function changePassword(array $params = []): void
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        if (!isset($_SESSION['user_id'])) {
            error_log("ChangePassword: Usuário não está na sessão.");
            $this->responseError('Não autenticado', 401);
        }

        if (strpos($_SERVER['HTTP_ACCEPT'] ?? '', 'application/json') !== false || $this->input('api')) {
            $novaSenha = $this->input('nova_senha');
            $confirmacao = $this->input('confirmacao_senha');

            error_log("ChangePassword Attempt: User ID " . $_SESSION['user_id']);

            if (!$novaSenha || !$confirmacao) {
                $this->responseError('Nova senha e confirmação são obrigatórias', 400);
            }

            if ($novaSenha !== $confirmacao) {
                $this->responseError('Senhas não conferem', 400);
            }

            $resultado = $this->authService->changePassword($_SESSION['user_id'], $novaSenha);

            if ($resultado['success']) {
                $_SESSION['senha_temporaria'] = 0; // Atualiza sessão
                $this->log('SENHA_ALTERADA', 'AUTH', 'Senha alterada com sucesso');
                $this->responseSuccess(null, $resultado['message']);
            } else {
                $this->responseError($resultado['message'], 400);
            }
        }

        $this->render('auth/change_password', [
            'title' => 'Alterar Senha - Controle de Frota'
        ]);
    }
}
