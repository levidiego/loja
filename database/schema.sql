-- Loja Virtual - Schema do banco de dados
-- Importe este arquivo pelo phpMyAdmin (cPanel > Bancos de dados > phpMyAdmin)
-- dentro do banco de dados que voce criar para o site.

SET NAMES utf8mb4;

CREATE TABLE IF NOT EXISTS admins (
    id INT AUTO_INCREMENT PRIMARY KEY,
    usuario VARCHAR(60) NOT NULL UNIQUE,
    senha_hash VARCHAR(255) NOT NULL,
    criado_em DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS configuracoes (
    chave VARCHAR(60) PRIMARY KEY,
    valor TEXT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

INSERT INTO configuracoes (chave, valor) VALUES
    ('nome_loja', 'Minha Loja'),
    ('subtitulo', 'Artigos e produtos selecionados com carinho'),
    ('whatsapp', ''),
    ('pix_chave', ''),
    ('pix_nome', ''),
    ('pix_cidade', ''),
    ('cor_destaque', '#7a1f2b'),
    ('logo', ''),
    ('banner', '')
ON DUPLICATE KEY UPDATE chave = chave;

CREATE TABLE IF NOT EXISTS produtos (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nome VARCHAR(150) NOT NULL,
    descricao TEXT,
    preco DECIMAL(10,2) NOT NULL DEFAULT 0,
    imagem VARCHAR(255),
    link_externo VARCHAR(255) DEFAULT NULL COMMENT 'Link do Kiwify ou similar. Se preenchido, o botao Comprar abre este link.',
    ativo TINYINT(1) NOT NULL DEFAULT 1,
    ordem INT NOT NULL DEFAULT 0,
    criado_em DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS pedidos (
    id INT AUTO_INCREMENT PRIMARY KEY,
    produto_id INT NULL,
    produto_nome VARCHAR(150) NOT NULL,
    valor DECIMAL(10,2) NOT NULL,
    nome_cliente VARCHAR(150) NOT NULL,
    telefone_cliente VARCHAR(30) NOT NULL,
    status ENUM('pendente','pago','cancelado') NOT NULL DEFAULT 'pendente',
    criado_em DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_pedido_produto FOREIGN KEY (produto_id) REFERENCES produtos(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
