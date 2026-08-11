<?php
session_start();
require_once __DIR__ . '/../includes/functions.php';
exigir_login_admin();

$id = (int) ($_GET['id'] ?? $_POST['id'] ?? 0);
$produto = ['nome' => '', 'descricao' => '', 'preco' => '', 'imagem' => '', 'link_externo' => '', 'ativo' => 1, 'ordem' => 0];
$erro = '';

if ($id) {
    $stmt = db()->prepare('SELECT * FROM produtos WHERE id = :id');
    $stmt->execute(['id' => $id]);
    $encontrado = $stmt->fetch();
    if ($encontrado) {
        $produto = $encontrado;
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_verificar();
    $produto['nome'] = trim($_POST['nome'] ?? '');
    $produto['descricao'] = trim($_POST['descricao'] ?? '');
    $produto['preco'] = str_replace(',', '.', trim($_POST['preco'] ?? '0'));
    $produto['link_externo'] = trim($_POST['link_externo'] ?? '');
    $produto['ativo'] = isset($_POST['ativo']) ? 1 : 0;
    $produto['ordem'] = (int) ($_POST['ordem'] ?? 0);

    if ($produto['nome'] === '') {
        $erro = 'Informe o nome do produto.';
    } elseif (!is_numeric($produto['preco']) || (float) $produto['preco'] < 0) {
        $erro = 'Informe um preco valido.';
    } else {
        try {
            $novaImagem = salvar_imagem_produto($_FILES['imagem'] ?? null);
            if ($novaImagem) {
                $produto['imagem'] = $novaImagem;
            }

            if ($id) {
                $stmt = db()->prepare(
                    'UPDATE produtos SET nome=:nome, descricao=:descricao, preco=:preco, imagem=:imagem,
                     link_externo=:link_externo, ativo=:ativo, ordem=:ordem WHERE id=:id'
                );
                $stmt->execute([
                    'nome' => $produto['nome'],
                    'descricao' => $produto['descricao'],
                    'preco' => $produto['preco'],
                    'imagem' => $produto['imagem'],
                    'link_externo' => $produto['link_externo'] ?: null,
                    'ativo' => $produto['ativo'],
                    'ordem' => $produto['ordem'],
                    'id' => $id,
                ]);
            } else {
                $stmt = db()->prepare(
                    'INSERT INTO produtos (nome, descricao, preco, imagem, link_externo, ativo, ordem)
                     VALUES (:nome, :descricao, :preco, :imagem, :link_externo, :ativo, :ordem)'
                );
                $stmt->execute([
                    'nome' => $produto['nome'],
                    'descricao' => $produto['descricao'],
                    'preco' => $produto['preco'],
                    'imagem' => $produto['imagem'],
                    'link_externo' => $produto['link_externo'] ?: null,
                    'ativo' => $produto['ativo'],
                    'ordem' => $produto['ordem'],
                ]);
            }
            header('Location: produtos.php?msg=' . rawurlencode('Produto salvo com sucesso.'));
            exit;
        } catch (RuntimeException $e) {
            $erro = $e->getMessage();
        }
    }
}

$paginaAtual = 'produtos';
require __DIR__ . '/../includes/admin_header.php';
?>

<h2><?= $id ? 'Editar produto' : 'Novo produto' ?></h2>

<?php if ($erro): ?><div class="alerta alerta-erro"><?= h($erro) ?></div><?php endif; ?>

<form method="post" action="produto_form.php" enctype="multipart/form-data" style="max-width:520px;">
    <?= csrf_campo() ?>
    <input type="hidden" name="id" value="<?= (int) $id ?>">

    <label for="nome">Nome do produto</label>
    <input type="text" id="nome" name="nome" required value="<?= h($produto['nome']) ?>">

    <label for="descricao">Descricao</label>
    <textarea id="descricao" name="descricao" rows="4"><?= h($produto['descricao']) ?></textarea>

    <label for="preco">Preco (R$)</label>
    <input type="text" id="preco" name="preco" required value="<?= h($produto['preco']) ?>" placeholder="Ex: 49.90">

    <label for="imagem">Imagem do produto</label>
    <?php if (!empty($produto['imagem'])): ?>
        <img src="<?= h($produto['imagem']) ?>" alt="" style="width:100px;border-radius:8px;margin:6px 0;display:block;">
    <?php endif; ?>
    <input type="file" id="imagem" name="imagem" accept="image/png,image/jpeg,image/webp">

    <label for="link_externo">Link externo de compra (Kiwify ou similar) &mdash; opcional</label>
    <input type="text" id="link_externo" name="link_externo" value="<?= h($produto['link_externo']) ?>" placeholder="https://pay.kiwify.com.br/...">
    <p style="font-size:0.82rem;color:#8a8078;margin-top:4px;">Se preenchido, o botao "Comprar" leva direto para esse link. Se deixar em branco, o cliente paga via Pix pela propria loja.</p>

    <label for="ordem">Ordem de exibicao (menor aparece primeiro)</label>
    <input type="number" id="ordem" name="ordem" value="<?= (int) $produto['ordem'] ?>">

    <label style="display:flex;align-items:center;gap:8px;margin-top:16px;">
        <input type="checkbox" name="ativo" style="width:auto;" <?= $produto['ativo'] ? 'checked' : '' ?>>
        Produto ativo (visivel na loja)
    </label>

    <button type="submit" class="btn" style="margin-top:20px;">Salvar produto</button>
    <a href="produtos.php" class="btn btn-secundario" style="margin-top:20px;">Cancelar</a>
</form>

<?php require __DIR__ . '/../includes/admin_footer.php'; ?>
