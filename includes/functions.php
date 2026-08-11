<?php
require_once __DIR__ . '/db.php';

// ---------- Configuracoes da loja (tabela configuracoes) ----------

function get_config($chave, $default = '') {
    static $cache = null;
    if ($cache === null) {
        $cache = [];
        $stmt = db()->query('SELECT chave, valor FROM configuracoes');
        foreach ($stmt as $row) {
            $cache[$row['chave']] = $row['valor'];
        }
    }
    return isset($cache[$chave]) && $cache[$chave] !== '' ? $cache[$chave] : $default;
}

function set_config($chave, $valor) {
    $stmt = db()->prepare(
        'INSERT INTO configuracoes (chave, valor) VALUES (:chave, :valor)
         ON DUPLICATE KEY UPDATE valor = :valor2'
    );
    $stmt->execute(['chave' => $chave, 'valor' => $valor, 'valor2' => $valor]);
}

// ---------- Formatacao ----------

function formatar_preco($valor) {
    return 'R$ ' . number_format((float) $valor, 2, ',', '.');
}

function h($texto) {
    return htmlspecialchars((string) $texto, ENT_QUOTES, 'UTF-8');
}

function imagem_produto($caminho) {
    if ($caminho) {
        return h($caminho);
    }
    $svg = '<svg xmlns="http://www.w3.org/2000/svg" width="300" height="200">'
        . '<rect width="100%" height="100%" fill="#eee"/>'
        . '<text x="50%" y="50%" font-family="Arial" font-size="16" fill="#999" text-anchor="middle" dominant-baseline="middle">Sem imagem</text>'
        . '</svg>';
    return 'data:image/svg+xml;base64,' . base64_encode($svg);
}

// ---------- Autenticacao do painel administrativo ----------

function admin_logado() {
    return !empty($_SESSION['admin_id']);
}

function exigir_login_admin() {
    if (!admin_logado()) {
        header('Location: login.php');
        exit;
    }
}

function existe_admin_cadastrado() {
    $stmt = db()->query('SELECT COUNT(*) AS total FROM admins');
    return (int) $stmt->fetch()['total'] > 0;
}

// ---------- Protecao CSRF simples para formularios do admin ----------

function csrf_token() {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function csrf_campo() {
    return '<input type="hidden" name="csrf_token" value="' . h(csrf_token()) . '">';
}

function csrf_verificar() {
    $enviado = $_POST['csrf_token'] ?? '';
    if (!$enviado || empty($_SESSION['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $enviado)) {
        http_response_code(400);
        die('Sessao invalida. Volte e tente novamente.');
    }
}

// ---------- Upload de imagem de produto ----------

function salvar_imagem_enviada($arquivo, $pastaRelativa, $prefixo) {
    if (empty($arquivo) || $arquivo['error'] === UPLOAD_ERR_NO_FILE) {
        return null;
    }
    if ($arquivo['error'] !== UPLOAD_ERR_OK) {
        throw new RuntimeException('Falha no envio da imagem.');
    }
    $extensoesPermitidas = ['jpg' => true, 'jpeg' => true, 'png' => true, 'webp' => true];
    $extensao = strtolower(pathinfo($arquivo['name'], PATHINFO_EXTENSION));
    if (!isset($extensoesPermitidas[$extensao])) {
        throw new RuntimeException('Formato de imagem invalido. Use JPG, PNG ou WEBP.');
    }
    if ($arquivo['size'] > 4 * 1024 * 1024) {
        throw new RuntimeException('Imagem maior que 4MB.');
    }
    $nomeArquivo = $prefixo . '_' . bin2hex(random_bytes(8)) . '.' . $extensao;
    $destino = __DIR__ . '/../' . $pastaRelativa . '/' . $nomeArquivo;
    if (!move_uploaded_file($arquivo['tmp_name'], $destino)) {
        throw new RuntimeException('Nao foi possivel salvar a imagem.');
    }
    return $pastaRelativa . '/' . $nomeArquivo;
}

function salvar_imagem_produto($arquivo) {
    return salvar_imagem_enviada($arquivo, 'assets/img/produtos', 'produto');
}

// ---------- Geracao do codigo Pix (BR Code / Pix Copia e Cola) ----------
// Implementa o padrao EMV do Banco Central para Pix estatico, sem
// necessidade de conta em gateway de pagamento.

function pix_normalizar_texto($texto, $limite) {
    $texto = preg_replace('/[^A-Za-z0-9 ]/', '', strtr($texto, [
        'á'=>'a','à'=>'a','ã'=>'a','â'=>'a','ä'=>'a',
        'é'=>'e','è'=>'e','ê'=>'e','ë'=>'e',
        'í'=>'i','ì'=>'i','î'=>'i','ï'=>'i',
        'ó'=>'o','ò'=>'o','õ'=>'o','ô'=>'o','ö'=>'o',
        'ú'=>'u','ù'=>'u','û'=>'u','ü'=>'u',
        'ç'=>'c','ñ'=>'n',
        'Á'=>'A','À'=>'A','Ã'=>'A','Â'=>'A','Ä'=>'A',
        'É'=>'E','È'=>'E','Ê'=>'E','Ë'=>'E',
        'Í'=>'I','Ì'=>'I','Î'=>'I','Ï'=>'I',
        'Ó'=>'O','Ò'=>'O','Õ'=>'O','Ô'=>'O','Ö'=>'O',
        'Ú'=>'U','Ù'=>'U','Û'=>'U','Ü'=>'U',
        'Ç'=>'C','Ñ'=>'N',
    ]));
    $texto = strtoupper(trim($texto));
    return substr($texto, 0, $limite);
}

function pix_emv($id, $valor) {
    $tamanho = str_pad((string) strlen($valor), 2, '0', STR_PAD_LEFT);
    return $id . $tamanho . $valor;
}

function pix_crc16($payload) {
    $polinomio = 0x1021;
    $resultado = 0xFFFF;
    for ($i = 0; $i < strlen($payload); $i++) {
        $resultado ^= (ord($payload[$i]) << 8);
        for ($j = 0; $j < 8; $j++) {
            if (($resultado & 0x8000) !== 0) {
                $resultado = (($resultado << 1) ^ $polinomio) & 0xFFFF;
            } else {
                $resultado = ($resultado << 1) & 0xFFFF;
            }
        }
    }
    return strtoupper(str_pad(dechex($resultado), 4, '0', STR_PAD_LEFT));
}

/**
 * Monta o codigo "Pix Copia e Cola" a partir da chave Pix da loja.
 * $txid identifica o pedido no comprovante (opcional, ate 25 caracteres alfanumericos).
 */
function gerar_pix_copia_cola($chavePix, $nomeRecebedor, $cidadeRecebedor, $valor, $descricao = '', $txid = '') {
    $nome = pix_normalizar_texto($nomeRecebedor, 25) ?: 'LOJA';
    $cidade = pix_normalizar_texto($cidadeRecebedor, 15) ?: 'BRASIL';
    $txid = preg_replace('/[^A-Za-z0-9]/', '', $txid);
    $txid = $txid !== '' ? substr($txid, 0, 25) : '***';

    $merchantAccountInfo = pix_emv('00', 'br.gov.bcb.pix') . pix_emv('01', $chavePix);
    if ($descricao !== '') {
        $descricao = substr(preg_replace('/[^\x20-\x7E]/', '', $descricao), 0, 40);
        $merchantAccountInfo .= pix_emv('02', $descricao);
    }

    $valorFormatado = number_format((float) $valor, 2, '.', '');

    $payload =
        pix_emv('00', '01') .
        pix_emv('26', $merchantAccountInfo) .
        pix_emv('52', '0000') .
        pix_emv('53', '986') .
        pix_emv('54', $valorFormatado) .
        pix_emv('58', 'BR') .
        pix_emv('59', $nome) .
        pix_emv('60', $cidade) .
        pix_emv('62', pix_emv('05', $txid));

    $payload .= '6304';
    $payload .= pix_crc16($payload);

    return $payload;
}

function pix_configurado() {
    return get_config('pix_chave') !== '' && get_config('pix_nome') !== '' && get_config('pix_cidade') !== '';
}

function link_whatsapp($telefone, $mensagem) {
    $numero = preg_replace('/\D/', '', $telefone);
    return 'https://wa.me/' . $numero . '?text=' . rawurlencode($mensagem);
}
