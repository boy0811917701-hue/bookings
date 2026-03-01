<?php 
include '../db.php'; 

$search_query = "";
$result_set = null;

/* =======================
   SEARCH
======================= */
if (!empty($_GET['q'])) {
    $search_query = trim($_GET['q']);

    $sql = "SELECT 
                b.booking_code,
                b.customer_name,
                b.customer_phone,
                b.booking_date,
                b.booking_time,
                b.status,
                s.service_name
            FROM bookings b
            LEFT JOIN services s ON b.service_id = s.service_id
            WHERE 
                b.customer_name LIKE ?
                OR b.booking_code LIKE ?
                OR b.customer_phone LIKE ?
            ORDER BY b.booking_date DESC, b.booking_time DESC";

    $stmt = $conn->prepare($sql);
    $like = "%$search_query%";
    $stmt->bind_param("sss", $like, $like, $like);
    $stmt->execute();
    $result_set = $stmt->get_result();
}

/* =======================
   ALL BOOKINGS
======================= */
$sql = "SELECT 
            b.booking_code, 
            b.customer_name, 
            b.customer_phone,
            s.service_name, 
            b.booking_date, 
            b.booking_time, 
            b.status 
        FROM bookings b
        LEFT JOIN services s ON b.service_id = s.service_id
        ORDER BY b.booking_date ASC, b.booking_time ASC";

$result = $conn->query($sql);
?>

<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <title>Fanier Beauty Style - Admin</title>
    <link rel="stylesheet" href="../css/admin-index.css">
</head>
<body>

<?php include '../bar.php'; ?>

<div class="container">
    <h2>รายการจองคิวลูกค้า</h2>

    <!-- SEARCH -->
    <form method="GET" style="margin-bottom:20px;">
        <input type="text" name="q"
            value="<?= htmlspecialchars($search_query) ?>"
            placeholder="พิมพ์ชื่อ / เบอร์ / รหัสจอง">
        <button type="submit">ค้นหา</button>
    </form>

<?php if ($result_set !== null): ?>

<!-- SEARCH RESULT -->
<table class="booking-table">
    <thead>
        <tr>
            <th>รหัสจอง</th>
            <th>ลูกค้า</th>
            <th>บริการ</th>
            <th>วัน / เวลา</th>
            <th>สถานะ</th>
        </tr>
    </thead>
    <tbody>
    <?php if ($result_set->num_rows > 0): ?>
        <?php while ($row = $result_set->fetch_assoc()): ?>
        <tr>
            <td><?= $row['booking_code']; ?></td>
            <td>
                <?= htmlspecialchars($row['customer_name']); ?><br>
                <small><?= htmlspecialchars($row['customer_phone']); ?></small>
            </td>
            <td><?= $row['service_name'] ?: 'ไม่ระบุ'; ?></td>
            <td>
                <?= date('d/m/Y', strtotime($row['booking_date'])); ?> |
                <?= substr($row['booking_time'],0,5); ?>
            </td>
            <td>
                <?= $row['status']=='pending'?'รอดำเนินการ':($row['status']=='confirmed'?'เสร็จสิ้น':'ยกเลิก'); ?>
            </td>
        </tr>
        <?php endwhile; ?>
    <?php else: ?>
        <tr><td colspan="5" align="center">ไม่พบข้อมูล</td></tr>
    <?php endif; ?>
    </tbody>
</table>

<a href="index.php">⬅ กลับรายการทั้งหมด</a>

<?php else: ?>

<!-- ALL BOOKINGS -->
<table class="booking-table">
    <thead>
        <tr>
            <th>รหัสจอง</th>
            <th>ลูกค้า</th>
            <th>บริการ</th>
            <th>วัน / เวลา</th>
            <th>สถานะ</th>
            <th>จัดการ</th>
           
        </tr>
    </thead>
    <tbody>
        
    <?php while ($row = $result->fetch_assoc()): ?>
   <tr class="<?= $row['status']=='confirmed' ? 'auto-hide' : '' ?>"
    data-booking-code="<?= $row['booking_code']; ?>">



            <td><?= $row['booking_code']; ?></td>
            <td><?= htmlspecialchars($row['customer_name']); ?></td>
            <td><?= $row['service_name'] ?: 'ไม่ระบุ'; ?></td>
            <td>
                <?= date('d/m/Y', strtotime($row['booking_date'])); ?> |
                <?= substr($row['booking_time'],0,5); ?>
            </td>
            <td>
                <?= $row['status']=='pending'?'รอดำเนินการ':($row['status']=='confirmed'?'เสร็จสิ้น':'ยกเลิก'); ?>
            </td>
            <td>
                <form action="update_status.php" method="POST" style="display:flex; gap:5px;">
                    <input type="hidden" name="booking_code" value="<?= $row['booking_code']; ?>">

                    <button name="status" value="pending"
                        <?= $row['status']=='pending'?'disabled':'' ?>>
                        🕒
                    </button>

                    <button name="status" value="confirmed"
                        <?= $row['status']=='confirmed'?'disabled':'' ?>>
                        ✅
                    </button>

                    <button name="status" value="cancelled"
                        <?= $row['status']=='cancelled'?'disabled':'' ?>>
                        ❌
                    </button>
                     <button>ยกเลิกคิว</button>
                </form>
            </td>
        </tr>
    <?php endwhile; ?>
    </tbody>
</table>

<?php endif; ?>

</div>
<script>
document.addEventListener("DOMContentLoaded", function () {
    const rows = document.querySelectorAll(".auto-hide");

    rows.forEach(row => {
        const bookingCode = row.dataset.bookingCode;

        setTimeout(() => {
            row.style.transition = "opacity 0.5s ease";
            row.style.opacity = "0";

            // ลบจากฐานข้อมูล
            fetch("delete_booking.php", {
                method: "POST",
                headers: {
                    "Content-Type": "application/x-www-form-urlencoded"
                },
                body: "booking_code=" + encodeURIComponent(bookingCode)
            });

            // ลบออกจากหน้า
            setTimeout(() => {
                row.remove();
            }, 500);

        }, 15000); // 15 วินาที
    });
});
</script>


</body>
</html>
