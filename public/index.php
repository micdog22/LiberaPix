    <?php
    declare(strict_types=1);
    require_once __DIR__ . '/../src/bootstrap.php';

    use LiberaPIX\Config;

    $appUrl = Config::appUrl();
?><!DOCTYPE html>
<html lang="pt-BR">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>LiberaPIX — Paywall PIX com liberação automática</title>
  <link rel="stylesheet" href="assets/style.css">
</head>
<body>
  <main class="container">
    <header>
      <h1>LiberaPIX</h1>
      <p class="subtitle">Paywall via PIX: pagou, liberou o download automaticamente.</p>
    </header>

    <section class="card">
      <h2>Produto Demo</h2>
      <p>Este é um produto digital de exemplo. Ao pagar <strong>R$ 9,90</strong> via PIX, você receberá um <em>token</em> que libera o download automaticamente.</p>
      <form id="checkout-form">
        <label>
          Seu e-mail (opcional):
          <input type="email" id="email" placeholder="voce@exemplo.com">
        </label>
        <button type="submit">Comprar com PIX — R$ 9,90</button>
      </form>
    </section>

    <section id="payment" class="card hidden">
      <h2>Pagamento PIX</h2>
      <div class="pix">
        <img id="qr" alt="QR Code PIX" />
        <div class="copy">
          <textarea id="qrtext" readonly></textarea>
          <button id="copybtn" type="button">Copiar código PIX</button>
        </div>
      </div>

      <p>Depois de pagar, <a id="goto-status" href="#">clique aqui para ver o status</a>.</p>
      <div class="hint">Este é um ambiente de demonstração. Configure sua conta do Mercado Pago no <code>.env</code>.</div>
    </section>

    <footer>
      <small>© <?php echo date('Y'); ?> Michael Douglas (MicDog). LiberaPIX é open-source (MIT).</small>
    </footer>
  </main>

  <script>
    const form = document.getElementById('checkout-form');
    const payment = document.getElementById('payment');
    const qrImg = document.getElementById('qr');
    const qrText = document.getElementById('qrtext');
    const copyBtn = document.getElementById('copybtn');
    const gotoStatus = document.getElementById('goto-status');

    let currentOrderId = null;

    form.addEventListener('submit', async (e) => {
      e.preventDefault();
      const email = document.getElementById('email').value.trim();

      const resp = await fetch('./api/checkout.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json'},
        body: JSON.stringify({
          product_id: 'produto-demo',
          amount_centavos: 990,
          email: email || null
        })
      });
      const data = await resp.json();
      if (!data.ok) {
        alert('Erro criando cobrança: ' + (data.error || 'desconhecido'));
        return;
      }
      currentOrderId = data.order_id;
      qrText.value = data.qr_code;
      qrImg.src = 'data:image/png;base64,' + data.qr_code_base64;
      payment.classList.remove('hidden');
      gotoStatus.href = './status.php?order_id=' + currentOrderId;
      gotoStatus.setAttribute('target', '_blank');
    });

    copyBtn.addEventListener('click', async () => {
      try {
        await navigator.clipboard.writeText(qrText.value);
        copyBtn.innerText = 'Copiado!';
        setTimeout(() => copyBtn.innerText = 'Copiar código PIX', 1500);
      } catch (e) {
        alert('Não foi possível copiar.');
      }
    });
  </script>
</body>
</html>
