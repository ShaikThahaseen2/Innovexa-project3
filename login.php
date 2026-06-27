<?php
require_once __DIR__ . '/../config/db.php';
$base = '/FS project3';
if (isLoggedIn()) redirect($base . '/index.php');

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = sanitize($conn, $_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    if (empty($email) || empty($password)) {
        $error = 'Please fill in all fields.';
    } else {
        $stmt = $conn->prepare("SELECT * FROM users WHERE email = ?");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $result = $stmt->get_result();
        $user = $result->fetch_assoc();

        if ($user && password_verify($password, $user['password'])) {
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['user_name'] = $user['name'];
            $_SESSION['user_role'] = $user['role'];
            $_SESSION['user_email'] = $user['email'];
            $_SESSION['alert'] = ['msg' => 'Welcome back, ' . $user['name'] . '! 🎉', 'type' => 'success'];
            redirect($user['role'] === 'instructor' ? $base . '/instructor/dashboard.php' : $base . '/student/dashboard.php');
        } else {
            $error = 'Invalid email or password. Please try again.';
        }
    }
}
$pageTitle = 'Login';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="Login to InnovExa LMS to access your courses and learning dashboard.">
    <title>Login | InnovExa LMS</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800;900&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="<?php echo $base; ?>/assets/css/style.css">
</head>
<body>
<?php require_once __DIR__ . '/../includes/header.php'; ?>

<main class="auth-page">
    <div class="auth-card">
        <div class="auth-logo">
            <div class="logo-icon" style="width:56px;height:56px;font-size:1.6rem;margin:0 auto 0.75rem;">🎓</div>
            <h1 class="auth-title">Welcome back</h1>
            <p class="auth-subtitle">Sign in to continue your learning journey</p>
        </div>

        <?php if ($error): ?>
            <div class="alert alert-error">⚠️ <?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>

        <div class="alert alert-info" style="margin-bottom:1.25rem;font-size:0.8rem;">
            <strong>Demo Accounts:</strong><br>
            👩‍🏫 Instructor: instructor@lms.com / instructor123<br>
            🎓 Student: student@lms.com / student123
        </div>

        <form method="POST" id="loginForm">
            <div class="form-group">
                <label class="form-label" for="loginEmail">Email Address</label>
                <input type="email" id="loginEmail" name="email" class="form-control" placeholder="you@example.com" value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>" required>
            </div>
            <div class="form-group">
                <label class="form-label" for="loginPassword">Password</label>
                <input type="password" id="loginPassword" name="password" class="form-control" placeholder="••••••••" required>
            </div>
            <button type="submit" class="btn btn-primary btn-full btn-lg" id="loginSubmit" style="margin-top:0.5rem;">
                🚀 Sign In
            </button>
        </form>

        <hr class="divider">
        <p class="text-center text-muted" style="font-size:0.88rem;">
            Don't have an account?
            <a href="<?php echo $base; ?>/auth/register.php" style="color:var(--primary-light);font-weight:600;">Create one free →</a>
        </p>
    </div>
</main>

<script src="<?php echo $base; ?>/assets/js/main.js"></script>
</body>
</html>
