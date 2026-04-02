<?php
/**
 * CLASS VeiculoService
 * 
 * Serviço de negócios para Veículos
 * Lógica de validação e processamento
 */

namespace App\Services;

use App\Repositories\VeiculoRepository;

class VeiculoService
{
    private VeiculoRepository $repository;

    public function __construct()
    {
        $this->repository = new VeiculoRepository();
    }

    /**
     * Criar novo veículo
     */
    public function criar(int $tenantId, array $dados): array
    {
        // Validações
        $erros = $this->validar($dados);
        if (!empty($erros)) {
            return ['success' => false, 'errors' => $erros];
        }

        // Verificar se placa já existe
        $existe = $this->repository->getByPlaca($dados['placa'], $tenantId);
        if ($existe) {
            return ['success' => false, 'message' => 'Placa já cadastrada'];
        }

        // Criar
        $id = $this->repository->create($tenantId, [
            'placa' => strtoupper($dados['placa']),
            'modelo' => $dados['modelo'],
            'marca' => $dados['marca'] ?? null,
            'cor' => $dados['cor'] ?? null,
            'ano_fabricacao' => $dados['ano_fabricacao'] ?? date('Y'),
            'status' => 'ativo',
            'ativo' => 1
        ]);

        return [
            'success' => true,
            'message' => 'Veículo criado com sucesso',
            'id' => $id
        ];
    }

    /**
     * Atualizar veículo
     */
    public function atualizar(int $id, int $tenantId, array $dados): array
    {
        // Verificar se existe
        $veiculo = $this->repository->getByIdAndTenant($id, $tenantId);
        if (!$veiculo) {
            return ['success' => false, 'message' => 'Veículo não encontrado'];
        }

        // Se placa mudou, verificar se não existe outra
        if (isset($dados['placa']) && $dados['placa'] !== $veiculo['placa']) {
            $existe = $this->repository->getByPlaca($dados['placa'], $tenantId);
            if ($existe) {
                return ['success' => false, 'message' => 'Nova placa já existe'];
            }
        }

        // Atualizar
        $this->repository->update($id, $tenantId, $dados);

        return ['success' => true, 'message' => 'Veículo atualizado com sucesso'];
    }

    /**
     * Validar dados do veículo
     */
    private function validar(array $dados): array
    {
        $erros = [];

        if (empty($dados['placa'])) {
            $erros['placa'] = 'Placa é obrigatória';
        }

        if (empty($dados['modelo'])) {
            $erros['modelo'] = 'Modelo é obrigatório';
        }

        if (isset($dados['ano_fabricacao']) && !is_numeric($dados['ano_fabricacao'])) {
            $erros['ano_fabricacao'] = 'Ano deve ser numérico';
        }

        return $erros;
    }

    /**
     * Obter veículos ativos
     */
    public function getAtivos(int $tenantId): array
    {
        return $this->repository->getAtivos($tenantId);
    }
}
