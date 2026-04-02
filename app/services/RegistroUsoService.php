<?php
/**
 * CLASS RegistroUsoService
 * 
 * Serviço para Registros de Uso (Checklists)
 */

namespace App\Services;

use App\Repositories\RegistroUsoRepository;
use App\Repositories\VeiculoRepository;

class RegistroUsoService
{
    private RegistroUsoRepository $repository;
    private VeiculoRepository $veiculoRepository;

    public function __construct()
    {
        $this->repository = new RegistroUsoRepository();
        $this->veiculoRepository = new VeiculoRepository();
    }

    /**
     * Iniciar novo registro de uso
     */
    public function iniciar(int $tenantId, int $userId, array $dados): array
    {
        $erros = $this->validar($dados);
        if (!empty($erros)) {
            return ['success' => false, 'errors' => $erros];
        }

        // Verificar se veículo existe
        $veiculo = $this->veiculoRepository->getByIdAndTenant($dados['veiculo_id'], $tenantId);
        if (!$veiculo) {
            return ['success' => false, 'message' => 'Veículo não encontrado'];
        }

        // Verificar se já há registro aberto para este veículo
        $abierto = $this->repository->hasOpenRecord($dados['veiculo_id'], $tenantId);
        if ($abierto) {
            return ['success' => false, 'message' => 'Já existe um registro aberto para este veículo'];
        }

        // Criar registro
        $id = $this->repository->create($tenantId, [
            'veiculo_id' => $dados['veiculo_id'],
            'motorista_id' => $dados['motorista_id'],
            'usuario_id' => $userId,
            'quilometragem_inicial' => $dados['quilometragem_inicial'],
            'combustivel_inicial' => $dados['combustivel_inicial'] ?? null,
            'status_veiculo' => $dados['status_veiculo'] ?? 'ok',
            'descricao_avarias' => $dados['descricao_avarias'] ?? null,
            'data_hora_inicio' => date('Y-m-d H:i:s'),
            'ativo' => 1
        ]);

        return [
            'success' => true,
            'message' => 'Registro iniciado com sucesso',
            'id' => $id
        ];
    }

    /**
     * Finalizar registro de uso
     */
    public function finalizar(int $id, int $tenantId, int $userId, array $dados): array
    {
        $registro = $this->repository->getByIdAndTenant($id, $tenantId);
        if (!$registro) {
            return ['success' => false, 'message' => 'Registro não encontrado'];
        }

        // Atualizar quilometragem do veículo
        if (isset($dados['quilometragem_final'])) {
            $this->veiculoRepository->updateQuilometragem(
                $registro['veiculo_id'],
                $tenantId,
                $dados['quilometragem_final']
            );
        }

        // Finalizar registro
        $this->repository->update($id, $tenantId, [
            'quilometragem_final' => $dados['quilometragem_final'],
            'combustivel_final' => $dados['combustivel_final'] ?? null,
            'status_veiculo' => $dados['status_veiculo'] ?? $registro['status_veiculo'],
            'descricao_avarias' => $dados['descricao_avarias'] ?? null,
            'observacoes' => $dados['observacoes'] ?? null,
            'data_hora_fim' => date('Y-m-d H:i:s')
        ]);

        // Atualizar status do veículo se houver avaria crítica
        if (isset($dados['status_veiculo']) && $dados['status_veiculo'] === 'critico') {
            $this->veiculoRepository->update($registro['veiculo_id'], $tenantId, [
                'status' => 'manutencao'
            ]);
        }

        return [
            'success' => true,
            'message' => 'Registro finalizado com sucesso'
        ];
    }

    /**
     * Validar dados
     */
    private function validar(array $dados): array
    {
        $erros = [];

        if (empty($dados['veiculo_id'])) {
            $erros['veiculo_id'] = 'Veículo é obrigatório';
        }

        if (empty($dados['motorista_id'])) {
            $erros['motorista_id'] = 'Motorista é obrigatório';
        }

        if (empty($dados['quilometragem_inicial']) || !is_numeric($dados['quilometragem_inicial'])) {
            $erros['quilometragem_inicial'] = 'Quilometragem inicial deve ser numérica';
        }

        return $erros;
    }
}
