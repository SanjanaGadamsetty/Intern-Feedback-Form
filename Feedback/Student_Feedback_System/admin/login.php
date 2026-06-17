<?php
require_once '../config/db.php';
startSecureSession();

if (isset($_SESSION['admin_logged_in']) && $_SESSION['admin_logged_in'] === true) {
    header('Location: dashboard.php');
    exit();
}

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';

    if (empty($username) || empty($password)) {
        $error = 'Please enter both username and password.';
    } else {
        $conn = getDB();
        $stmt = $conn->prepare("SELECT id, username, password, full_name FROM admin_users WHERE username = ?");
        $stmt->bind_param('s', $username);
        $stmt->execute();
        $result = $stmt->get_result();
        $admin = $result->fetch_assoc();

        // Support plain text 'admin123' or hashed passwords
        $valid = false;
        if ($admin) {
            if (password_verify($password, $admin['password'])) {
                $valid = true;
            } elseif ($password === 'admin123' && $admin['username'] === 'admin') {
                // Fallback for demo - update hash
                $newHash = password_hash($password, PASSWORD_DEFAULT);
                $upd = $conn->prepare("UPDATE admin_users SET password=? WHERE id=?");
                $upd->bind_param('si', $newHash, $admin['id']);
                $upd->execute();
                $valid = true;
            }
        }

        if ($valid) {
            session_regenerate_id(true);
            $_SESSION['admin_logged_in'] = true;
            $_SESSION['admin_id'] = $admin['id'];
            $_SESSION['admin_name'] = $admin['full_name'];
            $_SESSION['admin_username'] = $admin['username'];

            // Update last login
            $upd = $conn->prepare("UPDATE admin_users SET last_login=NOW() WHERE id=?");
            $upd->bind_param('i', $admin['id']);
            $upd->execute();

            header('Location: dashboard.php');
            exit();
        } else {
            $error = 'Invalid username or password.';
        }
        $stmt->close();
        $conn->close();
    }
}
?>
<!DOCTYPE html>
<html lang="en" data-theme="light">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Admin Login – Feedback System</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
<link href="../assets/css/style.css" rel="stylesheet">
<style>
  body { background: linear-gradient(135deg, #1a237e 0%, #0d47a1 50%, #01579b 100%); min-height: 100vh; display:flex; align-items:center; }
  .login-card { max-width: 440px; width: 100%; margin: auto; animation: fadeInUp 0.6s ease; }
  .login-logo { width: 80px; height: 80px; background: linear-gradient(135deg, #1a237e, #3949ab); border-radius: 50%; display:flex; align-items:center; justify-content:center; margin: 0 auto 20px; }
</style>
</head>
<body>
<div class="container">
  <div class="login-card">
    <div class="card border-0" style="border-radius:20px;overflow:hidden;box-shadow:0 20px 60px rgba(0,0,0,0.3);">
      <div style="background:linear-gradient(135deg,#1a237e,#3949ab);padding:40px 40px 30px;text-align:center;">
        <div class="login-logo">
          <i class="fas fa-graduation-cap fa-2x" style="color:white;"></i>
        </div>
        <h4 style="color:white;font-weight:800;margin:0;">Admin Portal</h4>
        <p style="color:rgba(255,255,255,0.75);font-size:0.9rem;margin-top:6px;">Student Feedback Management System</p>
      </div>
      <div class="card-body p-4" style="background:var(--card-bg);">
        <?php if ($error): ?>
        <div class="alert alert-danger alert-auto-dismiss d-flex align-items-center" role="alert">
          <i class="fas fa-exclamation-circle me-2"></i><?= e($error) ?>
        </div>
        <?php endif; ?>
        <form method="POST" action="login.php">
          <div class="mb-3">
            <label class="form-label fw-600">Username</label>
            <div class="input-group">
              <span class="input-group-text" style="background:var(--light-bg);border-color:var(--border);">
                <i class="fas fa-user" style="color:var(--primary);"></i>
              </span>
              <input type="text" class="form-control" name="username" placeholder="Enter username"
                     value="<?= e($_POST['username'] ?? '') ?>" autocomplete="username" required
                     style="border-left:none;">
            </div>
          </div>
          <div class="mb-4">
            <label class="form-label fw-600">Password</label>
            <div class="input-group">
              <span class="input-group-text" style="background:var(--light-bg);border-color:var(--border);">
                <i class="fas fa-lock" style="color:var(--primary);"></i>
              </span>
              <input type="password" class="form-control" name="password" id="passwordField"
                     placeholder="Enter password" autocomplete="current-password" required
                     style="border-left:none;border-right:none;">
              <button type="button" class="input-group-text" style="background:var(--light-bg);border-color:var(--border);cursor:pointer;"
                      onclick="togglePass()">
                <i class="fas fa-eye" id="eyeIcon" style="color:var(--primary);"></i>
              </button>
            </div>
          </div>
          <button type="submit" class="btn-primary-custom w-100" style="border-radius:10px;padding:13px;">
            <i class="fas fa-sign-in-alt me-2"></i> Login to Dashboard
          </button>
        </form>
        <div class="mt-4 p-3 rounded" style="background:var(--light-bg);font-size:0.82rem;color:var(--text-muted);">
          <i class="fas fa-info-circle me-1"></i>
          <strong>Default Credentials:</strong><br>
          Username: <code>admin</code> &nbsp;|&nbsp; Password: <code>admin123</code>
        </div>
        <div class="text-center mt-3">
          <a href="../index.php" style="color:var(--primary);font-size:0.9rem;">
            <i class="fas fa-arrow-left me-1"></i> Back to Feedback Form
          </a>
        </div>
      </div>
    </div>
  </div>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script src="../assets/js/main.js"></script>
<script>
function togglePass() {
    const f = document.getElementById('passwordField');
    const i = document.getElementById('eyeIcon');
    f.type = f.type === 'password' ? 'text' : 'password';
    i.className = f.type === 'password' ? 'fas fa-eye' : 'fas fa-eye-slash';
}
</script>
</body>
</html>
