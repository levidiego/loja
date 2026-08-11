<?php
session_start();
require_once __DIR__ . '/../includes/functions.php';

if (existe_admin_cadastrado()) {
    header('Location: login.php');
    exit;
}

$erro = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_verificar();
    $usuario = trim($_POST['usuario'] ?? '');
    $senha = $_POST['senha'] ?? '';
    $confirmarSenha = $_POST['confirmar_senha'] ?? '';

    if (strlen($usuario) < 3) {
        $erro = 'Escolha um usuario com pelo menos 3 caracteres.';
    } elseif (strlen($senha) < 6) {
        $erro = 'A senha deve ter pelo menos 6 caracteres.';
    } elseif ($senha !== $confirmarSenha) {
        $erro = 'As senhas nao coincidem.';
    } else {
        $stmt = db()->prepare('INSERT INTO admins (usuario, senha_hash) VALUES (:usuario, :senha_hash)');
        $stmt->execute(['usuario' => $usuario, 'senha_hash' => password_hash($senha, PASSWORD_DEFAULT)]);
        header('Location: login.php');
        exit;
    }
}
?><!DOCTYPE html>
<html lang="pt-br">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Criar conta administrativa</title>
<link rel="stylesheet" href="../assets/css/style.css">
</head>
<body style="background:#f3ece7;">
<div class="admin-login">
    <h2>Primeiro acesso</h2>
    <p style="font-size:0.9rem;color:#6b6259;">Crie a conta que voce vai usar para administrar a loja.</p>
    <?php if ($erro): ?><div class="alerta alerta-erro"><?= h($erro) ?></div><?php endif; ?>
    <form method="post" action="criar_admin.php">
        <?= csrf_campo() ?>
        <label for="usuario">Usuario</label>
        <input type="text" id="usuario" name="usuario" required autofocus>
        <label for="senha">Senha</label>
        <input type="password" id="senha" name="senha" required>
        <label for="confirmar_senha">Confirmar senha</label>
        <input type="password" id="confirmar_senha" name="confirmar_senha" required>
        <button type="submit" class="btn btn-bloco" style="margin-top:18px;">Criar conta</button>
    </form>
</div>
</body>
</html>
