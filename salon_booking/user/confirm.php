<?php
include '../db.php'; // 1. เชื่อมต่อฐานข้อมูลก่อน

// 2. วางไว้ตรงนี้ครับ เพื่อรับค่า 'code' จาก URL (เช่น confirm.php?code=ABC123)
$booking_code = isset($_GET['code']) ? $_GET['code'] : ''; 

$booking = null;

if (!empty($booking_code)) {
    // 3. นำตัวแปร $booking_code ไปใช้ใน Query
    $sql = "SELECT b.*, s.service_name, s.price, s.duration_minutes 
            FROM bookings b
            JOIN services s ON b.service_id = s.service_id
            WHERE b.booking_code = ?";
    
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $booking_code);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        $booking = $result->fetch_assoc();
    }
    $stmt->close();
}
?>

<!DOCTYPE html>
<html lang="th">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ยืนยันการจอง</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>

<body class="bg-gray-100 min-h-screen flex items-center justify-center p-4">
    <div class="bg-white p-8 rounded-lg shadow-xl w-full max-w-md">
        <?php if ($booking): ?>
        <svg class="mx-auto h-16 w-16 text-green-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
        </svg>
        <h1 class="text-2xl font-bold text-center text-green-600 mt-4 mb-2">จองคิวสำเร็จ!</h1>
        <p class="text-center text-gray-600 mb-6">ขอบคุณที่ใช้บริการ Fanier Beauty Style</p>

        <div class="border border-gray-200 p-4 rounded-lg space-y-3">
            <p><strong>รหัสการจอง:</strong> <span
                    class="text-pink-600 font-bold text-lg"><?= $booking['booking_code'] ?></span></p>
            <p><strong>ชื่อลูกค้า:</strong> <?= $booking['customer_name'] ?></p>
            <p><strong>เบอร์โทร:</strong> <?= $booking['customer_phone'] ?></p>
            <hr>
            <p><strong>บริการ:</strong> <?= $booking['service_name'] ?></p>
            <p><strong>ราคา:</strong> ฿<?= number_format($booking['price'], 2) ?></p>
            <p><strong>ระยะเวลา:</strong> <?= $booking['duration_minutes'] ?> นาที</p>
            <hr>
            <p><strong>วันที่:</strong> <?= date('d/m/Y', strtotime($booking['booking_date'])) ?></p>
            <p><strong>เวลา:</strong> <?= date('H:i', strtotime($booking['booking_time'])) ?> น.</p>
            <p><strong>สถานะ:</strong> <span class="text-blue-500 font-medium"><?= ucfirst($booking['status']) ?></span>
            </p>
        </div>

        <a href="Home.php"
            class="mt-6 w-full block text-center py-2 px-4 rounded-md text-sm font-medium text-white bg-pink-600 hover:bg-pink-700 transition duration-150">
            กลับไปหน้าหลัก
        </a>

        <?php else: ?>
        <h1 class="text-2xl font-bold text-center text-red-600 mt-4 mb-2">ไม่พบข้อมูลการจอง</h1>
        <p class="text-center text-gray-600 mb-6">กรุณาตรวจสอบรหัสการจองอีกครั้ง</p>
        <a href="Home.php"
            class="mt-6 w-full block text-center py-2 px-4 rounded-md text-sm font-medium text-white bg-blue-600 hover:bg-blue-700 transition duration-150">
            กลับไปหน้าจอง
        </a>
        <?php endif; ?>

    </div>
</body>

</html>

<?php $conn->close(); ?>