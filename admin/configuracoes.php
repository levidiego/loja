<?php
session_start();
require_once __DIR__ . '/../includes/functions.php';
exigir_login_admin();

$erro = '';
$sucesso = '';

$campos = ['nome_loja', 'subtitulo', 'whatsapp', 'pix_chave', 'pix_nome', 'pix_cidade', 'cor_destaque'];

$stmt = db()->query('SELECT chave, valor FROM configuracoes');
$config = [];
foreach ($stmt as $row) {
    $config[$row['chave']] = $row['valor'];
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_verificar();
    try {
        foreach ($campos as $campo) {
            set_config($campo, trim($_POST[$campo] ?? ''));
        }

        $novoLogo = salvar_imagem_enviada($_FILES['logo'] ?? null, 'assets/img/uploads', 'logo');
        if ($novoLogo) {
            set_config('logo', $novoLogo);
        }

        if (!empty($_POST['remover_banner']) && !empty($config['banner'])) {
            $caminhoAntigo = __DIR__ . '/../' . $config['banner'];
            if (is_file($caminhoAntigo)) {
                unlink($caminhoAntigo);
            }
            set_config('banner', '');
            $config['banner'] = '';
        }

        $novoBanner = salvar_imagem_enviada($_FILES['banner'] ?? null, 'assets/img/uploads', 'banner');
        if ($novoBanner) {
            set_config('banner', $novoBanner);
        }

        $sucesso = 'Configuracoes salvas com sucesso.';
    } catch (RuntimeException $e) {
        $erro = $e->getMessage();
    }

    $stmt = db()->query('SELECT chave, valor FROM configuracoes');
    $config = [];
    foreach ($stmt as $row) {
        $config[$row['chave']] = $row['valor'];
    }
}

$paginaAtual = 'configuracoes';
require __DIR__ . '/../includes/admin_header.php';
?>

<h2>Configuracoes da loja</h2>

<?php if ($erro): ?><div class="alerta alerta-erro"><?= h($erro) ?></div><?php endif; ?>
<?php if ($sucesso): ?><div class="alerta alerta-sucesso"><?= h($sucesso) ?></div><?php endif; ?>

<form method="post" action="configuracoes.php" enctype="multipart/form-data" style="max-width:560px;">
    <?= csrf_campo() ?>

    <h3 style="margin-top:0;">Identidade</h3>
    <label for="nome_loja">Nome da loja</label>
    <input type="text" id="nome_loja" name="nome_loja" value="<?= h($config['nome_loja'] ?? '') ?>" required>

    <label for="subtitulo">Frase de destaque (aparece na pagina inicial)</label>
    <input type="text" id="subtitulo" name="subtitulo" value="<?= h($config['subtitulo'] ?? '') ?>">

    <label for="cor_destaque">Cor principal do site</label>
    <input type="color" id="cor_destaque" name="cor_destaque" value="<?= h($config['cor_destaque'] ?? '#7a1f2b') ?>" style="height:44px;">

    <label for="logo">Logomarca (aparece no topo)</label>
    <?php if (!empty($config['logo'])): ?><img src="<?= h($config['logo']) ?>" style="height:50px;display:block;margin-bottom:6px;"><?php endif; ?>
    <input type="file" id="logo" name="logo" accept="image/png,image/jpeg,image/webp">

    <label for="banner">Banner de destaque (aparece na pagina inicial)</label>
    <p style="font-size:0.82rem;color:#8a8078;margin:0 0 8px;">Tamanho recomendado: 1200x400px (paisagem, proporcao 3:1). A imagem e exibida centralizada com no maximo 320px de altura, entao fotos muito verticais ou com o assunto principal nas bordas ficam cortadas. Formatos JPG, PNG ou WEBP, ate 4MB.</p>
    <?php if (!empty($config['banner'])): ?>
        <img src="<?= h($config['banner']) ?>" style="max-height:100px;display:block;margin-bottom:6px;">
        <label style="font-weight:normal;font-size:0.9rem;">
            <input type="checkbox" name="remover_banner" value="1"> Remover banner atual
        </label>
    <?php endif; ?>
    <input type="file" id="banner" name="banner" accept="image/png,image/jpeg,image/webp">

    <h3>Contato</h3>
    <label for="whatsapp">WhatsApp da loja (com DDD e DDI, ex: 5511999999999)</label>
    <input type="text" id="whatsapp" name="whatsapp" value="<?= h($config['whatsapp'] ?? '') ?>" placeholder="5511999999999">

    <h3>Pix (para receber pagamentos)</h3>
    <label for="pix_chave">Chave Pix (CPF, CNPJ, e-mail, telefone ou chave aleatoria)</label>
    <input type="text" id="pix_chave" name="pix_chave" value="<?= h($config['pix_chave'] ?? '') ?>">

    <label for="pix_nome">Nome do titular da chave Pix</label>
    <input type="text" id="pix_nome" name="pix_nome" value="<?= h($config['pix_nome'] ?? '') ?>" maxlength="25">

    <label for="pix_cidade">Cidade do titular da chave Pix</label>
    <input type="text" id="pix_cidade" name="pix_cidade" value="<?= h($config['pix_cidade'] ?? '') ?>" maxlength="15">

    <p style="font-size:0.82rem;color:#8a8078;">Esses tres campos sao usados para gerar o QR Code de pagamento Pix exibido para o cliente na hora da compra.</p>

    <button type="submit" class="btn" style="margin-top:16px;">Salvar configuracoes</button>
</form>

<?php require __DIR__ . '/../includes/admin_footer.php'; ?>
