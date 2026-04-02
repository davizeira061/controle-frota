# ✅ CHECKLIST DE INSTALAÇÃO

Use este documento para validar que tudo está funcionando.

---

## 🔧 FASE 1: Banco de Dados

### ✓ Banco criado?
```bash
mysql -u root -p
SHOW DATABASES;
# Deve listar: controle_frota
```

### ✓ Tabelas criadas?
```sql
USE controle_frota;
SHOW TABLES;
# Deve listar: empresas, usuarios, veiculos, motoristas, registros_uso, logs, etc
```

### ✓ Super Admin criado?
```sql
SELECT * FROM super_admin_users;
SELECT * FROM empresas LIMIT 1;
SELECT * FROM usuarios WHERE email = 'admin@empresa.test';
```

---

## ⚙️ FASE 2: Configuração

### ✓ .env configurado?
```bash
cat .env
# Deve ter:
# DB_HOST=localhost
# DB_NAME=controle_frota
# DB_USER=root
```

### ✓ Permissões de arquivo?
```bash
# Windows: Não precisa, mas checar que arquivos estão acessíveis
dir c:\wamp64\www\controle-frota
```

### ✓ PHP >= 8.0?
```bash
php -v
# Deve mostrar: PHP 8.x.x
```

---

## 🌐 FASE 3: Servidor Web

### ✓ Apache mod_rewrite ativo?
```bash
# Windows: no phpinfo() buscar mod_rewrite ou
# Éditar httpd.conf: LoadModule rewrite_module modules/mod_rewrite.so
```

### ✓ Base URL acessível?
```bash
curl http://localhost/controle-frota/public/
# Deve retornar JSON erro 404 (bom sinal!)
```

### ✓ .htaccess funcionando?
```bash
# Se não funcionar, revisar:
# 1. AllowOverride All em VirtualHost
# 2. mod_rewrite ativo
# 3. .htaccess no diretório public/
```

---

## 📡 FASE 4: API Básica

### ✓ Route 404 é JSON?
```bash
curl http://localhost/controle-frota/public/nao-existe
# Deve retornar: {"status":"error","message":"Rota não encontrada..."}
```

### ✓ POST /register funciona?
```bash
curl -X POST http://localhost/controle-frota/public/register \
  -H "Content-Type: application/json" \
  -d '{
    "nome": "Teste Setup",
    "email": "test@setup.local",
    "senha": "123456"
  }'

# Deve retornar: {"status":"success","data":{"tenant_id":N}}
```

### ✓ Empresa criada no banco?
```sql
SELECT * FROM empresas ORDER BY id DESC LIMIT 1;
# Deve ter: Teste Setup, test@setup.local
```

### ✓ Login funciona?
```bash
curl -X POST http://localhost/controle-frota/public/login \
  -H "Content-Type: application/json" \
  -c cookies.txt \
  -d '{
    "email": "test@setup.local",
    "senha": "123456"
  }'

# Deve retornar: {"status":"success","data":{"id":N,...}}
```

### ✓ Sessão mantida?
```bash
curl http://localhost/controle-frota/public/usuarios \
  -b cookies.txt

# Deve listar usuários (não erro 401)
```

---

## 🚗 FASE 5: Funcionalidades

### ✓ Criar Veículo
```bash
curl -X POST http://localhost/controle-frota/public/veiculos \
  -H "Content-Type: application/json" \
  -b cookies.txt \
  -d '{
    "placa": "TEST0001",
    "modelo": "Teste",
    "marca": "Teste"
  }'
```

### ✓ Listar Veículos
```bash
curl http://localhost/controle-frota/public/veiculos \
  -b cookies.txt
# Deve retornar: {"status":"success","data":{"data":[...]}}
```

### ✓ Criar Motorista
```bash
curl -X POST http://localhost/controle-frota/public/motoristas \
  -H "Content-Type: application/json" \
  -b cookies.txt \
  -d '{
    "nome": "Motorista Teste",
    "cpf": "12345678901"
  }'
```

### ✓ Iniciar Registro
```bash
curl -X POST http://localhost/controle-frota/public/registros \
  -H "Content-Type: application/json" \
  -b cookies.txt \
  -d '{
    "veiculo_id": 3,
    "motorista_id": 3,
    "quilometragem_inicial": 5000,
    "status_veiculo": "ok"
  }'
```

### ✓ Logs registrados?
```sql
SELECT * FROM logs ORDER BY id DESC LIMIT 5;
# Deve ter: LOGIN, CRIAR_EMPRESA, CRIAR_VEICULO, etc
```

---

## 🔒 FASE 6: Segurança

### ✓ Sem autenticação = erro?
```bash
curl http://localhost/controle-frota/public/veiculos
# Deve retornar 401: {"status":"error","message":"Não autenticado"}
```

### ✓ Admin check funciona?
```bash
# Criar usuário normal
curl -X POST http://localhost/controle-frota/public/usuarios \
  -b cookies.txt \
  -d '{"nome":"Teste","email":"teste@local","role":"operador"}'

# Tentar deletar com operador
curl -X DELETE http://localhost/controle-frota/public/usuarios/1 \
  -b cookies.txt
# Deve retornar 403: Acesso negado
```

### ✓ Tenant isolation?
```sql
-- Login como empresa 1
-- Criar veículo
-- Verificar tenant_id no banco
SELECT * FROM veiculos WHERE placa = 'TEST0001';
# Deve ter: tenant_id = 1
```

### ✓ SQL Injection protegido?
```bash
# Tentar injeção
curl -X POST http://localhost/controle-frota/public/login \
  -H "Content-Type: application/json" \
  -d '{
    "email": "admin\' OR \\'1\\'=\\'1",
    "senha": "anything"
  }'
# Deve falhar com erro de login (não quebrar security)
```

---

## 📊 FASE 7: Performance

### ✓ Índices criados?
```sql
SHOW INDEX FROM usuarios;
SHOW INDEX FROM veiculos;
SHOW INDEX FROM registros_uso;
# Cada tabela deve ter índices
```

### ✓ Query lenta?
```bash
# Ativar query log
SET GLOBAL slow_query_log = 'ON';

# Executar operação
curl http://localhost/controle-frota/public/veiculos -b cookies.txt

# Checar logs (deve ser rápido <1s)
```

---

## 🐛 FASE 8: Troubleshooting

### Erro 403 (Forbidden)?
```
✓ AllowOverride All em vhost?
✓ .htaccess arquivo pode ser lido?
✓ Permissões de pasta (Windows não bloqueia geralmente)
```

### Erro 404 (Not Found)?
```
✓ mod_rewrite ativo?
✓ Está acessando /public/?
✓ .htaccess copiado corretamente?
```

### Erro de Banco?
```
✓ Banco rodando? (mysql -u root)
✓ .env com dados corretos?
✓ Banco/tabelas criadas? (SHOW DATABASES, SHOW TABLES)
✓ Usuário root com acesso? (mysql -u root -p)
```

### SSL/COOKIES?
```
✓ SESSION_SECURE=false em .env (se HTTP)
✓ SESSION_SECURE=true (se HTTPS)
✓ Browsers antigos = desabilitar SameSite=Strict
```

---

## ✨ Tudo OK?

Se todos os testes passaram, você tem um sistema SaaS 100% funcional! 🎉

### Próximos passos:
1. Criar dashboard admin
2. Integrar front-end (React/Vue)
3. Configurar email
4. Setup CI/CD
5. Deploy em produção

---

## 📞 Erros comuns

| Erro | Causa | Solução |
|------|-------|---------|
| Rota não encontrada | .htaccess falhando | Checar mod_rewrite + AllowOverride |
| 401 Não autenticado | Sem sessão | Login primeiro, manter cookies |
| DB Connection error | Credenciais .env | Verificar DB_HOST, DB_USER, DB_PASS |
| Página branca | PHP erro | Ativar DEBUG=true no .env |
| Acesso negado (403) | Permissões arquivos | Windows: desbloquear propriedades arquivo |

---

Data: __________
Nome testador: __________
✅ Tudo funcionando!
