<?php
session_start();

if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

require_once("db.php");

$message = "";
$msg_type = "";

if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST['register'])) {

    if (!hash_equals($_SESSION['csrf_token'] ?? '', $_POST['csrf_token'] ?? '')) {
        $message = "❌ คำขอไม่ถูกต้อง";
        $msg_type = "error";
    } else {
        $username = trim($_POST['username']);
        $password = $_POST['password'];
        $phone = trim($_POST['phone']);

        // 1. เช็คค่าว่าง
        if ($username === "" || $password === "" || $phone === "") {
            $message = "❌ กรุณากรอกข้อมูลให้ครบ";
            $msg_type = "error";
        }
        // 2. เช็คความยาวรหัสผ่าน
        elseif (strlen($password) < 6) {
            $message = "❌ รหัสผ่านต้องอย่างน้อย 6 ตัวอักษร";
            $msg_type = "error";
        }
        // 3. เช็คว่าเบอร์โทรเป็นตัวเลข 10 หลักหรือไม่
        elseif (!preg_match('/^[0-9]{10}$/', $phone)) {
            $message = "❌ เบอร์โทรศัพท์ต้องเป็นตัวเลข 10 หลัก";
            $msg_type = "error";
        }
        else {
            // ตรวจสอบ Username ซ้ำ
            $check_user = $conn->prepare("SELECT id FROM users WHERE username = ?");
            $check_user->bind_param("s", $username);
            $check_user->execute();
            $check_user->store_result();

            // ตรวจสอบ Phone ซ้ำ
            $check_phone = $conn->prepare("SELECT id FROM users WHERE phone = ?");
            $check_phone->bind_param("s", $phone);
            $check_phone->execute();
            $check_phone->store_result();

            if ($check_user->num_rows > 0) {
                $message = "❌ ชื่อผู้ใช้นี้มีคนใช้แล้ว";
                $msg_type = "error";
            } elseif ($check_phone->num_rows > 0) {
                $message = "❌ เบอร์โทรศัพท์นี้ถูกใช้งานไปแล้ว";
                $msg_type = "error";
            } else {
                // ผ่านทุกขั้นตอน -> ทำการ Hash รหัสผ่านและบันทึก
                $hash = password_hash($password, PASSWORD_DEFAULT);
                $stmt = $conn->prepare("INSERT INTO users (username, password, phone, role) VALUES (?, ?, ?, 'user')");
                $stmt->bind_param("sss", $username, $hash, $phone);

                if ($stmt->execute()) {
                    // สมัครสำเร็จ ส่งไปหน้า login
                    header("Location: login.php?success=registered");
                    exit();
                } else {
                    $message = "❌ เกิดข้อผิดพลาดในระบบฐานข้อมูล";
                    $msg_type = "error";
                }
                $stmt->close();
            }
            $check_user->close();
            $check_phone->close();
        }
    }
}
?>

<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <title>สมัครสมาชิก - Fanier Beauty Style</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href="https://fonts.googleapis.com/css2?family=Prompt&display=swap" rel="stylesheet">
    <style>
        /* CSS ของคุณเดิม (สวยอยู่แล้วครับ ไม่ต้องเปลี่ยน) */
        * { box-sizing: border-box; margin: 0; padding: 0; font-family: 'Prompt', sans-serif; }
        body { 
            margin: 0; min-height: 100vh; display: flex; justify-content: center; align-items: center; 
            background: #0a0a0a url(image/backgroud_home.jpg) center/cover fixed;
            box-shadow: inset 0 0 0 2000px rgba(0, 0, 0, 0.8);
            backdrop-filter: blur(5px);
        }
        .box { 
            width: 100%; max-width: 380px; background: rgba(20, 20, 20, 0.9); padding: 40px 30px; 
            border-radius: 20px; border: 1px solid rgba(212, 175, 55, 0.3); color: #fff;
        }
        h2 { text-align: center; margin-bottom: 30px; color: #D4AF37; letter-spacing: 2px; }
        input { 
            width: 100%; padding: 14px; margin: 12px 0; border-radius: 12px; border: 1px solid #333;
            background: rgba(255, 255, 255, 0.05); color: #fff; font-size: 15px;
        }
        input:focus { outline: none; border-color: #D4AF37; background: rgba(255, 255, 255, 0.1); }
        button { 
            width: 100%; padding: 14px; margin-top: 20px; border-radius: 12px; border: none;
            background: linear-gradient(135deg, #D4AF37, #B8860B); color: #000; font-weight: bold; cursor: pointer;
        }
        .msg { text-align: center; margin-bottom: 15px; padding: 12px; border-radius: 10px; font-size: 14px; }
        .msg.error { background: rgba(255, 0, 0, 0.1); color: #ff9999; border: 1px solid #ff0000; }
        .link { text-align: center; margin-top: 20px; font-size: 14px; color: #bbb; }
        .link a { color: #D4AF37; text-decoration: none; font-weight: bold; }
    </style>
</head>
<body>
    <div class="box">
        <h2>REGISTER</h2>

        <?php if ($message != ""): ?>
            <div class="msg <?= $msg_type ?>">
                <?= htmlspecialchars($message) ?>
            </div>
        <?php endif; ?>

        <form method="post">
            <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
            <input type="text" name="username" placeholder="ชื่อผู้ใช้" required>
            <input type="password" name="password" placeholder="รหัสผ่าน (6 ตัวขึ้นไป)" required>
            <input type="tel" name="phone" placeholder="เบอร์โทรศัพท์ (10 หลัก)" 
                   pattern="[0-9]{10}" maxlength="10" required>

            <button type="submit" name="register">สร้างบัญชีผู้ใช้</button>
        </form>

        <div class="link">
            มีบัญชีแล้ว? <a href="login.php">เข้าสู่ระบบ</a>
        </div>
    </div>
</body>
</html>