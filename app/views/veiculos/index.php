<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $title ?? 'Veículos - Controle de Frota'; ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">
    <nav class="navbar navbar-expand-lg navbar-dark bg-primary mb-4">
        <div class="container">
            <a class="navbar-brand" href="#">Controle de Frota</a>
            <div class="collapse navbar-collapse">
                <ul class="navbar-nav me-auto">
                    <li class="nav-item"><a class="nav-link" href="<?php echo $baseUrl; ?>/dashboard">Dashboard</a></li>
                    <li class="nav-item"><a class="nav-link active" href="<?php echo $baseUrl; ?>/veiculos">Veículos</a></li>
                    <li class="nav-item"><a class="nav-link" href="<?php echo $baseUrl; ?>/motoristas">Motoristas</a></li>
                    <li class="nav-item"><a class="nav-link" href="<?php echo $baseUrl; ?>/registros">Uso de Veículos</a></li>
                    <li class="nav-item"><a class="nav-link" href="<?php echo $baseUrl; ?>/usuarios">Usuários</a></li>
                </ul>
                <button id="logoutBtn" class="btn btn-outline-light btn-sm">Sair</button>
            </div>
        </div>
    </nav>

    <div class="container">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h2>Veículos</h2>
            <button class="btn btn-success" data-bs-toggle="modal" data-bs-target="#addVeiculoModal">Novo Veículo</button>
        </div>

        <div class="card shadow-sm">
            <div class="card-body">
                <table class="table table-hover">
                    <thead>
                        <tr>
                            <th>Placa</th>
                            <th>Modelo</th>
                            <th>Marca</th>
                            <th>Ano</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody id="veiculosList">
                        <tr>
                            <td colspan="5" class="text-center">Carregando...</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Modal -->
    <div class="modal fade" id="addVeiculoModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <form id="veiculoForm">
                    <div class="modal-header">
                        <h5 class="modal-title">Novo Veículo</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <div class="mb-3">
                            <label class="form-label">Placa</label>
                            <input type="text" class="form-control" name="placa" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Modelo</label>
                            <input type="text" class="form-control" name="modelo" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Marca</label>
                            <input type="text" class="form-control" name="marca" required>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Fechar</button>
                        <button type="submit" class="btn btn-primary">Salvar</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        const baseUrl = '<?php echo $baseUrl; ?>';
        const csrfToken = '<?php echo $csrfToken; ?>';

        async function loadVeiculos() {
            try {
                const response = await fetch(`${baseUrl}/veiculos?api=1`, {
                    headers: { 'Accept': 'application/json' }
                });
                const result = await response.json();
                
                const list = document.getElementById('veiculosList');
                list.innerHTML = '';

                if (result.status === 'success' && result.data && result.data.data) {
                    result.data.data.forEach(v => {
                        list.innerHTML += `
                            <tr>
                                <td>${v.placa}</td>
                                <td>${v.modelo}</td>
                                <td>${v.marca}</td>
                                <td>${v.ano_fabricacao || '-'}</td>
                                <td><span class="badge bg-${v.status === 'ativo' ? 'success' : 'warning'}">${v.status}</span></td>
                            </tr>
                        `;
                    });
                } else {
                    list.innerHTML = '<tr><td colspan="5" class="text-center">Nenhum veículo encontrado.</td></tr>';
                }
            } catch (error) {
                console.error('Erro ao carregar veículos:', error);
            }
        }

        document.getElementById('veiculoForm')?.addEventListener('submit', async (e) => {
            e.preventDefault();
            const formData = new FormData(e.target);
            const data = Object.fromEntries(formData.entries());

            try {
                const response = await fetch(`${baseUrl}/veiculos`, {
                    method: 'POST',
                    headers: { 
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': csrfToken
                    },
                    body: JSON.stringify(data)
                });
                const result = await response.json();
                if (result.status === 'success') {
                    bootstrap.Modal.getInstance(document.getElementById('addVeiculoModal')).hide();
                    loadVeiculos();
                } else {
                    alert(result.message);
                }
            } catch (error) {
                alert('Erro ao salvar veículo');
            }
        });

        document.getElementById('logoutBtn').addEventListener('click', async () => {
            await fetch(`${baseUrl}/logout`, { 
                method: 'POST',
                headers: { 'X-CSRF-TOKEN': csrfToken }
            });
            window.location.href = `${baseUrl}/`;
        });

        loadVeiculos();
    </script>
</body>
</html>
