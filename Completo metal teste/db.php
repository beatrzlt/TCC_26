<?php
/* ================================================================
   db.php  –  METAL Financeiro
   Conexão central com o banco + helpers de segurança
   ================================================================ */

// ── Configurações do banco (ajuste para o seu ambiente) ──────────
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'metal_financeiro');
define('DB_CHARSET', 'utf8mb4');

// ── Pasta de uploads de foto de perfil ───────────────────────────
define('UPLOAD_DIR', __DIR__ . '/uploads/avatars/');
define('UPLOAD_URL', 'uploads/avatars/');

// ── Cria conexão PDO (mais segura que mysqli pura) ────────────────
function getDB(): PDO {
    static $pdo = null;
    if ($pdo === null) {
        $dsn = 'mysql:host=' . DB_HOST . ';dbname=' . DB_NAME
             . ';charset=' . DB_CHARSET;
        $options = [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
        ];
        try {
            $pdo = new PDO($dsn, DB_USER, DB_PASS, $options);
        } catch (PDOException $e) {
            // Em produção, nunca exibir o erro real
            error_log('DB Error: ' . $e->getMessage());
            die(json_encode(['erro' => 'Falha na conexão com o banco de dados.']));
        }
    }
    return $pdo;
}

// ── Proteção CSRF ─────────────────────────────────────────────────
function csrfToken(): string {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function csrfVerify(): void {
    $token = $_POST['csrf_token'] ?? $_SERVER['HTTP_X_CSRF_TOKEN'] ?? '';
    if (!hash_equals($_SESSION['csrf_token'] ?? '', $token)) {
        http_response_code(403);
        die('Token CSRF inválido.');
    }
}

// ── Rate limiting simples (tentativas de login) ───────────────────
function loginRateLimit(string $ip): bool {
    $key  = 'login_attempts_' . md5($ip);
    $max  = 5;
    $window = 300; // 5 minutos

    if (!isset($_SESSION[$key])) {
        $_SESSION[$key] = ['count' => 0, 'first' => time()];
    }

    $data = &$_SESSION[$key];

    // Reseta janela se passou o tempo
    if (time() - $data['first'] > $window) {
        $data = ['count' => 0, 'first' => time()];
    }

    $data['count']++;

    return $data['count'] <= $max;
}

function loginResetLimit(string $ip): void {
    $key = 'login_attempts_' . md5($ip);
    unset($_SESSION[$key]);
}

// ── Verifica se o usuário está autenticado, redireciona se não ───
function requireAuth(): void {
    if (empty($_SESSION['user_id'])) {
        header('Location: login.php');
        exit();
    }
    // Regenera sessão periodicamente (proteção de session fixation)
    if (empty($_SESSION['last_regen']) || time() - $_SESSION['last_regen'] > 300) {
        session_regenerate_id(true);
        $_SESSION['last_regen'] = time();
    }
}

// ── Valida upload de imagem de perfil ─────────────────────────────
function validarUploadAvatar(array $file): array {
    $maxSize  = 2 * 1024 * 1024; // 2 MB
    $allowed  = ['image/jpeg','image/png','image/webp','image/gif'];
    $ext_map  = ['image/jpeg'=>'jpg','image/png'=>'png','image/webp'=>'webp','image/gif'=>'gif'];

    if ($file['error'] !== UPLOAD_ERR_OK) {
        return ['ok' => false, 'msg' => 'Erro no upload do arquivo.'];
    }
    if ($file['size'] > $maxSize) {
        return ['ok' => false, 'msg' => 'Imagem muito grande. Máximo 2 MB.'];
    }

    // Detecta tipo real via finfo (não confia no nome/MIME do cliente)
    $finfo    = new finfo(FILEINFO_MIME_TYPE);
    $mimeReal = $finfo->file($file['tmp_name']);

    if (!in_array($mimeReal, $allowed, true)) {
        return ['ok' => false, 'msg' => 'Formato inválido. Use JPG, PNG, WEBP ou GIF.'];
    }

    $ext      = $ext_map[$mimeReal];
    $filename = 'avatar_' . bin2hex(random_bytes(8)) . '.' . $ext;
    $destino  = UPLOAD_DIR . $filename;

    if (!is_dir(UPLOAD_DIR)) {
        mkdir(UPLOAD_DIR, 0755, true);
    }

    if (!move_uploaded_file($file['tmp_name'], $destino)) {
        return ['ok' => false, 'msg' => 'Falha ao salvar imagem.'];
    }

    return ['ok' => true, 'filename' => $filename];
}
