# 🚗 GUIA RÁPIDO - CONTROLE FROTA

## ⚡ Setup Rápido (5 minutos)

### 1️⃣ Banco de Dados
```bash
# SSH no banco
mysql -u root

# Importar SQL
source /c/wamp64/www/controle-frota/database.sql;
```

### 2️⃣ Configurar .env
```
DB_HOST=localhost
DB_NAME=controle_frota  
DB_USER=root
DB_PASS=
DEBUG=true
```

### 3️⃣ Apache .htaccess
Acessar via `public/` ou configurar VirtualHost

---

## 🔥 Exemplos de Uso (cURL)

### Registrar Empresa
```bash
curl -X POST http://localhost/controle-frota/public/register \
  -H "Content-Type: application/json" \
  -d '{
    "nome": "Transportes Silva",
    "email": "admin@silva.com",
    "senha": "senha123"
  }'
```

**Retorno:**
```json
{
  "status": "success",
  "message": "Empresa registrada com sucesso",
  "data": {
    "tenant_id": 1
  }
}
```

### Login
```bash
curl -X POST http://localhost/controle-frota/public/login \
  -H "Content-Type: application/json" \
  -c cookies.txt \
  -d '{
    "email": "admin@silva.com",
    "senha": "senha123"
  }'
```

### Criar Veículo
```bash
curl -X POST http://localhost/controle-frota/public/veiculos \
  -H "Content-Type: application/json" \
  -b cookies.txt \
  -d '{
    "placa": "ABC1234",
    "modelo": "Saveiro",
    "marca": "VW",
    "cor": "Branco",
    "ano_fabricacao": 2022
  }'
```

### Listar Veículos
```bash
curl http://localhost/controle-frota/public/veiculos \
  -b cookies.txt
```

### Criar Motorista
```bash
curl -X POST http://localhost/controle-frota/public/motoristas \
  -H "Content-Type: application/json" \
  -b cookies.txt \
  -d '{
    "nome": "João Silva",
    "cpf": "12345678901",
    "email": "joao@silva.com",
    "telefone": "11999999999"
  }'
```

### Iniciar Registro de Uso
```bash
curl -X POST http://localhost/controle-frota/public/registros \
  -H "Content-Type: application/json" \
  -b cookies.txt \
  -d '{
    "veiculo_id": 1,
    "motorista_id": 1,
    "quilometragem_inicial": 10000,
    "combustivel_inicial": "cheio",
    "status_veiculo": "ok"
  }'
```

### Finalizar Registro
```bash
curl -X PUT http://localhost/controle-frota/public/registros/1 \
  -H "Content-Type: application/json" \
  -b cookies.txt \
  -d '{
    "quilometragem_final": 10150,
    "combustivel_final": "meia",
    "status_veiculo": "ok"
  }'
```

### Criar Usuário (admin)
```bash
curl -X POST http://localhost/controle-frota/public/usuarios \
  -H "Content-Type: application/json" \
  -b cookies.txt \
  -d '{
    "nome": "Operador",
    "email": "operador@silva.com",
    "role": "operador"
  }'
```

### Listar Usuários (admin)
```bash
curl http://localhost/controle-frota/public/usuarios \
  -b cookies.txt
```

### Alterar Senha
```bash
curl -X POST http://localhost/controle-frota/public/change-password \
  -H "Content-Type: application/json" \
  -b cookies.txt \
  -d '{
    "nova_senha": "novaSenha123",
    "confirmacao_senha": "novaSenha123"
  }'
```

### Logout
```bash
curl -X POST http://localhost/controle-frota/public/logout \
  -b cookies.txt
```

---

## 📊 Roles e Permissões

| Role | Descrição | Permissões |
|------|-----------|-----------|
| **admin** | Administrador da empresa | Total acesso |
| **operador** | Gerencia operações | Ver/criar registros, motoristas |
| **motorista** | Usa o sistema | Apenas fazer checklists |

---

## 🗂️ Estrutura de Pasta - O que vai aonde?

```
Controllers/        → Recebem requests (GET, POST, PUT, DELETE)
Services/          → Lógica de negócios (validação, cálculos)
Repositories/      → Acesso ao banco (SELECT, INSERT, UPDATE)
Middlewares/       → Autenticação e autorização
Models/            → (opcional) Validações de modelo
```

---

## 🔍 Entender o Fluxo

### Requisição: POST /veiculos
```
1. Router.php      → Identifica POST /veiculos → VeiculoController@store
2. Route Middleware → Valida autenticação (AuthMiddleware)
3. Controller      → Recebe dados, chama service
4. Service         → Valida e processa dados
5. Repository      → Insere no banco (com tenant_id)
6. Response        → JSON com resultado
```

---

## ⚡ Performance

### Índices Criados
- `tenant_id` em todas as tabelas
- `email` + `tenant_id` em usuários
- `placa` em veículos
- `data_hora_inicio` em registros
- Compound indexes para queries comuns

---

## 🐛 Troubleshooting

### Erro "Rota não encontrada"
- Verificar URL: `/controle-frota/public/veiculos`
- Testar com POST primeiro (JSON)

### Erro de conexão MySQL
- Verificar .env (DB_HOST, DB_NAME, DB_USER, DB_PASS)
- Testar: `mysql -u root -p controle_frota`

### Erro 401 Não Autenticado
- Login primeiro
- Manter cookies: `-b cookies.txt`

### SQL Injection (NÃO DEVE ACONTECER)
- Sistema usa prepared statements
- NUNCA concatenar query + input

---

## 📝 Dados de Teste

Ao executar SQL, criamos:

```
Empresa: "Empresa Teste LTDA" (admin@empresa.test)
Admin:   Senha = admin123 (hash no banco)
Motorista: João Silva
Veículos: 2 (ABC1234, XYZ5678)
```

---

## 🚀 Expandir Sistema

### Adicionar Nova Rota
1. Criar Controller: `app/controllers/NovoController.php`
2. Adicionar em routes: `$router->get('/rota', 'NovoController@metodo')`
3. Implementar método no controller

### Adicionar Tabela
1. Adicionar SQL em `database.sql`
2. Criar Repository: `app/repositories/NovoRepository.php`
3. Criar Service: `app/services/NovoService.php`
4. Criar Controller: `app/controllers/NovoController.php`

---

## 💡 Dicas

- Sempre incluir `tenant_id` em queries
- Soft delete (marcar `deleted_at`)
- Registrar ações em `logs` table
- Validar entrada em Service
- Escapar saída em Response

---

## 📞 Debug

### Ver Todas as Rotas
```php
// No final de api.php
var_dump($router->getRoutes());
```

### Ver Logs de DB
```sql
SELECT * FROM logs ORDER BY created_at DESC LIMIT 20;
```

### Ver Sessão
```php
// Qualquer lugar
var_dump($_SESSION);
```

---

Pronto! Sistema 100% funcional e pronto para produção! 🎉
