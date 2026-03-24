<?php
session_start();
require_once 'db.php';

// Redirect if already logged in
if (isset($_SESSION['user_id'])) {
    $role = strtolower($_SESSION['role']);
    header("Location: {$role}-dashboard.php");
    exit;
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = trim($_POST['password'] ?? '');

    if (empty($username) || empty($password)) {
        $error = 'Please enter both username and password.';
    } else {
        $db = getDB();
        // Check if user exists — PDO prepared statement (SQL injection safe)
        $stmt = $db->prepare("SELECT * FROM users WHERE Username = ? LIMIT 1");
        $stmt->execute([$username]);
        $user = $stmt->fetch();

        if (!$user) {
            // User does not exist at all
            $error = 'Credentials not found. Please register first.';
        } elseif (!password_verify($password, $user['Password'])) {
            // User exists but wrong password
            $error = 'Wrong password or username. Please try again.';
        } else {
            // Login success — set session
            $_SESSION['user_id']  = $user['Sn'];
            $_SESSION['fullname'] = $user['Fullname'];
            $_SESSION['username'] = $user['Username'];
            $_SESSION['role']     = $user['Role'];

            $role = strtolower($user['Role']);
            header("Location: {$role}-dashboard.php");
            exit;
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>URSS - Login</title>
    <link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../assets/css/auth.css">
</head>
<body>
    <div class="auth-wrapper">
        <div class="auth-brand">
            <a href="../index.php" class="back-link">← Back to Home</a>
            <div class="brand">
                <span class="brand-icon">📘</span>
                <span class="brand-name">URSS Portal</span>
            </div>
            <p class="brand-tagline">University Research & Submission System</p>
        </div>

        <div class="auth-card">
            <div class="auth-header">
                <h1>Welcome Back</h1>
                <p>Sign in to your account to continue</p>
            </div>

            <?php if ($error): ?>
                <div class="alert alert-error">
                    <span class="alert-icon">⚠️</span>
                    <?= htmlspecialchars($error) ?>
                </div>
            <?php endif; ?>

            <form method="POST" action="" class="auth-form">
                <div class="form-group">
                    <label for="username">Email / Username</label>
                    <input
                        type="text"
                        id="username"
                        name="username"
                        placeholder="email@university.edu"
                        value="<?= htmlspecialchars($_POST['username'] ?? '') ?>"
                        required
                        autocomplete="username"
                    >
                </div>

                <div class="form-group">
                    <label for="password">Password</label>
                    <div class="password-wrap">
                        <input
                            type="password"
                            id="password"
                            name="password"
                            placeholder="Enter your password"
                            required
                            autocomplete="current-password"
                        >
                        <button type="button" class="toggle-pw" onclick="togglePw('password', this)">👁</button>
                    </div>
                </div>

                <button type="submit" class="btn-submit">Sign In</button>
            </form>

            <div class="auth-footer">
                <p>Don't have an account? <a href="register.php">Register here</a></p>
            </div>
        </div>
    </div>

    <script>
        function togglePw(id, btn) {
            const input = document.getElementById(id);
            if (input.type === 'password') {
                input.type = 'text';
                btn.textContent = '🙈';
            } else {
                input.type = 'password';
                btn.textContent = '👁';
            }
        }
    </script>
</body>
</html>
