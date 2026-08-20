<?php
session_start();
require_once __DIR__ . '/includes/functions.php';

$produtoId = (int) ($_GET['produto_id'] ?? $_POST['produto_id'] ?? 0);
$stmt = db()->prepare('SELECT * FROM produtos WHERE id = :id AND ativo = 1');
$stmt->execute(['id' => $produtoId]);
$produto = $stmt->fetch();

if (!$produto || !pix_configurado() || !empty($produto['link_externo'])) {
    header('Location: index.php');
    exit;
}

$nomeLoja = get_config('nome_loja', 'Minha Loja');
$whatsappLoja = get_config('whatsapp', '');
$erro = '';
$pedidoCriado = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_verificar();
    $nomeCliente = trim($_POST['nome_cliente'] ?? '');
    $telefoneCliente = trim($_POST['telefone_cliente'] ?? '');

    if ($nomeCliente === '' || $telefoneCliente === '') {
        $erro = 'Preencha seu nome e telefone para confirmar o pedido.';
    } else {
        $stmt = db()->prepare(
            'INSERT INTO pedidos (produto_id, produto_nome, valor, comissao, nome_cliente, telefone_cliente, status)
             VALUES (:produto_id, :produto_nome, :valor, :comissao, :nome_cliente, :telefone_cliente, "pendente")'
        );
        $stmt->execute([
            'produto_id' => $produto['id'],
            'produto_nome' => $produto['nome'],
            'valor' => $produto['preco'],
            'comissao' => $produto['comissao'],
            'nome_cliente' => $nomeCliente,
            'telefone_cliente' => $telefoneCliente,
        ]);
        $pedidoId = db()->lastInsertId();

        if ($whatsappLoja) {
            $mensagem = "Ola! Fiz o pedido #{$pedidoId} - {$produto['nome']} (" . formatar_preco($produto['preco']) . ") "
                . "e ja realizei o pagamento via Pix. Segue o comprovante:";
            header('Location: ' . link_whatsapp($whatsappLoja, $mensagem));
            exit;
        }

        $pedidoCriado = $pedidoId;
    }
}

$txid = 'PEDIDO' . $produto['id'];
$codigoPix = gerar_pix_copia_cola(
    get_config('pix_chave'),
    get_config('pix_nome'),
    get_config('pix_cidade'),
    $produto['preco'],
    substr($nomeLoja, 0, 30),
    $txid
);
$qrUrl = 'https://api.qrserver.com/v1/create-qr-code/?size=260x260&data=' . rawurlencode($codigoPix);

$paginaTitulo = 'Pagar com Pix - ' . $nomeLoja;
require __DIR__ . '/includes/header.php';
?>

<div class="container" style="max-width:640px;">
    <a class="voltar" href="produto.php?id=<?= (int) $produto['id'] ?>">&larr; Voltar para o produto</a>

    <h1>Pagamento via Pix</h1>
    <p><strong><?= h($produto['nome']) ?></strong> &mdash; <?= formatar_preco($produto['preco']) ?></p>

    <?php if ($pedidoCriado): ?>
        <div class="alerta alerta-sucesso">
            Pedido #<?= (int) $pedidoCriado ?> registrado! Envie o comprovante do Pix para a loja para confirmarmos seu pedido.
        </div>
    <?php else: ?>

        <div class="caixa-pix">
            <div class="passo"><span class="numero">1</span> Abra o app do seu banco e escolha pagar com Pix usando QR Code ou "Pix Copia e Cola".</div>
            <div class="passo"><span class="numero">2</span> Escaneie o QR Code abaixo ou copie o codigo.</div>
            <div class="passo"><span class="numero">3</span> Confirme o pagamento de <strong><?= formatar_preco($produto['preco']) ?></strong>.</div>
            <div class="passo"><span class="numero">4</span> Preencha o formulario abaixo para enviarmos seu comprovante e confirmar o pedido.</div>

            <img class="qrcode" src="<?= h($qrUrl) ?>" alt="QR Code Pix" width="260" height="260">

            <label for="codigo-pix">Ou copie o codigo Pix (Copia e Cola)</label>
            <textarea id="codigo-pix" class="codigo-copia-cola" rows="3" readonly onclick="this.select()"><?= h($codigoPix) ?></textarea>
            <button type="button" class="btn" style="margin-top:8px;" onclick="copiarCodigoPix()">Copiar codigo</button>
        </div>

        <?php if ($erro): ?><div class="alerta alerta-erro" style="margin-top:20px;"><?= h($erro) ?></div><?php endif; ?>

        <form method="post" action="pix.php" style="margin-top:24px;">
            <?= csrf_campo() ?>
            <input type="hidden" name="produto_id" value="<?= (int) $produto['id'] ?>">
            <label for="nome_cliente">Seu nome</label>
            <input type="text" id="nome_cliente" name="nome_cliente" required value="<?= h($_POST['nome_cliente'] ?? '') ?>">
            <label for="telefone_cliente">Seu WhatsApp (com DDD)</label>
            <input type="text" id="telefone_cliente" name="telefone_cliente" required placeholder="(11) 99999-9999" value="<?= h($_POST['telefone_cliente'] ?? '') ?>">
            <button type="submit" class="btn btn-bloco" style="margin-top:16px;">Ja paguei, confirmar pedido</button>
        </form>
    <?php endif; ?>
</div>

<script>
function copiarCodigoPix() {
    var campo = document.getElementById('codigo-pix');
    campo.select();
    campo.setSelectionRange(0, 999999);
    navigator.clipboard && navigator.clipboard.writeText(campo.value);
    alert('Codigo Pix copiado!');
}
</script>

<?php require __DIR__ . '/includes/footer.php'; ?>
