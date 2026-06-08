<?php
/* ================================================================
   index.php  –  METAL Financeiro  |  Painel principal
   ================================================================ */
session_set_cookie_params([
    'lifetime' => 0, 'path' => '/',
    'secure'   => false, 'httponly' => true, 'samesite' => 'Strict',
]);
require 'db.php';

session_start();
requireAuth();

/* ── Carrega dados frescos do usuário ────────────────────────────*/
$pdo  = getDB();
$stmt = $pdo->prepare('SELECT * FROM usuarios WHERE id = ? LIMIT 1');
$stmt->execute([$_SESSION['user_id']]);
$u = $stmt->fetch();
if (!$u) { header('Location: logout.php'); exit(); }

/* ── Sincroniza session ──────────────────────────────────────────*/
$_SESSION['user_nome']      = $u['nome'];
$_SESSION['user_sobrenome'] = $u['sobrenome'];
$_SESSION['user_avatar']    = $u['avatar'];
$_SESSION['user_tema']      = $u['tema'];
$_SESSION['user_perfil']    = $u['perfil'];

$nomeCompleto = $u['nome'] . ' ' . $u['sobrenome'];
$tema         = $u['tema'];
$avatarUrl    = $u['avatar'] ? UPLOAD_URL . htmlspecialchars($u['avatar']) : '';
$isAdmin      = ($u['perfil'] === 'admin');

/* ── Metas do usuário ────────────────────────────────────────────*/
$mStmt = $pdo->prepare('SELECT * FROM metas WHERE usuario_id = ? ORDER BY criado_em DESC');
$mStmt->execute([$u['id']]);
$metasDB = $mStmt->fetchAll();

/* ── Mensagens de feedback ───────────────────────────────────────*/
$msgPerfil  = '';  $erroPerfil = '';
$msgConfig  = '';
$msgAvatar  = '';  $erroAvatar = '';

/* ── POST: salvar perfil ─────────────────────────────────────────*/
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['acao'] ?? '') === 'salvar_perfil') {
    csrfVerify();
    $nome      = trim(htmlspecialchars($_POST['nome']      ?? ''));
    $sobrenome = trim(htmlspecialchars($_POST['sobrenome'] ?? ''));
    $email     = trim($_POST['email'] ?? '');
    $telefone  = trim(htmlspecialchars($_POST['telefone']  ?? ''));
    $bio       = trim(htmlspecialchars($_POST['bio']       ?? ''));
    $novaSenha = $_POST['nova_senha']     ?? '';
    $confSenha = $_POST['confirma_senha'] ?? '';

    if ($nome === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $erroPerfil = 'Nome e e-mail válido são obrigatórios.';
    } elseif ($novaSenha !== '' && strlen($novaSenha) < 8) {
        $erroPerfil = 'Nova senha deve ter ao menos 8 caracteres.';
    } elseif ($novaSenha !== '' && $novaSenha !== $confSenha) {
        $erroPerfil = 'As senhas não coincidem.';
    } else {
        $chk = $pdo->prepare('SELECT id FROM usuarios WHERE email = ? AND id != ? LIMIT 1');
        $chk->execute([$email, $u['id']]);
        if ($chk->fetch()) {
            $erroPerfil = 'Este e-mail já está em uso por outra conta.';
        } else {
            if ($novaSenha !== '') {
                $hash = password_hash($novaSenha, PASSWORD_BCRYPT, ['cost' => 12]);
                $pdo->prepare('UPDATE usuarios SET nome=?,sobrenome=?,email=?,telefone=?,bio=?,senha_hash=? WHERE id=?')
                    ->execute([$nome,$sobrenome,$email,$telefone,$bio,$hash,$u['id']]);
            } else {
                $pdo->prepare('UPDATE usuarios SET nome=?,sobrenome=?,email=?,telefone=?,bio=? WHERE id=?')
                    ->execute([$nome,$sobrenome,$email,$telefone,$bio,$u['id']]);
            }
            $msgPerfil = 'Perfil updated com sucesso!';
            $stmt->execute([$u['id']]);
            $u = $stmt->fetch();
            $nomeCompleto = $u['nome'] . ' ' . $u['sobrenome'];
            $_SESSION['user_nome'] = $u['nome'];
            $_SESSION['user_sobrenome'] = $u['sobrenome'];
        }
    }
}

/* ── POST: upload de avatar ──────────────────────────────────────*/
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['acao'] ?? '') === 'upload_avatar') {
    csrfVerify();
    if (!empty($_FILES['avatar']['name'])) {
        $res = validarUploadAvatar($_FILES['avatar']);
        if ($res['ok']) {
            if ($u['avatar'] && file_exists(UPLOAD_DIR . $u['avatar'])) {
                @unlink(UPLOAD_DIR . $u['avatar']);
            }
            $pdo->prepare('UPDATE usuarios SET avatar = ? WHERE id = ?')
                ->execute([$res['filename'], $u['id']]);
            $_SESSION['user_avatar'] = $res['filename'];
            $avatarUrl = UPLOAD_URL . $res['filename'];
            $u['avatar'] = $res['filename'];
            $msgAvatar = 'Foto de perfil atualizada!';
        } else {
            $erroAvatar = $res['msg'];
        }
    }
}

/* ── POST: remover avatar ────────────────────────────────────────*/
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['acao'] ?? '') === 'remover_avatar') {
    csrfVerify();
    if ($u['avatar'] && file_exists(UPLOAD_DIR . $u['avatar'])) {
        @unlink(UPLOAD_DIR . $u['avatar']);
    }
    $pdo->prepare('UPDATE usuarios SET avatar = "" WHERE id = ?')->execute([$u['id']]);
    $_SESSION['user_avatar'] = '';
    $avatarUrl = '';
    $u['avatar'] = '';
    $msgAvatar = 'Foto removida.';
}

/* ── POST: salvar configurações ──────────────────────────────────*/
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['acao'] ?? '') === 'salvar_config') {
    csrfVerify();
    $novoTema = in_array($_POST['tema'] ?? '', ['dark','light']) ? $_POST['tema'] : 'dark';
    $pdo->prepare('
        UPDATE usuarios SET
            tema=?,fonte=?,cfg_animacoes=?,cfg_alerta_metas=?,
            cfg_resumo_semanal=?,cfg_alerta_gastos=?,cfg_email_notif=?,
            cfg_2fa=?,cfg_ocultar_saldo=?,cfg_sessao=?,cfg_sync_auto=?
        WHERE id=?
    ')->execute([
        $novoTema,
        htmlspecialchars($_POST['fonte']  ?? 'medio'),
        isset($_POST['animacoes'])      ? 1 : 0,
        isset($_POST['alerta_metas'])   ? 1 : 0,
        isset($_POST['resumo_semanal']) ? 1 : 0,
        isset($_POST['alerta_gastos'])  ? 1 : 0,
        isset($_POST['email_notif'])    ? 1 : 0,
        isset($_POST['2fa'])            ? 1 : 0,
        isset($_POST['ocultar_saldo'])  ? 1 : 0,
        htmlspecialchars($_POST['sessao'] ?? '30min'),
        isset($_POST['sync_auto'])      ? 1 : 0,
        $u['id'],
    ]);
    $_SESSION['user_tema'] = $novoTema;
    $tema = $novoTema;
    $msgConfig = 'Configurações salvas!';
    $stmt->execute([$u['id']]);
    $u = $stmt->fetch();
}

/* ── POST: metas (CRUD) ───────────────────────*/
$msgMeta = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['acao'] ?? '') === 'salvar_meta') {
    csrfVerify();
    $mNome   = trim(htmlspecialchars($_POST['meta_nome']     ?? ''));
    $mCat    = htmlspecialchars($_POST['meta_categoria']     ?? 'outro');
    $mTotal  = (float)($_POST['meta_total'] ?? 0);
    $mAtual  = (float)($_POST['meta_atual'] ?? 0);
    $mPrazo  = htmlspecialchars($_POST['meta_prazo']         ?? '');
    $mStatus = htmlspecialchars($_POST['meta_status']        ?? 'andamento');
    $mId     = (int)($_POST['meta_id'] ?? 0);

    if ($mNome && $mTotal > 0 && $mPrazo) {
        if ($mId) {
            $pdo->prepare('UPDATE metas SET nome=?,categoria=?,valor_total=?,valor_atual=?,prazo=?,status=? WHERE id=? AND usuario_id=?')
                ->execute([$mNome,$mCat,$mTotal,$mAtual,$mPrazo,$mStatus,$mId,$u['id']]);
            $msgMeta = 'Meta atualizada!';
        } else {
            $pdo->prepare('INSERT INTO metas (usuario_id,nome,categoria,valor_total,valor_atual,prazo,status) VALUES (?,?,?,?,?,?,?)')
                ->execute([$u['id'],$mNome,$mCat,$mTotal,$mAtual,$mPrazo,$mStatus]);
            $msgMeta = 'Meta criada!';
        }
        $mStmt->execute([$u['id']]);
        $metasDB = $mStmt->fetchAll();
    }
}
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['acao'] ?? '') === 'excluir_meta') {
    csrfVerify();
    $mId = (int)($_POST['meta_id'] ?? 0);
    $pdo->prepare('DELETE FROM metas WHERE id = ? AND usuario_id = ?')->execute([$mId, $u['id']]);
    $mStmt->execute([$u['id']]);
    $metasDB = $mStmt->fetchAll();
    $msgMeta = 'Meta excluída.';
}

/* ── Helpers ─────────────────────────────────────────────────────*/
function chk($v): string  { return $v ? 'checked' : ''; }
function sel($a,$b): string { return $a === $b ? 'selected' : ''; }

$csrf = csrfToken();

/* ── Definição da Página Ativa Inicial ───────────────────────────*/
$paginaInicial = 'page-dashboard'; // Alterado para iniciar no dashboard por padrão
if ($msgPerfil || $erroPerfil || $msgAvatar || $erroAvatar || $msgMeta) $paginaInicial = 'page-conta';
if ($msgConfig)  $paginaInicial = 'page-config';
if (isset($_GET['pesquisa']) || isset($_GET['ver_curso'])) $paginaInicial = 'page-curso';
?>
<!DOCTYPE html>
<html lang="pt-BR" data-theme="<?= $tema ?>">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>METAL Financeiro</title>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@tabler/icons-webfont@3.19.0/dist/tabler-icons.min.css">
  <link rel="stylesheet" href="style.css">
</head>
<body data-pagina="<?= $paginaInicial ?>">

<aside class="sidebar" id="sidebar">
  <div class="menu-toggle" onclick="toggleSidebar()">
    <i class="ti ti-menu-2"></i>
  </div>

  <div class="profile-container">
    <?php if ($avatarUrl): ?>
      <div class="avatar-placeholder avatar-img">
        <img src="<?= $avatarUrl ?>" alt="Foto de perfil">
      </div>
    <?php else: ?>
      <div class="avatar-placeholder"><i class="ti ti-user-circle"></i></div>
    <?php endif; ?>
    <div class="username" id="sidebar-username"><?= htmlspecialchars($nomeCompleto) ?></div>
    <?php if ($isAdmin): ?>
      <div class="admin-badge"><i class="ti ti-shield-filled"></i> Administrador</div>
    <?php else: ?>
      <div class="user-email-sb"><?= htmlspecialchars($u['email']) ?></div>
    <?php endif; ?>
  </div>

  <ul class="nav-menu">
    <li class="nav-item <?= $paginaInicial === 'page-conta' ? 'active' : '' ?>" onclick="navTo('page-conta',this)">
      <a><i class="ti ti-wallet"></i><span class="nav-label">Conta</span></a>
    </li>
    <li class="nav-item" onclick="navTo('page-conta',this,'perfil')">
      <a><i class="ti ti-user"></i><span class="nav-label">Perfil</span></a>
    </li>
    <li class="nav-item <?= $paginaInicial === 'page-config' ? 'active' : '' ?>" onclick="navTo('page-config',this)">
      <a><i class="ti ti-settings"></i><span class="nav-label">Configuração</span></a>
    </li>
    <li class="nav-item" onclick="navTo('page-graficos',this)">
      <a><i class="ti ti-chart-bar"></i><span class="nav-label">Gráficos</span></a>
    </li>
    <li class="nav-item" onclick="navTo('page-dashboard',this)">
      <a><i class="ti ti-notes"></i><span class="nav-label">Notas</span></a>
    </li>
    <li class="nav-item <?= $paginaInicial === 'page-curso' ? 'active' : '' ?>" onclick="navTo('page-curso',this)">
      <a><i class="ti ti-book"></i><span class="nav-label">Cursos</span></a>
    </li>
    <li class="nav-item" onclick="navTo('page-metas',this)">
      <a><i class="ti ti-target"></i><span class="nav-label">Metas</span></a>
    </li>
  </ul>

  <a href="logout.php" class="btn-sair">
    <i class="ti ti-logout"></i>
    <span class="btn-sair-label">Sair</span>
  </a>
</aside>

<main class="main-content">

  <div class="header-top">
    <div class="logo">METAL<span>Financeiro</span></div>
    <div class="header-icons">
      <button class="icon-btn" onclick="showToast('Sem novas notificações','ti-bell')" title="Notificações">
        <i class="ti ti-bell"></i><span class="notif-dot"></span>
      </button>
      <button class="icon-btn" onclick="showToast('Central de ajuda','ti-help-circle')" title="Ajuda">
        <i class="ti ti-help-circle"></i>
      </button>
    </div>
  </div>

  <div class="page <?= $paginaInicial === 'page-dashboard' ? 'active' : '' ?>" id="page-dashboard">
    <div class="search-container">
      <div class="search-box">
        <input type="text" placeholder="Pesquisar...">
        <i class="ti ti-search"></i>
      </div>
    </div>
    <div class="section-title">Acesso rápido</div>
    <div class="cards-wrapper">
      <div class="cards-row-top">
        <div class="card" onclick="navTo('page-metas',document.querySelector('.nav-menu li:nth-child(7)'))">
          <i class="ti ti-target"></i><span>Metas</span>
        </div>
      </div>
      <div class="cards-row-bottom">
        <div class="card" onclick="showToast('Tutoriais em breve!','ti-video')">
          <i class="ti ti-video"></i><span>Tutoriais</span>
        </div>
        <div class="card" onclick="showToast('Calculadora em breve!','ti-calculator')">
          <i class="ti ti-calculator"></i><span>Cálculos</span>
        </div>
      </div>
    </div>
    
    <div class="section-title">Resumo financeiro</div>
    <div class="stats-row">
      <div class="stat">
        <div class="stat-label">Saldo atual</div>
        <div class="stat-value">R$ 12.480</div>
        <div class="stat-up">↑ +3,2% este mês</div>
      </div>
      <div class="stat">
        <div class="stat-label">Meta mensal</div>
        <div class="stat-value">78%</div>
        <div class="stat-sub">R$ 7.800 de R$ 10.000</div>
      </div>
      <div class="stat">
        <div class="stat-label">Gastos</div>
        <div class="stat-value">R$ 3.210</div>
        <div class="stat-down">↓ -1,5% vs mês anterior</div>
      </div>
    </div>
  </div>

  <div class="page <?= $paginaInicial === 'page-curso' ? 'active' : '' ?>" id="page-curso">
    
    <?php
    $cursos = [
        ["id" => 1, "nome" => "Administração financeira", "tempo" => "0:45:55", "porcentagem" => 70],
        ["id" => 2, "nome" => "Curso de Investimentos Básicos", "tempo" => "1:25:05", "porcentagem" => 90],
        ["id" => 3, "nome" => "Planejamento Familiar", "tempo" => "0:03:00", "porcentagem" => 30],
    ];

    $modulos = [
        ["nome" => "Módulo 1", "status" => "feito ✓", "classe" => "feito"],
        ["nome" => "Módulo 2", "status" => "não feito ✓", "classe" => "nao-feito"],
        ["nome" => "Estudo", "status" => "visto ^", "classe" => "cinza"],
        ["nome" => "Lição", "status" => "não feito ^^", "classe" => "cinza"],
        ["nome" => "Módulo 3", "status" => "não feito v", "classe" => "nao-feito"],
    ];

    $pesquisa = htmlspecialchars($_GET['pesquisa'] ?? '');
    $verCurso = (int)($_GET['ver_curso'] ?? 0);
    ?>

    <?php if ($verCurso === 0): ?>
      <div class="section-title">Andamento dos Cursos</div>
      
      <form class="pesquisa-curso-form" method="GET" action="index.php" style="margin-bottom: 20px; display: flex; gap: 10px;">
        <input type="text" name="pesquisa" placeholder="Pesquisar curso..." value="<?= $pesquisa ?>" class="form-input" style="max-width: 300px;">
        <button type="submit" class="btn-primary" style="padding: 10px 15px;"><i class="ti ti-search"></i></button>
      </form>

      <div class="lista-cursos">
        <?php foreach($cursos as $c) { 
          if($pesquisa !== "" && stripos($c["nome"], $pesquisa) === false) {
            continue;
          }
        ?>
          <div class="curso-item-box" style="background: var(--bg-card); padding: 15px; border-radius: 8px; margin-bottom: 15px; border-left: 4px solid var(--accent);">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 10px;">
              <strong class="nome"><?= $c["nome"] ?></strong>
              <span class="tempo" style="font-size: 0.85rem; opacity: 0.7;"><i class="ti ti-clock"></i> <?= $c["tempo"] ?></span>
            </div>
            <div class="barra" style="background: rgba(0,0,0,0.1); height: 8px; border-radius: 4px; overflow: hidden; margin-bottom: 10px;">
              <div class="progresso" style="width: <?= $c["porcentagem"] ?>%; background: var(--gold); height: 100%;"></div>
            </div>
            <div style="display: flex; justify-content: space-between; align-items: center;">
              <span style="font-size: 0.8rem;"><?= $c["porcentagem"] ?>% concluído</span>
              <a href="index.php?ver_curso=<?= $c['id'] ?>" class="btn-save" style="font-size: 0.8rem; padding: 4px 10px; text-decoration: none;">Acessar Módulos →</a>
            </div>
          </div>
        <?php } ?>
      </div>

    <?php else: ?>
      <div class="page-header">
        <div>
          <h2>Curso: <span>Administração Financeira</span></h2>
          <p style="opacity: 0.7; font-size: 0.9rem;">Grade curricular do aluno</p>
        </div>
        <a href="index.php?page=page-curso" class="btn-cancel" style="text-decoration: none;"><i class="ti ti-arrow-left"></i> Voltar aos cursos</a>
      </div>

      <div class="lista-modulos-detalhe" style="display: grid; gap: 12px; margin-top: 20px;">
        <?php foreach($modulos as $m) { ?>
          <div class="modulo-item <?= $m["classe"] ?>" style="display: flex; justify-content: space-between; background: var(--bg-card); padding: 15px; border-radius: 6px; align-items: center;">
            <span><i class="ti ti-notebook"></i> <?= $m["nome"] ?></span>
            <span class="status-badge" style="text-transform: uppercase; font-size: 0.75rem; font-weight: bold;"><?= $m["status"] ?></span>
          </div>
        <?php } ?>
      </div>
    <?php endif; ?>
  </div>

  <div class="page" id="page-metas">
    <div class="page-header">
      <h2>Minhas <span>Metas</span></h2>
      <button class="btn-primary" onclick="MetasModule.abrirModal()">
        <i class="ti ti-plus"></i> Nova meta
      </button>
    </div>
    <?php if ($msgMeta): ?>
      <div class="alert alert-success" style="margin-bottom:16px">
        <i class="ti ti-circle-check"></i> <?= htmlspecialchars($msgMeta) ?>
      </div>
    <?php endif; ?>
    <div class="metas-resumo">
      <div class="resumo-card"><div class="resumo-num" id="resumo-total">0</div><div class="resumo-label">Total de metas</div></div>
      <div class="resumo-card"><div class="resumo-num" id="resumo-concluidas">0</div><div class="resumo-label">Concluídas</div></div>
      <div class="resumo-card"><div class="resumo-num" id="resumo-saldo">R$ 0</div><div class="resumo-label">Total poupado</div></div>
    </div>
    <div class="section-title">Suas metas</div>
    <div class="metas-list" id="metas-list"></div>
  </div>

  <div class="page" id="page-graficos">
    <div class="page-header">
      <h2>Meus <span>Gráficos</span></h2>
      <button class="btn-primary" onclick="GraficosModule.abrirModal()">
        <i class="ti ti-plus"></i> Novo gráfico
      </button>
    </div>
    <div class="graficos-grid" id="graficos-grid"></div>
  </div>

  <div class="page" id="page-conta">
    <div class="page-header"><h2>Minha <span>Conta</span></h2></div>
    <div class="tabs" id="conta-tabs">
      <button class="tab-btn active" onclick="switchTab('conta-tab',this)"><i class="ti ti-wallet"></i> Conta</button>
      <button class="tab-btn" onclick="switchTab('perfil-tab',this)"><i class="ti ti-user-edit"></i> Editar Perfil</button>
    </div>

    <div class="tab-panel active" id="conta-tab">
      <div class="conta-grid">
        <div class="saldo-hero">
          <div>
            <div class="saldo-hero-label">Saldo disponível</div>
            <div class="saldo-hero-value">R$ 12.480,00</div>
            <div class="saldo-hero-sub">↑ +3,2% este mês</div>
          </div>
          <div class="saldo-icon"><i class="ti ti-coins"></i></div>
        </div>
        <div class="info-card">
          <div class="info-card-label">Tipo de plano</div>
          <div class="info-card-value gold">Premium <span class="meta-badge badge-concluida" style="font-size:.72rem;margin-left:6px;"><i class="ti ti-star"></i> Ativo</span></div>
        </div>
        <div class="info-card">
          <div class="info-card-label">Membro desde</div>
          <div class="info-card-value sm"><?= date('d/m/Y', strtotime($u['criado_em'])) ?></div>
        </div>
      </div>
    </div>

    <div class="tab-panel" id="perfil-tab">
      <form method="POST" action="index.php">
        <input type="hidden" name="csrf_token" value="<?= $csrf ?>">
        <input type="hidden" name="acao" value="salvar_perfil">
        <div class="perfil-form-grid">
          <div class="form-group">
            <label class="form-label">Nome</label>
            <input class="form-input" name="nome" type="text" value="<?= htmlspecialchars($u['nome']) ?>" required>
          </div>
          <div class="form-group">
            <label class="form-label">Sobrenome</label>
            <input class="form-input" name="sobrenome" type="text" value="<?= htmlspecialchars($u['sobrenome']) ?>">
          </div>
          <div class="form-group full">
            <label class="form-label">E-mail</label>
            <input class="form-input" name="email" type="email" value="<?= htmlspecialchars($u['email']) ?>" required>
          </div>
        </div>
        <div class="save-row" style="margin-top: 20px;">
          <button type="submit" class="btn-save"><i class="ti ti-device-floppy"></i> Salvar alterações</button>
        </div>
      </form>
    </div>
  </div>

  <div class="page" id="page-config">
    <div class="page-header"><h2>Configurações <span>do sistema</span></h2></div>
    <form method="POST" action="index.php">
      <input type="hidden" name="csrf_token" value="<?= $csrf ?>">
      <input type="hidden" name="acao" value="salvar_config">
      <div class="config-card" style="background: var(--bg-card); padding: 20px; border-radius: 8px;">
         <label class="form-label">Tamanho da Fonte</label>
         <select class="cfg-select" name="fonte" style="width: 100%; padding: 8px; margin-top: 5px;">
            <option value="pequeno" <?= sel($u['fonte'],'pequeno') ?>>Pequeno</option>
            <option value="medio" <?= sel($u['fonte'],'medio') ?>>Médio</option>
            <option value="grande" <?= sel($u['fonte'],'grande') ?>>Grande</option>
         </select>
         <button type="submit" class="btn-save" style="margin-top:15px;"><i class="ti ti-device-floppy"></i> Salvar Configurações</button>
      </div>
    </form>
  </div>

</main>

<script>
  // Script SPA Simples para alternar entre os IDs das divs class="page"
  function navTo(pageId, element, subTab = null) {
    document.querySelectorAll('.page').forEach(p => p.classList.remove('active'));
    document.querySelectorAll('.nav-item').forEach(i => i.classList.remove('active'));
    
    const targetPage = document.getElementById(pageId);
    if(targetPage) targetPage.classList.add('active');
    if(element) element.classList.add('active');
    
    if(subTab === 'perfil') {
      switchTab('perfil-tab', document.querySelector('.tab-btn:nth-child(2)'));
    }
  }

  function switchTab(tabId, btn) {
    document.querySelectorAll('.tab-panel').forEach(p => p.classList.remove('active'));
    document.querySelectorAll('.tab-btn').forEach(b => b.classList.remove('active'));
    document.getElementById(tabId).classList.add('active');
    btn.classList.add('active');
  }
</script>
</body>
</html>