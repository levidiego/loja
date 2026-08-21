<?php
session_start();
require_once __DIR__ . '/../includes/functions.php';
exigir_login_admin();

$periodosValidos = ['hoje', '7dias', 'mes', 'mes_passado', 'tudo', 'personalizado'];
$periodo = $_GET['periodo'] ?? 'mes';
if (!in_array($periodo, $periodosValidos, true)) {
    $periodo = 'mes';
}

$hoje = new DateTime('today');
$deCustom = $_GET['de'] ?? '';
$ateCustom = $_GET['ate'] ?? '';

switch ($periodo) {
    case 'hoje':
        $de = clone $hoje;
        $ate = (clone $hoje)->setTime(23, 59, 59);
        break;
    case '7dias':
        $de = (clone $hoje)->modify('-6 days');
        $ate = (clone $hoje)->setTime(23, 59, 59);
        break;
    case 'mes_passado':
        $de = (clone $hoje)->modify('first day of last month')->setTime(0, 0, 0);
        $ate = (clone $hoje)->modify('last day of last month')->setTime(23, 59, 59);
        break;
    case 'tudo':
        $de = new DateTime('2000-01-01');
        $ate = (clone $hoje)->setTime(23, 59, 59);
        break;
    case 'personalizado':
        $de = DateTime::createFromFormat('Y-m-d', $deCustom);
        $ate = DateTime::createFromFormat('Y-m-d', $ateCustom);
        if (!$de || !$ate) {
            $periodo = 'mes';
            $de = (clone $hoje)->modify('first day of this month')->setTime(0, 0, 0);
            $ate = (clone $hoje)->modify('last day of this month')->setTime(23, 59, 59);
        } else {
            $ate->setTime(23, 59, 59);
        }
        break;
    case 'mes':
    default:
        $de = (clone $hoje)->modify('first day of this month')->setTime(0, 0, 0);
        $ate = (clone $hoje)->modify('last day of this month')->setTime(23, 59, 59);
        break;
}

$deStr = $de->format('Y-m-d H:i:s');
$ateStr = $ate->format('Y-m-d H:i:s');

$agrupamentos = [
    'dia' => ["DATE(criado_em)", "DATE_FORMAT(criado_em, '%d/%m/%Y')"],
    'semana' => ["YEARWEEK(criado_em, 3)", "CONCAT('Semana ', WEEK(criado_em, 3), ' de ', YEAR(criado_em))"],
    'mes' => ["DATE_FORMAT(criado_em, '%Y-%m')", "DATE_FORMAT(criado_em, '%m/%Y')"],
];
$agrupar = $_GET['agrupar'] ?? 'dia';
if (!isset($agrupamentos[$agrupar])) {
    $agrupar = 'dia';
}
[$chaveGroupBy, $rotuloExpr] = $agrupamentos[$agrupar];

$stmtResumo = db()->prepare(
    "SELECT COUNT(*) AS qtd,
            COALESCE(SUM(valor), 0) AS total_valor,
            COALESCE(SUM(comissao), 0) AS total_comissao,
            COALESCE(SUM(valor - comissao), 0) AS total_repasse
     FROM pedidos
     WHERE status = 'pago' AND criado_em BETWEEN :de AND :ate"
);
$stmtResumo->execute(['de' => $deStr, 'ate' => $ateStr]);
$resumo = $stmtResumo->fetch();

$stmtOutrosStatus = db()->prepare(
    "SELECT status, COUNT(*) AS qtd
     FROM pedidos
     WHERE status != 'pago' AND criado_em BETWEEN :de AND :ate
     GROUP BY status"
);
$stmtOutrosStatus->execute(['de' => $deStr, 'ate' => $ateStr]);
$outrosStatus = $stmtOutrosStatus->fetchAll();

$stmtDetalhe = db()->prepare(
    "SELECT $chaveGroupBy AS chave,
            $rotuloExpr AS rotulo,
            MIN(criado_em) AS primeiro,
            COUNT(*) AS qtd,
            SUM(valor) AS total_valor,
            SUM(comissao) AS total_comissao,
            SUM(valor - comissao) AS total_repasse
     FROM pedidos
     WHERE status = 'pago' AND criado_em BETWEEN :de AND :ate
     GROUP BY chave
     ORDER BY primeiro DESC"
);
$stmtDetalhe->execute(['de' => $deStr, 'ate' => $ateStr]);
$detalhe = $stmtDetalhe->fetchAll();

$paginaAtual = 'relatorio';
require __DIR__ . '/../includes/admin_header.php';
?>

<h2>Relatorio de vendas</h2>

<div class="filtros-relatorio">
    <a href="relatorio.php?periodo=hoje" class="btn <?= $periodo === 'hoje' ? '' : 'btn-secundario' ?>">Hoje</a>
    <a href="relatorio.php?periodo=7dias" class="btn <?= $periodo === '7dias' ? '' : 'btn-secundario' ?>">Ultimos 7 dias</a>
    <a href="relatorio.php?periodo=mes" class="btn <?= $periodo === 'mes' ? '' : 'btn-secundario' ?>">Este mes</a>
    <a href="relatorio.php?periodo=mes_passado" class="btn <?= $periodo === 'mes_passado' ? '' : 'btn-secundario' ?>">Mes passado</a>
    <a href="relatorio.php?periodo=tudo" class="btn <?= $periodo === 'tudo' ? '' : 'btn-secundario' ?>">Tudo</a>
</div>

<form method="get" action="relatorio.php" class="form-personalizado">
    <input type="hidden" name="periodo" value="personalizado">
    <div>
        <label for="de">De</label>
        <input type="date" id="de" name="de" value="<?= h($de->format('Y-m-d')) ?>">
    </div>
    <div>
        <label for="ate">Até</label>
        <input type="date" id="ate" name="ate" value="<?= h($ate->format('Y-m-d')) ?>">
    </div>
    <div>
        <label for="agrupar">Agrupar por</label>
        <select id="agrupar" name="agrupar">
            <option value="dia" <?= $agrupar === 'dia' ? 'selected' : '' ?>>Dia</option>
            <option value="semana" <?= $agrupar === 'semana' ? 'selected' : '' ?>>Semana</option>
            <option value="mes" <?= $agrupar === 'mes' ? 'selected' : '' ?>>Mes</option>
        </select>
    </div>
    <button type="submit" class="btn">Aplicar</button>
</form>

<p class="periodo-selecionado">Periodo: <?= h($de->format('d/m/Y')) ?> a <?= h($ate->format('d/m/Y')) ?></p>

<div class="cards-resumo">
    <div class="card-resumo">
        <span class="rotulo">Vendas pagas</span>
        <span class="valor"><?= (int) $resumo['qtd'] ?></span>
    </div>
    <div class="card-resumo">
        <span class="rotulo">Valor total vendido</span>
        <span class="valor"><?= formatar_preco($resumo['total_valor']) ?></span>
    </div>
    <div class="card-resumo destaque">
        <span class="rotulo">Total a repassar</span>
        <span class="valor"><?= formatar_preco($resumo['total_repasse']) ?></span>
    </div>
    <div class="card-resumo">
        <span class="rotulo">Comissao retida</span>
        <span class="valor"><?= formatar_preco($resumo['total_comissao']) ?></span>
    </div>
</div>

<?php if (!empty($outrosStatus)): ?>
    <p class="nota-status">
        Nao entram nos totais acima (so contam vendas pagas):
        <?php foreach ($outrosStatus as $linha): ?>
            <?= (int) $linha['qtd'] ?> <?= h(ucfirst($linha['status'])) ?><?= $linha !== end($outrosStatus) ? ', ' : '' ?>
        <?php endforeach; ?>
    </p>
<?php endif; ?>

<?php if (empty($detalhe)): ?>
    <div class="vazio">Nenhuma venda paga neste periodo.</div>
<?php else: ?>
    <table class="tabela">
        <thead>
            <tr>
                <th>Periodo</th>
                <th>Pedidos</th>
                <th>Valor vendido</th>
                <th>Repassar</th>
                <th>Comissao</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($detalhe as $linha): ?>
                <tr>
                    <td><?= h($linha['rotulo']) ?></td>
                    <td><?= (int) $linha['qtd'] ?></td>
                    <td><?= formatar_preco($linha['total_valor']) ?></td>
                    <td><?= formatar_preco($linha['total_repasse']) ?></td>
                    <td><?= formatar_preco($linha['total_comissao']) ?></td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
<?php endif; ?>

<?php require __DIR__ . '/../includes/admin_footer.php'; ?>
