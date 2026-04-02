# 🚀 CONTROLE FROTA - SISTEMA SAAS MULTI-TENANT

[![PHP 8+](https://img.shields.io/badge/PHP-8%2B-blue)](https://www.php.net/)
[![MySQL](https://img.shields.io/badge/MySQL-8%2B-orange)](https://www.mysql.com/)
[![License MIT](https://img.shields.io/badge/License-MIT-green)](LICENSE)

---

## 📋 Visão Geral

Sistema Web **SaaS de controle de frotas** totalmente desenvolvido em **PHP puro** com arquitetura profissional, segurança de nível empresarial e escalabilidade garantida.

### ✨ Principais Features

- **✅ Multi-tenant:** Isolamento completo de dados por empresa
- **✅ Autenticação completa:** Registro auto-serviço, login, senhas temporárias
- **✅ 5 Módulos funcionais:** Empresas, Usuários, Veículos, Motoristas, Registros de Uso
- **✅ API REST:** Endpoints prontos para produção
- **✅ Router próprio:** Sistema de rotas flexível e seguro
- **✅ Logs auditoria:** Rastreamento completo de ações
- **✅ Segurança militar:** PDO, bcrypt, CSRF, Input validation
- **✅ Design patterns:** Repository, Service, MVC
- **✅ Banco robusto:** MySQL com 8 tabelas, índices, views

---

## 📂 Estrutura do Projeto

```
controle-frota/
├── 📁 app/                    # Código da aplicação
│   ├── controllers/           # Controllers (requisições HTTP)
│   ├── services/              # Lógica de negócios
│   ├── repositories/          # Acesso a dados (padrão Repository)
│   ├── middlewares/           # Auth, Admin, Tenant
│   └── models/                # (opcional) Modelos
│
├── 📁 core/                   # Framework minimalista
│   ├── Router.php             # Sistema de rotas
│   ├── Controller.php         # Controlador base
│   └── Database.php           # Gerenciador PDO
│
├── 📁 config/                 # Configurações
│   └── config.php             # Variáveis de ambiente
│
├── 📁 routes/                 # Definição de rotas
│   └── api.php                # Todas as rotas da API
│
├── 📁 bootstrap/              # Inicialização
│   └── autoload.php           # Autoloader PSR-4
│
├── 📁 public/                 # Raiz do servidor web
│   ├── index.php              # Ponto de entrada
│   └── .htaccess              # URL rewriting
│
├── 📁 storage/
│   └── logs/                  # Logs da aplicação
│
├── database.sql               # Schema SQL completo
├── .env                       # Variáveis de ambiente
├── ARCHITECTURE.md            # Documentação completa
├── GUIA_RAPIDA.md             # Quick start
├── DOCUMENTACAO_TECNICA.md    # Referência técnica
├── CHECKLIST_INSTALACAO.md    # Validação de setup
└── EXEMPLO_NOVO_MODULO.md     # Como criar novo CRUD
```

---

## 🚀 Quick Start (5 minutos)

### 1️⃣ Pré-requisitos
- PHP 8.0+
- MySQL 5.7+
- Apache com mod_rewrite

### 2️⃣ Instalação

```bash
# 1. Clonar/baixar projeto
cd c:\wamp64\www\controle-frota

# 2. Criar banco de dados
mysql -u root < database.sql

# 3. Configurar .env
DB_HOST=localhost
DB_NAME=controle_frota
DB_USER=root
DB_PASS=

# 4. Acessar
http://localhost/controle-frota/public/
```

### 3️⃣ Teste Imediato

```bash
# Registrar empresa
curl -X POST http://localhost/controle-frota/public/register \
  -H "Content-Type: application/json" \
  -d '{
    "nome": "Minha Transportadora",
    "email": "admin@empresa.com",
    "senha": "senha123"
  }'
```

✅ **Pronto!** Sistema 100% funcional.

---

## 📚 Documentação

| Documento | Conteúdo |
|-----------|----------|
| [GUIA_RAPIDA.md](GUIA_RAPIDA.md) | Exemplos de API com cURL |
| [ARCHITECTURE.md](ARCHITECTURE.md) | Visão geral completa do sistema |
| [DOCUMENTACAO_TECNICA.md](DOCUMENTACAO_TECNICA.md) | Referência de classes e padrões |
| [CHECKLIST_INSTALACAO.md](CHECKLIST_INSTALACAO.md) | Validar instalação |
| [EXEMPLO_NOVO_MODULO.md](EXEMPLO_NOVO_MODULO.md) | Como estender com novos CRUDs |

---

## 📡 API Endpoints

### 🔐 Autenticação
```
POST   /register              Registrar nova empresa
POST   /login                 Fazer login
POST   /logout                Sair
POST   /change-password       Alterar senha
```

### 👤 Usuários (admin only)
```
GET    /usuarios              Listar usuários
POST   /usuarios              Criar usuário
PUT    /usuarios/{id}         Atualizar usuário
DELETE /usuarios/{id}         Deletar usuário
```

### 🚗 Veículos
```
GET    /veiculos              Listar veículos
POST   /veiculos              Criar veículo
GET    /veiculos/{id}         Detalhes
PUT    /veiculos/{id}         Atualizar
DELETE /veiculos/{id}         Deletar
```

### 👨‍🔧 Motoristas
```
GET    /motoristas            Listar motoristas
POST   /motoristas            Criar motorista
GET    /motoristas/{id}       Detalhes
```

### 📝 Registros de Uso
```
GET    /registros             Listar registros
POST   /registros             Iniciar registro
GET    /registros/{id}        Detalhes
PUT    /registros/{id}        Finalizar registro
GET    /registros/com-avarias Registros com problemas
```

---

## 🔒 Segurança

### ✅ Implementado

```
✓ PDO Prepared Statements    → Proteção SQL Injection
✓ Password Hashing (bcrypt)  → Senhas seguras
✓ Validação de Input         → Previne ataques
✓ CSRF Protection            → Via sessão segura
✓ Multi-tenant Isolation     → Dados separados por empresa
✓ Role-based Access          → Admin, Operador, Motorista
✓ Logs de Auditoria          → Rastreamento completo
✓ HTTPS Ready                → Configurável para produção
✓ Headers de Segurança       → X-Frame-Options, X-XSS, CSP
```

---

## 🏗️ Arquitetura

### Camadas

```
Request HTTP
    ↓
Router.php (despacha para controller)
    ↓
Middleware (valida autenticação)
    ↓
Controller (recebe dados, validação básica)
    ↓
Service (lógica de negócios, validações)
    ↓
Repository (acesso a dados com tenant_id)
    ↓
Database/PDO (executa query segura)
    ↓
Response JSON
```

---

## 💡 Features Diferenciais

1. **PHP Puro:** Sem frameworks pesados
2. **Production-ready:** Security, logging, error handling
3. **Bem documentado:** 5 guias completos
4. **Padrões sólidos:** Repository, Service, MVC
5. **Multi-tenant:** Isolamento garantido

---

## 🚀 Deploys & Escalabilidade

Pronto para: Single server, Load balancing, Redis caching, Cloud services, Docker

---

<div align="center">

**Feito com ❤️ para a comunidade PHP**

[GUIA_RAPIDA.md](GUIA_RAPIDA.md) • [ARCHITECTURE.md](ARCHITECTURE.md) • [MIT License](LICENSE)

</div>
