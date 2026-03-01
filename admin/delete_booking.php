<?php
session_start();
header('Content-Type: application/json');

if (!isset($_SESSION['user_id']) || ($_SESSION['role'] ?? '') !== 'admin') {
    http_response_code(403);
    echo json_encode(['status' => 'error', 'message' => 'forbidden']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['booking_code'], $_POST['csrf_token'])) {
    if (!hash_equals($_SESSION['csrf_token'] ?? '', $_POST['csrf_token'])) {
        http_response_code(403);
        echo json_encode(['status' => 'error', 'message' => 'CSRF failed']);
        exit;
    }

    require_once("../db.php");

    $booking_code = $_POST['booking_code'];

    $stmt = $conn->prepare("DELETE FROM bookings WHERE booking_code = ?");
    $stmt->bind_param("s", $booking_code);
    
    if ($stmt->execute()) {
        echo json_encode(['status' => 'success', 'message' => 'Deleted successfully']);
    } else {
        http_response_code(500);
        echo json_encode(['status' => 'error', 'message' => 'Could not delete']);
    }
    $stmt->close();
    $conn->close();
    exit;
}