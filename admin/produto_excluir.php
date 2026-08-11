<?php
session_start();
require_once __DIR__ . '/../includes/functions.php';
exigir_login_admin();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: produtos.php');
    exit;
}

csrf_verificar();
$id = (int) ($_POST['id'] ?? 0);

if ($id) {
    $stmt = db()->prepare('SELECT imagem FROM produtos WHERE id = :id');
    $stmt->execute(['id' => $id]);
    $produto = $stmt->fetch();

    db()->prepare('DELETE FROM produtos WHERE id = :id')->execute(['id' => $id]);

    if ($produto && !empty($produto['imagem'])) {
        $caminho = __DIR__ . '/../' . $produto['imagem'];
        if (is_file($caminho)) {
            @unlink($caminho);
        }
    }
}

header('Location: produtos.php?msg=' . rawurlencode('Produto excluido.'));
exit;
