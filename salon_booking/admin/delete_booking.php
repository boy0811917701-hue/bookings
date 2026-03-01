<?php
include '../db.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && !empty($_POST['booking_code'])) {

    $booking_code = $_POST['booking_code'];

    // ดึงสถานะเดิม
    $stmt = $conn->prepare("SELECT status FROM bookings WHERE booking_code = ?");
    $stmt->bind_param("s", $booking_code);
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result->fetch_assoc();

    if ($row && $row['status'] === 'confirmed') {

        // บันทึก log
        $log = $conn->prepare("
            INSERT INTO booking_logs 
            (booking_code, action, old_status, new_status, action_by)
            VALUES (?, 'DELETE', 'confirmed', 'deleted', 'system')
        ");
        $log->bind_param("s", $booking_code);
        $log->execute();

        // ลบข้อมูลจริง
        $del = $conn->prepare("
            DELETE FROM bookings 
            WHERE booking_code = ? AND status = 'confirmed'
        ");
        $del->bind_param("s", $booking_code);
        $del->execute();

        echo "deleted";
        exit;
    }
}

http_response_code(400);
echo "error";
