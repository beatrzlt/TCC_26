<?php
session_set_cookie_params([
    'lifetime' => 0,
    'path'     => '/',
    'secure'   => false,   // true em produção com HTTPS
    'httponly' => true,
    'samesite' => 'Strict',
]);

session_start();
require 'db.php';

// Se já está logado, vai direto ao painel
if (!empty($_SESSION['user_id'])) {
    header('Location: index.php');
    exit();
}

$erro    = '';
$sucesso = '';

// Verifica mensagem vinda do cadastro
if (!empty($_GET['cadastrado'])) {
    $sucesso = 'Cadastro realizado! Faça login para continuar.';
}

// ── Processamento do POST ─────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    csrfVerify();

    $ip      = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
    $login   = trim($_POST['usuario'] ?? '');
    $senha   = $_POST['senha'] ?? '';

    // Rate limiting: bloqueia após 5 tentativas em 5 min
    if (!loginRateLimit($ip)) {
        $erro = 'Muitas tentativas. Aguarde 5 minutos e tente novamente.';
    } elseif ($login === '' || $senha === '') {
        $erro = 'Preencha todos os campos.';
    } else {
        $pdo  = getDB();
        // Permite login por e-mail OU por nome de usuário (campo nome)
        $stmt = $pdo->prepare(
            'SELECT id, nome, sobrenome, email, senha_hash, tema, avatar, perfil
               FROM usuarios
              WHERE email = :login OR nome = :login2
              LIMIT 1'
        );
        $stmt->execute([':login' => $login, ':login2' => $login]);
        $user = $stmt->fetch();

        if ($user && password_verify($senha, $user['senha_hash'])) {

            loginResetLimit($ip);

            // Regenera session ID (proteção session fixation)
            session_regenerate_id(true);

            $_SESSION['user_id']       = $user['id'];
            $_SESSION['user_nome']     = $user['nome'];
            $_SESSION['user_sobrenome']= $user['sobrenome'];
            $_SESSION['user_email']    = $user['email'];
            $_SESSION['user_avatar']   = $user['avatar'];
            $_SESSION['user_tema']     = $user['tema'];
            $_SESSION['user_perfil']   = $user['perfil'];   // 'admin' | 'usuario'
            $_SESSION['last_regen']    = time();

            // Atualiza último acesso
            $pdo->prepare('UPDATE usuarios SET ultimo_acesso = NOW() WHERE id = ?')
                ->execute([$user['id']]);

            // Verifica se senha precisa de rehash (algoritmo mais forte)
            if (password_needs_rehash($user['senha_hash'], PASSWORD_BCRYPT, ['cost'=>12])) {
                $novo = password_hash($senha, PASSWORD_BCRYPT, ['cost'=>12]);
                $pdo->prepare('UPDATE usuarios SET senha_hash = ? WHERE id = ?')
                    ->execute([$novo, $user['id']]);
            }

            header('Location: index.php');
            exit();

        } else {
            $erro = 'Usuário ou senha incorretos.';
        }
    }
}

$csrf = csrfToken();
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Login – METAL Financeiro</title>
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@tabler/icons-webfont@3.19.0/dist/tabler-icons.min.css">
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:ital,wght@0,700;1,400&family=Barlow:wght@400;600;700&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="auth.css">
</head>
<body>

<div class="auth-wrap">

  <!-- Painel esquerdo decorativo -->
  <div class="auth-side">
    <div class="auth-side-inner">
      <div class="auth-logo">METAL<span>Financeiro</span></div>
      <div class="auth-tagline">Entre para ver o<br>seu Tesouro!</div>
      <div class="auth-features">
        <div class="auth-feat"><i class="ti ti-shield-lock"></i> Dados 100% seguros</div>
        <div class="auth-feat"><i class="ti ti-chart-line"></i> Gráficos em tempo real</div>
        <div class="auth-feat"><i class="ti ti-target"></i> Metas financeiras</div>
      </div>
    </div>
  </div>

  <!-- Formulário -->
  <div class="auth-main">
    <div class="auth-card">

      <div class="auth-card-logo">METAL<span>Financeiro</span></div>
      <h1 class="auth-title">Bem-vindo de volta</h1>
      <p class="auth-sub">Entre na sua conta para continuar</p>

      <?php if ($erro): ?>
        <div class="auth-alert auth-alert-error">
          <i class="ti ti-alert-circle"></i> <?= htmlspecialchars($erro) ?>
        </div>
      <?php endif; ?>

      <?php if ($sucesso): ?>
        <div class="auth-alert auth-alert-success">
          <i class="ti ti-circle-check"></i> <?= htmlspecialchars($sucesso) ?>
        </div>
      <?php endif; ?>

      <form method="POST" action="login.php" autocomplete="on" novalidate>
        <input type="hidden" name="csrf_token" value="<?= $csrf ?>">

        <div class="auth-field">
          <label class="auth-label">E-mail ou usuário</label>
          <div class="auth-input-wrap">
            <i class="ti ti-user"></i>
            <input
              type="text"
              name="usuario"
              placeholder="seu@email.com"
              autocomplete="username"
              required
              value="<?= htmlspecialchars($_POST['usuario'] ?? '') ?>"
            >
          </div>
        </div>

        <div class="auth-field">
          <div class="auth-label-row">
            <label class="auth-label">Senha</label>
            <a class="auth-link-sm" href="#">Esqueci minha senha</a>
          </div>
          <div class="auth-input-wrap">
            <i class="ti ti-lock"></i>
            <input
              type="password"
              name="senha"
              id="inp-senha"
              placeholder="••••••••"
              autocomplete="current-password"
              required
            >
            <button type="button" class="eye-toggle" onclick="toggleSenha()">
              <i class="ti ti-eye" id="eye-icon"></i>
            </button>
          </div>
        </div>

        <button type="submit" class="auth-btn">
          <i class="ti ti-login"></i> Entrar
        </button>
      </form>

      <p class="auth-switch">
        Ainda não tem conta?
        <a href="cadastro.php" class="auth-link">Cadastre-se grátis</a>
      </p>

    </div>
  </div>
</div>

<script>
function toggleSenha() {
  const inp = document.getElementById('inp-senha');
  const ico = document.getElementById('eye-icon');
  if (inp.type === 'password') {
    inp.type = 'text'; ico.className = 'ti ti-eye-off';
  } else {
    inp.type = 'password'; ico.className = 'ti ti-eye';
  }
}
</script>
</body>
</html>
