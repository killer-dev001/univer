<?php
session_start();
require_once 'db.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

$db       = getDB();
$userId   = $_SESSION['user_id'];
$fullname = $_SESSION['fullname'];
$role     = strtolower($_SESSION['role']);

$success = '';
$error   = '';

// Handle profile update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_profile'])) {
    $newName = trim($_POST['fullname'] ?? '');
    if (empty($newName)) {
        $error = 'Full name cannot be empty.';
    } else {
        $stmt = $db->prepare("UPDATE users SET Fullname = ? WHERE Sn = ?");
        $stmt->execute([$newName, $userId]);
        $_SESSION['fullname'] = $newName;
        $fullname = $newName;
        $success  = 'Profile updated successfully.';
    }
}

// Handle password change
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['change_password'])) {
    $current = trim($_POST['current_password'] ?? '');
    $new     = trim($_POST['new_password']     ?? '');
    $confirm = trim($_POST['confirm_password'] ?? '');

    // Get current password from DB
    $stmt = $db->prepare("SELECT Password FROM users WHERE Sn = ?");
    $stmt->execute([$userId]);
    $user = $stmt->fetch();

    if (!password_verify($current, $user['Password'])) {
        $error = 'Current password is incorrect.';
    } elseif (strlen($new) < 6) {
        $error = 'New password must be at least 6 characters.';
    } elseif ($new !== $confirm) {
        $error = 'New passwords do not match.';
    } else {
        $hashed = password_hash($new, PASSWORD_BCRYPT);
        $stmt   = $db->prepare("UPDATE users SET Password = ? WHERE Sn = ?");
        $stmt->execute([$hashed, $userId]);
        $success = 'Password changed successfully.';
    }
}

// Dashboard link based on role
$dashboardLink = $role . '-dashboard.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>URSS | Settings</title>
    <link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../assets/css/dashboard.css">
</head>
<body>
    <div class="app-container">
        <aside class="sidebar" id="sidebar">
            <div class="sidebar-header">
                <div class="logo">📘 URSS</div>
                <button class="sidebar-close" id="sidebarClose">✕</button>
            </div>
            <nav class="sidebar-nav">
                <a href="<?= $dashboardLink ?>" class="nav-item">🏠 Dashboard</a>
                <?php if ($role === 'student'): ?>
                    <a href="submissions.php" class="nav-item">📄 My Submissions</a>
                    <a href="submit.php" class="nav-item">📤 Submit Paper</a>
                <?php endif; ?>
                <a href="settings.php" class="nav-item active">⚙️ Settings</a>
            </nav>
            <div class="sidebar-footer">
                <div class="user-info-sidebar">
                    <div class="user-avatar"><?= strtoupper(substr($fullname, 0, 1)) ?></div>
                    <div>
                        <div class="user-name-sidebar"><?= htmlspecialchars($fullname) ?></div>
                        <div class="user-role-sidebar"><?= htmlspecialchars($_SESSION['role']) ?></div>
                    </div>
                </div>
                <a href="logout.php" class="logout-btn">🚪 Logout</a>
            </div>
        </aside>
        <div class="overlay" id="overlay"></div>

        <main class="main-content">
            <header class="topbar">
                <button class="hamburger-btn" id="menuBtn">☰</button>
                <div class="topbar-title">
                    <h1>Account Settings</h1>
                    <p>Manage your profile and security</p>
                </div>
            </header>

            <?php if ($success): ?>
                <div class="alert alert-success" style="margin:16px 24px 0;">✅ <?= htmlspecialchars($success) ?></div>
            <?php endif; ?>
            <?php if ($error): ?>
                <div class="alert alert-error" style="margin:16px 24px 0;">⚠️ <?= htmlspecialchars($error) ?></div>
            <?php endif; ?>

            <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(300px,1fr));gap:24px;padding:24px;">

                <!-- PROFILE CARD -->
                <div class="card" style="margin:0;">
                    <div class="card-header">
                        <h2>👤 Profile Information</h2>
                    </div>
                    <div style="padding:24px;">
                        <div style="text-align:center;margin-bottom:24px;">
                            <div style="width:72px;height:72px;border-radius:50%;background:#2563eb;color:white;display:flex;align-items:center;justify-content:center;font-size:28px;font-weight:700;margin:0 auto 12px;">
                                <?= strtoupper(substr($fullname, 0, 1)) ?>
                            </div>
                            <div style="font-weight:600;font-size:16px;"><?= htmlspecialchars($fullname) ?></div>
                            <div style="font-size:13px;color:#64748b;"><?= htmlspecialchars($_SESSION['username']) ?></div>
                            <span class="status-pill pill-blue" style="margin-top:8px;display:inline-block;">
                                <?= htmlspecialchars($_SESSION['role']) ?>
                            </span>
                        </div>

                        <form method="POST">
                            <div class="form-group">
                                <label>Full Name</label>
                                <input type="text" name="fullname" value="<?= htmlspecialchars($fullname) ?>" required>
                            </div>
                            <div class="form-group">
                                <label>Email / Username</label>
                                <input type="text" value="<?= htmlspecialchars($_SESSION['username']) ?>" disabled style="background:#f1f5f9;color:#94a3b8;cursor:not-allowed;">
                            </div>
                            <button type="submit" name="update_profile" class="btn-submit-form" style="margin-top:8px;">
                                Save Changes
                            </button>
                        </form>
                    </div>
                </div>

                <!-- SECURITY CARD -->
                <div class="card" style="margin:0;">
                    <div class="card-header">
                        <h2>🔒 Change Password</h2>
                    </div>
                    <div style="padding:24px;">
                        <div class="alert" style="background:#dbeafe;color:#1d4ed8;border:1px solid #bfdbfe;margin-bottom:20px;">
                            🔐 Passwords are encrypted with bcrypt — your password is never stored in plain text.
                        </div>
                        <form method="POST">
                            <div class="form-group">
                                <label>Current Password</label>
                                <div class="password-wrap" style="position:relative;display:flex;align-items:center;">
                                    <input type="password" name="current_password" id="cur" placeholder="Enter current password" required style="padding-right:48px;">
                                    <button type="button" onclick="togglePw('cur',this)" style="position:absolute;right:12px;background:none;border:none;cursor:pointer;font-size:16px;">👁</button>
                                </div>
                            </div>
                            <div class="form-group">
                                <label>New Password</label>
                                <div class="password-wrap" style="position:relative;display:flex;align-items:center;">
                                    <input type="password" name="new_password" id="newpw" placeholder="Minimum 6 characters" required style="padding-right:48px;">
                                    <button type="button" onclick="togglePw('newpw',this)" style="position:absolute;right:12px;background:none;border:none;cursor:pointer;font-size:16px;">👁</button>
                                </div>
                            </div>
                            <div class="form-group">
                                <label>Confirm New Password</label>
                                <div class="password-wrap" style="position:relative;display:flex;align-items:center;">
                                    <input type="password" name="confirm_password" id="confpw" placeholder="Re-enter new password" required style="padding-right:48px;">
                                    <button type="button" onclick="togglePw('confpw',this)" style="position:absolute;right:12px;background:none;border:none;cursor:pointer;font-size:16px;">👁</button>
                                </div>
                            </div>
                            <button type="submit" name="change_password" class="btn-submit-form" style="background:#334155;margin-top:8px;">
                                Update Password
                            </button>
                        </form>
                    </div>
                </div>

            </div>
        </main>
    </div>
    <script src="../assets/js/dashboard.js"></script>
    <script>
        function togglePw(id, btn) {
            const input = document.getElementById(id);
            input.type  = input.type === 'password' ? 'text' : 'password';
            btn.textContent = input.type === 'password' ? '👁' : '🙈';
        }
    </script>
</body>
</html>
