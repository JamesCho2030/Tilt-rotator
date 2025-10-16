(() => {
  const form = document.querySelector('#purchase-order-form');
  if (!form) return;

  const itemsBody = form.querySelector('tbody');
  const addRowBtn = document.querySelector('#add-line');
  const totalField = document.querySelector('#total-amount');

  const recalc = () => {
    let total = 0;
    itemsBody.querySelectorAll('tr').forEach((row) => {
      const qty = parseFloat(row.querySelector('.qty').value || '0');
      const price = parseFloat(row.querySelector('.price').value || '0');
      const amount = qty * price;
      row.querySelector('.amount').textContent = App.formatCurrency(amount);
      total += amount;
    });
    totalField.textContent = App.formatCurrency(total);
  };

  const addRow = () => {
    const tr = document.createElement('tr');
    tr.innerHTML = `
      <td><input type="number" class="product-id" min="1" required></td>
      <td><input type="number" class="qty" min="1" value="1" required></td>
      <td><input type="number" class="price" min="0" value="0" required></td>
      <td class="amount">${App.formatCurrency(0)}</td>
      <td><button type="button" class="button remove">삭제</button></td>
    `;
    itemsBody.appendChild(tr);
    tr.querySelectorAll('input').forEach((input) => input.addEventListener('input', recalc));
    tr.querySelector('.remove').addEventListener('click', () => {
      tr.remove();
      recalc();
    });
  };

  addRowBtn.addEventListener('click', addRow);
  addRow();

  form.addEventListener('submit', async (event) => {
    event.preventDefault();
    const supplierId = form.querySelector('select[name="supplier_id"]').value;
    const orderDate = form.querySelector('input[name="order_date"]').value;
    const requestedDate = form.querySelector('input[name="requested_delivery_date"]').value;
    const notes = form.querySelector('textarea[name="notes"]').value;

    const items = Array.from(itemsBody.querySelectorAll('tr')).map((row) => ({
      product_id: parseInt(row.querySelector('.product-id').value, 10),
      quantity: parseFloat(row.querySelector('.qty').value),
      unit_price: parseFloat(row.querySelector('.price').value),
    }));

    try {
      const numberResponse = await App.request('GET', '/purchase-orders/generate-number');
      await App.request('POST', '/purchase-orders', {
        po_number: numberResponse.po_number,
        supplier_id: supplierId,
        order_date: orderDate,
        requested_delivery_date: requestedDate,
        notes,
        items,
      });
      alert('발주서가 등록되었습니다.');
      form.reset();
      itemsBody.innerHTML = '';
      addRow();
      recalc();
    } catch (error) {
      alert(error.message || '발주서 등록에 실패했습니다.');
    }
  });
})();
