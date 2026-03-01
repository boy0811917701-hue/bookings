<!DOCTYPE html>
<html lang="th">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="../css/bar.css">
    <title>MyOrganize</title>
</head>

<body>

<nav class="navbar">
    <div class="header">
        <div class="nameWeb">
            <img src="../image/5989a8f1-f87e-4174-a5f8-5a62e900f69b.jfif" alt="" width="45">
            <h1>Fanier Beauty Style</h1>
        </div>

        <!-- ปุ่มเมนูมือถือ -->
        <div class="menu-toggle" onclick="toggleMenu()">☰</div>

        <ul class="menu" id="menu">
            <li><a href="../admin/dashboard.php">หน้าแรก</a></li>
            <li><a href="../admin/index.php">รายชื่อ</a></li>
            <li><a href="../user/Home.php" class="logout">ออกจากระบบ</a></li>
        </ul>
    </div>
</nav>

<script>
function toggleMenu() {
    document.getElementById("menu").classList.toggle("show");
}
</script>

</body>
</html>
