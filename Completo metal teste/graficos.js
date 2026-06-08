// ===== GRAFICOS MODULE =====
const GraficosModule = (() => {

  const GOLD   = '#c59b27';
  const NAVY   = '#002060';
  const SILVER = '#dcdcdc';
  const RED    = '#e24b4a';
  const GREEN  = '#4caf50';

  const PALETA = [GOLD, '#3b7dd8', GREEN, RED, '#9c59d1', '#e87420', SILVER];

  let graficos = [
    {
      id: 1, titulo: 'Receitas vs Despesas', tipo: 'bar',
      labels: ['Jan','Fev','Mar','Abr','Mai','Jun'],
      datasets: [
        { label: 'Receitas', data: [4200,4800,5100,4600,5400,5800], color: GOLD },
        { label: 'Despesas', data: [3100,3400,2900,3700,3200,3500], color: NAVY }
      ]
    },
    {
      id: 2, titulo: 'Distribuição de gastos', tipo: 'doughnut',
      labels: ['Moradia','Alimentação','Transporte','Lazer','Saúde','Outros'],
      datasets: [{ label: 'Gastos', data: [1200,800,450,320,280,160], color: null }]
    },
    {
      id: 3, titulo: 'Evolução do patrimônio', tipo: 'line',
      labels: ['Jan','Fev','Mar','Abr','Mai','Jun'],
      datasets: [{ label: 'Patrimônio', data: [8000,9200,10100,11500,12400,13800], color: GREEN }]
    }
  ];

  let nextId = 4;
  let selectedTipo = 'bar';
  const chartInstances = {};

  function TIPO_ICON(tipo) {
    const m = { bar:'ti-chart-bar', line:'ti-chart-line', doughnut:'ti-chart-donut', pie:'ti-chart-pie' };
    return m[tipo] || 'ti-chart-bar';
  }

  function buildChartConfig(g) {
    const isDoughnut = g.tipo === 'doughnut' || g.tipo === 'pie';

    if (isDoughnut) {
      return {
        type: g.tipo,
        data: {
          labels: g.labels,
          datasets: [{
            data: g.datasets[0].data,
            backgroundColor: PALETA,
            borderColor: '#1a1a1a',
            borderWidth: 2
          }]
        },
        options: {
          responsive: true, maintainAspectRatio: false,
          plugins: {
            legend: { position: 'right', labels: { color: SILVER, font: { size: 11 }, boxWidth: 12, padding: 8 } }
          }
        }
      };
    }

    return {
      type: g.tipo,
      data: {
        labels: g.labels,
        datasets: g.datasets.map((ds, i) => ({
          label: ds.label,
          data: ds.data,
          backgroundColor: g.tipo === 'bar' ? (ds.color || PALETA[i]) + 'cc' : 'transparent',
          borderColor: ds.color || PALETA[i],
          borderWidth: g.tipo === 'line' ? 2 : 0,
          fill: g.tipo === 'line',
          tension: 0.4,
          pointBackgroundColor: ds.color || PALETA[i],
          pointRadius: 4
        }))
      },
      options: {
        responsive: true, maintainAspectRatio: false,
        plugins: {
          legend: { labels: { color: SILVER, font: { size: 11 } } }
        },
        scales: {
          x: { grid: { color: '#2e2e2e' }, ticks: { color: '#777' } },
          y: { grid: { color: '#2e2e2e' }, ticks: { color: '#777' } }
        }
      }
    };
  }

  function renderCard(g) {
    return `
      <div class="grafico-card" id="gc-${g.id}">
        <div class="grafico-card-header">
          <div class="grafico-card-title">
            <i class="ti ${TIPO_ICON(g.tipo)}"></i>
            ${g.titulo}
          </div>
          <button class="grafico-del-btn" onclick="GraficosModule.excluir(${g.id})" title="Excluir gráfico">
            <i class="ti ti-trash"></i>
          </button>
        </div>
        <div class="chart-container">
          <canvas id="chart-${g.id}"></canvas>
        </div>
      </div>`;
  }

  function mountChart(g) {
    const canvas = document.getElementById(`chart-${g.id}`);
    if (!canvas) return;
    if (chartInstances[g.id]) { chartInstances[g.id].destroy(); }
    chartInstances[g.id] = new Chart(canvas, buildChartConfig(g));
  }

  function render() {
    const grid = document.getElementById('graficos-grid');
    if (!grid) return;
    grid.innerHTML = graficos.length
      ? graficos.map(renderCard).join('')
      : `<div style="grid-column:1/-1;text-align:center;color:var(--text-muted);padding:60px;">
           <i class="ti ti-chart-bar" style="font-size:52px;display:block;margin-bottom:14px;"></i>
           Nenhum gráfico criado ainda. Clique em <strong style="color:var(--gold)">+ Novo gráfico</strong>.
         </div>`;
    setTimeout(() => graficos.forEach(mountChart), 50);
  }

  function selecionarTipo(tipo) {
    selectedTipo = tipo;
    document.querySelectorAll('.tipo-btn').forEach(b => b.classList.remove('selected'));
    document.querySelector(`[data-tipo="${tipo}"]`)?.classList.add('selected');
    atualizarCamposDados();
  }

  function atualizarCamposDados() {
    const isDuo = selectedTipo === 'bar';
    document.getElementById('wrapper-dataset2').style.display = isDuo ? 'block' : 'none';
  }

  function abrirModal() {
    selectedTipo = 'bar';
    document.querySelectorAll('.tipo-btn').forEach(b => b.classList.remove('selected'));
    document.querySelector('[data-tipo="bar"]')?.classList.add('selected');
    document.getElementById('form-grafico').reset();
    atualizarCamposDados();
    document.getElementById('modal-grafico').classList.add('open');
  }

  function fecharModal() {
    document.getElementById('modal-grafico').classList.remove('open');
  }

  function salvar() {
    const titulo = document.getElementById('g-titulo').value.trim();
    const labelsRaw = document.getElementById('g-labels').value.trim();
    const d1Raw  = document.getElementById('g-data1').value.trim();
    const d1Nome = document.getElementById('g-data1-nome').value.trim() || 'Série 1';
    const d2Raw  = document.getElementById('g-data2').value.trim();
    const d2Nome = document.getElementById('g-data2-nome').value.trim() || 'Série 2';

    if (!titulo || !labelsRaw || !d1Raw) {
      showToast('Preencha os campos obrigatórios.', 'ti-alert-circle');
      return;
    }

    const labels = labelsRaw.split(',').map(s => s.trim());
    const d1 = d1Raw.split(',').map(Number);
    const datasets = [{ label: d1Nome, data: d1, color: GOLD }];

    const isDuo = selectedTipo === 'bar';
    if (isDuo && d2Raw) {
      datasets.push({ label: d2Nome, data: d2Raw.split(',').map(Number), color: NAVY });
    }

    graficos.push({ id: nextId++, titulo, tipo: selectedTipo, labels, datasets });
    fecharModal();
    render();
    showToast('Gráfico adicionado!', 'ti-check');
  }

  function excluir(id) {
    if (!confirm('Excluir este gráfico?')) return;
    if (chartInstances[id]) { chartInstances[id].destroy(); delete chartInstances[id]; }
    graficos = graficos.filter(g => g.id !== id);
    render();
    showToast('Gráfico excluído.', 'ti-trash');
  }

  return { init: render, render, abrirModal, fecharModal, salvar, excluir, selecionarTipo };
})();
