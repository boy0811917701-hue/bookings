<!DOCTYPE html>
<html lang="th">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>MyOrganize</title>
    <!-- LINK css -->
    <link rel="stylesheet" href="../css/bar.css">
    <!-- FONT GOOGLE -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Prompt&display=swap" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Prompt:wght@300;400;600&display=swap" rel="stylesheet">

</head>

<body>

<nav class="navbar">
    <div class="header">
        <div class="nameWeb">
          <img 
    src="../image/5989a8f1-f87e-4174-a5f8-5a62e900f69b.jfif"
    alt="Fanier Beauty Style"
    width="45"
    height="45"
    loading="lazy"
    onerror="this.src='../image/no-logo.png';"
>
            <h1>Fanier Beauty Style</h1>
        </div>

        <!-- ปุ่มเมนูมือถือ -->
        <div class="menu-toggle" onclick="toggleMenu()" aria-label="Toggle menu">☰</div>


        <ul class="menu" id="menu">
    <li><a href="../admin/dashboard.php">หน้าแรก</a></li>
    <li><a href="../admin/index.php">รายชื่อ</a></li>
    <!-- <li><a href="../admin/booking_date.php">ดูการจองตามวัน</a></li> -->
     <li><a href="../admin/dashboard_detail.php">สถิติสินค้า</a></li>
    <li><a href="../admin/add_product.php">เพิ่มสินค้า</a></li>
    <li><a href="../logout.php" class="logout">ออกจากระบบ</a></li>
</ul>

    </div>
</nav>

<script>
function toggleMenu() {
    var menu = document.getElementById("menu");
    if (menu) {
        menu.classList.toggle("show");
    }
}
</script>

</body>
</html>
