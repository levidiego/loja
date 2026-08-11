<?php
session_start();
require_once __DIR__ . '/includes/functions.php';

$nomeLoja = get_config('nome_loja', 'Minha Loja');
$subtitulo = get_config('subtitulo', '');
$banner = get_config('banner', '');
$paginaTitulo = $nomeLoja;

$stmt = db()->query('SELECT * FROM produtos WHERE ativo = 1 ORDER BY ordem ASC, id DESC');
$produtos = $stmt->fetchAll();

require __DIR__ . '/includes/header.php';
?>

<div class="hero">
    <h1><?= h($nomeLoja) ?></h1>
    <?php if ($subtitulo): ?><p><?= h($subtitulo) ?></p><?php endif; ?>
    <?php if ($banner): ?><img class="banner" src="<?= h($banner) ?>" alt="<?= h($nomeLoja) ?>"><?php endif; ?>
</div>

<div class="container">
    <div class="secao-titulo">
        <h2>Nossos produtos</h2>
    </div>

    <?php if (empty($produtos)): ?>
        <div class="vazio">Em breve novidades por aqui. Volte mais tarde!</div>
    <?php else: ?>
        <div class="grid-produtos">
            <?php foreach ($produtos as $produto): ?>
                <a class="card-produto" href="produto.php?id=<?= (int) $produto['id'] ?>">
                    <img class="imagem" src="<?= imagem_produto($produto['imagem']) ?>" alt="<?= h($produto['nome']) ?>">
                    <div class="corpo">
                        <h3><?= h($produto['nome']) ?></h3>
                        <div class="descricao"><?= h(mb_strimwidth($produto['descricao'] ?? '', 0, 90, '...')) ?></div>
                        <div class="preco"><?= formatar_preco($produto['preco']) ?></div>
                        <span class="btn btn-bloco">Ver produto</span>
                    </div>
                </a>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>

<?php require __DIR__ . '/includes/footer.php'; ?>
