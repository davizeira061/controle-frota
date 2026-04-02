<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $title ?? 'Auditoria de Logs - Master'; ?></title>
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
                    <li class="nav-item"><a class="nav-link" href="<?php echo $baseUrl; ?>/admin/empresas">Empresas</a></li>
                    <li class="nav-item"><a class="nav-link active" href="<?php echo $baseUrl; ?>/admin/logs">Logs Globais</a></li>
                </ul>
                <button id="logoutBtn" class="btn btn-outline-light btn-sm">Sair</button>
            </div>
        </div>
    </nav>

    <div class="container">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h2>Auditoria Global</h2>
            <button class="btn btn-outline-secondary btn-sm" onclick="loadLogs()"><i class="bi bi-arrow-clockwise"></i> Atualizar</button>
        </div>

        <div class="card shadow-sm">
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>Data/Hora</th>
                                <th>Empresa</th>
                                <th>Usuário</th>
                                <th>Ação</th>
                                <th>Descrição</th>
                                <th>IP</th>
                            </tr>
                        </thead>
                        <tbody id="logsList">
                            <tr><td colspan="6" class="text-center p-4">Carregando logs...</td></tr>
                        </tbody>
                    </table>
                </div>
            </div>
            <div class="card-footer bg-white d-flex justify-content-between align-items-center">
                <span id="logCount" class="text-muted small">Mostrando 0 registros</span>
                <nav>
                    <ul class="pagination pagination-sm mb-0" id="pagination"></ul>
                </nav>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        const baseUrl = '<?php echo $baseUrl; ?>';
        let currentPage = 1;

        async function loadLogs(page = 1) {
            currentPage = page;
            try {
                const response = await fetch(`${baseUrl}/admin/logs?api=1&page=${page}`);
                const result = await response.json();
                
                const list = document.getElementById('logsList');
                list.innerHTML = '';

                if (result.status === 'success' && result.data.data) {
                    result.data.data.forEach(l => {
                        list.innerHTML += `
                            <tr>
                                <td class="small text-nowrap">${new Date(l.created_at).toLocaleString()}</td>
                                <td><span class="badge bg-secondary">${l.empresa_nome || 'SISTEMA'}</span></td>
                                <td>${l.usuario_nome || 'Sistema'}</td>
                                <td><span class="badge bg-info text-dark">${l.acao}</span></td>
                                <td class="small">${l.descricao}</td>
                                <td class="small text-muted">${l.ip}</td>
                            </tr>
                        `;
                    });
                    
                    document.getElementById('logCount').textContent = `Total: ${result.data.total} registros`;
                    renderPagination(result.data.pages, result.data.page);
                }
            } catch (err) {
                console.error(err);
            }
        }

        function renderPagination(pages, current) {
            const nav = document.getElementById('pagination');
            nav.innerHTML = '';
            for (let i = 1; i <= pages; i++) {
                if (i > 10) break; // Limite simples
                nav.innerHTML += `
                    <li class="page-item ${i === current ? 'active' : ''}">
                        <a class="page-item"><button class="page-link" onclick="loadLogs(${i})">${i}</button></a>
                    </li>
                `;
            }
        }

        document.getElementById('logoutBtn').addEventListener('click', async () => {
            await fetch(`${baseUrl}/logout`, { method: 'POST' });
            window.location.href = `${baseUrl}/`;
        });

        loadLogs();
    </script>
</body>
</html>
