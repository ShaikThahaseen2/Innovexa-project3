<?php
require_once __DIR__ . '/../config/db.php';
$base = '/FS project3';
requireRole('student');
$uid = $_SESSION['user_id'];
$pageTitle = 'Student Dashboard';

// Fetch enrolled courses with progress
$enrolled = $conn->query("
    SELECT c.*, e.enrolled_at, u.name as instructor_name,
        (SELECT COUNT(*) FROM lessons WHERE course_id = c.id) as total_lessons,
        (SELECT COUNT(*) FROM progress p 
            JOIN lessons l ON p.lesson_id = l.id 
            WHERE l.course_id = c.id AND p.user_id = $uid AND p.is_completed = 1) as completed_lessons
    FROM enrollments e
    JOIN courses c ON e.course_id = c.id
    JOIN users u ON c.instructor_id = u.id
    WHERE e.user_id = $uid
    ORDER BY e.enrolled_at DESC
");

$enrolledList = [];
while ($r = $enrolled->fetch_assoc()) $enrolledList[] = $r;

// Stats
$totalEnrolled = count($enrolledList);
$totalCompleted = 0;
$totalLessonsCompleted = 0;
foreach ($enrolledList as $c) {
    $totalLessonsCompleted += $c['completed_lessons'];
    if ($c['total_lessons'] > 0 && $c['completed_lessons'] >= $c['total_lessons']) $totalCompleted++;
}

$user = $conn->query("SELECT * FROM users WHERE id = $uid")->fetch_assoc();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="Your InnovExa LMS student dashboard - track your learning progress.">
    <title>Student Dashboard | InnovExa LMS</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800;900&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="<?php echo $base; ?>/assets/css/style.css">
</head>
<body>
<?php require_once __DIR__ . '/../includes/header.php'; ?>

<div class="page-wrapper">
    <!-- Sidebar -->
    <aside class="sidebar" id="sidebar">
        <div>
            <div class="sidebar-section">
                <p class="sidebar-label">Main</p>
                <ul class="sidebar-nav">
                    <li><a href="<?php echo $base; ?>/student/dashboard.php" class="active" id="sidebarDash"><span class="nav-icon">📊</span> Dashboard</a></li>
                    <li><a href="<?php echo $base; ?>/courses.php" id="sidebarBrowse"><span class="nav-icon">🎓</span> Browse Courses</a></li>
                    <li><a href="<?php echo $base; ?>/profile.php" id="sidebarProfile"><span class="nav-icon">👤</span> My Profile</a></li>
                </ul>
            </div>
            <div class="sidebar-section">
                <p class="sidebar-label">My Courses</p>
                <ul class="sidebar-nav">
                    <?php foreach ($enrolledList as $c): ?>
                    <li>
                        <a href="<?php echo $base; ?>/student/watch.php?course_id=<?php echo $c['id']; ?>" id="sidebarCourse<?php echo $c['id']; ?>">
                            <span class="nav-icon">▶</span>
                            <?php echo htmlspecialchars(substr($c['title'], 0, 22)); ?>...
                        </a>
                    </li>
                    <?php endforeach; ?>
                    <?php if (empty($enrolledList)): ?>
                    <li><a href="<?php echo $base; ?>/courses.php" style="color:var(--text-muted);font-style:italic;">No courses yet</a></li>
                    <?php endif; ?>
                </ul>
            </div>
        </div>
        <div style="margin-top:auto;">
            <a href="<?php echo $base; ?>/auth/logout.php" class="btn btn-danger btn-full btn-sm" id="sidebarLogout">🚪 Logout</a>
        </div>
    </aside>

    <!-- Main -->
    <main class="main-content">
        <div class="page-header flex-between">
            <div>
                <h1 class="page-title">Welcome back, <?php echo htmlspecialchars(explode(' ', $user['name'])[0]); ?>! 👋</h1>
                <p class="page-subtitle">Here's your learning progress overview</p>
            </div>
            <a href="<?php echo $base; ?>/courses.php" class="btn btn-primary" id="dashBrowseMore">+ Explore Courses</a>
        </div>

        <!-- Stats -->
        <div class="stats-grid">
            <div class="stat-card" style="--icon-color:rgba(108,99,255,0.15);">
                <div class="stat-icon">🎓</div>
                <div class="stat-info">
                    <div class="stat-value" data-count="<?php echo $totalEnrolled; ?>">0</div>
                    <div class="stat-label">Enrolled Courses</div>
                </div>
            </div>
            <div class="stat-card" style="--icon-color:rgba(67,233,123,0.12);">
                <div class="stat-icon">✅</div>
                <div class="stat-info">
                    <div class="stat-value" data-count="<?php echo $totalLessonsCompleted; ?>">0</div>
                    <div class="stat-label">Lessons Completed</div>
                </div>
            </div>
            <div class="stat-card" style="--icon-color:rgba(255,193,7,0.12);">
                <div class="stat-icon">🏆</div>
                <div class="stat-info">
                    <div class="stat-value" data-count="<?php echo $totalCompleted; ?>">0</div>
                    <div class="stat-label">Courses Finished</div>
                </div>
            </div>
            <div class="stat-card" style="--icon-color:rgba(56,249,215,0.1);">
                <div class="stat-icon">🔥</div>
                <div class="stat-info">
                    <div class="stat-value">7</div>
                    <div class="stat-label">Day Streak</div>
                </div>
            </div>
        </div>

        <!-- Enrolled Courses -->
        <div class="page-header">
            <h2 style="font-size:1.2rem;font-weight:700;">📚 My Courses</h2>
        </div>

        <?php if (empty($enrolledList)): ?>
        <div style="text-align:center;padding:4rem 0;background:var(--bg-glass);border:1px solid var(--border);border-radius:var(--radius);">
            <div style="font-size:4rem;margin-bottom:1rem;">📭</div>
            <h3 style="margin-bottom:0.5rem;">You haven't enrolled yet</h3>
            <p style="color:var(--text-secondary);margin-bottom:1.5rem;">Browse our catalog and start learning today!</p>
            <a href="<?php echo $base; ?>/courses.php" class="btn btn-primary" id="dashEnrollNow">🎓 Browse Courses</a>
        </div>
        <?php else: ?>
        <div class="grid grid-auto" style="gap:1.5rem;">
            <?php foreach ($enrolledList as $c):
                $progress = $c['total_lessons'] > 0 ? ($c['completed_lessons'] / $c['total_lessons']) * 100 : 0;
            ?>
            <div class="card" id="studentCourse<?php echo $c['id']; ?>">
                <?php if ($c['thumbnail']): ?>
                    <img src="<?php echo htmlspecialchars($c['thumbnail']); ?>" alt="<?php echo htmlspecialchars($c['title']); ?>" class="card-thumb" style="height:150px;">
                <?php else: ?>
                    <div class="card-thumb-placeholder" style="height:120px;">📚</div>
                <?php endif; ?>
                <div class="card-body">
                    <div class="card-meta">
                        <span class="badge badge-primary"><?php echo htmlspecialchars($c['category']); ?></span>
                        <?php if ($progress >= 100): ?><span class="badge badge-success">✓ Complete</span><?php endif; ?>
                    </div>
                    <h3 class="card-title"><?php echo htmlspecialchars($c['title']); ?></h3>
                    <p style="font-size:0.8rem;color:var(--text-muted);margin-bottom:0.75rem;">by <?php echo htmlspecialchars($c['instructor_name']); ?></p>

                    <div class="progress-label">
                        <span><?php echo $c['completed_lessons']; ?>/<?php echo $c['total_lessons']; ?> lessons</span>
                        <span><?php echo round($progress); ?>%</span>
                    </div>
                    <div class="progress-wrap">
                        <div class="progress-bar" style="width:<?php echo $progress; ?>%;"></div>
                    </div>

                    <a href="<?php echo $base; ?>/student/watch.php?course_id=<?php echo $c['id']; ?>" class="btn btn-primary btn-full mt-2" id="continueCourse<?php echo $c['id']; ?>">
                        <?php echo $progress == 0 ? '🚀 Start Learning' : ($progress >= 100 ? '🏆 Review Course' : '▶ Continue'); ?>
                    </a>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>
    </main>
</div>

<script src="<?php echo $base; ?>/assets/js/main.js"></script>
</body>
</html>
