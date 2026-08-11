<?php
session_start();
require_once __DIR__ . '/../includes/functions.php';
exigir_login_admin();

$produtos = db()->query('SELECT * FROM produtos ORDER BY ordem ASC, id DESC')->fetchAll();
$paginaAtual = 'produtos';
require __DIR__ . '/../includes/admin_header.php';
?>

<div class="top-admin">
    <h2>Produtos</h2>
    <a href="produto_form.php" class="btn">+ Novo produto</a>
</div>

<?php if (!empty($_GET['msg'])): ?>
    <div class="alerta alerta-sucesso"><?= h($_GET['msg']) ?></div>
<?php endif; ?>

<?php if (empty($produtos)): ?>
    <div class="vazio">Nenhum produto cadastrado ainda. Clique em "Novo produto" para comecar.</div>
<?php else: ?>
    <table class="tabela">
        <thead>
            <tr>
                <th></th>
                <th>Nome</th>
                <th>Preco</th>
                <th>Venda via</th>
                <th>Status</th>
                <th>Acoes</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($produtos as $produto): ?>
                <tr>
                    <td><img class="thumb-tabela" src="<?= imagem_produto($produto['imagem']) ?>" alt=""></td>
                    <td><?= h($produto['nome']) ?></td>
                    <td><?= formatar_preco($produto['preco']) ?></td>
                    <td><?= $produto['link_externo'] ? 'Link externo' : 'Pix' ?></td>
                    <td>
                        <span class="badge <?= $produto['ativo'] ? 'badge-ativo' : 'badge-inativo' ?>">
                            <?= $produto['ativo'] ? 'Ativo' : 'Inativo' ?>
                        </span>
                    </td>
                    <td class="acoes-linha">
                        <a class="btn btn-secundario" href="produto_form.php?id=<?= (int) $produto['id'] ?>">Editar</a>
                        <form method="post" action="produto_excluir.php" onsubmit="return confirm('Excluir este produto?');">
                            <?= csrf_campo() ?>
                            <input type="hidden" name="id" value="<?= (int) $produto['id'] ?>">
                            <button type="submit" class="btn" style="background:#9a1f1f;">Excluir</button>
                        </form>
                    </td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
<?php endif; ?>

<?php require __DIR__ . '/../includes/admin_footer.php'; ?>
