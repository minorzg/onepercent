<?php
session_start();
require_once 'config.php';

$isLoggedIn = isset($_SESSION['uid']);
$uid = $isLoggedIn ? (int)$_SESSION['uid'] : 0;
$user   = ['name' => '', 'is_premium' => 0, 'is_vip' => 0];
$plan   = ['plan' => 'free', 'max_goals' => FREE_GOALS_PER_DAY, 'label' => 'Free'];

if ($isLoggedIn) {
    checkLogin();
    $stmt = getDB()->prepare("SELECT * FROM users WHERE id=?");
    $stmt->execute([$uid]);
    $user = $stmt->fetch();
    $plan = getUserPlan($user);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=yes, viewport-fit=cover">
<link rel="icon" href="logo.png">
<title>1% — Pricing & Plans</title>
<?php echo getSharedCSS() ?>
<style>
.pricing-hero{text-align:center;padding:40px 0 32px;}
.ph-badge{display:inline-flex;align-items:center;gap:6px;padding:6px 16px;border-radius:100px;background:var(--green-dim);border:1px solid var(--green-border);color:var(--green);font-size:.78rem;font-weight:600;margin-bottom:16px;}
.ph-title{font-size:2.2rem;font-weight:800;letter-spacing:-.5px;margin-bottom:10px;}
.ph-sub{color:var(--text2);font-size:1rem;max-width:560px;margin:0 auto;}

.plans-grid{display:grid;grid-template-columns:repeat(3,1fr);gap:16px;margin:32px 0;}
@media(max-width:900px){.plans-grid{grid-template-columns:1fr;max-width:420px;margin:32px auto;}}
@media(max-width:600px){.plans-grid{gap:12px;}}

.plan-card{background:var(--card);border:1px solid var(--card-b);border-radius:var(--rl);padding:24px;position:relative;transition:all .2s;}
.plan-card:hover{transform:translateY(-4px);border-color:var(--green-border);box-shadow:0 16px 40px rgba(0,0,0,.2);}
.plan-card.popular{border-color:var(--green-border);background:linear-gradient(160deg,rgba(34,197,94,.07),var(--card));}
.popular-badge{position:absolute;top:-12px;left:50%;transform:translateX(-50%);background:var(--green);color:#000;font-size:.68rem;font-weight:800;padding:4px 14px;border-radius:100px;white-space:nowrap;}
.plan-icon{width:44px;height:44px;border-radius:12px;display:flex;align-items:center;justify-content:center;font-size:1.2rem;margin-bottom:14px;}
.plan-name{font-weight:800;font-size:1.1rem;margin-bottom:6px;}
.plan-desc{font-size:.8rem;color:var(--text2);margin-bottom:18px;line-height:1.5;}
.plan-price{margin-bottom:20px;}
.plan-amount{font-size:2.4rem;font-weight:900;letter-spacing:-1px;line-height:1;}
.plan-amount.g{color:var(--green);}
.plan-period{font-size:.78rem;color:var(--text2);}
.plan-features{list-style:none;margin-bottom:22px;display:flex;flex-direction:column;gap:9px;}
.plan-feat{display:flex;align-items:flex-start;gap:8px;font-size:.82rem;}
.plan-feat i.ok{color:var(--green);}
.plan-feat i.no{color:var(--text3);}
.plan-feat span{color:var(--text2);}
.plan-feat.locked span{color:var(--text3);}

.plan-btn{width:100%;padding:12px;border-radius:var(--r);font-weight:700;font-size:.88rem;cursor:pointer;transition:all .2s;display:flex;align-items:center;justify-content:center;gap:7px;border:none;font-family:'Inter',sans-serif;}
.plan-btn.primary{background:var(--green);color:#000;box-shadow:0 0 16px var(--green-glow);}
.plan-btn.primary:hover{transform:translateY(-1px);box-shadow:0 0 26px var(--green-glow);}
.plan-btn.outline{background:transparent;color:var(--text);border:1px solid var(--card-b);}
.plan-btn.outline:hover{border-color:var(--green-border);color:var(--green);}
.plan-btn.current{background:var(--card-h);color:var(--text3);cursor:default;}

.guarantee{text-align:center;padding:20px;background:var(--card);border:1px solid var(--card-b);border-radius:var(--rl);margin-top:8px;font-size:.82rem;color:var(--text2);}
.guarantee i{color:var(--green);margin-right:6px;}

.crypto-info{background:linear-gradient(135deg,rgba(34,197,94,.07),rgba(34,197,94,.02));border:1px solid var(--green-border);border-radius:var(--rl);padding:20px;margin-top:20px;display:flex;align-items:flex-start;gap:14px;}
.crypto-ico{width:40px;height:40px;border-radius:10px;background:var(--green-dim);display:flex;align-items:center;justify-content:center;color:var(--green);font-size:1rem;flex-shrink:0;}
.crypto-title{font-weight:700;margin-bottom:4px;}
.crypto-desc{font-size:.8rem;color:var(--text2);line-height:1.5;}
.crypto-accepted{display:flex;gap:8px;margin-top:10px;flex-wrap:wrap;}
.crypto-tag{padding:3px 10px;border-radius:20px;border:1px solid var(--green-border);background:var(--green-dim);font-size:.68rem;color:var(--green);font-weight:600;}

/* Payment modal */
.pay-methods{display:grid;grid-template-columns:1fr 1fr;gap:8px;margin-bottom:14px;}
.pay-method{padding:12px;border:1px solid var(--card-b);border-radius:var(--r);cursor:pointer;text-align:center;transition:all .15s;background:var(--bg);}
.pay-method:hover,.pay-method.selected{border-color:var(--green-border);background:var(--green-dim);color:var(--green);}
.pay-method i{display:block;font-size:1.3rem;margin-bottom:4px;}
.pay-method span{font-size:.72rem;font-weight:600;}

/* Mobile fix */
@media(max-width:768px){
    .topbar{
        display:flex !important;
        min-height:calc(60px + env(safe-area-inset-top));
        padding-top:calc(10px + env(safe-area-inset-top));
    }
    .ham{
        display:flex !important;
    }
    .page{
        padding:16px 14px calc(64px + env(safe-area-inset-bottom) + 24px);
    }
}
</style>
</head>
<body>
<div class="app">

<?php if ($isLoggedIn): ?>
    <?php echo getNav('pricing', $user, $plan) ?>
    
    <!-- Topbar for mobile -->
    <div class="topbar">
        <button class="ham" id="ham" onclick="toggleSidebar()" aria-label="Menu">
            <span></span>
            <span></span>
            <span></span>
        </button>
        <span class="topbar-logo">1%</span>
        <div style="display:flex;gap:8px">
            <button class="top-icon-btn" onclick="toggleTheme()" aria-label="Toggle theme">
                <i class="fas fa-circle-half-stroke"></i>
            </button>
        </div>
    </div>
    
    <div class="main-content">
        <div class="page">
<?php else: ?>
    <!-- Navigation for non-logged in users -->
    <div style="width:100%;">
        <nav style="position:sticky;top:0;z-index:100;background:rgba(10,10,10,.95);backdrop-filter:blur(20px);border-bottom:1px solid rgba(255,255,255,.07);padding:16px 32px;display:flex;align-items:center;justify-content:space-between;">
            <a href="index.php" style="font-size:1.4rem;font-weight:900;color:#22c55e;text-decoration:none;">1%</a>
            <a href="login.php" style="padding:9px 20px;background:#22c55e;color:#000;border-radius:100px;font-weight:700;font-size:.85rem;text-decoration:none;"><i class="fas fa-sign-in-alt"></i> Sign in</a>
        </nav>
        <div style="max-width:900px;margin:0 auto;padding:0 20px 60px;">
<?php endif; ?>

<!-- Hero -->
<div class="pricing-hero">
    <div class="ph-badge"><i class="fas fa-crown"></i> Simple & transparent pricing</div>
    <h1 class="ph-title">Choose your plan</h1>
    <p class="ph-sub">Start for free. Upgrade to Pro or VIP when you're ready to go further.</p>
</div>

<!-- Plans -->
<div class="plans-grid">

    <!-- FREE -->
    <div class="plan-card">
        <div class="plan-icon" style="background:rgba(255,255,255,.05);">🌱</div>
        <div class="plan-name">Free</div>
        <div class="plan-desc">Start building your discipline.</div>
        <div class="plan-price">
            <div class="plan-amount">$0</div>
            <div class="plan-period">Forever</div>
        </div>
        <ul class="plan-features">
            <li class="plan-feat"><i class="fas fa-check ok"></i><span><?php echo FREE_GOALS_PER_DAY ?> activities per day</span></li>
            <li class="plan-feat"><i class="fas fa-check ok"></i><span>Basic dashboard</span></li>
            <li class="plan-feat"><i class="fas fa-check ok"></i><span>Streak & progress</span></li>
            <li class="plan-feat locked"><i class="fas fa-times no"></i><span>Advanced analytics</span></li>
            <li class="plan-feat locked"><i class="fas fa-times no"></i><span>Calendar planning</span></li>
            <li class="plan-feat locked"><i class="fas fa-times no"></i><span>Unlimited activities</span></li>
        </ul>
        <?php if (!$isLoggedIn): ?>
        <a href="login.php" class="plan-btn outline"><i class="fas fa-arrow-right"></i> Get started</a>
        <?php elseif ($plan['plan'] === 'free'): ?>
        <button class="plan-btn current" disabled>✓ Current plan</button>
        <?php else: ?>
        <button class="plan-btn outline" disabled>Base plan</button>
        <?php endif; ?>
    </div>

    <!-- PRO -->
    <div class="plan-card popular">
        <div class="popular-badge">⚡ Most popular</div>
        <div class="plan-icon" style="background:rgba(34,197,94,.15);">🚀</div>
        <div class="plan-name">Pro</div>
        <div class="plan-desc">For people serious about results.</div>
        <div class="plan-price">
            <div class="plan-amount g">$<?php echo number_format(PRO_PRICE, 2) ?></div>
            <div class="plan-period">per month · in crypto</div>
        </div>
        <ul class="plan-features">
            <li class="plan-feat"><i class="fas fa-check ok"></i><span><?php echo PRO_GOALS_PER_DAY ?> activities per day</span></li>
            <li class="plan-feat"><i class="fas fa-check ok"></i><span>Complete analytics</span></li>
            <li class="plan-feat"><i class="fas fa-check ok"></i><span>Monthly calendar</span></li>
            <li class="plan-feat"><i class="fas fa-check ok"></i><span>Advanced streak</span></li>
            <li class="plan-feat"><i class="fas fa-check ok"></i><span>Data export</span></li>
            <li class="plan-feat locked"><i class="fas fa-times no"></i><span>Unlimited activities</span></li>
        </ul>
        <?php if (!$isLoggedIn): ?>
        <a href="login.php" class="plan-btn primary"><i class="fas fa-rocket"></i> Get started — $<?php echo number_format(PRO_PRICE, 2) ?></a>
        <?php elseif ($plan['plan'] === 'pro'): ?>
        <button class="plan-btn current" disabled>✓ Current plan</button>
        <?php else: ?>
        <button class="plan-btn primary" onclick="openPayModal('pro')"><i class="fas fa-rocket"></i> Upgrade to Pro — $<?php echo number_format(PRO_PRICE, 2) ?></button>
        <?php endif; ?>
    </div>

    <!-- VIP -->
    <div class="plan-card">
        <div class="plan-icon" style="background:rgba(245,158,11,.1);">👑</div>
        <div class="plan-name">VIP</div>
        <div class="plan-desc">Everything, without limits. For the most ambitious.</div>
        <div class="plan-price">
            <div class="plan-amount" style="color:var(--amber);">$<?php echo number_format(VIP_PRICE, 2) ?></div>
            <div class="plan-period">per month · in crypto</div>
        </div>
        <ul class="plan-features">
            <li class="plan-feat"><i class="fas fa-check ok"></i><span><strong>Unlimited</strong> activities</span></li>
            <li class="plan-feat"><i class="fas fa-check ok"></i><span>Everything in Pro</span></li>
            <li class="plan-feat"><i class="fas fa-check ok"></i><span>Priority support</span></li>
            <li class="plan-feat"><i class="fas fa-check ok"></i><span>Early access to new features</span></li>
            <li class="plan-feat"><i class="fas fa-check ok"></i><span>VIP badge on profile</span></li>
            <li class="plan-feat"><i class="fas fa-check ok"></i><span>Advanced multi-day goals</span></li>
        </ul>
        <?php if (!$isLoggedIn): ?>
        <a href="login.php" class="plan-btn outline" style="border-color:rgba(245,158,11,.3);color:var(--amber);"><i class="fas fa-crown"></i> Get VIP</a>
        <?php elseif ($plan['plan'] === 'vip'): ?>
        <button class="plan-btn current" disabled>✓ Current plan</button>
        <?php else: ?>
        <button class="plan-btn outline" style="border-color:rgba(245,158,11,.3);color:var(--amber);" onclick="openPayModal('vip')"><i class="fas fa-crown"></i> Upgrade to VIP — $<?php echo number_format(VIP_PRICE, 2) ?></button>
        <?php endif; ?>
    </div>

</div>

<!-- Crypto info -->
<div class="crypto-info">
    <div class="crypto-ico"><i class="fab fa-bitcoin"></i></div>
    <div>
        <div class="crypto-title">100% secure crypto payments</div>
        <div class="crypto-desc">We use NOWPayments — you pay by card or crypto, we receive crypto. No bank required. Secure & instant transaction.</div>
        <div class="crypto-accepted">
            <span class="crypto-tag">₿ Bitcoin</span>
            <span class="crypto-tag">Ξ Ethereum</span>
            <span class="crypto-tag">◎ USDT</span>
            <span class="crypto-tag">◈ USDC</span>
            <span class="crypto-tag">💳 Credit card</span>
        </div>
    </div>
</div>

<!-- Guarantee -->
<div class="guarantee">
    <i class="fas fa-shield-alt"></i>
    Satisfaction guaranteed — if you're not happy within 7 days, contact us for a refund.
</div>

<?php if ($isLoggedIn): ?>
        </div><!-- page -->
        <?php echo getBottomNav('pricing') ?>
    </div><!-- main-content -->
<?php else: ?>
    </div>
<?php endif; ?>

</div><!-- app -->

<!-- PAYMENT MODAL -->
<div class="modal-bg" id="payModal">
<div class="modal-sheet" style="max-width:440px;">
    <div class="modal-handle"></div>
    <div class="modal-title">Complete subscription <div class="modal-close" onclick="closePayModal()"><i class="fas fa-times"></i></div></div>

    <div id="payContent">
        <p style="font-size:.85rem;color:var(--text2);margin-bottom:16px;">Choose your payment method:</p>
        <div class="pay-methods">
            <div class="pay-method selected" id="mCard" onclick="selectMethod('card')">
                <i class="fas fa-credit-card"></i><span>Credit card</span>
            </div>
            <div class="pay-method" id="mCrypto" onclick="selectMethod('crypto')">
                <i class="fab fa-bitcoin"></i><span>Crypto</span>
            </div>
        </div>

        <div id="payInfo" style="background:var(--bg);border:1px solid var(--card-b);border-radius:var(--r);padding:14px;margin-bottom:14px;font-size:.82rem;color:var(--text2);line-height:1.6;">
            💳 Card payment accepted via OxaPay. SSL secured. No card data stored on our servers.
        </div>

        <button class="plan-btn primary" id="payBtn" onclick="startPayment()" style="width:100%;">
            <i class="fas fa-lock"></i> Pay now
        </button>
        <p style="text-align:center;font-size:.73rem;color:var(--text3);margin-top:10px;"><i class="fas fa-shield-alt" style="color:var(--green);"></i> Secure payment · 7-day money-back guarantee</p>
    </div>
</div>
</div>

<?php echo getSharedJS() ?>
<script>
let selectedPlan = 'pro';
let selectedMethod = 'card';

function openPayModal(plan){
    selectedPlan = plan;
    document.getElementById('payModal').classList.add('open');
    document.body.style.overflow='hidden';
    document.getElementById('payBtn').textContent = '🔒 Pay $' + (plan === 'pro' ? '<?php echo number_format(PRO_PRICE,2) ?>' : '<?php echo number_format(VIP_PRICE,2) ?>');
}

function closePayModal(){
    document.getElementById('payModal').classList.remove('open');
    document.body.style.overflow='';
}

function selectMethod(m){
    selectedMethod = m;
    document.getElementById('mCard').classList.toggle('selected', m==='card');
    document.getElementById('mCrypto').classList.toggle('selected', m==='crypto');
    const info = document.getElementById('payInfo');
    info.innerHTML = m === 'card'
        ? '💳 Card payment accepted via OxaPay. SSL secured. No card data stored.'
        : '₿ Pay with Bitcoin, Ethereum, USDT, USDC or any crypto supported by NOWPayments.';
}

function startPayment(){
    const btn = document.getElementById('payBtn');
    btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Creating payment...';
    btn.disabled = true;

    fetch('payment/create.php', {
        method: 'POST',
        headers:{'Content-Type':'application/json'},
        body: JSON.stringify({plan: selectedPlan, method: selectedMethod})
    })
    .then(r => r.json())
    .then(data => {
        if(data.payment_url){
            window.location.href = data.payment_url;
        } else {
            alert(data.error || 'Error. Please try again.');
            btn.innerHTML = '<i class="fas fa-lock"></i> Pay now';
            btn.disabled = false;
        }
    })
    .catch(()=>{
        alert('Network error. Please try again.');
        btn.innerHTML = '<i class="fas fa-lock"></i> Pay now';
        btn.disabled = false;
    });
}

document.getElementById('payModal').addEventListener('click', e=>{
    if(e.target===document.getElementById('payModal')) closePayModal();
});
</script>
</body>
</html>