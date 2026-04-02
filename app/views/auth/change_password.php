<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $title ?? 'Alterar Senha'; ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body { background-color: #f8f9fa; height: 100vh; display: flex; align-items: center; justify-content: center; }
        .card-change { width: 100%; max-width: 400px; padding: 2rem; border-radius: 10px; box-shadow: 0 4px 6px rgba(0,0,0,0.1); background: white; }
    </style>
</head>
<body>
    <div class="card-change">
        <h3 class="text-center mb-4">Alterar Senha</h3>
        <p class="text-muted small text-center">Para sua segurança, você deve alterar sua senha antes de prosseguir.</p>
        <div id="alert" class="alert d-none"></div>
        <form id="changePasswordForm">
            <div class="mb-3">
                <label class="form-label">Nova Senha</label>
                <input type="password" class="form-control" name="nova_senha" required minlength="6">
            </div>
            <div class="mb-3">
                <label class="form-label">Confirme a Nova Senha</label>
                <input type="password" class="form-control" name="confirmacao_senha" required minlength="6">
            </div>
            <button type="submit" class="btn btn-primary w-100">Atualizar Senha</button>
        </form>
    </div>

    <script>
        const baseUrl = '<?php echo $baseUrl; ?>';
        const csrfToken = '<?php echo $csrfToken; ?>';

        document.getElementById('changePasswordForm').addEventListener('submit', async (e) => {
            e.preventDefault();
            const data = Object.fromEntries(new FormData(e.target));
            const alert = document.getElementById('alert');

            if (data.nova_senha !== data.confirmacao_senha) {
                alert.className = 'alert alert-danger';
                alert.textContent = 'As senhas não conferem.';
                alert.classList.remove('d-none');
                return;
            }

            try {
                const response = await fetch(`${baseUrl}/change-password`, {
                    method: 'POST',
                    headers: { 
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': csrfToken
                    },
                    body: JSON.stringify(data)
                });

                const text = await response.text();
                let result;
                
                try {
                    result = JSON.parse(text);
                } catch (e) {
                    console.error('Resposta não é JSON:', text);
                    alert.className = 'alert alert-danger';
                    alert.innerHTML = `<strong>Erro no Servidor:</strong><br><small>${text.substring(0, 200)}...</small>`;
                    alert.classList.remove('d-none');
                    return;
                }

                if (result.status === 'success') {
                    alert.className = 'alert alert-success';
                    alert.textContent = 'Senha atualizada! Redirecionando...';
                    alert.classList.remove('d-none');
                    setTimeout(() => window.location.href = `${baseUrl}/dashboard`, 1500);
                } else {
                    alert.className = 'alert alert-danger';
                    alert.textContent = result.message || 'Erro ao alterar senha';
                    alert.classList.remove('d-none');
                }
            } catch (error) {
                console.error('Erro na requisição:', error);
                alert.className = 'alert alert-danger';
                alert.textContent = 'Erro de conexão ou rede: ' + error.message;
                alert.classList.remove('d-none');
            }
        });
    </script>
</body>
</html>
