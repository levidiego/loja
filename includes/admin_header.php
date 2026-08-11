<?php
// Espera que $paginaAtual esteja definido (ex: 'produtos', 'pedidos', 'configuracoes')
$nomeLoja = get_config('nome_loja', 'Minha Loja');
?><!DOCTYPE html>
<html lang="pt-br">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Painel administrativo - <?= h($nomeLoja) ?></title>
<link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>
<div class="admin-layout">
    <div class="admin-menu">
        <div class="marca"><?= h($nomeLoja) ?></div>
        <a href="produtos.php" class="<?= ($paginaAtual ?? '') === 'produtos' ? 'ativo' : '' ?>">Produtos</a>
        <a href="pedidos.php" class="<?= ($paginaAtual ?? '') === 'pedidos' ? 'ativo' : '' ?>">Pedidos</a>
        <a href="configuracoes.php" class="<?= ($paginaAtual ?? '') === 'configuracoes' ? 'ativo' : '' ?>">Configuracoes</a>
        <a href="../index.php" target="_blank">Ver loja &#8599;</a>
        <a href="logout.php">Sair</a>
    </div>
    <div class="admin-conteudo">
