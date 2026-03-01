<!-- <?php
include 'db.php'; // ไฟล์ที่คุณส่งมา

$name = $_POST['name'];
$number_phone = $_POST['number_phone'];

// ใช้ prepared statement (ปลอดภัย)
$sql = "INSERT INTO users (name, number_phone) VALUES (?, ?)";
$stmt = $conn->prepare($sql);
$stmt->bind_param("ss", $name, $number_phone);

if ($stmt->execute()) {
    echo "สมัครสมาชิกสำเร็จ ✅";
} else {
    echo "เกิดข้อผิดพลาด ❌";
}

$stmt->close();
$conn->close();
?>
