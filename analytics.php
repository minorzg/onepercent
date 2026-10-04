<?php
session_start();
require_once 'config.php';
checkLogin();

$uid = (int)$_SESSION['uid'];
$u   = getUser($uid);
$p   = getUserPlan($u);
$str = calcStreak($uid);

$gs = getDB()->prepare('SELECT COUNT(*) t, SUM(done) d, COUNT(DISTINCT DATE(date_created)) days FROM goals WHERE user_id=?');
$gs->execute(array($uid));
$g = $gs->fetch();
$tot = (int)$g['t']; $don = (int)$g['d']; $actDays = (int)$g['days'];
$gRate = $tot > 0 ? round($don/$tot*100) : 0;

$wq = getDB()->prepare('SELECT DATE(date_created) d, COUNT(*) t, SUM(done) dn FROM goals WHERE user_id=? AND date_created>=DATE_SUB(CURDATE(),INTERVAL 7 DAY) GROUP BY DATE(date_created) ORDER BY d');
$wq->execute(array($uid));
$wkR = $wq->fetchAll();
$wkL = array(); $wkP = array();
for ($i = 6; $i >= 0; $i--) {
    $d = date('Y-m-d', strtotime("-{$i} days"));
    $wkL[] = date('D', strtotime($d));
    $row = array_values(array_filter($wkR, function($r) use($d){ return $r['d'] === $d; }));
    $wkP[] = (!empty($row) && $row[0]['t'] > 0) ? round($row[0]['dn']/$row[0]['t']*100) : 0;
}
$wkAvg = count(array_filter($wkP)) > 0 ? round(array_sum($wkP)/count(array_filter($wkP))) : 0;

$hq = getDB()->prepare('SELECT hour_slot, COUNT(*) t, SUM(done) d, ROUND(SUM(done)/COUNT(*)*100) r FROM goals WHERE user_id=? GROUP BY hour_slot ORDER BY r DESC LIMIT 5');
$hq->execute(array($uid));
$topH = $hq->fetchAll();

$mq = getDB()->prepare('SELECT DATE(date_created) d, COUNT(*) t, SUM(done) dn FROM goals WHERE user_id=? AND date_created>=DATE_SUB(CURDATE(),INTERVAL 30 DAY) GROUP BY DATE(date_created)');
$mq->execute(array($uid));
$mthMap = array();
foreach ($mq->fetchAll() as $r) $mthMap[$r['d']] = $r['t'] > 0 ? round($r['dn']/$r['t']*100) : 0;
?>
<!DOCTYPE html><html lang="en"><head>
<meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=yes, viewport-fit=cover">
<title>1% — Analytics</title>
<?php echo getSharedCSS() ?>
<style>
.sg{display:grid;grid-template-columns:repeat(4,1fr);gap:8px;margin-bottom:16px;}
@media(max-width:580px){.sg{grid-template-columns:repeat(2,1fr);}}
.sc{background:var(--card);border:1px solid var(--card-b);border-radius:var(--rl);padding:13px 14px;}
.sc:hover{border-color:var(--green-border);}
.sci{width:28px;height:28px;border-radius:7px;background:var(--green-dim);border:1px solid var(--green-border);display:flex;align-items:center;justify-content:center;color:var(--green);font-size:.71rem;margin-bottom:7px;}
.scv{font-size:1.65rem;font-weight:900;letter-spacing:-1px;line-height:1;margin-bottom:2px;}
.scv.g{color:var(--green);}
.scl{font-size:.68rem;color:var(--text2);}
.chart-card{background:var(--card);border:1px solid var(--card-b);border-radius:var(--rl);padding:16px;margin-bottom:12px;}
.bars{display:flex;align-items:flex-end;gap:5px;height:100px;margin:10px 0 6px;}
.brc{flex:1;display:flex;flex-direction:column;align-items:center;gap:3px;}
.br{width:100%;border-radius:3px 3px 0 0;min-height:3px;background:var(--card-b);border:1px solid var(--card-b);}
.br.on{background:var(--green);border-color:var(--green);}
.brlbl{font-size:.6rem;color:var(--text3);text-transform:uppercase;}
.two{display:grid;grid-template-columns:1fr 1fr;gap:12px;}
@media(max-width:680px){.two{grid-template-columns:1fr;}}
.hl{display:flex;flex-direction:column;gap:7px;}
.hi{display:flex;align-items:center;gap:9px;}
.ht{font-weight:700;font-size:.76rem;color:var(--green);width:44px;flex-shrink:0;}
.htr{flex:1;height:6px;background:var(--card-b);border-radius:6px;overflow:hidden;}
.hfi{height:100%;background:linear-gradient(90deg,var(--green),#10b981);border-radius:6px;}
.hpc{font-size:.71rem;font-weight:700;width:34px;text-align:right;color:var(--text2);}
.hmap{display:grid;grid-template-columns:repeat(7,1fr);gap:2px;}
.hd{aspect-ratio:1;border-radius:2px;background:var(--card-b);}
.hd0{background:rgba(34,197,94,.07);}
.hd1{background:rgba(34,197,94,.2);}
.hd2{background:rgba(34,197,94,.4);}
.hd3{background:rgba(34,197,94,.65);}
.hd4{background:var(--green);}
.lockcard{position:relative;overflow:hidden;}
.lock-ov{position:absolute;inset:0;background:rgba(8,12,10,.88);border-radius:var(--rl);display:flex;flex-direction:column;align-items:center;justify-content:center;gap:8px;font-size:.84rem;font-weight:700;color:var(--text2);}
</style>
</head><body>
<div class="app">
<?php echo getNav('analytics', $u, $p) ?>
<div class="main-content">
    <div class="page">
        <div style="margin-bottom:18px;">
            <div style="font-size:.7rem;color:var(--text3);text-transform:uppercase;letter-spacing:.5px;margin-bottom:3px;">Analytics</div>
            <div style="font-size:1.45rem;font-weight:800;letter-spacing:-.3px;">Your <span style="color:var(--green);">progress</span></div>
        </div>

        <div class="sg">
            <div class="sc"><div class="sci"><i class="fas fa-fire"></i></div><div class="scv g"><?php echo $str ?></div><div class="scl">Streak 🔥</div></div>
            <div class="sc"><div class="sci"><i class="fas fa-check-double"></i></div><div class="scv"><?php echo $don ?></div><div class="scl">Total done</div></div>
            <div class="sc"><div class="sci"><i class="fas fa-percent"></i></div><div class="scv g"><?php echo $gRate ?></div><div class="scl">All-time rate</div></div>
            <div class="sc"><div class="sci"><i class="fas fa-calendar-check"></i></div><div class="scv"><?php echo $actDays ?></div><div class="scl">Active days</div></div>
        </div>

        <div class="chart-card">
            <div style="display:flex;justify-content:space-between;align-items:center;">
                <div class="sh" style="margin-bottom:0;">Last 7 days</div>
                <span style="font-size:.8rem;font-weight:700;color:var(--green);"><?php echo $wkAvg ?>% avg</span>
            </div>
            <div class="bars">
                <?php foreach ($wkP as $i => $pv): ?>
                <div class="brc"><div class="br <?php echo $pv > 0 ? 'on' : '' ?>" style="height:<?php echo max(3,$pv) ?>%"></div><div class="brlbl"><?php echo $wkL[$i] ?></div></div>
                <?php endforeach; ?>
            </div>
        </div>

        <div class="two">
            <div class="chart-card">
                <div class="sh">Top productive hours</div>
                <div class="hl">
                <?php if (empty($topH)): ?>
                    <p style="color:var(--text3);font-size:.81rem;">Not enough data yet.</p>
                <?php else: foreach ($topH as $h): ?>
                    <div class="hi">
                        <div class="ht"><?php echo sprintf('%02d',(int)$h['hour_slot']) ?>h</div>
                        <div class="htr"><div class="hfi" style="width:<?php echo $h['r'] ?>%"></div></div>
                        <div class="hpc"><?php echo $h['r'] ?>%</div>
                    </div>
                <?php endforeach; endif; ?>
                </div>
            </div>

            <div class="chart-card lockcard">
                <div class="sh">30-day heatmap</div>
                <div class="hmap">
                <?php for ($i = 29; $i >= 0; $i--) {
                    $d  = date('Y-m-d', strtotime("-{$i} days"));
                    $pv = isset($mthMap[$d]) ? $mthMap[$d] : -1;
                    $cls = $pv < 0 ? '' : ($pv >= 100 ? 'hd4' : ($pv >= 75 ? 'hd3' : ($pv >= 50 ? 'hd2' : ($pv >= 25 ? 'hd1' : 'hd0'))));
                    echo "<div class='hd $cls'></div>";
                } ?>
                </div>
                <div style="display:flex;gap:5px;align-items:center;margin-top:8px;font-size:.65rem;color:var(--text3);">
                    <span>Less</span>
                    <?php foreach (array('hd0','hd1','hd2','hd3','hd4') as $c): ?><div style="width:10px;height:10px;border-radius:2px;" class="hd <?php echo $c ?>"></div><?php endforeach; ?>
                    <span>More</span>
                </div>
                <?php if ($p['plan'] === 'free'): ?>
                <div class="lock-ov">
                    <i class="fas fa-lock" style="font-size:1.2rem;"></i>
                    <span>Available in Pro</span>
                    <a href="pricing.php" class="btn" style="font-size:.75rem;padding:7px 14px;">Upgrade →</a>
                </div>
                <?php endif; ?>
            </div>
        </div>

        <?php if ($p['plan'] === 'free'): ?>
        <div style="text-align:center;padding:18px;background:rgba(245,158,11,.07);border:1px solid rgba(245,158,11,.18);border-radius:var(--rl);margin-top:8px;">
            <div style="font-weight:700;margin-bottom:5px;">🔒 Full analytics in Pro</div>
            <p style="color:var(--text2);font-size:.81rem;margin-bottom:12px;">Heatmap, export, monthly trends and more.</p>
            <a href="pricing.php" class="btn"><i class="fas fa-rocket"></i> Upgrade to Pro</a>
        </div>
        <?php endif; ?>

    </div><!-- page -->

    <?php echo getBottomNav('analytics') ?>
</div><!-- main-content -->

</div><!-- app -->

<?php echo getSharedJS() ?>
</body></html>