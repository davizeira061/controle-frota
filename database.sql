-- ===================================
-- DATABASE SETUP - CONTROLE FROTA
-- ===================================

-- Criar banco de dados
CREATE DATABASE IF NOT EXISTS controle_frota CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE controle_frota;

-- ===================================
-- TABELA: EMPRESAS (TENANTS)
-- ===================================
CREATE TABLE IF NOT EXISTS empresas (
    id INT PRIMARY KEY AUTO_INCREMENT,
    nome VARCHAR(255) NOT NULL,
    email VARCHAR(255) NOT NULL UNIQUE,
    telefone VARCHAR(20),
    cnpj VARCHAR(20),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    deleted_at TIMESTAMP NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ===================================
-- TABELA: USUARIOS
-- ===================================
CREATE TABLE IF NOT EXISTS usuarios (
    id INT PRIMARY KEY AUTO_INCREMENT,
    tenant_id INT NOT NULL,
    nome VARCHAR(255) NOT NULL,
    email VARCHAR(255) NOT NULL,
    senha VARCHAR(255) NOT NULL,
    role ENUM('admin', 'operador', 'motorista') DEFAULT 'operador',
    senha_temporaria TINYINT DEFAULT 0,
    ativo TINYINT DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    deleted_at TIMESTAMP NULL,
    FOREIGN KEY (tenant_id) REFERENCES empresas(id) ON DELETE CASCADE,
    UNIQUE KEY unique_email_tenant (email, tenant_id),
    INDEX idx_tenant_id (tenant_id),
    INDEX idx_email (email)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ===================================
-- TABELA: VEICULOS
-- ===================================
CREATE TABLE IF NOT EXISTS veiculos (
    id INT PRIMARY KEY AUTO_INCREMENT,
    tenant_id INT NOT NULL,
    placa VARCHAR(20) NOT NULL UNIQUE,
    modelo VARCHAR(255) NOT NULL,
    marca VARCHAR(100),
    cor VARCHAR(50),
    ano_fabricacao INT,
    quilometragem_total INT DEFAULT 0,
    status ENUM('ativo', 'manutencao', 'inativo') DEFAULT 'ativo',
    ativo TINYINT DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    deleted_at TIMESTAMP NULL,
    FOREIGN KEY (tenant_id) REFERENCES empresas(id) ON DELETE CASCADE,
    INDEX idx_tenant_id (tenant_id),
    INDEX idx_placa (placa),
    INDEX idx_status (status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ===================================
-- TABELA: MOTORISTAS
-- ===================================
CREATE TABLE IF NOT EXISTS motoristas (
    id INT PRIMARY KEY AUTO_INCREMENT,
    tenant_id INT NOT NULL,
    nome VARCHAR(255) NOT NULL,
    cpf VARCHAR(20),
    cnh VARCHAR(20),
    telefone VARCHAR(20),
    email VARCHAR(255),
    data_admissao DATE,
    ativo TINYINT DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    deleted_at TIMESTAMP NULL,
    FOREIGN KEY (tenant_id) REFERENCES empresas(id) ON DELETE CASCADE,
    INDEX idx_tenant_id (tenant_id),
    INDEX idx_cpf (cpf),
    UNIQUE KEY unique_cpf_tenant (cpf, tenant_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ===================================
-- TABELA: REGISTROS DE USO (CHECKLISTS)
-- ===================================
CREATE TABLE IF NOT EXISTS registros_uso (
    id INT PRIMARY KEY AUTO_INCREMENT,
    tenant_id INT NOT NULL,
    veiculo_id INT NOT NULL,
    motorista_id INT NOT NULL,
    usuario_id INT NOT NULL,
    quilometragem_inicial INT NOT NULL,
    quilometragem_final INT,
    combustivel_inicial VARCHAR(50),
    combustivel_final VARCHAR(50),
    status_veiculo ENUM('ok', 'avarias', 'critico') DEFAULT 'ok',
    descricao_avarias TEXT,
    observacoes TEXT,
    data_hora_inicio DATETIME NOT NULL,
    data_hora_fim DATETIME,
    ativo TINYINT DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (tenant_id) REFERENCES empresas(id) ON DELETE CASCADE,
    FOREIGN KEY (veiculo_id) REFERENCES veiculos(id) ON DELETE CASCADE,
    FOREIGN KEY (motorista_id) REFERENCES motoristas(id) ON DELETE CASCADE,
    FOREIGN KEY (usuario_id) REFERENCES usuarios(id) ON DELETE CASCADE,
    INDEX idx_tenant_id (tenant_id),
    INDEX idx_veiculo_id (veiculo_id),
    INDEX idx_motorista_id (motorista_id),
    INDEX idx_data (data_hora_inicio)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ===================================
-- TABELA: LOGS GLOBAIS (AUDITORIA)
-- ===================================
CREATE TABLE IF NOT EXISTS logs (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT,
    tenant_id INT,
    acao VARCHAR(100) NOT NULL,
    modulo VARCHAR(50),
    descricao TEXT,
    ip VARCHAR(45),
    user_agent VARCHAR(255),
    dados_antigos JSON,
    dados_novos JSON,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES usuarios(id) ON DELETE SET NULL,
    FOREIGN KEY (tenant_id) REFERENCES empresas(id) ON DELETE CASCADE,
    INDEX idx_tenant_id (tenant_id),
    INDEX idx_user_id (user_id),
    INDEX idx_acao (acao),
    INDEX idx_created_at (created_at),
    INDEX idx_modulo (modulo)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ===================================
-- TABELA: MANUTENCOES
-- ===================================
CREATE TABLE IF NOT EXISTS manutencoes (
    id INT PRIMARY KEY AUTO_INCREMENT,
    tenant_id INT NOT NULL,
    veiculo_id INT NOT NULL,
    tipo ENUM('preventiva', 'corretiva') DEFAULT 'preventiva',
    descricao TEXT NOT NULL,
    data_inicio DATE NOT NULL,
    data_conclusao DATE,
    custo DECIMAL(10, 2),
    status ENUM('agendada', 'em_andamento', 'concluida', 'cancelada') DEFAULT 'agendada',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (tenant_id) REFERENCES empresas(id) ON DELETE CASCADE,
    FOREIGN KEY (veiculo_id) REFERENCES veiculos(id) ON DELETE CASCADE,
    INDEX idx_tenant_id (tenant_id),
    INDEX idx_veiculo_id (veiculo_id),
    INDEX idx_status (status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ===================================
-- DADOS DE TESTE (OPCIONAL)
-- ===================================

-- Inserir empresa de teste
INSERT INTO empresas (nome, email, cnpj) VALUES 
('Empresa Teste LTDA', 'admin@empresa.test', '12.345.678/0001-00');

-- Obter ID da empresa (será 1)
SET @tenant_id = LAST_INSERT_ID();

-- Inserir usuário admin da empresa
-- Senha: admin123 (hash gerado com password_hash)
INSERT INTO usuarios (tenant_id, nome, email, senha, role, senha_temporaria) VALUES 
(@tenant_id, 'Admin Empresa', 'admin@empresa.test', '$2y$10$n3ThGlPJLm8Zg0L9V8qh5eP2Q9R0S1T2U3V4W5X6Y7Z8A9B0C1D2', 'admin', 0);

-- Inserir motorista de teste
INSERT INTO motoristas (tenant_id, nome, cpf, email) VALUES 
(@tenant_id, 'João Silva', '12345678901', 'joao@empresa.test');

-- Inserir veículos de teste
INSERT INTO veiculos (tenant_id, placa, modelo, marca, cor, ano_fabricacao, status) VALUES 
(@tenant_id, 'ABC1234', 'Saveiro', 'Volkswagen', 'Branco', 2022, 'ativo'),
(@tenant_id, 'XYZ5678', 'Logan', 'Renault', 'Cinza', 2021, 'ativo');

-- ===================================
-- SUPER ADMIN (GLOBAL)
-- ===================================

-- Criar tabela para super admin (fora do multi-tenant)
CREATE TABLE IF NOT EXISTS super_admin_users (
    id INT PRIMARY KEY AUTO_INCREMENT,
    nome VARCHAR(255) NOT NULL,
    email VARCHAR(255) NOT NULL UNIQUE,
    senha VARCHAR(255) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_email (email)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Inserir super admin de teste
-- Senha: superadmin123
INSERT INTO super_admin_users (nome, email, senha) VALUES 
('Super Admin', 'superadmin@controlefrota.test', '$2y$10$m2QhGlPJLm8Zg0L9V8qh5eP2Q9R0S1T2U3V4W5X6Y7Z8A9B0C1D2');

-- ===================================
-- VIEWS ÚTEIS
-- ===================================

-- View: Dados completos de registros de uso
CREATE OR REPLACE VIEW v_registros_completos AS
SELECT 
    ru.id,
    ru.tenant_id,
    e.nome as empresa_nome,
    ru.veiculo_id,
    v.placa,
    v.modelo,
    ru.motorista_id,
    m.nome as motorista_nome,
    ru.quilometragem_inicial,
    ru.quilometragem_final,
    ru.status_veiculo,
    ru.descricao_avarias,
    ru.data_hora_inicio,
    ru.data_hora_fim,
    ru.created_at
FROM registros_uso ru
JOIN empresas e ON ru.tenant_id = e.id
JOIN veiculos v ON ru.veiculo_id = v.id
JOIN motoristas m ON ru.motorista_id = m.id;

-- View: Último acesso de cada veículo
CREATE OR REPLACE VIEW v_ultimo_acesso_veiculo AS
SELECT 
    v.id,
    v.placa,
    v.modelo,
    MAX(ru.data_hora_fim) as ultimo_acesso,
    MAX(ru.quilometragem_final) as ultima_quilometragem,
    v.quilometragem_total
FROM veiculos v
LEFT JOIN registros_uso ru ON v.id = ru.veiculo_id
GROUP BY v.id, v.placa, v.modelo, v.quilometragem_total;

-- ===================================
-- ÍNDICES ADICIONAIS PARA PERFORMANCE
-- ===================================

ALTER TABLE registros_uso ADD INDEX idx_motor_data (motorista_id, data_hora_inicio);
ALTER TABLE veiculos ADD INDEX idx_tenant_status (tenant_id, status);
ALTER TABLE usuarios ADD INDEX idx_tenant_role (tenant_id, role);
