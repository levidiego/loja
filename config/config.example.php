<?php
// Copie este arquivo para "config.php" (no mesmo diretorio) e preencha
// com os dados do banco de dados criado no cPanel.
// O arquivo config.php NAO deve ser enviado ao Git (veja .gitignore).

define('DB_HOST', 'localhost');
define('DB_NAME', 'usuario_nomedobanco');
define('DB_USER', 'usuario_nomedobanco');
define('DB_PASS', 'sua_senha_aqui');

// URL base do site, sem barra no final. Ex: https://lojadosinval.com.br
define('SITE_URL', 'https://exemplo.com.br');

// Fuso horario usado para exibir datas no painel administrativo.
date_default_timezone_set('America/Sao_Paulo');
