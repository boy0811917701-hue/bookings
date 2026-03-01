<?php
require_once("../db.php");

if (!isset($_POST['booking_code'])) {
    die("ไม่พบรหัสจอง");
}

$booking_code = $_POST['booking_code'];

$stmt = $conn->prepare("
    UPDATE bookings 
    SET status = 'completed'
    WHERE booking_code = ?
");

$stmt->bind_param("s", $booking_code);

if ($stmt->execute()) {
    header("Location: status.php");
    exit;
} else {
    echo "Error: " . $stmt->error;
}
