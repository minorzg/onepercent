<?php
require_once 'config.php';
checkLogin();

$uid  = (int)$_SESSION['uid'];
$u    = getUser($uid);
$plan = getUserPlan($u);
$streak = calcStreak($uid);

$msg = null;

/* Delete all activities */
if (isset($_POST['del_all'])) {
    getDB()->prepare('DELETE FROM goals WHERE user_id=?')->execute([$uid]);
    $msg = ['type'=>'ok','text'=>'All activities deleted.'];
}

/* Update name */
if (isset($_POST['update_name'])) {
    $n = trim($_POST['name'] ?? '');
    if (strlen($n) >= 2) {
        getDB()->prepare('UPDATE users SET name=? WHERE id=?')->execute([$n, $uid]);
        $u['name'] = $n;
        $msg = ['type'=>'ok','text'=>'Name updated!'];
    } else {
        $msg = ['type'=>'err','text'=>'Name too short (min 2 chars).'];
    }
}

/* Update password */
if (isset($_POST['update_pass'])) {
    $old = $_POST['old_pass'] ?? '';
    $new = $_POST['new_pass'] ?? '';
    if (!password_verify($old, $u['password'])) {
        $msg = ['type'=>'err','text'=>'Current password is wrong.'];
    } elseif (strlen($new) < 6) {
        $msg = ['type'=>'err','text'=>'New password too short (min 6).'];
    } else {
        getDB()->prepare('UPDATE users SET password=? WHERE id=?')->execute([password_hash($new, PASSWORD_DEFAULT), $uid]);
        $msg = ['type'=>'ok','text'=>'Password updated!'];
    }
}

/* Stats */
$gs = getDB()->prepare('SELECT COUNT(*) t, SUM(done) d, COUNT(DISTINCT DATE(date_created)) days FROM goals WHERE user_id=?');
$gs->execute([$uid]); $stats = $gs->fetch();
$gRate = (int)$stats['t'] > 0 ? round((int)$stats['d'] / (int)$stats['t'] * 100) : 0;
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=yes, viewport-fit=cover">
<title>1% — Profile</title>
<?php echo getSharedCSS(); ?>
<style>
.profile-header{background:linear-gradient(135deg,rgba(34,197,94,.08),rgba(34,197,94,.02));border:1px solid var(--green-border);border-radius:var(--rl);padding:24px;margin-bottom:20px;display:flex;align-items:center;gap:18px;}
.big-avatar{width:64px;height:64px;border-radius:50%;background:linear-gradient(135deg,#22c55e,#10b981);display:flex;align-items:center;justify-content:center;font-size:1.6rem;font-weight:800;color:#000;flex-shrink:0;box-shadow:0 0 20px rgba(34,197,94,.3);}
.profile-name{font-size:1.3rem;font-weight:800;margin-bottom:4px;}
.profile-email{font-size:.8rem;color:var(--text2);}
.plan-pill{display:inline-flex;align-items:center;gap:5px;padding:3px 10px;border-radius:100px;font-size:.7rem;font-weight:700;margin-top:6px;}
.plan-pill.free{background:var(--card);border:1px solid var(--card-b);color:var(--text2);}
.plan-pill.pro{background:rgba(34,197,94,.15);border:1px solid var(--green-border);color:var(--green);}
.plan-pill.vip{background:rgba(245,158,11,.15);border:1px solid rgba(245,158,11,.3);color:var(--amber);}
.stats-mini{display:grid;grid-template-columns:repeat(3,1fr);gap:10px;margin-bottom:20px;}
.sm-card{background:var(--card);border:1px solid var(--card-b);border-radius:var(--r);padding:14px;text-align:center;}
.sm-val{font-size:1.6rem;font-weight:800;letter-spacing:-1px;color:var(--green);margin-bottom:2px;}
.sm-lbl{font-size:.72rem;color:var(--text2);}
.form-card{background:var(--card);border:1px solid var(--card-b);border-radius:var(--rl);padding:20px;margin-bottom:14px;}
.form-row{display:grid;grid-template-columns:1fr auto;gap:8px;align-items:flex-end;}
.save-btn{padding:11px 18px;background:var(--green);color:#000;border:none;border-radius:var(--r);font-weight:700;font-size:.85rem;cursor:pointer;white-space:nowrap;transition:opacity .2s;}
.save-btn:hover{opacity:.85;}
.danger-zone{background:rgba(239,68,68,.05);border:1px solid rgba(239,68,68,.2);border-radius:var(--rl);padding:20px;}
.danger-title{color:var(--red);font-weight:700;margin-bottom:4px;font-size:.92rem;}
.btn-danger{padding:10px 18px;background:transparent;color:var(--red);border:1px solid rgba(239,68,68,.3);border-radius:var(--r);font-weight:600;font-size:.85rem;cursor:pointer;transition:all .2s;}
.btn-danger:hover{background:rgba(239,68,68,.1);}
</style>
</head>
<body>
<div class="app">
<?php echo getNav('profile', $u, $plan); ?>
<div class="main-content">
<?php echo getBottomNav('profile'); ?>
<div class="page">

<?php if ($msg): ?>
<div class="notif <?php echo $msg['type']; ?>">
  <i class="fas <?php echo $msg['type']==='ok'?'fa-check-circle':'fa-exclamation-circle'; ?>"></i>
  <?php echo htmlspecialchars($msg['text']); ?>
</div>
<?php endif; ?>

<div class="profile-header">
  <div class="big-avatar"><?php echo strtoupper(substr($u['name']??'U',0,1)); ?></div>
  <div>
    <div class="profile-name"><?php echo htmlspecialchars($u['name']??''); ?></div>
    <div class="profile-email"><?php echo htmlspecialchars($u['email']??''); ?></div>
    <div class="plan-pill <?php echo $plan['plan']; ?>">
      <?php if($plan['plan']==='vip'): ?><i class="fas fa-crown"></i>
      <?php elseif($plan['plan']==='pro'): ?><i class="fas fa-rocket"></i>
      <?php else: ?><i class="fas fa-user"></i><?php endif; ?>
      <?php echo $plan['label']; ?>
    </div>
  </div>
</div>

<div class="stats-mini">
  <div class="sm-card"><div class="sm-val"><?php echo $streak; ?></div><div class="sm-lbl">🔥 Streak</div></div>
  <div class="sm-card"><div class="sm-val"><?php echo (int)$stats['d']; ?></div><div class="sm-lbl">✅ Done</div></div>
  <div class="sm-card"><div class="sm-val"><?php echo $gRate; ?>%</div><div class="sm-lbl">📊 Rate</div></div>
</div>

<!-- Edit name -->
<div class="form-card">
  <div class="sec"><div class="sec-dot"></div>Display name</div>
  <form method="POST">
    <div class="form-row">
      <div><label class="f-label">Name</label><input type="text" name="name" class="f-input" value="<?php echo htmlspecialchars($u['name']??''); ?>" required></div>
      <button type="submit" name="update_name" class="save-btn">Save</button>
    </div>
  </form>
</div>

<!-- Change password -->
<div class="form-card">
  <div class="sec"><div class="sec-dot"></div>Change password</div>
  <form method="POST">
    <div class="f-group"><label class="f-label">Current password</label><input type="password" name="old_pass" class="f-input" placeholder="••••••••" required></div>
    <div class="f-group"><label class="f-label">New password</label><input type="password" name="new_pass" class="f-input" placeholder="Min 6 characters" required></div>
    <button type="submit" name="update_pass" class="save-btn" style="width:100%;padding:12px;display:flex;align-items:center;justify-content:center;gap:7px;"><i class="fas fa-lock"></i> Update password</button>
  </form>
</div>

<!-- Plan -->
<div class="form-card">
  <div class="sec"><div class="sec-dot"></div>Subscription</div>
  <div style="display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:12px">
    <div>
      <div style="font-weight:600;margin-bottom:4px">Current plan: <?php echo $plan['label']; ?></div>
      <div style="font-size:.8rem;color:var(--text2)">Limit: <?php echo $plan['max_goals']>=9999?'Unlimited':$plan['max_goals'].' activities/day'; ?></div>
    </div>
    <?php if($plan['plan']!=='vip'): ?>
    <a href="pricing.php" class="btn-green"><i class="fas fa-crown"></i> Upgrade</a>
    <?php endif; ?>
  </div>
</div>

<!-- Danger zone -->
<div class="danger-zone">
  <div class="danger-title"><i class="fas fa-exclamation-triangle"></i> Danger zone</div>
  <div style="font-size:.8rem;color:var(--text2);margin-bottom:14px">These actions are irreversible.</div>
  <form method="POST" onsubmit="return confirm('Delete ALL your activities? This cannot be undone!')">
    <button type="submit" name="del_all" class="btn-danger"><i class="fas fa-trash"></i> Delete all my activities</button>
  </form>
</div>

</div>
</div>
</div>
<?php echo getSharedJS(); ?>
</body>
</html>
