<?php
session_start();
require_once 'db.php';

if (!isset($_SESSION['user_id']) || strtolower($_SESSION['role']) !== 'supervisor') {
    header("Location: login.php");
    exit;
}

$db       = getDB();
$fullname = $_SESSION['fullname'];

// Handle review action
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    $paperId = intval($_POST['paper_id']);
    $comment = trim($_POST['comment'] ?? '');
    $action  = $_POST['action'];
    $status  = $action === 'approve' ? 'Reviewed' : 'For Review';

    $stmt = $db->prepare("UPDATE papers SET Status = ?, Lastcomment = ?, Lastupdate = NOW() WHERE Sn = ? AND Supervisor = ?");
    $stmt->execute([$status, $comment, $paperId, $fullname]);
    header("Location: supervisor-dashboard.php?msg=updated");
    exit;
}

// Fetch papers assigned to this supervisor
$stmt = $db->prepare("SELECT * FROM papers WHERE Supervisor = ? ORDER BY Lastupdate DESC");
$stmt->execute([$fullname]);
$papers = $stmt->fetchAll();

// Stats
$total    = count($papers);
$reviewed = count(array_filter($papers, fn($p) => $p['Status'] === 'Reviewed'));
$pending  = count(array_filter($papers, fn($p) => $p['Status'] === 'For Review'));
$flagged  = count(array_filter($papers, fn($p) => is_numeric($p['Percentage']) && $p['Percentage'] > 40));

// Count assigned students
$studentStmt = $db->prepare("SELECT COUNT(DISTINCT Owner) as cnt FROM papers WHERE Supervisor = ?");
$studentStmt->execute([$fullname]);
$studentCount = $studentStmt->fetch()['cnt'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>URSS | Supervisor Dashboard</title>
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
                <a href="supervisor-dashboard.php" class="nav-item active">🏠 Dashboard</a>
                <a href="settings.php" class="nav-item">⚙️ Settings</a>
            </nav>
            <div class="sidebar-footer">
                <div class="user-info-sidebar">
                    <div class="user-avatar"><?= strtoupper(substr($fullname, 0, 1)) ?></div>
                    <div>
                        <div class="user-name-sidebar"><?= htmlspecialchars($fullname) ?></div>
                        <div class="user-role-sidebar">Supervisor</div>
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
                    <h1>Supervisor Dashboard</h1>
                    <p>Welcome, <?= htmlspecialchars(explode(' ', $fullname)[0]) ?>. Manage your students' submissions.</p>
                </div>
            </header>

            <?php if (isset($_GET['msg'])): ?>
                <div class="alert alert-success">✅ Paper review updated successfully.</div>
            <?php endif; ?>

            <!-- STATS -->
            <div class="stats-grid">
                <div class="stat-card blue">
                    <div class="stat-icon">🎓</div>
                    <div class="stat-info">
                        <div class="stat-value"><?= $studentCount ?></div>
                        <div class="stat-label">Assigned Students</div>
                    </div>
                </div>
                <div class="stat-card yellow">
                    <div class="stat-icon">⏳</div>
                    <div class="stat-info">
                        <div class="stat-value"><?= $pending ?></div>
                        <div class="stat-label">Pending Reviews</div>
                    </div>
                </div>
                <div class="stat-card green">
                    <div class="stat-icon">✅</div>
                    <div class="stat-info">
                        <div class="stat-value"><?= $reviewed ?></div>
                        <div class="stat-label">Reviewed</div>
                    </div>
                </div>
                <div class="stat-card red">
                    <div class="stat-icon">🚩</div>
                    <div class="stat-info">
                        <div class="stat-value"><?= $flagged ?></div>
                        <div class="stat-label">High Similarity</div>
                    </div>
                </div>
            </div>

            <!-- SUBMISSIONS TABLE -->
            <div class="card">
                <div class="card-header">
                    <h2>Student Submissions</h2>
                    <span class="badge-count"><?= $total ?> total</span>
                </div>
                <div class="table-wrap">
                    <?php if (empty($papers)): ?>
                        <div class="empty-state">
                            <div class="empty-icon">📭</div>
                            <p>No submissions assigned to you yet.</p>
                        </div>
                    <?php else: ?>
                        <table class="data-table">
                            <thead>
                                <tr>
                                    <th>Student</th>
                                    <th>Title</th>
                                    <th>Score</th>
                                    <th>Submitted</th>
                                    <th>Status</th>
                                    <th>Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($papers as $paper): ?>
                                <tr>
                                    <td><strong><?= htmlspecialchars($paper['Owner']) ?></strong></td>
                                    <td><?= htmlspecialchars($paper['Title']) ?></td>
                                    <td>
                                        <?php
                                        $score = $paper['Percentage'];
                                        $cls   = 'score-na';
                                        if (is_numeric($score)) {
                                            $cls = $score <= 20 ? 'score-good' : ($score <= 40 ? 'score-warn' : 'score-bad');
                                        }
                                        ?>
                                        <span class="score-badge <?= $cls ?>"><?= htmlspecialchars($score) ?>%</span>
                                    </td>
                                    <td><?= date('M d, Y', strtotime($paper['Lastupdate'])) ?></td>
                                    <td>
                                        <span class="status-pill <?= $paper['Status'] === 'Reviewed' ? 'pill-green' : 'pill-yellow' ?>">
                                            <?= htmlspecialchars($paper['Status']) ?>
                                        </span>
                                    </td>
                                    <td>
                                        <button class="btn-review" onclick="openReview(<?= $paper['Sn'] ?>, '<?= addslashes($paper['Title']) ?>', '<?= addslashes($paper['Lastcomment']) ?>')">
                                            Review
                                        </button>
                                        <?php if (!empty($paper['Original'])): ?>
                                            <a href="../<?= htmlspecialchars($paper['Original']) ?>" class="btn-view" target="_blank">View</a>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    <?php endif; ?>
                </div>
            </div>
        </main>
    </div>

    <!-- REVIEW MODAL -->
    <div class="modal-overlay" id="reviewModal" style="display:none;">
        <div class="modal">
            <div class="modal-header">
                <h3 id="modalTitle">Review Paper</h3>
                <button onclick="closeModal()" class="modal-close">✕</button>
            </div>
            <form method="POST" class="modal-body">
                <input type="hidden" name="paper_id" id="modalPaperId">
                <div class="form-group">
                    <label>Comment / Feedback</label>
                    <textarea name="comment" id="modalComment" rows="5" placeholder="Leave your feedback for the student..." required></textarea>
                </div>
                <div class="modal-actions">
                    <button type="submit" name="action" value="approve" class="btn-approve">✅ Mark as Reviewed</button>
                    <button type="submit" name="action" value="reject" class="btn-reject">↩️ Send Back for Revision</button>
                </div>
            </form>
        </div>
    </div>

    <script src="../assets/js/dashboard.js"></script>
    <script>
        function openReview(id, title, comment) {
            document.getElementById('modalPaperId').value  = id;
            document.getElementById('modalTitle').textContent = 'Review: ' + title;
            document.getElementById('modalComment').value  = comment !== 'NL' ? comment : '';
            document.getElementById('reviewModal').style.display = 'flex';
        }
        function closeModal() {
            document.getElementById('reviewModal').style.display = 'none';
        }
        document.getElementById('reviewModal').addEventListener('click', function(e) {
            if (e.target === this) closeModal();
        });
    </script>
</body>
</html>
