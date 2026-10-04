<?php
session_start();
require_once 'config.php';

if (!empty($_SESSION['uid'])) { header('Location: dashboard.php'); exit; }

$error = '';
$isReg = false;

if (isset($_POST['submit'])) {
    $email = trim(isset($_POST['email']) ? $_POST['email'] : '');
    $pw    = isset($_POST['pw']) ? $_POST['pw'] : '';
    $isReg = isset($_POST['reg']) && $_POST['reg'] === '1';

    if ($isReg) {
        $name = trim(isset($_POST['name']) ? $_POST['name'] : '');
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $error = 'Invalid email address.';
        } elseif (strlen($pw) < 6) {
            $error = 'Password must be at least 6 characters.';
        } elseif (strlen($name) < 2) {
            $error = 'Name must be at least 2 characters.';
        } else {
            $chk = getDB()->prepare('SELECT id FROM users WHERE email=?');
            $chk->execute(array($email));
            if ($chk->fetch()) {
                $error = 'This email is already registered.';
            } else {
                $hash = password_hash($pw, PASSWORD_DEFAULT);
                $ins  = getDB()->prepare('INSERT INTO users(name, email, password, created_at) VALUES(?,?,?, NOW())');
                $ins->execute(array($name, $email, $hash));
                
                $newId = (int) getDB()->lastInsertId();
                
                if ($newId > 0) {
                    $_SESSION['uid'] = $newId;
                    $_SESSION['new_user'] = true;
                    
                    // Email de bienvenue
                    $subject = 'Welcome to 1% - Start building discipline';
                    $message = '
                    <!DOCTYPE html>
                    <html>
                    <head><meta charset="UTF-8"><style>
                        body{font-family:sans-serif;background:#0a0a0a;color:#f2f4f7;padding:20px;}
                        .container{max-width:500px;margin:0 auto;background:#111;border:1px solid #22c55e;border-radius:20px;padding:30px;}
                        h1{color:#22c55e;font-size:24px;}
                        .btn{display:inline-block;background:#22c55e;color:#000;padding:12px 24px;border-radius:100px;text-decoration:none;font-weight:bold;margin-top:20px;}
                    </style></head>
                    <body>
                    <div class="container">
                        <h1>Welcome to 1%, '.htmlspecialchars($name).'! 👋</h1>
                        <p>You\'ve taken the first step toward becoming 1% better every day.</p>
                        <p>Start by adding your first activity on the dashboard.</p>
                        <a href="'.SITE_URL.'/dashboard.php" class="btn">Go to Dashboard</a>
                        <p style="margin-top:20px;color:#8a92a0;font-size:14px;">— The 1% Team</p>
                    </div>
                    </body>
                    </html>';
                    
                    sendMail($email, $subject, $message);
                    
                    header('Location: dashboard.php');
                    exit;
                } else {
                    $error = 'Error creating account. Please try again.';
                }
            }
        }
    } else {
        $stmt = getDB()->prepare('SELECT id, password FROM users WHERE email=?');
        $stmt->execute(array($email));
        $row  = $stmt->fetch();
        if ($row && password_verify($pw, $row['password'])) {
            $_SESSION['uid'] = (int) $row['id'];
            header('Location: dashboard.php');
            exit;
        } else {
            $error = 'Incorrect email or password.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=yes, viewport-fit=cover">
<title>1% — Sign in</title>
<link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;600;700;800;900&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
<style>
*{margin:0;padding:0;box-sizing:border-box;}
body{font-family:'DM Sans',sans-serif;background:#080c0a;color:#f0f3f1;min-height:100vh;display:flex;align-items:center;justify-content:center;padding:20px;}
body::before{content:'';position:fixed;inset:0;background:radial-gradient(ellipse at 30% 40%,rgba(34,197,94,.07),transparent 55%);pointer-events:none;}
.card{background:rgba(255,255,255,.04);border:1px solid rgba(255,255,255,.09);padding:32px 28px;border-radius:20px;width:100%;max-width:400px;position:relative;z-index:1;}
.logo-row{display:flex;align-items:center;justify-content:center;gap:9px;margin-bottom:20px;}
.logo-b{width:42px;height:42px;background:#22c55e;border-radius:11px;display:flex;align-items:center;justify-content:center;font-weight:900;font-size:1.05rem;color:#000;box-shadow:0 0 20px rgba(34,197,94,.32);}
.logo-n{font-size:1.75rem;font-weight:900;color:#22c55e;}
h1{font-size:1.3rem;font-weight:800;text-align:center;margin-bottom:4px;}
.sub{color:#7a8a7e;text-align:center;font-size:.86rem;margin-bottom:20px;}
.ig{margin-bottom:13px;position:relative;}
.ig i{position:absolute;left:12px;top:50%;transform:translateY(-50%);color:#4a5e50;font-size:.8rem;}
.ig input{width:100%;background:rgba(255,255,255,.05);border:1px solid rgba(255,255,255,.09);padding:11px 12px 11px 37px;border-radius:11px;color:#f0f3f1;font-family:inherit;font-size:.86rem;outline:none;transition:border-color .2s;}
.ig input:focus{border-color:rgba(34,197,94,.4);}
.ig input::placeholder{color:#3d4d41;}
.btn{width:100%;padding:12px;border-radius:11px;border:none;background:linear-gradient(135deg,#22c55e,#16a34a);color:#000;font-weight:700;font-size:.88rem;cursor:pointer;transition:all .2s;font-family:inherit;margin-top:5px;}
.btn:hover{transform:translateY(-1px);box-shadow:0 6px 20px rgba(34,197,94,.28);}
.err{background:rgba(239,68,68,.1);border:1px solid rgba(239,68,68,.2);color:#ef4444;padding:10px 12px;border-radius:10px;margin-bottom:14px;font-size:.84rem;text-align:center;}
.sw-row{text-align:center;margin-top:16px;color:#7a8a7e;font-size:.85rem;}
.sw-l{color:#22c55e;cursor:pointer;font-weight:600;}
.divider{border:none;border-top:1px solid rgba(255,255,255,.07);margin:16px 0;}
.back{display:block;text-align:center;color:#3d4d41;font-size:.78rem;transition:color .2s;}
.back:hover{color:#22c55e;}
#nameWrap{display:none;}
</style>
</head>
<body>
<div class="card">
  <div class="logo-row"><span class="logo-n">1%</span></div>
  <h1 id="ttl"><?php echo $isReg ? 'Create account' : 'Welcome back' ?></h1>
  <p class="sub">Build discipline. Not motivation.</p>

  <?php if ($error): ?><div class="err"><i class="fas fa-exclamation-circle"></i> <?php echo htmlspecialchars($error) ?></div><?php endif; ?>

  <form method="POST">
    <input type="hidden" name="submit" value="1">
    <input type="hidden" name="reg" id="regH" value="<?php echo $isReg ? '1' : '0' ?>">

    <div class="ig" id="nameWrap" style="display:<?php echo $isReg ? 'block' : 'none' ?>">
      <i class="fas fa-user"></i>
      <input type="text" name="name" placeholder="Your name" value="<?php echo htmlspecialchars(isset($_POST['name'])?$_POST['name']:'') ?>">
    </div>
    <div class="ig">
      <i class="fas fa-envelope"></i>
      <input type="email" name="email" placeholder="Email address" required value="<?php echo htmlspecialchars(isset($_POST['email'])?$_POST['email']:'') ?>">
    </div>
    <div class="ig">
      <i class="fas fa-lock"></i>
      <input type="password" name="pw" placeholder="Password" required>
    </div>
    <button type="submit" class="btn" id="btnTxt"><?php echo $isReg ? 'Create account' : 'Sign in' ?></button>
  </form>

  <div class="sw-row">
    <span id="swTxt"><?php echo $isReg ? 'Already have an account?' : 'New here?' ?></span>
    <span class="sw-l" onclick="toggle()"><?php echo $isReg ? 'Sign in' : 'Create account' ?></span>
  </div>
  <hr class="divider">
  <a href="index.php" class="back"><i class="fas fa-arrow-left"></i> Back to home</a>
</div>
<script>
var r = <?php echo $isReg ? 'true' : 'false' ?>;
function toggle() {
  r = !r;
  document.getElementById('nameWrap').style.display = r ? 'block' : 'none';
  document.getElementById('regH').value = r ? '1' : '0';
  document.getElementById('ttl').textContent = r ? 'Create account' : 'Welcome back';
  document.getElementById('btnTxt').textContent = r ? 'Create account' : 'Sign in';
  document.getElementById('swTxt').textContent = r ? 'Already have an account?' : 'New here?';
  document.querySelector('.sw-l').textContent = r ? 'Sign in' : 'Create account';
}
</script>
</body>
</html>