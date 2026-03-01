<?php
session_start();
if (!isset($_SESSION['user_id']) || ($_SESSION['role'] ?? '') !== 'admin') {
    header("Location: ../login.php");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !hash_equals($_SESSION['csrf_token'] ?? '', $_POST['csrf_token'] ?? '')) {
    http_response_code(403);
    die('Invalid request');
}

include '../db.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // รับค่าจากฟอร์ม
    $product_id = $_POST['product_id'] ?? null;
    $product_name = $_POST['product_name'];
    $price = $_POST['price'];
    $category = $_POST['category'];
    $display_type = $_POST['display_type'];
    $status = $_POST['status'];
    
    // จัดการรูปภาพพร้อมตรวจสอบความปลอดภัยพื้นฐาน
    $image_name = $_POST['old_image'] ?? '';
    if (!empty($_FILES['image']['name'])) {
        $allowed_ext = ['jpg', 'jpeg', 'png', 'gif', 'jfif', 'webp'];
        $allowed_mime = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
        $max_size = 2 * 1024 * 1024; // 2MB

        $file = $_FILES['image'];
        if ($file['error'] !== UPLOAD_ERR_OK) {
            die("เกิดข้อผิดพลาดในการอัปโหลดไฟล์");
        }

        $file_ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        if (!in_array($file_ext, $allowed_ext, true)) {
            die("นามสกุลไฟล์ไม่ถูกต้อง");
        }

        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mime = finfo_file($finfo, $file['tmp_name']);
        finfo_close($finfo);
        if (!in_array($mime, $allowed_mime, true)) {
            die("ประเภทไฟล์ไม่รองรับ");
        }

        if ($file['size'] > $max_size) {
            die("ไฟล์มีขนาดเกิน 2MB");
        }

        $target_dir = "../uploads/";
        $image_name = 'product_' . uniqid('', true) . '.' . $file_ext;
        $target_path = $target_dir . $image_name;

        if (!move_uploaded_file($file['tmp_name'], $target_path)) {
            die("ไม่สามารถบันทึกไฟล์ได้");
        }

        // ลบไฟล์เก่าเมื่อมีการเปลี่ยนรูป
        if (!empty($_POST['old_image'])) {
            $old_path = $target_dir . $_POST['old_image'];
            if (is_file($old_path)) {@unlink($old_path);} // best-effort delete
        }
    }

    if ($product_id) {
        // --- กรณีแก้ไข (UPDATE) ---
        $sql = "UPDATE products SET 
                product_name = ?, 
                price = ?, 
                category = ?, 
                display_type = ?, 
                status = ?, 
                image = ? 
                WHERE product_id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("sdssssi", $product_name, $price, $category, $display_type, $status, $image_name, $product_id);
    } else {
        // --- กรณีเพิ่มใหม่ (INSERT) ---
        $sql = "INSERT INTO products (product_name, price, category, display_type, status, image) VALUES (?, ?, ?, ?, ?, ?)";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("sdssss", $product_name, $price, $category, $display_type, $status, $image_name);
    }

    if ($stmt->execute()) {
        header("Location: add_product.php?success=1");
    } else {
        echo "เกิดข้อผิดพลาด: " . $conn->error;
    }
}
?>