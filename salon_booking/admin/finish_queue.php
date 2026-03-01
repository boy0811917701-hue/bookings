<?php
require_once("../db.php");

if (isset($_POST['booking_id'])) {
    $booking_id = intval($_POST['booking_id']);

    $sql = "UPDATE bookings 
            SET status = 'completed' 
            WHERE booking_id = $booking_id";

    if ($conn->query($sql)) {
        header("Location: status.php");
        exit;
    } else {
        echo "Error: " . $conn->error;
    }
}
