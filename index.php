<?php
// ============================================================
//  InnovExa LMS — All-in-One Single File (PHP + MySQL/XAMPP)
// ============================================================
session_start();

// ── DATABASE CONFIG ─────────────────────────────────────────
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'innovexa_lms');

$conn = new mysqli(DB_HOST, DB_USER, DB_PASS);
if ($conn->connect_error) die("❌ MySQL Connection Failed: " . $conn->connect_error . "<br>Make sure XAMPP MySQL is running.");
$conn->query("CREATE DATABASE IF NOT EXISTS `" . DB_NAME . "` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
$conn->select_db(DB_NAME);

// ── CREATE TABLES ────────────────────────────────────────────
$conn->query("CREATE TABLE IF NOT EXISTS `users` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,`name` VARCHAR(100) NOT NULL,`email` VARCHAR(100) UNIQUE NOT NULL,
    `password` VARCHAR(255) NOT NULL,`role` ENUM('student','instructor') DEFAULT 'student',
    `bio` TEXT,`profile_pic` VARCHAR(255),`created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
$conn->query("CREATE TABLE IF NOT EXISTS `courses` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,`title` VARCHAR(255) NOT NULL,`description` TEXT NOT NULL,
    `instructor_id` INT NOT NULL,`thumbnail` VARCHAR(255),`category` VARCHAR(100) DEFAULT 'General',
    `price` DECIMAL(10,2) DEFAULT 0.00,`level` ENUM('Beginner','Intermediate','Advanced') DEFAULT 'Beginner',
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,FOREIGN KEY(`instructor_id`) REFERENCES `users`(`id`) ON DELETE CASCADE) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
$conn->query("CREATE TABLE IF NOT EXISTS `lessons` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,`course_id` INT NOT NULL,`title` VARCHAR(255) NOT NULL,
    `description` TEXT,`video_url` VARCHAR(255) NOT NULL,`duration` VARCHAR(50) DEFAULT '10 mins',
    `lesson_order` INT DEFAULT 1,`created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY(`course_id`) REFERENCES `courses`(`id`) ON DELETE CASCADE) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
$conn->query("CREATE TABLE IF NOT EXISTS `enrollments` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,`user_id` INT NOT NULL,`course_id` INT NOT NULL,
    `enrolled_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY `uq_uc`(`user_id`,`course_id`),
    FOREIGN KEY(`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
    FOREIGN KEY(`course_id`) REFERENCES `courses`(`id`) ON DELETE CASCADE) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
$conn->query("CREATE TABLE IF NOT EXISTS `progress` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,`user_id` INT NOT NULL,`lesson_id` INT NOT NULL,
    `is_completed` TINYINT(1) DEFAULT 1,`watched_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY `uq_ul`(`user_id`,`lesson_id`),
    FOREIGN KEY(`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
    FOREIGN KEY(`lesson_id`) REFERENCES `lessons`(`id`) ON DELETE CASCADE) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

// ── SEED DATA ─────────────────────────────────────────────────
$ck = $conn->query("SELECT COUNT(*) as c FROM users")->fetch_assoc()['c'];
if ($ck == 0) {
    $ip = password_hash('instructor123', PASSWORD_BCRYPT);
    $sp = password_hash('student123', PASSWORD_BCRYPT);
    $conn->query("INSERT INTO users(name,email,password,role,bio) VALUES
        ('Dr. Sarah Johnson','instructor@lms.com','$ip','instructor','PhD in Computer Science, 10+ years teaching web dev & AI.'),
        ('Alex Thompson','student@lms.com','$sp','student','Passionate learner exploring modern web technologies.')");
    $iid = 1; $sid = 2;
    $conn->query("INSERT INTO courses(title,description,instructor_id,thumbnail,category,price,level) VALUES
        ('Complete Web Development Bootcamp','Master HTML, CSS, JavaScript, PHP and MySQL from scratch to advanced. Build real-world projects.',1,'https://images.unsplash.com/photo-1461749280684-dccba630e2f6?w=500','Web Development',49.99,'Beginner'),
        ('Advanced Python & Machine Learning','Deep dive into Python, data science libraries, ML algorithms and neural networks.',1,'https://images.unsplash.com/photo-1526379095098-d400fd0bf935?w=500','Data Science',79.99,'Advanced'),
        ('UI/UX Design Fundamentals','Learn design thinking, wireframing, prototyping using Figma and modern UX principles.',1,'https://images.unsplash.com/photo-1561070791-2526d30994b5?w=500','Design',39.99,'Beginner')");
    $conn->query("INSERT INTO lessons(course_id,title,description,video_url,duration,lesson_order) VALUES
        (1,'Introduction to HTML5','Building blocks of the web','https://www.youtube.com/embed/qz0aGYrrlhU','15 mins',1),
        (1,'CSS Styling & Flexbox','Style pages with modern CSS','https://www.youtube.com/embed/yfoY53QXEnI','22 mins',2),
        (1,'JavaScript Fundamentals','Core browser programming','https://www.youtube.com/embed/W6NZfCO5SIk','30 mins',3),
        (1,'PHP Backend Basics','Server-side scripting with PHP','https://www.youtube.com/embed/OK_JCtrrv-c','25 mins',4),
        (1,'MySQL Database Design','Relational databases & SQL','https://www.youtube.com/embed/xiUTqnI6xk8','28 mins',5),
        (2,'Python Syntax & Data Types','Master Python fundamentals','https://www.youtube.com/embed/_uQrJ0TkZlc','20 mins',1),
        (2,'NumPy & Pandas','Data manipulation libraries','https://www.youtube.com/embed/vmEHCJofslg','35 mins',2),
        (2,'Machine Learning with Scikit-Learn','Build your first ML model','https://www.youtube.com/embed/0B5eIE_1vpU','40 mins',3),
        (2,'Neural Networks & Deep Learning','Intro to neural nets','https://www.youtube.com/embed/aircAruvnKk','45 mins',4),
        (3,'Design Thinking Process','User-centered design principles','https://www.youtube.com/embed/a7sEoEvT8l8','18 mins',1),
        (3,'Wireframing Basics','Sketch layouts & user flows','https://www.youtube.com/embed/PmmQjLqJQlY','22 mins',2),
        (3,'Figma Masterclass','Build high-fidelity prototypes','https://www.youtube.com/embed/FTFaQWZBqQ8','38 mins',3)");
    $conn->query("INSERT INTO enrollments(user_id,course_id) VALUES(2,1),(2,2)");
}

// ── HELPERS ───────────────────────────────────────────────────
function S($conn,$s){return $conn->real_escape_string(htmlspecialchars(strip_tags(trim($s))));}
function uid(){return (int)($_SESSION['user_id']??0);}
function role(){return $_SESSION['user_role']??'';}
function loggedIn(){return isset($_SESSION['user_id']);}
function url($page,$params=[]){$q=http_build_query(array_merge(['page'=>$page],$params));return '?'.$q;}
function redirect($u){header("Location:$u");exit();}
function flash($m,$t='success'){$_SESSION['flash']=['m'=>$m,'t'=>$t];}

// ── ROUTING ───────────────────────────────────────────────────
$page = $_GET['page'] ?? 'home';

// ── AJAX: mark lesson complete ────────────────────────────────
if ($page === 'ajax_progress' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    header('Content-Type: application/json');
    if (!loggedIn()){echo json_encode(['ok'=>false]);exit();}
    $lid = (int)($_POST['lid']??0);
    $done = (int)($_POST['done']??1);
    $uid = uid();
    $ck = $conn->query("SELECT l.course_id FROM lessons l JOIN enrollments e ON e.course_id=l.course_id WHERE l.id=$lid AND e.user_id=$uid");
    if(!$ck||$ck->num_rows===0){echo json_encode(['ok'=>false,'e'=>'no access']);exit();}
    $cid = $ck->fetch_assoc()['course_id'];
    if($done) $conn->query("INSERT IGNORE INTO progress(user_id,lesson_id,is_completed) VALUES($uid,$lid,1)");
    else $conn->query("DELETE FROM progress WHERE user_id=$uid AND lesson_id=$lid");
    $tot=$conn->query("SELECT COUNT(*) c FROM lessons WHERE course_id=$cid")->fetch_assoc()['c'];
    $fin=$conn->query("SELECT COUNT(*) c FROM progress p JOIN lessons l ON p.lesson_id=l.id WHERE l.course_id=$cid AND p.user_id=$uid AND p.is_completed=1")->fetch_assoc()['c'];
    echo json_encode(['ok'=>true,'pct'=>$tot>0?round($fin/$tot*100,1):0,'done'=>$fin,'total'=>$tot]);
    exit();
}

// ── PROCESS FORMS ─────────────────────────────────────────────
$formError = '';

// LOGIN
if ($page==='login' && $_SERVER['REQUEST_METHOD']==='POST') {
    $email=S($conn,$_POST['email']??''); $pass=$_POST['password']??'';
    $r=$conn->query("SELECT * FROM users WHERE email='$email'")->fetch_assoc();
    if($r && password_verify($pass,$r['password'])){
        $_SESSION['user_id']=$r['id'];$_SESSION['user_name']=$r['name'];
        $_SESSION['user_role']=$r['role'];$_SESSION['user_email']=$r['email'];
        flash("Welcome back, {$r['name']}! 🎉");
        redirect(url($r['role']==='instructor'?'instructor':'dashboard'));
    } else $formError='Invalid email or password.';
}

// REGISTER
if ($page==='register' && $_SERVER['REQUEST_METHOD']==='POST') {
    $name=S($conn,$_POST['name']??'');$email=S($conn,$_POST['email']??'');
    $pass=$_POST['password']??'';$conf=$_POST['confirm']??'';
    $role2=in_array($_POST['role']??'',['student','instructor'])?$_POST['role']:'student';
    if(!$name||!$email||!$pass) $formError='Fill all fields.';
    elseif(strlen($pass)<6) $formError='Password min 6 chars.';
    elseif($pass!==$conf) $formError='Passwords do not match.';
    elseif($conn->query("SELECT id FROM users WHERE email='$email'")->num_rows>0) $formError='Email already registered.';
    else{
        $h=password_hash($pass,PASSWORD_BCRYPT);
        $conn->query("INSERT INTO users(name,email,password,role) VALUES('$name','$email','$h','$role2')");
        $id=$conn->insert_id;
        $_SESSION['user_id']=$id;$_SESSION['user_name']=$name;$_SESSION['user_role']=$role2;$_SESSION['user_email']=$email;
        flash("Welcome to InnovExa, $name! 🎉");
        redirect(url($role2==='instructor'?'instructor':'dashboard'));
    }
}

// LOGOUT
if ($page==='logout'){session_destroy();redirect(url('home'));}

// ENROLL
if ($page==='enroll' && loggedIn() && role()==='student'){
    $cid=(int)($_GET['cid']??0);
    if($cid){$uid2=uid();$conn->query("INSERT IGNORE INTO enrollments(user_id,course_id) VALUES($uid2,$cid)");flash("Enrolled successfully! 🎉");}
    redirect(url('watch',['cid'=>$cid,'lid'=>0]));
}

// SAVE COURSE (instructor)
if ($page==='save_course' && $_SERVER['REQUEST_METHOD']==='POST' && loggedIn() && role()==='instructor'){
    $eid=(int)($_POST['eid']??0);
    $t=S($conn,$_POST['title']??'');$desc=S($conn,$_POST['desc']??'');$cat=S($conn,$_POST['cat']??'');
    $price=floatval($_POST['price']??0);$lv=in_array($_POST['lv']??'',['Beginner','Intermediate','Advanced'])?$_POST['lv']:'Beginner';
    $thumb=S($conn,$_POST['thumb']??'');$uid2=uid();
    if(!$t||!$desc||!$cat){flash('Fill all required fields.','error');redirect(url($eid?'edit_course':'create_course',['id'=>$eid]));}
    if($eid){
        $conn->query("UPDATE courses SET title='$t',description='$desc',category='$cat',price=$price,level='$lv',thumbnail='$thumb' WHERE id=$eid AND instructor_id=$uid2");
        $cid=$eid;
        $kept=array_filter(array_map('intval',$_POST['lid']??[]),fn($v)=>$v>0);
        if($kept) $conn->query("DELETE FROM lessons WHERE course_id=$cid AND id NOT IN(".implode(',',$kept).")");
        else $conn->query("DELETE FROM lessons WHERE course_id=$cid");
    } else {
        $conn->query("INSERT INTO courses(title,description,instructor_id,category,price,level,thumbnail) VALUES('$t','$desc',$uid2,'$cat',$price,'$lv','$thumb')");
        $cid=$conn->insert_id;
    }
    $lts=$_POST['ltitle']??[];$lurls=$_POST['lurl']??[];$ldurs=$_POST['ldur']??[];$ldescs=$_POST['ldesc']??[];$lids=$_POST['lid']??[];
    foreach($lts as $i=>$lt){
        $lt=S($conn,$lt);$lurl=S($conn,$lurls[$i]??'');$ldur=S($conn,$ldurs[$i]??'10 mins');$ldesc2=S($conn,$ldescs[$i]??'');$lo=$i+1;$lid2=(int)($lids[$i]??0);
        if(!$lt||!$lurl) continue;
        if($lid2>0) $conn->query("UPDATE lessons SET title='$lt',description='$ldesc2',video_url='$lurl',duration='$ldur',lesson_order=$lo WHERE id=$lid2 AND course_id=$cid");
        else $conn->query("INSERT INTO lessons(course_id,title,description,video_url,duration,lesson_order) VALUES($cid,'$lt','$ldesc2','$lurl','$ldur',$lo)");
    }
    flash($eid?'Course updated!':'Course published! 🚀');
    redirect(url('instructor'));
}

// DELETE COURSE
if ($page==='delete_course' && loggedIn() && role()==='instructor'){
    $cid=(int)($_GET['cid']??0);$uid2=uid();
    $conn->query("DELETE FROM courses WHERE id=$cid AND instructor_id=$uid2");
    flash('Course deleted.','info');redirect(url('instructor'));
}

// SAVE PROFILE
if ($page==='save_profile' && $_SERVER['REQUEST_METHOD']==='POST' && loggedIn()){
    $name=S($conn,$_POST['name']??'');$bio=S($conn,$_POST['bio']??'');$pic=S($conn,$_POST['pic']??'');
    $np=$_POST['np']??'';$cp=$_POST['cp']??'';$uid2=uid();
    if(!$name){flash('Name required.','error');}else{
        if($np){
            if(strlen($np)<6){flash('Password min 6 chars.','error');}
            elseif($np!==$cp){flash('Passwords do not match.','error');}
            else{$h=password_hash($np,PASSWORD_BCRYPT);$conn->query("UPDATE users SET name='$name',bio='$bio',profile_pic='$pic',password='$h' WHERE id=$uid2");$_SESSION['user_name']=$name;flash('Profile updated!');}
        } else {$conn->query("UPDATE users SET name='$name',bio='$bio',profile_pic='$pic' WHERE id=$uid2");$_SESSION['user_name']=$name;flash('Profile updated!');}
    }
    redirect(url('profile'));
}

// ── FETCH DATA FOR VIEWS ──────────────────────────────────────
$flashMsg = $_SESSION['flash']??null; unset($_SESSION['flash']);
$uid2 = uid();

// ── HTML OUTPUT ───────────────────────────────────────────────
?><!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1.0">
<meta name="description" content="InnovExa LMS — All-in-one learning management system powered by PHP & MySQL on XAMPP.">
<title>InnovExa LMS<?php
$titles=['home'=>'','courses'=>' · Courses','course'=>' · Course Detail','login'=>' · Login','register'=>' · Register',
'dashboard'=>' · Student Dashboard','watch'=>' · Watch','instructor'=>' · Instructor Dashboard',
'create_course'=>' · Create Course','edit_course'=>' · Edit Course','profile'=>' · Profile'];
echo $titles[$page]??'';?></title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800;900&display=swap" rel="stylesheet">
<style>
:root{--p:#6c63ff;--pd:#5a52d5;--pl:#9d96ff;--s:#ff6584;--a:#43e97b;--a2:#38f9d7;--bg:#0d0d1a;--bg2:#14142a;--bg3:#1c1c38;--gc:rgba(108,99,255,.07);--gc2:rgba(108,99,255,.13);--t1:#f0f0ff;--t2:#9090b8;--t3:#50507a;--br:rgba(255,255,255,.08);--brp:rgba(108,99,255,.25);--sh:0 8px 32px rgba(0,0,0,.45);--shp:0 8px 24px rgba(108,99,255,.3);--r:16px;--rs:8px;--rl:24px;--tr:all .3s cubic-bezier(.4,0,.2,1);--fn:'Inter',sans-serif}
*,*::before,*::after{box-sizing:border-box;margin:0;padding:0}html{scroll-behavior:smooth}
body{font-family:var(--fn);background:var(--bg);color:var(--t1);min-height:100vh;line-height:1.6;overflow-x:hidden}
body::before{content:'';position:fixed;inset:0;background:radial-gradient(ellipse at 15% 15%,rgba(108,99,255,.1) 0,transparent 55%),radial-gradient(ellipse at 85% 85%,rgba(255,101,132,.07) 0,transparent 55%),radial-gradient(ellipse at 65% 5%,rgba(67,233,123,.05) 0,transparent 40%);pointer-events:none;z-index:0}

/* ─ NAVBAR ─ */
.nav{position:sticky;top:0;z-index:500;height:66px;background:rgba(13,13,26,.88);backdrop-filter:blur(20px);border-bottom:1px solid var(--br);display:flex;align-items:center;justify-content:space-between;padding:0 2rem}
.nav-brand{display:flex;align-items:center;gap:10px;text-decoration:none;font-size:1.3rem;font-weight:900;letter-spacing:-.5px}
.nav-logo{width:36px;height:36px;background:linear-gradient(135deg,var(--p),var(--s));border-radius:10px;display:flex;align-items:center;justify-content:center;font-size:1rem;box-shadow:var(--shp)}
.nav-brand span{background:linear-gradient(135deg,var(--pl),var(--a2));-webkit-background-clip:text;-webkit-text-fill-color:transparent;background-clip:text}
.nav-links{display:flex;align-items:center;gap:.25rem;list-style:none}
.nav-links a{color:var(--t2);text-decoration:none;padding:7px 14px;border-radius:var(--rs);font-size:.87rem;font-weight:500;transition:var(--tr)}
.nav-links a:hover,.nav-links a.act{color:var(--t1);background:rgba(255,255,255,.06)}
.nav-right{display:flex;gap:.6rem}

/* ─ BUTTONS ─ */
.btn{display:inline-flex;align-items:center;gap:7px;padding:9px 20px;border-radius:var(--rs);font-size:.87rem;font-weight:600;cursor:pointer;border:none;text-decoration:none;transition:var(--tr);letter-spacing:.2px;white-space:nowrap;font-family:var(--fn)}
.btn-p{background:linear-gradient(135deg,var(--p),var(--pd));color:#fff;box-shadow:var(--shp)}
.btn-p:hover{transform:translateY(-2px);box-shadow:0 12px 28px rgba(108,99,255,.4)}
.btn-sec{background:rgba(255,255,255,.06);color:var(--t1);border:1px solid var(--br)}
.btn-sec:hover{background:rgba(255,255,255,.1);border-color:var(--p)}
.btn-d{background:rgba(255,101,132,.12);color:var(--s);border:1px solid rgba(255,101,132,.25)}
.btn-d:hover{background:rgba(255,101,132,.22)}
.btn-g{background:linear-gradient(135deg,var(--a),var(--a2));color:#0d0d1a;font-weight:700}
.btn-g:hover{transform:translateY(-2px);box-shadow:0 8px 20px rgba(67,233,123,.3)}
.btn-sm{padding:6px 13px;font-size:.79rem}
.btn-lg{padding:13px 30px;font-size:.97rem;border-radius:var(--r)}
.btn-fw{width:100%;justify-content:center}

/* ─ LAYOUT ─ */
.wrap{max-width:1180px;margin:0 auto;padding:0 1.5rem}
.page{position:relative;z-index:1}
.pg{padding:2.5rem 0}
.pg-sm{padding:1.5rem 0}

/* ─ HERO ─ */
.hero{text-align:center;padding:5.5rem 0 4rem;position:relative;z-index:1}
.hero-badge{display:inline-flex;align-items:center;gap:8px;background:rgba(108,99,255,.1);border:1px solid rgba(108,99,255,.22);border-radius:50px;padding:5px 16px;margin-bottom:1.5rem;font-size:.8rem;color:var(--pl)}
.hero h1{font-size:clamp(2.2rem,5vw,4rem);font-weight:900;line-height:1.08;letter-spacing:-1.5px;margin-bottom:1.2rem}
.grad{background:linear-gradient(135deg,var(--pl) 0%,var(--s) 50%,var(--a2) 100%);-webkit-background-clip:text;-webkit-text-fill-color:transparent;background-clip:text}
.hero p{font-size:1.1rem;color:var(--t2);max-width:560px;margin:0 auto 2.5rem;line-height:1.7}
.hero-btns{display:flex;justify-content:center;gap:1rem;flex-wrap:wrap}
.hero-stats{display:flex;justify-content:center;gap:3rem;margin-top:4rem;flex-wrap:wrap}
.hs{text-align:center}.hs .n{font-size:2rem;font-weight:900;color:var(--pl);display:block}.hs .l{font-size:.76rem;color:var(--t3);text-transform:uppercase;letter-spacing:1px}

/* ─ SECTION ─ */
.sec{padding:4.5rem 0;position:relative;z-index:1}
.sec-alt{background:rgba(255,255,255,.012)}
.sec-hd{text-align:center;margin-bottom:2.5rem}
.sec-tag{font-size:.74rem;font-weight:700;text-transform:uppercase;letter-spacing:2px;color:var(--pl);margin-bottom:.6rem}
.sec-hd h2{font-size:clamp(1.7rem,3vw,2.4rem);font-weight:800;letter-spacing:-.5px}
.sec-hd p{color:var(--t2);margin-top:.6rem;font-size:.95rem;max-width:520px;margin-left:auto;margin-right:auto}

/* ─ GRID ─ */
.grid{display:grid;gap:1.4rem}
.g2{grid-template-columns:repeat(2,1fr)}.g3{grid-template-columns:repeat(3,1fr)}.g4{grid-template-columns:repeat(4,1fr)}
.gauto{grid-template-columns:repeat(auto-fill,minmax(290px,1fr))}
@media(max-width:900px){.g3,.g4{grid-template-columns:repeat(2,1fr)}}
@media(max-width:580px){.g2,.g3,.g4{grid-template-columns:1fr}}

/* ─ CARD ─ */
.card{background:var(--bg2);border:1px solid var(--br);border-radius:var(--r);overflow:hidden;transition:var(--tr);position:relative}
.card:hover{transform:translateY(-4px);border-color:var(--brp);box-shadow:var(--shp)}
.card-img{width:100%;height:180px;object-fit:cover;display:block}
.card-ph{width:100%;height:180px;background:linear-gradient(135deg,var(--bg3),var(--bg2));display:flex;align-items:center;justify-content:center;font-size:2.8rem}
.card-body{padding:1.2rem}
.card-meta{display:flex;gap:.45rem;margin-bottom:.55rem;flex-wrap:wrap}
.card-title{font-size:.98rem;font-weight:700;margin-bottom:.35rem;line-height:1.3}
.card-desc{font-size:.8rem;color:var(--t2);margin-bottom:.7rem;display:-webkit-box;-webkit-line-clamp:2;-webkit-box-orient:vertical;overflow:hidden;line-height:1.5}
.card-foot{display:flex;align-items:center;justify-content:space-between;padding:.8rem 1.2rem;border-top:1px solid var(--br)}
.price{font-size:1rem;font-weight:800;color:var(--a)}.price.free{color:var(--a2)}

/* ─ BADGE ─ */
.badge{display:inline-flex;align-items:center;gap:3px;padding:3px 10px;border-radius:50px;font-size:.7rem;font-weight:600;text-transform:uppercase;letter-spacing:.4px}
.bp{background:rgba(108,99,255,.13);color:var(--pl);border:1px solid rgba(108,99,255,.18)}
.bs{background:rgba(67,233,123,.1);color:var(--a);border:1px solid rgba(67,233,123,.18)}
.bw{background:rgba(255,193,7,.1);color:#ffc107;border:1px solid rgba(255,193,7,.18)}
.bd{background:rgba(255,101,132,.1);color:var(--s);border:1px solid rgba(255,101,132,.18)}
.bi{background:rgba(56,249,215,.08);color:var(--a2);border:1px solid rgba(56,249,215,.18)}

/* ─ FEATURE CARD ─ */
.fc{background:rgba(255,255,255,.03);border:1px solid var(--br);border-radius:var(--r);padding:1.75rem;text-align:center;transition:var(--tr)}
.fc:hover{border-color:var(--brp);background:var(--gc2);transform:translateY(-4px)}
.fi{width:60px;height:60px;background:linear-gradient(135deg,rgba(108,99,255,.18),rgba(108,99,255,.04));border:1px solid rgba(108,99,255,.18);border-radius:16px;display:flex;align-items:center;justify-content:center;font-size:1.7rem;margin:0 auto 1.1rem}
.ft{font-size:.97rem;font-weight:700;margin-bottom:.45rem}
.fd{font-size:.83rem;color:var(--t2);line-height:1.6}

/* ─ TESTIMONIAL ─ */
.tc{background:rgba(255,255,255,.03);border:1px solid var(--br);border-radius:var(--r);padding:1.6rem;position:relative}
.tc::before{content:'"';position:absolute;top:8px;left:18px;font-size:3.5rem;color:var(--p);opacity:.25;font-family:Georgia,serif;line-height:1}
.tc-txt{color:var(--t2);font-size:.87rem;line-height:1.7;margin-bottom:1.1rem}
.tc-auth{display:flex;align-items:center;gap:.7rem}
.tc-av{width:40px;height:40px;border-radius:50%;background:linear-gradient(135deg,var(--p),var(--s));display:flex;align-items:center;justify-content:center;font-weight:700;font-size:.85rem}
.tc-name{font-weight:700;font-size:.87rem}.tc-role{font-size:.75rem;color:var(--t3)}
.stars{color:#ffc107;font-size:.82rem;margin-bottom:.65rem}

/* ─ AUTH ─ */
.auth-wrap{min-height:calc(100vh - 66px);display:flex;align-items:center;justify-content:center;padding:2rem;position:relative;z-index:1}
.auth-box{background:var(--bg2);border:1px solid var(--br);border-radius:var(--rl);padding:2.5rem;width:100%;max-width:440px;box-shadow:var(--sh)}
.auth-hd{text-align:center;margin-bottom:2rem}
.auth-logo{width:54px;height:54px;background:linear-gradient(135deg,var(--p),var(--s));border-radius:14px;display:flex;align-items:center;justify-content:center;font-size:1.5rem;margin:0 auto .75rem;box-shadow:var(--shp)}
.auth-title{font-size:1.55rem;font-weight:800;margin-bottom:.3rem}
.auth-sub{color:var(--t2);font-size:.87rem}
.role-tgl{display:flex;gap:.4rem;padding:4px;background:rgba(255,255,255,.04);border-radius:var(--rs);margin-bottom:1.4rem}
.role-btn{flex:1;padding:8px;border:none;border-radius:6px;background:transparent;color:var(--t2);cursor:pointer;font-size:.83rem;font-weight:600;transition:var(--tr);font-family:var(--fn)}
.role-btn.act{background:var(--p);color:#fff;box-shadow:0 4px 12px rgba(108,99,255,.3)}

/* ─ FORM ─ */
.fg{margin-bottom:1.15rem}
.fl{display:block;font-size:.82rem;font-weight:600;color:var(--t2);margin-bottom:.45rem}
.fi2{width:100%;padding:11px 15px;background:rgba(255,255,255,.05);border:1px solid var(--br);border-radius:var(--rs);color:var(--t1);font-size:.88rem;font-family:var(--fn);transition:var(--tr);outline:none}
.fi2:focus{border-color:var(--p);background:var(--gc);box-shadow:0 0 0 3px rgba(108,99,255,.12)}
.fi2::placeholder{color:var(--t3)}
textarea.fi2{resize:vertical;min-height:90px}
select.fi2 option{background:var(--bg2)}

/* ─ ALERT / FLASH ─ */
.alert{padding:.85rem 1.15rem;border-radius:var(--rs);margin-bottom:1rem;font-size:.86rem;font-weight:500;display:flex;align-items:center;gap:8px}
.al-s{background:rgba(67,233,123,.09);border:1px solid rgba(67,233,123,.22);color:var(--a)}
.al-e{background:rgba(255,101,132,.09);border:1px solid rgba(255,101,132,.22);color:var(--s)}
.al-i{background:rgba(108,99,255,.09);border:1px solid rgba(108,99,255,.22);color:var(--pl)}

/* ─ DASHBOARD ─ */
.dash-wrap{display:flex;min-height:calc(100vh - 66px);position:relative;z-index:1}
.sidebar{width:255px;flex-shrink:0;background:var(--bg2);border-right:1px solid var(--br);padding:1.4rem .9rem;position:sticky;top:66px;height:calc(100vh - 66px);overflow-y:auto;display:flex;flex-direction:column}
.sb-lbl{font-size:.68rem;font-weight:700;text-transform:uppercase;letter-spacing:1.5px;color:var(--t3);margin-bottom:.4rem;padding:0 .7rem}
.sb-nav{list-style:none;margin-bottom:1.4rem}
.sb-nav a{display:flex;align-items:center;gap:9px;padding:9px 11px;border-radius:var(--rs);color:var(--t2);text-decoration:none;font-size:.85rem;font-weight:500;transition:var(--tr);margin-bottom:2px}
.sb-nav a:hover,.sb-nav a.act{color:var(--t1);background:var(--gc2);border-left:2px solid var(--p)}
.mc{flex:1;padding:2rem;overflow:hidden}

/* ─ STATS ─ */
.sgrid{display:grid;grid-template-columns:repeat(auto-fit,minmax(180px,1fr));gap:1.1rem;margin-bottom:2rem}
.sc{background:var(--bg2);border:1px solid var(--br);border-radius:var(--r);padding:1.4rem;display:flex;align-items:center;gap:.9rem;position:relative;overflow:hidden;transition:var(--tr)}
.sc:hover{border-color:var(--brp)}
.sc::before{content:'';position:absolute;top:0;right:0;width:70px;height:70px;border-radius:50%;background:var(--ic,rgba(108,99,255,.1));transform:translate(30%,-30%)}
.sc-icon{font-size:1.7rem;z-index:1}.sc-val{font-size:1.7rem;font-weight:900;line-height:1;z-index:1}.sc-lbl{font-size:.73rem;color:var(--t3);text-transform:uppercase;letter-spacing:.4px;margin-top:3px;z-index:1}

/* ─ PROGRESS ─ */
.pb-wrap{background:rgba(255,255,255,.07);border-radius:50px;height:7px;overflow:hidden}
.pb{height:100%;border-radius:50px;background:linear-gradient(90deg,var(--p),var(--a2));transition:width .5s ease}
.pb-lbl{display:flex;justify-content:space-between;font-size:.78rem;color:var(--t2);margin-bottom:5px}

/* ─ LESSON ITEMS ─ */
.li{display:flex;align-items:center;gap:.9rem;padding:.9rem 1.1rem;border-radius:var(--rs);border:1px solid var(--br);margin-bottom:.45rem;background:rgba(255,255,255,.025);cursor:pointer;transition:var(--tr);text-decoration:none;color:inherit}
.li:hover{border-color:var(--brp);background:var(--gc)}
.li.act{border-color:var(--p);background:rgba(108,99,255,.1)}
.li.done{border-color:rgba(67,233,123,.25)}
.ln{width:30px;height:30px;border-radius:50%;background:rgba(108,99,255,.13);border:1px solid rgba(108,99,255,.18);display:flex;align-items:center;justify-content:center;font-size:.75rem;font-weight:700;color:var(--pl);flex-shrink:0}
.ln.done{background:rgba(67,233,123,.18);border-color:rgba(67,233,123,.25);color:var(--a)}
.lt{font-size:.85rem;font-weight:600;white-space:nowrap;overflow:hidden;text-overflow:ellipsis}.ld{font-size:.73rem;color:var(--t3);margin-top:2px}

/* ─ VIDEO PLAYER ─ */
.player{background:#000;border-radius:var(--r);overflow:hidden;aspect-ratio:16/9;width:100%}
.player iframe{width:100%;height:100%;border:none}

/* ─ WATCH LAYOUT ─ */
.wl{display:grid;grid-template-columns:1fr 345px;gap:1.4rem;align-items:start}
@media(max-width:1000px){.wl{grid-template-columns:1fr}}
.ls-box{background:var(--bg2);border:1px solid var(--br);border-radius:var(--r);overflow:hidden;position:sticky;top:80px;max-height:calc(100vh - 96px);overflow-y:auto}
.ls-hd{padding:1.1rem;border-bottom:1px solid var(--br);position:sticky;top:0;background:var(--bg2);z-index:5}

/* ─ PROFILE ─ */
.av-big{width:96px;height:96px;border-radius:50%;background:linear-gradient(135deg,var(--p),var(--s));display:flex;align-items:center;justify-content:center;font-size:2.4rem;font-weight:700;margin:0 auto 1rem;border:3px solid var(--brp)}

/* ─ TABLE ─ */
.tbl{width:100%;border-collapse:collapse}
.tbl th{padding:.85rem 1.2rem;text-align:left;font-size:.74rem;color:var(--t3);text-transform:uppercase;letter-spacing:.4px;border-bottom:1px solid var(--br)}
.tbl td{padding:.9rem 1.2rem;border-bottom:1px solid rgba(255,255,255,.04);font-size:.88rem;transition:background .2s}
.tbl tr:hover td{background:rgba(255,255,255,.018)}

/* ─ COURSE HERO ─ */
.chero{background:linear-gradient(135deg,rgba(108,99,255,.09),rgba(255,101,132,.04));border:1px solid var(--br);border-radius:var(--rl);padding:2.5rem;margin-bottom:1.8rem}
.enroll-box{background:var(--bg2);border:1px solid var(--brp);border-radius:var(--r);padding:1.8rem;position:sticky;top:80px}
.ep{font-size:1.9rem;font-weight:900;color:var(--a);margin-bottom:1rem}

/* ─ LESSON EDITOR ─ */
.le-row{background:rgba(255,255,255,.04);border:1px solid var(--br);border-radius:var(--rs);padding:1rem;margin-bottom:.7rem}

/* ─ FOOTER ─ */
.footer{background:var(--bg2);border-top:1px solid var(--br);padding:2.5rem 0 1.2rem;position:relative;z-index:1}
.fg2{display:grid;grid-template-columns:2fr 1fr 1fr;gap:2rem;margin-bottom:2rem}
.fbl{font-size:.83rem;color:var(--t2);margin-top:.6rem;line-height:1.7}
.fh{font-size:.76rem;font-weight:700;text-transform:uppercase;letter-spacing:1px;color:var(--t3);margin-bottom:.8rem}
.flinks{list-style:none}.flinks li{margin-bottom:.4rem}.flinks a{color:var(--t2);text-decoration:none;font-size:.83rem;transition:color .2s}.flinks a:hover{color:var(--pl)}
.fbot{border-top:1px solid var(--br);padding-top:1.2rem;display:flex;align-items:center;justify-content:space-between;color:var(--t3);font-size:.77rem;flex-wrap:wrap;gap:.5rem}
@media(max-width:700px){.fg2{grid-template-columns:1fr 1fr}.sidebar{display:none}.dash-wrap{display:block}}
@media(max-width:450px){.fg2{grid-template-columns:1fr}}

/* ─ MISC ─ */
.divider{border:none;border-top:1px solid var(--br);margin:1.4rem 0}
.flex{display:flex}.fbet{display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:.75rem}
.fcenter{display:flex;align-items:center;justify-content:center}
.gap1{gap:.5rem}.gap2{gap:1rem}.items-c{align-items:center}
.mt1{margin-top:.5rem}.mt2{margin-top:1rem}.mt3{margin-top:1.5rem}.mt4{margin-top:2rem}
.mb1{margin-bottom:.5rem}.mb2{margin-bottom:1rem}.mb3{margin-bottom:1.5rem}
.tc2{text-align:center}.t2{color:var(--t2)}.t3{color:var(--t3)}
.fw{width:100%}
.ph-box{text-align:center;padding:3.5rem 2rem;background:rgba(255,255,255,.025);border:1px solid var(--br);border-radius:var(--r)}
.ph-box .ph-icon{font-size:3rem;margin-bottom:.75rem}
::-webkit-scrollbar{width:5px}::-webkit-scrollbar-track{background:var(--bg)}::-webkit-scrollbar-thumb{background:rgba(108,99,255,.35);border-radius:3px}
@keyframes fadeUp{from{opacity:0;transform:translateY(16px)}to{opacity:1;transform:translateY(0)}}
.fade-up{animation:fadeUp .45s ease both}
@keyframes countUp{from{opacity:0}to{opacity:1}}
</style>
</head>
<body>

<!-- ══════════════════════════════ NAVBAR ══════════════════════════════ -->
<nav class="nav" id="topNav">
    <a href="<?=url('home')?>" class="nav-brand" id="navBrand">
        <div class="nav-logo">🎓</div>
        <span>InnovExa</span>
    </a>
    <ul class="nav-links">
        <li><a href="<?=url('home')?>" id="navHome" <?=$page==='home'?'class="act"':''?>>Home</a></li>
        <li><a href="<?=url('courses')?>" id="navCourses" <?=$page==='courses'?'class="act"':''?>>Courses</a></li>
        <?php if(loggedIn()&&role()==='student'):?><li><a href="<?=url('dashboard')?>" id="navDash" <?=$page==='dashboard'?'class="act"':''?>>Dashboard</a></li><?php endif;?>
        <?php if(loggedIn()&&role()==='instructor'):?><li><a href="<?=url('instructor')?>" id="navInst" <?=$page==='instructor'?'class="act"':''?>>Dashboard</a></li><?php endif;?>
    </ul>
    <div class="nav-right">
        <?php if(loggedIn()):?>
            <a href="<?=url('profile')?>" class="btn btn-sec btn-sm" id="navProfile">👤 <?=htmlspecialchars(explode(' ',$_SESSION['user_name'])[0])?></a>
            <a href="<?=url('logout')?>" class="btn btn-d btn-sm" id="navLogout">Logout</a>
        <?php else:?>
            <a href="<?=url('login')?>" class="btn btn-sec btn-sm" id="navLogin">Login</a>
            <a href="<?=url('register')?>" class="btn btn-p btn-sm" id="navReg">Get Started</a>
        <?php endif;?>
    </div>
</nav>

<!-- Flash Message -->
<?php if($flashMsg):?>
<div class="wrap" style="padding-top:.9rem;position:relative;z-index:1;">
    <div class="alert <?=$flashMsg['t']==='error'?'al-e':($flashMsg['t']==='info'?'al-i':'al-s')?>" id="flashMsg"><?=htmlspecialchars($flashMsg['m'])?></div>
</div>
<?php endif;?>

<?php
// ═══════════════════════════════════════════════════════════════════════
//  PAGE: HOME
// ═══════════════════════════════════════════════════════════════════════
if ($page === 'home'):
    $featured = $conn->query("SELECT c.*,u.name as iname,(SELECT COUNT(*) FROM enrollments WHERE course_id=c.id) ec,(SELECT COUNT(*) FROM lessons WHERE course_id=c.id) lc FROM courses c JOIN users u ON c.instructor_id=u.id ORDER BY c.created_at DESC LIMIT 3");
    $stats=['courses'=>$conn->query("SELECT COUNT(*) c FROM courses")->fetch_assoc()['c'],'students'=>$conn->query("SELECT COUNT(*) c FROM users WHERE role='student'")->fetch_assoc()['c'],'inst'=>$conn->query("SELECT COUNT(*) c FROM users WHERE role='instructor'")->fetch_assoc()['c']];
?>
<section class="hero page">
    <div class="wrap">
        <div class="hero-badge">🚀 Now with AI-powered learning paths</div>
        <h1 class="fade-up">Learn Today,<br><span class="grad">Lead Tomorrow</span></h1>
        <p class="fade-up">Access world-class courses in web dev, data science, design and more. Build real skills with hands-on projects.</p>
        <div class="hero-btns fade-up">
            <a href="<?=url('courses')?>" class="btn btn-p btn-lg" id="heroBrowse">🎓 Explore Courses</a>
            <?php if(!loggedIn()):?><a href="<?=url('register')?>" class="btn btn-sec btn-lg" id="heroStart">✨ Start Free</a><?php endif;?>
        </div>
        <div class="hero-stats">
            <div class="hs"><span class="n" data-c="<?=$stats['courses']?>" data-s="+">0</span><span class="l">Expert Courses</span></div>
            <div class="hs"><span class="n" data-c="<?=max($stats['students'],5200)?>" data-s="+">0</span><span class="l">Active Students</span></div>
            <div class="hs"><span class="n" data-c="<?=$stats['inst']?>" data-s="+">0</span><span class="l">Instructors</span></div>
            <div class="hs"><span class="n" data-c="98" data-s="%">0</span><span class="l">Satisfaction</span></div>
        </div>
    </div>
</section>

<!-- Features -->
<section class="sec sec-alt">
    <div class="wrap">
        <div class="sec-hd"><p class="sec-tag">Why InnovExa</p><h2>Everything you need to succeed</h2><p>A platform built for modern learners who demand the best.</p></div>
        <div class="grid g3" style="gap:1.1rem;">
            <?php foreach([['🎯','Expert-Led Courses','Learn from industry professionals with real-world experience.'],['📊','Progress Tracking','Monitor your journey with detailed analytics and milestones.'],['🏆','Certificates','Earn recognized certs to showcase your skills to employers.'],['📱','Learn Anywhere','Fully responsive — desktop, tablet, or smartphone.'],['⚡','Instant Feedback','Interactive quizzes keep you engaged and on track.'],['🌐','Global Community','Join learners and instructors from 150+ countries.']] as $f):?>
            <div class="fc"><div class="fi"><?=$f[0]?></div><h3 class="ft"><?=$f[1]?></h3><p class="fd"><?=$f[2]?></p></div>
            <?php endforeach;?>
        </div>
    </div>
</section>

<!-- Featured Courses -->
<section class="sec">
    <div class="wrap">
        <div class="sec-hd"><p class="sec-tag">Featured Courses</p><h2>Start learning today</h2><p>Handpicked courses to get you started.</p></div>
        <div class="grid g3">
            <?php while($c=$featured->fetch_assoc()):?>
            <a href="<?=url('course',['id'=>$c['id']])?>" class="card" style="text-decoration:none;color:inherit;" id="hCourse<?=$c['id']?>">
                <?php if($c['thumbnail']):?><img src="<?=htmlspecialchars($c['thumbnail'])?>" class="card-img" alt="<?=htmlspecialchars($c['title'])?>">
                <?php else:?><div class="card-ph">📚</div><?php endif;?>
                <div class="card-body">
                    <div class="card-meta"><span class="badge bp"><?=htmlspecialchars($c['category'])?></span><span class="badge bi"><?=htmlspecialchars($c['level'])?></span></div>
                    <h3 class="card-title"><?=htmlspecialchars($c['title'])?></h3>
                    <p class="card-desc"><?=htmlspecialchars($c['description'])?></p>
                    <div style="font-size:.77rem;color:var(--t3);display:flex;gap:1rem;flex-wrap:wrap;">
                        <span>👤 <?=htmlspecialchars($c['iname'])?></span>
                        <span>📚 <?=$c['lc']?> lessons</span>
                        <span>👥 <?=$c['ec']?> students</span>
                    </div>
                </div>
                <div class="card-foot">
                    <span class="price <?=$c['price']==0?'free':''?>"><?=$c['price']==0?'🎉 Free':'$'.number_format($c['price'],2)?></span>
                    <span class="btn btn-p btn-sm">Enroll Now</span>
                </div>
            </a>
            <?php endwhile;?>
        </div>
        <div class="tc2 mt3"><a href="<?=url('courses')?>" class="btn btn-sec" id="viewAll">View All Courses →</a></div>
    </div>
</section>

<!-- Testimonials -->
<section class="sec sec-alt">
    <div class="wrap">
        <div class="sec-hd"><p class="sec-tag">Testimonials</p><h2>What our students say</h2></div>
        <div class="grid g3" style="gap:1.1rem;">
            <?php foreach([['MK','Maria Kowalski','Full-Stack Dev @ Google','InnovExa transformed my career. I went from zero to landing a full-stack job in just 6 months!','linear-gradient(135deg,var(--p),var(--s))'],['JR','James Rodriguez','Data Scientist @ Netflix','Outstanding instruction quality. The projects are real-world and the mentorship is world-class!','linear-gradient(135deg,var(--s),var(--a))'],['AL','Aisha Li','UX Designer @ Apple','The UI is beautiful, courses comprehensive, progress tracking kept me motivated. Highly recommend!','linear-gradient(135deg,var(--a2),var(--p))']] as $t):?>
            <div class="tc"><div class="stars">⭐⭐⭐⭐⭐</div><p class="tc-txt">"<?=$t[3]?>"</p>
                <div class="tc-auth"><div class="tc-av" style="background:<?=$t[4]?>"><?=$t[0]?></div>
                <div><p class="tc-name"><?=$t[1]?></p><p class="tc-role"><?=$t[2]?></p></div></div>
            </div>
            <?php endforeach;?>
        </div>
    </div>
</section>

<!-- CTA -->
<?php if(!loggedIn()):?>
<section class="sec" style="background:linear-gradient(135deg,rgba(108,99,255,.1),rgba(255,101,132,.05));border-top:1px solid var(--br);border-bottom:1px solid var(--br);">
    <div class="wrap tc2">
        <h2 style="font-size:2rem;font-weight:800;margin-bottom:.75rem;">Ready to transform your future?</h2>
        <p class="t2 mb3">Join 5,000+ learners building their dream careers.</p>
        <a href="<?=url('register')?>" class="btn btn-p btn-lg" id="ctaStart">Get Started Free 🚀</a>
    </div>
</section>
<?php endif;?>

<?php
// ═══════════════════════════════════════════════════════════════════════
//  PAGE: COURSES
// ═══════════════════════════════════════════════════════════════════════
elseif ($page === 'courses'):
    $search=S($conn,$_GET['q']??'');$cat=S($conn,$_GET['cat']??'');$lv=S($conn,$_GET['lv']??'');
    $sql="SELECT c.*,u.name iname,(SELECT COUNT(*) FROM enrollments WHERE course_id=c.id) ec,(SELECT COUNT(*) FROM lessons WHERE course_id=c.id) lc FROM courses c JOIN users u ON c.instructor_id=u.id WHERE 1=1";
    if($search) $sql.=" AND (c.title LIKE '%$search%' OR c.description LIKE '%$search%')";
    if($cat) $sql.=" AND c.category='$cat'";
    if($lv) $sql.=" AND c.level='$lv'";
    $sql.=" ORDER BY c.created_at DESC";
    $courses=$conn->query($sql);
    $cats=$conn->query("SELECT DISTINCT category FROM courses ORDER BY category");
?>
<div class="page pg">
    <div class="wrap">
        <div class="fbet mb3">
            <div><h1 style="font-size:1.7rem;font-weight:800;">Browse Courses</h1><p class="t2 mt1"><?=$courses->num_rows?> courses found</p></div>
        </div>
        <!-- Filter -->
        <form method="GET" style="display:flex;gap:.7rem;flex-wrap:wrap;margin-bottom:2rem;">
            <input type="hidden" name="page" value="courses">
            <div style="flex:1;min-width:200px;position:relative;">
                <span style="position:absolute;left:12px;top:50%;transform:translateY(-50%);color:var(--t3);">🔍</span>
                <input type="text" name="q" class="fi2" placeholder="Search courses..." value="<?=htmlspecialchars($search)?>" style="padding-left:38px;" id="searchInput">
            </div>
            <select name="cat" class="fi2" style="width:auto;" id="catFilter" onchange="this.form.submit()">
                <option value="">All Categories</option>
                <?php while($c=$cats->fetch_assoc()):?><option value="<?=htmlspecialchars($c['category'])?>" <?=$cat===$c['category']?'selected':''?>><?=htmlspecialchars($c['category'])?></option><?php endwhile;?>
            </select>
            <select name="lv" class="fi2" style="width:auto;" id="lvFilter" onchange="this.form.submit()">
                <option value="">All Levels</option>
                <?php foreach(['Beginner','Intermediate','Advanced'] as $l):?><option value="<?=$l?>" <?=$lv===$l?'selected':''?>><?=$l?></option><?php endforeach;?>
            </select>
            <button type="submit" class="btn btn-p" id="searchBtn">Search</button>
            <?php if($search||$cat||$lv):?><a href="<?=url('courses')?>" class="btn btn-sec" id="clearFilter">Clear</a><?php endif;?>
        </form>

        <?php if($courses->num_rows===0):?>
        <div class="ph-box"><div class="ph-icon">🔍</div><h3 style="margin-bottom:.4rem;">No courses found</h3><p class="t2">Try adjusting your filters.</p><a href="<?=url('courses')?>" class="btn btn-p mt2">View All</a></div>
        <?php else:?>
        <div class="grid gauto">
            <?php while($c=$courses->fetch_assoc()):?>
            <a href="<?=url('course',['id'=>$c['id']])?>" class="card" style="text-decoration:none;color:inherit;" id="cCard<?=$c['id']?>">
                <?php if($c['thumbnail']):?><img src="<?=htmlspecialchars($c['thumbnail'])?>" class="card-img" alt="<?=htmlspecialchars($c['title'])?>">
                <?php else:?><div class="card-ph">📚</div><?php endif;?>
                <div class="card-body">
                    <div class="card-meta"><span class="badge bp"><?=htmlspecialchars($c['category'])?></span><span class="badge bi"><?=htmlspecialchars($c['level'])?></span></div>
                    <h2 class="card-title"><?=htmlspecialchars($c['title'])?></h2>
                    <p class="card-desc"><?=htmlspecialchars($c['description'])?></p>
                    <div style="font-size:.77rem;color:var(--t3);display:flex;gap:.8rem;flex-wrap:wrap;"><span>👤 <?=htmlspecialchars($c['iname'])?></span><span>📚 <?=$c['lc']?></span><span>👥 <?=$c['ec']?></span></div>
                </div>
                <div class="card-foot">
                    <span class="price <?=$c['price']==0?'free':''?>"><?=$c['price']==0?'🎉 Free':'$'.number_format($c['price'],2)?></span>
                    <span class="btn btn-p btn-sm">View →</span>
                </div>
            </a>
            <?php endwhile;?>
        </div>
        <?php endif;?>
    </div>
</div>

<?php
// ═══════════════════════════════════════════════════════════════════════
//  PAGE: COURSE DETAIL
// ═══════════════════════════════════════════════════════════════════════
elseif ($page === 'course'):
    $cid=(int)($_GET['id']??0);
    if(!$cid){redirect(url('courses'));}
    $c=$conn->query("SELECT c.*,u.name iname,u.bio ibio FROM courses c JOIN users u ON c.instructor_id=u.id WHERE c.id=$cid")->fetch_assoc();
    if(!$c){redirect(url('courses'));}
    $ls=$conn->query("SELECT * FROM lessons WHERE course_id=$cid ORDER BY lesson_order");
    $llist=[];while($l=$ls->fetch_assoc())$llist[]=$l;
    $ec=$conn->query("SELECT COUNT(*) c FROM enrollments WHERE course_id=$cid")->fetch_assoc()['c'];
    $isEnrolled=false;
    if(loggedIn()){$isEnrolled=$conn->query("SELECT id FROM enrollments WHERE user_id=$uid2 AND course_id=$cid")->num_rows>0;}
    $totalM=0;foreach($llist as $l){preg_match('/\d+/',$l['duration'],$m);$totalM+=(int)($m[0]??0);}
    $totalH=round($totalM/60,1);
?>
<div class="page pg">
    <div class="wrap">
        <div style="display:grid;grid-template-columns:1fr 320px;gap:2rem;align-items:start;" id="cdetailGrid">
            <div>
                <!-- Breadcrumb -->
                <div style="display:flex;gap:.5rem;font-size:.8rem;color:var(--t3);margin-bottom:1rem;align-items:center;">
                    <a href="<?=url('courses')?>" style="color:var(--pl);text-decoration:none;">Courses</a><span>›</span>
                    <span><?=htmlspecialchars($c['category'])?></span>
                </div>
                <div class="chero">
                    <div class="card-meta mb2">
                        <span class="badge bp"><?=htmlspecialchars($c['category'])?></span>
                        <span class="badge bi"><?=htmlspecialchars($c['level'])?></span>
                        <?php if($isEnrolled):?><span class="badge bs">✓ Enrolled</span><?php endif;?>
                    </div>
                    <h1 style="font-size:clamp(1.5rem,3vw,2.1rem);font-weight:800;line-height:1.15;margin-bottom:1rem;"><?=htmlspecialchars($c['title'])?></h1>
                    <p style="color:var(--t2);line-height:1.7;margin-bottom:1.4rem;"><?=htmlspecialchars($c['description'])?></p>
                    <div style="display:flex;gap:1.5rem;flex-wrap:wrap;font-size:.85rem;color:var(--t2);">
                        <span>👤 <strong><?=htmlspecialchars($c['iname'])?></strong></span>
                        <span>📚 <strong><?=count($llist)?></strong> lessons</span>
                        <span>⏱️ <strong><?=$totalH?>h</strong></span>
                        <span>👥 <strong><?=$ec?></strong> students</span>
                    </div>
                    <?php if($c['thumbnail']):?><img src="<?=htmlspecialchars($c['thumbnail'])?>" alt="<?=htmlspecialchars($c['title'])?>" style="width:100%;border-radius:var(--r);margin-top:1.5rem;max-height:260px;object-fit:cover;"><?php endif;?>
                </div>

                <!-- Syllabus -->
                <div class="card mb2">
                    <div style="padding:1.4rem;">
                        <h2 style="font-size:1rem;font-weight:700;margin-bottom:1.1rem;">📋 Course Curriculum</h2>
                        <?php foreach($llist as $i=>$l):?>
                        <div class="li" style="cursor:default;">
                            <div class="ln"><?=$i+1?></div>
                            <div style="flex:1;min-width:0;"><div class="lt"><?=htmlspecialchars($l['title'])?></div><div class="ld">⏱️ <?=htmlspecialchars($l['duration'])?><?=$l['description']?' · '.htmlspecialchars($l['description']):'';?></div></div>
                            <?php if($isEnrolled):?><a href="<?=url('watch',['cid'=>$cid,'lid'=>$l['id']])?>" class="btn btn-p btn-sm" id="playL<?=$l['id']?>">▶ Play</a>
                            <?php else:?><span style="color:var(--t3);font-size:.85rem;">🔒</span><?php endif;?>
                        </div>
                        <?php endforeach;?>
                    </div>
                </div>

                <!-- Instructor -->
                <div class="card">
                    <div style="padding:1.4rem;">
                        <h2 style="font-size:1rem;font-weight:700;margin-bottom:1rem;">👩‍🏫 Your Instructor</h2>
                        <div style="display:flex;align-items:center;gap:.9rem;margin-bottom:.7rem;">
                            <div style="width:52px;height:52px;border-radius:50%;background:linear-gradient(135deg,var(--p),var(--s));display:flex;align-items:center;justify-content:center;font-weight:700;font-size:1.1rem;flex-shrink:0;"><?=strtoupper(substr($c['iname'],0,2))?></div>
                            <div><p style="font-weight:700;"><?=htmlspecialchars($c['iname'])?></p><p style="font-size:.79rem;color:var(--t3);">Expert Instructor</p></div>
                        </div>
                        <?php if($c['ibio']):?><p style="color:var(--t2);font-size:.85rem;line-height:1.6;"><?=htmlspecialchars($c['ibio'])?></p><?php endif;?>
                    </div>
                </div>
            </div>

            <!-- Enroll Box -->
            <div class="enroll-box" id="enrollBox">
                <div class="ep"><?=$c['price']==0?'🎉 Free':'$'.number_format($c['price'],2)?></div>
                <?php if($isEnrolled):?>
                    <a href="<?=url('watch',['cid'=>$cid,'lid'=>(!empty($llist)?$llist[0]['id']:0)])?>" class="btn btn-g btn-fw btn-lg" id="continueLearning">▶ Continue Learning</a>
                    <p style="text-align:center;color:var(--a);font-size:.83rem;margin-top:.6rem;">✓ You're enrolled!</p>
                <?php elseif(loggedIn()&&role()==='student'):?>
                    <a href="<?=url('enroll',['cid'=>$cid])?>" class="btn btn-p btn-fw btn-lg" id="enrollBtn">🚀 Enroll Now</a>
                <?php elseif(!loggedIn()):?>
                    <a href="<?=url('login')?>" class="btn btn-p btn-fw btn-lg" id="loginEnroll">🔑 Login to Enroll</a>
                    <a href="<?=url('register')?>" class="btn btn-sec btn-fw mt2" id="regEnroll">✨ Create Free Account</a>
                <?php else:?>
                    <p class="t2 tc2" style="font-size:.85rem;">Instructors cannot enroll in courses.</p>
                <?php endif;?>
                <hr class="divider">
                <ul style="list-style:none;font-size:.83rem;color:var(--t2);">
                    <?php foreach(["📚 ".count($llist)." lessons","⏱️ $totalH hours of content","📊 ".htmlspecialchars($c['level'])." level","🏆 Certificate of completion","📱 Access on all devices","🔄 Lifetime access"] as $item):?>
                    <li style="margin-bottom:.5rem;"><?=$item?></li><?php endforeach;?>
                </ul>
            </div>
        </div>
    </div>
</div>
<style>@media(max-width:900px){#cdetailGrid{grid-template-columns:1fr!important}#enrollBox{position:static!important}}</style>

<?php
// ═══════════════════════════════════════════════════════════════════════
//  PAGE: LOGIN
// ═══════════════════════════════════════════════════════════════════════
elseif ($page === 'login'):?>
<div class="auth-wrap">
    <div class="auth-box fade-up">
        <div class="auth-hd">
            <div class="auth-logo">🎓</div>
            <h1 class="auth-title">Welcome back</h1>
            <p class="auth-sub">Sign in to continue your learning journey</p>
        </div>
        <?php if($formError):?><div class="alert al-e">⚠️ <?=htmlspecialchars($formError)?></div><?php endif;?>
        <div class="alert al-i" style="font-size:.78rem;">
            <div><strong>Demo:</strong> 👩‍🏫 instructor@lms.com / instructor123 &nbsp;|&nbsp; 🎓 student@lms.com / student123</div>
        </div>
        <form method="POST" id="loginForm">
            <input type="hidden" name="page" value="login">
            <div class="fg"><label class="fl">Email Address</label><input type="email" name="email" class="fi2" placeholder="you@example.com" value="<?=htmlspecialchars($_POST['email']??'')?>" required id="loginEmail"></div>
            <div class="fg"><label class="fl">Password</label><input type="password" name="password" class="fi2" placeholder="••••••••" required id="loginPass"></div>
            <button type="submit" class="btn btn-p btn-fw btn-lg mt1" id="loginBtn">🚀 Sign In</button>
        </form>
        <hr class="divider">
        <p class="tc2 t2" style="font-size:.85rem;">Don't have an account? <a href="<?=url('register')?>" style="color:var(--pl);font-weight:600;">Create one →</a></p>
    </div>
</div>

<?php
// ═══════════════════════════════════════════════════════════════════════
//  PAGE: REGISTER
// ═══════════════════════════════════════════════════════════════════════
elseif ($page === 'register'):?>
<div class="auth-wrap">
    <div class="auth-box fade-up" style="max-width:460px;">
        <div class="auth-hd">
            <div class="auth-logo">🎓</div>
            <h1 class="auth-title">Create your account</h1>
            <p class="auth-sub">Join thousands of learners on InnovExa</p>
        </div>
        <?php if($formError):?><div class="alert al-e">⚠️ <?=htmlspecialchars($formError)?></div><?php endif;?>
        <label class="fl mb1">I want to join as:</label>
        <div class="role-tgl" id="roleTgl">
            <button type="button" class="role-btn act" data-r="student" id="rBtnS">🎓 Student</button>
            <button type="button" class="role-btn" data-r="instructor" id="rBtnI">👩‍🏫 Instructor</button>
        </div>
        <form method="POST" id="regForm">
            <input type="hidden" name="page" value="register">
            <input type="hidden" name="role" id="roleVal" value="student">
            <div class="fg"><label class="fl">Full Name</label><input type="text" name="name" class="fi2" placeholder="John Doe" value="<?=htmlspecialchars($_POST['name']??'')?>" required id="regName"></div>
            <div class="fg"><label class="fl">Email</label><input type="email" name="email" class="fi2" placeholder="you@example.com" value="<?=htmlspecialchars($_POST['email']??'')?>" required id="regEmail"></div>
            <div class="fg"><label class="fl">Password <span class="t3">(min 6 chars)</span></label><input type="password" name="password" class="fi2" placeholder="••••••••" required id="regPass"></div>
            <div class="fg"><label class="fl">Confirm Password</label><input type="password" name="confirm" class="fi2" placeholder="••••••••" required id="regConf"></div>
            <button type="submit" class="btn btn-p btn-fw btn-lg mt1" id="regBtn">✨ Create Account</button>
        </form>
        <hr class="divider">
        <p class="tc2 t2" style="font-size:.85rem;">Already have an account? <a href="<?=url('login')?>" style="color:var(--pl);font-weight:600;">Sign in →</a></p>
    </div>
</div>

<?php
// ═══════════════════════════════════════════════════════════════════════
//  PAGE: STUDENT DASHBOARD
// ═══════════════════════════════════════════════════════════════════════
elseif ($page === 'dashboard'):
    if(!loggedIn()||role()!=='student'){redirect(url('login'));}
    $user=$conn->query("SELECT * FROM users WHERE id=$uid2")->fetch_assoc();
    $en=$conn->query("SELECT c.*,e.enrolled_at,u.name iname,(SELECT COUNT(*) FROM lessons WHERE course_id=c.id) tl,(SELECT COUNT(*) FROM progress p JOIN lessons l ON p.lesson_id=l.id WHERE l.course_id=c.id AND p.user_id=$uid2 AND p.is_completed=1) cl FROM enrollments e JOIN courses c ON e.course_id=c.id JOIN users u ON c.instructor_id=u.id WHERE e.user_id=$uid2 ORDER BY e.enrolled_at DESC");
    $enList=[];while($r=$en->fetch_assoc())$enList[]=$r;
    $totC=count($enList);$totL=0;$totFin=0;foreach($enList as $r){$totL+=$r['cl'];if($r['tl']>0&&$r['cl']>=$r['tl'])$totFin++;}
?>
<div class="dash-wrap">
    <aside class="sidebar">
        <div>
            <div><p class="sb-lbl">Main</p><ul class="sb-nav">
                <li><a href="<?=url('dashboard')?>" class="act">📊 Dashboard</a></li>
                <li><a href="<?=url('courses')?>">🎓 Browse Courses</a></li>
                <li><a href="<?=url('profile')?>">👤 My Profile</a></li>
            </ul></div>
            <div class="mt2"><p class="sb-lbl">My Courses</p><ul class="sb-nav">
                <?php if(empty($enList)):?><li><a href="<?=url('courses')?>" style="color:var(--t3);font-style:italic;">No courses yet</a></li>
                <?php else:foreach($enList as $c):?><li><a href="<?=url('watch',['cid'=>$c['id'],'lid'=>0])?>">▶ <?=htmlspecialchars(substr($c['title'],0,20))?>...</a></li><?php endforeach;endif;?>
            </ul></div>
        </div>
        <div style="margin-top:auto;"><a href="<?=url('logout')?>" class="btn btn-d btn-fw btn-sm">🚪 Logout</a></div>
    </aside>
    <main class="mc">
        <div class="fbet mb3">
            <div><h1 style="font-size:1.55rem;font-weight:800;">Welcome back, <?=htmlspecialchars(explode(' ',$user['name'])[0])?>! 👋</h1><p class="t2 mt1">Your learning progress overview</p></div>
            <a href="<?=url('courses')?>" class="btn btn-p">+ Explore</a>
        </div>
        <div class="sgrid">
            <div class="sc" style="--ic:rgba(108,99,255,.12);"><div class="sc-icon">🎓</div><div><div class="sc-val" data-c="<?=$totC?>">0</div><div class="sc-lbl">Enrolled</div></div></div>
            <div class="sc" style="--ic:rgba(67,233,123,.1);"><div class="sc-icon">✅</div><div><div class="sc-val" data-c="<?=$totL?>">0</div><div class="sc-lbl">Lessons Done</div></div></div>
            <div class="sc" style="--ic:rgba(255,193,7,.1);"><div class="sc-icon">🏆</div><div><div class="sc-val" data-c="<?=$totFin?>">0</div><div class="sc-lbl">Completed</div></div></div>
            <div class="sc" style="--ic:rgba(255,101,132,.08);"><div class="sc-icon">🔥</div><div><div class="sc-val">7</div><div class="sc-lbl">Day Streak</div></div></div>
        </div>
        <h2 style="font-size:1.1rem;font-weight:700;margin-bottom:1.1rem;">📚 My Courses</h2>
        <?php if(empty($enList)):?>
        <div class="ph-box"><div class="ph-icon">📭</div><h3>You haven't enrolled yet</h3><p class="t2 mt1">Browse our catalog and start learning!</p><a href="<?=url('courses')?>" class="btn btn-p mt2">🎓 Browse Courses</a></div>
        <?php else:?><div class="grid gauto"><?php foreach($enList as $c):$p=$c['tl']>0?$c['cl']/$c['tl']*100:0;?>
            <div class="card">
                <?php if($c['thumbnail']):?><img src="<?=htmlspecialchars($c['thumbnail'])?>" class="card-img" style="height:145px;" alt="<?=htmlspecialchars($c['title'])?>">
                <?php else:?><div class="card-ph" style="height:120px;">📚</div><?php endif;?>
                <div class="card-body">
                    <div class="card-meta"><span class="badge bp"><?=htmlspecialchars($c['category'])?></span><?php if($p>=100):?><span class="badge bs">✓ Done</span><?php endif;?></div>
                    <h3 class="card-title"><?=htmlspecialchars($c['title'])?></h3>
                    <p style="font-size:.77rem;color:var(--t3);margin-bottom:.7rem;">by <?=htmlspecialchars($c['iname'])?></p>
                    <div class="pb-lbl"><span><?=$c['cl']?>/<?=$c['tl']?> lessons</span><span><?=round($p)?>%</span></div>
                    <div class="pb-wrap"><div class="pb" style="width:<?=$p?>%;"></div></div>
                    <a href="<?=url('watch',['cid'=>$c['id'],'lid'=>0])?>" class="btn btn-p btn-fw mt2"><?=$p==0?'🚀 Start':($p>=100?'🏆 Review':'▶ Continue')?></a>
                </div>
            </div>
        <?php endforeach;?></div><?php endif;?>
    </main>
</div>

<?php
// ═══════════════════════════════════════════════════════════════════════
//  PAGE: WATCH (Student Video Player)
// ═══════════════════════════════════════════════════════════════════════
elseif ($page === 'watch'):
    if(!loggedIn()||role()!=='student'){redirect(url('login'));}
    $cid=(int)($_GET['cid']??0);if(!$cid){redirect(url('dashboard'));}
    if($conn->query("SELECT id FROM enrollments WHERE user_id=$uid2 AND course_id=$cid")->num_rows===0) redirect(url('course',['id'=>$cid]));
    $course=$conn->query("SELECT c.*,u.name iname FROM courses c JOIN users u ON c.instructor_id=u.id WHERE c.id=$cid")->fetch_assoc();
    if(!$course) redirect(url('dashboard'));
    $allL=$conn->query("SELECT * FROM lessons WHERE course_id=$cid ORDER BY lesson_order");
    $llist=[];while($l=$allL->fetch_assoc())$llist[]=$l;
    $lid=(int)($_GET['lid']??0);if(!$lid&&!empty($llist))$lid=$llist[0]['id'];
    $cur=null;foreach($llist as $l){if($l['id']==$lid){$cur=$l;break;}}
    if(!$cur&&!empty($llist)){$cur=$llist[0];$lid=$cur['id'];}
    $prog=$conn->query("SELECT lesson_id FROM progress WHERE user_id=$uid2");
    $done=[];while($p=$prog->fetch_assoc())$done[$p['lesson_id']]=true;
    $tot=count($llist);$fin=count(array_intersect(array_column($llist,'id'),array_keys($done)));
    $pct=$tot>0?$fin/$tot*100:0;
    $prev=null;$next=null;for($i=0;$i<count($llist);$i++){if($llist[$i]['id']==$lid){if($i>0)$prev=$llist[$i-1];if($i<count($llist)-1)$next=$llist[$i+1];break;}}
?>
<div class="page" style="padding:1.25rem 0;">
    <div style="max-width:1380px;margin:0 auto;padding:0 1.4rem;">
        <div class="fbet mb2">
            <div style="display:flex;gap:.4rem;font-size:.79rem;color:var(--t3);align-items:center;flex-wrap:wrap;">
                <a href="<?=url('dashboard')?>" style="color:var(--pl);text-decoration:none;">Dashboard</a><span>›</span>
                <a href="<?=url('course',['id'=>$cid])?>" style="color:var(--pl);text-decoration:none;"><?=htmlspecialchars(substr($course['title'],0,28))?>...</a><span>›</span>
                <span><?=htmlspecialchars($cur['title']??'Lesson')?></span>
            </div>
            <div style="display:flex;align-items:center;gap:.7rem;">
                <span style="font-size:.79rem;color:var(--t3);"><?=$fin?>/<?=$tot?> done</span>
                <div class="pb-wrap" style="width:130px;"><div class="pb" id="topPB" style="width:<?=$pct?>%;"></div></div>
                <span style="font-size:.79rem;font-weight:600;color:var(--a);" id="topPBTxt"><?=round($pct)?>%</span>
            </div>
        </div>
        <div class="wl">
            <!-- Player -->
            <div>
                <?php if($cur):?>
                <div class="player" id="videoWrap"><iframe src="<?=htmlspecialchars($cur['video_url'])?>?autoplay=0" allowfullscreen title="<?=htmlspecialchars($cur['title'])?>"></iframe></div>
                <div class="card mt2">
                    <div style="padding:1.4rem;">
                        <div class="fbet mb2">
                            <div>
                                <h1 style="font-size:1.2rem;font-weight:700;margin-bottom:.25rem;"><?=htmlspecialchars($cur['title'])?></h1>
                                <p style="font-size:.79rem;color:var(--t3);">Lesson <?=$cur['lesson_order']?> · ⏱️ <?=htmlspecialchars($cur['duration'])?></p>
                            </div>
                            <label for="markDone" style="display:flex;align-items:center;gap:9px;cursor:pointer;background:var(--gc2);border:1px solid var(--brp);padding:9px 15px;border-radius:var(--rs);">
                                <input type="checkbox" id="markDone" <?=isset($done[$lid])?'checked':''?> onchange="markLesson(<?=$lid?>,this)" style="width:17px;height:17px;accent-color:var(--p);cursor:pointer;">
                                <span style="font-size:.85rem;font-weight:600;">Mark complete</span>
                            </label>
                        </div>
                        <?php if($cur['description']):?><p class="t2" style="font-size:.87rem;line-height:1.7;"><?=htmlspecialchars($cur['description'])?></p><?php endif;?>
                        <div class="fbet mt3">
                            <?php if($prev):?><a href="<?=url('watch',['cid'=>$cid,'lid'=>$prev['id']])?>" class="btn btn-sec" id="prevBtn">← Previous</a><?php else:?><div></div><?php endif;?>
                            <?php if($next):?><a href="<?=url('watch',['cid'=>$cid,'lid'=>$next['id']])?>" class="btn btn-p" id="nextBtn">Next →</a>
                            <?php else:?><a href="<?=url('dashboard')?>" class="btn btn-g" id="finishBtn">🏆 Course Complete!</a><?php endif;?>
                        </div>
                    </div>
                </div>
                <?php else:?>
                <div class="ph-box"><div class="ph-icon">📭</div><p>No lessons yet.</p></div>
                <?php endif;?>
            </div>
            <!-- Lesson Sidebar -->
            <div class="ls-box">
                <div class="ls-hd">
                    <p style="font-size:.9rem;font-weight:700;"><?=htmlspecialchars($course['title'])?></p>
                    <div class="pb-lbl mt1" style="font-size:.73rem;"><span><?=$fin?>/<?=$tot?> done</span><span style="color:var(--a);"><?=round($pct)?>%</span></div>
                    <div class="pb-wrap" style="height:4px;margin-top:4px;"><div class="pb" style="width:<?=$pct?>%;"></div></div>
                </div>
                <div style="padding:.7rem;">
                    <?php foreach($llist as $i=>$l):$isDone=isset($done[$l['id']]);$isAct=$l['id']==$lid;?>
                    <a href="<?=url('watch',['cid'=>$cid,'lid'=>$l['id']])?>" class="li <?=$isAct?'act':''?> <?=$isDone?'done':''?>" id="sL<?=$l['id']?>">
                        <div class="ln <?=$isDone?'done':''?>" data-lid="<?=$l['id']?>" data-ord="<?=$i+1?>"><?=$isDone?'✓':($i+1)?></div>
                        <div style="flex:1;min-width:0;"><div class="lt"><?=htmlspecialchars($l['title'])?></div><div class="ld">⏱️ <?=htmlspecialchars($l['duration'])?></div></div>
                    </a>
                    <?php endforeach;?>
                </div>
            </div>
        </div>
    </div>
</div>
<script>
function markLesson(lid, cb) {
    const done = cb.checked ? 1 : 0;
    const formData = new URLSearchParams();
    formData.append('page','ajax_progress');formData.append('lid',lid);formData.append('done',done);
    fetch(window.location.pathname+'?page=ajax_progress',{method:'POST',headers:{'Content-Type':'application/x-www-form-urlencoded'},body:formData})
    .then(r=>r.json()).then(d=>{
        if(!d.ok) return;
        const pct = d.pct;
        document.getElementById('topPB').style.width = pct+'%';
        document.getElementById('topPBTxt').textContent = Math.round(pct)+'%';
        const numEl = document.querySelector(`.ln[data-lid="${lid}"]`);
        if(numEl){
            numEl.classList.toggle('done', done==1);
            numEl.textContent = done==1 ? '✓' : numEl.dataset.ord;
        }
        const liEl = cb.closest('.li');
        if(liEl){liEl.classList.toggle('done',done==1);liEl.style.transform='scale(1.02)';setTimeout(()=>liEl.style.transform='',200);}
    }).catch(()=>{ cb.checked=!cb.checked; });
}
</script>

<?php
// ═══════════════════════════════════════════════════════════════════════
//  PAGE: INSTRUCTOR DASHBOARD
// ═══════════════════════════════════════════════════════════════════════
elseif ($page === 'instructor'):
    if(!loggedIn()||role()!=='instructor'){redirect(url('login'));}
    $user=$conn->query("SELECT * FROM users WHERE id=$uid2")->fetch_assoc();
    $myC=$conn->query("SELECT c.*,(SELECT COUNT(*) FROM enrollments WHERE course_id=c.id) ec,(SELECT COUNT(*) FROM lessons WHERE course_id=c.id) lc FROM courses c WHERE c.instructor_id=$uid2 ORDER BY c.created_at DESC");
    $cList=[];while($r=$myC->fetch_assoc())$cList[]=$r;
    $totC=count($cList);
    $totS=$conn->query("SELECT COUNT(DISTINCT e.user_id) c FROM enrollments e JOIN courses c ON e.course_id=c.id WHERE c.instructor_id=$uid2")->fetch_assoc()['c'];
    $totL=$conn->query("SELECT COUNT(*) c FROM lessons l JOIN courses c ON l.course_id=c.id WHERE c.instructor_id=$uid2")->fetch_assoc()['c'];
?>
<div class="dash-wrap">
    <aside class="sidebar">
        <div>
            <div><p class="sb-lbl">Instructor</p><ul class="sb-nav">
                <li><a href="<?=url('instructor')?>" class="act">📊 Dashboard</a></li>
                <li><a href="<?=url('create_course')?>">➕ Create Course</a></li>
                <li><a href="<?=url('courses')?>">🎓 Browse All</a></li>
                <li><a href="<?=url('profile')?>">👤 My Profile</a></li>
            </ul></div>
            <div class="mt2"><p class="sb-lbl">My Courses</p><ul class="sb-nav">
                <?php foreach($cList as $c):?><li><a href="<?=url('edit_course',['id'=>$c['id']])?>">✏️ <?=htmlspecialchars(substr($c['title'],0,20))?>...</a></li><?php endforeach;?>
            </ul></div>
        </div>
        <div style="margin-top:auto;"><a href="<?=url('logout')?>" class="btn btn-d btn-fw btn-sm">🚪 Logout</a></div>
    </aside>
    <main class="mc">
        <div class="fbet mb3">
            <div><h1 style="font-size:1.55rem;font-weight:800;">Instructor Dashboard 👩‍🏫</h1><p class="t2 mt1">Hello, <?=htmlspecialchars($user['name'])?>! Manage your courses.</p></div>
            <a href="<?=url('create_course')?>" class="btn btn-p">+ New Course</a>
        </div>
        <div class="sgrid">
            <div class="sc" style="--ic:rgba(108,99,255,.12);"><div class="sc-icon">📚</div><div><div class="sc-val" data-c="<?=$totC?>">0</div><div class="sc-lbl">Total Courses</div></div></div>
            <div class="sc" style="--ic:rgba(67,233,123,.1);"><div class="sc-icon">👥</div><div><div class="sc-val" data-c="<?=$totS?>">0</div><div class="sc-lbl">Students</div></div></div>
            <div class="sc" style="--ic:rgba(255,193,7,.1);"><div class="sc-icon">🎬</div><div><div class="sc-val" data-c="<?=$totL?>">0</div><div class="sc-lbl">Lessons</div></div></div>
            <div class="sc" style="--ic:rgba(255,101,132,.08);"><div class="sc-icon">⭐</div><div><div class="sc-val">4.9</div><div class="sc-lbl">Avg Rating</div></div></div>
        </div>
        <div class="card">
            <div style="padding:1.3rem 1.3rem 0;"><div class="fbet mb2"><h2 style="font-size:1rem;font-weight:700;">📋 My Courses</h2><a href="<?=url('create_course')?>" class="btn btn-p btn-sm">+ Add</a></div></div>
            <?php if(empty($cList)):?>
            <div class="ph-box"><div class="ph-icon">📭</div><h3>No courses yet</h3><p class="t2 mt1">Create your first course!</p><a href="<?=url('create_course')?>" class="btn btn-p mt2">Create Course</a></div>
            <?php else:?>
            <div style="overflow-x:auto;">
            <table class="tbl">
                <thead><tr>
                    <th>Course</th><th style="text-align:center;">Category</th>
                    <th style="text-align:center;">Students</th><th style="text-align:center;">Lessons</th>
                    <th style="text-align:center;">Price</th><th style="text-align:right;">Actions</th>
                </tr></thead>
                <tbody>
                    <?php foreach($cList as $c):?>
                    <tr>
                        <td>
                            <div style="display:flex;align-items:center;gap:.7rem;">
                                <?php if($c['thumbnail']):?><img src="<?=htmlspecialchars($c['thumbnail'])?>" style="width:46px;height:34px;object-fit:cover;border-radius:6px;flex-shrink:0;" alt="">
                                <?php else:?><div style="width:46px;height:34px;background:rgba(108,99,255,.13);border-radius:6px;display:flex;align-items:center;justify-content:center;flex-shrink:0;font-size:1.1rem;">📚</div><?php endif;?>
                                <div><p style="font-weight:600;font-size:.87rem;"><?=htmlspecialchars($c['title'])?></p><p style="font-size:.74rem;color:var(--t3);"><?=htmlspecialchars($c['level'])?></p></div>
                            </div>
                        </td>
                        <td style="text-align:center;"><span class="badge bp"><?=htmlspecialchars($c['category'])?></span></td>
                        <td style="text-align:center;font-weight:600;"><?=$c['ec']?></td>
                        <td style="text-align:center;font-weight:600;"><?=$c['lc']?></td>
                        <td style="text-align:center;color:var(--a);font-weight:700;"><?=$c['price']==0?'Free':'$'.number_format($c['price'],2)?></td>
                        <td style="text-align:right;">
                            <div style="display:flex;gap:.4rem;justify-content:flex-end;">
                                <a href="<?=url('course',['id'=>$c['id']])?>" class="btn btn-sec btn-sm">👁️</a>
                                <a href="<?=url('edit_course',['id'=>$c['id']])?>" class="btn btn-p btn-sm">✏️ Edit</a>
                                <a href="<?=url('delete_course',['cid'=>$c['id']])?>" class="btn btn-d btn-sm" onclick="return confirm('Delete this course?')">🗑️</a>
                            </div>
                        </td>
                    </tr>
                    <?php endforeach;?>
                </tbody>
            </table>
            </div><?php endif;?>
        </div>
    </main>
</div>

<?php
// ═══════════════════════════════════════════════════════════════════════
//  PAGE: CREATE / EDIT COURSE
// ═══════════════════════════════════════════════════════════════════════
elseif (in_array($page,['create_course','edit_course'])):
    if(!loggedIn()||role()!=='instructor'){redirect(url('login'));}
    $eid=(int)($_GET['id']??0);$c=null;$llist=[];$isEdit=false;
    if($eid){
        $c=$conn->query("SELECT * FROM courses WHERE id=$eid AND instructor_id=$uid2")->fetch_assoc();
        if($c){$isEdit=true;$lr=$conn->query("SELECT * FROM lessons WHERE course_id=$eid ORDER BY lesson_order");while($l=$lr->fetch_assoc())$llist[]=$l;}
    }
    $formAction = url('save_course');
?>
<div class="page pg">
    <div class="wrap" style="max-width:840px;">
        <div style="margin-bottom:1.5rem;">
            <div style="display:flex;gap:.4rem;font-size:.79rem;color:var(--t3);margin-bottom:.5rem;">
                <a href="<?=url('instructor')?>" style="color:var(--pl);text-decoration:none;">Dashboard</a><span>›</span>
                <span><?=$isEdit?'Edit Course':'Create Course'?></span>
            </div>
            <h1 style="font-size:1.6rem;font-weight:800;"><?=$isEdit?'✏️ Edit Course':'➕ Create New Course'?></h1>
            <p class="t2 mt1"><?=$isEdit?'Update course details and lessons.':'Fill in the details to publish your course.'?></p>
        </div>
        <?php if($formError):?><div class="alert al-e">⚠️ <?=htmlspecialchars($formError)?></div><?php endif;?>
        <form method="POST" action="<?=$formAction?>" id="courseForm">
            <input type="hidden" name="page" value="save_course">
            <input type="hidden" name="eid" value="<?=$eid?>">
            <div class="card mb2">
                <div style="padding:1.4rem;">
                    <h2 style="font-size:.95rem;font-weight:700;margin-bottom:1.1rem;padding-bottom:.7rem;border-bottom:1px solid var(--br);">📋 Course Details</h2>
                    <div class="fg"><label class="fl">Course Title *</label><input type="text" name="title" class="fi2" placeholder="e.g. Complete Web Dev Bootcamp" value="<?=htmlspecialchars($c['title']??'')?>" required id="cTitle"></div>
                    <div class="fg"><label class="fl">Description *</label><textarea name="desc" class="fi2" rows="4" placeholder="Describe what students will learn..." required id="cDesc"><?=htmlspecialchars($c['description']??'')?></textarea></div>
                    <div style="display:grid;grid-template-columns:1fr 1fr 1fr;gap:1rem;">
                        <div class="fg"><label class="fl">Category *</label><input type="text" name="cat" class="fi2" placeholder="e.g. Web Development" value="<?=htmlspecialchars($c['category']??'')?>" required id="cCat"></div>
                        <div class="fg"><label class="fl">Level</label><select name="lv" class="fi2" id="cLv"><?php foreach(['Beginner','Intermediate','Advanced'] as $lv):?><option value="<?=$lv?>" <?=($c['level']??'Beginner')===$lv?'selected':''?>><?=$lv?></option><?php endforeach;?></select></div>
                        <div class="fg"><label class="fl">Price ($)</label><input type="number" name="price" class="fi2" min="0" step="0.01" value="<?=$c['price']??0?>" id="cPrice"></div>
                    </div>
                    <div class="fg"><label class="fl">Thumbnail URL</label><input type="url" name="thumb" class="fi2" placeholder="https://images.unsplash.com/..." value="<?=htmlspecialchars($c['thumbnail']??'')?>" id="cThumb"></div>
                </div>
            </div>
            <div class="card mb2">
                <div style="padding:1.4rem;">
                    <div class="fbet mb2" style="padding-bottom:.7rem;border-bottom:1px solid var(--br);">
                        <h2 style="font-size:.95rem;font-weight:700;">🎬 Lessons</h2>
                        <button type="button" class="btn btn-sec btn-sm" onclick="addLesson()" id="addLessonBtn">+ Add Lesson</button>
                    </div>
                    <div id="lessonsWrap">
                        <?php foreach($llist as $i=>$l):?>
                        <div class="le-row" id="lr<?=$i?>">
                            <input type="hidden" name="lid[]" value="<?=$l['id']?>">
                            <div class="fbet mb1">
                                <span style="font-size:.82rem;font-weight:700;color:var(--pl);">Lesson <?=$i+1?></span>
                                <button type="button" class="btn btn-d btn-sm" onclick="this.closest('.le-row').remove();renum()">✕</button>
                            </div>
                            <div style="display:grid;grid-template-columns:1fr 1fr;gap:.7rem;">
                                <div class="fg"><label class="fl">Title *</label><input type="text" name="ltitle[]" class="fi2" placeholder="Lesson title" value="<?=htmlspecialchars($l['title'])?>" required></div>
                                <div class="fg"><label class="fl">Duration</label><input type="text" name="ldur[]" class="fi2" placeholder="15 mins" value="<?=htmlspecialchars($l['duration'])?>"></div>
                            </div>
                            <div class="fg"><label class="fl">YouTube Embed URL *</label><input type="text" name="lurl[]" class="fi2" placeholder="https://www.youtube.com/embed/VIDEO_ID" value="<?=htmlspecialchars($l['video_url'])?>" required></div>
                            <div class="fg" style="margin-bottom:0;"><label class="fl">Description (optional)</label><input type="text" name="ldesc[]" class="fi2" placeholder="Brief description" value="<?=htmlspecialchars($l['description']??'')?>"></div>
                        </div>
                        <?php endforeach;?>
                    </div>
                    <?php if(empty($llist)):?>
                    <div id="emptyMsg" style="text-align:center;padding:1.8rem;color:var(--t3);border:2px dashed rgba(255,255,255,.08);border-radius:var(--rs);">No lessons yet. Click "+ Add Lesson"</div>
                    <?php endif;?>
                </div>
            </div>
            <div style="display:flex;gap:1rem;justify-content:flex-end;">
                <a href="<?=url('instructor')?>" class="btn btn-sec">Cancel</a>
                <button type="submit" class="btn btn-p btn-lg"><?=$isEdit?'💾 Save Changes':'🚀 Publish Course'?></button>
            </div>
        </form>
    </div>
</div>
<script>
let lc=<?=count($llist)?>;
function addLesson(){
    const em=document.getElementById('emptyMsg');if(em)em.remove();
    const w=document.getElementById('lessonsWrap');
    const d=document.createElement('div');d.className='le-row';d.id='lr'+lc;
    d.innerHTML=`<input type="hidden" name="lid[]" value="0">
    <div class="fbet mb1"><span style="font-size:.82rem;font-weight:700;color:var(--pl);">Lesson ${lc+1}</span>
    <button type="button" class="btn btn-d btn-sm" onclick="this.closest('.le-row').remove();renum()">✕</button></div>
    <div style="display:grid;grid-template-columns:1fr 1fr;gap:.7rem;">
        <div class="fg"><label class="fl">Title *</label><input type="text" name="ltitle[]" class="fi2" placeholder="Lesson title" required></div>
        <div class="fg"><label class="fl">Duration</label><input type="text" name="ldur[]" class="fi2" placeholder="15 mins" value="10 mins"></div>
    </div>
    <div class="fg"><label class="fl">YouTube Embed URL *</label><input type="text" name="lurl[]" class="fi2" placeholder="https://www.youtube.com/embed/VIDEO_ID" required></div>
    <div class="fg" style="margin-bottom:0;"><label class="fl">Description (optional)</label><input type="text" name="ldesc[]" class="fi2" placeholder="Brief description"></div>`;
    w.appendChild(d);lc++;d.scrollIntoView({behavior:'smooth',block:'nearest'});
}
function renum(){document.querySelectorAll('.le-row').forEach((r,i)=>{const s=r.querySelector('span[style*="pl"]');if(s)s.textContent='Lesson '+(i+1);});}
</script>

<?php
// ═══════════════════════════════════════════════════════════════════════
//  PAGE: PROFILE
// ═══════════════════════════════════════════════════════════════════════
elseif ($page === 'profile'):
    if(!loggedIn()){redirect(url('login'));}
    $user=$conn->query("SELECT * FROM users WHERE id=$uid2")->fetch_assoc();
    $ec=$conn->query("SELECT COUNT(*) c FROM enrollments WHERE user_id=$uid2")->fetch_assoc()['c'];
    $cc=$conn->query("SELECT COUNT(*) c FROM courses WHERE instructor_id=$uid2")->fetch_assoc()['c'];
    $days=max(1,round((time()-strtotime($user['created_at']))/86400));
?>
<div class="page pg">
    <div class="wrap" style="max-width:720px;">
        <h1 style="font-size:1.6rem;font-weight:800;margin-bottom:.4rem;">👤 My Profile</h1>
        <p class="t2 mb3">Manage your account settings</p>

        <div class="card mb2">
            <div style="padding:2rem;text-align:center;">
                <?php if($user['profile_pic']):?><img src="<?=htmlspecialchars($user['profile_pic'])?>" style="width:96px;height:96px;border-radius:50%;object-fit:cover;margin:0 auto 1rem;border:3px solid var(--brp);display:block;" alt="Profile">
                <?php else:?><div class="av-big"><?=strtoupper(substr($user['name'],0,2))?></div><?php endif;?>
                <h2 style="font-size:1.25rem;font-weight:700;"><?=htmlspecialchars($user['name'])?></h2>
                <p style="color:var(--pl);font-size:.87rem;margin:.2rem 0;"><?=htmlspecialchars($user['email'])?></p>
                <span class="badge <?=$user['role']==='instructor'?'bw':'bi'?>" style="margin-top:.4rem;"><?=$user['role']==='instructor'?'👩‍🏫 Instructor':'🎓 Student'?></span>
                <div style="display:flex;justify-content:center;gap:2.5rem;margin-top:1.4rem;flex-wrap:wrap;">
                    <div class="tc2"><div style="font-size:1.4rem;font-weight:900;color:var(--pl);"><?=$days?></div><div style="font-size:.73rem;color:var(--t3);text-transform:uppercase;">Days Member</div></div>
                    <div class="tc2"><div style="font-size:1.4rem;font-weight:900;color:var(--a);"><?=$user['role']==='student'?$ec:$cc?></div><div style="font-size:.73rem;color:var(--t3);text-transform:uppercase;"><?=$user['role']==='student'?'Enrolled':'Courses'?></div></div>
                    <div class="tc2"><div style="font-size:1.4rem;font-weight:900;color:var(--s);">⭐4.9</div><div style="font-size:.73rem;color:var(--t3);text-transform:uppercase;">Rating</div></div>
                </div>
            </div>
        </div>

        <div class="card">
            <div style="padding:1.4rem;">
                <h2 style="font-size:.95rem;font-weight:700;margin-bottom:1.1rem;padding-bottom:.7rem;border-bottom:1px solid var(--br);">✏️ Edit Profile</h2>
                <form method="POST" action="<?=url('save_profile')?>" id="profileForm">
                    <input type="hidden" name="page" value="save_profile">
                    <div class="fg"><label class="fl">Full Name *</label><input type="text" name="name" class="fi2" value="<?=htmlspecialchars($user['name'])?>" required id="pName"></div>
                    <div class="fg"><label class="fl">Profile Picture URL</label><input type="url" name="pic" class="fi2" placeholder="https://..." value="<?=htmlspecialchars($user['profile_pic']??'')?>" id="pPic"></div>
                    <div class="fg"><label class="fl">Bio</label><textarea name="bio" class="fi2" rows="3" placeholder="Tell us about yourself..." id="pBio"><?=htmlspecialchars($user['bio']??'')?></textarea></div>
                    <hr class="divider">
                    <h3 style="font-size:.92rem;font-weight:700;margin-bottom:.9rem;">🔒 Change Password <span class="t3" style="font-weight:400;font-size:.8rem;">(leave blank to keep current)</span></h3>
                    <div style="display:grid;grid-template-columns:1fr 1fr;gap:1rem;">
                        <div class="fg"><label class="fl">New Password</label><input type="password" name="np" class="fi2" placeholder="Min 6 chars" id="pNP"></div>
                        <div class="fg"><label class="fl">Confirm New Password</label><input type="password" name="cp" class="fi2" placeholder="Repeat password" id="pCP"></div>
                    </div>
                    <div style="display:flex;gap:1rem;justify-content:flex-end;margin-top:.5rem;">
                        <a href="<?=url('home')?>" class="btn btn-sec">Cancel</a>
                        <button type="submit" class="btn btn-p" id="saveProfile">💾 Save Changes</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<?php else: // 404 / unknown page ?>
<div class="page pg fcenter" style="min-height:calc(100vh - 66px);">
    <div class="tc2">
        <div style="font-size:5rem;margin-bottom:1rem;">🌌</div>
        <h1 style="font-size:2rem;font-weight:800;margin-bottom:.5rem;">Page Not Found</h1>
        <p class="t2 mb3">The page you're looking for doesn't exist.</p>
        <a href="<?=url('home')?>" class="btn btn-p btn-lg">🏠 Go Home</a>
    </div>
</div>
<?php endif; ?>

<!-- ══════════════════════════════ FOOTER ══════════════════════════════ -->
<footer class="footer">
    <div class="wrap">
        <div class="fg2">
            <div>
                <a href="<?=url('home')?>" class="nav-brand" style="display:inline-flex;margin-bottom:.5rem;text-decoration:none;">
                    <div class="nav-logo" style="margin-right:9px;">🎓</div><span>InnovExa</span>
                </a>
                <p class="fbl">The premier all-in-one learning platform. Unlock your potential with world-class courses.</p>
            </div>
            <div><p class="fh">Platform</p><ul class="flinks">
                <li><a href="<?=url('courses')?>">Browse Courses</a></li>
                <li><a href="<?=url('register')?>">Become an Instructor</a></li>
                <li><a href="#">Pricing</a></li>
            </ul></div>
            <div><p class="fh">Account</p><ul class="flinks">
                <?php if(loggedIn()):?>
                <li><a href="<?=url('profile')?>">My Profile</a></li>
                <li><a href="<?=url(role()==='instructor'?'instructor':'dashboard')?>">Dashboard</a></li>
                <li><a href="<?=url('logout')?>">Logout</a></li>
                <?php else:?>
                <li><a href="<?=url('login')?>">Login</a></li>
                <li><a href="<?=url('register')?>">Register</a></li>
                <?php endif;?>
            </ul></div>
        </div>
        <div class="fbot">
            <span>© <?=date('Y')?> InnovExa LMS. All rights reserved.</span>
            <span>Built with ❤️ using PHP & MySQL on XAMPP</span>
        </div>
    </div>
</footer>

<!-- ══════════════════════════════ SCRIPTS ═════════════════════════════ -->
<script>
// Counter animation
document.querySelectorAll('[data-c]').forEach(el => {
    const target = +el.dataset.c, suffix = el.dataset.s||'';
    let cur = 0, step = target / 55;
    const io = new IntersectionObserver(([e]) => {
        if (!e.isIntersecting) return;
        io.unobserve(el);
        const t = setInterval(() => {
            cur = Math.min(cur + step, target);
            el.textContent = Math.floor(cur).toLocaleString() + suffix;
            if (cur >= target) clearInterval(t);
        }, 16);
    }, {threshold: 0.5});
    io.observe(el);
});

// Role toggle (register)
document.querySelectorAll('.role-btn').forEach(btn => {
    btn.addEventListener('click', () => {
        document.querySelectorAll('.role-btn').forEach(b => b.classList.remove('act'));
        btn.classList.add('act');
        const rv = document.getElementById('roleVal');
        if (rv) rv.value = btn.dataset.r;
    });
});

// Flash auto-hide
const fl = document.getElementById('flashMsg');
if (fl) setTimeout(() => { fl.style.transition='opacity .4s,transform .4s'; fl.style.opacity='0'; fl.style.transform='translateY(-8px)'; setTimeout(()=>fl.remove(), 400); }, 3500);
</script>
</body>
</html>
