<?php
require_once("db.php");

$message = "";
$msg_type = "";

if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST['register'])) {

    $username = trim($_POST['username']);
    $password = $_POST['password'];
    $phone    = trim($_POST['phone']);

    if ($username === "" || $password === "" || $phone === "") {
        $message = "❌ กรุณากรอกข้อมูลให้ครบ";
        $msg_type = "error";
    } elseif (strlen($password) < 6) {
        $message = "❌ รหัสผ่านต้องอย่างน้อย 6 ตัวอักษร";
        $msg_type = "error";
    } else {

        $check = $conn->prepare("SELECT id FROM users WHERE username = ?");
        $check->bind_param("s", $username);
        $check->execute();
        $check->store_result();

        if ($check->num_rows > 0) {
            $message = "❌ ชื่อผู้ใช้นี้มีคนใช้แล้ว";
            $msg_type = "error";
        } else {
            $hash = password_hash($password, PASSWORD_DEFAULT);

            $stmt = $conn->prepare(
                "INSERT INTO users (username, password, phone, role)
                 VALUES (?, ?, ?, 'user')"
            );
            $stmt->bind_param("sss", $username, $hash, $phone);

            if ($stmt->execute()) {
                header("Location: http://localhost/salon_booking/user/componan.php"); // ✅ ตรงนี้
                exit();
            } else {
                $message = "❌ เกิดข้อผิดพลาด กรุณาลองใหม่";
                $msg_type = "error";
            }
        }
    }
}
?>



<!DOCTYPE html>
<html lang="th">
<head>
<meta charset="UTF-8">
<title>สมัครสมาชิก</title>
<meta name="viewport" content="width=device-width, initial-scale=1.0">

<style>
*{
    box-sizing: border-box;
}

body{
    margin:0;
    min-height:100vh;
    display:flex;
    justify-content:center;
    align-items:center;
    font-family: "Segoe UI", Tahoma, sans-serif;
    background-color: #f5f3ef;
}

.box{
    width:100%;
    max-width:380px;
    background:#fff;
    padding:30px 25px;
    border-radius:16px;
    box-shadow:0 15px 40px rgba(0,0,0,.25);
    animation: fadeIn .6s ease;
}

@keyframes fadeIn{
    from{opacity:0; transform:translateY(20px);}
    to{opacity:1; transform:translateY(0);}
}

h2{
    text-align:center;
    margin-bottom:20px;
    color:#CD853F;
}

input{
    width:100%;
    padding:14px;
    margin:10px 0;
    border-radius:10px;
    border:1px solid #ddd;
    font-size:15px;
    transition:.3s;
}

input:focus{
    outline:none;
    border-color:#F4A460;
    box-shadow:0 0 0 3px rgba(244,164,96,.25);
}

button{
    width:100%;
    padding:14px;
    margin-top:10px;
    background: linear-gradient(135deg, #F4A460, #CD853F);
    color:#fff;
    border:none;
    border-radius:10px;
    font-size:16px;
    font-weight:bold;
    cursor:pointer;
    transition:.3s;
}

button:hover{
    transform: translateY(-2px);
    box-shadow:0 8px 20px rgba(0,0,0,.25);
}

.msg{
    text-align:center;
    margin-bottom:15px;
    padding:12px;
    border-radius:10px;
    font-size:14px;
}

.msg.error{
    background:#ffe5e5;
    color:#b00020;
}

.msg.success{
    background:#e7fbe7;
    color:#2e7d32;
}

.link{
    text-align:center;
    margin-top:16px;
    font-size:14px;
}

.link a{
    color:#CD853F;
    text-decoration:none;
    font-weight:bold;
}

.link a:hover{
    text-decoration:underline;
}
</style>
</head>

<body>

<div class="box">
    <h2>✨ สมัครสมาชิก ✨</h2>

    <?php if($message!=""){ ?>
        <div class="msg <?= $msg_type ?>">
            <?= htmlspecialchars($message) ?>
        </div>
    <?php } ?>

    <form method="post">
        <input type="text" name="username" placeholder="ชื่อผู้ใช้" required>
        <input type="password" name="password" placeholder="รหัสผ่าน (อย่างน้อย 6 ตัว)" required>
        <input type="text" name="phone" placeholder="เบอร์โทรศัพท์" required>

        <button type="submit" name="register">สมัครสมาชิก</button>
    </form>

    <div class="link">
        มีบัญชีแล้ว? <a href="login.php">เข้าสู่ระบบ</a>
    </div>
</div>

</body>
</html>
