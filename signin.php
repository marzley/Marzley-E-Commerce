<?php
<?php
session_start();
require_once __DIR__ . '/mtkenya.php';

function h($s){ return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8'); }

$signin_msg = '';
$signin_ok = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = filter_var(trim($_POST['email'] ?? ''), FILTER_VALIDATE_EMAIL);
    $password = $_POST['password'] ?? '';
    $remember = isset($_POST['remember']);

    if (!$email || $password === '') {
        $signin_msg = 'Please fill both fields.';
    } else {
        $rows = db_query('SELECT id, username, password_hash FROM users WHERE email = ? LIMIT 1', 's', [$email]);
        if ($rows === false || count($rows) === 0) {
            $signin_msg = 'Invalid credentials.';
        } else {
            $row = $rows[0];
            if (password_verify($password, $row['password_hash'])) {
                // success: set session
                $_SESSION['user'] = ['id' => (int)$row['id'], 'email' => $email, 'username' => $row['username']];
                $signin_ok = true;
                // remember cookie (optional, demo)
                if ($remember) {
                    setcookie('remember_user', json_encode(['email'=>$email,'username'=>$row['username']]), time()+60*60*24*30, '/');
                } else {
                    setcookie('remember_user', '', time()-3600, '/');
                }
                // redirect to homepage
                header('Location: home.html');
                exit;
            } else {
                $signin_msg = 'Invalid credentials.';
            }
        }
    }
}
?><!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width,initial-scale=1" />
  <title>Sign In — Marzley Shop</title>

  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700&display=swap" rel="stylesheet">

  <style>
    :root{
      --bg: linear-gradient(135deg,#eaf3ff 0,#eef7fb 100%);
      --brand:#21334a;
      --accent:#e67e22;
      --card:#fff;
      --muted:#6b7785;
      --shadow:0 10px 30px rgba(20,40,60,0.06);
      --radius:12px;
      --max:980px;
      --danger:#e05b5b;
      --ok:#133f1e;
    }
    *{box-sizing:border-box}
    body{margin:0;font-family:Inter,system-ui,Segoe UI,Roboto,Arial,sans-serif;background:var(--bg);color:#162029;-webkit-font-smoothing:antialiased}
    .container{max-width:var(--max);margin:0 auto;padding:20px}

    header{background:var(--brand);color:#fff;padding:.9rem 0;position:sticky;top:0;z-index:1200;box-shadow:0 6px 20px rgba(10,20,30,0.06)}
    .top{max-width:var(--max);margin:0 auto;padding:0 18px;display:flex;align-items:center;justify-content:space-between}
    .logo{font-weight:800;color:var(--accent);font-size:1.15rem}
    .nav-links{display:flex;gap:12px;list-style:none;margin:0;padding:0;align-items:center}
    .nav-links a{color:#fff;text-decoration:none;padding:8px 10px;border-radius:8px;display:inline-flex;gap:8px;align-items:center;font-weight:600}
    .nav-toggle{display:none;background:transparent;border:0;color:#fff;font-size:1.15rem;padding:6px;cursor:pointer}
    @media(max-width:900px){ .nav-toggle{display:inline-block} .nav-links{position:absolute;left:12px;right:12px;top:64px;background:var(--brand);flex-direction:column;padding:12px;gap:8px;border-radius:8px;display:none} .nav-links.show{display:flex} }

    .card{background:var(--card);border-radius:var(--radius);box-shadow:var(--shadow);padding:26px;display:grid;grid-template-columns:1fr 360px;gap:20px;align-items:center;margin:32px 0}
    @media(max-width:880px){ .card{grid-template-columns:1fr;padding:18px} }

    .left{padding:12px}
    .left h1{margin:0 0 10px;font-size:1.6rem}
    .left p{margin:0 0 12px;color:var(--muted)}

    .right{background:linear-gradient(180deg,#fff,#fbfcff);padding:18px;border-radius:10px;border:1px solid #f1f6fb}
    .field{position:relative;margin-bottom:10px}
    .field input{width:100%;padding:.8rem 1rem .8rem 40px;border-radius:10px;border:1px solid #e8f1fb;background:transparent}
    .field .ico{position:absolute;left:12px;top:50%;transform:translateY(-50%);color:var(--muted)}
    .small{font-size:.92rem;color:var(--muted)}

    .accent-btn{background:var(--accent);color:#fff;border:0;padding:.75rem 1rem;border-radius:10px;font-weight:800;cursor:pointer;width:100%}
    .ghost{background:transparent;border:1px solid #e7eff8;padding:.6rem .85rem;border-radius:8px;cursor:pointer;width:100%}

    .row{display:flex;gap:8px}
    .remember{display:flex;align-items:center;gap:8px;color:var(--muted);font-size:.95rem}

    .links{display:flex;gap:8px;flex-wrap:wrap;margin-top:12px}
    .links a{color:var(--accent);text-decoration:none;font-weight:700}

    .toast{position:fixed;right:18px;bottom:18px;background:#24313a;color:#fff;padding:12px 16px;border-radius:10px;box-shadow:0 12px 30px rgba(0,0,0,0.18);display:none;z-index:2200}
    .toast.show{display:block;animation:pop .28s ease}
    @keyframes pop{from{transform:translateY(8px);opacity:0}to{transform:none;opacity:1}}

    footer{padding:18px 0;text-align:center;color:#fff;background:linear-gradient(135deg,#0b2a3a 0,#2b5870 100%);margin-top:12px;border-radius:6px}
    .msg {font-weight:700;margin-top:8px}
    .msg.error{color:var(--danger)}
    .msg.ok{color:var(--ok)}
  </style>
</head>
<body>
  <header>
    <div class="top">
      <div style="display:flex;align-items:center;gap:12px">
        <button class="nav-toggle" aria-label="Toggle menu"><i class="fas fa-bars"></i></button>
        <div class="logo">Marzley Shop</div>
      </div>

      <ul class="nav-links" role="menu" aria-label="Main navigation">
        <li><a href="home.html"><i class="fas fa-home"></i> Home</a></li>
        <li><a href="product.html"><i class="fas fa-box"></i> Products</a></li>
        <li><a href="about us.html"><i class="fas fa-info-circle"></i> About</a></li>
        <li><a href="index.php"><i class="fas fa-user"></i> Account</a></li>
        <li><a href="admin.html"><i class="fas fa-user-shield"></i> Admin</a></li>
      </ul>
    </div>
  </header>

  <main class="container">
    <div class="card">
      <div class="left">
        <h1>Sign in to your account</h1>
        <p class="small">Access your saved carts, track orders and manage your profile. Secure server-side authentication against the users table.</p>

        <div style="display:grid;grid-template-columns:repeat(2,1fr);gap:8px;margin-top:14px">
          <div style="background:#fff;padding:12px;border-radius:10px;box-shadow:0 8px 30px rgba(20,40,60,0.04)">
            <div style="font-weight:800">New here?</div>
            <div class="small" style="margin-top:6px">Create an account to get personalized offers and order history.</div>
            <div style="margin-top:10px"><a class="ghost" href="index.php"><i class="fas fa-user-plus"></i> Create account</a></div>
          </div>

          <div style="background:#fff;padding:12px;border-radius:10px;box-shadow:0 8px 30px rgba(20,40,60,0.04)">
            <div style="font-weight:800">Need help?</div>
            <div class="small" style="margin-top:6px">Contact support or recover a forgotten password.</div>
            <div style="margin-top:10px"><a class="ghost" href="index.php#recover"><i class="fas fa-key"></i> Recover</a></div>
          </div>
        </div>

        <div class="links" aria-hidden="false">
          <a href="https://www.linkedin.com/in/kelvin-githeru-wanyoike" target="_blank" rel="noopener"><i class="fab fa-linkedin"></i> Marzley</a>
          <a href="https://github.com/kelvinwanyoike" target="_blank" rel="noopener"><i class="fab fa-github"></i> GitHub</a>
        </div>
      </div>

      <div class="right" role="region" aria-label="Sign in form">
        <form id="signin-form" method="post" action="signin.php" autocomplete="off" novalidate>
          <div class="field">
            <i class="ico fas fa-envelope"></i>
            <input id="signin-email" name="email" type="email" placeholder="Email address" required value="<?php echo h($_POST['email'] ?? ($_COOKIE['remember_user'] ? json_decode($_COOKIE['remember_user'], true)['email'] ?? '' : '')); ?>">
          </div>

          <div class="field">
            <i class="ico fas fa-lock"></i>
            <input id="signin-password" name="password" type="password" placeholder="Password" required>
            <button type="button" id="toggle-pwd" title="Show password" style="position:absolute;right:10px;background:transparent;border:0;color:var(--muted)"><i class="fas fa-eye"></i></button>
          </div>

          <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:8px">
            <label class="remember"><input id="remember" name="remember" type="checkbox" <?php echo isset($_COOKIE['remember_user']) ? 'checked' : ''; ?>> Remember me</label>
            <a href="index.php#recover" class="small">Forgot password?</a>
          </div>

          <button class="accent-btn" type="submit"><i class="fas fa-sign-in-alt"></i> Sign In</button>
          <button type="button" id="demo-signin" class="ghost" style="margin-top:8px"><i class="fas fa-user-secret"></i> Demo sign-in</button>

          <div id="signin-msg" class="msg <?php echo $signin_ok ? 'ok' : ($signin_msg ? 'error' : ''); ?>"><?php echo h($signin_msg); ?></div>
        </form>
      </div>
    </div>

    <footer>
      &copy; 2025 Marzley Shop — Marzley Tech Solutions
    </footer>
  </main>

  <div id="toast" class="toast" role="status" aria-live="polite"></div>

  <script>
    // nav toggle
    (function(){
      const t = document.querySelector('.nav-toggle'), links = document.querySelector('.nav-links');
      if (!t || !links) return;
      t.addEventListener('click', ()=> links.classList.toggle('show'));
      window.addEventListener('resize', ()=> { if (window.innerWidth>900) links.classList.remove('show'); });
    })();

    // password toggle
    document.getElementById('toggle-pwd')?.addEventListener('click', function(){
      const inp = document.getElementById('signin-password');
      if (!inp) return;
      const shown = inp.type === 'text';
      inp.type = shown ? 'password' : 'text';
      this.innerHTML = shown ? '<i class="fas fa-eye"></i>' : '<i class="fas fa-eye-slash"></i>';
    });

    // demo sign-in: attempt to POST demo credentials to server-side sign-in endpoint (creates no DB user)
    document.getElementById('demo-signin').addEventListener('click', function(){
      // client side: redirect to account registration for demo creation if no users exist
      window.location.href = 'index.php';
    });

    // accessibility: Esc closes nav
    document.addEventListener('keydown', function(e){ if (e.key === 'Escape') document.querySelectorAll('.nav-links').forEach(n=>n.classList.remove('show')); });
  </script>
</body>
</html>
```// filepath: c:\xampp\htdocs\911\Marzley\signin.php
<?php
session_start();
require_once __DIR__ . '/mtkenya.php';

function h($s){ return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8'); }

$signin_msg = '';
$signin_ok = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = filter_var(trim($_POST['email'] ?? ''), FILTER_VALIDATE_EMAIL);
    $password = $_POST['password'] ?? '';
    $remember = isset($_POST['remember']);

    if (!$email || $password === '') {
        $signin_msg = 'Please fill both fields.';
    } else {
        $rows = db_query('SELECT id, username, password_hash FROM users WHERE email = ? LIMIT 1', 's', [$email]);
        if ($rows === false || count($rows) === 0) {
            $signin_msg = 'Invalid credentials.';
        } else {
            $row = $rows[0];
            if (password_verify($password, $row['password_hash'])) {
                // success: set session
                $_SESSION['user'] = ['id' => (int)$row['id'], 'email' => $email, 'username' => $row['username']];
                $signin_ok = true;
                // remember cookie (optional, demo)
                if ($remember) {
                    setcookie('remember_user', json_encode(['email'=>$email,'username'=>$row['username']]), time()+60*60*24*30, '/');
                } else {
                    setcookie('remember_user', '', time()-3600, '/');
                }
                // redirect to homepage
                header('Location: home.html');
                exit;
            } else {
                $signin_msg = 'Invalid credentials.';
            }
        }
    }
}
?><!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width,initial-scale=1" />
  <title>Sign In — Marzley Shop</title>

  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700&display=swap" rel="stylesheet">

  <style>
    :root{
      --bg: linear-gradient(135deg,#eaf3ff 0,#eef7fb 100%);
      --brand:#21334a;
      --accent:#e67e22;
      --card:#fff;
      --muted:#6b7785;
      --shadow:0 10px 30px rgba(20,40,60,0.06);
      --radius:12px;
      --max:980px;
      --danger:#e05b5b;
      --ok:#133f1e;
    }
    *{box-sizing:border-box}
    body{margin:0;font-family:Inter,system-ui,Segoe UI,Roboto,Arial,sans-serif;background:var(--bg);color:#162029;-webkit-font-smoothing:antialiased}
    .container{max-width:var(--max);margin:0 auto;padding:20px}

    header{background:var(--brand);color:#fff;padding:.9rem 0;position:sticky;top:0;z-index:1200;box-shadow:0 6px 20px rgba(10,20,30,0.06)}
    .top{max-width:var(--max);margin:0 auto;padding:0 18px;display:flex;align-items:center;justify-content:space-between}
    .logo{font-weight:800;color:var(--accent);font-size:1.15rem}
    .nav-links{display:flex;gap:12px;list-style:none;margin:0;padding:0;align-items:center}
    .nav-links a{color:#fff;text-decoration:none;padding:8px 10px;border-radius:8px;display:inline-flex;gap:8px;align-items:center;font-weight:600}
    .nav-toggle{display:none;background:transparent;border:0;color:#fff;font-size:1.15rem;padding:6px;cursor:pointer}
    @media(max-width:900px){ .nav-toggle{display:inline-block} .nav-links{position:absolute;left:12px;right:12px;top:64px;background:var(--brand);flex-direction:column;padding:12px;gap:8px;border-radius:8px;display:none} .nav-links.show{display:flex} }

    .card{background:var(--card);border-radius:var(--radius);box-shadow:var(--shadow);padding:26px;display:grid;grid-template-columns:1fr 360px;gap:20px;align-items:center;margin:32px 0}
    @media(max-width:880px){ .card{grid-template-columns:1fr;padding:18px} }

    .left{padding:12px}
    .left h1{margin:0 0 10px;font-size:1.6rem}
    .left p{margin:0 0 12px;color:var(--muted)}

    .right{background:linear-gradient(180deg,#fff,#fbfcff);padding:18px;border-radius:10px;border:1px solid #f1f6fb}
    .field{position:relative;margin-bottom:10px}
    .field input{width:100%;padding:.8rem 1rem .8rem 40px;border-radius:10px;border:1px solid #e8f1fb;background:transparent}
    .field .ico{position:absolute;left:12px;top:50%;transform:translateY(-50%);color:var(--muted)}
    .small{font-size:.92rem;color:var(--muted)}

    .accent-btn{background:var(--accent);color:#fff;border:0;padding:.75rem 1rem;border-radius:10px;font-weight:800;cursor:pointer;width:100%}
    .ghost{background:transparent;border:1px solid #e7eff8;padding:.6rem .85rem;border-radius:8px;cursor:pointer;width:100%}

    .row{display:flex;gap:8px}
    .remember{display:flex;align-items:center;gap:8px;color:var(--muted);font-size:.95rem}

    .links{display:flex;gap:8px;flex-wrap:wrap;margin-top:12px}
    .links a{color:var(--accent);text-decoration:none;font-weight:700}

    .toast{position:fixed;right:18px;bottom:18px;background:#24313a;color:#fff;padding:12px 16px;border-radius:10px;box-shadow:0 12px 30px rgba(0,0,0,0.18);display:none;z-index:2200}
    .toast.show{display:block;animation:pop .28s ease}
    @keyframes pop{from{transform:translateY(8px);opacity:0}to{transform:none;opacity:1}}

    footer{padding:18px 0;text-align:center;color:#fff;background:linear-gradient(135deg,#0b2a3a 0,#2b5870 100%);margin-top:12px;border-radius:6px}
    .msg {font-weight:700;margin-top:8px}
    .msg.error{color:var(--danger)}
    .msg.ok{color:var(--ok)}
  </style>
</head>
<body>
  <header>
    <div class="top">
      <div style="display:flex;align-items:center;gap:12px">
        <button class="nav-toggle" aria-label="Toggle menu"><i class="fas fa-bars"></i></button>
        <div class="logo">Marzley Shop</div>
      </div>

      <ul class="nav-links" role="menu" aria-label="Main navigation">
        <li><a href="home.html"><i class="fas fa-home"></i> Home</a></li>
        <li><a href="product.html"><i class="fas fa-box"></i> Products</a></li>
        <li><a href="about us.html"><i class="fas fa-info-circle"></i> About</a></li>
        <li><a href="index.php"><i class="fas fa-user"></i> Account</a></li>
        <li><a href="admin.html"><i class="fas fa-user-shield"></i> Admin</a></li>
      </ul>
    </div>
  </header>

  <main class="container">
    <div class="card">
      <div class="left">
        <h1>Sign in to your account</h1>
        <p class="small">Access your saved carts, track orders and manage your profile. Secure server-side authentication against the users table.</p>

        <div style="display:grid;grid-template-columns:repeat(2,1fr);gap:8px;margin-top:14px">
          <div style="background:#fff;padding:12px;border-radius:10px;box-shadow:0 8px 30px rgba(20,40,60,0.04)">
            <div style="font-weight:800">New here?</div>
            <div class="small" style="margin-top:6px">Create an account to get personalized offers and order history.</div>
            <div style="margin-top:10px"><a class="ghost" href="index.php"><i class="fas fa-user-plus"></i> Create account</a></div>
          </div>

          <div style="background:#fff;padding:12px;border-radius:10px;box-shadow:0 8px 30px rgba(20,40,60,0.04)">
            <div style="font-weight:800">Need help?</div>
            <div class="small" style="margin-top:6px">Contact support or recover a forgotten password.</div>
            <div style="margin-top:10px"><a class="ghost" href="index.php#recover"><i class="fas fa-key"></i> Recover</a></div>
          </div>
        </div>

        <div class="links" aria-hidden="false">
          <a href="https://www.linkedin.com/in/kelvin-githeru-wanyoike" target="_blank" rel="noopener"><i class="fab fa-linkedin"></i> Marzley</a>
          <a href="https://github.com/kelvinwanyoike" target="_blank" rel="noopener"><i class="fab fa-github"></i> GitHub</a>
        </div>
      </div>

      <div class="right" role="region" aria-label="Sign in form">
        <form id="signin-form" method="post" action="signin.php" autocomplete="off" novalidate>
          <div class="field">
            <i class="ico fas fa-envelope"></i>
            <input id="signin-email" name="email" type="email" placeholder="Email address" required value="<?php echo h($_POST['email'] ?? ($_COOKIE['remember_user'] ? json_decode($_COOKIE['remember_user'], true)['email'] ?? '' : '')); ?>">
          </div>

          <div class="field">
            <i class="ico fas fa-lock"></i>
            <input id="signin-password" name="password" type="password" placeholder="Password" required>
            <button type="button" id="toggle-pwd" title="Show password" style="position:absolute;right:10px;background:transparent;border:0;color:var(--muted)"><i class="fas fa-eye"></i></button>
          </div>

          <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:8px">
            <label class="remember"><input id="remember" name="remember" type="checkbox" <?php echo isset($_COOKIE['remember_user']) ? 'checked' : ''; ?>> Remember me</label>
            <a href="index.php#recover" class="small">Forgot password?</a>
          </div>

          <button class="accent-btn" type="submit"><i class="fas fa-sign-in-alt"></i> Sign In</button>
          <button type="button" id="demo-signin" class="ghost" style="margin-top:8px"><i class="fas fa-user-secret"></i> Demo sign-in</button>

          <div id="signin-msg" class="msg <?php echo $signin_ok ? 'ok' : ($signin_msg ? 'error' : ''); ?>"><?php echo h($signin_msg); ?></div>
        </form>
      </div>
    </div>

    <footer>
      &copy; 2025 Marzley Shop — Marzley Tech Solutions
    </footer>
  </main>

  <div id="toast" class="toast" role="status" aria-live="polite"></div>

  <script>
    // nav toggle
    (function(){
      const t = document.querySelector('.nav-toggle'), links = document.querySelector('.nav-links');
      if (!t || !links) return;
      t.addEventListener('click', ()=> links.classList.toggle('show'));
      window.addEventListener('resize', ()=> { if (window.innerWidth>900) links.classList.remove('show'); });
    })();

    // password toggle
    document.getElementById('toggle-pwd')?.addEventListener('click', function(){
      const inp = document.getElementById('signin-password');
      if (!inp) return;
      const shown = inp.type === 'text';
      inp.type = shown ? 'password' : 'text';
      this.innerHTML = shown ? '<i class="fas fa-eye"></i>' : '<i class="fas fa-eye-slash"></i>';
    });

    // demo sign-in: attempt to POST demo credentials to server-side sign-in endpoint (creates no DB user)
    document.getElementById('demo-signin').addEventListener('click', function(){
      // client side: redirect to account registration for demo creation if no users exist
      window.location.href = 'index.php';
    });

    // accessibility: Esc closes nav
    document.addEventListener('keydown', function(e){ if (e.key === 'Escape') document.querySelectorAll('.nav-links').forEach(n=>n.classList.remove('show'));