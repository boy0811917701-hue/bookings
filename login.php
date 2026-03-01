<?php
session_start();
require_once(__DIR__ . "/db.php");

$message = "";

/* ======================
    CHECK LOGIN SESSION
====================== */
if (isset($_SESSION['user_id']) && isset($_SESSION['role'])) {
    if ($_SESSION['role'] === 'admin') {
        header("Location: admin/dashboard.php");
    } else {
        header("Location: user/recommended.php");
    }
    exit;
}

if (isset($_POST['login'])) {
    $username = trim($_POST['username']);
    $password = $_POST['password'];

    // 1. เพิ่ม 'phone' เข้าไปในคำสั่ง SELECT เพื่อนำมาใช้ในหน้า My Bookings
    $stmt = $conn->prepare("SELECT id, username, password, role, phone FROM users WHERE username = ?");
    $stmt->bind_param("s", $username);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows === 1) {
        $user = $result->fetch_assoc();

        if (password_verify($password, $user['password'])) {
            // 2. เก็บข้อมูลลงใน Session ให้ครบถ้วน
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['username'] = $user['username'];
            $_SESSION['role'] = $user['role'];
            $_SESSION['phone'] = $user['phone']; // <--- จุดสำคัญที่ต้องมี

            if ($user['role'] === 'admin') {
                header("Location: admin/dashboard.php");
            } else {
                // แนะนำให้ใช้ path สั้นๆ เพื่อความปลอดภัยและความง่ายในการย้าย Server
                header("Location: user/recommended.php");
            }
            exit;
        } else {
            $message = "❌ รหัสผ่านไม่ถูกต้อง";
        }
    } else {
        $message = "❌ ไม่พบบัญชีผู้ใช้";
    }
}
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <title>เข้าสู่ระบบ</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Prompt&display=swap" rel="stylesheet">
    <style>
        /* CSS เดิมของคุณ... */
        * { box-sizing: border-box; font-family: 'Prompt', sans-serif; }
        body {
            min-height: 100vh; margin: 0; display: flex; align-items: center; justify-content: center;
            background-image: url(image/backgroud_home.jpg); background-size: cover; background-position: center;
            box-shadow: inset 0 0 0 2000px rgba(0, 0, 0, 0.8); backdrop-filter: blur(5px);
        }
        .box {
            width: 340px; background: rgba(20, 20, 20, 0.85); padding: 35px; border-radius: 15px;
            border: 1px solid rgba(255, 215, 0, 0.3); box-shadow: 0 15px 35px rgba(0, 0, 0, .5);
        }
        .box h2 { text-align: center; margin-bottom: 30px; color: #D4AF37; }
        input {
            width: 100%; padding: 12px; margin-bottom: 20px; border-radius: 8px; border: 1px solid #444;
            background: rgba(255, 255, 255, 0.05); color: #fff; font-size: 14px; transition: .3s;
        }
        input:focus { outline: none; border-color: #D4AF37; box-shadow: 0 0 0 2px rgba(255, 215, 0, .2); }
        button {
            width: 100%; padding: 12px; margin-top: 10px; background-color: #000; color: #D4AF37;
            border: 2px solid #D4AF37; border-radius: 8px; font-size: 15px; font-weight: bold; cursor: pointer; transition: .3s;
        }
        button:hover { background-color: #D4AF37; color: #000; }
        .msg {
            background: rgba(255, 0, 0, 0.2); color: #ff9999; padding: 10px; border-radius: 8px;
            text-align: center; margin-bottom: 20px; font-size: 14px; border: 1px solid rgba(255, 0, 0, 0.3);
        }
        .noacc { color: #fff; text-align: center; margin-top: 20px; }
        .noacc a { color: #D4AF37; }
    </style>
</head>
<body>
    <div class="box">
        <h2>เข้าสู่ระบบ</h2>
        <?php if ($message != "") { ?>
            <div class="msg"><?= $message ?></div>
        <?php } ?>
        <form method="post">
            <input type="text" name="username" placeholder="ชื่อผู้ใช้" required>
            <input type="password" name="password" placeholder="รหัสผ่าน" required>
            <button type="submit" name="login">เข้าสู่ระบบ</button>
        </form>
        <div class="noacc">ยังไม่มีบัญชี? <a href="register.php">สมัครสมาชิก</a></div>
    </div>
</body>
</html>