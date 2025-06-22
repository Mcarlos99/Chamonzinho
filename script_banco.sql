-- 1. CRIAÇÃO DO BANCO DE DADOS
-- Execute este script no MySQL para criar a estrutura

CREATE DATABASE extremes_deputado_chamonzinho CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

USE extremes_deputado_chamonzinho;

-- Tabela para armazenar os cadastros
CREATE TABLE cadastros (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nome VARCHAR(255) NOT NULL,
    cidade VARCHAR(100) NOT NULL,
    cargo VARCHAR(100) NOT NULL,
    telefone VARCHAR(20) NOT NULL,
    data_nascimento DATE NOT NULL,
    observacoes TEXT,
    data_cadastro TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    ip_address VARCHAR(45),
    status ENUM('ativo', 'inativo') DEFAULT 'ativo',
    INDEX idx_nome (nome),
    INDEX idx_cidade (cidade),
    INDEX idx_data_cadastro (data_cadastro)
);

-- Tabela para administradores
CREATE TABLE administradores (
    id INT AUTO_INCREMENT PRIMARY KEY,
    usuario VARCHAR(50) NOT NULL UNIQUE,
    senha VARCHAR(255) NOT NULL,
    nome VARCHAR(255) NOT NULL,
    email VARCHAR(255),
    ultimo_acesso TIMESTAMP NULL,
    status ENUM('ativo', 'inativo') DEFAULT 'ativo',
    data_criacao TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Inserir administrador padrão (senha: admin123)
INSERT INTO administradores (usuario, senha, nome, email) VALUES 
('admin', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Administrador', 'admin@chamonzinho.com.br');

-- Tabela para logs de acesso
CREATE TABLE logs_acesso (
    id INT AUTO_INCREMENT PRIMARY KEY,
    tipo ENUM('cadastro', 'admin_login', 'admin_action') NOT NULL,
    descricao TEXT,
    ip_address VARCHAR(45),
    user_agent TEXT,
    data_hora TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_tipo (tipo),
    INDEX idx_data_hora (data_hora)
);