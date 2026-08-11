<?php
session_start();
require_once __DIR__ . '/includes/functions.php';

$id = (int) ($_GET['id'] ?? 0);
$stmt = db()->prepare('SELECT * FROM produtos WHERE id = :id AND ativo = 1');
$stmt->execute(['id' => $id]);
$produto = $stmt->fetch();

if (!$produto) {
    header('Location: index.php');
    exit;
}

$nomeLoja = get_config('nome_loja', 'Minha Loja');
$paginaTitulo = $produto['nome'] . ' - ' . $nomeLoja;

require __DIR__ . '/includes/header.php';
?>

<div class="container">
    <a class="voltar" href="index.php">&larr; Voltar para a loja</a>

    <div class="produto-detalhe">
        <div>
            <img src="<?= imagem_produto($produto['imagem']) ?>" alt="<?= h($produto['nome']) ?>">
        </div>
        <div>
            <h1><?= h($produto['nome']) ?></h1>
            <p><?= nl2br(h($produto['descricao'])) ?></p>
            <div class="preco" style="font-size:1.6rem;"><?= formatar_preco($produto['preco']) ?></div>

            <?php if (!empty($produto['link_externo'])): ?>
                <a class="btn btn-bloco" target="_blank" rel="noopener"
                   href="<?= h($produto['link_externo']) ?>">
                    Comprar agora
                </a>
            <?php elseif (pix_configurado()): ?>
                <a class="btn btn-bloco" href="pix.php?produto_id=<?= (int) $produto['id'] ?>">
                    Comprar com Pix
                </a>
            <?php else: ?>
                <div class="alerta alerta-info">
                    Compra via Pix ainda nao configurada. Fale com a loja pelo WhatsApp para comprar este produto.
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php require __DIR__ . '/includes/footer.php'; ?>
