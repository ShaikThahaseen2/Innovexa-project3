<?php
require_once __DIR__ . '/../config/db.php';
$base = '/FS project3';
requireRole('student');
$uid = $_SESSION['user_id'];

$courseId = intval($_GET['course_id'] ?? 0);
if (!$courseId) redirect($base . '/student/dashboard.php');

// Verify enrollment
$enCheck = $conn->query("SELECT id FROM enrollments WHERE user_id = $uid AND course_id = $courseId");
if ($enCheck->num_rows === 0) redirect($base . "/course.php?id=$courseId");

// Fetch course
$course = $conn->query("SELECT c.*, u.name as instructor_name FROM courses c JOIN users u ON c.instructor_id = u.id WHERE c.id = $courseId")->fetch_assoc();
if (!$course) redirect($base . '/student/dashboard.php');

// Fetch all lessons
$lessonResult = $conn->query("SELECT * FROM lessons WHERE course_id = $courseId ORDER BY lesson_order");
$lessonList = [];
while ($l = $lessonResult->fetch_assoc()) $lessonList[] = $l;

// Current lesson
$lessonId = intval($_GET['lesson_id'] ?? 0);
if (!$lessonId && !empty($lessonList)) $lessonId = $lessonList[0]['id'];
$currentLesson = null;
foreach ($lessonList as $l) { if ($l['id'] == $lessonId) { $currentLesson = $l; break; } }
if (!$currentLesson && !empty($lessonList)) { $currentLesson = $lessonList[0]; $lessonId = $currentLesson['id']; }

// Fetch progress
$progressResult = $conn->query("SELECT lesson_id FROM progress WHERE user_id = $uid AND course_id_check IS NULL");
$completedMap = [];
$progressRows = $conn->query("SELECT lesson_id FROM progress WHERE user_id = $uid");
while ($p = $progressRows->fetch_assoc()) $completedMap[$p['lesson_id']] = true;

$totalLessons = count($lessonList);
$completedCount = count(array_intersect(array_column($lessonList, 'id'), array_keys($completedMap)));
$progressPct = $totalLessons > 0 ? ($completedCount / $totalLessons) * 100 : 0;

$pageTitle = $course['title'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="Watch <?php echo htmlspecialchars($course['title']); ?> on InnovExa LMS.">
    <title><?php echo htmlspecialchars($currentLesson['title'] ?? $course['title']); ?> | InnovExa LMS</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800;900&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="<?php echo $base; ?>/assets/css/style.css">
</head>
<body>
<?php require_once __DIR__ . '/../includes/header.php'; ?>

<div style="position:relative;z-index:1;padding:1.5rem 0;">
    <div class="container-fluid" style="max-width:1400px;margin:0 auto;padding:0 1.5rem;">

        <!-- Top bar -->
        <div class="flex-between" style="margin-bottom:1.25rem;flex-wrap:wrap;gap:0.75rem;">
            <div class="breadcrumb" style="margin-bottom:0;">
                <a href="<?php echo $base; ?>/student/dashboard.php">Dashboard</a>
                <span>›</span>
                <a href="<?php echo $base; ?>/course.php?id=<?php echo $courseId; ?>"><?php echo htmlspecialchars(substr($course['title'], 0, 30)); ?>...</a>
                <span>›</span>
                <span><?php echo htmlspecialchars($currentLesson['title'] ?? 'Lesson'); ?></span>
            </div>
            <div style="display:flex;align-items:center;gap:0.75rem;">
                <span style="font-size:0.82rem;color:var(--text-muted);"><?php echo $completedCount; ?>/<?php echo $totalLessons; ?> lessons</span>
                <div class="progress-wrap" style="width:140px;">
                    <div class="progress-bar" id="mainProgressBar" style="width:<?php echo $progressPct; ?>%;"></div>
                </div>
                <span style="font-size:0.82rem;font-weight:600;color:var(--accent);" id="mainProgressText"><?php echo round($progressPct); ?>%</span>
            </div>
        </div>

        <!-- Watch Layout -->
        <div class="watch-layout">
            <!-- Player Area -->
            <div>
                <?php if ($currentLesson): ?>
                <div class="player-container" id="videoPlayer">
                    <iframe src="<?php echo htmlspecialchars($currentLesson['video_url']); ?>?autoplay=0"
                            allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture"
                            allowfullscreen
                            title="<?php echo htmlspecialchars($currentLesson['title']); ?>">
                    </iframe>
                </div>

                <div class="card" style="margin-top:1rem;">
                    <div style="padding:1.5rem;">
                        <div class="flex-between" style="margin-bottom:1rem;flex-wrap:wrap;gap:0.75rem;">
                            <div>
                                <h1 style="font-size:1.3rem;font-weight:700;margin-bottom:0.3rem;"><?php echo htmlspecialchars($currentLesson['title']); ?></h1>
                                <p style="font-size:0.82rem;color:var(--text-muted);">Lesson <?php echo $currentLesson['lesson_order']; ?> · ⏱️ <?php echo htmlspecialchars($currentLesson['duration']); ?></p>
                            </div>
                            <label for="markComplete" style="display:flex;align-items:center;gap:10px;cursor:pointer;background:var(--bg-glass2);border:1px solid var(--border-primary);padding:10px 16px;border-radius:var(--radius-sm);">
                                <input type="checkbox" id="markComplete" 
                                       <?php echo isset($completedMap[$lessonId]) ? 'checked' : ''; ?>
                                       onchange="toggleLessonComplete(<?php echo $lessonId; ?>, this, document.getElementById('mainProgressBar'), document.getElementById('mainProgressText'))"
                                       style="width:18px;height:18px;accent-color:var(--primary);cursor:pointer;">
                                <span style="font-size:0.88rem;font-weight:600;">Mark as complete</span>
                            </label>
                        </div>
                        <?php if ($currentLesson['description']): ?>
                        <p style="color:var(--text-secondary);line-height:1.7;"><?php echo htmlspecialchars($currentLesson['description']); ?></p>
                        <?php endif; ?>

                        <!-- Navigation -->
                        <div class="flex-between" style="margin-top:1.5rem;flex-wrap:wrap;gap:0.5rem;">
                            <?php
                            $prevLesson = null; $nextLesson = null;
                            for ($i = 0; $i < count($lessonList); $i++) {
                                if ($lessonList[$i]['id'] == $lessonId) {
                                    if ($i > 0) $prevLesson = $lessonList[$i-1];
                                    if ($i < count($lessonList)-1) $nextLesson = $lessonList[$i+1];
                                    break;
                                }
                            }
                            ?>
                            <?php if ($prevLesson): ?>
                                <a href="?course_id=<?php echo $courseId; ?>&lesson_id=<?php echo $prevLesson['id']; ?>" class="btn btn-secondary" id="prevLesson">← Previous Lesson</a>
                            <?php else: ?><div></div><?php endif; ?>
                            <?php if ($nextLesson): ?>
                                <a href="?course_id=<?php echo $courseId; ?>&lesson_id=<?php echo $nextLesson['id']; ?>" class="btn btn-primary" id="nextLesson">Next Lesson →</a>
                            <?php else: ?>
                                <a href="<?php echo $base; ?>/student/dashboard.php" class="btn btn-success" id="backToDash">🏆 Course Complete!</a>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
                <?php else: ?>
                <div style="text-align:center;padding:4rem;background:var(--bg-card);border-radius:var(--radius);">
                    <div style="font-size:3rem;margin-bottom:1rem;">📭</div>
                    <p>No lessons in this course yet.</p>
                </div>
                <?php endif; ?>
            </div>

            <!-- Lesson Sidebar -->
            <div class="lesson-sidebar" id="lessonSidebar">
                <div class="lesson-sidebar-header">
                    <p class="lesson-sidebar-title"><?php echo htmlspecialchars($course['title']); ?></p>
                    <div class="progress-label mt-1">
                        <span style="font-size:0.75rem;"><?php echo $completedCount; ?> of <?php echo $totalLessons; ?> completed</span>
                        <span style="font-size:0.75rem;color:var(--accent);"><?php echo round($progressPct); ?>%</span>
                    </div>
                    <div class="progress-wrap mt-1" style="height:5px;">
                        <div class="progress-bar" style="width:<?php echo $progressPct; ?>%;"></div>
                    </div>
                </div>
                <div class="lesson-sidebar-body">
                    <?php foreach ($lessonList as $i => $lesson):
                        $isDone = isset($completedMap[$lesson['id']]);
                        $isActive = $lesson['id'] == $lessonId;
                    ?>
                    <a href="?course_id=<?php echo $courseId; ?>&lesson_id=<?php echo $lesson['id']; ?>"
                       class="lesson-item <?php echo $isActive ? 'active' : ''; ?> <?php echo $isDone ? 'completed' : ''; ?>"
                       style="text-decoration:none;color:inherit;"
                       id="sidebarLesson<?php echo $lesson['id']; ?>">
                        <div class="lesson-num <?php echo $isDone ? 'done' : ''; ?>" 
                             data-lesson="<?php echo $lesson['id']; ?>"
                             data-order="<?php echo $i+1; ?>">
                            <?php echo $isDone ? '✓' : ($i+1); ?>
                        </div>
                        <div class="lesson-info">
                            <div class="lesson-title"><?php echo htmlspecialchars($lesson['title']); ?></div>
                            <div class="lesson-duration">⏱️ <?php echo htmlspecialchars($lesson['duration']); ?></div>
                        </div>
                    </a>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
</body>
</html>
