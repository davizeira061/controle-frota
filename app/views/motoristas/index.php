<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $title ?? 'Gestão de Motoristas'; ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
</head>
<body class="bg-light">
    <nav class="navbar navbar-expand-lg navbar-dark bg-primary mb-4">
        <div class="container">
            <a class="navbar-brand" href="#">Controle de Frota</a>
            <div class="collapse navbar-collapse">
                <ul class="navbar-nav me-auto">
                    <li class="nav-item"><a class="nav-link" href="<?php echo $baseUrl; ?>/dashboard">Dashboard</a></li>
                    <li class="nav-item"><a class="nav-link" href="<?php echo $baseUrl; ?>/veiculos">Veículos</a></li>
                    <li class="nav-item"><a class="nav-link active" href="<?php echo $baseUrl; ?>/motoristas">Motoristas</a></li>
                    <li class="nav-item"><a class="nav-link" href="<?php echo $baseUrl; ?>/registros">Uso de Veículos</a></li>
                    <li class="nav-item"><a class="nav-link" href="<?php echo $baseUrl; ?>/usuarios">Usuários</a></li>
                </ul>
                <button id="logoutBtn" class="btn btn-outline-light btn-sm">Sair</button>
            </div>
        </div>
    </nav>

    <div class="container">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h2>Motoristas</h2>
            <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addMotoristaModal">
                <i class="bi bi-person-plus"></i> Novo Motorista
            </button>
        </div>

        <div class="card shadow-sm">
            <div class="card-body p-0">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>Nome</th>
                            <th>CPF</th>
                            <th>CNH</th>
                            <th>Telefone</th>
                            <th>Status</th>
                            <th>Ações</th>
                        </tr>
                    </thead>
                    <tbody id="motoristasList">
                        <tr><td colspan="6" class="text-center p-4">Carregando motoristas...</td></tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Modal Novo Motorista -->
    <div class="modal fade" id="addMotoristaModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <form id="motoristaForm">
                    <div class="modal-header">
                        <h5 class="modal-title">Cadastrar Novo Motorista</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <div class="mb-3">
                            <label class="form-label">Nome Completo</label>
                            <input type="text" class="form-control" name="nome" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">CPF</label>
                            <input type="text" class="form-control" name="cpf" placeholder="000.000.000-00">
                        </div>
                        <div class="mb-3">
                            <label class="form-label">CNH</label>
                            <input type="text" class="form-control" name="cnh">
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Telefone</label>
                            <input type="text" class="form-control" name="telefone">
                        </div>
                        <div class="mb-3">
                            <label class="form-label">E-mail</label>
                            <input type="email" class="form-control" name="email">
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Fechar</button>
                        <button type="submit" class="btn btn-primary">Salvar Motorista</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        const baseUrl = '<?php echo $baseUrl; ?>';
        const csrfToken = '<?php echo $csrfToken; ?>';

        async function loadMotoristas() {
            try {
                const response = await fetch(`${baseUrl}/motoristas?api=1`);
                const result = await response.json();
                const list = document.getElementById('motoristasList');
                list.innerHTML = '';

                if (result.status === 'success' && result.data.data) {
                    result.data.data.forEach(m => {
                        list.innerHTML += `
                            <tr>
                                <td>${m.nome}</td>
                                <td>${m.cpf || '-'}</td>
                                <td>${m.cnh || '-'}</td>
                                <td>${m.telefone || '-'}</td>
                                <td><span class="badge bg-${m.ativo ? 'success' : 'danger'}">${m.ativo ? 'Ativo' : 'Inativo'}</span></td>
                                <td>
                                    <button class="btn btn-sm btn-outline-primary"><i class="bi bi-pencil"></i></button>
                                </td>
                            </tr>
                        `;
                    });
                }
            } catch (err) {
                console.error(err);
            }
        }

        document.getElementById('motoristaForm').addEventListener('submit', async (e) => {
            e.preventDefault();
            const data = Object.fromEntries(new FormData(e.target));
            try {
                const res = await fetch(`${baseUrl}/motoristas`, {
                    method: 'POST',
                    headers: { 
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': csrfToken
                    },
                    body: JSON.stringify(data)
                });
                const result = await res.json();
                if (result.status === 'success') {
                    bootstrap.Modal.getInstance(document.getElementById('addMotoristaModal')).hide();
                    loadMotoristas();
                } else {
                    alert(result.message);
                }
            } catch (err) {
                alert('Erro ao salvar motorista');
            }
        });

        document.getElementById('logoutBtn').addEventListener('click', async () => {
            await fetch(`${baseUrl}/logout`, { 
                method: 'POST',
                headers: { 'X-CSRF-TOKEN': csrfToken }
            });
            window.location.href = `${baseUrl}/`;
        });

        loadMotoristas();
    </script>
</body>
</html>
