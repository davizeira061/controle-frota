<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $title ?? 'Gestão de Usuários'; ?></title>
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
                    <li class="nav-item"><a class="nav-link" href="<?php echo $baseUrl; ?>/motoristas">Motoristas</a></li>
                    <li class="nav-item"><a class="nav-link" href="<?php echo $baseUrl; ?>/registros">Uso de Veículos</a></li>
                    <li class="nav-item"><a class="nav-link active" href="<?php echo $baseUrl; ?>/usuarios">Usuários</a></li>
                </ul>
                <button id="logoutBtn" class="btn btn-outline-light btn-sm">Sair</button>
            </div>
        </div>
    </nav>

    <div class="container">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h2>Gestão de Usuários</h2>
            <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addUserModal">
                <i class="bi bi-person-plus"></i> Novo Usuário
            </button>
        </div>

        <div class="card shadow-sm">
            <div class="card-body p-0">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>Nome</th>
                            <th>E-mail</th>
                            <th>Role</th>
                            <th>Status</th>
                            <th>Último Login</th>
                            <th>Ações</th>
                        </tr>
                    </thead>
                    <tbody id="usuariosList">
                        <tr><td colspan="6" class="text-center p-4">Carregando usuários...</td></tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Modal Novo Usuário -->
    <div class="modal fade" id="addUserModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <form id="userForm">
                    <div class="modal-header">
                        <h5 class="modal-title">Cadastrar Novo Usuário</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <div id="tempPasswordAlert" class="alert alert-warning d-none">
                            <strong>Atenção!</strong> Usuário criado. Senha temporária: <span id="tempPasswordDisplay" class="fw-bold"></span>
                            <p class="small mb-0">Copie esta senha agora, ela não será exibida novamente.</p>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Nome Completo</label>
                            <input type="text" class="form-control" name="nome" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">E-mail</label>
                            <input type="email" class="form-control" name="email" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Perfil de Acesso</label>
                            <select class="form-select" name="role" required>
                                <option value="operador">Operador</option>
                                <option value="motorista">Motorista</option>
                                <option value="admin">Administrador</option>
                            </select>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal" id="closeModalBtn">Fechar</button>
                        <button type="submit" class="btn btn-primary" id="saveUserBtn">Salvar Usuário</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        const baseUrl = '<?php echo $baseUrl; ?>';
        const csrfToken = '<?php echo $csrfToken; ?>';

        async function loadUsers() {
            try {
                const response = await fetch(`${baseUrl}/usuarios?api=1`);
                const result = await response.json();
                const list = document.getElementById('usuariosList');
                list.innerHTML = '';

                if (result.status === 'success' && result.data) {
                    result.data.forEach(u => {
                        list.innerHTML += `
                            <tr>
                                <td>${u.nome} ${u.senha_temporaria ? '<span class="badge bg-warning text-dark small">Troca Pendente</span>' : ''}</td>
                                <td>${u.email}</td>
                                <td><span class="badge bg-secondary">${u.role.toUpperCase()}</span></td>
                                <td><span class="badge bg-${u.ativo ? 'success' : 'danger'}">${u.ativo ? 'Ativo' : 'Inativo'}</span></td>
                                <td class="small">${u.ultimo_login ? new Date(u.ultimo_login).toLocaleString() : 'Nunca'}</td>
                                <td>
                                    <button class="btn btn-sm btn-outline-danger" onclick="deleteUser(${u.id})"><i class="bi bi-trash"></i></button>
                                </td>
                            </tr>
                        `;
                    });
                }
            } catch (err) {
                console.error(err);
            }
        }

        document.getElementById('userForm').addEventListener('submit', async (e) => {
            e.preventDefault();
            const data = Object.fromEntries(new FormData(e.target));
            const saveBtn = document.getElementById('saveUserBtn');
            const closeBtn = document.getElementById('closeModalBtn');
            
            saveBtn.disabled = true;
            try {
                const res = await fetch(`${baseUrl}/usuarios`, {
                    method: 'POST',
                    headers: { 
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': csrfToken
                    },
                    body: JSON.stringify(data)
                });
                const result = await res.json();
                
                if (result.status === 'success') {
                    document.getElementById('tempPasswordDisplay').textContent = result.data.temporary_password;
                    document.getElementById('tempPasswordAlert').classList.remove('d-none');
                    saveBtn.classList.add('d-none');
                    closeBtn.textContent = "Entendido";
                    loadUsers();
                } else {
                    alert(result.message);
                    saveBtn.disabled = false;
                }
            } catch (err) {
                alert('Erro ao criar usuário');
                saveBtn.disabled = false;
            }
        });

        // Reset modal when closed
        document.getElementById('addUserModal').addEventListener('hidden.bs.modal', function () {
            document.getElementById('userForm').reset();
            document.getElementById('tempPasswordAlert').classList.add('d-none');
            document.getElementById('saveUserBtn').classList.remove('d-none');
            document.getElementById('saveUserBtn').disabled = false;
            document.getElementById('closeModalBtn').textContent = "Fechar";
        });

        async function deleteUser(id) {
            if (!confirm('Deseja realmente remover este usuário?')) return;
            try {
                await fetch(`${baseUrl}/usuarios/${id}`, { 
                    method: 'DELETE',
                    headers: { 'X-CSRF-TOKEN': csrfToken }
                });
                loadUsers();
            } catch (err) {
                alert('Erro ao remover usuário');
            }
        }

        document.getElementById('logoutBtn').addEventListener('click', async () => {
            await fetch(`${baseUrl}/logout`, { 
                method: 'POST',
                headers: { 'X-CSRF-TOKEN': csrfToken }
            });
            window.location.href = `${baseUrl}/`;
        });

        loadUsers();
    </script>
</body>
</html>
