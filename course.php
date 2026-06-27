<?php
require_once __DIR__ . '/config/db.php';
$base = '/FS project3';

$id = intval($_GET['id'] ?? 0);
if (!$id) redirect($base . '/courses.php');

$stmt = $conn->prepare("SELECT c.*, u.name as instructor_name, u.bio as instructor_bio
    FROM courses c JOIN users u ON c.instructor_id = u.id WHERE c.id = ?");
$stmt->bind_param("i", $id);
$stmt->execute();
$course = $stmt->get_result()->fetch_assoc();
if (!$course) redirect($base . '/courses.php');

$lessons = $conn->query("SELECT * FROM lessons WHERE course_id = $id ORDER BY lesson_order");
$lessonList = [];
while ($l = $lessons->fetch_assoc()) $lessonList[] = $l;

$enrollCount = $conn->query("SELECT COUNT(*) as cnt FROM enrollments WHERE course_id = $id")->fetch_assoc()['cnt'];
$lessonCount = count($lessonList);

// Check enrollment
$isEnrolled = false;
if (isLoggedIn()) {
    $uid = $_SESSION['user_id'];
    $check = $conn->query("SELECT id FROM enrollments WHERE user_id = $uid AND course_id = $id");
    $isEnrolled = $check->num_rows > 0;
}

// Handle Enroll
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['enroll'])) {
    requireLogin();
    if (!$isEnrolled) {
        $uid = $_SESSION['user_id'];
        $conn->query("INSERT IGNORE INTO enrollments (user_id, course_id) VALUES ($uid, $id)");
        $_SESSION['alert'] = ['msg' => "You've enrolled in " . $course['title'] . "! 🎉", 'type' => 'success'];
        redirect($base . "/student/watch.php?course_id=$id");
    }
}

$pageTitle = $course['title'];
// Calculate total duration
$totalMins = 0;
foreach ($lessonList as $l) { preg_match('/\d+/', $l['duration'], $m); $totalMins += intval($m[0] ?? 0); }
$totalHours = round($totalMins / 60, 1);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="<?php echo htmlspecialchars(substr($course['description'], 0, 160)); ?>">
    <title><?php echo htmlspecialchars($course['title']); ?> | InnovExa LMS</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800;900&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="<?php echo $base; ?>/assets/css/style.css">
</head>
<body>
<?php require_once __DIR__ . '/includes/header.php'; ?>

<main style="position:relative;z-index:1;padding:2.5rem 0;">
    <div class="container">
        <div class="breadcrumb">
            <a href="<?php echo $base; ?>/courses.php">Courses</a>
            <span>›</span>
            <span><?php echo htmlspecialchars($course['category']); ?></span>
            <span>›</span>
            <span><?php echo htmlspecialchars(substr($course['title'], 0, 40)); ?>...</span>
        </div>

        <div style="display:grid;grid-template-columns:1fr 340px;gap:2rem;align-items:start;" id="courseDetailGrid">
            <!-- Left: Course Info -->
            <div>
                <div class="course-hero">
                    <div class="card-meta" style="margin-bottom:1rem;">
                        <span class="badge badge-primary"><?php echo htmlspecialchars($course['category']); ?></span>
                        <span class="badge badge-info"><?php echo htmlspecialchars($course['level']); ?></span>
                        <?php if ($isEnrolled): ?><span class="badge badge-success">✓ Enrolled</span><?php endif; ?>
                    </div>
                    <h1 style="font-size:clamp(1.6rem,3vw,2.2rem);font-weight:800;line-height:1.2;margin-bottom:1rem;">
                        <?php echo htmlspecialchars($course['title']); ?>
                    </h1>
                    <p style="color:var(--text-secondary);line-height:1.7;margin-bottom:1.5rem;">
                        <?php echo htmlspecialchars($course['description']); ?>
                    </p>
                    <div style="display:flex;gap:2rem;flex-wrap:wrap;font-size:0.88rem;color:var(--text-secondary);">
                        <span>👤 <strong><?php echo htmlspecialchars($course['instructor_name']); ?></strong></span>
                        <span>📚 <strong><?php echo $lessonCount; ?></strong> lessons</span>
                        <span>⏱️ <strong><?php echo $totalHours; ?>h</strong> total</span>
                        <span>👥 <strong><?php echo $enrollCount; ?></strong> students</span>
                    </div>
                    <?php if ($course['thumbnail']): ?>
                    <img src="<?php echo htmlspecialchars($course['thumbnail']); ?>" 
                         alt="<?php echo htmlspecialchars($course['title']); ?>"
                         style="width:100%;border-radius:var(--radius);margin-top:1.5rem;max-height:280px;object-fit:cover;">
                    <?php endif; ?>
                </div>

                <!-- Syllabus -->
                <div class="card" style="margin-bottom:1.5rem;">
                    <div style="padding:1.5rem;">
                        <h2 style="font-size:1.1rem;font-weight:700;margin-bottom:1.25rem;">📋 Course Curriculum</h2>
                        <?php foreach ($lessonList as $i => $lesson): ?>
                        <div class="lesson-item" style="cursor:default;">
                            <div class="lesson-num"><?php echo $i + 1; ?></div>
                            <div class="lesson-info">
                                <div class="lesson-title"><?php echo htmlspecialchars($lesson['title']); ?></div>
                                <div class="lesson-duration">⏱️ <?php echo htmlspecialchars($lesson['duration']); ?>
                                    <?php if ($lesson['description']): ?>
                                        · <?php echo htmlspecialchars($lesson['description']); ?>
                                    <?php endif; ?>
                                </div>
                            </div>
                            <?php if ($isEnrolled): ?>
                                <a href="<?php echo $base; ?>/student/watch.php?course_id=<?php echo $id; ?>&lesson_id=<?php echo $lesson['id']; ?>" class="btn btn-primary btn-sm">▶ Play</a>
                            <?php else: ?>
                                <span style="color:var(--text-muted);font-size:0.8rem;">🔒</span>
                            <?php endif; ?>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>

                <!-- Instructor -->
                <div class="card">
                    <div style="padding:1.5rem;">
                        <h2 style="font-size:1.1rem;font-weight:700;margin-bottom:1rem;">👩‍🏫 Your Instructor</h2>
                        <div style="display:flex;align-items:center;gap:1rem;margin-bottom:0.75rem;">
                            <div style="width:56px;height:56px;border-radius:50%;background:linear-gradient(135deg,var(--primary),var(--secondary));display:flex;align-items:center;justify-content:center;font-weight:700;font-size:1.2rem;flex-shrink:0;">
                                <?php echo strtoupper(substr($course['instructor_name'], 0, 2)); ?>
                            </div>
                            <div>
                                <p style="font-weight:700;"><?php echo htmlspecialchars($course['instructor_name']); ?></p>
                                <p style="font-size:0.82rem;color:var(--text-muted);">Expert Instructor</p>
                            </div>
                        </div>
                        <?php if ($course['instructor_bio']): ?>
                            <p style="color:var(--text-secondary);font-size:0.88rem;line-height:1.6;"><?php echo htmlspecialchars($course['instructor_bio']); ?></p>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <!-- Right: Enroll Box -->
            <div class="enroll-box" id="enrollBox">
                <div class="enroll-price">
                    <?php echo $course['price'] == 0 ? '🎉 Free' : '$' . number_format($course['price'], 2); ?>
                </div>
                <?php if ($isEnrolled): ?>
                    <a href="<?php echo $base; ?>/student/watch.php?course_id=<?php echo $id; ?>" class="btn btn-success btn-full btn-lg" id="continueLearning">
                        ▶ Continue Learning
                    </a>
                    <p style="text-align:center;color:var(--accent);font-size:0.85rem;margin-top:0.75rem;">✓ You're enrolled!</p>
                <?php elseif (isLoggedIn() && $_SESSION['user_role'] === 'student'): ?>
                    <form method="POST">
                        <button type="submit" name="enroll" class="btn btn-primary btn-full btn-lg" id="enrollBtn">
                            🚀 Enroll Now
                        </button>
                    </form>
                <?php elseif (!isLoggedIn()): ?>
                    <a href="<?php echo $base; ?>/auth/login.php" class="btn btn-primary btn-full btn-lg" id="enrollLogin">
                        🔑 Login to Enroll
                    </a>
                    <a href="<?php echo $base; ?>/auth/register.php" class="btn btn-secondary btn-full mt-2" id="enrollRegister">
                        ✨ Create Free Account
                    </a>
                <?php endif; ?>

                <hr class="divider">
                <ul style="list-style:none;font-size:0.85rem;color:var(--text-secondary);">
                    <li style="margin-bottom:0.6rem;">📚 <?php echo $lessonCount; ?> lessons</li>
                    <li style="margin-bottom:0.6rem;">⏱️ <?php echo $totalHours; ?> hours of content</li>
                    <li style="margin-bottom:0.6rem;">📊 <?php echo htmlspecialchars($course['level']); ?> level</li>
                    <li style="margin-bottom:0.6rem;">🏆 Certificate of completion</li>
                    <li style="margin-bottom:0.6rem;">📱 Access on all devices</li>
                    <li>🔄 Lifetime access</li>
                </ul>
            </div>
        </div>
    </div>
</main>

<style>
@media (max-width: 900px) {
    #courseDetailGrid { grid-template-columns: 1fr !important; }
    .enroll-box { position: static !important; }
}
</style>
<?php require_once __DIR__ . '/includes/footer.php'; ?>
</body>
</html>
