(() => {
  const kpiCards = document.querySelectorAll('[data-kpi]');
  const trendContainer = document.querySelector('#trend-chart');

  const renderKpis = (kpis) => {
    kpiCards.forEach((card) => {
      const key = card.getAttribute('data-kpi');
      card.querySelector('.value').textContent = key.includes('spend') ? App.formatCurrency(kpis[key]) : (kpis[key] || 0);
    });
  };

  const renderTrend = (trend) => {
    if (!trendContainer) return;
    trendContainer.innerHTML = '';
    trend.forEach((point) => {
      const bar = document.createElement('div');
      bar.classList.add('bar');
      bar.style.height = `${Math.min(100, (point.value / 1000000) * 10)}%`;
      bar.innerHTML = `<span>${point.label}</span><strong>${App.formatCurrency(point.value)}</strong>`;
      trendContainer.appendChild(bar);
    });
  };

  const loadDashboard = async () => {
    try {
      const { kpis, trend } = await App.request('GET', '/dashboard');
      renderKpis(kpis);
      renderTrend(trend || []);
    } catch (error) {
      console.error(error);
    }
  };

  loadDashboard();
})();
