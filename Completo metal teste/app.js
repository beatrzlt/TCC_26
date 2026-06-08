
   /* Lógica principal do painel*/


// ── Toast ────────────────────────────────────────────────────────
let toastTimer;
function showToast(msg, icon = 'ti-check') {
  const t = document.getElementById('toast');
  t.innerHTML = `<i class="ti ${icon}"></i> ${msg}`;
  t.classList.add('show');
  clearTimeout(toastTimer);
  toastTimer = setTimeout(() => t.classList.remove('show'), 2800);
}

// ── Sidebar ──────────────────────────────────────────────────────
function toggleSidebar() {
  document.querySelector('.sidebar').classList.toggle('collapsed');
}

// ── Navegação entre páginas ──────────────────────────────────────
/**
 * @param {string}  pageId  – id da div.page destino
 * @param {Element} el      – elemento clicado no nav
 * @param {string}  [tab]   – 'conta' | 'perfil'
 */
function navTo(pageId, el, tab) {
  document.querySelectorAll('.page').forEach(p => p.classList.remove('active'));
  document.getElementById(pageId)?.classList.add('active');

  document.querySelectorAll('.nav-item').forEach(n => n.classList.remove('active'));
  el?.closest?.('.nav-item')?.classList.add('active');

  // Abre tab específica em page-conta
  if (pageId === 'page-conta') {
    const tabId = tab === 'perfil' ? 'perfil-tab' : 'conta-tab';
    const btnIdx = tab === 'perfil' ? 1 : 0;
    const btns = document.querySelectorAll('#conta-tabs .tab-btn');
    switchTab(tabId, btns[btnIdx]);
  }

  if (pageId === 'page-graficos') GraficosModule.render();
}

// ── Tabs ─────────────────────────────────────────────────────────
function switchTab(id, btn) {
  document.querySelectorAll('#conta-tabs .tab-btn')
    .forEach(b => b.classList.remove('active'));
  document.querySelectorAll('#page-conta .tab-panel')
    .forEach(p => p.classList.remove('active'));
  btn?.classList.add('active');
  document.getElementById(id)?.classList.add('active');
}

// ── Preview nome no sidebar em tempo real ────────────────────────
function previewNome() {
  const nome = document.getElementById('f-nome')?.value.trim() ?? '';
  const sobrenome = document.getElementById('f-sobrenome')?.value.trim() ?? '';
  const full = [nome, sobrenome].filter(Boolean).join(' ');
  const el = document.getElementById('sidebar-username');
  if (el) el.textContent = full || '—';
}

// ── Olho da senha ────────────────────────────────────────────────
function togglePwd(inputId, iconId) {
  const inp = document.getElementById(inputId);
  const ico = document.getElementById(iconId);
  if (!inp || !ico) return;
  inp.type = inp.type === 'password' ? 'text' : 'password';
  ico.className = inp.type === 'text' ? 'ti ti-eye-off' : 'ti ti-eye';
}

// ── Tema claro / escuro ──────────────────────────────────────────
function setTheme(theme) {
  document.documentElement.setAttribute('data-theme', theme);
  const inp = document.getElementById('input-tema');
  if (inp) inp.value = theme;

  document.getElementById('opt-dark')?.classList.toggle('active', theme === 'dark');
  document.getElementById('opt-light')?.classList.toggle('active', theme === 'light');

  showToast(
    theme === 'dark' ? 'Tema escuro ativado' : 'Tema claro ativado',
    theme === 'dark' ? 'ti-moon' : 'ti-sun'
  );
}

// ── Preview de avatar antes de salvar ────────────────────────────
function previewAvatar(input) {
  if (!input.files || !input.files[0]) return;

  const file = input.files[0];
  const maxSize = 2 * 1024 * 1024;

  if (file.size > maxSize) {
    showToast('Imagem muito grande. Máximo 2 MB.', 'ti-alert-circle');
    input.value = '';
    return;
  }

  const reader = new FileReader();
  reader.onload = function (e) {
    const wrap = document.getElementById('avatar-preview-wrap');
    if (!wrap) return;

    // Substitui ícone por imagem
    wrap.innerHTML = `
      <img src="${e.target.result}" alt="Preview" style="width:100%;height:100%;object-fit:cover">
      <div class="avatar-upload-overlay"><i class="ti ti-camera"></i><span>Alterar</span></div>
    `;
    wrap.onclick = () => document.getElementById('file-avatar').click();

    // Mostra botão de salvar
    const btnSalvar = document.getElementById('btn-salvar-avatar');
    if (btnSalvar) btnSalvar.style.display = 'inline-flex';

    showToast('Clique em "Salvar foto" para confirmar.', 'ti-photo');
  };
  reader.readAsDataURL(file);
}

// ── Pesquisa dashboard ───────────────────────────────────────────
function initSearch() {
  let searchTimer;
  document.getElementById('search-input')?.addEventListener('input', e => {
    clearTimeout(searchTimer);
    const q = e.target.value.trim();
    if (q.length > 2) {
      searchTimer = setTimeout(() =>
        showToast(`Pesquisando: "${q}"`, 'ti-search'), 400);
    }
  });
}

// ── Determina página inicial após POST do PHP ────────────────────
function initPage() {
  const paginaInicial = document.body.dataset.pagina || 'page-metas';

  if (paginaInicial !== 'page-metas') {
    // mapeia pageId → índice do nav-item
    const navMap = {
      'page-conta': 0,
      'page-config': 2,
      'page-graficos': 3,
      'page-cursos': 5,
    };
    const idx = navMap[paginaInicial] ?? -1;
    const navEl = idx >= 0
      ? document.querySelectorAll('.nav-item')[idx]
      : null;

    // Esconde page-metas e ativa a correta
    document.querySelectorAll('.page').forEach(p => p.classList.remove('active'));
    document.getElementById(paginaInicial)?.classList.add('active');
    document.querySelectorAll('.nav-item').forEach(n => n.classList.remove('active'));
    navEl?.classList.add('active');

    // Se for conta + há alerta → abre aba perfil
    if (paginaInicial === 'page-conta') {
      const temAlerta = !!document.querySelector('#perfil-tab .alert');
      if (temAlerta) {
        const btns = document.querySelectorAll('#conta-tabs .tab-btn');
        switchTab('perfil-tab', btns[1]);
      }
    }
  }

  // Inicializa módulos da página ativa
  MetasModule.init();
  GraficosModule.init();
}

// ── Toast para mensagens PHP injetadas via data-attr ─────────────
function initToastFromPHP() {
  // As mensagens são emitidas inline pelo PHP logo abaixo dos scripts
  // usando window.addEventListener('DOMContentLoaded', ...) no index.php
}

// ── DOMContentLoaded ─────────────────────────────────────────────
document.addEventListener('DOMContentLoaded', () => {
  initSearch();
  initPage();

  if (window.MSG_META && window.MSG_META !== '') {
    showToast(window.MSG_META, 'ti-circle-check');
  }
});

// ── Cursos: sub-tabs ─────────────────────────────────────────────
function switchCursosTab(id, btn) {
  document.querySelectorAll('#cursos-tabs .tab-btn')
    .forEach(b => b.classList.remove('active'));
  document.querySelectorAll('#page-cursos .tab-panel')
    .forEach(p => p.classList.remove('active'));
  btn?.classList.add('active');
  document.getElementById(id)?.classList.add('active');
}

// ── Cursos: toggle módulos (accordion) ───────────────────────────
function toggleCurso(id) {
  const modulos = document.getElementById('modulos-' + id);
  const chev = document.getElementById('chev-' + id);
  if (!modulos) return;
  const aberto = modulos.style.display !== 'none';
  modulos.style.display = aberto ? 'none' : 'block';
  if (chev) chev.style.transform = aberto ? '' : 'rotate(180deg)';
}

// ── Cursos: pesquisa (lista) ─────────────────────────────────────
function filtrarCursos(query) {
  const q = query.toLowerCase().trim();
  const items = document.querySelectorAll('#cursos-grid .curso-card');
  let visible = 0;

  items.forEach(el => {
    const nome = el.dataset.nome || '';
    const cat = el.dataset.cat || '';
    const show = q === '' || nome.includes(q) || cat.includes(q);
    el.style.display = show ? '' : 'none';
    if (show) visible++;
  });

  const empty = document.getElementById('cursos-empty');
  if (empty) empty.style.display = visible === 0 ? 'flex' : 'none';
}

// ── Cursos: pesquisa (andamento) ─────────────────────────────────
function filtrarAndamento(query) {
  const q = query.toLowerCase().trim();
  const items = document.querySelectorAll('#andamento-lista .andamento-item');
  let visible = 0;

  items.forEach(el => {
    const nome = el.dataset.nome || '';
    const show = q === '' || nome.includes(q);
    el.style.display = show ? '' : 'none';
    if (show) visible++;
  });

  const empty = document.getElementById('andamento-empty');
  if (empty) empty.style.display = visible === 0 ? 'flex' : 'none';
}
