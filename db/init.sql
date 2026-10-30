-- DSK - Gestao de Custo
-- INSTALACAO LIMPA: cria somente o banco projetoindividual.
-- Executar este arquivo apaga e recria esse banco e todos os dados dele.
SET NAMES utf8mb4;

-- ATENCAO: somente projetoindividual sera apagado; outros bancos nao sao alterados.
DROP DATABASE IF EXISTS projetoindividual;

CREATE DATABASE projetoindividual
  CHARACTER SET utf8mb4
  COLLATE utf8mb4_unicode_ci;

CREATE USER IF NOT EXISTS 'dsk'@'localhost' IDENTIFIED BY 'dsk123';
CREATE USER IF NOT EXISTS 'dsk'@'127.0.0.1' IDENTIFIED BY 'dsk123';
ALTER USER 'dsk'@'localhost' IDENTIFIED BY 'dsk123';
ALTER USER 'dsk'@'127.0.0.1' IDENTIFIED BY 'dsk123';
GRANT ALL PRIVILEGES ON projetoindividual.* TO 'dsk'@'localhost';
GRANT ALL PRIVILEGES ON projetoindividual.* TO 'dsk'@'127.0.0.1';

USE projetoindividual;

CREATE TABLE usuarios (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT,
  nome VARCHAR(150) NOT NULL,
  email VARCHAR(150) NOT NULL,
  senha VARCHAR(255) NOT NULL,
  perfil ENUM('admin','cliente') NOT NULL DEFAULT 'cliente',
  admin_unico TINYINT GENERATED ALWAYS AS (CASE WHEN perfil = 'admin' THEN 1 ELSE NULL END) STORED,
  status ENUM('ativo','inadimplente','inativo') NOT NULL DEFAULT 'ativo',
  status_motivo VARCHAR(255) DEFAULT NULL,
  status_alterado_em DATETIME DEFAULT NULL,
  criado_em TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  UNIQUE KEY uq_usuarios_email (email),
  UNIQUE KEY uq_usuarios_admin_unico (admin_unico),
  CONSTRAINT ck_usuarios_admin_principal CHECK (perfil <> 'admin' OR email = 'dsk.danillo@gmail.com')
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE clientes (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT,
  usuario_id INT UNSIGNED NOT NULL,
  telefone VARCHAR(30) DEFAULT NULL,
  cidade VARCHAR(100) DEFAULT NULL,
  tipo_veiculo VARCHAR(40) DEFAULT NULL,
  modelo_veiculo VARCHAR(80) DEFAULT NULL,
  placa VARCHAR(10) DEFAULT NULL,
  consumo_km_l DECIMAL(6,2) DEFAULT NULL,
  preco_combustivel DECIMAL(8,3) DEFAULT NULL,
  valor_mensalidade DECIMAL(10,2) NOT NULL DEFAULT 49.90,
  dia_vencimento TINYINT UNSIGNED NOT NULL DEFAULT 10,
  criado_em TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  UNIQUE KEY uq_clientes_usuario (usuario_id),
  UNIQUE KEY uq_clientes_telefone (telefone),
  CONSTRAINT fk_clientes_usuario FOREIGN KEY (usuario_id) REFERENCES usuarios (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE rotas (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT,
  cliente_id INT UNSIGNED NOT NULL,
  origem VARCHAR(255) NOT NULL,
  destino VARCHAR(255) NOT NULL,
  km DECIMAL(10,2) NOT NULL,
  ida_volta TINYINT(1) NOT NULL DEFAULT 0,
  valor_km DECIMAL(10,2) NOT NULL,
  valor_receber DECIMAL(10,2) NOT NULL,
  data_rota DATE NOT NULL,
  criado_em TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  KEY idx_rotas_cliente_data (cliente_id, data_rota),
  CONSTRAINT fk_rotas_cliente FOREIGN KEY (cliente_id) REFERENCES clientes (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE custos_rota (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT,
  rota_id INT UNSIGNED NOT NULL,
  categoria VARCHAR(40) NOT NULL,
  descricao VARCHAR(200) DEFAULT NULL,
  valor DECIMAL(10,2) NOT NULL,
  data_custo DATE NOT NULL,
  criado_em TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  KEY idx_custos_rota (rota_id),
  CONSTRAINT fk_custos_rota FOREIGN KEY (rota_id) REFERENCES rotas (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE mensalidades (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT,
  cliente_id INT UNSIGNED NOT NULL,
  referencia DATE NOT NULL COMMENT 'primeiro dia do mes de referencia',
  valor DECIMAL(10,2) NOT NULL,
  vencimento DATE NOT NULL,
  pago_em DATE DEFAULT NULL,
  forma_pagamento VARCHAR(30) DEFAULT NULL,
  criado_em TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  UNIQUE KEY uq_mensalidade_cliente_mes (cliente_id, referencia),
  KEY idx_mensalidade_vencimento (vencimento),
  CONSTRAINT fk_mensalidade_cliente FOREIGN KEY (cliente_id) REFERENCES clientes (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO usuarios (nome, email, senha, perfil, status)
VALUES (
  'Administrador DSK',
  'dsk.danillo@gmail.com',
  '$2y$10$7Lm8yjGmJei33F7KrDBcnuBKWJ6FjXoq6dlBDoFQ/lwWA2kw/YZFa',
  'admin',
  'ativo'
);
