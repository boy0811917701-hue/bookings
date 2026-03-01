<?php
session_start();
if (!isset($_SESSION['user_id']) || ($_SESSION['role'] ?? '') !== 'admin') {
    header("Location: ../login.php");
    exit();
}

if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

include '../db.php';

// ตรวจสอบว่ามีการส่ง ID มาเพื่อแก้ไขหรือไม่
$edit_data = null;
if (isset($_GET['edit_id'])) {
    $id = intval($_GET['edit_id']);
    // ใช้ Prepared Statement เพื่อความปลอดภัย
    $stmt = $conn->prepare("SELECT * FROM products WHERE product_id = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $edit_data = $stmt->get_result()->fetch_assoc();
}

// ดึงรายการทั้งหมดมาโชว์ในตาราง
$result = $conn->query("SELECT * FROM products ORDER BY created_at DESC");
?>

<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>จัดการสินค้า - Fanier</title>
    <link rel="stylesheet" href="../css/add_product.css">
    <link href="https://fonts.googleapis.com/css2?family=Prompt:wght@300;400;600&display=swap" rel="stylesheet">
    <style>
        /* ส่วนที่เพิ่มเติมเพื่อให้รูปไม่ทับซ้อนและสวยงาม */
        .image-upload-container { text-align: center; background: #0d0d0d; padding: 15px; border-radius: 10px; border: 1px dashed #444; }
        #preview { width: 120px; height: 120px; object-fit: cover; border-radius: 8px; border: 2px solid var(--primary-gold); margin-bottom: 10px; display: block; margin-left: auto; margin-right: auto; }
        .img-label { font-size: 12px; color: var(--primary-gold); margin-top: 5px; display: block; }
    </style>
</head>
<body>
    <?php include '../bar.php'; ?>

    <div class="container">
        <div class="card">
            <h2><?= $edit_data ? '✏️ แก้ไขสินค้า' : '✨ เพิ่มสินค้าใหม่' ?></h2>
            <form id="serviceForm" action="save_product.php" method="POST" enctype="multipart/form-data" class="space-y-4">
                <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
                
                <?php if ($edit_data): ?>
                    <input type="hidden" name="product_id" value="<?= $edit_data['product_id'] ?>">
                    <input type="hidden" name="old_image" value="<?= $edit_data['image'] ?>">
                <?php endif; ?>

                <div class="input-group">
                    <label>รูปภาพสินค้า</label>
                    <div class="image-upload-container">
                        <?php 
                            $img_src = ($edit_data && $edit_data['image']) ? "../uploads/".$edit_data['image'] : "../uploads/no-image.png";
                        ?>
                        <img id="preview" src="<?= $img_src ?>" alt="Preview">
                        <span class="img-label"><?= $edit_data ? 'เปลี่ยนรูปภาพใหม่' : 'เลือกรูปภาพสินค้า' ?></span>
                        <input type="file" name="image" accept="image/*" onchange="previewImage(event)" style="margin-top: 10px;">
                    </div>
                </div>

                <div class="input-group">
                    <label>ชื่อสินค้า/บริการ</label>
                    <input type="text" name="product_name" value="<?= htmlspecialchars($edit_data['product_name'] ?? '') ?>" required>
                </div>

                <div class="input-group">
                    <label>ราคา (บาท)</label>
                    <input type="number" step="0.01" name="price" value="<?= $edit_data['price'] ?? '' ?>" required>
                </div>

                <div class="input-group">
                    <label>หมวดหมู่</label>
                    <input type="text" name="category" value="<?= htmlspecialchars($edit_data['category'] ?? '') ?>" placeholder="เช่น ทำผม, ทรีทเม้นท์">
                </div>

                <div class="input-group">
                    <label>ตำแหน่งการแสดงผล</label>
                    <select name="display_type">
                        <option value="menu" <?= (isset($edit_data) && $edit_data['display_type'] == 'menu') ? 'selected' : '' ?>>เฉพาะหน้าเมนู</option>
                        <option value="recommended" <?= (isset($edit_data) && $edit_data['display_type'] == 'recommended') ? 'selected' : '' ?>>เฉพาะหน้าแนะนำ</option>
                        <option value="both" <?= (isset($edit_data) && $edit_data['display_type'] == 'both') ? 'selected' : '' ?>>แสดงทั้งสองหน้า</option>
                    </select>
                </div>

                <div class="input-group">
                    <label>สถานะ</label>
                    <select name="status">
                        <option value="active" <?= (isset($edit_data) && $edit_data['status'] == 'active') ? 'selected' : '' ?>>แสดง</option>
                        <option value="inactive" <?= (isset($edit_data) && $edit_data['status'] == 'inactive') ? 'selected' : '' ?>>ซ่อน</option>
                    </select>
                </div>

                <button type="submit" name="submit" class="btn-save"><?= $edit_data ? '💾 อัปเดตข้อมูล' : '➕ บันทึกข้อมูล' ?></button>
                <?php if ($edit_data): ?>
                    <a href="add_product.php" class="btn-cancel" style="display: block; text-align: center; margin-top: 10px; color: #aaa; text-decoration: none;">❌ ยกเลิกการแก้ไข</a>
                <?php endif; ?>
            </form>
        </div>

        <div class="card">
            <h2>📋 รายการสินค้าทั้งหมด</h2>
            <div class="table-wrapper">
                <table class="responsive-table">
                    <thead>
                        <tr>
                            <th>รูป</th>
                            <th>ข้อมูล</th>
                            <th>สถานะ</th>
                            <th>จัดการ</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while ($row = $result->fetch_assoc()): ?>
                        <tr>
                            <td data-label="รูป">
                                <img src="../uploads/<?= $row['image'] ?: 'no-image.png' ?>" class="img-table">
                            </td>
                            <td data-label="ข้อมูล">
                                <strong><?= htmlspecialchars($row['product_name']) ?></strong><br>
                                <span class="price-tag">฿<?= number_format($row['price'], 2) ?></span><br>
                                <small style="color:#777;"><?= $row['category'] ?></small>
                            </td>
                            <td data-label="สถานะ">
                                <span class="<?= $row['status'] == 'active' ? 'badge-active' : 'badge-inactive' ?>">
                                    <?= $row['status'] == 'active' ? 'แสดง' : 'ซ่อน' ?>
                                </span>
                            </td>
                            <td data-label="จัดการ" class="action-cell">
                                <a href="add_product.php?edit_id=<?= $row['product_id'] ?>" class="btn-edit">✏️</a>
                                <button onclick="deleteProduct(<?= $row['product_id'] ?>)" class="btn-delete">🗑️</button>
                            </td>
                        </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script>
        // ฟังก์ชัน Preview รูปภาพทันทีเมื่อเลือกไฟล์
        function previewImage(event) {
            var reader = new FileReader();
            reader.onload = function() {
                var output = document.getElementById('preview');
                output.src = reader.result;
            }
            reader.readAsDataURL(event.target.files[0]);
        }

        function deleteProduct(id) {
            Swal.fire({
                title: 'คุณต้องการลบใช่หรือไม่?',
                text: "ลบแล้วไม่สามารถกู้คืนได้!",
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#c62828',
                cancelButtonColor: '#555',
                confirmButtonText: 'ยืนยันการลบ',
                cancelButtonText: 'ยกเลิก',
                background: '#1c1c1c', 
                color: '#fff'
            }).then((result) => {
                if (result.isConfirmed) { 
                    window.location.href = 'delete_product.php?id=' + id; 
                }
            });
        }
    </script>
</body>
</html>