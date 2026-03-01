<?php
require_once("../db.php");

$sql = "SELECT 
        booking_id,
        booking_code,
        customer_name,
        customer_phone,
        service_id,
        booking_date,
        status
    FROM bookings
    WHERE status = 'cancelled'
    ORDER BY booking_id ASC";


$result = $conn->query($sql);
if (!$result) {
    die("SQL Error: " . $conn->error);
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>status</title>
    <link rel="stylesheet" href="../css/finish.css">
</head>

<body>

<?php include '../bar.php'; ?>

<div class="container">
    <h2>คิวที่กำลังดำเนินการ</h2>

    <table class="booking-table">
        <thead>
            <tr>
                <th>รหัสคิว</th>
                <th>ชื่อ</th>
                <th>เบอร์โทร</th>
                <th>บริการ</th>
                <th>วันที่</th>
                <th>สถานะ</th>
                <th>จัดการ</th>
            </tr>
        </thead>
        <tbody>

        <?php if ($result->num_rows > 0): ?>
            <?php while ($row = $result->fetch_assoc()): ?>
            <tr>
                <td><?= htmlspecialchars($row['booking_code']) ?></td>
                <td><?= htmlspecialchars($row['customer_name']) ?></td>
                <td><?= htmlspecialchars($row['customer_phone']) ?></td>
                <td><?= htmlspecialchars($row['service_id']) ?></td>
                <td><?= htmlspecialchars($row['booking_date']) ?></td>

                <!-- สถานะ -->
                <td>
                    <?php
                    if ($row['status'] === 'pending') {
                        echo '<span class="status-badge status-pending">รอดำเนินการ</span>';
                    } elseif ($row['status'] === 'completed') {
                        echo '<span class="status-badge status-completed">เสร็จสิ้น</span>';
                    } else {
                        echo '<span class="status-badge status-cancelled">ยกเลิก</span>';
                    }
                    ?>
                </td>

                <!-- จัดการ -->
                <td>
                    <?php if ($row['status'] === 'cancelled'): ?>
                        <form action="delete_queue.php" method="post" style="display:inline;">
                            <input type="hidden" name="booking_id" value="<?= $row['booking_id'] ?>">
                            <button type="submit" class="action-link"
                                onclick="return confirm('ยืนยันลบการจองที่ถูกยกเลิก?')">
                                ลบ
                            </button>
                        </form>
                    <?php else: ?>
                        <span style="color:#888;">-</span>
                    <?php endif; ?>
                </td>
            </tr>
            <?php endwhile; ?>
        <?php else: ?>
            <tr>
                <td colspan="7" style="padding:20px; color:#888;">
                    ไม่มีข้อมูลการจอง
                </td>
            </tr>
        <?php endif; ?>

        </tbody>
    </table>
</div>

</body>
</html>
