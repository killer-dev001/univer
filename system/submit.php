<?php
session_start();
require_once 'db.php';

if (!isset($_SESSION['user_id']) || strtolower($_SESSION['role']) !== 'student') {
    header("Location: login.php");
    exit;
}

$fullname = $_SESSION['fullname'];
$error    = '';
$success  = '';
$finalScore = 0;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title      = trim($_POST['title']      ?? '');
    $supervisor = trim($_POST['supervisor'] ?? '');
    $keywords   = trim($_POST['keywords']   ?? '');
    $abstract   = trim($_POST['abstract']   ?? '');
    $wordCount  = intval($_POST['word_count'] ?? 0);

    if (empty($title) || empty($supervisor) || empty($keywords)) {
        $error = 'Please fill in all required fields.';
    } elseif (!isset($_FILES['document']) || $_FILES['document']['error'] !== 0) {
        $error = 'Please upload a document.';
    } else {
        $file    = $_FILES['document'];
        $ext     = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        $allowed = ['pdf', 'doc', 'docx'];

        if (!in_array($ext, $allowed)) {
            $error = 'Only PDF, DOC, and DOCX files are allowed.';
        } elseif ($file['size'] > 10 * 1024 * 1024) {
            $error = 'File size must not exceed 10MB.';
        } else {
            $uploadDir = '../uploads/';
            if (!is_dir($uploadDir)) mkdir($uploadDir, 0755, true);
            $filename  = uniqid() . '_' . preg_replace('/[^a-zA-Z0-9._-]/', '_', $file['name']);
            $filepath  = $uploadDir . $filename;
            move_uploaded_file($file['tmp_name'], $filepath);

            // CHECK FOR DUPLICATE SUBMISSION
            $db = getDB();

            // Check 1: Same title by same student
            $dupTitle = $db->prepare("SELECT Sn FROM papers WHERE LOWER(Title) = LOWER(?) AND Owner = ? LIMIT 1");
            $dupTitle->execute([$title, $fullname]);
            if ($dupTitle->fetch()) {
                $error = 'Title already exists.';
                if (file_exists($filepath)) unlink($filepath);
            }

            // Check 2: Same title by ANY student (across the system)
            if (empty($error)) {
                $dupAny = $db->prepare("SELECT Sn, Owner FROM papers WHERE LOWER(Title) = LOWER(?) LIMIT 1");
                $dupAny->execute([$title]);
                $existingPaper = $dupAny->fetch();
                if ($existingPaper) {
                    $error = 'Title already exists.';
                    if (file_exists($filepath)) unlink($filepath);
                }
            }

            // Check 3: Same keywords AND same student (very similar submission)
            if (empty($error)) {
                $dupKw = $db->prepare("SELECT Sn FROM papers WHERE LOWER(Keywords) = LOWER(?) AND Owner = ? LIMIT 1");
                $dupKw->execute([$keywords, $fullname]);
                if ($dupKw->fetch()) {
                    $error = 'A paper with these exact keywords already exists. Please ensure this is a unique submission.';
                    if (file_exists($filepath)) unlink($filepath);
                }
            }

            if (empty($error)) {
            // Simulate plagiarism score
            $allPapers = $db->query("SELECT Title, Keywords FROM papers")->fetchAll();
            $userKeywords = array_map('trim', explode(',', strtolower($keywords)));
            $matchScore   = 0;

            foreach ($allPapers as $existing) {
                $existingKw = array_map('trim', explode(',', strtolower($existing['Keywords'] ?? '')));
                $matches    = count(array_intersect($userKeywords, $existingKw));
                if ($matches > 0) {
                    $matchScore = max($matchScore, min(95, $matches * 15 + rand(5, 20)));
                }
            }

            if ($matchScore === 0) $matchScore = rand(5, 25);

            $stmt = $db->prepare("INSERT INTO papers
                (Title, Owner, Supervisor, Original, Updated, Percentage, Lastupdate, Lastcomment, Status, Keywords, Abstract)
                VALUES (?, ?, ?, ?, ?, ?, NOW(), ?, ?, ?, ?)");
            $stmt->execute([
                $title, $fullname, $supervisor,
                'uploads/' . $filename, 'uploads/' . $filename,
                $matchScore, 'Awaiting supervisor review',
                'For Review', $keywords, $abstract
            ]);

            $success    = 'success';
            $finalScore = $matchScore;
            } // end empty($error) check
        }
    }
}

$db          = getDB();
$supervisors = $db->query("SELECT Fullname FROM users WHERE Role = 'Supervisor' ORDER BY Fullname")->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>URSS | Submit Paper</title>
    <link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../assets/css/dashboard.css">
    <style>
        .checker-overlay {
            display: none;
            position: fixed;
            inset: 0;
            background: rgba(15,23,42,0.85);
            z-index: 1000;
            align-items: flex-start;
            justify-content: center;
            backdrop-filter: blur(4px);
            overflow-y: auto;
            padding: 20px 16px;
        }
        .checker-overlay.show { display: flex; }

        .checker-box {
            background: white;
            border-radius: 20px;
            padding: 36px 32px;
            width: 100%;
            max-width: 480px;
            margin: 20px;
            text-align: center;
            box-shadow: 0 25px 60px rgba(0,0,0,0.3);
        }

        .checker-icon { font-size: 48px; margin-bottom: 12px; animation: pulse 1.5s infinite; }
        @keyframes pulse { 0%,100%{transform:scale(1)} 50%{transform:scale(1.1)} }

        .checker-title { font-size: 20px; font-weight: 700; color: #0f172a; margin-bottom: 6px; }
        .checker-stage { font-size: 14px; color: #64748b; margin-bottom: 20px; min-height: 20px; }

        .progress-wrap { background: #f1f5f9; border-radius: 20px; height: 14px; overflow: hidden; margin-bottom: 10px; }
        .progress-fill { height: 100%; border-radius: 20px; background: linear-gradient(90deg,#2563eb,#16a34a); width: 0%; transition: width 0.8s ease; }
        .progress-percent { font-size: 28px; font-weight: 700; color: #2563eb; margin-bottom: 2px; }
        .progress-label { font-size: 12px; color: #94a3b8; margin-bottom: 20px; }

        .stages-list { text-align: left; display: flex; flex-direction: column; gap: 8px; margin-bottom: 20px; }
        .stage-item { display: flex; align-items: center; gap: 12px; padding: 10px 14px; border-radius: 10px; background: #f8fafc; font-size: 13px; color: #64748b; transition: all 0.3s; }
        .stage-item.active { background: #dbeafe; color: #1d4ed8; font-weight: 600; }
        .stage-item.done   { background: #dcfce7; color: #16a34a; font-weight: 600; }

        .stage-dot { width: 22px; height: 22px; border-radius: 50%; background: #e2e8f0; display: flex; align-items: center; justify-content: center; font-size: 11px; flex-shrink: 0; transition: all 0.3s; }
        .stage-item.active .stage-dot { background: #2563eb; color: white; animation: spin 1s linear infinite; }
        .stage-item.done   .stage-dot { background: #16a34a; color: white; }
        @keyframes spin { from{transform:rotate(0deg)} to{transform:rotate(360deg)} }

        .warning-text { font-size: 12px; color: #ef4444; font-weight: 500; }

        .result-box { display: none; padding: 20px; border-radius: 12px; margin-bottom: 16px; }
        .result-box.low  { background: #dcfce7; border: 2px solid #86efac; }
        .result-box.mid  { background: #fef9c3; border: 2px solid #fde047; }
        .result-box.high { background: #fee2e2; border: 2px solid #fca5a5; }
        .result-score { font-size: 52px; font-weight: 700; margin-bottom: 6px; }
        .result-box.low  .result-score { color: #16a34a; }
        .result-box.mid  .result-score { color: #ca8a04; }
        .result-box.high .result-score { color: #dc2626; }
        .result-label { font-size: 15px; font-weight: 600; margin-bottom: 4px; }
        .result-desc  { font-size: 13px; color: #64748b; }

        .btn-done { width: 100%; padding: 14px; background: #2563eb; color: white; border: none; border-radius: 10px; font-size: 15px; font-weight: 600; cursor: pointer; font-family: inherit; transition: background 0.2s; margin-top: 8px; }
        .btn-done:hover { background: #1d4ed8; }

        .word-count-display { font-size: 12px; color: #64748b; text-align: right; margin-top: 4px; }
        .word-count-display span { font-weight: 700; color: #2563eb; }
    </style>
</head>
<body>
    <div class="app-container">
        <aside class="sidebar" id="sidebar">
            <div class="sidebar-header">
                <div class="logo">📘 URSS</div>
                <button class="sidebar-close" id="sidebarClose">✕</button>
            </div>
            <nav class="sidebar-nav">
                <a href="student-dashboard.php" class="nav-item">🏠 Dashboard</a>
                <a href="submissions.php" class="nav-item">📄 My Submissions</a>
                <a href="submit.php" class="nav-item active">📤 Submit Paper</a>
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

        <main class="main-content">
            <header class="topbar">
                <button class="hamburger-btn" id="menuBtn">☰</button>
                <div class="topbar-title">
                    <h1>Submit Research Paper</h1>
                    <p>Upload your document for plagiarism screening</p>
                </div>
            </header>

            <div class="card" style="max-width:700px;margin:24px;">

                <?php if ($error): ?>
                    <div class="alert alert-error" style="margin:20px 24px 0;">⚠️ <?= htmlspecialchars($error) ?></div>
                <?php endif; ?>

                <div class="submission-steps">
                    <div class="step-item" id="stepFile">
                        <div class="step-dot">1</div>
                        <div class="step-text">File Upload</div>
                    </div>
                    <div class="step-line"></div>
                    <div class="step-item" id="stepCheck">
                        <div class="step-dot">2</div>
                        <div class="step-text">Plagiarism Check</div>
                    </div>
                    <div class="step-line"></div>
                    <div class="step-item" id="stepReview">
                        <div class="step-dot">3</div>
                        <div class="step-text">Supervisor Review</div>
                    </div>
                </div>

                <form method="POST" enctype="multipart/form-data" id="submitForm" style="padding:0 24px 24px;">
                    <div class="form-group">
                        <label>Project Title *</label>
                        <input type="text" name="title" placeholder="Enter your full project title..." required
                               value="<?= htmlspecialchars($_POST['title'] ?? '') ?>">
                    </div>

                    <div class="form-group">
                        <label>Assign Supervisor *</label>
                        <select name="supervisor" required>
                            <option value="">— Select Supervisor —</option>
                            <?php foreach ($supervisors as $sv): ?>
                                <option value="<?= htmlspecialchars($sv['Fullname']) ?>"
                                    <?= (($_POST['supervisor'] ?? '') === $sv['Fullname']) ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($sv['Fullname']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="form-group">
                        <label>Keywords * <small>(comma separated)</small></label>
                        <input type="text" name="keywords" placeholder="e.g. AI, Machine Learning, Healthcare" required
                               value="<?= htmlspecialchars($_POST['keywords'] ?? '') ?>">
                    </div>

                    <div class="form-group">
                        <label>Abstract <small>(paste here for accurate word count)</small></label>
                        <textarea name="abstract" rows="4" id="abstractInput"
                                  placeholder="Briefly summarize your research..."><?= htmlspecialchars($_POST['abstract'] ?? '') ?></textarea>
                        <div class="word-count-display">Word count: <span id="wordCountDisplay">0</span> words</div>
                    </div>

                    <input type="hidden" name="word_count" id="wordCountInput" value="0">

                    <div class="form-group">
                        <label>Upload Document * <small>(PDF, DOC, DOCX — max 10MB)</small></label>
                        <div class="drop-zone" id="dropZone">
                            <input type="file" name="document" id="fileInput" accept=".pdf,.doc,.docx" hidden required>
                            <div class="drop-content" id="dropContent">
                                <div class="drop-icon">📁</div>
                                <p>Drag & drop your file here or <span class="browse-link">Browse</span></p>
                                <small>PDF, DOC, DOCX only — max 10MB</small>
                            </div>
                        </div>
                    </div>

                    <button type="submit" class="btn-submit-form" id="submitBtn">
                        🔍 Submit & Run Plagiarism Check
                    </button>
                </form>
            </div>
        </main>
    </div>

    <!-- PLAGIARISM CHECKER OVERLAY -->
    <div class="checker-overlay" id="checkerOverlay">
        <div class="checker-box">
            <div class="checker-icon" id="checkerIcon">🔍</div>
            <div class="checker-title">Running Plagiarism Analysis</div>
            <div class="checker-stage" id="checkerStage">Initializing...</div>

            <div class="progress-wrap">
                <div class="progress-fill" id="progressFill"></div>
            </div>
            <div class="progress-percent" id="progressPercent">0%</div>
            <div class="progress-label" id="progressLabel">Calculating time remaining...</div>

            <div class="stages-list">
                <div class="stage-item" id="stage1">
                    <div class="stage-dot">⟳</div>
                    <span>📄 Reading and parsing document</span>
                </div>
                <div class="stage-item" id="stage2">
                    <div class="stage-dot">2</div>
                    <span>🔑 Extracting text and keywords</span>
                </div>
                <div class="stage-item" id="stage3">
                    <div class="stage-dot">3</div>
                    <span>🌐 Scanning against 90M+ academic sources</span>
                </div>
                <div class="stage-item" id="stage4">
                    <div class="stage-dot">4</div>
                    <span>📊 Running similarity algorithm</span>
                </div>
                <div class="stage-item" id="stage5">
                    <div class="stage-dot">5</div>
                    <span>📝 Generating plagiarism report</span>
                </div>
            </div>

            <p class="warning-text">⚠️ Do not close or refresh this page</p>

            <div class="result-box" id="resultBox">
                <div class="result-score" id="resultScore">0%</div>
                <div class="result-label" id="resultLabel"></div>
                <div class="result-desc"  id="resultDesc"></div>
            </div>

            <button class="btn-done" id="btnDone" style="display:none;"
                    onclick="window.location.href='submissions.php'">
                ✅ View My Submissions
            </button>
            <button class="btn-done" id="btnDashboard" style="display:none;background:#334155;margin-top:10px;"
                    onclick="window.location.href='student-dashboard.php'">
                🏠 Go to Dashboard
            </button>
        </div>
    </div>

    <script src="../assets/js/dashboard.js"></script>
    <script>
    // WORD COUNT
    const abstractInput    = document.getElementById('abstractInput');
    const wordCountDisplay = document.getElementById('wordCountDisplay');
    const wordCountInput   = document.getElementById('wordCountInput');

    function countWords(text) {
        return text.trim() === '' ? 0 : text.trim().split(/\s+/).length;
    }

    abstractInput.addEventListener('input', function () {
        const count = countWords(this.value);
        wordCountDisplay.textContent = count;
        wordCountInput.value = count;
    });

    // FILE DROP
    const dropZone    = document.getElementById('dropZone');
    const fileInput   = document.getElementById('fileInput');
    const dropContent = document.getElementById('dropContent');

    dropZone.addEventListener('dragover', e => { e.preventDefault(); dropZone.classList.add('drag-over'); });
    dropZone.addEventListener('dragleave', () => dropZone.classList.remove('drag-over'));
    dropZone.addEventListener('drop', e => { e.preventDefault(); dropZone.classList.remove('drag-over'); assignFile(e.dataTransfer.files[0]); });
    dropZone.addEventListener('click', () => fileInput.click());
    fileInput.addEventListener('change', () => { if (fileInput.files[0]) assignFile(fileInput.files[0]); });

    function assignFile(file) {
        if (!/\.(pdf|doc|docx)$/i.test(file.name)) { alert('Only PDF, DOC, DOCX allowed.'); return; }
        if (file.size > 10 * 1024 * 1024) { alert('File must be under 10MB.'); return; }
        const dt = new DataTransfer(); dt.items.add(file); fileInput.files = dt.files;
        dropContent.innerHTML = `<div class="drop-icon">✅</div><p><strong>${file.name}</strong></p><small>${(file.size/1024).toFixed(1)} KB selected</small>`;
        dropZone.classList.add('file-selected');
    }

    // SIMULATION TIMING based on word count
    // 500-1000  = 60-90s  (1 - 1.5 min)
    // 1500      = 120-180s (2 - 3 min)
    // 3000      = 180-270s (3 - 4.5 min)
    // 10000+    = 420s    (7 min)
    function getSimulationTime(wc) {
        if (wc <= 0)     return 75;
        if (wc <= 1000)  return Math.floor(Math.random() * 30) + 60;
        if (wc <= 1500)  return Math.floor(Math.random() * 60) + 120;
        if (wc <= 3000)  return Math.floor(Math.random() * 90) + 180;
        return 420;
    }

    function formatTime(s) {
        if (s <= 0) return 'almost done...';
        const m = Math.floor(s / 60), sec = s % 60;
        return m > 0 ? `${m}m ${sec}s remaining` : `${sec}s remaining`;
    }

    const stageConfig = [
        { id: 'stage1', pct: 20  },
        { id: 'stage2', pct: 40  },
        { id: 'stage3', pct: 65  },
        { id: 'stage4', pct: 85  },
        { id: 'stage5', pct: 100 },
    ];

    const stageMessages = [
        '📄 Reading and parsing document...',
        '🔑 Extracting text and keywords...',
        '🌐 Scanning against 90M+ academic sources...',
        '📊 Running similarity algorithm...',
        '📝 Generating plagiarism report...',
    ];

    function runSimulation(totalSeconds, finalScore) {
        document.getElementById('checkerOverlay').classList.add('show');
        document.getElementById('stepFile').classList.add('step-done');
        document.getElementById('stepCheck').classList.add('step-active');

        const totalMs   = totalSeconds * 1000;
        const intervalMs = 500;
        let elapsedMs   = 0;

        const stageBoundaries = stageConfig.map(s => Math.floor((s.pct / 100) * totalMs));

        const timer = setInterval(() => {
            elapsedMs += intervalMs;
            const pct       = Math.min(100, Math.floor((elapsedMs / totalMs) * 100));
            const remaining = Math.max(0, Math.ceil((totalMs - elapsedMs) / 1000));

            document.getElementById('progressFill').style.width    = pct + '%';
            document.getElementById('progressPercent').textContent = pct + '%';
            document.getElementById('progressLabel').textContent   = formatTime(remaining);

            // Update stage indicators
            for (let i = 0; i < stageConfig.length; i++) {
                const el  = document.getElementById(stageConfig[i].id);
                const dot = el.querySelector('.stage-dot');
                const prevBoundary = i === 0 ? 0 : stageBoundaries[i - 1];

                if (elapsedMs >= stageBoundaries[i]) {
                    el.className     = 'stage-item done';
                    dot.textContent  = '✓';
                } else if (elapsedMs >= prevBoundary) {
                    el.className     = 'stage-item active';
                    dot.textContent  = '⟳';
                    document.getElementById('checkerStage').textContent = stageMessages[i];
                }
            }

            if (elapsedMs >= totalMs) {
                clearInterval(timer);
                showResult(finalScore);
            }
        }, intervalMs);
    }

    function showResult(score) {
        const resultBox  = document.getElementById('resultBox');
        const icon       = document.getElementById('checkerIcon');
        icon.style.animation = 'none';

        if (score <= 20) {
            resultBox.className = 'result-box low';
            document.getElementById('resultLabel').textContent = '✅ Low Similarity — Great Work!';
            document.getElementById('resultDesc').textContent  = 'Your work appears mostly original. Your supervisor will review it shortly.';
            icon.textContent = '✅';
        } else if (score <= 40) {
            resultBox.className = 'result-box mid';
            document.getElementById('resultLabel').textContent = '⚠️ Moderate Similarity — Review Recommended';
            document.getElementById('resultDesc').textContent  = 'Some similarity detected. Ensure all sources are properly referenced.';
            icon.textContent = '⚠️';
        } else {
            resultBox.className = 'result-box high';
            document.getElementById('resultLabel').textContent = '❌ High Similarity — Revision Required';
            document.getElementById('resultDesc').textContent  = 'High similarity detected. Please revise and cite all sources properly.';
            icon.textContent = '❌';
        }

        document.getElementById('resultScore').textContent           = score + '%';
        document.getElementById('checkerStage').textContent          = 'Analysis complete!';
        document.getElementById('stepCheck').classList.remove('step-active');
        document.getElementById('stepCheck').classList.add('step-done');
        document.getElementById('stepReview').classList.add('step-active');
        resultBox.style.display                                       = 'block';
        document.getElementById('btnDone').style.display             = 'block';
        document.getElementById('btnDashboard').style.display        = 'block';

        // Scroll to bottom of overlay so buttons are visible
        const overlay = document.getElementById('checkerOverlay');
        setTimeout(() => { overlay.scrollTop = overlay.scrollHeight; }, 100);
    }

    // AUTO START if PHP processed successfully
    <?php if ($success === 'success'): ?>
    window.addEventListener('load', function () {
        const wordCount  = <?= intval($_POST['word_count'] ?? 0) ?>;
        const finalScore = <?= intval($finalScore) ?>;
        const simTime    = getSimulationTime(wordCount);
        runSimulation(simTime, finalScore);
    });
    <?php endif; ?>

    document.getElementById('submitForm').addEventListener('submit', function () {
        document.getElementById('submitBtn').disabled    = true;
        document.getElementById('submitBtn').textContent = '⏳ Uploading document...';
    });
    </script>
</body>
</html>
