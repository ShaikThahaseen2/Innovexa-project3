<?php
require_once __DIR__ . '/../config/db.php';
$base = '/FS project3';
requireRole('instructor');
$uid = $_SESSION['user_id'];

$editId = intval($_GET['id'] ?? 0);
$course = null;
$lessons = [];
$isEdit = false;

if ($editId) {
    $stmt = $conn->prepare("SELECT * FROM courses WHERE id = ? AND instructor_id = ?");
    $stmt->bind_param("ii", $editId, $uid);
    $stmt->execute();
    $course = $stmt->get_result()->fetch_assoc();
    if ($course) {
        $isEdit = true;
        $lr = $conn->query("SELECT * FROM lessons WHERE course_id = $editId ORDER BY lesson_order");
        while ($l = $lr->fetch_assoc()) $lessons[] = $l;
    }
}

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = sanitize($conn, $_POST['title'] ?? '');
    $description = sanitize($conn, $_POST['description'] ?? '');
    $category = sanitize($conn, $_POST['category'] ?? '');
    $price = floatval($_POST['price'] ?? 0);
    $level = in_array($_POST['level'] ?? '', ['Beginner','Intermediate','Advanced']) ? $_POST['level'] : 'Beginner';
    $thumbnail = sanitize($conn, $_POST['thumbnail'] ?? '');

    if (empty($title) || empty($description) || empty($category)) {
        $error = 'Please fill in all required course fields.';
    } else {
        if ($isEdit) {
            $stmt = $conn->prepare("UPDATE courses SET title=?, description=?, category=?, price=?, level=?, thumbnail=? WHERE id=? AND instructor_id=?");
            $stmt->bind_param("sssdssii", $title, $description, $category, $price, $level, $thumbnail, $editId, $uid);
            $stmt->execute();
            $courseId = $editId;
        } else {
            $stmt = $conn->prepare("INSERT INTO courses (title, description, instructor_id, category, price, level, thumbnail) VALUES (?,?,?,?,?,?,?)");
            $stmt->bind_param("ssisdss", $title, $description, $uid, $category, $price, $level, $thumbnail);
            $stmt->execute();
            $courseId = $conn->insert_id;
        }

        // Handle lessons
        if ($isEdit) {
            // Delete removed lessons
            $keptIds = array_filter(array_map('intval', $_POST['lesson_id'] ?? []), fn($v) => $v > 0);
            if (!empty($keptIds)) {
                $inList = implode(',', $keptIds);
                $conn->query("DELETE FROM lessons WHERE course_id = $courseId AND id NOT IN ($inList)");
            } else {
                $conn->query("DELETE FROM lessons WHERE course_id = $courseId");
            }
        }

        $lessonTitles = $_POST['lesson_title'] ?? [];
        $lessonDescs = $_POST['lesson_desc'] ?? [];
        $lessonUrls = $_POST['lesson_url'] ?? [];
        $lessonDurations = $_POST['lesson_duration'] ?? [];
        $lessonIds = $_POST['lesson_id'] ?? [];

        foreach ($lessonTitles as $i => $ltitle) {
            $ltitle = sanitize($conn, $ltitle);
            $ldesc = sanitize($conn, $lessonDescs[$i] ?? '');
            $lurl = sanitize($conn, $lessonUrls[$i] ?? '');
            $ldur = sanitize($conn, $lessonDurations[$i] ?? '10 mins');
            $lorder = $i + 1;
            $lid = intval($lessonIds[$i] ?? 0);

            if (empty($ltitle) || empty($lurl)) continue;

            if ($lid > 0) {
                $stmt = $conn->prepare("UPDATE lessons SET title=?, description=?, video_url=?, duration=?, lesson_order=? WHERE id=? AND course_id=?");
                $stmt->bind_param("ssssiiii", $ltitle, $ldesc, $lurl, $ldur, $lorder, $lid, $courseId);
                $stmt->execute();
            } else {
                $stmt = $conn->prepare("INSERT INTO lessons (course_id, title, description, video_url, duration, lesson_order) VALUES (?,?,?,?,?,?)");
                $stmt->bind_param("issssi", $courseId, $ltitle, $ldesc, $lurl, $ldur, $lorder);
                $stmt->execute();
            }
        }

        $_SESSION['alert'] = ['msg' => $isEdit ? 'Course updated successfully!' : 'Course created successfully! 🎉', 'type' => 'success'];
        redirect($base . '/instructor/dashboard.php');
    }
}

$pageTitle = $isEdit ? 'Edit Course' : 'Create Course';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="<?php echo $isEdit ? 'Edit your course' : 'Create a new course'; ?> on InnovExa LMS.">
    <title><?php echo $pageTitle; ?> | InnovExa LMS</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800;900&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="<?php echo $base; ?>/assets/css/style.css">
</head>
<body>
<?php require_once __DIR__ . '/../includes/header.php'; ?>

<div style="position:relative;z-index:1;padding:2.5rem 0;">
    <div class="container" style="max-width:860px;">
        <div class="page-header">
            <div class="breadcrumb">
                <a href="<?php echo $base; ?>/instructor/dashboard.php">Dashboard</a>
                <span>›</span>
                <span><?php echo $isEdit ? 'Edit Course' : 'Create Course'; ?></span>
            </div>
            <h1 class="page-title"><?php echo $isEdit ? '✏️ Edit Course' : '➕ Create New Course'; ?></h1>
            <p class="page-subtitle"><?php echo $isEdit ? 'Update your course details and lessons below.' : 'Fill in the details to publish your course.'; ?></p>
        </div>

        <?php if ($error): ?>
            <div class="alert alert-error">⚠️ <?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>

        <form method="POST" id="manageCourseForm">
            <!-- Course Details -->
            <div class="card" style="margin-bottom:1.5rem;">
                <div style="padding:1.5rem;">
                    <h2 style="font-size:1rem;font-weight:700;margin-bottom:1.25rem;padding-bottom:0.75rem;border-bottom:1px solid var(--border);">📋 Course Details</h2>
                    <div class="form-group">
                        <label class="form-label" for="courseTitle">Course Title *</label>
                        <input type="text" id="courseTitle" name="title" class="form-control" placeholder="e.g. Complete Web Development Bootcamp" value="<?php echo htmlspecialchars($course['title'] ?? ''); ?>" required>
                    </div>
                    <div class="form-group">
                        <label class="form-label" for="courseDesc">Description *</label>
                        <textarea id="courseDesc" name="description" class="form-control" rows="4" placeholder="Describe what students will learn..." required><?php echo htmlspecialchars($course['description'] ?? ''); ?></textarea>
                    </div>
                    <div style="display:grid;grid-template-columns:1fr 1fr 1fr;gap:1rem;">
                        <div class="form-group">
                            <label class="form-label" for="courseCategory">Category *</label>
                            <input type="text" id="courseCategory" name="category" class="form-control" placeholder="e.g. Web Development" value="<?php echo htmlspecialchars($course['category'] ?? ''); ?>" required>
                        </div>
                        <div class="form-group">
                            <label class="form-label" for="courseLevel">Level</label>
                            <select id="courseLevel" name="level" class="form-control">
                                <?php foreach (['Beginner','Intermediate','Advanced'] as $lv): ?>
                                <option value="<?php echo $lv; ?>" <?php echo ($course['level'] ?? 'Beginner') === $lv ? 'selected' : ''; ?>><?php echo $lv; ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="form-group">
                            <label class="form-label" for="coursePrice">Price ($)</label>
                            <input type="number" id="coursePrice" name="price" class="form-control" min="0" step="0.01" value="<?php echo $course['price'] ?? 0; ?>">
                        </div>
                    </div>
                    <div class="form-group">
                        <label class="form-label" for="courseThumbnail">Thumbnail URL</label>
                        <input type="url" id="courseThumbnail" name="thumbnail" class="form-control" placeholder="https://images.unsplash.com/..." value="<?php echo htmlspecialchars($course['thumbnail'] ?? ''); ?>">
                    </div>
                </div>
            </div>

            <!-- Lessons -->
            <div class="card" style="margin-bottom:1.5rem;">
                <div style="padding:1.5rem;">
                    <div class="flex-between" style="margin-bottom:1.25rem;padding-bottom:0.75rem;border-bottom:1px solid var(--border);">
                        <h2 style="font-size:1rem;font-weight:700;">🎬 Course Lessons</h2>
                        <button type="button" id="addLesson" class="btn btn-secondary btn-sm" onclick="addLessonRow()">+ Add Lesson</button>
                    </div>
                    <div id="lessonsContainer">
                        <?php foreach ($lessons as $i => $lesson): ?>
                        <div class="lesson-row" id="lessonRow<?php echo $i; ?>">
                            <input type="hidden" name="lesson_id[]" value="<?php echo $lesson['id']; ?>">
                            <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:0.75rem;">
                                <span style="font-size:0.85rem;font-weight:700;color:var(--primary-light);">Lesson <?php echo $i+1; ?></span>
                                <button type="button" class="btn btn-danger btn-sm" onclick="this.closest('.lesson-row').remove();renumberLessons()">✕ Remove</button>
                            </div>
                            <div style="display:grid;grid-template-columns:1fr 1fr;gap:0.75rem;">
                                <div class="form-group">
                                    <label class="form-label">Title *</label>
                                    <input type="text" name="lesson_title[]" class="form-control" placeholder="Lesson title" value="<?php echo htmlspecialchars($lesson['title']); ?>" required>
                                </div>
                                <div class="form-group">
                                    <label class="form-label">Duration (e.g. "15 mins")</label>
                                    <input type="text" name="lesson_duration[]" class="form-control" placeholder="15 mins" value="<?php echo htmlspecialchars($lesson['duration']); ?>">
                                </div>
                            </div>
                            <div class="form-group">
                                <label class="form-label">YouTube Embed URL *</label>
                                <input type="text" name="lesson_url[]" class="form-control" placeholder="https://www.youtube.com/embed/VIDEO_ID" value="<?php echo htmlspecialchars($lesson['video_url']); ?>" required>
                            </div>
                            <div class="form-group" style="margin-bottom:0;">
                                <label class="form-label">Description (optional)</label>
                                <input type="text" name="lesson_desc[]" class="form-control" placeholder="Brief description of this lesson" value="<?php echo htmlspecialchars($lesson['description']); ?>">
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                    <?php if (empty($lessons)): ?>
                    <div id="emptyLessonsMsg" style="text-align:center;padding:2rem;color:var(--text-muted);border:2px dashed rgba(255,255,255,0.1);border-radius:var(--radius-sm);">
                        No lessons yet. Click "+ Add Lesson" to get started.
                    </div>
                    <?php endif; ?>
                </div>
            </div>

            <div style="display:flex;gap:1rem;justify-content:flex-end;">
                <a href="<?php echo $base; ?>/instructor/dashboard.php" class="btn btn-secondary" id="cancelBtn">Cancel</a>
                <button type="submit" class="btn btn-primary btn-lg" id="saveBtn">
                    <?php echo $isEdit ? '💾 Save Changes' : '🚀 Publish Course'; ?>
                </button>
            </div>
        </form>
    </div>
</div>

<script>
let lessonCount = <?php echo max(count($lessons), 0); ?>;
function addLessonRow() {
    const empty = document.getElementById('emptyLessonsMsg');
    if (empty) empty.remove();
    const container = document.getElementById('lessonsContainer');
    const row = document.createElement('div');
    row.className = 'lesson-row';
    row.id = 'lessonRow' + lessonCount;
    row.innerHTML = `
        <input type="hidden" name="lesson_id[]" value="0">
        <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:0.75rem;">
            <span style="font-size:0.85rem;font-weight:700;color:var(--primary-light);">Lesson ${lessonCount + 1}</span>
            <button type="button" class="btn btn-danger btn-sm" onclick="this.closest('.lesson-row').remove();renumberLessons()">✕ Remove</button>
        </div>
        <div style="display:grid;grid-template-columns:1fr 1fr;gap:0.75rem;">
            <div class="form-group"><label class="form-label">Title *</label>
                <input type="text" name="lesson_title[]" class="form-control" placeholder="Lesson title" required></div>
            <div class="form-group"><label class="form-label">Duration</label>
                <input type="text" name="lesson_duration[]" class="form-control" placeholder="15 mins" value="10 mins"></div>
        </div>
        <div class="form-group"><label class="form-label">YouTube Embed URL *</label>
            <input type="text" name="lesson_url[]" class="form-control" placeholder="https://www.youtube.com/embed/VIDEO_ID" required></div>
        <div class="form-group" style="margin-bottom:0;"><label class="form-label">Description (optional)</label>
            <input type="text" name="lesson_desc[]" class="form-control" placeholder="Brief description"></div>`;
    container.appendChild(row);
    lessonCount++;
    row.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
}
function renumberLessons() {
    document.querySelectorAll('.lesson-row').forEach((row, i) => {
        const label = row.querySelector('span[style*="primary-light"]');
        if (label) label.textContent = 'Lesson ' + (i + 1);
    });
}
</script>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>
</body>
</html>
