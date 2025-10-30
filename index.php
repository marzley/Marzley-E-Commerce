<?php
session_start();
require_once __DIR__ . '/mtkenya.php'; // expects mtkenya.php in same folder

$reg_msg = '';
$login_msg = '';
$recover_msg = '';
$users_count = 0;
$active_sessions = isset($_SESSION['user']) ? 1 : 0;

// helper to safely output
function h($s){ return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8'); }

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'register') {
        $email = filter_var(trim($_POST['email'] ?? ''), FILTER_VALIDATE_EMAIL);
        $username = trim($_POST['username'] ?? '');
        $phone = trim($_POST['phone'] ?? '');
        $password = $_POST['password'] ?? '';

        if (!$email || $username === '' || $password === '') {
            $reg_msg = 'Please fill required fields.';
        } else {
            // check existing
            $exists = db_query('SELECT id FROM users WHERE email = ?', 's', [$email]);
            if ($exists === false) {
                $reg_msg = 'Server error checking email.';
            } elseif (count($exists) > 0) {
                $reg_msg = 'Email already registered.';
            } else {
                $hash = password_hash($password, PASSWORD_DEFAULT);
                $res = db_query('INSERT INTO users (email, username, phone, password_hash) VALUES (?,?,?,?)', 'ssss', [$email, $username, $phone, $hash]);
                if ($res === false) {
                    $reg_msg = 'Registration failed, try again.';
                } else {
                    $reg_msg = 'Account created. You can now login.';
                    header('Refresh:1; url=index.php#login');
                }
            }
        }
    }

    if ($action === 'login') {
        $email = filter_var(trim($_POST['email'] ?? ''), FILTER_VALIDATE_EMAIL);
        $password = $_POST['password'] ?? '';
        if (!$email || $password === '') {
            $login_msg = 'Please provide email and password.';
        } else {
            $rows = db_query('SELECT id, username, password_hash FROM users WHERE email = ?', 's', [$email]);
            if ($rows === false || count($rows) === 0) {
                $login_msg = 'Invalid credentials.';
            } else {
                $row = $rows[0];
                if (password_verify($password, $row['password_hash'])) {
                    // set session
                    $_SESSION['user'] = ['id' => $row['id'], 'email' => $email, 'username' => $row['username']];
                    $_SESSION['user_id'] = $row['id'];   // integer from users.id
                    $_SESSION['username'] = $row['username']; // optional
                    $active_sessions = 1;
                    $login_msg = 'Welcome, ' . h($row['username']) . '!';
                    // redirect to home after short delay
                    header('Refresh:1; url=successlogin.php');
                } else {
                    $login_msg = 'Invalid credentials.';
                }
            }
        }
    }

    if ($action === 'recover') {
        $email = filter_var(trim($_POST['email'] ?? ''), FILTER_VALIDATE_EMAIL);
        if (!$email) {
            $recover_msg = 'Please provide your email.';
        } else {
            $rows = db_query('SELECT id, username FROM users WHERE email = ?', 's', [$email]);
            if ($rows === false || count($rows) === 0) {
                $recover_msg = 'Email not found.';
            } else {
                // demo only: do not reveal password in production; send email / token flow instead.
                $recover_msg = 'Check your email for recovery instructions (demo).';
            }
        }
    }
}

// fetch simple counts for UI
$cnt = db_query('SELECT COUNT(*) AS c FROM users');
if (is_array($cnt) && count($cnt) > 0) { $users_count = (int)$cnt[0]['c']; }
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width,initial-scale=1" />
  <title>Account — Marzley Shop</title>

  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700&display=swap" rel="stylesheet">

  <style>
    :root{
      --bg: linear-gradient(135deg,#eaf3ff 0,#eef7fb 100%);
      --card:#ffffff;
      --brand:#21334a;
      --accent:#e67e22;
      --muted:#6b7785;
      --ok:#27ae60;
      --danger:#e05b5b;
      --glass:rgba(255,255,255,0.85);
      --shadow: 0 10px 30px rgba(20,40,60,0.06);
      --radius:12px;
      --max:980px;
    }
    *{box-sizing:border-box}
    body{margin:0;font-family:Inter,system-ui,Segoe UI,Roboto,Arial,sans-serif;background:var(--bg);color:#162029;-webkit-font-smoothing:antialiased;line-height:1.45}
    a{color:inherit}
    .container{max-width:var(--max);margin:0 auto;padding:20px}

    /* header / nav (consistent with other pages) */
    header{background:var(--brand);color:#fff;padding:.9rem 0;position:sticky;top:0;z-index:1200;box-shadow:0 6px 20px rgba(10,20,30,0.06)}
    .top{max-width:var(--max);margin:0 auto;padding:0 18px;display:flex;align-items:center;justify-content:space-between}
    .logo{font-weight:800;color:var(--accent);font-size:1.15rem}
    .nav-links{display:flex;gap:12px;list-style:none;margin:0;padding:0;align-items:center}
    .nav-links a{color:#fff;text-decoration:none;padding:8px 10px;border-radius:8px;display:inline-flex;gap:8px;align-items:center;font-weight:600}
    .nav-toggle{display:none;background:transparent;border:0;color:#fff;font-size:1.15rem;padding:6px;cursor:pointer}
    @media(max-width:900px){ .nav-toggle{display:inline-block} .nav-links{position:absolute;left:12px;right:12px;top:64px;background:var(--brand);flex-direction:column;padding:12px;gap:8px;border-radius:8px;display:none} .nav-links.show{display:flex} }

    /* page card */
    .account-wrap{max-width:920px;margin:2.25rem auto;background:var(--card);border-radius:var(--radius);box-shadow:var(--shadow);overflow:hidden;display:grid;grid-template-columns:380px 1fr}
    @media(max-width:880px){ .account-wrap{grid-template-columns:1fr} }

    .side{
      padding:28px;background:linear-gradient(180deg,#fff,#fbfcff);
      border-right:1px solid #f1f6fb;
      display:flex;flex-direction:column;gap:14px;align-items:flex-start;
    }
    .brand-xs{display:flex;gap:10px;align-items:center}
    .brand-xs .logo{font-size:1.25rem}
    .hero-title{font-size:1.25rem;font-weight:800;color:#0f2b22}
    .hero-sub{color:var(--muted);font-size:.95rem}

    .side .stats{display:flex;gap:10px;flex-wrap:wrap;margin-top:6px}
    .stat{background:#fff;padding:8px 10px;border-radius:10px;box-shadow:0 6px 18px rgba(20,40,60,0.03);min-width:120px}
    .stat .n{font-weight:800;color:#133f1e}

    .main{
      padding:28px;display:flex;flex-direction:column;gap:14px;
    }

    /* tabs */
    .tabs{display:flex;gap:8px;background:transparent}
    .tab-btn{flex:1;padding:.65rem .9rem;border-radius:10px;background:transparent;border:1px solid transparent;cursor:pointer;font-weight:700;color:var(--brand);display:inline-flex;gap:8px;align-items:center;justify-content:center}
    .tab-btn.active{background:linear-gradient(90deg,var(--accent),#ff9b5a);color:#fff;box-shadow:0 10px 28px rgba(230,126,34,0.12)}

    .forms{margin-top:4px;display:grid;grid-template-columns:1fr;gap:12px}
    form{display:none;gap:10px}
    form.active{display:flex;flex-direction:column}

    /* input with icon */
    .field{position:relative;display:flex;align-items:center}
    .field input{flex:1;padding:.8rem 1rem .8rem 40px;border-radius:10px;border:1px solid #e8f1fb;background:transparent;font-size:1rem;transition:box-shadow .18s,transform .12s}
    .field .ico{position:absolute;left:12px;color:var(--muted);font-size:0.95rem}
    .field input:focus{outline:none;box-shadow:0 6px 22px rgba(30,60,90,0.06);transform:translateY(-1px)}

    .row{display:flex;gap:8px}
    .row .field{flex:1}

    .accent-btn{background:var(--accent);color:#fff;border:0;padding:.75rem 1rem;border-radius:10px;font-weight:800;cursor:pointer;display:inline-flex;gap:8px;align-items:center}
    .ghost-btn{background:transparent;border:1px solid #e7eff8;padding:.6rem .85rem;border-radius:8px;cursor:pointer}

    .msg{min-height:22px;font-weight:600}
    .msg.success{color:var(--ok)}
    .msg.error{color:var(--danger)}

    /* password strength */
    .pwd-meter{height:8px;border-radius:8px;background:#f1f5f9;overflow:hidden;margin-top:6px}
    .pwd-meter > i{display:block;height:100%;width:0;transition:width .36s ease;background:linear-gradient(90deg,#ffb86b,#34a853)}

    /* small utilities */
    .muted{color:var(--muted)}
    .note{font-size:.95rem;color:var(--muted);margin-top:6px}

    /* animations */
    .fade-in{opacity:0;transform:translateY(10px);animation:enter .5s forwards}
    .scale-pop{transform-origin:center;animation:pop .36s ease both}
    @keyframes enter{to{opacity:1;transform:none}}
    @keyframes pop{from{opacity:0;transform:scale(.98) translateY(6px)}to{opacity:1;transform:none}}

    /* toast */
    .toast{position:fixed;right:18px;bottom:18px;background:#24313a;color:#fff;padding:12px 16px;border-radius:10px;box-shadow:0 12px 30px rgba(0,0,0,0.18);display:none;z-index:2200}
    .toast.show{display:block;animation:pop .28s ease}

    footer{padding:14px;text-align:center;color:#fff;background:linear-gradient(135deg,#0b2a3a 0,#2b5870 100%);margin-top:22px;border-radius:6px}
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
        <li><a href="about us.html"><i class="fas fa-info-circle"></i> About</a></li>
        <li><a href="product.html"><i class="fas fa-box"></i> Products</a></li>
        <li><a href="add to cart.html"><i class="fas fa-shopping-cart"></i> Cart</a></li>
        <li><a href="index.php" aria-current="page"><i class="fas fa-user"></i> Account</a></li>
        <li><a href="admin.html"><i class="fas fa-user-shield"></i> Admin</a></li>
      </ul>
    </div>
  </header>

  <main class="container">
    <section class="account-wrap scale-pop" aria-labelledby="accTitle">
      <aside class="side">
        <div class="brand-xs">
          <div style="width:44px;height:44px;border-radius:10px;background:linear-gradient(135deg,#fff6f0,#fff);display:flex;align-items:center;justify-content:center;color:var(--accent);font-weight:800">MK</div>
          <div>
            <div class="hero-title">Account & Profile</div>
            <div class="hero-sub">Secure your Marzley Shop account — register, login or recover password.</div>
          </div>
        </div>

        <div class="stats">
          <div class="stat"><div class="n" id="users-count"><?php echo h($users_count); ?></div><div class="muted">Registered</div></div>
          <div class="stat"><div class="n" id="active-sessions"><?php echo h($active_sessions); ?></div><div class="muted">Active</div></div>
        </div>

        <div class="note">Create a profile to save carts, track orders and get exclusive offers.</div>
        <div style="margin-top:auto;display:flex;gap:8px;flex-wrap:wrap">
          <a class="ghost-btn" href="tel:0745789590"><i class="fas fa-store"></i> Contact us</a>
          <a class="ghost-btn" href="mailto:sirkelvinwanyoike@gmail.com"><i class="fas fa-envelope"></i> Support</a>
        </div>
      </aside>

      <div class="main">
        <div style="display:flex;justify-content:space-between;align-items:center">
          <div>
            <h2 id="accTitle" style="margin:0">Welcome back</h2>
            <div class="muted">Sign in or create a new account</div>
          </div>
          <div style="padding-right:12px;"><small class="muted">secured by Marzley Tech Solutions</small></div>
        </div>

        <div class="tabs">
          <button class="tab-btn active" data-tab="register"><i class="fas fa-user-plus"></i> Register</button>
          <button class="tab-btn" data-tab="login"><i class="fas fa-sign-in-alt"></i> Login</button>
          <button class="tab-btn" data-tab="recover"><i class="fas fa-key"></i> Recover</button>
        </div>

        <div class="forms">
          <!-- register (server-backed) -->
          <form id="register-form" class="active fade-in" autocomplete="off" novalidate method="post" action="">
            <input type="hidden" name="action" value="register">
            <div class="field">
              <i class="ico fas fa-envelope"></i>
              <input id="reg-email" name="email" type="email" placeholder="Email address" required>
            </div>

            <div class="row">
              <div class="field">
                <i class="ico fas fa-user"></i>
                <input id="reg-username" name="username" type="text" placeholder="Username" required>
              </div>
              <div class="field">
                <i class="ico fas fa-phone"></i>
                <input id="reg-phone" name="phone" type="text" placeholder="Phone (optional)">
              </div>
            </div>

            <div class="field">
              <i class="ico fas fa-lock"></i>
              <input id="reg-password" name="password" type="password" placeholder="Create password" required>
            </div>
            <div class="pwd-meter" aria-hidden="true"><i id="pwd-bar"></i></div>
            <div style="display:flex;gap:8px;align-items:center">
              <button type="submit" class="accent-btn"><i class="fas fa-user-plus"></i> <a href="home.html">Create account</a></button>
              <button type="button" id="reg-demo" class="ghost-btn"><i class="fas fa-flask"></i> Demo</button>
              <div class="msg <?php echo $reg_msg && strpos($reg_msg,'created')!==false ? 'success' : ($reg_msg? 'error':''); ?>" id="reg-msg" aria-live="polite"><?php echo h($reg_msg); ?></div>
            </div>
            <div class="muted">By creating an account you agree to our <a href="conditions.html" style="color:var(--accent)">Terms</a> and <a href="privacy.html" style="color:var(--accent)">Privacy</a>.</div>
          </form>

          <!-- login (server-backed) -->
          <form id="login-form" class="fade-in" novalidate method="post" action="">
            <input type="hidden" name="action" value="login">
            <div class="field">
              <i class="ico fas fa-envelope"></i>
              <input id="login-email" name="email" type="email" placeholder="Email" required>
            </div>
            <div class="field">
              <i class="ico fas fa-lock"></i>
              <input id="login-password" name="password" type="password" placeholder="Password" required>
              <button type="button" id="pwd-toggle" title="Show password" style="position:absolute;right:10px;background:transparent;border:0;color:var(--muted)"><i class="fas fa-eye"></i></button>
            </div>
            <div style="display:flex;gap:8px;align-items:center">
              <button type="submit" class="accent-btn"><i class="fas fa-sign-in-alt"></i> <a href="home.html">Login</a></button>
              <button type="button" id="login-demo" class="ghost-btn"><i class="fas fa-user-secret"></i> Demo login</button>
              <div class="msg" id="login-msg"><?php echo h($login_msg); ?></div>
            </div>
            <div class="note"><a href="#" onclick="switchTab('recover');return false">Forgot password?</a></div>
          </form>

          <!-- recover -->
          <form id="recover-form" class="fade-in" novalidate method="post" action="">
            <input type="hidden" name="action" value="recover">
            <div class="field">
              <i class="ico fas fa-envelope-open"></i>
              <input id="recover-email" name="email" type="email" placeholder="Enter your account email" required>
            </div>
            <div style="display:flex;gap:8px;align-items:center">
              <button type="submit" class="accent-btn"><i class="fas fa-paper-plane"></i> Recover</button>
              <div class="msg" id="recover-msg"><?php echo h($recover_msg); ?></div>
            </div>
            <div class="note">A recovery flow will be sent to your email (demo).</div>
          </form>
        </div>
      </div>
    </section>

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

    // small helpers: toast
    const toastEl = document.getElementById('toast');
    function showToast(msg, t=2200){ toastEl.textContent = msg; toastEl.classList.add('show'); clearTimeout(toastEl._t); toastEl._t = setTimeout(()=> toastEl.classList.remove('show'), t); }

    // tab handling (client-side only to switch forms)
    document.querySelectorAll('.tab-btn').forEach(btn=>{
      btn.addEventListener('click', ()=> switchTab(btn.dataset.tab) );
    });
    function switchTab(name){
      document.querySelectorAll('.tab-btn').forEach(b=>b.classList.toggle('active', b.dataset.tab===name));
      document.querySelectorAll('form').forEach(f=>f.classList.toggle('active', f.id===name+'-form'));
      const el = document.querySelector('form.active'); if (el){ el.classList.remove('fade-in'); void el.offsetWidth; el.classList.add('fade-in'); }
    }

    // password strength meter (client-side UX only)
    const pwd = document.getElementById('reg-password'), bar = document.getElementById('pwd-bar');
    function strengthScore(s){
      let score = 0;
      if (!s) return 0;
      if (s.length >= 8) score++;
      if (/[A-Z]/.test(s)) score++;
      if (/[0-9]/.test(s)) score++;
      if (/[\W_]/.test(s)) score++;
      return score;
    }
    pwd?.addEventListener('input', function(){
      const sc = strengthScore(this.value);
      const w = (sc/4)*100;
      bar.style.width = w + '%';
      bar.style.background = sc < 2 ? 'linear-gradient(90deg,#ff6b6b,#ffb86b)' : sc === 2 ? 'linear-gradient(90deg,#ffb86b,#ffd86b)' : 'linear-gradient(90deg,#8ae07b,#34a853)';
    });

    // password show toggle on login
    document.getElementById('pwd-toggle')?.addEventListener('click', function(){
      const inp = document.getElementById('login-password');
      if (!inp) return;
      const shown = inp.type === 'text';
      inp.type = shown ? 'password' : 'text';
      this.innerHTML = shown ? '<i class="fas fa-eye"></i>' : '<i class="fas fa-eye-slash"></i>';
    });

    // Demo quick add - creates a local demo record by calling serverless endpoint (for demo only)
    document.getElementById('reg-demo')?.addEventListener('click', function(){
      // Create a temporary demo POST using fetch to call same page
      const data = new URLSearchParams();
      data.append('action','register');
      data.append('email','demo+' + Date.now() + '@example.com');
      data.append('username','demo' + Math.floor(Math.random()*9999));
      data.append('phone','');
      data.append('password','Demo@1234');
      fetch(location.href, { method:'POST', body: data })
        .then(()=> { showToast('Demo account added (server)'); setTimeout(()=> location.reload(), 800); })
        .catch(()=> showToast('Demo failed'));
    });

    // Demo login: attempt login with first user via client request (not recommended for production)
    document.getElementById('login-demo')?.addEventListener('click', function(){
      const data = new URLSearchParams();
      data.append('action','login');
      data.append('email','demo@example.com');
      data.append('password','Demo@1234');
      fetch(location.href, { method:'POST', body: data })
        .then(()=> { showToast('Demo login attempted'); setTimeout(()=> location.reload(), 700); })
        .catch(()=> showToast('Demo login failed'));
    });

    // initialize view/tab via hash
    (function(){
      const h = location.hash.replace('#','');
      if (h === 'login' || h === 'recover') switchTab(h);
    })();
  </script>
</body>
</html>