<?php
session_start();
require_once 'db.php';

if (isset($_SESSION['user_id'])) {
    header("Location: " . strtolower($_SESSION['role']) . "-dashboard.php");
    exit;
}

$error   = '';
$success = '';
$role    = $_GET['role'] ?? 'student';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $fullname = trim($_POST['fullname'] ?? '');
    $username = trim($_POST['username'] ?? '');
    $password = trim($_POST['password'] ?? '');
    $confirm  = trim($_POST['confirm']  ?? '');
    $role     = trim($_POST['role']     ?? 'student');

    if (empty($fullname) || empty($username) || empty($password) || empty($confirm)) {
        $error = 'Please fill in all fields.';
    } elseif (!filter_var($username, FILTER_VALIDATE_EMAIL)) {
        $error = 'Please enter a valid email address.';
    } elseif (strlen($password) < 6) {
        $error = 'Password must be at least 6 characters.';
    } elseif ($password !== $confirm) {
        $error = 'Passwords do not match.';
    } else {
        $db = getDB();

        // Check if username already exists
        $check = $db->prepare("SELECT Sn FROM users WHERE Username = ? LIMIT 1");
        $check->execute([$username]);

        if ($check->fetch()) {
            $error = 'An account with this email already exists. Please login.';
        } else {
            // ENCRYPT PASSWORD with bcrypt — industry standard
            $hashedPassword = password_hash($password, PASSWORD_BCRYPT);

            $roleFormatted = ucfirst(strtolower($role));

            $insert = $db->prepare("INSERT INTO users (Fullname, Username, Password, Role) VALUES (?, ?, ?, ?)");
            $insert->execute([$fullname, $username, $hashedPassword, $roleFormatted]);

            $success = 'Account created successfully! You can now login.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>URSS - Register</title>
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
            <p class="brand-tagline">Create your account to get started</p>
        </div>

        <div class="auth-card">
            <div class="auth-header">
                <h1>Create Account</h1>
                <p>Join the URSS research portal</p>
            </div>

            <?php if ($error): ?>
                <div class="alert alert-error">
                    <span class="alert-icon">⚠️</span>
                    <?= htmlspecialchars($error) ?>
                </div>
            <?php endif; ?>

            <?php if ($success): ?>
                <div class="alert alert-success">
                    <span class="alert-icon">✅</span>
                    <?= htmlspecialchars($success) ?>
                    <br><a href="login.php" style="color:inherit;font-weight:600;">Click here to login →</a>
                </div>
            <?php endif; ?>

            <form method="POST" action="" class="auth-form">
                <div class="form-group">
                    <label for="fullname">Full Name</label>
                    <input
                        type="text"
                        id="fullname"
                        name="fullname"
                        placeholder="e.g. John Moses"
                        value="<?= htmlspecialchars($_POST['fullname'] ?? '') ?>"
                        required
                    >
                </div>

                <div class="form-group">
                    <label for="username">Email Address</label>
                    <input
                        type="email"
                        id="username"
                        name="username"
                        placeholder="email@university.edu"
                        value="<?= htmlspecialchars($_POST['username'] ?? '') ?>"
                        required
                    >
                </div>

                <div class="form-group">
                    <label for="role">Register as</label>
                    <select id="role" name="role">
                        <option value="student"    <?= ($role === 'student')    ? 'selected' : '' ?>>Student</option>
                        <option value="supervisor" <?= ($role === 'supervisor') ? 'selected' : '' ?>>Supervisor</option>
                    </select>
                </div>

                <div class="form-group">
                    <label for="password">Password</label>
                    <div class="password-wrap">
                        <input
                            type="password"
                            id="password"
                            name="password"
                            placeholder="Minimum 6 characters"
                            required
                        >
                        <button type="button" class="toggle-pw" onclick="togglePw('password', this)">👁</button>
                    </div>
                </div>

                <div class="form-group">
                    <label for="confirm">Confirm Password</label>
                    <div class="password-wrap">
                        <input
                            type="password"
                            id="confirm"
                            name="confirm"
                            placeholder="Re-enter your password"
                            required
                        >
                        <button type="button" class="toggle-pw" onclick="togglePw('confirm', this)">👁</button>
                    </div>
                </div>

                <div class="password-strength" id="strengthBar">
                    <div class="strength-label">Password strength: <span id="strengthText">—</span></div>
                    <div class="strength-track"><div class="strength-fill" id="strengthFill"></div></div>
                </div>

                <button type="submit" class="btn-submit">Create Account</button>
            </form>

            <div class="auth-footer">
                <p>Already have an account? <a href="login.php">Login here</a></p>
            </div>
        </div>
    </div>

    <script>
        function togglePw(id, btn) {
            const input = document.getElementById(id);
            input.type = input.type === 'password' ? 'text' : 'password';
            btn.textContent = input.type === 'password' ? '👁' : '🙈';
        }

        document.getElementById('password').addEventListener('input', function () {
            const val = this.value;
            const fill = document.getElementById('strengthFill');
            const text = document.getElementById('strengthText');
            let strength = 0;
            if (val.length >= 6) strength++;
            if (val.length >= 10) strength++;
            if (/[A-Z]/.test(val)) strength++;
            if (/[0-9]/.test(val)) strength++;
            if (/[^A-Za-z0-9]/.test(val)) strength++;

            const labels = ['', 'Very Weak', 'Weak', 'Fair', 'Strong', 'Very Strong'];
            const colors = ['', '#ef4444', '#f97316', '#eab308', '#22c55e', '#16a34a'];
            text.textContent = labels[strength] || '—';
            fill.style.width = (strength * 20) + '%';
            fill.style.background = colors[strength] || '#e2e8f0';
        });
    </script>
</body>
</html>
