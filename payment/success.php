<?php
require_once '../config.php';
$plan = isset($_GET['plan']) ? $_GET['plan'] : 'pro';
$label = $plan === 'vip' ? 'VIP 👑' : 'Pro 🚀';
?>
<!DOCTYPE html><html lang="fr"><head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1,viewport-fit=cover">
<title>1% — Paiement réussi</title>
<?php echo getSharedCSS(); ?>
</head><body>
<div style="min-height:100vh;display:flex;align-items:center;justify-content:center;padding:20px;">
  <div style="max-width:440px;width:100%;text-align:center">
    <div style="width:72px;height:72px;border-radius:50%;background:var(--green-dim);border:2px solid var(--green);display:flex;align-items:center;justify-content:center;margin:0 auto 20px;font-size:2rem;">✅</div>
    <h1 style="font-size:1.6rem;font-weight:800;margin-bottom:10px;">Paiement reçu !</h1>
    <p style="color:var(--text2);margin-bottom:6px;">Ton compte <strong style="color:var(--green)">1% <?php echo $label; ?></strong> est en cours d'activation.</p>
    <p style="font-size:.82rem;color:var(--text3);margin-bottom:28px;">Quelques minutes selon la blockchain.<br>Rafraîchis le dashboard si ton plan n'est pas encore visible.</p>
    <a href="../dashboard.php" class="btn-green" style="display:inline-flex;align-items:center;gap:8px;text-decoration:none"><i class="fas fa-home"></i> Dashboard</a>
  </div>
</div>
<?php echo getSharedJS(); ?>
</body></html>
