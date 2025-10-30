<?php
session_start();
require_once __DIR__ . '/mtkenya.php';

function h($s){ return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8'); }

// logout handler
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'logout') {
    $_SESSION = [];
    if (ini_get("session.use_cookies")) {
        $params = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000,
            $params["path"], $params["domain"],
            $params["secure"], $params["httponly"]
        );
    }
    session_destroy();
    header('Location: signin.php');
    exit;
}

// require auth
if (empty($_SESSION['user']['id'])) {
    header('Location: signin.php');
    exit;
}

$userId = (int) $_SESSION['user']['id'];
$user = db_query('SELECT id, email, username, phone, created_at, updated_at FROM users WHERE id = ? LIMIT 1', 'i', [$userId]);
$user = ($user && is_array($user) && count($user) ? $user[0] : null);

?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <title>Dashboard — Marzley Shop</title>
  <style>
    body{font-family:Inter,system-ui,Arial;margin:24px;background:#f6fbff;color:#102026}
    .wrap{max-width:980px;margin:0 auto;background:#fff;padding:20px;border-radius:12px;box-shadow:0 12px 36px rgba(2,19,24,.06)}
    header{display:flex;justify-content:space-between;align-items:center;margin-bottom:14px}
    .btn{background:#e67e22;color:#fff;padding:8px 12px;border-radius:8px;text-decoration:none;font-weight:700;border:0;cursor:pointer}
    dl{display:grid;grid-template-columns:160px 1fr;gap:8px 12px}
    dt{font-weight:700;color:#334;}
    dd{margin:0}
    .muted{color:#6b7785;font-size:.95rem}
  </style>
</head>
<body>
  <div class="wrap">
    <header>
      <div>
        <h1 style="margin:0">Welcome, <?php echo h($_SESSION['user']['username'] ?? ($_SESSION['user']['email'] ?? '')); ?></h1>
        <div class="muted">Signed in to Marzley Shop</div>
      </div>
      <form method="post" style="margin:0">
        <input type="hidden" name="action" value="logout">
        <button class="btn" type="submit">Sign out</button>
      </form>
    </header>

    <section>
      <h2 style="margin-top:0">Your profile</h2>
      <?php if ($user): ?>
        <dl>
          <dt>ID</dt><dd><?php echo h($user['id']); ?></dd>
          <dt>Email</dt><dd><?php echo h($user['email']); ?></dd>
          <dt>Username</dt><dd><?php echo h($user['username']); ?></dd>
          <dt>Phone</dt><dd><?php echo h($user['phone'] ?? '—'); ?></dd>
          <dt>Created</dt><dd><?php echo h($user['created_at'] ?? '—'); ?></dd>
          <dt>Updated</dt><dd><?php echo h($user['updated_at'] ?? '—'); ?></dd>
        </dl>
      <?php else: ?>
        <p class="muted">User details not found. (<a href="index.php">edit profile</a>)</p>
      <?php endif; ?>
    </section>

    <hr>

    <section>
      <h3>Quick actions</h3>
      <p class="muted">
        <a href="index.php" style="color:#e67e22;font-weight:700">Edit profile</a>
        &nbsp;·&nbsp;
        <a href="users.php" style="color:#e67e22;font-weight:700">View users (admin)</a>
      </p>
    </section>
  </div>
</body>
</html>