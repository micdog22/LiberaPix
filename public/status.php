    <?php
    declare(strict_types=1);
    require_once __DIR__ . '/../src/bootstrap.php';

    use LiberaPIX\Config;

    $orderId = isset($_GET['order_id']) ? (int)$_GET['order_id'] : 0;
    $appUrl = Config::appUrl();
?><!DOCTYPE html>
<html lang="pt-BR">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Status do Pedido #<?php echo htmlspecialchars((string)$orderId); ?> — LiberaPIX</title>
  <link rel="stylesheet" href="assets/style.css">
</head>
<body>
  <main class="container">
    <header>
      <h1>Status do Pedido #<?php echo htmlspecialchars((string)$orderId); ?></h1>
      <p class="subtitle">Esta página atualiza automaticamente.</p>
    </header>

    <section class="card">
      <div id="status-box" class="status">Carregando status...</div>
      <div id="download-box" class="hidden">
        <p><strong>Pagamento aprovado!</strong></p>
        <a id="download-link" class="btn" href="#">Baixar arquivo</a>
        <p class="hint">O link expira na data mostrada abaixo.</p>
        <div id="expires"></div>
      </div>
    </section>

    <footer>
      <small>© <?php echo date('Y'); ?> Michael Douglas (MicDog).</small>
    </footer>
  </main>

  <script>
    const orderId = <?php echo json_encode($orderId); ?>;
    const statusBox = document.getElementById('status-box');
    const downloadBox = document.getElementById('download-box');
    const downloadLink = document.getElementById('download-link');
    const expires = document.getElementById('expires');

    async function poll() {
      try {
        const r = await fetch('./api/status.php?order_id=' + orderId, { cache: 'no-store' });
        const data = await r.json();
        if (!data.ok) {
          statusBox.innerText = 'Erro: ' + (data.error || 'desconhecido');
          return;
        }
        statusBox.innerText = 'Status: ' + data.status;
        if (data.status === 'paid' && data.download_token) {
          downloadBox.classList.remove('hidden');
          downloadLink.href = './download.php?token=' + encodeURIComponent(data.download_token);
          expires.innerText = 'Expira em: ' + (data.download_expires_at || '(desconhecido)');
          statusBox.innerText = 'Status: pago';
          clearInterval(timer);
        }
      } catch (e) {
        statusBox.innerText = 'Erro ao consultar status.';
      }
    }
    const timer = setInterval(poll, 3000);
    poll();
  </script>
</body>
</html>
