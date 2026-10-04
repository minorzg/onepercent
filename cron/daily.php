<?php
// cron/daily.php
// Call via URL: https://one-percent.xo.je/cron/daily.php?key=MY_SECRET_KEY
// Set up in InfinityFree control panel -> Cron Jobs -> every day at 08:00
require_once '../config.php';
define('CRON_KEY', 'change_this_to_a_random_secret');
$key = isset($_GET['key']) ? $_GET['key'] : '';
if ($key !== CRON_KEY) { http_response_code(403); die('Forbidden'); }

header('Content-Type: application/json');
$results = array('sent'=>0,'expired'=>0,'errors'=>0);
$today   = date('N'); // 1=Mon 7=Sun

// 1. CHECK PLAN EXPIRY
$expQ = getDB()->query("SELECT id,name,email,plan_expires FROM users WHERE (is_premium=1 OR is_vip=1) AND plan_expires IS NOT NULL");
foreach ($expQ->fetchAll() as $u) {
    $daysLeft = (int)ceil((strtotime($u['plan_expires']) - time()) / 86400);
    if ($daysLeft === 3) {
        $ok = sendMail($u['email'], 'Your 1% plan expires in 3 days',
            '<h2 style="color:#f59e0b;">Plan expiring soon</h2><p>Hi '.$u['name'].', your plan expires on <strong>'.$u['plan_expires'].'</strong>.<br>Renew to keep your streak and access.</p><a href="'.SITE_URL.'/pricing.php" style="display:inline-block;padding:12px 22px;background:#f59e0b;color:#000;border-radius:10px;font-weight:700;text-decoration:none;">Renew Plan</a>');
        if ($ok) $results['sent']++;
    }
    if ($daysLeft <= 0) {
        getDB()->prepare('UPDATE users SET is_premium=0, is_vip=0 WHERE id=?')->execute(array($u['id']));
        $results['expired']++;
        sendMail($u['email'], 'Your 1% plan has expired',
            '<h2>Plan expired</h2><p>Hi '.$u['name'].', your plan expired. You are back on the free tier. <a href="'.SITE_URL.'/pricing.php">Renew anytime</a>.</p>');
    }
}

// 2. WEEKLY REPORT (every Monday)
if ($today == 1) {
    $usersQ = getDB()->query("SELECT id,name,email,notif_weekly FROM users WHERE notif_weekly=1 OR notif_weekly IS NULL");
    foreach ($usersQ->fetchAll() as $u) {
        $ws = getDB()->prepare('SELECT COUNT(*) t, SUM(done) d FROM goals WHERE user_id=? AND date_created>=DATE_SUB(CURDATE(),INTERVAL 7 DAY)');
        $ws->execute(array($u['id'])); $wr = $ws->fetch();
        $wPct = (int)$wr['t'] > 0 ? round((int)$wr['d']/(int)$wr['t']*100) : 0;
        $str  = getStreak((int)$u['id']);
        $bd   = getDB()->prepare('SELECT DATE(date_created) d FROM goals WHERE user_id=? AND date_created>=DATE_SUB(CURDATE(),INTERVAL 7 DAY) GROUP BY d HAVING SUM(done)/COUNT(*)=MAX(SUM(done)/COUNT(*)) LIMIT 1');
        $bd->execute(array($u['id'])); $bdr = $bd->fetch();
        $bestDay = $bdr ? date('l', strtotime($bdr['d'])) : 'N/A';
        $ok = sendMail($u['email'], 'Your 1% weekly report - '.date('M j'), emailWeeklyReport($u['name'],$wPct,$str,$bestDay));
        if ($ok) $results['sent']++; else $results['errors']++;
    }
}

// 3. DAILY MOTIVATION FOR VIP
$vipsQ = getDB()->query("SELECT id,name,email FROM users WHERE is_vip=1 AND notif_daily=1");
foreach ($vipsQ->fetchAll() as $u) {
    $ok = sendMail($u['email'], 'Be the 1% - Daily motivation', emailVipMotivation($u['name']));
    if ($ok) $results['sent']++; else $results['errors']++;
}

echo json_encode(array('status'=>'ok','date'=>date('Y-m-d'),'results'=>$results));
