<?php
/**
 * CLASS MotoristaService
 * 
 * Serviço de negócios para Motoristas
 */

namespace App\Services;

use App\Repositories\MotoristaRepository;

class MotoristaService
{
    private MotoristaRepository $repository;

    public function __construct()
    {
        $this->repository = new MotoristaRepository();
    }

    /**
     * Criar novo motorista
     */
    public function criar(int $tenantId, array $dados): array
    {
        $erros = $this->validar($dados);
        if (!empty($erros)) {
            return ['success' => false, 'errors' => $erros];
        }

        // Verificar CPF único (se fornecido)
        if (!empty($dados['cpf'])) {
            $existe = $this->repository->getByCpf($dados['cpf'], $tenantId);
            if ($existe) {
                return ['success' => false, 'message' => 'CPF já cadastrado'];
            }
        }

        $id = $this->repository->create($tenantId, [
            'nome' => $dados['nome'],
            'cpf' => $dados['cpf'] ?? null,
            'cnh' => $dados['cnh'] ?? null,
            'telefone' => $dados['telefone'] ?? null,
            'email' => $dados['email'] ?? null,
            'data_admissao' => $dados['data_admissao'] ?? date('Y-m-d'),
            'ativo' => 1
        ]);

        return [
            'success' => true,
            'message' => 'Motorista criado com sucesso',
            'id' => $id
        ];
    }

    /**
     * Validar dados
     */
    private function validar(array $dados): array
    {
        $erros = [];

        if (empty($dados['nome'])) {
            $erros['nome'] = 'Nome é obrigatório';
        }

        if (isset($dados['email']) && !filter_var($dados['email'], FILTER_VALIDATE_EMAIL)) {
            $erros['email'] = 'Email inválido';
        }

        return $erros;
    }

    /**
     * Listar motoristas ativos
     */
    public function getAtivos(int $tenantId): array
    {
        return $this->repository->getAtivos($tenantId);
    }
}
