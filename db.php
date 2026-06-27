<?php
session_start();

define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'innovexa_lms');

// Connect without DB first to create it if needed
$conn = new mysqli(DB_HOST, DB_USER, DB_PASS);
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Create DB if not exists
$conn->query("CREATE DATABASE IF NOT EXISTS `" . DB_NAME . "` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
$conn->select_db(DB_NAME);

// Create tables
$tables = [
"CREATE TABLE IF NOT EXISTS `users` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `name` VARCHAR(100) NOT NULL,
    `email` VARCHAR(100) UNIQUE NOT NULL,
    `password` VARCHAR(255) NOT NULL,
    `role` ENUM('student','instructor') NOT NULL DEFAULT 'student',
    `profile_pic` VARCHAR(255) DEFAULT NULL,
    `bio` TEXT DEFAULT NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",

"CREATE TABLE IF NOT EXISTS `courses` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `title` VARCHAR(255) NOT NULL,
    `description` TEXT NOT NULL,
    `instructor_id` INT NOT NULL,
    `thumbnail` VARCHAR(255) DEFAULT NULL,
    `category` VARCHAR(100) NOT NULL DEFAULT 'General',
    `price` DECIMAL(10,2) DEFAULT 0.00,
    `level` ENUM('Beginner','Intermediate','Advanced') DEFAULT 'Beginner',
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (`instructor_id`) REFERENCES `users`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",

"CREATE TABLE IF NOT EXISTS `lessons` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `course_id` INT NOT NULL,
    `title` VARCHAR(255) NOT NULL,
    `description` TEXT,
    `video_url` VARCHAR(255) NOT NULL,
    `duration` VARCHAR(50) NOT NULL DEFAULT '10 mins',
    `lesson_order` INT NOT NULL DEFAULT 1,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (`course_id`) REFERENCES `courses`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",

"CREATE TABLE IF NOT EXISTS `enrollments` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `user_id` INT NOT NULL,
    `course_id` INT NOT NULL,
    `enrolled_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY `unique_user_course` (`user_id`,`course_id`),
    FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`course_id`) REFERENCES `courses`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",

"CREATE TABLE IF NOT EXISTS `progress` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `user_id` INT NOT NULL,
    `lesson_id` INT NOT NULL,
    `is_completed` TINYINT(1) DEFAULT 1,
    `watched_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY `unique_user_lesson` (`user_id`,`lesson_id`),
    FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`lesson_id`) REFERENCES `lessons`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4"
];

foreach ($tables as $sql) {
    $conn->query($sql);
}

// Seed default data if users table is empty
$check = $conn->query("SELECT COUNT(*) as cnt FROM `users`");
$row = $check->fetch_assoc();
if ($row['cnt'] == 0) {
    // Insert instructor
    $instructor_pass = password_hash('instructor123', PASSWORD_BCRYPT);
    $conn->query("INSERT INTO `users` (`name`,`email`,`password`,`role`,`bio`) VALUES
        ('Dr. Sarah Johnson', 'instructor@lms.com', '$instructor_pass', 'instructor', 'PhD in Computer Science with 10+ years of teaching experience in web development and AI.')");
    $instructor_id = $conn->insert_id;

    // Insert student
    $student_pass = password_hash('student123', PASSWORD_BCRYPT);
    $conn->query("INSERT INTO `users` (`name`,`email`,`password`,`role`,`bio`) VALUES
        ('Alex Thompson', 'student@lms.com', '$student_pass', 'student', 'Passionate learner exploring web technologies.')");
    $student_id = $conn->insert_id;

    // Insert courses
    $courses_data = [
        ["Complete Web Development Bootcamp", "Master HTML, CSS, JavaScript, PHP, and MySQL from scratch to advanced level. Build real-world projects and deploy them.", $instructor_id, "https://images.unsplash.com/photo-1461749280684-dccba630e2f6?w=400", "Web Development", 49.99, "Beginner"],
        ["Advanced Python & Machine Learning", "Deep dive into Python programming, data science libraries, machine learning algorithms, and neural networks.", $instructor_id, "https://images.unsplash.com/photo-1526379095098-d400fd0bf935?w=400", "Data Science", 79.99, "Advanced"],
        ["UI/UX Design Fundamentals", "Learn design thinking, wireframing, prototyping, and modern UI/UX principles using Figma and Adobe XD.", $instructor_id, "https://images.unsplash.com/photo-1561070791-2526d30994b5?w=400", "Design", 39.99, "Beginner"],
    ];

    $course_ids = [];
    foreach ($courses_data as $c) {
        $conn->query("INSERT INTO `courses` (`title`,`description`,`instructor_id`,`thumbnail`,`category`,`price`,`level`) VALUES
            ('{$c[0]}', '{$c[1]}', {$c[2]}, '{$c[3]}', '{$c[4]}', {$c[5]}, '{$c[6]}')");
        $course_ids[] = $conn->insert_id;
    }

    // Insert lessons for course 1
    $lessons_c1 = [
        ["Introduction to HTML5", "Learn the building blocks of the web", "https://www.youtube.com/embed/qz0aGYrrlhU", "15 mins", 1],
        ["CSS Styling & Flexbox", "Style your pages with modern CSS techniques", "https://www.youtube.com/embed/yfoY53QXEnI", "22 mins", 2],
        ["JavaScript Fundamentals", "Core programming concepts for the browser", "https://www.youtube.com/embed/W6NZfCO5SIk", "30 mins", 3],
        ["PHP Backend Basics", "Server-side scripting with PHP", "https://www.youtube.com/embed/OK_JCtrrv-c", "25 mins", 4],
        ["MySQL Database Design", "Relational databases and SQL queries", "https://www.youtube.com/embed/xiUTqnI6xk8", "28 mins", 5],
    ];
    foreach ($lessons_c1 as $l) {
        $conn->query("INSERT INTO `lessons` (`course_id`,`title`,`description`,`video_url`,`duration`,`lesson_order`) VALUES
            ({$course_ids[0]}, '{$l[0]}', '{$l[1]}', '{$l[2]}', '{$l[3]}', {$l[4]})");
    }

    // Insert lessons for course 2
    $lessons_c2 = [
        ["Python Syntax & Data Types", "Master Python fundamentals", "https://www.youtube.com/embed/_uQrJ0TkZlc", "20 mins", 1],
        ["NumPy & Pandas", "Data manipulation libraries", "https://www.youtube.com/embed/vmEHCJofslg", "35 mins", 2],
        ["Machine Learning with Scikit-Learn", "Build your first ML model", "https://www.youtube.com/embed/0B5eIE_1vpU", "40 mins", 3],
        ["Neural Networks & Deep Learning", "Introduction to neural nets", "https://www.youtube.com/embed/aircAruvnKk", "45 mins", 4],
    ];
    foreach ($lessons_c2 as $l) {
        $conn->query("INSERT INTO `lessons` (`course_id`,`title`,`description`,`video_url`,`duration`,`lesson_order`) VALUES
            ({$course_ids[1]}, '{$l[0]}', '{$l[1]}', '{$l[2]}', '{$l[3]}', {$l[4]})");
    }

    // Insert lessons for course 3
    $lessons_c3 = [
        ["Design Thinking Process", "Understand user-centered design", "https://www.youtube.com/embed/a7sEoEvT8l8", "18 mins", 1],
        ["Wireframing Basics", "Sketch layouts and user flows", "https://www.youtube.com/embed/PmmQjLqJQlY", "22 mins", 2],
        ["Figma Masterclass", "Build high-fidelity prototypes", "https://www.youtube.com/embed/FTFaQWZBqQ8", "38 mins", 3],
    ];
    foreach ($lessons_c3 as $l) {
        $conn->query("INSERT INTO `lessons` (`course_id`,`title`,`description`,`video_url`,`duration`,`lesson_order`) VALUES
            ({$course_ids[2]}, '{$l[0]}', '{$l[1]}', '{$l[2]}', '{$l[3]}', {$l[4]})");
    }

    // Enroll student in first two courses
    $conn->query("INSERT INTO `enrollments` (`user_id`,`course_id`) VALUES ($student_id, {$course_ids[0]}), ($student_id, {$course_ids[1]})");
}

function redirect($url) {
    header("Location: $url");
    exit();
}

function isLoggedIn() {
    return isset($_SESSION['user_id']);
}

function requireLogin() {
    if (!isLoggedIn()) {
        redirect('/FS project3/auth/login.php');
    }
}

function requireRole($role) {
    requireLogin();
    if ($_SESSION['user_role'] !== $role) {
        redirect('/FS project3/index.php');
    }
}

function sanitize($conn, $str) {
    return $conn->real_escape_string(htmlspecialchars(strip_tags(trim($str))));
}
?>
