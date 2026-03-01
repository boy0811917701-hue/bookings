<?php
include '../db.php';

$name = $_GET['name'] ?? '';
$bookings = [];
$current = null;

// 🔔 คิวที่กำลังให้บริการ
$currentStmt = $conn->query("
    SELECT booking_code
    FROM bookings
    WHERE status = 'processing'
    ORDER BY booking_date, booking_time, booking_id
    LIMIT 1
");
$current = $currentStmt ? $currentStmt->fetch_assoc() : null;

if (!empty($name)) {

    // 🔍 ดึงคิวทั้งหมดของชื่อนี้
    $stmt = $conn->prepare("
        SELECT *
        FROM bookings
        WHERE customer_name LIKE ?
        ORDER BY booking_date DESC, booking_time DESC
    ");
    $like = "%{$name}%";
    $stmt->bind_param("s", $like);
    $stmt->execute();
    $bookings = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
}

// ฟังก์ชันหาลำดับคิว
function getQueueNo($conn, $booking)
{
    if ($booking['status'] !== 'pending') {
        return null;
    }

    $stmt = $conn->prepare("
        SELECT COUNT(*) AS queue_before
        FROM bookings
        WHERE status = 'pending'
          AND (
                booking_date < ?
                OR (booking_date = ? AND booking_time < ?)
                OR (booking_date = ? AND booking_time = ? AND booking_id < ?)
              )
    ");

    $stmt->bind_param(
        "sssssi",
        $booking['booking_date'],
        $booking['booking_date'],
        $booking['booking_time'],
        $booking['booking_date'],
        $booking['booking_time'],
        $booking['booking_id']
    );

    $stmt->execute();
    $count = $stmt->get_result()->fetch_assoc();

    return $count['queue_before'] + 1;
}
?>
<!DOCTYPE html>
<html lang="th">

<head>
    <meta charset="UTF-8">
    <title>ตรวจสอบคิวจากชื่อ</title>

    <!-- สำคัญสำหรับมือถือ -->
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <meta http-equiv="refresh" content="30">
    <link rel="stylesheet" href="../css/queue_check.css">

    <style>
        body {
            font-family: Arial;
            background: #f5f5f5;
        }

        .box {
            background: #fff;
            padding: 20px;
            max-width: 800px;
            margin: 30px auto;
            border-radius: 8px;
        }

        table {
            width: 100%;
            border-collapse: collapse;
        }

        th,
        td {
            border: 1px solid #ccc;
            padding: 8px;
            text-align: center;
        }

        th {
            background: #eee;
        }

        .pending {
            color: orange;
        }

        .processing {
            color: green;
        }

        .done {
            color: gray;
        }
    </style>
</head>

<body>

    <div class="box">
        <h2>📋 ตรวจสอบคิวจากชื่อ</h2>

        <?php if ($current): ?>
            <p>🔔 <strong>คิวที่กำลังให้บริการ:</strong> <?= htmlspecialchars($current['booking_code']) ?></p>
        <?php endif; ?>

        <form method="get" class="search-form">
            <label for="name">กรอกชื่อ</label>

            <input type="text" id="name" name="name" value="<?= htmlspecialchars($name) ?>"
                placeholder="พิมพ์ชื่อผู้จอง" required>

            <div class="form-actions">
                <button type="submit">ค้นหา</button>
                <a href="componan.php" class="back-link">กลับหน้าหลัก</a>
            </div>
        </form>

        <?php if (!empty($name)): ?>
            <hr>

            <?php if (count($bookings) > 0): ?>
                <h3>📌 รายการจองคิวของ “<?= htmlspecialchars($name) ?>”</h3>
                <div class="table-wrap">



                    <table>
                        <tr>
                            <th>รหัสคิว</th>
                            <th>วันที่</th>
                            <th>เวลา</th>
                            <th>สถานะ</th>
                            <th>ลำดับคิว</th>
                            <th>เวลารอ (ประมาณ)</th>
                        </tr>

                        <?php foreach ($bookings as $b): ?>
                            <?php
                            $queueNo = getQueueNo($conn, $b);
                            $wait = $queueNo ? ($queueNo - 1) * 10 : '-';
                            ?>
                            <tr>
                                <td><?= htmlspecialchars($b['booking_code']) ?></td>
                                <td><?= $b['booking_date'] ?></td>
                                <td><?= $b['booking_time'] ?></td>
                                <td class="<?= $b['status'] ?>"><?= $b['status'] ?></td>
                                <td><?= $queueNo ?? '-' ?></td>
                                <td><?= $queueNo ? $wait . ' นาที' : '-' ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </table>
                </div>
            <?php else: ?>
                <p style="color:red;">❌ ไม่พบประวัติการจองคิว</p>
            <?php endif; ?>
        <?php endif; ?>

    </div>

</body>

</html>