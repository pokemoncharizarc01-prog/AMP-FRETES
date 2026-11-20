CREATE DATABASE IF NOT EXISTS amp_fretes
  CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE amp_fretes;

CREATE TABLE IF NOT EXISTS produtos (
  id INT AUTO_INCREMENT PRIMARY KEY,
  nome VARCHAR(120) NOT NULL,
  descricao TEXT NULL,
  peso DECIMAL(10,2) DEFAULT 0,
  valor DECIMAL(10,2) DEFAULT 0,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS enderecos (
  id INT AUTO_INCREMENT PRIMARY KEY,
  identificacao VARCHAR(100) NOT NULL,
  logradouro VARCHAR(150) NOT NULL,
  numero VARCHAR(20) NULL,
  bairro VARCHAR(100) NULL,
  cidade VARCHAR(100) NOT NULL,
  estado CHAR(2) NOT NULL,
  cep VARCHAR(10) NULL,
  complemento VARCHAR(120) NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS transportadores (
  id INT AUTO_INCREMENT PRIMARY KEY,
  nome VARCHAR(120) NOT NULL,
  documento VARCHAR(20) NULL,
  telefone VARCHAR(30) NULL,
  email VARCHAR(120) NULL,
  tipo_veiculo VARCHAR(80) NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS fretes (
  id INT AUTO_INCREMENT PRIMARY KEY,
  codigo VARCHAR(30) NOT NULL UNIQUE,
  origem_id INT NOT NULL,
  destino_id INT NOT NULL,
  transportador_id INT NOT NULL,
  data_frete DATE NOT NULL,
  status ENUM('Pendente','Em transporte','Entregue','Cancelado') NOT NULL DEFAULT 'Pendente',
  observacoes TEXT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT fk_frete_origem FOREIGN KEY (origem_id) REFERENCES enderecos(id),
  CONSTRAINT fk_frete_destino FOREIGN KEY (destino_id) REFERENCES enderecos(id),
  CONSTRAINT fk_frete_transportador FOREIGN KEY (transportador_id) REFERENCES transportadores(id)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS frete_produtos (
  id INT AUTO_INCREMENT PRIMARY KEY,
  frete_id INT NOT NULL,
  produto_id INT NOT NULL,
  quantidade INT NOT NULL DEFAULT 1,
  CONSTRAINT fk_fp_frete FOREIGN KEY (frete_id) REFERENCES fretes(id) ON DELETE CASCADE,
  CONSTRAINT fk_fp_produto FOREIGN KEY (produto_id) REFERENCES produtos(id),
  UNIQUE KEY uk_frete_produto (frete_id, produto_id)
) ENGINE=InnoDB;

-- Dados demonstrativos opcionais
INSERT INTO produtos (nome, descricao, peso, valor)
SELECT 'Caixa de componentes', 'Produto demonstrativo', 12.50, 450.00
WHERE NOT EXISTS (SELECT 1 FROM produtos WHERE nome='Caixa de componentes');
INSERT INTO enderecos (identificacao, logradouro, numero, bairro, cidade, estado, cep)
SELECT 'Depósito AMP', 'Rua das Indústrias', '100', 'Centro', 'Francisco Beltrão', 'PR', '85601-000'
WHERE NOT EXISTS (SELECT 1 FROM enderecos WHERE identificacao='Depósito AMP');
INSERT INTO enderecos (identificacao, logradouro, numero, bairro, cidade, estado, cep)
SELECT 'Cliente - Unidade 1', 'Avenida Brasil', '250', 'Centro', 'Pato Branco', 'PR', '85501-000'
WHERE NOT EXISTS (SELECT 1 FROM enderecos WHERE identificacao='Cliente - Unidade 1');
INSERT INTO transportadores (nome, documento, telefone, email, tipo_veiculo)
SELECT 'Transportadora Exemplo', '00.000.000/0001-00', '(46) 3000-0000', 'contato@exemplo.com', 'Caminhão baú'
WHERE NOT EXISTS (SELECT 1 FROM transportadores WHERE nome='Transportadora Exemplo');
