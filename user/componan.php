<?php
require_once(__DIR__ . "/../db.php");

$services = $conn->query("SELECT * FROM services ORDER BY service_id");

$images = [
    1 => "../image/a301ce7e-4d4e-45a6-8682-7d7d7bb18c38 (1).jfif",
    2 => "../image/eacc912c-c340-4e82-9c0f-507f8a12128d.jfif",
    3 => "../image/b03867c6-d9e2-43f5-9562-600c96e611ae.jfif"
];

$default_image = "../image/default.jpg"; // รูปสำรอง
?>

<!DOCTYPE html>
<html lang="th">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>เลือกบริการ | Fanier Beauty Style</title>
<link rel="stylesheet" href="../css/componan.css">
</head>

<body>

<header class="header">
<div class="header-content">

<div class="logo">
<img src="../image/5989a8f1-f87e-4174-a5f8-5a62e900f69b.jfif" width="50">
<h1>Fanier Beauty Style</h1>
</div>

<nav class="menu">
<a href="componan.php"><button>หน้าแรก</button></a>
<a href="queue_check.php"><button>ตรวจสอบคิว</button></a>
<a href="index.php"><button>ข้อมูลการจอง</button></a>
<a href="../logout.php">ออกจากระบบ</a>
</nav>

</div>
</header>

<main class="container">

<?php if($services && $services->num_rows > 0): ?>
<?php while($row = $services->fetch_assoc()): 

$image = $images[$row['service_id']] ?? $default_image;
?>

<div class="card">
<a href="index.php?service_id=<?= $row['service_id'] ?>" class="card-link">


<img src="<?= $images[$row['service_id']] ?>" class="service-img">


<p><?= htmlspecialchars($row['service_name']) ?></p>
<p>฿<?= number_format($row['price']) ?></p>

</a>
</div>

<?php endwhile; ?>
<?php else: ?>

<p style="text-align:center;">ยังไม่มีบริการในระบบ</p>

<?php endif; ?>

</main>

<footer>
<div class="footer-content">
<p>Email</p>
<p>เบอร์โทร</p>
</div>
</footer>

</body>
</html>

<?php $conn->close(); ?>
