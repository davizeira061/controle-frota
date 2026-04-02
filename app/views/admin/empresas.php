<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $title ?? 'Gestão de Empresas - Master'; ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
</head>
<body class="bg-light">
    <nav class="navbar navbar-expand-lg navbar-dark bg-dark mb-4">
        <div class="container">
            <a class="navbar-brand" href="#"><i class="bi bi-shield-lock"></i> Master Admin</a>
            <div class="collapse navbar-collapse">
                <ul class="navbar-nav me-auto">
                    <li class="nav-item"><a class="nav-link" href="<?php echo $baseUrl; ?>/dashboard">Dashboard</a></li>
                    <li class="nav-item"><a class="nav-link active" href="<?php echo $baseUrl; ?>/admin/empresas">Empresas</a></li>
                    <li class="nav-item"><a class="nav-link" href="<?php echo $baseUrl; ?>/admin/logs">Logs Globais</a></li>
                </ul>
                <button id="logoutBtn" class="btn btn-outline-light btn-sm">Sair</button>
            </div>
        </div>
    </nav>

    <div class="container">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h2>Empresas (Tenants)</h2>
            <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addEmpresaModal">Nova Empresa</button>
        </div>

        <div class="card shadow-sm">
            <div class="card-body">
                <table class="table table-hover align-middle">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Nome</th>
                            <th>E-mail</th>
                            <th>Status</th>
                            <th>Data Cadastro</th>
                            <th>Ações</th>
                        </tr>
                    </thead>
                    <tbody id="empresasList">
                        <tr><td colspan="6" class="text-center">Carregando...</td></tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Modal Nova Empresa -->
    <div class="modal fade" id="addEmpresaModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <form id="empresaForm">
                    <div class="modal-header">
                        <h5 class="modal-title">Cadastrar Nova Empresa</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <div id="companySuccessAlert" class="alert alert-success d-none">
                            <strong>Sucesso!</strong> Empresa criada.
                            <br>Login Admin: <span id="adminEmailDisplay" class="fw-bold"></span>
                            <br>Senha Temporária: <span id="adminPassDisplay" class="fw-bold text-danger"></span>
                            <p class="small mt-2 mb-0 text-muted">Forneça estas credenciais ao cliente. Ele deverá trocar a senha no primeiro login.</p>
                        </div>
                        <div id="formFields">
                            <div class="mb-3">
                                <label class="form-label">Nome da Empresa</label>
                                <input type="text" class="form-control" name="nome" required>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">E-mail Administrativo</label>
                                <input type="email" class="form-control" name="email" required>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal" id="closeModalBtn">Fechar</button>
                        <button type="submit" class="btn btn-primary" id="saveCompanyBtn">Criar Empresa</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        const baseUrl = '<?php echo $baseUrl; ?>';
        const csrfToken = '<?php echo $csrfToken; ?>';

        async function loadEmpresas() {
            try {
                const response = await fetch(`${baseUrl}/admin/empresas?api=1`);
                const result = await response.json();
                
                const list = document.getElementById('empresasList');
                list.innerHTML = '';

                if (result.status === 'success' && result.data.data) {
                    result.data.data.forEach(e => {
                        const isAtivo = !e.deleted_at;
                        list.innerHTML += `
                            <tr>
                                <td>${e.id}</td>
                                <td>${e.nome}</td>
                                <td>${e.email}</td>
                                <td>
                                    <span class="badge bg-${isAtivo ? 'success' : 'danger'}">
                                        ${isAtivo ? 'Ativo' : 'Inativo'}
                                    </span>
                                </td>
                                <td>${new Date(e.created_at).toLocaleDateString()}</td>
                                <td>
                                    <button class="btn btn-sm btn-outline-warning me-1" 
                                            onclick="resetPassword(${e.id})" title="Resetar Senha Admin">
                                        <i class="bi bi-key"></i>
                                    </button>
                                    <button class="btn btn-sm btn-${isAtivo ? 'outline-danger' : 'outline-success'}" 
                                            onclick="toggleStatus(${e.id}, ${isAtivo ? 0 : 1})">
                                        ${isAtivo ? 'Desativar' : 'Ativar'}
                                    </button>
                                </td>
                            </tr>
                        `;
                    });
                }
            } catch (err) {
                console.error(err);
            }
        }

        async function toggleStatus(id, ativo) {
            if (!confirm('Deseja realmente alterar o status desta empresa?')) return;
            try {
                await fetch(`${baseUrl}/admin/empresas/${id}/status`, {
                    method: 'PUT',
                    headers: { 
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': csrfToken
                    },
                    body: JSON.stringify({ ativo })
                });
                loadEmpresas();
            } catch (err) {
                alert('Erro ao atualizar status');
            }
        }

        async function resetPassword(id) {
            if (!confirm('Deseja realmente resetar a senha do administrador desta empresa? Uma nova senha temporária será gerada.')) return;
            
            try {
                const res = await fetch(`${baseUrl}/admin/empresas/${id}/reset-password`, {
                    method: 'POST',
                    headers: { 
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': csrfToken
                    }
                });
                const result = await res.json();
                
                if (result.status === 'success') {
                    // Reutilizar o modal de sucesso para mostrar a nova senha
                    document.getElementById('adminEmailDisplay').textContent = result.data.email;
                    document.getElementById('adminPassDisplay').textContent = result.data.temporary_password;
                    
                    document.getElementById('formFields').classList.add('d-none');
                    document.getElementById('companySuccessAlert').classList.remove('d-none');
                    document.getElementById('saveCompanyBtn').classList.add('d-none');
                    document.getElementById('closeModalBtn').textContent = 'Fechar e Atualizar';
                    
                    const modal = new bootstrap.Modal(document.getElementById('addEmpresaModal'));
                    modal.show();
                } else {
                    alert(result.message);
                }
            } catch (err) {
                alert('Erro ao resetar senha');
            }
        }

        document.getElementById('empresaForm').addEventListener('submit', async (e) => {
            e.preventDefault();
            const data = Object.fromEntries(new FormData(e.target));
            const saveBtn = document.getElementById('saveCompanyBtn');
            const closeBtn = document.getElementById('closeModalBtn');
            const formFields = document.getElementById('formFields');
            const successAlert = document.getElementById('companySuccessAlert');

            try {
                saveBtn.disabled = true;
                const res = await fetch(`${baseUrl}/admin/empresas`, {
                    method: 'POST',
                    headers: { 
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': csrfToken
                    },
                    body: JSON.stringify(data)
                });
                const result = await res.json();
                if (result.status === 'success') {
                    // Mostrar senha temporária
                    document.getElementById('adminEmailDisplay').textContent = result.data.admin_email;
                    document.getElementById('adminPassDisplay').textContent = result.data.temporary_password;
                    
                    formFields.classList.add('d-none');
                    successAlert.classList.remove('d-none');
                    saveBtn.classList.add('d-none');
                    closeBtn.textContent = 'Entendido';
                    
                    loadEmpresas();
                } else {
                    alert(result.message);
                    saveBtn.disabled = false;
                }
            } catch (err) {
                alert('Erro ao criar empresa');
                saveBtn.disabled = false;
            }
        });

        // Reset modal ao fechar
        document.getElementById('addEmpresaModal').addEventListener('hidden.bs.modal', function () {
            document.getElementById('empresaForm').reset();
            document.getElementById('formFields').classList.remove('d-none');
            document.getElementById('companySuccessAlert').classList.add('d-none');
            document.getElementById('saveCompanyBtn').classList.remove('d-none');
            document.getElementById('saveCompanyBtn').disabled = false;
             document.getElementById('closeModalBtn').textContent = 'Fechar';
         });

         document.getElementById('logoutBtn').addEventListener('click', async () => {
             await fetch(`${baseUrl}/logout`, { 
                 method: 'POST',
                 headers: { 'X-CSRF-TOKEN': csrfToken }
             });
             window.location.href = `${baseUrl}/`;
         });

         loadEmpresas();
    </script>
</body>
</html>
