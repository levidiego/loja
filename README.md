# Loja Virtual do Sinval

Loja virtual simples em PHP + MySQL, feita para funcionar em hospedagem
compartilhada com cPanel (sem necessidade de Node.js, build ou instalação de
dependencias).

## O que a loja faz

- Pagina inicial chamativa com banner, logo e vitrine de produtos.
- Pagina de cada produto com botao **Comprar**:
  - Se o produto tiver um **link externo** cadastrado (Kiwify ou similar), o botao abre esse link.
  - Se nao tiver, o botao leva para uma tela de **pagamento via Pix** (QR Code + codigo "Copia e Cola"), gerada a partir da chave Pix da loja. Nao e necessario ter conta em nenhum gateway de pagamento.
  - Depois de pagar, o cliente informa nome e WhatsApp e e redirecionado para o WhatsApp da loja com uma mensagem pronta, para enviar o comprovante.
- Painel administrativo (`/admin`) para:
  - Cadastrar, editar, excluir e ativar/desativar produtos (com imagem, preco, descricao e link externo opcional).
  - Ver os pedidos recebidos e marcar como **Pago** ou **Cancelado**.
  - Configurar nome da loja, cores, logo, banner, WhatsApp e chave Pix.

Nao ha carrinho de compras: cada produto tem seu proprio botao de compra, o
que mantem a loja simples e facil de usar no celular.

## Estrutura do projeto

```
sinval/
├── admin/              painel administrativo
├── assets/             css, imagens e uploads
├── config/             config.example.php (copiar para config.php)
├── database/           schema.sql (importar no phpMyAdmin)
├── includes/           codigo compartilhado (conexao, funcoes, layout)
├── index.php           pagina inicial (vitrine)
├── produto.php         pagina de um produto
└── pix.php             pagamento via Pix
```

## Como publicar no cPanel

### 1. Criar o banco de dados

No cPanel, em **Bancos de dados > Assistente de banco de dados** (ou MySQL
Databases):

1. Crie um banco de dados, por exemplo `usuario_loja`.
2. Crie um usuario e uma senha, e associe esse usuario ao banco com todos os privilegios.
3. Anote o nome do banco, usuario e senha (o cPanel geralmente prefixa com `usuario_`).

Depois, abra o **phpMyAdmin**, selecione o banco criado, vá em **Importar** e
envie o arquivo `database/schema.sql` deste projeto. Isso cria as tabelas
`admins`, `configuracoes`, `produtos` e `pedidos`.

### 2. Enviar os arquivos para a hospedagem

Duas opcoes, use a que preferir:

- **Gerenciador de Arquivos / FTP**: comprima a pasta `sinval` em `.zip`,
  envie para `public_html` (ou para a pasta do dominio/subdominio do
  cliente) e extraia lá.
- **Controle de Versao do Git** (disponivel no cPanel, visto no seu painel):
  suba este projeto para um repositorio Git e use essa ferramenta para
  clonar/atualizar direto no servidor.

Se o site vai ficar num subdominio (ex: `loja.dominiodocliente.com.br`),
crie o subdominio em **Dominios** apontando para a pasta onde os arquivos
foram enviados.

### 3. Configurar a conexao com o banco

Dentro da pasta `config/` no servidor, copie `config.example.php` para
`config.php` e preencha com os dados do banco criado no passo 1:

```php
define('DB_HOST', 'localhost');
define('DB_NAME', 'usuario_loja');
define('DB_USER', 'usuario_loja');
define('DB_PASS', 'senha_do_banco');
define('SITE_URL', 'https://dominiodocliente.com.br');
```

O arquivo `config.php` nunca deve ser enviado a um repositorio Git publico
(ele ja esta no `.gitignore`).

### 4. Criar o acesso do administrador

Acesse `https://seudominio.com.br/admin/` no navegador. Como ainda nao
existe nenhum administrador cadastrado, a loja vai pedir para criar o
primeiro usuario e senha. Depois disso essa tela nao aparece mais — o
acesso passa a ser feito por `admin/login.php`.

### 5. Configurar a loja

No painel, va em **Configuracoes** e preencha:

- Nome da loja, frase de destaque, cor principal, logo e banner.
- WhatsApp da loja (formato `55DDDNUMERO`, ex: `5511987654321`).
- Chave Pix, nome do titular e cidade do titular — usados para gerar o QR Code de pagamento.

### 6. Cadastrar produtos

Em **Produtos > Novo produto**, cadastre nome, descricao, preco e imagem.

- Para vender por um checkout externo (Kiwify ou similar), cole o link de
  pagamento no campo **Link externo**.
- Para vender direto pela loja com Pix, deixe o campo **Link externo** em
  branco.

## Seguranca

- As pastas `config/`, `includes/` e `database/` tem `.htaccess` bloqueando
  acesso direto pelo navegador.
- As pastas de imagens enviadas (`assets/img/produtos` e `assets/img/uploads`)
  bloqueiam a execucao de PHP, mesmo que algum arquivo indevido seja
  enviado.
- Senhas de administrador sao armazenadas com hash (`password_hash`), nunca
  em texto puro.
- Formularios do painel administrativo usam token CSRF.

## Observacoes sobre o pagamento via Pix

O Pix desta loja e **manual**: a loja gera o QR Code/codigo Pix a partir da
chave Pix cadastrada, mas a confirmacao do pagamento e feita pelo proprio
lojista (o cliente avisa pelo WhatsApp e o lojista confere o recebimento no
app do banco e marca o pedido como "Pago" no painel). Isso evita a
necessidade de criar conta em um gateway de pagamentos para o primeiro
lancamento da loja. Se no futuro for necessario Pix automatico (confirmacao
instantanea via webhook), isso exige integrar um gateway como Mercado Pago,
Efi ou PagSeguro — uma evolucao possivel para uma versao futura.
