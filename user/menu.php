<?php
require_once(__DIR__ . "/../db.php");

// 1. แก้ไขชื่อตารางเป็น products
// 2. เพิ่มเงื่อนไข display_type ให้แสดงเฉพาะหน้าเมนู หรือ ทั้งสองหน้า
$sql = "SELECT * FROM products 
        WHERE status='active' 
        AND display_type IN ('menu', 'both') 
        ORDER BY product_id DESC";

$products = $conn->query($sql);
$default_image = "../uploads/default.jpg";
?>

<!DOCTYPE html>
<html lang="th">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>เมนูสินค้า | Fanier Beauty Style</title>

<link rel="stylesheet" href="../css/menu.css">
<link rel="stylesheet" href="../css/bar_menu.css">
<link href="https://fonts.googleapis.com/css2?family=Prompt:wght@300;400;500;600&display=swap" rel="stylesheet">
</head>

<body>

<?php include '../bar_menu.php';?>

<section class="page-header">
    <h2>เมนูสินค้า</h2>
    <p>เลือกชมสินค้าคุณภาพจากร้านของเรา</p>
</section>

<div class="card-grid">

<?php if ($products && $products->num_rows > 0): ?>

    <?php while ($row = $products->fetch_assoc()): 
        // ตรวจสอบรูปภาพ
        $image = !empty($row['image']) ? "../uploads/" . $row['image'] : $default_image;
    ?>

    <a href="booking.php?product_id=<?= $row['product_id'] ?>">
        <div class="card">
            <div class="img-box">
                <img src="<?= htmlspecialchars($image) ?>" 
                     alt="<?= htmlspecialchars($row['product_name']) ?>"
                     onerror="this.src='../uploads/default.jpg';"> </div>

            <div class="card-body">
                <h3><?= htmlspecialchars($row['product_name']) ?></h3>
                <p class="price">฿<?= number_format($row['price']) ?></p>
            </div>
        </div>
    </a>

    <?php endwhile; ?>

<?php else: ?>
    <p class="empty" style="text-align: center; width: 100%; grid-column: 1/-1; padding: 50px; color: #777;">
        ขออภัย ขณะนี้ยังไม่มีสินค้าในระบบ
    </p>

<?php endif; ?>

</div>

</body>
</html>

<?php $conn->close(); ?>