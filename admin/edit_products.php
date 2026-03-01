<?php
include '../db.php';

// 1. ตรวจสอบ ID และป้องกัน SQL Injection เบื้องต้นด้วย intval
if (!isset($_GET['id']) || empty($_GET['id'])) {
    header("Location: add_product.php");
    exit;
}

$id = intval($_GET['id']);

// 2. ใช้ Prepared Statement เพื่อความปลอดภัย
$stmt = $conn->prepare("SELECT * FROM products WHERE product_id = ?");
$stmt->bind_param("i", $id);
$stmt->execute();
$result = $stmt->get_result();
$product = $result->fetch_assoc();

if (!$product) {
    echo "<script>alert('ไม่พบข้อมูลสินค้า'); window.location='add_product.php';</script>";
    exit;
}
?>

<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>แก้ไขสินค้า - Fanier Beauty Style</title>
    <link rel="stylesheet" href="../css/add_product.css">
    <link href="https://fonts.googleapis.com/css2?family=Prompt:wght@300;400;600&display=swap" rel="stylesheet">
    <style>
        :root { --primary-gold: #D4AF37; --dark-bg: #121212; --card-bg: #1c1c1c; }
        body { font-family: 'Prompt', sans-serif; background-color: var(--dark-bg); color: white; padding: 20px; }
        .edit-card { max-width: 600px; margin: 40px auto; background: var(--card-bg); padding: 30px; border-radius: 16px; border: 1px solid #333; box-shadow: 0 10px 30px rgba(0,0,0,0.5); }
        h2 { color: var(--primary-gold); text-align: center; margin-bottom: 25px; }
        label { display: block; margin-top: 15px; color: #aaa; font-size: 0.9rem; }
        input, select { width: 100%; padding: 12px; margin-top: 5px; border-radius: 8px; border: 1px solid #333; background: #0d0d0d; color: white; box-sizing: border-box; outline: none; }
        input:focus { border-color: var(--primary-gold); }
        .preview-img { width: 150px; height: 150px; object-fit: cover; border-radius: 10px; margin: 15px auto; display: block; border: 2px solid var(--primary-gold); background: #222; }
        .btn-group { display: grid; grid-template-columns: 1fr 1fr; gap: 10px; margin-top: 25px; }
        .btn-update { background: var(--primary-gold); color: black; border: none; padding: 14px; border-radius: 8px; font-weight: 600; cursor: pointer; transition: 0.3s; }
        .btn-update:hover { background: #b8962d; transform: translateY(-2px); }
        .btn-back { background: #444; color: white; text-decoration: none; padding: 14px; border-radius: 8px; text-align: center; font-size: 0.9rem; transition: 0.3s; }
        .btn-back:hover { background: #555; }
    </style>
</head>
<body>

    <div class="edit-card">
        <h2>✏️ แก้ไขข้อมูลสินค้า</h2>
        
        <form action="save_product.php" method="POST" enctype="multipart/form-data">
            <input type="hidden" name="product_id" value="<?= $product['product_id'] ?>">
            <input type="hidden" name="old_image" value="<?= $product['image'] ?>">

            <center>
                <?php 
                    $img_path = "../uploads/" . $product['image'];
                    $display_img = (!empty($product['image']) && file_exists($img_path)) ? $img_path : "../uploads/no-image.png";
                ?>
                <img src="<?= $display_img ?>" class="preview-img" id="output">
                <small style="color: #888;">รูปภาพตัวอย่าง</small>
            </center>

            <label>ชื่อสินค้า / บริการ</label>
            <input type="text" name="product_name" value="<?= htmlspecialchars($product['product_name']) ?>" required>

            <label>ราคา (บาท)</label>
            <input type="number" step="0.01" name="price" value="<?= $product['price'] ?>" required>

            <label>หมวดหมู่</label>
            <input type="text" name="category" value="<?= htmlspecialchars($product['category'] ?? '') ?>">

            <label>ตำแหน่งที่ต้องการแสดง</label>
            <select name="display_type">
                <option value="menu" <?= $product['display_type'] == 'menu' ? 'selected' : '' ?>>หน้าเมนูปกติ</option>
                <option value="recommended" <?= $product['display_type'] == 'recommended' ? 'selected' : '' ?>>หน้าแนะนำ (Recommended)</option>
                <option value="both" <?= $product['display_type'] == 'both' ? 'selected' : '' ?>>แสดงทั้งสองหน้า</option>
            </select>

            <label>สถานะ</label>
            <select name="status">
                <option value="active" <?= $product['status'] == 'active' ? 'selected' : '' ?>>แสดง (Active)</option>
                <option value="inactive" <?= $product['status'] == 'inactive' ? 'selected' : '' ?>>ซ่อน (Inactive)</option>
            </select>

            <label>เปลี่ยนรูปภาพ (ปล่อยว่างไว้หากไม่ต้องการเปลี่ยน)</label>
            <input type="file" name="image" accept="image/*" onchange="loadFile(event)">

            <div class="btn-group">
                <a href="add_product.php" class="btn-back">🔙 ย้อนกลับ</a>
                <button type="submit" name="submit" class="btn-update">💾 บันทึกการแก้ไข</button>
            </div>
        </form>
    </div>

    <script>
        var loadFile = function(event) {
            var output = document.getElementById('output');
            if (event.target.files[0]) {
                output.src = URL.createObjectURL(event.target.files[0]);
                output.onload = function() {
                    URL.revokeObjectURL(output.src) 
                }
            }
        };
    </script>
</body>
</html>