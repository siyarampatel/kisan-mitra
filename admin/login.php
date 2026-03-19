<?php
// ============================================================
//  login.php — Admin Login Page
// ============================================================

session_start();

// If already logged in, go straight to dashboard
if (isset($_SESSION['admin_logged_in']) && $_SESSION['admin_logged_in'] === true) {
    header('Location: dashboard.php');
    exit();
}

require_once 'db.php';

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = trim($_POST['password'] ?? '');

    if ($username === '' || $password === '') {
        $error = 'कृपया username और password दोनों भरें। / Please enter both username and password.';
    } else {
        // Fetch user from database
        $stmt = $conn->prepare('SELECT id, username, password FROM admin_users WHERE username = ?');
        $stmt->bind_param('s', $username);
        $stmt->execute();
        $result = $stmt->get_result();
        $user   = $result->fetch_assoc();
        $stmt->close();

        if ($user && password_verify($password, $user['password'])) {
            // Login successful
            $_SESSION['admin_logged_in'] = true;
            $_SESSION['admin_id']        = $user['id'];
            $_SESSION['admin_username']  = $user['username'];
            header('Location: dashboard.php');
            exit();
        } else {
            $error = 'गलत username या password। / Incorrect username or password.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="hi">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>किसान मित्र — Admin Login</title>
<link href="https://fonts.googleapis.com/css2?family=Tiro+Devanagari+Hindi&family=Nunito:wght@400;600;800&display=swap" rel="stylesheet">
<style>
  *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
  body {
    min-height: 100vh;
    background: #f6f4f0;
    font-family: 'Nunito', sans-serif;
    display: flex;
    align-items: center;
    justify-content: center;
    padding: 24px;
  }
  .card {
    background: white;
    border-radius: 20px;
    border: 1.5px solid #e8e0d0;
    padding: 40px 36px;
    width: 100%;
    max-width: 400px;
  }
  .logo {
    text-align: center;
    margin-bottom: 28px;
  }
  .logo-icon {
    width: 56px;
    height: 56px;
    background: #1a1208;
    border-radius: 16px;
    display: flex;
    align-items: center;
    justify-content: center;
    margin: 0 auto 12px;
  }
  .logo-title {
    font-family: 'Tiro Devanagari Hindi', serif;
    font-size: 26px;
    color: #1a1208;
  }
  .logo-sub {
    font-size: 11px;
    font-weight: 800;
    color: #e8560a;
    letter-spacing: 2px;
    text-transform: uppercase;
    margin-top: 2px;
  }
  .admin-badge {
    display: inline-block;
    margin-top: 8px;
    background: #fff3ed;
    color: #e8560a;
    font-size: 11px;
    font-weight: 800;
    padding: 4px 14px;
    border-radius: 20px;
    letter-spacing: 1px;
    text-transform: uppercase;
    border: 1px solid #f5c0a0;
  }
  .divider {
    height: 1px;
    background: #e8e0d0;
    margin: 24px 0;
  }
  .field { margin-bottom: 16px; }
  .field label {
    display: block;
    font-size: 12px;
    font-weight: 800;
    color: #9a8a70;
    letter-spacing: 1px;
    text-transform: uppercase;
    margin-bottom: 6px;
  }
  .field input {
    width: 100%;
    padding: 12px 16px;
    border: 1.5px solid #e8e0d0;
    border-radius: 12px;
    font-size: 15px;
    font-family: 'Nunito', sans-serif;
    color: #1a1208;
    background: #fafaf7;
    outline: none;
    transition: border-color 0.2s;
  }
  .field input:focus { border-color: #e8560a; background: white; }
  .error-box {
    background: #ffe5e5;
    border: 1.5px solid #f5c0c0;
    border-radius: 12px;
    padding: 12px 16px;
    margin-bottom: 16px;
    font-size: 13px;
    color: #c0392b;
    font-weight: 600;
    line-height: 1.5;
  }
  .btn-login {
    width: 100%;
    padding: 14px;
    background: #1a1208;
    color: white;
    border: none;
    border-radius: 12px;
    font-size: 16px;
    font-weight: 800;
    font-family: 'Nunito', sans-serif;
    cursor: pointer;
    transition: background 0.2s;
    margin-top: 8px;
  }
  .btn-login:hover { background: #e8560a; }
  .btn-login:active { transform: scale(0.98); }
  .back-link {
    display: block;
    text-align: center;
    margin-top: 20px;
    font-size: 13px;
    color: #9a8a70;
    text-decoration: none;
    font-weight: 600;
  }
  .back-link:hover { color: #e8560a; }
</style>
</head>
<body>
<div class="card">

  <div class="logo">
    <div class="logo-icon">
      <svg width="28" height="28" viewBox="0 0 36 36" fill="none">
        <path d="M18 4C18 4 10 10 10 20C10 26 13 30 18 32C23 30 26 26 26 20C26 10 18 4 18 4Z" fill="#e8560a"/>
        <path d="M18 10L18 32M18 18C18 18 22 14 26 15" stroke="white" stroke-width="1.5" stroke-linecap="round" fill="none"/>
      </svg>
    </div>
    <div class="logo-title">किसान मित्र</div>
    <div class="logo-sub">Kisan Mitra</div>
    <span class="admin-badge">Admin Panel</span>
  </div>

  <div class="divider"></div>

  <?php if ($error): ?>
  <div class="error-box"><?= htmlspecialchars($error) ?></div>
  <?php endif; ?>

  <form method="POST" action="login.php">
    <div class="field">
      <label>Username</label>
      <input type="text" name="username" placeholder="Enter username"
             value="<?= htmlspecialchars($_POST['username'] ?? '') ?>"
             autocomplete="username" required>
    </div>
    <div class="field">
      <label>Password</label>
      <input type="password" name="password" placeholder="Enter password"
             autocomplete="current-password" required>
    </div>
    <button type="submit" class="btn-login">Login →</button>
  </form>

  <a href="../index.html" class="back-link">← Back to farmer website</a>

</div>
</body>
</html>
