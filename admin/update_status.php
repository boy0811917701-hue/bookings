<?php
session_start();
header('Content-Type: application/json');

if (!isset($_SESSION['user_id']) || ($_SESSION['role'] ?? '') !== 'admin') {
    http_response_code(403);
    echo json_encode(['status' => 'error', 'message' => 'forbidden']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['booking_code'], $_POST['status'], $_POST['csrf_token'])) {
    if (!hash_equals($_SESSION['csrf_token'] ?? '', $_POST['csrf_token'])) {
        http_response_code(403);
        echo json_encode(['status' => 'error', 'message' => 'CSRF failed']);
        exit;
    }

    include '../db.php';

    $booking_code = $_POST['booking_code'];
    $new_status = $_POST['status'];

    // 1. ตรวจสอบความถูกต้องของสถานะ
    $allowed_statuses = ['pending', 'confirmed', 'cancelled', 'completed'];
    if (!in_array($new_status, $allowed_statuses)) {
        echo json_encode(['status' => 'error', 'message' => 'สถานะไม่ถูกต้อง']);
        exit;
    }

    // 2. อัปเดตสถานะ
    $sql = "UPDATE bookings SET status = ? WHERE booking_code = ?";
    $stmt = $conn->prepare($sql);
    
    if ($stmt) {
        $stmt->bind_param("ss", $new_status, $booking_code);
        $stmt->execute();

        if ($stmt->affected_rows > 0) {
            // สำเร็จ! ส่ง JSON กลับไปให้ JavaScript จัดการต่อ (เช่น ลบแถวหรือเปลี่ยนสี)
            echo json_encode([
                'status' => 'success', 
                'message' => 'อัปเดตเรียบร้อย',
                'new_status' => $new_status
            ]);
        } else {
            echo json_encode(['status' => 'warning', 'message' => 'ไม่มีการเปลี่ยนแปลงข้อมูล']);
        }
        $stmt->close();
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Database Error']);
    }
} else {
    echo json_encode(['status' => 'error', 'message' => 'Invalid Request']);
}

$conn->close();
exit;