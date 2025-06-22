<script>
function openCartModal() {
  fetch('get_cart.php')
    .then(res => res.json())
    .then(data => {
      if (!data.success) {
        alert("Gagal ambil data keranjang");
        return;
      }

      const cartItemsDiv = document.getElementById("cartItems");
      cartItemsDiv.innerHTML = '';

      let total = 0;
      data.items.forEach(item => {
        const subtotal = item.quantity * item.price;
        total += subtotal;

        cartItemsDiv.innerHTML += `
          <div class="item">
            <div class="item-name">${item.menu_name}</div>
            <div class="qty">
              <button onclick="updateQty(${item.menu_id}, 'decrease')">-</button>
              <span>${item.quantity}</span>
              <button onclick="updateQty(${item.menu_id}, 'increase')">+</button>
            </div>
            <div class="price">Rp${Number(item.price).toLocaleString()}</div>
          </div>
        `;
      });

      document.getElementById("cartTotal").innerText = `Rp${total.toLocaleString()}`;
      document.getElementById("cartModal").style.display = "flex";
    })
    .catch(err => {
      console.error(err);
      alert("Error memuat data keranjang");
    });
}

function closeCartModal() {
  document.getElementById("cartModal").style.display = "none";
}

function updateQty(menu_id, action) {
  fetch('cart.php', {
    method: 'POST',
    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
    body: `menu_id=${menu_id}&action=${action}`
  }).then(() => {
    openCartModal(); // refresh tampilan keranjang
  });
}

function checkout() {
  window.location.href = 'cart.php';
}
</script>
