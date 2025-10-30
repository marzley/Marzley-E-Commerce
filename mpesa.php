<?php>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="utf-8" />
  <meta http-equiv="X-UA-Compatible" content="IE=edge" />
  <meta name="viewport" content="width=device-width,initial-scale=1" />
  <title>Marzley Tech Solutions — Checkout</title>

  <!-- Bootstrap 5 -->
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">

  <!-- Font Awesome (icons) -->
  <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" rel="stylesheet">

  <!-- PayPal JS SDK (sandbox). Replace client-id=sb with your sandbox/live client id as needed -->
  <script src="https://www.paypal.com/sdk/js?client-id=sb&currency=USD" defer></script>

  <style>
    :root{
      --accent:#2f8f3f;
      --card:#ffffff;
      --muted:#8b96a3;
      --bg:#eef3f9;
    }
    html,body{height:100%;}
    body{
      margin:0;
      background: radial-gradient(circle at 10% 10%, #f7fbff 0%, var(--bg) 40%);
      font-family: Inter, system-ui, -apple-system, "Segoe UI", Roboto, "Helvetica Neue", Arial;
      display:flex;
      align-items:center;
      justify-content:center;
      padding:32px;
      -webkit-font-smoothing:antialiased;
    }

    .app-wrap{
      width:100%;
      max-width:520px;
    }

    .brand {
      text-align:center;
      margin-bottom:12px;
    }
    .brand h1{
      font-size:20px;
      margin:0;
      letter-spacing:0.2px;
      color:#21334a;
      font-weight:800;
    }
    .brand p{
      margin:6px 0 0;
      color:var(--muted);
      font-size:13px;
    }

    .card-pay{
      border-radius:16px;
      background:var(--card);
      box-shadow:0 10px 40px rgba(35,50,74,0.08);
      padding:20px;
      overflow:hidden;
    }

    .payment-tabs{
      display:flex;
      gap:10px;
      justify-content:center;
      margin-bottom:14px;
    }
    .pay-item{
      width:120px;
      text-align:center;
      padding:10px 8px;
      border-radius:12px;
      background:#f6f8fb;
      color:var(--muted);
      font-weight:700;
      transition:all .28s ease;
      cursor:pointer;
      display:flex;
      flex-direction:column;
      align-items:center;
      gap:8px;
      user-select:none;
    }
    .pay-item:hover{ transform:translateY(-4px); box-shadow:0 8px 20px rgba(0,0,0,0.04); }
    .pay-item.active{
      background:linear-gradient(135deg,var(--accent),#3fb55a);
      color:white;
      transform:translateY(-6px);
      box-shadow:0 10px 30px rgba(47,143,63,0.14);
    }

    .pay-icon{
      width:52px;height:52px;border-radius:12px;
      display:flex;align-items:center;justify-content:center;
      background:rgba(0,0,0,0.04);
      transition:transform .28s ease;
      font-size:20px;
    }
    .pay-item.active .pay-icon{ transform:scale(1.12); background:rgba(255,255,255,0.12);}

    /* subtle floating animation for MPESA logo */
    .mpesa-logo{
      width:86px;
      display:block;
      margin-right:12px;
      transition:transform .35s ease;
      will-change:transform;
      animation:floaty 3.6s ease-in-out infinite;
    }
    @keyframes floaty{
      0%{ transform: translateY(0px); }
      50%{ transform: translateY(-6px) rotate(-1deg); }
      100%{ transform: translateY(0px); }
    }

    .form-control:focus{
      box-shadow:0 0 0 0.2rem rgba(47,143,63,0.18);
      border-color:var(--accent);
    }

    .btn-pay{
      background:linear-gradient(90deg,var(--accent),#3fb55a);
      border:none;color:white;font-weight:700;
      box-shadow:0 8px 20px rgba(47,143,63,0.16);
      transition:transform .18s ease, box-shadow .18s ease;
    }
    .btn-pay:hover{ transform:translateY(-3px); box-shadow:0 12px 26px rgba(47,143,63,0.2); }

    /* small helper */
    .muted{color:var(--muted);font-size:13px}

    /* PayPal section styling */
    #paypal-container{
      margin-top:8px;
    }

    /* responsive */
    @media (max-width:520px){
      .mpesa-logo{width:72px}
      .pay-item{width:calc(33.333% - 8px);}
    }
  </style>
</head>
<body>
  <div class="app-wrap">
    <div class="brand">
      <h1>Marzley Tech Solutions</h1>
      <p class="muted">Checkout — select a payment method</p>
    </div>

    <div class="card-pay">
      <div class="d-flex align-items-center mb-3">
        <img src="./images/1200px-M-PESA_LOGO-01.svg.png" alt="M-PESA" class="mpesa-logo">
        <div>
          <h5 class="mb-0">Enter amount & phone</h5>
          <div class="muted">Choose PayPal, M-Pesa or Card. PayPal uses sandbox by default (client-id=sb).</div>
        </div>
      </div>

      <div class="payment-tabs mb-3" role="tablist" aria-label="payment types">
        <div class="pay-item active" data-method="mpesa" aria-pressed="true">
          <div class="pay-icon"><i class="fas fa-mobile-screen-button fa-lg"></i></div>
          <small>Mpesa</small>
        </div>
        <div class="pay-item" data-method="paypal" aria-pressed="false">
          <div class="pay-icon"><i class="fab fa-paypal fa-lg"></i></div>
          <small>PayPal</small>
        </div>
        <div class="pay-item" data-method="card" aria-pressed="false">
          <div class="pay-icon"><i class="fas fa-credit-card fa-lg"></i></div>
          <small>Card</small>
        </div>
      </div>

      <form id="checkout-form" action="./stk_initiate.php" method="POST" class="row g-3">
        <div class="col-12 col-md-6">
          <label class="form-label small">Amount (KES)</label>
          <!-- amount is loaded from the cart in localStorage and includes shipping -->
          <input name="amount" id="amount" required inputmode="decimal" pattern="[0-9.]*"
                 class="form-control form-control-lg"
                 placeholder="Amount will load from cart"
                 readonly>
          <div class="muted small mt-1" id="amount-hint">Loading order summary from cart...</div>

          <!-- Visible order summary (items, shipping, grand total) -->
          <div id="order-summary" class="mt-2" style="font-size:0.95rem;color:#444"></div>

          <script>
            (function(){
              try {
                var amountInput = document.getElementById('amount');
                var hint = document.getElementById('amount-hint');
                var summaryEl = document.getElementById('order-summary');
                var payBtn = document.querySelector('#form-actions button');

                var cart = JSON.parse(localStorage.getItem('cart') || '[]');

                // Determine shipping preference/cost if stored in localStorage (fallback to Standard KES 5)
                var shippingPref = localStorage.getItem('shipping') || '';
                try {
                  var checkout = JSON.parse(localStorage.getItem('checkout') || 'null');
                  if (checkout && checkout.shipping) shippingPref = checkout.shipping;
                } catch(e){ /* ignore parse errors */ }

                var shippingCost = 5; // default Standard
                if (shippingPref === 'Express') shippingCost = 12;
                else if (/pickup/i.test(shippingPref)) shippingCost = 0;

                if (Array.isArray(cart) && cart.length > 0) {
                  var itemsTotal = 0;
                  cart.forEach(function(item){
                    var price = parseFloat(item.price) || 0;
                    var qty = parseInt(item.quantity, 10) || 1;
                    itemsTotal += price * qty;
                  });

                  var grand = itemsTotal + shippingCost;

                  // populate fields and summary
                  amountInput.value = grand.toFixed(2);
                  hint.textContent = 'Order summary loaded from your cart.';
                  summaryEl.innerHTML =
                    '<div><strong>Items Total:</strong> KES ' + itemsTotal.toFixed(2) + '</div>' +
                    '<div><strong>Shipping (' + (shippingPref || 'Standard') + '):</strong> KES ' + shippingCost.toFixed(2) + '</div>' +
                    '<div style="margin-top:6px;font-weight:700;color:#e67e22;"><strong>Grand Total:</strong> KES ' + grand.toFixed(2) + '</div>';

                  if (payBtn) payBtn.disabled = false;
                } else {
                  amountInput.value = '';
                  amountInput.placeholder = 'No items in cart';
                  hint.textContent = 'Your cart is empty. Add items to cart first.';
                  summaryEl.innerHTML = '';
                  if (payBtn) payBtn.disabled = true;
                }
              } catch (err) {
                console.error('Error loading order summary', err);
                var hintEl = document.getElementById('amount-hint');
                if (hintEl) hintEl.textContent = 'Unable to load order summary from cart.';
              }
            })();
          </script>
        </div>

        <div class="col-12 col-md-6">
          <label class="form-label small">Phone Number</label>
          <input name="phone" id="phone" required inputmode="tel" class="form-control form-control-lg" placeholder="Enter Phone Number e.g. 2547xxxxxxx">
        </div>

        <div class="col-12 d-grid" id="form-actions">
          <button type="submit" name="submit" value="submit" class="btn btn-pay btn-lg">
            <i class="fas fa-bolt me-2"></i> Pay with M-Pesa
          </button>
        </div>
      </form>

      <!-- PayPal buttons container (hidden by default) -->
      <div id="paypal-container" style="display:none;"></div>

      <div class="mt-3 text-center muted small">
        Test mode — payments in sandbox will be reversed automatically by midnight.
      </div>
    </div>
  </div>

  <!-- Bootstrap bundle -->
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>

  <script>
    // Tab interaction and PayPal integration
    const payItems = document.querySelectorAll('.pay-item');
    const form = document.getElementById('checkout-form');
    const formActions = document.getElementById('form-actions');
    const paypalContainer = document.getElementById('paypal-container');
    const amountInput = document.getElementById('amount');

    let paypalRenderedFor = null; // remember amount for which PayPal was rendered

    function setActiveMethod(method, item) {
      payItems.forEach(i=>{
        i.classList.remove('active');
        i.setAttribute('aria-pressed','false');
      });
      if(item) { item.classList.add('active'); item.setAttribute('aria-pressed','true'); }

      if(method === 'paypal') {
        form.style.display = 'none';
        paypalContainer.style.display = 'block';
        renderPayPalButtons();
      } else {
        // show form (mpesa or card)
        paypalContainer.style.display = 'none';
        form.style.display = 'block';

        // update submit button label for mpesa/card
        const btnHtml = method === 'mpesa'
          ? '<i class="fas fa-bolt me-2"></i> Pay with M-Pesa'
          : '<i class="fas fa-credit-card me-2"></i> Pay with Card';
        formActions.innerHTML = '<button type="submit" name="submit" value="submit" class="btn btn-pay btn-lg">' + btnHtml + '</button>';
      }
    }

    payItems.forEach(item=>{
      item.addEventListener('click', ()=> {
        const method = item.dataset.method || 'mpesa';
        setActiveMethod(method, item);
      });
    });

    // Render PayPal buttons using the amount in the input.
    function renderPayPalButtons(){
      // ensure PayPal SDK loaded
      if (typeof paypal === 'undefined') {
        paypalContainer.innerHTML = '<div class="muted small">PayPal SDK not loaded. Check network or replace client-id.</div>';
        return;
      }

      const raw = (amountInput && amountInput.value) ? amountInput.value.trim() : '';
      const amount = (raw && !isNaN(raw) && Number(raw) > 0) ? Number(raw).toFixed(2) : '1.00';

      // if rendered already for the same amount, skip re-render
      if (paypalRenderedFor === amount) return;
      paypalRenderedFor = amount;
      paypalContainer.innerHTML = ''; // clear existing

      paypal.Buttons({
        style: { layout: 'horizontal', color: 'gold', shape: 'pill', label: 'paypal' },
        createOrder: (data, actions) => {
          return actions.order.create({
            purchase_units: [{
              amount: { value: amount },
              description: 'Marzley Tech Solutions - Order'
            }]
          });
        },
        onApprove: (data, actions) => {
          return actions.order.capture().then(function(details) {
            // success UI
            paypalContainer.innerHTML = '<div class="alert alert-success">Payment completed by ' + (details.payer.name?.given_name || '') + '. Transaction ID: ' + (details.id || '') + '</div>';
            // optionally send capture details to your server for record (uncomment and adapt)
            /*
            fetch('/paypal_capture.php', {
              method: 'POST',
              headers: {'Content-Type':'application/json'},
              body: JSON.stringify({ orderID: data.orderID, details })
            });
            */
          });
        },
        onError: (err) => {
          paypalContainer.innerHTML = '<div class="alert alert-danger">Payment error: ' + String(err) + '</div>';
        }
      }).render('#paypal-container');
    }

    // re-render PayPal when amount changes
    amountInput.addEventListener('input', ()=> {
      // if the PayPal tab is active, re-render
      if (document.querySelector('.pay-item.active')?.dataset.method === 'paypal') {
        // force re-render for new amount
        paypalRenderedFor = null;
        renderPayPalButtons();
      }
    });

    // initialize (ensure default active is mpesa)
    setActiveMethod('mpesa', document.querySelector('.pay-item[data-method="mpesa"]'));
  </script>
</body>
</html>
