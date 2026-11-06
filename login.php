<?php
/**
 * Magaram Real Estate - Executive Admin Authentication
 */
require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/functions.php';

// If already logged in, redirect to dashboard
if (is_admin_logged_in()) {
    redirect(BASE_URL . '/dashboard.php');
}

$error = '';
$csrfToken = generate_csrf_token();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $submittedToken = $_POST['csrf_token'] ?? '';
    if (!verify_csrf_token($submittedToken)) {
        $error = 'Security session expired. Please refresh and try again.';
    } elseif (!check_rate_limit('admin_login', 6, 60)) {
        $error = 'Too many login attempts. Please wait 60 seconds.';
    } else {
        $username = clean_string($_POST['username'] ?? '');
        $password = $_POST['password'] ?? '';

        $db = get_db();
        if ($db) {
            try {
                $stmt = $db->prepare("SELECT * FROM admins WHERE username = :u LIMIT 1");
                $stmt->execute(['u' => $username]);
                $admin = $stmt->fetch();

                if ($admin && password_verify($password, $admin['password_hash'])) {
                    session_regenerate_id(true);
                    $_SESSION['admin_user_id'] = $admin['id'];
                    $_SESSION['admin_username'] = $admin['username'];
                    $_SESSION['admin_user_name'] = $admin['name'];
                    redirect(BASE_URL . '/dashboard.php');
                } else {
                    $error = 'Invalid credentials provided.';
                }
            } catch (Exception $e) {
                $error = 'Database authentication error: ' . $e->getMessage();
            }
        } else {
            if ($username === 'admin' && $password === 'admin123') {
                session_regenerate_id(true);
                $_SESSION['admin_user_id'] = 1;
                $_SESSION['admin_username'] = 'admin';
                $_SESSION['admin_user_name'] = 'Magaram Admin';
                redirect(BASE_URL . '/dashboard.php');
            } else {
                $error = "Database not connected. (Default credentials: admin / admin123)";
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Executive Login | Magaram Real Estate</title>
    <link rel="stylesheet" href="<?= BASE_URL; ?>/assets/css/style.css">
    <link rel="stylesheet" href="<?= BASE_URL; ?>/assets/css/admin.css">
    <style>
        body.login-page {
            display: flex;
            align-items: center;
            justify-content: center;
            min-height: 100vh;
            background: #080808;
            padding: 24px;
        }
        .login-card {
            max-width: 440px;
            width: 100%;
            background: #141414;
            border: 1px solid var(--border-gold);
            border-radius: 8px;
            padding: 40px;
            box-shadow: 0 25px 60px rgba(0,0,0,0.9);
            text-align: center;
        }
    </style>
</head>
<body class="login-page">
    <div class="login-card">
        <img src="<?= BASE_URL; ?>/assets/img/magaram-logo.svg" alt="Magaram Monogram" width="54" height="54" style="margin: 0 auto 16px auto;">
        <h2 style="font-size: 1.6rem; letter-spacing: 0.05em; margin-bottom: 4px;">MAGARAM</h2>
        <span style="font-size: 0.72rem; letter-spacing: 0.25em; color: var(--gold-bright); display: block; margin-bottom: 24px;">EXECUTIVE MANAGEMENT</span>

        <?php if (!empty($error)): ?>
            <div class="alert alert-danger" style="text-align: left; font-size: 0.88rem;">
                <?= e($error); ?>
            </div>
        <?php endif; ?>

        <form method="POST" class="luxury-form" style="text-align: left;">
            <input type="hidden" name="csrf_token" value="<?= e($csrfToken); ?>">

            <div class="form-group">
                <label for="username">Username</label>
                <input type="text" id="username" name="username" class="form-control" placeholder="admin" required autofocus>
            </div>

            <div class="form-group">
                <label for="password">Password</label>
                <input type="password" id="password" name="password" class="form-control" placeholder="&bull;&bull;&bull;&bull;&bull;&bull;&bull;&bull;" required>
            </div>

            <button type="submit" class="btn btn-primary" style="width: 100%; margin-top: 14px;">
                Enter Executive Suite
            </button>
        </form>

        <div style="margin-top: 24px; font-size: 0.8rem; color: var(--admin-muted);">
            Default credentials: <strong style="color: var(--gold-bright);">admin</strong> / <strong style="color: var(--gold-bright);">admin123</strong>
        </div>
    </div>
</body>
</html>
