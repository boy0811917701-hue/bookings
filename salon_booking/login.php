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
        header("Location: user/componan.php");
    }
    exit;
}
if (isset($_POST['login'])) {
    $username = trim($_POST['username']);
    $password = $_POST['password'];

    $stmt = $conn->prepare("SELECT id, username, password, role FROM users WHERE username = ?");
    $stmt->bind_param("s", $username);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows === 1) {
        $user = $result->fetch_assoc();

        if (password_verify($password, $user['password'])) {
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['username'] = $user['username'];
            $_SESSION['role'] = $user['role'];

            if ($user['role'] === 'admin') {
                header("Location: admin/dashboard.php");
            } else {
                header("Location: user/componan.php");
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

<style>
*{
    box-sizing:border-box;
    font-family: 'Segoe UI', Tahoma, sans-serif;
}

body{
    min-height:100vh;
    margin:0;
    display:flex;
    align-items:center;
    justify-content:center;
    background-color: #f5f3ef;
}

.box{
    width:340px;
    background:#ffffff;
    padding:30px;
    border-radius:15px;
    box-shadow:0 15px 35px rgba(0,0,0,.2);
}

.box h2{
    text-align:center;
    margin-bottom:20px;
    color:#F4A460;
}

input{
    width:100%;
    padding:12px;
    margin-bottom:12px;
    border-radius:8px;
    border:1px solid #ccc;
    font-size:14px;
    transition:.3s;
}

input:focus{
    outline:none;
    border-color:#7b2cbf;
    box-shadow:0 0 0 2px rgba(123,44,191,.2);
}

button{
    width:100%;
    padding:12px;
    background-color:#F4A460;
    color:#fff;
    border:none;
    border-radius:8px;
    font-size:15px;
    cursor:pointer;
    transition:.3s;
}

button:hover{
    opacity:.9;
    transform:translateY(-1px);
}

.msg{
    background:#ffe5e5;
    color:#b00020;
    padding:10px;
    border-radius:8px;
    text-align:center;
    margin-bottom:15px;
    font-size:14px;
}

a{
    display:block;
    text-align:center;
    margin-top:15px;
    color:#5a189a;
    text-decoration:none;
    font-size:14px;
    color: #FF33CC;
}

a:hover{
    text-decoration:underline;
}
</style>
</head>

<body>

<div class="box">
    <h2>เข้าสู่ระบบ</h2>

    <?php if($message!=""){ ?>
        <div class="msg"><?= $message ?></div>
    <?php } ?>

    <form method="post">
        <input type="text" name="username" placeholder="ชื่อผู้ใช้" required>
        <input type="password" name="password" placeholder="รหัสผ่าน" required>
        <button type="submit" name="login">เข้าสู่ระบบ</button>
    </form>

    <a href="register.php">สมัครสมาชิก</a>
</div>

</body>
</html>
