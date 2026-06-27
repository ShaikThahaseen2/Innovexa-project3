<?php
require_once __DIR__ . '/config/db.php';
$base = '/FS project3';
$pageTitle = 'Browse Courses';

$search = sanitize($conn, $_GET['search'] ?? '');
$category = sanitize($conn, $_GET['category'] ?? '');
$level = sanitize($conn, $_GET['level'] ?? '');

$sql = "SELECT c.*, u.name as instructor_name, 
    (SELECT COUNT(*) FROM enrollments WHERE course_id = c.id) as enrollment_count,
    (SELECT COUNT(*) FROM lessons WHERE course_id = c.id) as lesson_count
    FROM courses c JOIN users u ON c.instructor_id = u.id WHERE 1=1";

if ($search) $sql .= " AND (c.title LIKE '%$search%' OR c.description LIKE '%$search%')";
if ($category) $sql .= " AND c.category = '$category'";
if ($level) $sql .= " AND c.level = '$level'";
$sql .= " ORDER BY c.created_at DESC";

$courses = $conn->query($sql);
$categories = $conn->query("SELECT DISTINCT category FROM courses ORDER BY category");
$totalCourses = $conn->query("SELECT COUNT(*) as cnt FROM courses")->fetch_assoc()['cnt'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="Browse all courses on InnovExa LMS. Find the perfect course in web development, data science, design and more.">
    <title>Browse Courses | InnovExa LMS</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800;900&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="<?php echo $base; ?>/assets/css/style.css">
</head>
<body>
<?php require_once __DIR__ . '/includes/header.php'; ?>

<main style="position:relative;z-index:1;padding:2.5rem 0;">
    <div class="container">
        <div class="page-header">
            <h1 class="page-title">Browse Courses</h1>
            <p class="page-subtitle"><?php echo $totalCourses; ?> courses available across all categories</p>
        </div>

        <!-- Search & Filter -->
        <form method="GET" id="filterForm">
            <div class="filter-row">
                <div class="search-bar" style="flex:1;min-width:220px;">
                    <span class="search-icon">🔍</span>
                    <input type="text" name="search" id="searchInput" class="form-control" placeholder="Search courses..." value="<?php echo htmlspecialchars($search); ?>">
                </div>
                <select name="category" id="categoryFilter" class="form-control" style="width:auto;" onchange="this.form.submit()">
                    <option value="">All Categories</option>
                    <?php while ($cat = $categories->fetch_assoc()): ?>
                        <option value="<?php echo htmlspecialchars($cat['category']); ?>" <?php echo $category === $cat['category'] ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($cat['category']); ?>
                        </option>
                    <?php endwhile; ?>
                </select>
                <select name="level" id="levelFilter" class="form-control" style="width:auto;" onchange="this.form.submit()">
                    <option value="">All Levels</option>
                    <option value="Beginner" <?php echo $level === 'Beginner' ? 'selected' : ''; ?>>Beginner</option>
                    <option value="Intermediate" <?php echo $level === 'Intermediate' ? 'selected' : ''; ?>>Intermediate</option>
                    <option value="Advanced" <?php echo $level === 'Advanced' ? 'selected' : ''; ?>>Advanced</option>
                </select>
                <button type="submit" class="btn btn-primary" id="applyFilter">Search</button>
                <?php if ($search || $category || $level): ?>
                    <a href="<?php echo $base; ?>/courses.php" class="btn btn-secondary" id="clearFilter">Clear</a>
                <?php endif; ?>
            </div>
        </form>

        <?php if ($courses->num_rows === 0): ?>
            <div style="text-align:center;padding:4rem 0;">
                <div style="font-size:4rem;margin-bottom:1rem;">🔍</div>
                <h2 style="font-size:1.4rem;margin-bottom:0.5rem;">No courses found</h2>
                <p style="color:var(--text-secondary);">Try adjusting your search filters.</p>
                <a href="<?php echo $base; ?>/courses.php" class="btn btn-primary mt-2">View All Courses</a>
            </div>
        <?php else: ?>
        <div class="grid grid-auto" style="gap:1.5rem;">
            <?php while ($course = $courses->fetch_assoc()): ?>
            <a href="<?php echo $base; ?>/course.php?id=<?php echo $course['id']; ?>" class="card course-card" style="text-decoration:none;color:inherit;" id="courseCard<?php echo $course['id']; ?>">
                <?php if ($course['thumbnail']): ?>
                    <img src="<?php echo htmlspecialchars($course['thumbnail']); ?>" alt="<?php echo htmlspecialchars($course['title']); ?>" class="card-thumb" loading="lazy">
                <?php else: ?>
                    <div class="card-thumb-placeholder">📚</div>
                <?php endif; ?>
                <div class="card-body">
                    <div class="card-meta">
                        <span class="badge badge-primary"><?php echo htmlspecialchars($course['category']); ?></span>
                        <span class="badge badge-info"><?php echo htmlspecialchars($course['level']); ?></span>
                    </div>
                    <h2 class="card-title"><?php echo htmlspecialchars($course['title']); ?></h2>
                    <p class="card-desc"><?php echo htmlspecialchars($course['description']); ?></p>
                    <div style="display:flex;align-items:center;gap:1rem;font-size:0.8rem;color:var(--text-muted);">
                        <span>👤 <?php echo htmlspecialchars($course['instructor_name']); ?></span>
                        <span>📚 <?php echo $course['lesson_count']; ?> lessons</span>
                        <span>👥 <?php echo $course['enrollment_count']; ?> students</span>
                    </div>
                </div>
                <div class="card-footer">
                    <span class="price <?php echo $course['price'] == 0 ? 'free' : ''; ?>">
                        <?php echo $course['price'] == 0 ? '🎉 Free' : '$' . number_format($course['price'], 2); ?>
                    </span>
                    <span class="btn btn-primary btn-sm">View Course</span>
                </div>
            </a>
            <?php endwhile; ?>
        </div>
        <?php endif; ?>
    </div>
</main>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
</body>
</html>
