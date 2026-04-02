# 🚗 CONTROLE FROTA - SAAS MULTI-TENANT

Sistema completo de controle de frotas desenvolvido em PHP puro com arquitetura profissional.

## 📋 Características

- ✅ **Multi-tenant:** Isolamento completo de dados por empresa
- ✅ **Autenticação:** Login, registro de empresa, senhas temporárias
- ✅ **MVC Simplificado:** Router próprio, Controllers, Services, Repositories
- ✅ **Segurança:** PDO prepared statements, password hashing, CSRF
- ✅ **Logs:** Auditoria completa de ações
- ✅ **API REST:** Endpoints funcionais
- ✅ **Banco MySQL:** Schema completo com relacionamentos

---

## 🏗️ Arquitetura

```
controle-frota/
├── app/
│   ├── controllers/        # Controllers da API
│   ├── services/          # Lógica de negócios
│   ├── repositories/      # Acesso a dados (padrão Repository)
│   ├── models/            # Modelos (se necessário)
│   └── middlewares/       # Middlewares (Auth, Admin, Tenant)
├── core/
│   ├── Router.php        # Sistema de rotas
│   ├── Controller.php    # Controller base
│   └── Database.php      # Gerenciador PDO
├── config/
│   └── config.php        # Configurações
├── routes/
│   └── api.php          # Definição de rotas
├── public/
│   ├── index.php        # Entrada da aplicação
│   └── .htaccess        # URL rewriting
├── bootstrap/
│   └── autoload.php     # Autoloader PSR-4
├── storage/
│   └── logs/            # Logs dos requests
├── database.sql         # Schema SQL
└── .env                 # Variáveis de ambiente
```

---

## ⚙️ Instalação

### 1. Clonar/Baixar projeto
```bash
cd c:\wamp64\www\controle-frota
```

### 2. Configurar .env
```bash
# Editar .env com dados do banco
DB_HOST=localhost
DB_NAME=controle_frota
DB_USER=root
DB_PASS=
```

### 3. Criar banco de dados
```bash
# MySQL CLI
mysql -u root < database.sql

# Ou importar pelo phpMyAdmin
```

### 4. Configurar servidor web (Apache)

#### Opção A: Virtual Host (recomendado)
```apache
<VirtualHost *:80>
    ServerName controle-frota.local
    ServerAlias www.controle-frota.local
    DocumentRoot "c:/wamp64/www/controle-frota/public"
    
    <Directory "c:/wamp64/www/controle-frota/public">
        AllowOverride All
        Require all granted
    </Directory>
</VirtualHost>
```

#### Opção B: URL simples
```
http://localhost/controle-frota/public/
```

### 5. Testar acesso
```bash
# Deve retornar erro 404 com JSON (rota não encontrada)
http://localhost/controle-frota/public/
```

---

## 🚀 Usando o Sistema

### Fluxo Básico

#### 1️⃣ Registrar Nova Empresa
```bash
POST /register
Content-Type: application/json

{
  "nome": "Empresa XYZ LTDA",
  "email": "admin@empresa.com",
  "senha": "senha123"
}
```

**Resposta:**
```json
{
  "status": "success",
  "message": "Empresa registrada com sucesso",
  "data": {
    "tenant_id": 1
  }
}
```

#### 2️⃣ Fazer Login
```bash
POST /login
Content-Type: application/json

{
  "email": "admin@empresa.com",
  "senha": "senha123"
}
```

**Resposta:**
```json
{
  "status": "success",
  "message": "Login realizado com sucesso",
  "data": {
    "id": 1,
    "nome": "Admin Empresa",
    "email": "admin@empresa.com",
    "role": "admin",
    "empresa_nome": "Empresa XYZ LTDA",
    "senha_temporaria": false
  }
}
```

#### 3️⃣ Criar Veículo
```bash
POST /veiculos
Content-Type: application/json
(com sessão ativa)

{
  "placa": "ABC1234",
  "modelo": "Saveiro",
  "marca": "Volkswagen",
  "cor": "Branco",
  "ano_fabricacao": 2022
}
```

#### 4️⃣ Registrar Uso de Veículo
```bash
POST /registros
Content-Type: application/json

{
  "veiculo_id": 1,
  "motorista_id": 1,
  "quilometragem_inicial": 10000,
  "combustivel_inicial": "cheio",
  "status_veiculo": "ok"
}
```

#### 5️⃣ Finalizar Registro
```bash
PUT /registros/1
Content-Type: application/json

{
  "quilometragem_final": 10150,
  "combustivel_final": "meia",
  "status_veiculo": "ok"
}
```

---

## 📡 Endpoints Disponíveis

### 🔐 Autenticação

| Método | Endpoint | Descrição |
|--------|----------|-----------|
| POST | `/register` | Registrar nova empresa |
| POST | `/login` | Login de usuário |
| POST | `/logout` | Sair do sistema |
| POST | `/change-password` | Alterar senha |

### 👥 Usuários (admin only)

| Método | Endpoint | Descrição |
|--------|----------|-----------|
| GET | `/usuarios` | Listar usuários |
| POST | `/usuarios` | Criar usuário |
| PUT | `/usuarios/{id}` | Atualizar usuário |
| DELETE | `/usuarios/{id}` | Deletar usuário |

### 🚗 Veículos

| Método | Endpoint | Descrição |
|--------|----------|-----------|
| GET | `/veiculos` | Listar veículos |
| POST | `/veiculos` | Criar veículo |
| GET | `/veiculos/{id}` | Detalhes |
| PUT | `/veiculos/{id}` | Atualizar |
| DELETE | `/veiculos/{id}` | Deletar |

### 👨‍🔧 Motoristas

| Método | Endpoint | Descrição |
|--------|----------|-----------|
| GET | `/motoristas` | Listar motoristas |
| POST | `/motoristas` | Criar motorista |
| GET | `/motoristas/{id}` | Detalhes |

### 📝 Registros de Uso

| Método | Endpoint | Descrição |
|--------|----------|-----------|
| GET | `/registros` | Listar registros |
| POST | `/registros` | Iniciar registro |
| GET | `/registros/{id}` | Detalhes |
| PUT | `/registros/{id}` | Finalizar registro |
| GET | `/registros/com-avarias` | Registros com avarias |

---

## 🔒 Segurança

### ✅ Implementado

- **PDO Prepared Statements:** Proteção contra SQL Injection
- **Password Hashing:** bcrypt com custo 10
- **Sessões Seguras:** Configurações HTTPOnly e SameSite
- **Multi-tenant:** Isolamento por tenant_id
- **Validação:** Inputs validados no Controller e Service
- **Escape:** Saída escapada com htmlspecialchars
- **Logs:** Todas as ações registradas

### 🔐 Melhorias Futuras

- Rate limiting
- JWT tokens
- OAuth2
- Autenticação 2FA
- CSP (Content Security Policy)

---

## 🛠️ Sistema de Rotas

### Como Funciona

O `Router.php` implementa um sistema simples mas poderoso:

```php
$router = new Router();

// GET simples
$router->get('/veiculos', 'VeiculoController@index');

// POST com dados
$router->post('/veiculos', 'VeiculoController@store');

// GET com parâmetro
$router->get('/veiculos/{id}', 'VeiculoController@show');

// Com middlewares
$router->delete('/veiculos/{id}', 'VeiculoController@destroy', ['auth', 'admin']);
```

### Recursos

✅ Suporte a GET, POST, PUT, DELETE
✅ Parâmetros dinâmicos: `/veiculos/{id}`
✅ Múltiplos parâmetros: `/veiculos/{id}/registros/{registroId}`
✅ Middlewares por rota
✅ Validação automática

---

## 🗄️ Banco de Dados

### Tabelas Principais

#### `empresas`
```sql
id, nome, email, telefone, cnpj, created_at, updated_at
```

#### `usuarios`
```sql
id, tenant_id, nome, email, senha, role, senha_temporaria, ativo
```

#### `veiculos`
```sql
id, tenant_id, placa, modelo, marca, cor, ano_fabricacao, status
```

#### `motoristas`
```sql
id, tenant_id, nome, cpf, cnh, telefone, email, data_admissao
```

#### `registros_uso`
```sql
id, tenant_id, veiculo_id, motorista_id, usuario_id, 
quilometragem_inicial, quilometragem_final, combustivel_inicial, 
combustivel_final, status_veiculo, descricao_avarias, data_hora_inicio
```

#### `logs`
```sql
id, user_id, tenant_id, acao, modulo, descricao, ip, user_agent, dados_novos
```

---

## 📊 Padrões Utilizados

### Repository Pattern
```php
class VeiculoRepository extends Repository {
    // Métodos específicos
    public function getByPlaca($placa, $tenantId) { ... }
}
```

### Service Pattern
```php
class VeiculoService {
    public function criar($tenantId, $dados) {
        // Validação
        // Lógica de negócios
        // Chamada ao repository
    }
}
```

### MVC
- **Model:** Repository + Database
- **View:** JSON responses
- **Controller:** Orquestra fluxos

---

## 🧪 Testando a API

### Com cURL

```bash
# Registrar empresa
curl -X POST http://localhost/register \
  -H "Content-Type: application/json" \
  -d '{"nome":"Teste","email":"test@test.com","senha":"123456"}'

# Login
curl -X POST http://localhost/login \
  -H "Content-Type: application/json" \
  -d '{"email":"test@test.com","senha":"123456"}'

# Criar veículo (com sessão)
curl -X POST http://localhost/veiculos \
  -H "Content-Type: application/json" \
  -b "PHPSESSID=..." \
  -d '{"placa":"ABC1234","modelo":"Saveiro"}'
```

### Com Postman

1. Importar coleção de rotas
2. Configurar variáveis de ambiente
3. Executar requests em sequência

---

## 📚 Próximos Passos

1. **Painel Admin:** Dashboard com charts
2. **App Mobile:** React Native/Flutter
3. **Pagamentos:** Integração com Stripe
4. **Notificações:** Email/SMS
5. **Relatórios:** Excel/PDF
6. **Cache:** Redis
7. **Queue:** Background jobs
8. **API Documentation:** Swagger/OpenAPI

---

## 📝 Notas Importantes

### Senha Temporária
Ao criar usuário, uma senha aleatória é gerada. No primeiro login, obriga a mudança.

### Multi-tenant
Toda query SEMPRE filtra por `tenant_id`. Super admin vem em tabela separada.

### Logs
Todas as ações críticas são registradas para auditoria completa.

### Soft Delete
Registros são marcados com `deleted_at` em vez de serem deletados.

---

## 🤝 Contribuindo

1. Fork o projeto
2. Crie uma branch para sua feature
3. Commit suas mudanças
4. Push e abra um Pull Request

---

## 📄 Licença

MIT License - Veja LICENSE.md

---

Desenvolvido com ❤️ como arquitetura de referência para sistemas SaaS em PHP puro.
