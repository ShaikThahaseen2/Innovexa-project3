<?php
require_once __DIR__ . '/../config/db.php';
$base = '/FS project3';
if (isLoggedIn()) redirect($base . '/index.php');

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = sanitize($conn, $_POST['name'] ?? '');
    $email = sanitize($conn, $_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirm = $_POST['confirm_password'] ?? '';
    $role = in_array($_POST['role'] ?? '', ['student','instructor']) ? $_POST['role'] : 'student';

    if (empty($name) || empty($email) || empty($password)) {
        $error = 'Please fill in all required fields.';
    } elseif (strlen($password) < 6) {
        $error = 'Password must be at least 6 characters.';
    } elseif ($password !== $confirm) {
        $error = 'Passwords do not match.';
    } else {
        $check = $conn->prepare("SELECT id FROM users WHERE email = ?");
        $check->bind_param("s", $email);
        $check->execute();
        if ($check->get_result()->num_rows > 0) {
            $error = 'An account with this email already exists.';
        } else {
            $hash = password_hash($password, PASSWORD_BCRYPT);
            $stmt = $conn->prepare("INSERT INTO users (name, email, password, role) VALUES (?, ?, ?, ?)");
            $stmt->bind_param("ssss", $name, $email, $hash, $role);
            if ($stmt->execute()) {
                $userId = $conn->insert_id;
                $_SESSION['user_id'] = $userId;
                $_SESSION['user_name'] = $name;
                $_SESSION['user_role'] = $role;
                $_SESSION['user_email'] = $email;
                $_SESSION['alert'] = ['msg' => "Welcome to InnovExa, $name! Your account is ready 🎉", 'type' => 'success'];
                redirect($role === 'instructor' ? $base . '/instructor/dashboard.php' : $base . '/student/dashboard.php');
            } else {
                $error = 'Registration failed. Please try again.';
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="Create your free InnovExa LMS account and start learning today.">
    <title>Register | InnovExa LMS</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800;900&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="<?php echo $base; ?>/assets/css/style.css">
</head>
<body>
<?php require_once __DIR__ . '/../includes/header.php'; ?>

<main class="auth-page">
    <div class="auth-card" style="max-width:480px;">
        <div class="auth-logo">
            <div class="logo-icon" style="width:56px;height:56px;font-size:1.6rem;margin:0 auto 0.75rem;">🎓</div>
            <h1 class="auth-title">Create your account</h1>
            <p class="auth-subtitle">Join thousands of learners on InnovExa</p>
        </div>

        <?php if ($error): ?>
            <div class="alert alert-error">⚠️ <?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>

        <p class="form-label" style="margin-bottom:0.5rem;">I want to join as:</p>
        <div class="role-toggle" id="roleToggle">
            <button type="button" class="role-btn active" data-role="student" id="roleStudent">🎓 Student</button>
            <button type="button" class="role-btn" data-role="instructor" id="roleInstructor">👩‍🏫 Instructor</button>
        </div>

        <form method="POST" id="registerForm">
            <input type="hidden" name="role" id="roleInput" value="student">
            <div class="form-group">
                <label class="form-label" for="regName">Full Name</label>
                <input type="text" id="regName" name="name" class="form-control" placeholder="John Doe" value="<?php echo htmlspecialchars($_POST['name'] ?? ''); ?>" required>
            </div>
            <div class="form-group">
                <label class="form-label" for="regEmail">Email Address</label>
                <input type="email" id="regEmail" name="email" class="form-control" placeholder="you@example.com" value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>" required>
            </div>
            <div class="form-group">
                <label class="form-label" for="regPassword">Password <span class="text-muted">(min. 6 characters)</span></label>
                <input type="password" id="regPassword" name="password" class="form-control" placeholder="••••••••" required>
            </div>
            <div class="form-group">
                <label class="form-label" for="regConfirm">Confirm Password</label>
                <input type="password" id="regConfirm" name="confirm_password" class="form-control" placeholder="••••••••" required>
            </div>
            <button type="submit" class="btn btn-primary btn-full btn-lg" id="registerSubmit" style="margin-top:0.5rem;">
                ✨ Create Account
            </button>
        </form>

        <hr class="divider">
        <p class="text-center text-muted" style="font-size:0.88rem;">
            Already have an account?
            <a href="<?php echo $base; ?>/auth/login.php" style="color:var(--primary-light);font-weight:600;">Sign in →</a>
        </p>
    </div>
</main>

<script src="<?php echo $base; ?>/assets/js/main.js"></script>
</body>
</html>
