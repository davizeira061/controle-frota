<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $title ?? 'Login - Controle de Frota'; ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body { background-color: #f8f9fa; height: 100vh; display: flex; align-items: center; justify-content: center; }
        .login-card { width: 100%; max-width: 400px; padding: 2rem; border-radius: 10px; box-shadow: 0 4px 6px rgba(0,0,0,0.1); background: white; }
    </style>
</head>
<body>
    <div class="login-card">
        <h3 class="text-center mb-4">Controle de Frota</h3>
        <div id="alert" class="alert d-none"></div>
        <form id="loginForm">
            <div class="mb-3">
                <label for="email" class="form-label">E-mail</label>
                <input type="email" class="form-control" id="email" required>
            </div>
            <div class="mb-3">
                <label for="senha" class="form-label">Senha</label>
                <input type="password" class="form-control" id="senha" required>
            </div>
            <button type="submit" class="btn btn-primary w-100">Entrar</button>
        </form>
    </div>

    <script>
        const baseUrl = '<?php echo $baseUrl; ?>';
        const csrfToken = '<?php echo $csrfToken; ?>';

        document.getElementById('loginForm').addEventListener('submit', async (e) => {
            e.preventDefault();
            const email = document.getElementById('email').value;
            const senha = document.getElementById('senha').value;
            const alert = document.getElementById('alert');

            try {
                const response = await fetch(`${baseUrl}/login`, {
                    method: 'POST',
                    headers: { 
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': csrfToken
                    },
                    body: JSON.stringify({ email, senha })
                });

                const result = await response.json();

                if (result.status === 'success') {
                    alert.className = 'alert alert-success';
                    alert.textContent = 'Login realizado! Redirecionando...';
                    alert.classList.remove('d-none');
                    setTimeout(() => window.location.href = `${baseUrl}/dashboard`, 1000);
                } else {
                    alert.className = 'alert alert-danger';
                    alert.textContent = result.message || 'Erro ao fazer login';
                    alert.classList.remove('d-none');
                }
            } catch (error) {
                console.error(error);
                alert.className = 'alert alert-danger';
                alert.textContent = 'Erro na requisição';
                alert.classList.remove('d-none');
            }
        });
    </script>
</body>
</html>
