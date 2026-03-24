<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>URSS - UniResearch Portal System</title>
    <link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@300;400;500;600;700&family=Playfair+Display:wght@700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/landing.css">
</head>
<body>
    <nav class="navbar">
        <div class="nav-inner">
            <div class="brand">
                <span class="brand-icon">📘</span>
                <span class="brand-name">URSS Portal</span>
            </div>
            <div class="nav-links">
                <a href="system/login.php" class="btn btn-outline">Login</a>
                <a href="system/register.php" class="btn btn-primary">Get Started</a>
            </div>
            <button class="hamburger" id="hamburger" aria-label="Menu">
                <span></span><span></span><span></span>
            </button>
        </div>
        <div class="mobile-menu" id="mobileMenu">
            <a href="system/login.php">Login</a>
            <a href="system/register.php">Get Started</a>
        </div>
    </nav>

    <section class="hero">
        <div class="hero-inner">
            <div class="hero-badge">🎓 University Research Management</div>
            <h1 class="hero-title">
                Plagiarism Checking &<br>
                <span class="highlight">Research Moderation</span><br>
                System
            </h1>
            <p class="hero-desc">
                A platform that helps Supervisors and Students manage
                the originality of their academic works with ease.
            </p>
            <div class="hero-actions">
                <a href="system/register.php" class="btn btn-primary btn-lg">
                    Get Started →
                </a>
                <a href="system/login.php" class="btn btn-outline btn-lg">
                    Login to Account
                </a>
            </div>
        </div>
        <div class="hero-visual">
            <div class="visual-card">
                <div class="vc-header">
                    <span class="vc-dot red"></span>
                    <span class="vc-dot yellow"></span>
                    <span class="vc-dot green"></span>
                    <span class="vc-title">Plagiarism Analysis</span>
                </div>
                <div class="vc-body">
                    <div class="vc-step done">✓ Document Uploaded</div>
                    <div class="vc-step done">✓ Keywords Extracted</div>
                    <div class="vc-step active">⏳ Running Similarity Check...</div>
                    <div class="vc-step pending">◦ Supervisor Review</div>
                </div>
                <div class="vc-score">
                    <span class="score-label">Similarity Score</span>
                    <span class="score-value green">12%</span>
                </div>
            </div>
        </div>
    </section>

    <section class="features">
        <div class="features-inner">
            <h2 class="section-title">How It Works</h2>
            <div class="features-grid">
                <div class="feature-card">
                    <div class="feature-icon">📤</div>
                    <h3>Submit Research</h3>
                    <p>Students upload their research papers in PDF or Word format for analysis.</p>
                </div>
                <div class="feature-card">
                    <div class="feature-icon">🔍</div>
                    <h3>Plagiarism Check</h3>
                    <p>System runs similarity analysis and generates a plagiarism score automatically.</p>
                </div>
                <div class="feature-card">
                    <div class="feature-icon">👨‍🏫</div>
                    <h3>Supervisor Review</h3>
                    <p>Supervisors review submissions, leave comments and approve or reject papers.</p>
                </div>
                <div class="feature-card">
                    <div class="feature-icon">🛡️</div>
                    <h3>Admin Control</h3>
                    <p>Admins manage all users, monitor submissions and generate system reports.</p>
                </div>
            </div>
        </div>
    </section>

    <section class="roles-section">
        <div class="roles-inner">
            <h2 class="section-title">Who Uses URSS?</h2>
            <div class="roles-grid">
                <div class="role-card student">
                    <div class="role-icon">🎓</div>
                    <h3>Students</h3>
                    <ul>
                        <li>Submit research papers</li>
                        <li>View plagiarism scores</li>
                        <li>Track supervisor feedback</li>
                        <li>Resubmit revised work</li>
                    </ul>
                    <a href="system/register.php?role=student" class="btn btn-primary">Register as Student</a>
                </div>
                <div class="role-card supervisor">
                    <div class="role-icon">👨‍🏫</div>
                    <h3>Supervisors</h3>
                    <ul>
                        <li>View assigned students</li>
                        <li>Review submitted papers</li>
                        <li>Leave detailed comments</li>
                        <li>Approve or reject work</li>
                    </ul>
                    <a href="system/register.php?role=supervisor" class="btn btn-outline">Register as Supervisor</a>
                </div>
            </div>
        </div>
    </section>

    <footer class="footer">
        <div class="footer-inner">
            <div class="footer-brand">
                <span class="brand-icon">📘</span>
                <span class="brand-name">URSS Portal</span>
            </div>
            <p class="footer-desc">Online Research Management System</p>
            <p class="footer-contact">Support: +234 808 419 2480 (WhatsApp)</p>
            <p class="footer-copy">© 2026 URSS Portal. All rights reserved.</p>
        </div>
    </footer>

    <script>
        const hamburger = document.getElementById('hamburger');
        const mobileMenu = document.getElementById('mobileMenu');
        hamburger.addEventListener('click', () => {
            mobileMenu.classList.toggle('open');
            hamburger.classList.toggle('active');
        });
    </script>
</body>
</html>
