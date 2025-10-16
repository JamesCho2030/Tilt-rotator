(() => {
  const tableBody = document.querySelector('#inventory-table tbody');
  if (!tableBody) return;

  const renderRow = (item) => {
    const tr = document.createElement('tr');
    tr.innerHTML = `
      <td>${item.product_name}</td>
      <td>${item.quantity}</td>
      <td>${item.safety_stock}</td>
      <td>${item.quantity <= item.safety_stock ? '<span class="badge">안전재고 이하</span>' : ''}</td>
    `;
    tableBody.appendChild(tr);
  };

  const loadInventory = async () => {
    try {
      const { inventory } = await App.request('GET', '/dashboard');
      tableBody.innerHTML = '';
      (inventory || []).forEach(renderRow);
    } catch (error) {
      tableBody.innerHTML = `<tr><td colspan="4">${error.message || '재고 정보를 불러올 수 없습니다.'}</td></tr>`;
    }
  };

  loadInventory();
})();
