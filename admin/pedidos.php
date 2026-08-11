<?php
session_start();
require_once __DIR__ . '/../includes/functions.php';
exigir_login_admin();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_verificar();
    $id = (int) ($_POST['id'] ?? 0);
    $status = $_POST['status'] ?? '';
    if ($id && in_array($status, ['pendente', 'pago', 'cancelado'], true)) {
        db()->prepare('UPDATE pedidos SET status = :status WHERE id = :id')
           ->execute(['status' => $status, 'id' => $id]);
    }
    header('Location: pedidos.php');
    exit;
}

$pedidos = db()->query('SELECT * FROM pedidos ORDER BY criado_em DESC')->fetchAll();
$paginaAtual = 'pedidos';
require __DIR__ . '/../includes/admin_header.php';
?>

<h2>Pedidos</h2>

<?php if (empty($pedidos)): ?>
    <div class="vazio">Nenhum pedido registrado ainda.</div>
<?php else: ?>
    <table class="tabela">
        <thead>
            <tr>
                <th>#</th>
                <th>Produto</th>
                <th>Valor</th>
                <th>Cliente</th>
                <th>WhatsApp</th>
                <th>Data</th>
                <th>Status</th>
                <th>Acoes</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($pedidos as $pedido): ?>
                <tr>
                    <td>#<?= (int) $pedido['id'] ?></td>
                    <td><?= h($pedido['produto_nome']) ?></td>
                    <td><?= formatar_preco($pedido['valor']) ?></td>
                    <td><?= h($pedido['nome_cliente']) ?></td>
                    <td>
                        <a target="_blank" rel="noopener" href="<?= h(link_whatsapp($pedido['telefone_cliente'], 'Ola ' . $pedido['nome_cliente'] . '! Sobre seu pedido #' . $pedido['id'] . '...')) ?>">
                            <?= h($pedido['telefone_cliente']) ?>
                        </a>
                    </td>
                    <td><?= date('d/m/Y H:i', strtotime($pedido['criado_em'])) ?></td>
                    <td><span class="badge badge-<?= h($pedido['status']) ?>"><?= h(ucfirst($pedido['status'])) ?></span></td>
                    <td class="acoes-linha">
                        <?php if ($pedido['status'] !== 'pago'): ?>
                            <form method="post" action="pedidos.php">
                                <?= csrf_campo() ?>
                                <input type="hidden" name="id" value="<?= (int) $pedido['id'] ?>">
                                <input type="hidden" name="status" value="pago">
                                <button type="submit" class="btn" style="background:#1e6b3a;">Marcar pago</button>
                            </form>
                        <?php endif; ?>
                        <?php if ($pedido['status'] !== 'cancelado'): ?>
                            <form method="post" action="pedidos.php">
                                <?= csrf_campo() ?>
                                <input type="hidden" name="id" value="<?= (int) $pedido['id'] ?>">
                                <input type="hidden" name="status" value="cancelado">
                                <button type="submit" class="btn" style="background:#9a1f1f;">Cancelar</button>
                            </form>
                        <?php endif; ?>
                    </td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
<?php endif; ?>

<?php require __DIR__ . '/../includes/admin_footer.php'; ?>
