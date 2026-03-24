<?php
session_start();
require_once 'db.php';

if (!isset($_SESSION['user_id']) || strtolower($_SESSION['role']) !== 'admin') {
    header("Location: login.php");
    exit;
}

$db       = getDB();
$fullname = $_SESSION['fullname'];

// Real stats from database
$totalUsers      = $db->query("SELECT COUNT(*) FROM users")->fetchColumn();
$totalStudents   = $db->query("SELECT COUNT(*) FROM users WHERE Role = 'Student'")->fetchColumn();
$totalSupervisors = $db->query("SELECT COUNT(*) FROM users WHERE Role = 'Supervisor'")->fetchColumn();
$totalPapers     = $db->query("SELECT COUNT(*) FROM papers")->fetchColumn();
$reviewed        = $db->query("SELECT COUNT(*) FROM papers WHERE Status = 'Reviewed'")->fetchColumn();
$pending         = $db->query("SELECT COUNT(*) FROM papers WHERE Status = 'For Review'")->fetchColumn();
$flagged         = $db->query("SELECT COUNT(*) FROM papers WHERE Percentage > 40 AND Percentage != 'NA' AND Percentage != ''")->fetchColumn();

// All users
$users  = $db->query("SELECT * FROM users ORDER BY Role, Fullname")->fetchAll();
// All papers
$papers = $db->query("SELECT * FROM papers ORDER BY Lastupdate DESC")->fetchAll();

// Handle user delete
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_user'])) {
    $uid = intval($_POST['delete_user']);
    $db->prepare("DELETE FROM users WHERE Sn = ? AND Role != 'Admin'")->execute([$uid]);
    header("Location: admin-dashboard.php?msg=deleted");
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>URSS | Admin Dashboard</title>
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
                <a href="admin-dashboard.php" class="nav-item active">🏠 Dashboard</a>
                <a href="#usersSection" class="nav-item">👥 Manage Users</a>
                <a href="#papersSection" class="nav-item">📄 All Papers</a>
                <a href="settings.php" class="nav-item">⚙️ Settings</a>
            </nav>
            <div class="sidebar-footer">
                <div class="user-info-sidebar">
                    <div class="user-avatar"><?= strtoupper(substr($fullname, 0, 1)) ?></div>
                    <div>
                        <div class="user-name-sidebar"><?= htmlspecialchars($fullname) ?></div>
                        <div class="user-role-sidebar">Admin</div>
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
                    <h1>Admin Dashboard</h1>
                    <p>System overview and user management</p>
                </div>
            </header>

            <?php if (isset($_GET['msg'])): ?>
                <div class="alert alert-success">✅ Action completed successfully.</div>
            <?php endif; ?>

            <!-- STATS -->
            <div class="stats-grid">
                <div class="stat-card blue">
                    <div class="stat-icon">👥</div>
                    <div class="stat-info">
                        <div class="stat-value"><?= $totalUsers ?></div>
                        <div class="stat-label">Total Users</div>
                        <div class="stat-sub"><?= $totalStudents ?> Students · <?= $totalSupervisors ?> Supervisors</div>
                    </div>
                </div>
                <div class="stat-card green">
                    <div class="stat-icon">📄</div>
                    <div class="stat-info">
                        <div class="stat-value"><?= $totalPapers ?></div>
                        <div class="stat-label">Total Submissions</div>
                        <div class="stat-sub"><?= $reviewed ?> Reviewed · <?= $pending ?> Pending</div>
                    </div>
                </div>
                <div class="stat-card red">
                    <div class="stat-icon">🚩</div>
                    <div class="stat-info">
                        <div class="stat-value"><?= $flagged ?></div>
                        <div class="stat-label">Flagged Papers</div>
                        <div class="stat-sub">Similarity &gt; 40%</div>
                    </div>
                </div>
                <div class="stat-card yellow">
                    <div class="stat-icon">⏳</div>
                    <div class="stat-info">
                        <div class="stat-value"><?= $pending ?></div>
                        <div class="stat-label">Pending Review</div>
                        <div class="stat-sub">Awaiting supervisors</div>
                    </div>
                </div>
            </div>

            <!-- USERS TABLE -->
            <div class="card" id="usersSection">
                <div class="card-header">
                    <h2>👥 All Users</h2>
                    <span class="badge-count"><?= $totalUsers ?> users</span>
                </div>
                <div class="table-wrap">
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>#</th>
                                <th>Full Name</th>
                                <th>Username / Email</th>
                                <th>Role</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($users as $user): ?>
                            <tr>
                                <td><?= $user['Sn'] ?></td>
                                <td><strong><?= htmlspecialchars($user['Fullname']) ?></strong></td>
                                <td><?= htmlspecialchars($user['Username']) ?></td>
                                <td>
                                    <span class="status-pill <?=
                                        $user['Role'] === 'Admin' ? 'pill-blue' :
                                        ($user['Role'] === 'Supervisor' ? 'pill-green' : 'pill-yellow')
                                    ?>">
                                        <?= htmlspecialchars($user['Role']) ?>
                                    </span>
                                </td>
                                <td>
                                    <?php if ($user['Role'] !== 'Admin'): ?>
                                    <form method="POST" onsubmit="return confirm('Delete this user?');" style="display:inline;">
                                        <input type="hidden" name="delete_user" value="<?= $user['Sn'] ?>">
                                        <button type="submit" class="btn-reject btn-sm">🗑 Delete</button>
                                    </form>
                                    <?php else: ?>
                                        <span style="color:#94a3b8;font-size:12px;">Protected</span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- PAPERS TABLE -->
            <div class="card" id="papersSection">
                <div class="card-header">
                    <h2>📄 All Submissions</h2>
                    <span class="badge-count"><?= $totalPapers ?> papers</span>
                </div>
                <div class="table-wrap">
                    <?php if (empty($papers)): ?>
                        <div class="empty-state">
                            <div class="empty-icon">📭</div>
                            <p>No papers submitted yet.</p>
                        </div>
                    <?php else: ?>
                        <table class="data-table">
                            <thead>
                                <tr>
                                    <th>Title</th>
                                    <th>Student</th>
                                    <th>Supervisor</th>
                                    <th>Score</th>
                                    <th>Status</th>
                                    <th>Date</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($papers as $paper): ?>
                                <tr>
                                    <td><strong><?= htmlspecialchars($paper['Title']) ?></strong></td>
                                    <td><?= htmlspecialchars($paper['Owner']) ?></td>
                                    <td><?= htmlspecialchars($paper['Supervisor']) ?></td>
                                    <td>
                                        <?php
                                        $s   = $paper['Percentage'];
                                        $cls = 'score-na';
                                        if (is_numeric($s)) {
                                            $cls = $s <= 20 ? 'score-good' : ($s <= 40 ? 'score-warn' : 'score-bad');
                                        }
                                        ?>
                                        <span class="score-badge <?= $cls ?>"><?= htmlspecialchars($s) ?>%</span>
                                    </td>
                                    <td>
                                        <span class="status-pill <?= $paper['Status'] === 'Reviewed' ? 'pill-green' : 'pill-yellow' ?>">
                                            <?= htmlspecialchars($paper['Status']) ?>
                                        </span>
                                    </td>
                                    <td><?= date('M d, Y', strtotime($paper['Lastupdate'])) ?></td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    <?php endif; ?>
                </div>
            </div>
        </main>
    </div>
    <script src="../assets/js/dashboard.js"></script>
</body>
</html>
