<?php
require_once '../db.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $booking_code = $_POST['booking_code'];
    $status = $_POST['status'];

    $sql = "UPDATE bookings SET status = ? WHERE booking_code = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ss", $status, $booking_code);

    if ($stmt->execute()) {
        header("Location: index.php?success=1");
        exit;
    } else {
        echo "เกิดข้อผิดพลาด";
    }
}
