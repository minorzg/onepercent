<?php
require_once 'config.php';
checkLogin();

$uid  = (int)$_SESSION['uid'];
$u    = getUser($uid);
$plan = getUserPlan($u);

$mo = isset($_GET['m']) ? (int)$_GET['m'] : (int)date('n');
$yr = isset($_GET['y']) ? (int)$_GET['y'] : (int)date('Y');
$mo = max(1, min(12, $mo));

$prevM = $mo==1?12:$mo-1; $prevY = $mo==1?$yr-1:$yr;
$nextM = $mo==12?1:$mo+1; $nextY = $mo==12?$yr+1:$yr;
$monthNames=['','January','February','March','April','May','June','July','August','September','October','November','December'];

$start = "$yr-".str_pad($mo,2,'0',STR_PAD_LEFT)."-01";
$end   = date('Y-m-t',strtotime($start));

$cs = getDB()->prepare('SELECT DATE(date_created) d,COUNT(*) t,SUM(done) dn FROM goals WHERE user_id=? AND date_created BETWEEN ? AND ? GROUP BY DATE(date_created)');
$cs->execute([$uid,$start,$end]); $calData=[];
foreach($cs->fetchAll() as $r) $calData[$r['d']]=['t'=>(int)$r['t'],'dn'=>(int)$r['dn']];

$firstDow=(int)date('N',strtotime($start));
$dim=(int)date('t',strtotime($start));

/* Selected day */
$selDay = isset($_GET['d'])?(int)$_GET['d']:(int)date('j');
$selDate= "$yr-".str_pad($mo,2,'0',STR_PAD_LEFT)."-".str_pad($selDay,2,'0',STR_PAD_LEFT);

/* Actions on selected day */
if(isset($_POST['tog_cal'])){
    getDB()->prepare('UPDATE goals SET done=NOT done WHERE id=? AND user_id=?')->execute([(int)$_POST['tog_cal'],$uid]);
    header("Location: calendar.php?m=$mo&y=$yr&d=$selDay"); exit;
}
if(isset($_POST['del_cal'])){
    getDB()->prepare('DELETE FROM goals WHERE id=? AND user_id=?')->execute([(int)$_POST['del_cal'],$uid]);
    header("Location: calendar.php?m=$mo&y=$yr&d=$selDay"); exit;
}
if(isset($_POST['del_all_cal'])){
    $rid=$_POST['del_all_cal'];
    try{ getDB()->prepare('DELETE FROM goals WHERE recurrence_id=? AND user_id=?')->execute([$rid,$uid]); }catch(Exception $e){}
    header("Location: calendar.php?m=$mo&y=$yr&d=$selDay"); exit;
}
/* Add on calendar date */
if(isset($_POST['add_cal'])){
    $title=trim($_POST['title']??'');
    $sh=(int)($_POST['sh']??0);
    $eh=(int)($_POST['eh']??0);
    $diff=(int)($_POST['diff']??1);
    $cnt=getDB()->prepare('SELECT COUNT(*) FROM goals WHERE user_id=? AND date_created=?');
    $cnt->execute([$uid,$selDate]);
    if(!empty($title)&&$eh>$sh&&(int)$cnt->fetchColumn()<$plan['max_goals']){
        getDB()->prepare('INSERT INTO goals(user_id,title,hour_slot,end_hour,difficulty,repeat_daily,date_created)VALUES(?,?,?,?,?,0,?)')->execute([$uid,$title,$sh,$eh,$diff,$selDate]);
    }
    header("Location: calendar.php?m=$mo&y=$yr&d=$selDay"); exit;
}

$dq=getDB()->prepare('SELECT * FROM goals WHERE user_id=? AND date_created=? ORDER BY hour_slot');
$dq->execute([$uid,$selDate]); $dayGoals=$dq->fetchAll();
$diffS=['★','★★','★★★','★★★★'];
$diffC=['var(--text3)','var(--green)','var(--amber)','var(--red)'];
?>
<!DOCTYPE html><html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=yes, viewport-fit=cover">
<title>1% — Planning</title>
<?php echo getSharedCSS(); ?>
<style>
.page-sup{font-size:.78rem;color:var(--text3);text-transform:uppercase;letter-spacing:.5px;margin-bottom:4px;}
.cal-wrap{display:grid;grid-template-columns:1fr 300px;gap:20px;}
@media(max-width:900px){.cal-wrap{grid-template-columns:1fr;}}
.cal-card{background:var(--card);border:1px solid var(--card-b);border-radius:var(--rl);padding:20px;}
.cal-header{display:flex;align-items:center;justify-content:space-between;margin-bottom:16px;}
.cal-month{font-size:1.1rem;font-weight:800;}
.cal-nav{width:34px;height:34px;border-radius:var(--r);background:var(--bg);border:1px solid var(--card-b);display:flex;align-items:center;justify-content:center;color:var(--text2);text-decoration:none;transition:all .15s;}
.cal-nav:hover{border-color:var(--green-border);color:var(--green);}
.cal-dow-row{display:grid;grid-template-columns:repeat(7,1fr);gap:3px;margin-bottom:6px;}
.cal-dow{text-align:center;font-size:.65rem;font-weight:700;color:var(--text3);text-transform:uppercase;padding:4px 0;}
.cal-grid{display:grid;grid-template-columns:repeat(7,1fr);gap:3px;}
.cal-cell{aspect-ratio:1;display:flex;flex-direction:column;align-items:center;justify-content:center;border-radius:8px;cursor:pointer;position:relative;transition:all .15s;border:1px solid transparent;font-size:.82rem;font-weight:600;text-decoration:none;color:var(--text);}
.cal-cell:hover{background:var(--card-h);}
.cal-cell.today{border-color:var(--green-border);color:var(--green);}
.cal-cell.selected{background:var(--green);color:#000 !important;border-color:var(--green);}
.cal-cell.empty{cursor:default;opacity:0;pointer-events:none;}
.cal-dot{width:5px;height:5px;border-radius:50%;margin-top:2px;}
.cal-dot.ok{background:var(--green);}
.cal-dot.partial{background:var(--amber);}
.cal-dot.none{background:var(--card-b);}
.day-panel{background:var(--card);border:1px solid var(--card-b);border-radius:var(--rl);padding:18px;}
.day-panel-title{font-weight:800;font-size:1rem;margin-bottom:14px;}
.dg-row{display:flex;align-items:center;gap:8px;padding:9px 10px;border:1px solid var(--card-b);border-radius:var(--r);margin-bottom:7px;background:var(--bg);transition:border-color .15s;}
.dg-row:hover{border-color:var(--green-border);}
.dg-row.done{opacity:.45;}
.dg-time{font-weight:700;font-size:.74rem;color:var(--green);width:52px;flex-shrink:0;line-height:1.3;}
.dg-name{font-size:.82rem;flex:1;font-weight:500;}
.dg-row.done .dg-name{text-decoration:line-through;color:var(--text3);}
.dg-acts{display:flex;gap:4px;flex-shrink:0;}
.dg-btn{width:27px;height:27px;border-radius:7px;border:1px solid var(--card-b);background:transparent;color:var(--text2);display:flex;align-items:center;justify-content:center;cursor:pointer;font-size:.66rem;transition:all .15s;}
.dg-btn:hover{color:var(--green);border-color:var(--green-border);}
.dg-btn.chk{background:var(--green);color:#000;border-color:var(--green);}
.dg-btn.del:hover{color:var(--red);border-color:rgba(239,68,68,.3);}
.add-cal-form{border-top:1px solid var(--card-b);margin-top:14px;padding-top:14px;}
</style>
</head>
<body>
<div class="app">
<?php echo getNav('calendar',$u,$plan); ?>
<div class="main-content">
<?php echo getBottomNav('calendar'); ?>
<div class="page">

<div style="margin-bottom:20px">
  <div class="page-sup">Planning</div>
  <div style="font-size:1.5rem;font-weight:800;letter-spacing:-.5px">Calendar</div>
</div>

<?php if($plan['plan']==='free'): ?>
<div style="background:linear-gradient(135deg,rgba(245,158,11,.08),transparent);border:1px solid rgba(245,158,11,.2);border-radius:var(--rl);padding:24px;text-align:center">
  <div style="font-size:1.1rem;font-weight:700;margin-bottom:8px">🔒 Calendar — Pro feature</div>
  <p style="color:var(--text2);font-size:.85rem;margin-bottom:16px">Plan ahead and visualize your month.</p>
  <a href="pricing.php" class="btn-green"><i class="fas fa-rocket"></i> Upgrade to Pro</a>
</div>
<?php else: ?>

<div class="cal-wrap">
  <!-- CALENDAR -->
  <div class="cal-card">
    <div class="cal-header">
      <a href="?m=<?php echo $prevM; ?>&y=<?php echo $prevY; ?>" class="cal-nav"><i class="fas fa-chevron-left"></i></a>
      <div class="cal-month"><?php echo $monthNames[$mo].' '.$yr; ?></div>
      <a href="?m=<?php echo $nextM; ?>&y=<?php echo $nextY; ?>" class="cal-nav"><i class="fas fa-chevron-right"></i></a>
    </div>
    <div class="cal-dow-row">
      <?php foreach(['MON','TUE','WED','THU','FRI','SAT','SUN'] as $d): ?>
      <div class="cal-dow"><?php echo $d; ?></div>
      <?php endforeach; ?>
    </div>
    <div class="cal-grid">
      <?php
      for($i=1;$i<$firstDow;$i++) echo '<div class="cal-cell empty"></div>';
      $todayStr=date('Y-m-d');
      for($day=1;$day<=$dim;$day++){
          $ds="$yr-".str_pad($mo,2,'0',STR_PAD_LEFT)."-".str_pad($day,2,'0',STR_PAD_LEFT);
          $isTod=($ds===$todayStr);
          $isSel=((int)$selDay===$day);
          $dat=$calData[$ds]??null;
          $dotCls='';
          if($dat){ $r=$dat['t']>0?$dat['dn']/$dat['t']:0; $dotCls=$r>=1?'ok':($r>0?'partial':'none'); }
          $cls=($isTod?' today':'').($isSel?' selected':'');
          echo "<a href='?m={$mo}&y={$yr}&d={$day}' class='cal-cell{$cls}'>{$day}";
          if($dat) echo "<div class='cal-dot {$dotCls}'></div>";
          echo "</a>";
      }
      ?>
    </div>
    <div style="display:flex;gap:14px;margin-top:14px;font-size:.72rem;color:var(--text2);justify-content:center">
      <span><span style="display:inline-block;width:8px;height:8px;border-radius:50%;background:var(--green);margin-right:4px"></span>100%</span>
      <span><span style="display:inline-block;width:8px;height:8px;border-radius:50%;background:var(--amber);margin-right:4px"></span>Partial</span>
      <span><span style="display:inline-block;width:8px;height:8px;border-radius:50%;background:var(--card-b);margin-right:4px"></span>0%</span>
    </div>
  </div>

  <!-- DAY PANEL -->
  <div class="day-panel">
    <div class="day-panel-title">
      • <?php echo date('l j', strtotime($selDate)).' '.$monthNames[$mo]; ?>
    </div>

    <?php if(empty($dayGoals)): ?>
    <div style="text-align:center;padding:28px 10px;color:var(--text3)">
      <div style="font-size:1.8rem;opacity:.2;margin-bottom:8px">📅</div>
      <p style="font-size:.82rem">No activities this day</p>
    </div>
    <?php else: ?>
    <?php foreach($dayGoals as $g):
      $gid=(int)$g['id'];
      $isRec=!empty($g['recurrence_id']);
      $rid=$g['recurrence_id']??'';
      $di=min(3,(int)($g['difficulty']??1));
    ?>
    <div class="dg-row <?php echo $g['done']?'done':''; ?>">
      <div class="dg-time"><?php echo sprintf('%02d',(int)$g['hour_slot']); ?>h<br><?php echo sprintf('%02d',(int)$g['end_hour']); ?>h</div>
      <div class="dg-name"><?php echo htmlspecialchars($g['title']); ?></div>
      <span style="font-size:.62rem;color:<?php echo $diffC[$di]; ?>;margin-right:2px"><?php echo $diffS[$di]; ?></span>
      <div class="dg-acts">
        <!-- Toggle -->
        <form method="POST" style="display:contents">
          <input type="hidden" name="tog_cal" value="<?php echo $gid; ?>">
          <button class="dg-btn <?php echo $g['done']?'chk':''; ?>" title="<?php echo $g['done']?'Undo':'Done'; ?>">
            <i class="fas <?php echo $g['done']?'fa-undo':'fa-check'; ?>"></i>
          </button>
        </form>
        <!-- Delete -->
        <?php if($isRec&&!empty($rid)): ?>
        <button class="dg-btn del" title="Delete"
          onclick="document.getElementById('cDRid').value='<?php echo htmlspecialchars($rid); ?>';document.getElementById('cDGid').value='<?php echo $gid; ?>';openModal('calDelM');">
          <i class="fas fa-trash"></i>
        </button>
        <?php else: ?>
        <form method="POST" style="display:contents" onsubmit="return confirm('Delete?')">
          <input type="hidden" name="del_cal" value="<?php echo $gid; ?>">
          <button class="dg-btn del" title="Delete"><i class="fas fa-trash"></i></button>
        </form>
        <?php endif; ?>
      </div>
    </div>
    <?php endforeach; ?>
    <?php endif; ?>

    <!-- ADD on this date -->
    <div class="add-cal-form">
      <div style="font-size:.75rem;font-weight:700;color:var(--text2);margin-bottom:10px;display:flex;align-items:center;gap:6px"><i class="fas fa-plus" style="color:var(--green)"></i> Add activity</div>
      <form method="POST">
        <input type="hidden" name="add_cal" value="1">
        <div class="f-group">
          <input type="text" name="title" class="f-input" placeholder="Activity name..." required>
        </div>
        <div style="display:grid;grid-template-columns:1fr 1fr;gap:8px;margin-bottom:10px">
          <select name="sh" class="f-select">
            <option value="">Start</option>
            <?php for($h=5;$h<=22;$h++) echo "<option value='$h'>".sprintf('%02d',$h).":00</option>"; ?>
          </select>
          <select name="eh" class="f-select">
            <option value="">End</option>
            <?php for($h=6;$h<=23;$h++) echo "<option value='$h'>".sprintf('%02d',$h).":00</option>"; ?>
          </select>
        </div>
        <button type="submit" class="btn-green" style="width:100%;justify-content:center;border-radius:var(--r)">
          <i class="fas fa-plus"></i> Add activity
        </button>
      </form>
    </div>
  </div>
</div>

<!-- DELETE CHOICE MODAL -->
<div class="modal-bg" id="calDelM">
<div class="modal-sheet">
  <div class="modal-handle"></div>
  <div class="modal-title">Delete activity <div class="modal-close" onclick="closeModal('calDelM')"><i class="fas fa-times"></i></div></div>
  <p style="font-size:.85rem;color:var(--text2);margin-bottom:18px">This activity repeats weekly. What do you want to delete?</p>
  <input type="hidden" id="cDRid">
  <input type="hidden" id="cDGid">
  <div style="display:flex;flex-direction:column;gap:10px">
    <button type="button" class="btn-outline" style="justify-content:center;border-radius:var(--r)"
      onclick="var f=document.createElement('form');f.method='POST';var i=document.createElement('input');i.type='hidden';i.name='del_cal';i.value=document.getElementById('cDGid').value;f.appendChild(i);document.body.appendChild(f);f.submit();">
      <i class="fas fa-calendar-day"></i> Delete only this day
    </button>
    <button type="button" style="padding:12px;background:var(--red);color:#fff;border:none;border-radius:var(--r);font-weight:700;cursor:pointer;display:flex;align-items:center;justify-content:center;gap:8px"
      onclick="var f=document.createElement('form');f.method='POST';var i=document.createElement('input');i.type='hidden';i.name='del_all_cal';i.value=document.getElementById('cDRid').value;f.appendChild(i);document.body.appendChild(f);f.submit();">
      <i class="fas fa-trash"></i> Delete ALL occurrences
    </button>
  </div>
</div>
</div>

<?php endif; ?>

</div>
</div>
</div>
<?php echo getSharedJS(); ?>
</body></html>
