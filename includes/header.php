<?php
require_once __DIR__ . '/functions.php';
$nomeLoja = get_config('nome_loja', 'Minha Loja');
$corDestaque = get_config('cor_destaque', '#7a1f2b');
$whatsapp = get_config('whatsapp', '');
$logo = get_config('logo', '');
?><!DOCTYPE html>
<html lang="pt-br">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?= h($paginaTitulo ?? $nomeLoja) ?></title>
<link rel="stylesheet" href="assets/css/style.css">
<style>:root { --cor-destaque: <?= h($corDestaque) ?>; }</style>
</head>
<body>
<div class="topbar">
    <a href="index.php" class="marca">
        <?php if ($logo): ?><img src="<?= h($logo) ?>" alt="<?= h($nomeLoja) ?>"><?php endif; ?>
        <?= h($nomeLoja) ?>
    </a>
    <?php if ($whatsapp): ?>
        <a class="whatsapp-flutuante" target="_blank" rel="noopener" href="<?= h(link_whatsapp($whatsapp, 'Ola! Vim pela loja online e gostaria de mais informacoes.')) ?>">
            Fale no WhatsApp
        </a>
    <?php endif; ?>
</div>
