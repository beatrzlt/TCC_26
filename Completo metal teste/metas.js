// ===== METAS MODULE =====
const MetasModule = (() => {

  const ICONES = {
    viagem:    'ti-plane',
    casa:      'ti-home',
    carro:     'ti-car',
    educacao:  'ti-school',
    emergencia:'ti-shield',
    aposentadoria: 'ti-trending-up',
    outro:     'ti-star'
  };

  let metas = [
    { id: 1, nome: 'Viagem para Europa', categoria: 'viagem',    atual: 8500,  total: 15000, prazo: '2025-12', status: 'andamento' },
    { id: 2, nome: 'Fundo de emergência', categoria: 'emergencia', atual: 10000, total: 10000, prazo: '2024-06', status: 'concluida' },
    { id: 3, nome: 'Entrada do apartamento', categoria: 'casa',  atual: 22000, total: 60000, prazo: '2026-12', status: 'andamento' },
    { id: 4, nome: 'Curso de MBA',          categoria: 'educacao', atual: 1200, total: 8000, prazo: '2024-03', status: 'atrasada'  }
  ];

  let nextId = 5;
  let editando = null;

  function pct(meta) {
    return Math.min(100, Math.round((meta.atual / meta.total) * 100));
  }

  function formatBRL(v) {
    return v.toLocaleString('pt-BR', { style: 'currency', currency: 'BRL' });
  }

  function prazoFormatado(str) {
    const [ano, mes] = str.split('-');
    const meses = ['Jan','Fev','Mar','Abr','Mai','Jun','Jul','Ago','Set','Out','Nov','Dez'];
    return `${meses[parseInt(mes)-1]} ${ano}`;
  }

  function badgeHTML(status) {
    const map = {
      andamento: ['badge-andamento', 'ti-clock',     'Em andamento'],
      concluida: ['badge-concluida', 'ti-circle-check','Concluída'],
      atrasada:  ['badge-atrasada',  'ti-alert-circle','Atrasada']
    };
    const [cls, ic, label] = map[status] || map.andamento;
    return `<span class="meta-badge ${cls}"><i class="ti ${ic}"></i> ${label}</span>`;
  }

  function renderMeta(meta) {
    const p = pct(meta);
    const conc = meta.status === 'concluida' ? 'meta-concluida' : '';
    return `
      <div class="meta-card ${conc}" data-id="${meta.id}">
        <div class="meta-card-header">
          <div class="meta-card-title">
            <div class="meta-icon"><i class="ti ${ICONES[meta.categoria] || ICONES.outro}"></i></div>
            <div>
              <div class="meta-name">${meta.nome}</div>
              <div class="meta-prazo">Prazo: ${prazoFormatado(meta.prazo)}</div>
            </div>
          </div>
          <div class="meta-actions">
            <button class="meta-btn" onclick="MetasModule.abrirEditar(${meta.id})" title="Editar"><i class="ti ti-pencil"></i></button>
            <button class="meta-btn delete" onclick="MetasModule.excluir(${meta.id})" title="Excluir"><i class="ti ti-trash"></i></button>
          </div>
        </div>
        <div class="meta-valores">
          <span class="meta-atual">${formatBRL(meta.atual)}</span>
          <span class="meta-total">de ${formatBRL(meta.total)}</span>
          <span class="meta-pct">${p}%</span>
        </div>
        <div class="progress-bar"><div class="progress-fill" style="width:${p}%"></div></div>
        ${badgeHTML(meta.status)}
      </div>`;
  }

  function renderResumo() {
    const total     = metas.length;
    const concluidas= metas.filter(m => m.status === 'concluida').length;
    const totalSaldo= metas.reduce((s, m) => s + m.atual, 0);
    document.getElementById('resumo-total').textContent     = total;
    document.getElementById('resumo-concluidas').textContent = concluidas;
    document.getElementById('resumo-saldo').textContent     = formatBRL(totalSaldo);
  }

  function render() {
    const list = document.getElementById('metas-list');
    if (!list) return;
    list.innerHTML = metas.length
      ? metas.map(renderMeta).join('')
      : `<div style="text-align:center;color:var(--text-muted);padding:40px;">
           <i class="ti ti-target" style="font-size:48px;display:block;margin-bottom:12px;"></i>
           Nenhuma meta cadastrada ainda.
         </div>`;
    renderResumo();
  }

  function abrirModal() {
    editando = null;
    document.getElementById('modal-meta-title').innerHTML = '<i class="ti ti-target"></i> Nova meta';
    document.getElementById('form-meta').reset();
    document.getElementById('modal-meta').classList.add('open');
  }

  function abrirEditar(id) {
    const meta = metas.find(m => m.id === id);
    if (!meta) return;
    editando = id;
    document.getElementById('modal-meta-title').innerHTML = '<i class="ti ti-pencil"></i> Editar meta';
    document.getElementById('meta-nome').value      = meta.nome;
    document.getElementById('meta-categoria').value = meta.categoria;
    document.getElementById('meta-total').value     = meta.total;
    document.getElementById('meta-atual').value     = meta.atual;
    document.getElementById('meta-prazo').value     = meta.prazo;
    document.getElementById('meta-status').value    = meta.status;
    document.getElementById('modal-meta').classList.add('open');
  }

  function fecharModal() {
    document.getElementById('modal-meta').classList.remove('open');
  }

  function salvar() {
    const nome      = document.getElementById('meta-nome').value.trim();
    const categoria = document.getElementById('meta-categoria').value;
    const total     = parseFloat(document.getElementById('meta-total').value);
    const atual     = parseFloat(document.getElementById('meta-atual').value);
    const prazo     = document.getElementById('meta-prazo').value;
    const status    = document.getElementById('meta-status').value;

    if (!nome || !total || !prazo) {
      showToast('Preencha os campos obrigatórios.', 'ti-alert-circle');
      return;
    }

    if (editando) {
      const idx = metas.findIndex(m => m.id === editando);
      metas[idx] = { id: editando, nome, categoria, total, atual: atual || 0, prazo, status };
      showToast('Meta atualizada!', 'ti-check');
    } else {
      metas.push({ id: nextId++, nome, categoria, total, atual: atual || 0, prazo, status });
      showToast('Meta criada!', 'ti-check');
    }

    fecharModal();
    render();
  }

  function excluir(id) {
    if (!confirm('Excluir esta meta?')) return;
    metas = metas.filter(m => m.id !== id);
    render();
    showToast('Meta excluída.', 'ti-trash');
  }

  return { init: render, render, abrirModal, abrirEditar, fecharModal, salvar, excluir };
})();
