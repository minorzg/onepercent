<?php session_start(); require_once 'config.php';
if(isset($_SESSION['uid'])){header("Location: dashboard.php");exit;}
?>
<!DOCTYPE html><html lang="en"><head>
<meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1,viewport-fit=cover">
<link rel="icon" href="logo.png"><title>1% — Build Discipline. Not Motivation.</title>
<link href="https://fonts.googleapis.com/css2?family=DM+Sans:ital,wght@0,300;0,400;0,500;0,600;0,700;0,800;0,900;1,400&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
<style>
*{margin:0;padding:0;box-sizing:border-box;}html{scroll-behavior:smooth;}
body{font-family:'DM Sans',sans-serif;background:#080c0a;color:#f0f3f1;overflow-x:hidden;}
body::before{content:'';position:fixed;inset:0;background:radial-gradient(ellipse 70% 50% at 20% 30%,rgba(34,197,94,.07),transparent),radial-gradient(ellipse 50% 60% at 80% 70%,rgba(34,197,94,.04),transparent);pointer-events:none;z-index:0;}

/* NAV */
.nav{position:fixed;top:0;inset:0 0 auto;height:60px;display:flex;align-items:center;justify-content:space-between;padding:0 5%;background:rgba(8,12,10,.88);backdrop-filter:blur(20px);border-bottom:1px solid rgba(34,197,94,.1);z-index:100;}
.nav-logo{display:flex;align-items:center;gap:9px;text-decoration:none;}
.nav-lb{width:36px;height:36px;background:#22c55e;border-radius:10px;display:flex;align-items:center;justify-content:center;font-weight:900;color:#000;font-size:.9rem;box-shadow:0 0 14px rgba(34,197,94,.3);}
.nav-n{font-weight:900;font-size:1.25rem;color:#22c55e;}
.nav-links{display:flex;align-items:center;gap:28px;}
.nav-link{color:#7a8a7e;font-size:.88rem;font-weight:500;text-decoration:none;transition:color .2s;}
.nav-link:hover{color:#22c55e;}
.nav-cta{padding:8px 18px;background:#22c55e;color:#000;border-radius:100px;font-weight:700;font-size:.85rem;text-decoration:none;transition:all .2s;box-shadow:0 0 14px rgba(34,197,94,.25);}
.nav-cta:hover{transform:translateY(-1px);box-shadow:0 0 22px rgba(34,197,94,.35);}
.mob-btn{display:none;background:none;border:none;color:#22c55e;font-size:1.4rem;cursor:pointer;width:40px;height:40px;align-items:center;justify-content:center;border-radius:9px;transition:background .2s;}
.mob-btn:hover{background:rgba(34,197,94,.1);}
.mob-menu{position:fixed;top:60px;right:-100%;width:260px;height:100vh;background:rgba(8,12,10,.98);backdrop-filter:blur(20px);border-left:1px solid rgba(34,197,94,.1);padding:24px 16px;display:flex;flex-direction:column;gap:4px;z-index:99;transition:right .3s cubic-bezier(.4,0,.2,1);}
.mob-menu.open{right:0;}
.mob-ov{display:none;position:fixed;inset:0;background:rgba(0,0,0,.6);z-index:98;}
.mob-ov.v{display:block;}

/* HERO */
.hero{min-height:100vh;display:flex;align-items:center;padding:80px 5% 60px;max-width:1300px;margin:0 auto;gap:60px;position:relative;z-index:1;}
.hc{flex:1;max-width:640px;}
.hbadge{display:inline-flex;align-items:center;gap:7px;padding:6px 14px;background:rgba(34,197,94,.1);border:1px solid rgba(34,197,94,.2);border-radius:100px;font-size:.76rem;font-weight:600;color:#22c55e;margin-bottom:20px;}
.htitle{font-size:3.6rem;font-weight:900;line-height:1.08;margin-bottom:20px;letter-spacing:-.5px;}
.htitle span{color:#22c55e;}
.hsub{font-size:1.1rem;color:#7a8a7e;margin-bottom:32px;max-width:520px;line-height:1.7;}
.hstats{display:flex;gap:32px;margin-bottom:36px;}
.hs-n{font-size:2rem;font-weight:900;color:#22c55e;letter-spacing:-1px;line-height:1;}
.hs-l{font-size:.8rem;color:#7a8a7e;margin-top:2px;}
.hbtns{display:flex;gap:14px;flex-wrap:wrap;}
.btn-p{padding:14px 32px;background:linear-gradient(135deg,#22c55e,#16a34a);color:#000;border-radius:14px;font-weight:700;font-size:.95rem;text-decoration:none;display:inline-flex;align-items:center;gap:9px;box-shadow:0 0 22px rgba(34,197,94,.3);transition:all .25s;}
.btn-p:hover{transform:translateY(-3px);box-shadow:0 0 36px rgba(34,197,94,.4);}
.btn-s{padding:14px 32px;background:rgba(255,255,255,.05);color:#f0f3f1;border-radius:14px;font-weight:600;font-size:.95rem;text-decoration:none;border:1px solid rgba(34,197,94,.25);display:inline-flex;align-items:center;gap:9px;transition:all .25s;}
.btn-s:hover{background:rgba(34,197,94,.08);transform:translateY(-2px);}

/* PREVIEW */
.hv{flex:1;display:flex;justify-content:center;position:relative;}
.preview{width:100%;max-width:400px;background:rgba(15,20,17,.95);border-radius:20px;padding:20px;border:1px solid rgba(34,197,94,.2);box-shadow:0 28px 60px rgba(0,0,0,.5);animation:fl 5s ease-in-out infinite;}
@keyframes fl{0%,100%{transform:translateY(0)}50%{transform:translateY(-14px)}}
.prev-hdr{display:flex;justify-content:space-between;align-items:center;margin-bottom:16px;padding-bottom:12px;border-bottom:1px solid rgba(255,255,255,.07);}
.prev-logo{font-weight:900;font-size:1rem;color:#22c55e;}
.prev-pct{font-weight:800;color:#22c55e;font-size:.9rem;}
.prev-item{background:rgba(8,12,10,.9);padding:11px;border-radius:10px;margin-bottom:8px;display:flex;align-items:center;gap:10px;border-left:3px solid #22c55e;}
.prev-t{font-weight:700;color:#22c55e;font-size:.78rem;min-width:42px;}
.prev-task{flex:1;color:#c8d4cc;font-size:.84rem;}
.prev-chk{width:24px;height:24px;border-radius:6px;background:rgba(34,197,94,.2);display:flex;align-items:center;justify-content:center;color:#22c55e;font-size:.68rem;}
.prev-pend{width:24px;height:24px;border-radius:6px;border:1px solid rgba(255,255,255,.1);display:flex;align-items:center;justify-content:center;color:#3d4d41;font-size:.65rem;}
.prev-bar{height:4px;background:rgba(255,255,255,.07);border-radius:4px;margin-top:12px;overflow:hidden;}
.prev-fill{height:100%;background:linear-gradient(90deg,#22c55e,#10b981);width:75%;}

/* SECTIONS */
.sec{padding:90px 5%;max-width:1300px;margin:0 auto;position:relative;z-index:1;}
.sec-t{font-size:2.4rem;font-weight:900;text-align:center;margin-bottom:14px;letter-spacing:-.4px;}
.sec-s{font-size:.98rem;color:#7a8a7e;text-align:center;max-width:580px;margin:0 auto 52px;line-height:1.7;}

/* FEATURES */
.feats{display:grid;grid-template-columns:repeat(auto-fit,minmax(280px,1fr));gap:22px;}
.fc{background:rgba(15,20,17,.8);border-radius:18px;padding:28px;border:1px solid rgba(255,255,255,.06);transition:all .28s;position:relative;overflow:hidden;}
.fc:hover{transform:translateY(-8px);border-color:rgba(34,197,94,.22);box-shadow:0 14px 36px rgba(0,0,0,.3);}
.fc::before{content:'';position:absolute;top:0;left:0;width:100%;height:2px;background:linear-gradient(90deg,#22c55e,#10b981);transform:scaleX(0);transform-origin:left;transition:transform .3s;}
.fc:hover::before{transform:scaleX(1);}
.fi-ico{width:52px;height:52px;background:rgba(34,197,94,.1);border-radius:14px;display:flex;align-items:center;justify-content:center;font-size:1.4rem;color:#22c55e;margin-bottom:18px;}
.f-t{font-size:1.15rem;font-weight:700;margin-bottom:10px;}
.f-d{color:#7a8a7e;font-size:.92rem;line-height:1.7;}

/* STEPS */
.steps{max-width:700px;margin:0 auto;position:relative;}
.steps::before{content:'';position:absolute;top:0;left:27px;width:2px;height:100%;background:linear-gradient(to bottom,#22c55e,transparent);}
.step{display:flex;gap:24px;align-items:flex-start;margin-bottom:40px;position:relative;z-index:1;}
.sn{width:54px;height:54px;background:linear-gradient(135deg,#22c55e,#16a34a);border-radius:50%;display:flex;align-items:center;justify-content:center;font-size:1.2rem;font-weight:900;color:#000;flex-shrink:0;box-shadow:0 0 20px rgba(34,197,94,.3);}
.sc-body{flex:1;padding-top:8px;}
.st{font-size:1.2rem;font-weight:700;margin-bottom:7px;}
.sd{color:#7a8a7e;font-size:.93rem;line-height:1.7;}

/* TESTIMONIALS */
.testis{display:grid;grid-template-columns:repeat(auto-fit,minmax(260px,1fr));gap:20px;}
.tc{background:rgba(15,20,17,.8);border-radius:16px;padding:24px;border:1px solid rgba(255,255,255,.06);}
.tt{color:#c8d4cc;font-size:.95rem;line-height:1.7;margin-bottom:18px;font-style:italic;}
.ta{display:flex;align-items:center;gap:12px;}
.tav{width:42px;height:42px;border-radius:50%;background:linear-gradient(135deg,#22c55e,#16a34a);display:flex;align-items:center;justify-content:center;font-weight:800;color:#000;font-size:.9rem;}
.tn{font-weight:700;font-size:.9rem;margin-bottom:2px;}
.tr{color:#7a8a7e;font-size:.78rem;}

/* CTA */
.cta{text-align:center;padding:80px 5%;background:linear-gradient(135deg,rgba(34,197,94,.07),transparent);border-radius:32px;margin:60px 5%;border:1px solid rgba(34,197,94,.1);position:relative;z-index:1;}
.cta-t{font-size:2.8rem;font-weight:900;margin-bottom:14px;letter-spacing:-.4px;}
.cta-t span{color:#22c55e;}
.cta-s{font-size:1rem;color:#7a8a7e;max-width:560px;margin:0 auto 36px;line-height:1.7;}

/* FOOTER */
.footer{padding:60px 5% 32px;border-top:1px solid rgba(255,255,255,.06);position:relative;z-index:1;}
.fg{display:grid;grid-template-columns:repeat(auto-fit,minmax(200px,1fr));gap:40px;margin-bottom:40px;}
.fl-n{font-size:1.4rem;font-weight:900;color:#22c55e;display:inline-block;margin-bottom:12px;}
.fl-d{color:#7a8a7e;font-size:.87rem;max-width:260px;line-height:1.6;margin-bottom:18px;}
.socials{display:flex;gap:10px;}
.soa{width:38px;height:38px;background:rgba(255,255,255,.05);border-radius:9px;display:flex;align-items:center;justify-content:center;color:#7a8a7e;text-decoration:none;transition:all .2s;}
.soa:hover{background:rgba(34,197,94,.1);color:#22c55e;transform:translateY(-2px);}
.fc-col h3{font-size:.9rem;font-weight:700;margin-bottom:14px;}
.fc-col ul{list-style:none;}
.fc-col li{margin-bottom:9px;}
.fc-col a{color:#7a8a7e;text-decoration:none;font-size:.87rem;transition:color .2s;}
.fc-col a:hover{color:#22c55e;}
.fb{text-align:center;padding-top:32px;border-top:1px solid rgba(255,255,255,.06);color:#3d4d41;font-size:.82rem;}

/* RESPONSIVE */
@media(max-width:960px){.hero{flex-direction:column;text-align:center;padding-top:100px;gap:36px;}.hstats{justify-content:center;}.hbtns{justify-content:center;}}
@media(max-width:768px){.htitle{font-size:2.6rem;}.sec-t{font-size:2rem;}.cta-t{font-size:2.1rem;}.nav-links{display:none;}.mob-btn{display:flex;}.btn-p,.btn-s{width:100%;justify-content:center;}.hbtns{flex-direction:column;}.step{flex-direction:column;text-align:center;}.steps::before{display:none;}}

@keyframes fadeUp{from{opacity:0;transform:translateY(28px)}to{opacity:1;transform:translateY(0)}}
.a{animation:fadeUp .6s ease forwards;opacity:0;}
.a1{animation-delay:.1s}.a2{animation-delay:.25s}.a3{animation-delay:.4s}
</style>
</head><body>

<nav class="nav">
  <a href="#" class="nav-logo"><span class="nav-n">1%</span></a>
  <button class="mob-btn" id="mbtn" onclick="toggleMenu()"><i class="fas fa-bars" id="mbico"></i></button>
  <div class="nav-links">
    <a href="#features" class="nav-link">Features</a>
    <a href="#how" class="nav-link">How it works</a>
    <a href="pricing.php" class="nav-link">Pricing</a>
    <a href="login.php" class="nav-cta"><i class="fas fa-sign-in-alt"></i> Sign in</a>
  </div>
</nav>
<div class="mob-ov" id="mOv" onclick="closeMenu()"></div>
<div class="mob-menu" id="mMenu">
  <a href="#features" class="nav-link" onclick="closeMenu()" style="padding:12px;border-radius:10px;">Features</a>
  <a href="#how" class="nav-link" onclick="closeMenu()" style="padding:12px;border-radius:10px;">How it works</a>
  <a href="pricing.php" class="nav-link" style="padding:12px;border-radius:10px;">Pricing</a>
  <a href="login.php" class="nav-cta" style="margin-top:10px;justify-content:center;display:flex;"><i class="fas fa-sign-in-alt"></i> Sign in</a>
</div>

<!-- HERO -->
<section class="hero">
  <div class="hc">
    <div class="hbadge a"><i class="fas fa-bolt"></i> Discipline, not motivation</div>
    <h1 class="htitle a a1">Become <span>1% better</span><br>every single day</h1>
    <p class="hsub a a2">Track your daily activities, build unbreakable habits, and measure your discipline — not just your intentions.</p>
    <div class="hstats a a2">
      <div><div class="hs-n">1%</div><div class="hs-l">better daily</div></div>
      <div><div class="hs-n">37x</div><div class="hs-l">better in 1 year</div></div>
      <div><div class="hs-n">365</div><div class="hs-l">days to master it</div></div>
    </div>
    <div class="hbtns a a3">
      <a href="login.php" class="btn-p"><i class="fas fa-rocket"></i> Start for free</a>
      <a href="pricing.php" class="btn-s"><i class="fas fa-crown"></i> See plans</a>
    </div>
  </div>
  <div class="hv a a3">
    <div class="preview">
      <div class="prev-hdr"><span class="prev-logo">1%</span><span class="prev-pct">75% complete ✅</span></div>
      <div class="prev-item"><div class="prev-t">06:00</div><div class="prev-task">Morning workout 🏋️</div><div class="prev-chk"><i class="fas fa-check"></i></div></div>
      <div class="prev-item"><div class="prev-t">08:00</div><div class="prev-task">Read 30 minutes 📚</div><div class="prev-chk"><i class="fas fa-check"></i></div></div>
      <div class="prev-item"><div class="prev-t">10:00</div><div class="prev-task">Deep work session 💻</div><div class="prev-pend"><i class="fas fa-clock"></i></div></div>
      <div class="prev-bar"><div class="prev-fill"></div></div>
    </div>
  </div>
</section>

<!-- FEATURES -->
<section class="sec" id="features">
  <h2 class="sec-t">Everything you need to build discipline</h2>
  <p class="sec-s">Simple, powerful tools to build your daily routine and measure your real progress — not just your effort.</p>
  <div class="feats">
    <div class="fc"><div class="fi-ico"><i class="fas fa-calendar-alt"></i></div><h3 class="f-t">Hourly scheduling</h3><p class="f-d">Plan your day by time slots. See exactly what you need to do and when — no ambiguity, just execution.</p></div>
    <div class="fc"><div class="fi-ico"><i class="fas fa-fire"></i></div><h3 class="f-t">Streak system</h3><p class="f-d">Track your consecutive days. Every day you show up counts. Break the chain and start again — the system is honest.</p></div>
    <div class="fc"><div class="fi-ico"><i class="fas fa-chart-line"></i></div><h3 class="f-t">Real analytics</h3><p class="f-d">Completion rates, productive hours, heatmaps — see your real discipline data, not just notifications.</p></div>
    <div class="fc"><div class="fi-ico"><i class="fas fa-mobile-alt"></i></div><h3 class="f-t">Built for mobile</h3><p class="f-d">95% of our users are on mobile. The whole app is designed for your phone first — fast, tap-friendly, offline-ready.</p></div>
    <div class="fc"><div class="fi-ico"><i class="fas fa-repeat"></i></div><h3 class="f-t">Auto-repeat activities</h3><p class="f-d">Set your morning routine once. It repeats every day automatically. Your habits are built in, not just hoped for.</p></div>
    <div class="fc"><div class="fi-ico"><i class="fas fa-star"></i></div><h3 class="f-t">Difficulty levels</h3><p class="f-d">Mark tasks as Trivial → Hard. Track not just what you did, but how hard you pushed yourself.</p></div>
  </div>
</section>

<!-- HOW IT WORKS -->
<section class="sec" id="how" style="background:rgba(34,197,94,.02);border-radius:28px;margin-top:-20px;">
  <h2 class="sec-t">Simple. Honest. Effective.</h2>
  <p class="sec-s">Three steps to transform your goals into real habits.</p>
  <div class="steps">
    <div class="step"><div class="sn">1</div><div class="sc-body"><h3 class="st">Plan your day</h3><p class="sd">Add your activities, assign time slots. Start with 3 — discipline is built slowly, not all at once.</p></div></div>
    <div class="step"><div class="sn">2</div><div class="sc-body"><h3 class="st">Execute with discipline</h3><p class="sd">Check off each activity as you do it. No motivation needed — you made a plan. Honor it.</p></div></div>
    <div class="step"><div class="sn">3</div><div class="sc-body"><h3 class="st">Measure your growth</h3><p class="sd">Watch your streak climb. See your weekly rate improve. 1% better today = 37x better in a year.</p></div></div>
  </div>
</section>

<!-- TESTIMONIALS -->
<section class="sec">
  <h2 class="sec-t">Real people, real discipline</h2>
  <p class="sec-s">Join thousands building their daily discipline, one activity at a time.</p>
  <div class="testis">
    <div class="tc"><p class="tt">"45 consecutive days. I've never kept a habit for more than a week before 1%. The streak system changed everything."</p><div class="ta"><div class="tav">ML</div><div><div class="tn">Marie L.</div><div class="tr">Entrepreneur · 120-day streak</div></div></div></div>
    <div class="tc"><p class="tt">"My completion rate went from 20% to 85% in one month. Seeing the real number daily is brutal and effective."</p><div class="ta"><div class="tav">TP</div><div><div class="tn">Thomas P.</div><div class="tr">Developer · 92-day streak</div></div></div></div>
    <div class="tc"><p class="tt">"I use it every morning on my phone. The interface is fast, no distractions. I plan, I execute, I track. That's it."</p><div class="ta"><div class="tav">SC</div><div><div class="tn">Sarah C.</div><div class="tr">Student · 67-day streak</div></div></div></div>
  </div>
</section>

<!-- CTA -->
<div class="cta">
  <h2 class="cta-t">Start building your <span>1%</span> today</h2>
  <p class="cta-s">Free forever. No credit card. No motivation required — just your decision to start.</p>
  <a href="login.php" class="btn-p" style="font-size:1rem;padding:16px 40px;"><i class="fas fa-bolt"></i> Create free account</a>
  <p style="color:#3d4d41;margin-top:14px;font-size:.82rem;">Free forever · No credit card · No BS</p>
</div>

<!-- FOOTER -->
<footer class="footer">
  <div class="fg">
    <div><span class="fl-n">1%</span><p class="fl-d">Build discipline. Not motivation. 1% better every day = 37x better in a year.</p>
    <div class="socials"><a href="#" class="soa"><i class="fab fa-twitter"></i></a><a href="#" class="soa"><i class="fab fa-instagram"></i></a><a href="#" class="soa"><i class="fab fa-github"></i></a></div></div>
    <div class="fc-col"><h3>Product</h3><ul><li><a href="#features">Features</a></li><li><a href="#how">How it works</a></li><li><a href="pricing.php">Pricing</a></li></ul></div>
    <div class="fc-col"><h3>Account</h3><ul><li><a href="login.php">Sign in</a></li><li><a href="login.php">Create account</a></li></ul></div>
    <div class="fc-col"><h3>Legal</h3><ul><li><a href="#">Privacy</a></li><li><a href="#">Terms</a></li></ul></div>
  </div>
  <div class="fb">© <?php echo date('Y')?> 1% — Build Discipline. Not Motivation.</div>
</footer>

<script>
function toggleMenu(){const open=document.getElementById('mMenu').classList.toggle('open');document.getElementById('mOv').classList.toggle('v',open);document.getElementById('mbico').className=open?'fas fa-times':'fas fa-bars';}
function closeMenu(){document.getElementById('mMenu').classList.remove('open');document.getElementById('mOv').classList.remove('v');document.getElementById('mbico').className='fas fa-bars';}
window.addEventListener('resize',()=>{if(window.innerWidth>768)closeMenu();});
document.querySelectorAll('a[href^="#"]').forEach(a=>{a.addEventListener('click',e=>{const t=document.querySelector(a.getAttribute('href'));if(t){e.preventDefault();window.scrollTo({top:t.offsetTop-70,behavior:'smooth'});}});});
</script>
</body></html>
