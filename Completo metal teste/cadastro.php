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

if (!empty($_SESSION['user_id'])) {
    header('Location: index.php');
    exit();
}

$erro    = '';
$sucesso = '';
$dados   = [];

//  POST 
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    csrfVerify();

    // Coleta e limpa
    $dados = [
        'nome'      => trim($_POST['nome']      ?? ''),
        'sobrenome' => trim($_POST['sobrenome']  ?? ''),
        'email'     => trim($_POST['email']      ?? ''),
        'senha'     => $_POST['senha']           ?? '',
        'conf_senha'=> $_POST['conf_senha']      ?? '',
        'telefone'  => trim($_POST['telefone']   ?? ''),
        'data'      => trim($_POST['data']       ?? ''),
        'genero'    => trim($_POST['genero']      ?? ''),
        'cidade'    => trim($_POST['cidade']     ?? ''),
        'estado'    => trim($_POST['estado']     ?? ''),
    ];

    // Validações
    $erros = [];

    if (strlen($dados['nome']) < 2)
        $erros[] = 'Nome deve ter ao menos 2 caracteres.';

    if (!filter_var($dados['email'], FILTER_VALIDATE_EMAIL))
        $erros[] = 'E-mail inválido.';

    if (strlen($dados['senha']) < 8)
        $erros[] = 'Senha deve ter ao menos 8 caracteres.';

    if (!preg_match('/[A-Z]/', $dados['senha']))
        $erros[] = 'Senha deve conter ao menos uma letra maiúscula.';

    if (!preg_match('/[0-9]/', $dados['senha']))
        $erros[] = 'Senha deve conter ao menos um número.';

    if ($dados['senha'] !== $dados['conf_senha'])
        $erros[] = 'As senhas não coincidem.';

    if ($dados['data'] && !DateTime::createFromFormat('Y-m-d', $dados['data']))
        $erros[] = 'Data de nascimento inválida.';

    if (!empty($erros)) {
        $erro = implode('<br>', $erros);
    } else {
        $pdo = getDB();

        // Verifica e-mail duplicado
        $chk = $pdo->prepare('SELECT id FROM usuarios WHERE email = ? LIMIT 1');
        $chk->execute([$dados['email']]);
        if ($chk->fetch()) {
            $erro = 'Este e-mail já está cadastrado.';
        } else {
            $hash = password_hash($dados['senha'], PASSWORD_BCRYPT, ['cost' => 12]);

            $ins = $pdo->prepare('
                INSERT INTO usuarios
                    (nome, sobrenome, email, senha_hash, telefone, data_nascimento,
                     genero, cidade, estado)
                VALUES
                    (:nome, :sobrenome, :email, :hash, :tel, :dt, :gen, :cid, :est)
            ');
            $ins->execute([
                ':nome'      => $dados['nome'],
                ':sobrenome' => $dados['sobrenome'],
                ':email'     => $dados['email'],
                ':hash'      => $hash,
                ':tel'       => $dados['telefone'],
                ':dt'        => $dados['data'] ?: null,
                ':gen'       => $dados['genero'],
                ':cid'       => $dados['cidade'],
                ':est'       => $dados['estado'],
            ]);

            header('Location: login.php?cadastrado=1');
            exit();
        }
    }
}

$csrf = csrfToken();
$generos = ['Masculino', 'Feminino', 'Outro'];
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Cadastro – METAL Financeiro</title>
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@tabler/icons-webfont@3.19.0/dist/tabler-icons.min.css">
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:ital,wght@0,700;1,400&family=Barlow:wght@400;600;700&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="auth.css">
</head>
<body>

<div class="auth-wrap">

  <!-- Painel esquerdo -->
  <div class="auth-side">
    <div class="auth-side-inner">
      <div class="auth-logo">METAL<span>Financeiro</span></div>
      <div class="auth-tagline">Comece sua<br>jornada financeira.</div>
      <div class="auth-features">
        <div class="auth-feat"><i class="ti ti-lock"></i> Senhas criptografadas</div>
        <div class="auth-feat"><i class="ti ti-target"></i> Crie metas pessoais</div>
        <div class="auth-feat"><i class="ti ti-chart-bar"></i> Visualize seus dados</div>
      </div>
    </div>
  </div>

  <!-- Formulário -->
  <div class="auth-main">
    <div class="auth-card auth-card-wide">

      <div class="auth-card-logo">METAL<span>Financeiro</span></div>
      <h1 class="auth-title">Criar conta</h1>
      <p class="auth-sub">Preencha seus dados para começar</p>

      <?php if ($erro): ?>
        <div class="auth-alert auth-alert-error">
          <i class="ti ti-alert-circle"></i>
          <span><?= $erro ?></span>
        </div>
      <?php endif; ?>

      <form method="POST" action="cadastro.php" novalidate>
        <input type="hidden" name="csrf_token" value="<?= $csrf ?>">

        <div class="auth-grid2">

          <div class="auth-field">
            <label class="auth-label">Nome *</label>
            <div class="auth-input-wrap">
              <i class="ti ti-user"></i>
              <input type="text" name="nome" placeholder="João"
                     value="<?= htmlspecialchars($dados['nome'] ?? '') ?>" required>
            </div>
          </div>

          <div class="auth-field">
            <label class="auth-label">Sobrenome *</label>
            <div class="auth-input-wrap">
              <i class="ti ti-user"></i>
              <input type="text" name="sobrenome" placeholder="Silva"
                     value="<?= htmlspecialchars($dados['sobrenome'] ?? '') ?>" required>
            </div>
          </div>

          <div class="auth-field auth-full">
            <label class="auth-label">E-mail *</label>
            <div class="auth-input-wrap">
              <i class="ti ti-mail"></i>
              <input type="email" name="email" placeholder="seu@email.com"
                     value="<?= htmlspecialchars($dados['email'] ?? '') ?>"
                     autocomplete="email" required>
            </div>
          </div>

          <div class="auth-field">
            <label class="auth-label">Senha *</label>
            <div class="auth-input-wrap">
              <i class="ti ti-lock"></i>
              <input type="password" name="senha" id="inp-senha1"
                     placeholder="Mín. 8 chars, 1 maiúscula, 1 número"
                     autocomplete="new-password" required>
              <button type="button" class="eye-toggle" onclick="toggleSenha('inp-senha1','eye1')">
                <i class="ti ti-eye" id="eye1"></i>
              </button>
            </div>
            <div class="pwd-strength" id="pwd-bar"></div>
          </div>

          <div class="auth-field">
            <label class="auth-label">Confirmar senha *</label>
            <div class="auth-input-wrap">
              <i class="ti ti-lock-check"></i>
              <input type="password" name="conf_senha" id="inp-senha2"
                     placeholder="Repita a senha"
                     autocomplete="new-password" required>
              <button type="button" class="eye-toggle" onclick="toggleSenha('inp-senha2','eye2')">
                <i class="ti ti-eye" id="eye2"></i>
              </button>
            </div>
          </div>

          <div class="auth-field">
            <label class="auth-label">Telefone</label>
            <div class="auth-input-wrap">
              <i class="ti ti-phone"></i>
              <input type="tel" name="telefone" placeholder="(11) 9xxxx-xxxx"
                     value="<?= htmlspecialchars($dados['telefone'] ?? '') ?>">
            </div>
          </div>

          <div class="auth-field">
            <label class="auth-label">Data de nascimento</label>
            <div class="auth-input-wrap">
              <i class="ti ti-calendar"></i>
              <input type="date" name="data"
                     value="<?= htmlspecialchars($dados['data'] ?? '') ?>">
            </div>
          </div>

          <div class="auth-field">
            <label class="auth-label">Gênero</label>
            <div class="auth-input-wrap">
              <i class="ti ti-gender-bigender"></i>
              <select name="genero">
                <option value="">Selecione</option>
                <?php foreach ($generos as $g): ?>
                  <option value="<?= $g ?>"
                    <?= ($dados['genero'] ?? '') === $g ? 'selected' : '' ?>>
                    <?= $g ?>
                  </option>
                <?php endforeach; ?>
              </select>
            </div>
          </div>

          <div class="auth-field">
            <label class="auth-label">Cidade</label>
            <div class="auth-input-wrap">
              <i class="ti ti-map-pin"></i>
              <input type="text" name="cidade" placeholder="São Paulo"
                     value="<?= htmlspecialchars($dados['cidade'] ?? '') ?>">
            </div>
          </div>

          <div class="auth-field">
            <label class="auth-label">Estado</label>
            <div class="auth-input-wrap">
              <i class="ti ti-map"></i>
              <input type="text" name="estado" placeholder="SP"
                     value="<?= htmlspecialchars($dados['estado'] ?? '') ?>">
            </div>
          </div>

        </div><!-- /auth-grid2 -->

        <!-- Força da senha -->
        <div class="pwd-hint" id="pwd-hint"></div>

        <button type="submit" class="auth-btn" style="margin-top:8px">
          <i class="ti ti-user-plus"></i> Criar minha conta
        </button>
      </form>

      <p class="auth-switch">
        Já tem conta?
        <a href="login.php" class="auth-link">Entrar</a>
      </p>

    </div>
  </div>
</div>

<script>
function toggleSenha(id, iconId) {
  const inp = document.getElementById(id);
  const ico = document.getElementById(iconId);
  inp.type  = inp.type === 'password' ? 'text' : 'password';
  ico.className = inp.type === 'text' ? 'ti ti-eye-off' : 'ti ti-eye';
}

// Indicador de força de senha
document.getElementById('inp-senha1')?.addEventListener('input', function () {
  const v = this.value;
  let score = 0;
  if (v.length >= 8)               score++;
  if (/[A-Z]/.test(v))             score++;
  if (/[0-9]/.test(v))             score++;
  if (/[^A-Za-z0-9]/.test(v))     score++;

  const bar   = document.getElementById('pwd-bar');
  const hint  = document.getElementById('pwd-hint');
  const labels = ['', 'Fraca', 'Regular', 'Boa', 'Forte'];
  const colors = ['', '#e24b4a', '#e0b840', '#c59b27', '#4caf50'];

  bar.style.cssText = `width:${score * 25}%; background:${colors[score]}; height:4px; border-radius:4px; margin-top:6px; transition:.3s`;
  hint.textContent  = score > 0 ? 'Força: ' + labels[score] : '';
  hint.style.color  = colors[score];
});
</script>
</body>
</html>
