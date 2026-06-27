<?php
require_once __DIR__ . '/../config/db.php';
header('Content-Type: application/json');

if (!isLoggedIn()) {
    echo json_encode(['success' => false, 'error' => 'Not authenticated']);
    exit();
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'error' => 'Invalid method']);
    exit();
}

$uid = intval($_SESSION['user_id']);
$lessonId = intval($_POST['lesson_id'] ?? 0);
$completed = intval($_POST['completed'] ?? 1);

if (!$lessonId) {
    echo json_encode(['success' => false, 'error' => 'Invalid lesson']);
    exit();
}

// Verify the lesson exists and user has access via enrollment
$check = $conn->query("
    SELECT l.id, l.course_id FROM lessons l 
    JOIN enrollments e ON e.course_id = l.course_id 
    WHERE l.id = $lessonId AND e.user_id = $uid
");
if ($check->num_rows === 0) {
    echo json_encode(['success' => false, 'error' => 'Access denied']);
    exit();
}
$lessonData = $check->fetch_assoc();
$courseId = $lessonData['course_id'];

if ($completed) {
    $conn->query("INSERT IGNORE INTO progress (user_id, lesson_id, is_completed) VALUES ($uid, $lessonId, 1)");
} else {
    $conn->query("DELETE FROM progress WHERE user_id = $uid AND lesson_id = $lessonId");
}

// Recalculate progress
$total = $conn->query("SELECT COUNT(*) as cnt FROM lessons WHERE course_id = $courseId")->fetch_assoc()['cnt'];
$done = $conn->query("
    SELECT COUNT(*) as cnt FROM progress p 
    JOIN lessons l ON p.lesson_id = l.id 
    WHERE l.course_id = $courseId AND p.user_id = $uid AND p.is_completed = 1
")->fetch_assoc()['cnt'];

$progress = $total > 0 ? ($done / $total) * 100 : 0;

echo json_encode([
    'success' => true,
    'progress' => round($progress, 1),
    'completed' => $done,
    'total' => $total
]);
?>
