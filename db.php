<?php
// กำหนดตัวแปรการเชื่อมต่อ
$host = "localhost";
$user = "root"; // เปลี่ยนตาม Username ฐานข้อมูลของคุณ
$password = ""; // เปลี่ยนตาม Password ฐานข้อมูลของคุณ
$dbname = "hair_salon"; // ชื่อฐานข้อมูลจากไฟล์ SQL

// สร้างการเชื่อมต่อ
$conn = new mysqli($host, $user, $password, $dbname);

// ตรวจสอบการเชื่อมต่อ
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// ตั้งค่า charset เป็น utf8mb4 เพื่อรองรับภาษาไทย
$conn->set_charset("utf8mb4");

// ฟังก์ชันสำหรับสร้างรหัสการจองที่ไม่ซ้ำกัน
function generateBookingCode($conn) {
    // 1. ลองนับจำนวนแถวแทน MAX(id) เพื่อความปลอดภัยในกรณีตารางเพิ่งสร้าง
    $sql = "SELECT COUNT(*) AS total FROM bookings";
    $result = $conn->query($sql);

    

    $row = $result->fetch_assoc();
    
    // 2. นำจำนวนแถวทั้งหมดมา + 1 
    $next_number = $row['total'] + 1;
    
    return "คิวที่-" . $next_number;
}
?>