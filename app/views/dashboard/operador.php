<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $title ?? 'Dashboard Operador - Controle de Frota'; ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
</head>
<body class="bg-light">
    <nav class="navbar navbar-expand-lg navbar-dark bg-info mb-4">
        <div class="container">
            <a class="navbar-brand" href="#"><i class="bi bi-gear-fill"></i> Operação de Frota</a>
            <div class="collapse navbar-collapse">
                <ul class="navbar-nav me-auto">
                    <li class="nav-item"><a class="nav-link active" href="<?php echo $baseUrl; ?>/dashboard">Dashboard</a></li>
                    <li class="nav-item"><a class="nav-link" href="<?php echo $baseUrl; ?>/veiculos">Veículos</a></li>
                    <li class="nav-item"><a class="nav-link" href="<?php echo $baseUrl; ?>/motoristas">Motoristas</a></li>
                    <li class="nav-item"><a class="nav-link" href="<?php echo $baseUrl; ?>/registros">Uso de Veículos</a></li>
                </ul>
                <button id="logoutBtn" class="btn btn-outline-light btn-sm">Sair</button>
            </div>
        </div>
    </nav>

    <div class="container">
        <h2 class="mb-4">Olá, <?php echo $_SESSION['nome']; ?>!</h2>
        <div class="row g-4">
            <div class="col-md-4">
                <div class="card text-white bg-info shadow-sm">
                    <div class="card-body text-center p-4">
                        <i class="bi bi-car-front h1"></i>
                        <h5 class="card-title mt-2">Veículos</h5>
                        <h2 id="totalVeiculos" class="display-4 fw-bold">0</h2>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card text-white bg-success shadow-sm">
                    <div class="card-body text-center p-4">
                        <i class="bi bi-person-badge h1"></i>
                        <h5 class="card-title mt-2">Motoristas</h5>
                        <h2 id="totalMotoristas" class="display-4 fw-bold">0</h2>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card text-white bg-warning shadow-sm">
                    <div class="card-body text-center p-4">
                        <i class="bi bi-play-circle h1"></i>
                        <h5 class="card-title mt-2">Usos Ativos</h5>
                        <h2 id="usosAtivos" class="display-4 fw-bold">0</h2>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        const baseUrl = '<?php echo $baseUrl; ?>';
        const csrfToken = '<?php echo $csrfToken; ?>';

        async function loadStats() {
            try {
                const response = await fetch(`${baseUrl}/dashboard?api=1`);
                const result = await response.json();
                
                if (result.status === 'success') {
                    document.getElementById('totalVeiculos').textContent = result.data.veiculos_total;
                    document.getElementById('totalMotoristas').textContent = result.data.motoristas_total;
                    document.getElementById('usosAtivos').textContent = result.data.usos_ativos;
                }
            } catch (err) {
                console.error(err);
            }
        }

        document.getElementById('logoutBtn').addEventListener('click', async () => {
            await fetch(`${baseUrl}/logout`, { 
                method: 'POST',
                headers: { 'X-CSRF-TOKEN': csrfToken }
            });
            window.location.href = `${baseUrl}/`;
        });

        loadStats();
    </script>
</body>
</html>
