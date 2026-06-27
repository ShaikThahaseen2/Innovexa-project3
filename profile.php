<?php
require_once __DIR__ . '/config/db.php';
$base = '/FS project3';
requireLogin();
$uid = $_SESSION['user_id'];
$pageTitle = 'My Profile';

$user = $conn->query("SELECT * FROM users WHERE id = $uid")->fetch_assoc();
$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = sanitize($conn, $_POST['name'] ?? '');
    $bio = sanitize($conn, $_POST['bio'] ?? '');
    $pic = sanitize($conn, $_POST['profile_pic'] ?? '');
    $newPass = $_POST['new_password'] ?? '';
    $confirmPass = $_POST['confirm_password'] ?? '';

    if (empty($name)) {
        $error = 'Name cannot be empty.';
    } else {
        if (!empty($newPass)) {
            if (strlen($newPass) < 6) {
                $error = 'Password must be at least 6 characters.';
            } elseif ($newPass !== $confirmPass) {
                $error = 'Passwords do not match.';
            } else {
                $hash = password_hash($newPass, PASSWORD_BCRYPT);
                $stmt = $conn->prepare("UPDATE users SET name=?, bio=?, profile_pic=?, password=? WHERE id=?");
                $stmt->bind_param("ssssi", $name, $bio, $pic, $hash, $uid);
                $stmt->execute();
            }
        }
        if (empty($error)) {
            $stmt = $conn->prepare("UPDATE users SET name=?, bio=?, profile_pic=? WHERE id=?");
            $stmt->bind_param("sssi", $name, $bio, $pic, $uid);
            $stmt->execute();
            $_SESSION['user_name'] = $name;
            $success = 'Profile updated successfully!';
            $user = $conn->query("SELECT * FROM users WHERE id = $uid")->fetch_assoc();
        }
    }
}

// Stats
$enrolledCount = $conn->query("SELECT COUNT(*) as cnt FROM enrollments WHERE user_id = $uid")->fetch_assoc()['cnt'];
$coursesCreated = $conn->query("SELECT COUNT(*) as cnt FROM courses WHERE instructor_id = $uid")->fetch_assoc()['cnt'];
$memberDays = max(1, round((time() - strtotime($user['created_at'])) / 86400));
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="Manage your InnovExa LMS profile settings.">
    <title>My Profile | InnovExa LMS</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800;900&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="<?php echo $base; ?>/assets/css/style.css">
</head>
<body>
<?php require_once __DIR__ . '/includes/header.php'; ?>

<main style="position:relative;z-index:1;padding:2.5rem 0;">
    <div class="container" style="max-width:760px;">
        <div class="page-header">
            <h1 class="page-title">👤 My Profile</h1>
            <p class="page-subtitle">Manage your account settings and personal information</p>
        </div>

        <?php if ($error): ?><div class="alert alert-error" data-auto-dismiss>⚠️ <?php echo htmlspecialchars($error); ?></div><?php endif; ?>
        <?php if ($success): ?><div class="alert alert-success" data-auto-dismiss>✅ <?php echo htmlspecialchars($success); ?></div><?php endif; ?>

        <!-- Profile Header -->
        <div class="card" style="margin-bottom:1.5rem;">
            <div style="padding:2rem;text-align:center;">
                <?php if ($user['profile_pic']): ?>
                    <img src="<?php echo htmlspecialchars($user['profile_pic']); ?>" alt="Profile" style="width:100px;height:100px;border-radius:50%;object-fit:cover;margin:0 auto 1rem;border:3px solid var(--border-primary);display:block;">
                <?php else: ?>
                    <div class="profile-avatar"><?php echo strtoupper(substr($user['name'], 0, 2)); ?></div>
                <?php endif; ?>
                <h2 style="font-size:1.3rem;font-weight:700;"><?php echo htmlspecialchars($user['name']); ?></h2>
                <p style="color:var(--primary-light);font-size:0.9rem;margin:0.25rem 0;"><?php echo htmlspecialchars($user['email']); ?></p>
                <span class="badge badge-<?php echo $user['role'] === 'instructor' ? 'warning' : 'info'; ?>" style="margin-top:0.5rem;">
                    <?php echo $user['role'] === 'instructor' ? '👩‍🏫 Instructor' : '🎓 Student'; ?>
                </span>

                <div style="display:flex;justify-content:center;gap:2rem;margin-top:1.5rem;flex-wrap:wrap;">
                    <div style="text-align:center;">
                        <div style="font-size:1.5rem;font-weight:800;color:var(--primary-light);"><?php echo $memberDays; ?></div>
                        <div style="font-size:0.75rem;color:var(--text-muted);text-transform:uppercase;letter-spacing:0.5px;">Days Member</div>
                    </div>
                    <?php if ($user['role'] === 'student'): ?>
                    <div style="text-align:center;">
                        <div style="font-size:1.5rem;font-weight:800;color:var(--accent);"><?php echo $enrolledCount; ?></div>
                        <div style="font-size:0.75rem;color:var(--text-muted);text-transform:uppercase;letter-spacing:0.5px;">Enrolled Courses</div>
                    </div>
                    <?php else: ?>
                    <div style="text-align:center;">
                        <div style="font-size:1.5rem;font-weight:800;color:var(--accent);"><?php echo $coursesCreated; ?></div>
                        <div style="font-size:0.75rem;color:var(--text-muted);text-transform:uppercase;letter-spacing:0.5px;">Courses Created</div>
                    </div>
                    <?php endif; ?>
                    <div style="text-align:center;">
                        <div style="font-size:1.5rem;font-weight:800;color:var(--secondary);">⭐ 4.9</div>
                        <div style="font-size:0.75rem;color:var(--text-muted);text-transform:uppercase;letter-spacing:0.5px;">Rating</div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Edit Form -->
        <div class="card">
            <div style="padding:1.5rem;">
                <h2 style="font-size:1rem;font-weight:700;margin-bottom:1.25rem;padding-bottom:0.75rem;border-bottom:1px solid var(--border);">✏️ Edit Profile</h2>
                <form method="POST" id="profileForm">
                    <div class="form-group">
                        <label class="form-label" for="profileName">Full Name *</label>
                        <input type="text" id="profileName" name="name" class="form-control" value="<?php echo htmlspecialchars($user['name']); ?>" required>
                    </div>
                    <div class="form-group">
                        <label class="form-label" for="profilePic">Profile Picture URL</label>
                        <input type="url" id="profilePic" name="profile_pic" class="form-control" placeholder="https://..." value="<?php echo htmlspecialchars($user['profile_pic'] ?? ''); ?>">
                    </div>
                    <div class="form-group">
                        <label class="form-label" for="profileBio">Bio</label>
                        <textarea id="profileBio" name="bio" class="form-control" rows="3" placeholder="Tell us about yourself..."><?php echo htmlspecialchars($user['bio'] ?? ''); ?></textarea>
                    </div>

                    <hr class="divider">
                    <h3 style="font-size:0.95rem;font-weight:700;margin-bottom:1rem;">🔒 Change Password (leave blank to keep current)</h3>
                    <div style="display:grid;grid-template-columns:1fr 1fr;gap:1rem;">
                        <div class="form-group">
                            <label class="form-label" for="newPass">New Password</label>
                            <input type="password" id="newPass" name="new_password" class="form-control" placeholder="Min. 6 characters">
                        </div>
                        <div class="form-group">
                            <label class="form-label" for="confirmPass">Confirm Password</label>
                            <input type="password" id="confirmPass" name="confirm_password" class="form-control" placeholder="Repeat new password">
                        </div>
                    </div>

                    <div style="display:flex;gap:1rem;justify-content:flex-end;margin-top:0.5rem;">
                        <a href="<?php echo $base; ?>/index.php" class="btn btn-secondary" id="cancelProfile">Cancel</a>
                        <button type="submit" class="btn btn-primary" id="saveProfile">💾 Save Changes</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</main>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
</body>
</html>
