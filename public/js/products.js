(() => {
  const tableBody = document.querySelector('#products-table tbody');
  if (!tableBody) return;

  const renderProducts = (items) => {
    tableBody.innerHTML = '';
    items.forEach((product) => {
      const tr = document.createElement('tr');
      tr.innerHTML = `
        <td>${product.product_code}</td>
        <td>${product.product_name}</td>
        <td>${product.unit}</td>
        <td>${product.category || '-'}</td>
        <td>${product.safety_stock}</td>
        <td><span class="badge">${product.is_active ? '사용' : '중지'}</span></td>
      `;
      tableBody.appendChild(tr);
    });
  };

  const loadProducts = async () => {
    try {
      const { data } = await App.request('GET', '/products');
      renderProducts(data.items || []);
      document.querySelector('#products-total').textContent = data.total;
    } catch (error) {
      tableBody.innerHTML = `<tr><td colspan="6">${error.message || '제품을 불러올 수 없습니다.'}</td></tr>`;
    }
  };

  loadProducts();
})();
