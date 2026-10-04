<?php
require_once 'config.php';
checkLogin();

$uid  = (int)$_SESSION['uid'];
$u    = getUser($uid);
$plan = getUserPlan($u);
$streak = calcStreak($uid);

/* Generate recurring goals for this month (safe — skips if columns missing) */
function genMonth($uid,$mo,$yr){
    try {
        $db = getDB();
        $db->query('SELECT recurrence_id FROM goals LIMIT 1');
        $dim = (int)date('t', mktime(0,0,0,$mo,1,$yr));
        $tpl = $db->prepare('SELECT recurrence_id,title,hour_slot,end_hour,difficulty,repeat_dow FROM goals WHERE user_id=? AND recurrence_id IS NOT NULL GROUP BY recurrence_id');
        $tpl->execute([$uid]);
        foreach($tpl->fetchAll() as $t){
            $dow = (int)$t['repeat_dow'];
            for($day=1;$day<=$dim;$day++){
                $date = $yr.'-'.str_pad($mo,2,'0',STR_PAD_LEFT).'-'.str_pad($day,2,'0',STR_PAD_LEFT);
                if((int)date('N',strtotime($date))!==$dow) continue;
                $chk = $db->prepare('SELECT id FROM goals WHERE user_id=? AND recurrence_id=? AND date_created=?');
                $chk->execute([$uid,$t['recurrence_id'],$date]);
                if(!$chk->fetch()){
                    $db->prepare('INSERT INTO goals(user_id,title,hour_slot,end_hour,difficulty,done,repeat_weekly,repeat_dow,recurrence_id,date_created)VALUES(?,?,?,?,?,0,1,?,?,?)')->execute([$uid,$t['title'],$t['hour_slot'],$t['end_hour'],$t['difficulty'],$dow,$t['recurrence_id'],$date]);
                }
            }
        }
    } catch(Exception $e){}
}
genMonth($uid,(int)date('n'),(int)date('Y'));

/* ── ADD ── */
if(isset($_POST['add'])){
    $title    = trim($_POST['title'] ?? '');
    $sh       = (int)($_POST['sh']   ?? 0);
    $eh       = (int)($_POST['eh']   ?? 0);
    $diff     = (int)($_POST['diff'] ?? 1);
    $days_raw = trim($_POST['days_sel'] ?? '');
    $days_sel = [];
    if($days_raw !== '') foreach(explode(',',$days_raw) as $d){ $d=(int)trim($d); if($d>=1&&$d<=7) $days_sel[]=$d; }

    $cnt = getDB()->prepare('SELECT COUNT(*) FROM goals WHERE user_id=? AND date_created=CURDATE()');
    $cnt->execute([$uid]);
    if((int)$cnt->fetchColumn() >= $plan['max_goals']){
        $_SESSION['msg'] = ['warn','Limit reached. <a href="pricing.php" style="color:var(--green);font-weight:700">Upgrade</a>'];
    } elseif(empty($title)||$sh<5||$eh<=$sh){
        $_SESSION['msg'] = ['err','Check title and hours.'];
    } elseif(!empty($days_sel)){
        $dowFull=[1=>'Mon',2=>'Tue',3=>'Wed',4=>'Thu',5=>'Fri',6=>'Sat',7=>'Sun'];
        $todDow=(int)date('N');
        try {
            foreach($days_sel as $dow){
                $rid = uniqid('rec_',true);
                $diff2 = ($dow-$todDow+7)%7;
                $first = date('Y-m-d',strtotime('+'.$diff2.' days'));
                getDB()->prepare('INSERT INTO goals(user_id,title,hour_slot,end_hour,difficulty,done,repeat_weekly,repeat_dow,recurrence_id,date_created)VALUES(?,?,?,?,?,0,1,?,?,?)')->execute([$uid,$title,$sh,$eh,$diff,$dow,$rid,$first]);
            }
            genMonth($uid,(int)date('n'),(int)date('Y'));
            $names = array_map(fn($d)=>$dowFull[$d], $days_sel);
            $_SESSION['msg'] = ['ok','Repeats every '.implode(', ',$names).' — visible in calendar!'];
        } catch(Exception $e){
            $_SESSION['msg'] = ['err','DB error: run the schema migration first (schema_v4.sql).'];
        }
    } else {
        getDB()->prepare('INSERT INTO goals(user_id,title,hour_slot,end_hour,difficulty,repeat_daily,date_created)VALUES(?,?,?,?,?,0,CURDATE())')->execute([$uid,$title,$sh,$eh,$diff]);
        $_SESSION['msg'] = ['ok','Activity added!'];
    }
    header('Location: dashboard.php'); exit;
}

/* ── TOGGLE done ── */
if(isset($_POST['tog'])){
    getDB()->prepare('UPDATE goals SET done=NOT done WHERE id=? AND user_id=?')->execute([(int)$_POST['tog'],$uid]);
    header('Location: dashboard.php'); exit;
}

/* ── DELETE single occurrence ── */
if(isset($_POST['del'])){
    getDB()->prepare('DELETE FROM goals WHERE id=? AND user_id=?')->execute([(int)$_POST['del'],$uid]);
    header('Location: dashboard.php'); exit;
}

/* ── DELETE ALL occurrences of a recurrence ── */
if(isset($_POST['del_all_rec'])){
    $rid = $_POST['del_all_rec'];
    try { getDB()->prepare('DELETE FROM goals WHERE recurrence_id=? AND user_id=?')->execute([$rid,$uid]); }
    catch(Exception $e){}
    $_SESSION['msg'] = ['ok','All occurrences deleted.'];
    header('Location: dashboard.php'); exit;
}

/* ── EDIT single occurrence ── */
if(isset($_POST['edit_save'])){
    $eid = (int)$_POST['edit_id'];
    $et  = trim($_POST['edit_title'] ?? '');
    $es  = (int)($_POST['edit_sh'] ?? 5);
    $ee  = (int)($_POST['edit_eh'] ?? 6);
    if(!empty($et)&&$ee>$es){
        try {
            getDB()->prepare('UPDATE goals SET title=?,hour_slot=?,end_hour=?,recurrence_id=NULL,repeat_weekly=0,repeat_dow=NULL WHERE id=? AND user_id=?')->execute([$et,$es,$ee,$eid,$uid]);
        } catch(Exception $e){
            getDB()->prepare('UPDATE goals SET title=?,hour_slot=?,end_hour=? WHERE id=? AND user_id=?')->execute([$et,$es,$ee,$eid,$uid]);
        }
        $_SESSION['msg'] = ['ok','Updated.'];
    }
    header('Location: dashboard.php'); exit;
}

/* ── DATA ── */
$gs = getDB()->prepare('SELECT * FROM goals WHERE user_id=? AND date_created=CURDATE() ORDER BY hour_slot');
$gs->execute([$uid]); $goals = $gs->fetchAll();
$total = count($goals); $done = 0;
foreach($goals as $g) $done += (int)$g['done'];
$pct = $total > 0 ? round($done/$total*100) : 0;

$wq = getDB()->prepare('SELECT DATE(date_created) d,COUNT(*) t,SUM(done) dn FROM goals WHERE user_id=? AND date_created>=DATE_SUB(CURDATE(),INTERVAL 7 DAY) GROUP BY DATE(date_created)');
$wq->execute([$uid]); $wkMap=[];
foreach($wq->fetchAll() as $r) $wkMap[$r['d']] = $r['t']>0 ? round($r['dn']/$r['t']*100) : 0;
$wkAvg = count(array_filter($wkMap)) > 0 ? round(array_sum($wkMap)/count(array_filter($wkMap))) : 0;

$msg = $_SESSION['msg'] ?? null; unset($_SESSION['msg']);
$diffC = ['var(--text3)','var(--green)','var(--amber)','var(--red)'];
$diffS = ['★','★★','★★★','★★★★'];
$dowFull=[1=>'Monday',2=>'Tuesday',3=>'Wednesday',4=>'Thursday',5=>'Friday',6=>'Saturday',7=>'Sunday'];
$todDow = (int)date('N');
?>
<!DOCTYPE html><html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=yes, viewport-fit=cover">
<title>1% — Dashboard</title>
<?php echo getSharedCSS(); ?>
<style>
.page-header{display:flex;align-items:flex-end;justify-content:space-between;flex-wrap:wrap;gap:12px;margin-bottom:24px;}
.page-title{font-size:1.8rem;font-weight:800;letter-spacing:-.5px;line-height:1.1;}
.page-sub{font-size:.83rem;color:var(--text2);margin-top:4px;}
/* STATS */
.stats-grid{display:grid;grid-template-columns:repeat(4,1fr);gap:12px;margin-bottom:20px;}
.stat-card{background:var(--card);border:1px solid var(--card-b);border-radius:var(--rl);padding:16px 18px;}
.stat-icon{width:32px;height:32px;border-radius:10px;background:var(--green-dim);border:1px solid var(--green-border);display:flex;align-items:center;justify-content:center;color:var(--green);font-size:.8rem;margin-bottom:10px;}
.stat-val{font-size:1.8rem;font-weight:800;letter-spacing:-1px;line-height:1;color:var(--green);}
.stat-lbl{font-size:.73rem;color:var(--text2);margin-top:3px;}
/* PROGRESS */
.prog-bar-wrap{height:6px;background:var(--card-b);border-radius:6px;overflow:hidden;margin:10px 0 6px;}
.prog-bar{height:100%;background:linear-gradient(90deg,var(--green),#10b981);border-radius:6px;transition:width .8s ease;}
/* MAIN GRID */
.dash-grid{display:grid;grid-template-columns:1fr 300px;gap:16px;align-items:start;}
/* ACTIVITY ITEM */
.act-list{display:flex;flex-direction:column;gap:8px;}
.act-item{background:var(--card);border:1px solid var(--card-b);border-radius:var(--rl);padding:14px 16px;display:grid;grid-template-columns:60px 1fr auto;gap:10px;align-items:center;transition:border-color .18s;}
.act-item:hover{border-color:var(--green-border);}
.act-item.done-item{opacity:.45;}
.act-time{font-size:.78rem;font-weight:700;color:var(--green);line-height:1.3;}
.act-dur{font-size:.65rem;color:var(--text3);margin-top:2px;}
.act-name{font-weight:600;font-size:.9rem;margin-bottom:4px;}
.act-item.done-item .act-name{text-decoration:line-through;color:var(--text3);}
.act-tags{display:flex;gap:5px;flex-wrap:wrap;align-items:center;}
.tag{font-size:.62rem;font-weight:600;padding:2px 8px;border-radius:20px;border:1px solid var(--card-b);color:var(--text3);}
.tag-rec{color:var(--green);border-color:var(--green-border);background:var(--green-dim);}
.tag-amber{color:var(--amber);border-color:rgba(245,158,11,.3);background:rgba(245,158,11,.08);}
.act-btns{display:flex;gap:5px;}
.act-btn{width:32px;height:32px;border-radius:9px;border:1px solid var(--card-b);background:transparent;color:var(--text2);display:flex;align-items:center;justify-content:center;cursor:pointer;font-size:.75rem;transition:all .15s;}
.act-btn:hover{color:var(--green);border-color:var(--green-border);}
.act-btn.done-btn{background:var(--green);color:#000;border-color:var(--green);}
.act-btn.del-btn:hover{color:var(--red);border-color:rgba(239,68,68,.3);}
.act-btn.edit-btn:hover{color:var(--amber);border-color:rgba(245,158,11,.3);}
/* ADD FORM card */
.add-form-card{background:var(--card);border:1px solid var(--card-b);border-radius:var(--rl);padding:20px;}
/* STREAK */
.streak-card{background:linear-gradient(135deg,rgba(34,197,94,.12),rgba(34,197,94,.02));border:1px solid var(--green-border);border-radius:var(--rl);padding:18px;margin-bottom:14px;}
.streak-num{font-size:2.8rem;font-weight:800;color:var(--green);letter-spacing:-2px;line-height:1;}
/* CHART */
.bar-chart{display:flex;align-items:flex-end;gap:4px;height:56px;margin:10px 0 6px;}
.bar-col{flex:1;display:flex;flex-direction:column;align-items:center;gap:3px;}
.bar-fill{width:100%;border-radius:4px 4px 0 0;min-height:3px;background:var(--card-b);transition:height .5s ease;}
.bar-fill.today{background:var(--green);}
.bar-lbl{font-size:.58rem;color:var(--text3);text-transform:uppercase;}
/* EMPTY */
.empty-state{text-align:center;padding:40px 20px;color:var(--text3);}
/* Upgrade banner */
.upgrade-banner{display:flex;align-items:center;gap:14px;padding:14px 16px;background:rgba(245,158,11,.08);border:1px solid rgba(245,158,11,.2);border-radius:var(--rl);color:var(--amber);transition:transform .2s;}
.upgrade-banner:hover{transform:translateY(-2px);}
/* RESPONSIVE */
@media(max-width:960px){.dash-grid{grid-template-columns:1fr;}}
@media(max-width:640px){.stats-grid{grid-template-columns:repeat(2,1fr);}.act-item{grid-template-columns:50px 1fr auto;gap:8px;}.page-title{font-size:1.4rem;}}
</style>
</head>
<body>
<div class="app">
<?php echo getNav('dashboard',$u,$plan); ?>
<div class="main-content">
<?php echo getBottomNav('dashboard'); ?>
<div class="page">

<?php if($msg): $cls=['ok'=>'ok','err'=>'err','warn'=>'warn'][$msg[0]]; ?>
<div class="notif <?php echo $cls; ?>"><i class="fas <?php echo $msg[0]==='ok'?'fa-check-circle':'fa-exclamation-circle'; ?>"></i> <?php echo $msg[1]; ?></div>
<?php endif; ?>

<!-- HEADER -->
<div class="page-header">
  <div>
    <div style="font-size:.8rem;color:var(--green);font-weight:600;margin-bottom:4px"><?php echo date('l, F j'); ?></div>
    <div class="page-title">Hey, <?php echo htmlspecialchars($u['name']??'Champion'); ?> 👋</div>
  </div>
  <button class="btn-green" onclick="openModal('addM')"><i class="fas fa-plus"></i> New activity</button>
</div>

<!-- LIMIT -->
<?php if($plan['plan']!=='vip'&&$total>0): $lp=round($total/$plan['max_goals']*100); ?>
<div style="display:flex;align-items:center;gap:10px;padding:10px 14px;background:var(--card);border:1px solid var(--card-b);border-radius:var(--r);margin-bottom:16px;font-size:.8rem;color:var(--text2)">
  <span><?php echo $total.'/'.$plan['max_goals']; ?> today</span>
  <div style="flex:1;height:4px;background:var(--card-b);border-radius:4px;overflow:hidden"><div style="height:100%;border-radius:4px;background:<?php echo $lp>=100?'var(--red)':($lp>=75?'var(--amber)':'var(--green)'); ?>;width:<?php echo min(100,$lp); ?>%"></div></div>
  <?php if($lp>=75): ?><a href="pricing.php" style="color:var(--amber);font-weight:700;font-size:.75rem;white-space:nowrap">Upgrade</a><?php endif; ?>
</div>
<?php endif; ?>

<!-- STATS -->
<div class="stats-grid">
  <div class="stat-card"><div class="stat-icon"><i class="fas fa-fire"></i></div><div class="stat-val"><?php echo $streak; ?></div><div class="stat-lbl">Streak 🔥</div></div>
  <div class="stat-card"><div class="stat-icon"><i class="fas fa-check-double"></i></div><div class="stat-val"><?php echo $done; ?></div><div class="stat-lbl">Done today</div></div>
  <div class="stat-card"><div class="stat-icon"><i class="fas fa-percent"></i></div><div class="stat-val"><?php echo $pct; ?></div><div class="stat-lbl">Completion</div></div>
  <div class="stat-card"><div class="stat-icon"><i class="fas fa-chart-line"></i></div><div class="stat-val"><?php echo $wkAvg; ?></div><div class="stat-lbl">7-day avg</div></div>
</div>

<!-- PROGRESS BAR -->
<div class="card" style="margin-bottom:20px">
  <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:4px">
    <div class="sec"><div class="sec-dot"></div>Today's Progress</div>
    <span style="font-weight:700;color:var(--green)"><?php echo $pct; ?>%</span>
  </div>
  <div class="prog-bar-wrap"><div class="prog-bar" style="width:<?php echo $pct; ?>%"></div></div>
  <div style="display:flex;justify-content:space-between;font-size:.76rem;color:var(--text2)"><span>✅ <?php echo $done; ?> done</span><span>⏳ <?php echo $total-$done; ?> left</span></div>
</div>

<!-- MAIN GRID -->
<div class="dash-grid">
<div>
  <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:12px">
    <div class="sec" style="margin-bottom:0"><div class="sec-dot"></div>Activities</div>
    <span style="font-size:.75rem;color:var(--text3)"><?php echo date('D j M'); ?></span>
  </div>
  <div class="act-list">
  <?php if(empty($goals)): ?>
    <div class="empty-state">
      <div style="font-size:2.5rem;opacity:.15;margin-bottom:12px">📅</div>
      <p style="font-weight:600">No activities today</p>
      <p style="font-size:.8rem;margin-top:6px">Tap "New activity" to start</p>
    </div>
  <?php else: foreach($goals as $g):
    $gid  = (int)$g['id'];
    $dur  = (int)$g['end_hour']-(int)$g['hour_slot'];
    $di   = min(3,(int)($g['difficulty']??1));
    $isRec= !empty($g['recurrence_id']);
    $rid  = $g['recurrence_id'] ?? '';
    $dow  = (int)($g['repeat_dow']??0);
    $dowLabel = $isRec&&$dow>0&&isset($dowFull[$dow]) ? '↻ '.$dowFull[$dow] : '';
  ?>
  <div class="act-item <?php echo $g['done']?'done-item':''; ?>">
    <div>
      <div class="act-time"><?php echo sprintf('%02d',(int)$g['hour_slot']); ?>:00<br><?php echo sprintf('%02d',(int)$g['end_hour']); ?>:00</div>
      <div class="act-dur"><?php echo $dur; ?>h</div>
    </div>
    <div>
      <div class="act-name"><?php echo htmlspecialchars($g['title']); ?></div>
      <div class="act-tags">
        <span style="font-size:.65rem;color:<?php echo $diffC[$di]; ?>"><?php echo $diffS[$di]; ?></span>
        <?php if($isRec&&$dowLabel): ?><span class="tag tag-rec"><?php echo $dowLabel; ?></span><?php endif; ?>
      </div>
    </div>
    <div class="act-btns">
      <!-- Toggle done -->
      <form method="POST" style="display:contents">
        <input type="hidden" name="tog" value="<?php echo $gid; ?>">
        <button class="act-btn <?php echo $g['done']?'done-btn':''; ?>" title="<?php echo $g['done']?'Undo':'Mark done'; ?>"><i class="fas <?php echo $g['done']?'fa-undo':'fa-check'; ?>"></i></button>
      </form>
      <!-- Edit this day -->
      <button class="act-btn edit-btn" title="Edit this day"
        onclick="document.getElementById('eId').value='<?php echo $gid; ?>';document.getElementById('eTitle').value='<?php echo addslashes(htmlspecialchars($g['title'])); ?>';document.getElementById('eSh').value='<?php echo (int)$g['hour_slot']; ?>';document.getElementById('eEh').value='<?php echo (int)$g['end_hour']; ?>';document.getElementById('eRid').value='<?php echo htmlspecialchars($rid); ?>';document.getElementById('eRecNote').style.display='<?php echo $isRec?'flex':'none'; ?>';openModal('editM');">
        <i class="fas fa-pen"></i>
      </button>
      <!-- Delete: single or all? -->
      <?php if($isRec&&!empty($rid)): ?>
      <button class="act-btn del-btn" title="Delete options"
        onclick="document.getElementById('dRid').value='<?php echo htmlspecialchars($rid); ?>';document.getElementById('dGid').value='<?php echo $gid; ?>';openModal('delM');">
        <i class="fas fa-trash"></i>
      </button>
      <?php else: ?>
      <form method="POST" style="display:contents" onsubmit="return confirm('Delete this activity?')">
        <input type="hidden" name="del" value="<?php echo $gid; ?>">
        <button class="act-btn del-btn" title="Delete"><i class="fas fa-trash"></i></button>
      </form>
      <?php endif; ?>
    </div>
  </div>
  <?php endforeach; endif; ?>
  </div>
</div>

<!-- RIGHT COLUMN -->
<div>
  <!-- Streak -->
  <div class="streak-card" style="margin-bottom:14px">
    <div class="sec" style="margin-bottom:6px"><div class="sec-dot"></div>Streak</div>
    <div class="streak-num"><?php echo $streak; ?></div>
    <div style="font-size:.78rem;color:var(--text2);margin-top:3px">consecutive days 🔥</div>
  </div>

  <!-- Add form inline (desktop) -->
  <div class="add-form-card" style="margin-bottom:14px">
    <div class="sec" style="margin-bottom:14px"><div class="sec-dot"></div>Add Activity</div>
    <form method="POST">
      <?php echo addForm(''); ?>
    </form>
  </div>

  <!-- Chart -->
  <div class="card" style="margin-bottom:14px">
    <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:4px">
      <div class="sec" style="margin-bottom:0"><div class="sec-dot"></div>Last 7 days</div>
      <span style="font-weight:700;color:var(--green);font-size:.85rem"><?php echo $wkAvg; ?>%</span>
    </div>
    <div class="bar-chart">
      <?php
      $wkD=['M','T','W','T','F','S','S'];
      $todD=(int)date('N')-1;
      for($i=0;$i<7;$i++){
          $d=date('Y-m-d',strtotime('-'.(6-$i).' days'));
          $pv=isset($wkMap[$d])?(int)$wkMap[$d]:0;
          $td=($i===$todD)?'today':'';
          echo '<div class="bar-col"><div class="bar-fill '.$td.'" style="height:'.max(3,$pv).'%"></div><div class="bar-lbl">'.$wkD[$i].'</div></div>';
      }
      ?>
    </div>
    <div style="display:flex;justify-content:space-between;font-size:.75rem;color:var(--text2)">
      <span>Weekly avg</span><a href="analytics.php" style="color:var(--green);font-weight:600">More →</a>
    </div>
  </div>

  <?php if($plan['plan']==='free'): ?>
  <a href="pricing.php" class="upgrade-banner">
    <i class="fas fa-crown" style="font-size:1.1rem;flex-shrink:0"></i>
    <div><div style="font-weight:700;font-size:.9rem">Upgrade to Pro</div><div style="font-size:.74rem;opacity:.75">Unlimited activities + analytics</div></div>
    <i class="fas fa-chevron-right" style="font-size:.75rem;margin-left:auto"></i>
  </a>
  <?php endif; ?>
</div>
</div><!-- dash-grid -->

</div><!-- page -->
</div><!-- main-content -->
</div><!-- app -->

<!-- ADD MODAL (mobile / "New activity" button) -->
<div class="modal-bg" id="addM">
<div class="modal-sheet">
  <div class="modal-handle"></div>
  <div class="modal-title">New Activity <div class="modal-close" onclick="closeModal('addM')"><i class="fas fa-times"></i></div></div>
  <form method="POST">
    <?php echo addForm('M'); ?>
  </form>
</div>
</div>

<!-- EDIT MODAL -->
<div class="modal-bg" id="editM">
<div class="modal-sheet">
  <div class="modal-handle"></div>
  <div class="modal-title">Edit this day <div class="modal-close" onclick="closeModal('editM')"><i class="fas fa-times"></i></div></div>
  <div id="eRecNote" style="display:none;align-items:center;gap:8px;padding:10px 14px;background:rgba(245,158,11,.08);border:1px solid rgba(245,158,11,.2);border-radius:var(--r);font-size:.8rem;color:var(--amber);margin-bottom:14px">
    <i class="fas fa-info-circle"></i> Only this day changes — other occurrences are kept.
  </div>
  <form method="POST">
    <input type="hidden" name="edit_save" value="1">
    <input type="hidden" name="edit_id"   id="eId">
    <input type="hidden" name="rec_id"    id="eRid">
    <div class="f-group"><label class="f-label">Title</label><input type="text" name="edit_title" id="eTitle" class="f-input" required></div>
    <div class="f-group"><label class="f-label">Time</label>
      <div style="display:grid;grid-template-columns:1fr 1fr;gap:8px">
        <select name="edit_sh" id="eSh" class="f-select"><?php for($h=5;$h<=22;$h++) echo "<option value='{$h}'>".sprintf('%02d',$h).":00</option>"; ?></select>
        <select name="edit_eh" id="eEh" class="f-select"><?php for($h=6;$h<=23;$h++) echo "<option value='{$h}'>".sprintf('%02d',$h).":00</option>"; ?></select>
      </div>
    </div>
    <button type="submit" style="width:100%;padding:12px;background:var(--green);color:#000;border:none;border-radius:var(--r);font-weight:700;cursor:pointer"><i class="fas fa-save"></i> Save this day</button>
  </form>
</div>
</div>

<!-- DELETE CHOICE MODAL (for recurring: this day or all) -->
<div class="modal-bg" id="delM">
<div class="modal-sheet">
  <div class="modal-handle"></div>
  <div class="modal-title">Delete activity <div class="modal-close" onclick="closeModal('delM')"><i class="fas fa-times"></i></div></div>
  <p style="font-size:.85rem;color:var(--text2);margin-bottom:18px">This activity repeats weekly. What do you want to delete?</p>
  <form method="POST" style="display:flex;flex-direction:column;gap:10px">
    <input type="hidden" name="del_all_rec" id="dRid">
    <input type="hidden" id="dGid">
    <!-- Delete just today -->
    <button type="button" class="btn-outline" style="justify-content:center;border-radius:var(--r)"
      onclick="var f=document.createElement('form');f.method='POST';var i=document.createElement('input');i.type='hidden';i.name='del';i.value=document.getElementById('dGid').value;f.appendChild(i);document.body.appendChild(f);f.submit();">
      <i class="fas fa-calendar-day"></i> Delete only today
    </button>
    <!-- Delete all -->
    <button type="submit" style="padding:12px;background:var(--red);color:#fff;border:none;border-radius:var(--r);font-weight:700;cursor:pointer;display:flex;align-items:center;justify-content:center;gap:8px">
      <i class="fas fa-trash"></i> Delete ALL occurrences (stop repeating)
    </button>
  </form>
</div>
</div>

<?php echo getSharedJS(); ?>
</body></html>
