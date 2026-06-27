<?php
require_once __DIR__ . '/../config/db.php';
$base = '/FS project3';
$currentPage = basename($_SERVER['PHP_SELF']);
$isLoggedIn = isLoggedIn();
$userRole = $_SESSION['user_role'] ?? null;
$userName = $_SESSION['user_name'] ?? '';
$alertMsg = '';
$alertType = '';
if (isset($_SESSION['alert'])) { $alertMsg = $_SESSION['alert']['msg']; $alertType = $_SESSION['alert']['type']; unset($_SESSION['alert']); }
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="InnovExa LMS - The most advanced learning management system. Learn from world-class instructors.">
    <title><?php echo isset($pageTitle) ? htmlspecialchars($pageTitle) . ' | InnovExa LMS' : 'InnovExa LMS - Learn Without Limits'; ?></title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800;900&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="<?php echo $base; ?>/assets/css/style.css">
</head>
<body>

<nav class="navbar" role="navigation" aria-label="Main navigation">
    <a href="<?php echo $base; ?>/index.php" class="navbar-brand" id="navBrand">
        <div class="logo-icon">🎓</div>
        <span>InnovExa</span>
    </a>
    <ul class="navbar-nav" id="mainNav">
        <li><a href="<?php echo $base; ?>/index.php" id="navHome">Home</a></li>
        <li><a href="<?php echo $base; ?>/courses.php" id="navCourses">Courses</a></li>
        <?php if ($isLoggedIn && $userRole === 'student'): ?>
            <li><a href="<?php echo $base; ?>/student/dashboard.php" id="navStudentDash">Dashboard</a></li>
        <?php endif; ?>
        <?php if ($isLoggedIn && $userRole === 'instructor'): ?>
            <li><a href="<?php echo $base; ?>/instructor/dashboard.php" id="navInstructorDash">Dashboard</a></li>
        <?php endif; ?>
    </ul>
    <div class="navbar-actions">
        <?php if ($isLoggedIn): ?>
            <a href="<?php echo $base; ?>/profile.php" class="btn btn-secondary btn-sm" id="navProfile">
                👤 <?php echo htmlspecialchars(explode(' ', $userName)[0]); ?>
            </a>
            <a href="<?php echo $base; ?>/auth/logout.php" class="btn btn-danger btn-sm" id="navLogout">Logout</a>
        <?php else: ?>
            <a href="<?php echo $base; ?>/auth/login.php" class="btn btn-secondary btn-sm" id="navLogin">Login</a>
            <a href="<?php echo $base; ?>/auth/register.php" class="btn btn-primary btn-sm" id="navRegister">Get Started</a>
        <?php endif; ?>
    </div>
</nav>

<?php if ($alertMsg): ?>
<div class="container" style="padding-top:1rem;">
    <div class="alert alert-<?php echo $alertType; ?>" data-auto-dismiss>
        <?php echo htmlspecialchars($alertMsg); ?>
    </div>
</div>
<?php endif; ?>
