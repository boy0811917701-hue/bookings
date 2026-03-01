<?php
session_start();
if (!isset($_SESSION['user_id']) || ($_SESSION['role'] ?? '') !== 'admin') {
    header("Location: ../login.php");
    exit();
}

include '../db.php';

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if ($id <= 0) {
    header("Location: add_product.php");
    exit();
}

// ดึงชื่อไฟล์ภาพเดิมด้วย prepared statement
$res = $conn->prepare("SELECT image FROM products WHERE product_id = ?");
$res->bind_param("i", $id);
$res->execute();
$data = $res->get_result()->fetch_assoc();
$res->close();

if ($data && !empty($data['image'])) {
    $old = "../uploads/" . $data['image'];
    if (is_file($old)) { @unlink($old); }
}

$del = $conn->prepare("DELETE FROM products WHERE product_id = ?");
$del->bind_param("i", $id);
$del->execute();
$del->close();

header("Location: add_product.php");
exit();
?>