(() => {
  const tableBody = document.querySelector('#suppliers-table tbody');
  if (!tableBody) return;

  const loadSuppliers = async () => {
    try {
      const { data } = await App.request('GET', '/suppliers');
      tableBody.innerHTML = '';
      data.forEach((supplier) => {
        const tr = document.createElement('tr');
        tr.innerHTML = `
          <td>${supplier.supplier_code}</td>
          <td>${supplier.supplier_name}</td>
          <td>${supplier.phone || '-'}</td>
          <td>${supplier.email || '-'}</td>
          <td>${supplier.rating || '0.0'}</td>
        `;
        tableBody.appendChild(tr);
      });
    } catch (error) {
      tableBody.innerHTML = `<tr><td colspan="5">${error.message || '공급업체를 불러올 수 없습니다.'}</td></tr>`;
    }
  };

  loadSuppliers();
})();
