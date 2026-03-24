<?php
session_start();
require_once 'db.php';

if (!isset($_SESSION['user_id']) || strtolower($_SESSION['role']) !== 'student') {
    header("Location: login.php");
    exit;
}

$db       = getDB();
$userId   = $_SESSION['user_id'];
$fullname = $_SESSION['fullname'];

// Fetch student's papers from database
$stmt = $db->prepare("SELECT * FROM papers WHERE Owner = ? ORDER BY Lastupdate DESC");
$stmt->execute([$fullname]);
$papers = $stmt->fetchAll();

// Stats
$total    = count($papers);
$reviewed = count(array_filter($papers, fn($p) => $p['Status'] === 'Reviewed'));
$pending  = count(array_filter($papers, fn($p) => $p['Status'] === 'For Review'));
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>URSS | Student Dashboard</title>
    <link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../assets/css/dashboard.css">
</head>
<body>
    <div class="app-container">

        <!-- SIDEBAR -->
        <aside class="sidebar" id="sidebar">
            <div class="sidebar-header">
                <div class="logo">📘 URSS</div>
                <button class="sidebar-close" id="sidebarClose">✕</button>
            </div>
            <nav class="sidebar-nav">
                <a href="student-dashboard.php" class="nav-item active">🏠 Dashboard</a>
                <a href="submissions.php" class="nav-item">📄 My Submissions</a>
                <a href="submit.php" class="nav-item">📤 Submit Paper</a>
                <a href="settings.php" class="nav-item">⚙️ Settings</a>
            </nav>
            <div class="sidebar-footer">
                <div class="user-info-sidebar">
                    <div class="user-avatar"><?= strtoupper(substr($fullname, 0, 1)) ?></div>
                    <div>
                        <div class="user-name-sidebar"><?= htmlspecialchars($fullname) ?></div>
                        <div class="user-role-sidebar">Student</div>
                    </div>
                </div>
                <a href="logout.php" class="logout-btn">🚪 Logout</a>
            </div>
        </aside>

        <div class="overlay" id="overlay"></div>

        <!-- MAIN -->
        <main class="main-content">
            <header class="topbar">
                <button class="hamburger-btn" id="menuBtn">☰</button>
                <div class="topbar-title">
                    <h1>Welcome back, <?= htmlspecialchars(explode(' ', $fullname)[0]) ?>!</h1>
                    <p>Here's an overview of your research submissions</p>
                </div>
                <div class="topbar-right">
                    <a href="submit.php" class="btn-primary-sm">+ Submit Paper</a>
                </div>
            </header>

            <!-- STATS -->
            <div class="stats-grid">
                <div class="stat-card blue">
                    <div class="stat-icon">📄</div>
                    <div class="stat-info">
                        <div class="stat-value"><?= $total ?></div>
                        <div class="stat-label">Total Submissions</div>
                    </div>
                </div>
                <div class="stat-card green">
                    <div class="stat-icon">✅</div>
                    <div class="stat-info">
                        <div class="stat-value"><?= $reviewed ?></div>
                        <div class="stat-label">Reviewed</div>
                    </div>
                </div>
                <div class="stat-card yellow">
                    <div class="stat-icon">⏳</div>
                    <div class="stat-info">
                        <div class="stat-value"><?= $pending ?></div>
                        <div class="stat-label">Pending Review</div>
                    </div>
                </div>
            </div>

            <!-- RECENT SUBMISSIONS -->
            <div class="card">
                <div class="card-header">
                    <h2>My Submissions</h2>
                    <a href="submit.php" class="btn-link">+ New Submission</a>
                </div>
                <div class="table-wrap">
                    <?php if (empty($papers)): ?>
                        <div class="empty-state">
                            <div class="empty-icon">📭</div>
                            <p>No submissions yet.</p>
                            <a href="submit.php" class="btn-primary-sm">Submit your first paper</a>
                        </div>
                    <?php else: ?>
                        <table class="data-table">
                            <thead>
                                <tr>
                                    <th>Title</th>
                                    <th>Supervisor</th>
                                    <th>Score</th>
                                    <th>Last Updated</th>
                                    <th>Status</th>
                                    <th>Comment</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($papers as $paper): ?>
                                <tr>
                                    <td><strong><?= htmlspecialchars($paper['Title']) ?></strong></td>
                                    <td><?= htmlspecialchars($paper['Supervisor']) ?></td>
                                    <td>
                                        <?php
                                        $score = $paper['Percentage'];
                                        $scoreClass = 'score-na';
                                        if (is_numeric($score)) {
                                            $scoreClass = $score <= 20 ? 'score-good' : ($score <= 40 ? 'score-warn' : 'score-bad');
                                        }
                                        ?>
                                        <span class="score-badge <?= $scoreClass ?>"><?= htmlspecialchars($score) ?>%</span>
                                    </td>
                                    <td><?= date('M d, Y', strtotime($paper['Lastupdate'])) ?></td>
                                    <td>
                                        <span class="status-pill <?= $paper['Status'] === 'Reviewed' ? 'pill-green' : 'pill-yellow' ?>">
                                            <?= htmlspecialchars($paper['Status']) ?>
                                        </span>
                                    </td>
                                    <td class="comment-cell"><?= htmlspecialchars($paper['Lastcomment']) ?></td>
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
