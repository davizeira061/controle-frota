<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $title ?? 'Registros de Uso'; ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
</head>
<body class="bg-light">
    <nav class="navbar navbar-expand-lg navbar-dark <?php echo ($role === 'motorista') ? 'bg-success' : 'bg-primary'; ?> mb-4">
        <div class="container">
            <a class="navbar-brand" href="#">
                <?php if ($role === 'motorista'): ?>
                    <i class="bi bi-person-circle"></i> Portal do Motorista
                <?php else: ?>
                    Controle de Frota
                <?php endif; ?>
            </a>
            <div class="collapse navbar-collapse">
                <ul class="navbar-nav me-auto">
                    <li class="nav-item"><a class="nav-link" href="<?php echo $baseUrl; ?>/dashboard">Dashboard</a></li>
                    
                    <?php if ($role !== 'motorista'): ?>
                        <li class="nav-item"><a class="nav-link" href="<?php echo $baseUrl; ?>/veiculos">Veículos</a></li>
                        <li class="nav-item"><a class="nav-link" href="<?php echo $baseUrl; ?>/motoristas">Motoristas</a></li>
                    <?php endif; ?>

                    <li class="nav-item"><a class="nav-link active" href="<?php echo $baseUrl; ?>/registros"><?php echo ($role === 'motorista') ? 'Meus Usos' : 'Uso de Veículos'; ?></a></li>
                    
                    <?php if ($role !== 'motorista' && $role !== 'operador'): ?>
                        <li class="nav-item"><a class="nav-link" href="<?php echo $baseUrl; ?>/usuarios">Usuários</a></li>
                    <?php endif; ?>
                </ul>
                <button id="logoutBtn" class="btn btn-outline-light btn-sm">Sair</button>
            </div>
        </div>
    </nav>

    <div class="container">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h2>Registros de Uso</h2>
            <button class="btn btn-success" data-bs-toggle="modal" data-bs-target="#iniciarUsoModal">
                <i class="bi bi-play-circle"></i> Iniciar Novo Uso
            </button>
        </div>

        <div class="card shadow-sm">
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>Veículo</th>
                                <th>Motorista</th>
                                <th>Início</th>
                                <th>Fim</th>
                                <th>Status</th>
                                <th>KM Inicial/Final</th>
                                <th>Ações</th>
                            </tr>
                        </thead>
                        <tbody id="registrosList">
                            <tr><td colspan="7" class="text-center p-4">Carregando registros...</td></tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal Iniciar Uso -->
    <div class="modal fade" id="iniciarUsoModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <form id="iniciarUsoForm">
                    <div class="modal-header">
                        <h5 class="modal-title">Iniciar Uso de Veículo</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <div class="mb-3">
                            <label class="form-label">Veículo</label>
                            <select class="form-select" name="veiculo_id" id="selectVeiculos" required>
                                <option value="">Selecione um veículo...</option>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Motorista</label>
                            <select class="form-select" name="motorista_id" id="selectMotoristas" required>
                                <option value="">Selecione um motorista...</option>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">KM Inicial</label>
                            <input type="number" class="form-control" name="quilometragem_inicial" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Nível de Combustível</label>
                            <select class="form-select" name="combustivel_inicial">
                                <option value="cheio">Cheio</option>
                                <option value="3/4">3/4</option>
                                <option value="1/2">1/2</option>
                                <option value="1/4">1/4</option>
                                <option value="reserva">Reserva</option>
                            </select>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                        <button type="submit" class="btn btn-success">Iniciar</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Modal Finalizar Uso -->
    <div class="modal fade" id="finalizarUsoModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <form id="finalizarUsoForm">
                    <input type="hidden" name="registro_id" id="finalizar_id">
                    <div class="modal-header bg-warning">
                        <h5 class="modal-title">Finalizar Uso</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <div class="mb-3">
                            <label class="form-label">KM Final</label>
                            <input type="number" class="form-control" name="quilometragem_final" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Nível de Combustível Final</label>
                            <select class="form-select" name="combustivel_final">
                                <option value="cheio">Cheio</option>
                                <option value="3/4">3/4</option>
                                <option value="1/2">1/2</option>
                                <option value="1/4">1/4</option>
                                <option value="reserva">Reserva</option>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Status do Veículo</label>
                            <select class="form-select" name="status_veiculo">
                                <option value="ok">Tudo OK</option>
                                <option value="avarias">Possui Avarias</option>
                                <option value="critico">Crítico (Requer Manutenção)</option>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Observações / Avarias</label>
                            <textarea class="form-control" name="descricao_avarias" rows="2"></textarea>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                        <button type="submit" class="btn btn-warning">Finalizar</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        const baseUrl = '<?php echo $baseUrl; ?>';
        const csrfToken = '<?php echo $csrfToken; ?>';

        async function loadData() {
            try {
                // Carregar registros
                const res = await fetch(`${baseUrl}/registros?api=1`);
                const result = await res.json();
                const list = document.getElementById('registrosList');
                list.innerHTML = '';

                if (result.status === 'success' && result.data.data) {
                    result.data.data.forEach(r => {
                        const emAberto = !r.data_hora_fim;
                        list.innerHTML += `
                            <tr>
                                <td><strong>${r.placa || '-'}</strong></td>
                                <td>${r.motorista_nome || '-'}</td>
                                <td class="small">${new Date(r.data_hora_inicio).toLocaleString()}</td>
                                <td class="small">${r.data_hora_fim ? new Date(r.data_hora_fim).toLocaleString() : '<span class="text-success">Em uso...</span>'}</td>
                                <td>
                                    <span class="badge bg-${r.status_veiculo === 'ok' ? 'success' : (r.status_veiculo === 'avarias' ? 'warning' : 'danger')}">
                                        ${r.status_veiculo.toUpperCase()}
                                    </span>
                                </td>
                                <td class="small">${r.quilometragem_inicial} / ${r.quilometragem_final || '-'}</td>
                                <td>
                                    ${emAberto ? `<button class="btn btn-sm btn-warning" onclick="openFinalizar(${r.id})">Finalizar</button>` : '<i class="bi bi-check-circle text-success"></i>'}
                                </td>
                            </tr>
                        `;
                    });
                }

                // Carregar Veículos e Motoristas para o Select (apenas na primeira vez)
                if (document.getElementById('selectVeiculos').options.length === 1) {
                    const resV = await fetch(`${baseUrl}/veiculos?api=1`);
                    const dataV = await resV.json();
                    dataV.data.data.forEach(v => {
                        if (v.status === 'ativo') {
                            document.getElementById('selectVeiculos').innerHTML += `<option value="${v.id}">${v.placa} - ${v.modelo}</option>`;
                        }
                    });

                    const resM = await fetch(`${baseUrl}/motoristas?api=1`);
                    const dataM = await resM.json();
                    dataM.data.data.forEach(m => {
                        document.getElementById('selectMotoristas').innerHTML += `<option value="${m.id}">${m.nome}</option>`;
                    });
                }
            } catch (err) {
                console.error(err);
            }
        }

        function openFinalizar(id) {
            document.getElementById('finalizar_id').value = id;
            new bootstrap.Modal(document.getElementById('finalizarUsoModal')).show();
        }

        document.getElementById('iniciarUsoForm').addEventListener('submit', async (e) => {
            e.preventDefault();
            const data = Object.fromEntries(new FormData(e.target));
            try {
                const res = await fetch(`${baseUrl}/registros`, {
                    method: 'POST',
                    headers: { 
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': csrfToken
                    },
                    body: JSON.stringify(data)
                });
                const result = await res.json();
                if (result.status === 'success') {
                    bootstrap.Modal.getInstance(document.getElementById('iniciarUsoModal')).hide();
                    loadData();
                } else {
                    alert(result.message);
                }
            } catch (err) {
                alert('Erro ao iniciar uso');
            }
        });

        document.getElementById('finalizarUsoForm').addEventListener('submit', async (e) => {
            e.preventDefault();
            const formData = new FormData(e.target);
            const id = formData.get('registro_id');
            const data = Object.fromEntries(formData.entries());
            try {
                const res = await fetch(`${baseUrl}/registros/${id}`, {
                    method: 'PUT',
                    headers: { 
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': csrfToken
                    },
                    body: JSON.stringify(data)
                });
                const result = await res.json();
                if (result.status === 'success') {
                    bootstrap.Modal.getInstance(document.getElementById('finalizarUsoModal')).hide();
                    loadData();
                } else {
                    alert(result.message);
                }
            } catch (err) {
                alert('Erro ao finalizar uso');
            }
        });

        document.getElementById('logoutBtn').addEventListener('click', async () => {
            await fetch(`${baseUrl}/logout`, { 
                method: 'POST',
                headers: { 'X-CSRF-TOKEN': csrfToken }
            });
            window.location.href = `${baseUrl}/`;
        });

        loadData();
    </script>
</body>
</html>
