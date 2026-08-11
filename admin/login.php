<?php
session_start();
require_once __DIR__ . '/../includes/functions.php';

if (!existe_admin_cadastrado()) {
    header('Location: criar_admin.php');
    exit;
}

if (admin_logado()) {
    header('Location: produtos.php');
    exit;
}

$erro = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_verificar();
    $usuario = trim($_POST['usuario'] ?? '');
    $senha = $_POST['senha'] ?? '';

    $stmt = db()->prepare('SELECT * FROM admins WHERE usuario = :usuario');
    $stmt->execute(['usuario' => $usuario]);
    $admin = $stmt->fetch();

    if ($admin && password_verify($senha, $admin['senha_hash'])) {
        session_regenerate_id(true);
        $_SESSION['admin_id'] = $admin['id'];
        $_SESSION['admin_usuario'] = $admin['usuario'];
        header('Location: produtos.php');
        exit;
    }
    $erro = 'Usuario ou senha invalidos.';
}
?><!DOCTYPE html>
<html lang="pt-br">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Login - Painel administrativo</title>
<link rel="stylesheet" href="../assets/css/style.css">
</head>
<body style="background:#f3ece7;">
<div class="admin-login">
    <h2>Painel administrativo</h2>
    <?php if ($erro): ?><div class="alerta alerta-erro"><?= h($erro) ?></div><?php endif; ?>
    <form method="post" action="login.php">
        <?= csrf_campo() ?>
        <label for="usuario">Usuario</label>
        <input type="text" id="usuario" name="usuario" required autofocus>
        <label for="senha">Senha</label>
        <input type="password" id="senha" name="senha" required>
        <button type="submit" class="btn btn-bloco" style="margin-top:18px;">Entrar</button>
    </form>
</div>
</body>
</html>
