<?php
session_start();
include '../db.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: ../login.php");
    exit;
}

$user_id = $_SESSION['user_id'];
$name = $_GET['name'] ?? '';

/* 🔔 1. ดึงคิวที่กำลังให้บริการ (Service Panel) */
$currentStmt = $conn->query("SELECT booking_code FROM bookings WHERE status = 'processing' ORDER BY booking_date, booking_time, booking_id LIMIT 1");
$current = $currentStmt ? $currentStmt->fetch_assoc() : null;

/* 🔐 2. ดึงคิวเฉพาะของผู้ใช้ */
$sql = "SELECT b.*, p.product_name 
        FROM bookings b 
        LEFT JOIN products p ON b.product_id = p.product_id 
        WHERE b.user_id = ?";
if (!empty($name)) { $sql .= " AND b.customer_name LIKE ?"; }
$sql .= " ORDER BY b.booking_date ASC, b.booking_time ASC";

$stmt = $conn->prepare($sql);
if (!empty($name)) {
    $like = "%{$name}%";
    $stmt->bind_param("is", $user_id, $like);
} else {
    $stmt->bind_param("i", $user_id);
}
$stmt->execute();
$all_bookings = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

/* 📂 3. แยกกลุ่มคิว (เช้า/บ่าย) */
$morning_bookings = [];
$afternoon_bookings = [];

foreach ($all_bookings as $b) {
    $hour = (int)date('H', strtotime($b['booking_time']));
    if ($hour < 13) {
        $morning_bookings[] = $b;
    } else {
        $afternoon_bookings[] = $b;
    }
}

/* 🔢 ฟังก์ชันคำนวณลำดับคิว (แยกเงื่อนไข SQL ตามรอบ) */
function getQueueNo($conn, $booking) {
    if (!in_array($booking['status'], ['pending', 'confirmed', 'processing'])) return null;
    $is_afternoon = (strtotime($booking['booking_time']) >= strtotime('13:00:00'));
    $time_condition = $is_afternoon ? ">= '13:00:00'" : "< '13:00:00'";

    $stmt = $conn->prepare("
        SELECT COUNT(*) AS queue_before FROM bookings 
        WHERE status IN ('pending', 'confirmed', 'processing') 
        AND booking_date = ? AND booking_time $time_condition
        AND (booking_time < ? OR (booking_time = ? AND booking_id < ?))
    ");
    $stmt->bind_param("sssi", $booking['booking_date'], $booking['booking_time'], $booking['booking_time'], $booking['booking_id']);
    $stmt->execute();
    return $stmt->get_result()->fetch_assoc()['queue_before'] + 1;
}

$status_th = [
    'pending' => 'รอคิว',
    'confirmed' => 'ยืนยันแล้ว',
    'processing' => 'กำลังดำเนินการ',
    'completed' => 'เสร็จสิ้น',
    'cancelled' => 'ยกเลิก'
];
?>

<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <title>สถานะคิว | Fanier Beauty Style</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="refresh" content="30">
    <link rel="stylesheet" href="../css/bar_menu.css">
    <link href="https://fonts.googleapis.com/css2?family=Prompt:wght@300;400;600&display=swap" rel="stylesheet">
    <style>
        :root { --gold: #d4af37; --light-gold: #f1d592; --dark-bg: #1a1a1a; --card-bg: #2d2d2d; }
        body { font-family: 'Prompt', sans-serif; background-color: var(--dark-bg); color: #eee; margin: 0; padding-bottom: 50px; }
        .container { max-width: 500px; margin: 0 auto; padding: 20px; }
        
        /* สไตล์หัวข้อรอบ */
        .session-header { 
            display: flex; align-items: center; margin: 30px 0 15px; 
            color: var(--light-gold); font-weight: 600; font-size: 1.1rem;
        }
        .session-header::after { content: ''; flex: 1; height: 1px; background: #444; margin-left: 15px; }
        
        .ticket {
            background: var(--card-bg); border: 1px solid #444; border-radius: 15px;
            padding: 20px; margin-bottom: 20px; position: relative; overflow: hidden;
        }
        .ticket::before { content: ''; position: absolute; top: 0; left: 0; width: 5px; height: 100%; background: var(--gold); }
        .ticket-head { display: flex; justify-content: space-between; align-items: center; margin-bottom: 10px; }
        .ticket-id { font-size: 1.3rem; font-weight: 600; color: var(--gold); }
        
        .status { font-size: 0.75rem; padding: 4px 10px; border-radius: 6px; border: 1px solid; }
        .status.confirmed { color: #339af0; border-color: #339af0; background: rgba(51, 154, 240, 0.1); }
        .status.pending { color: #fcc419; border-color: #fcc419; }
        .status.processing { color: #51cf66; border-color: #51cf66; }
        
        .info { font-size: 0.9rem; color: #bbb; margin: 5px 0; }
        .info b { color: #fff; font-weight: 400; }

        .queue-summary {
            display: flex; background: #222; border-radius: 12px; margin-top: 15px; padding: 12px; border: 1px solid #3d3d3d;
        }
        .q-item { flex: 1; text-align: center; }
        .q-item:first-child { border-right: 1px solid #444; }
        .q-item span { display: block; font-size: 0.7rem; color: var(--gold); }
        .q-item strong { font-size: 1.2rem; color: #fff; }

        .empty-msg { text-align: center; color: #666; font-size: 0.9rem; padding: 20px; border: 1px dashed #444; border-radius: 10px; }
        .back-link { display: block; text-align: center; color: var(--gold); text-decoration: none; margin-top: 30px; }
    </style>
</head>
<body>

<?php include '../bar_menu.php'; ?>

<div class="container">
    <h2 style="text-align:center; color:white; margin-bottom: 10px;">สถานะคิวของคุณ</h2>

    <div class="session-header">☀️ รอบเช้า (08:00 - 12:59)</div>
    <?php if (empty($morning_bookings)): ?>
        <div class="empty-msg">ไม่มีรายการจองในรอบเช้า</div>
    <?php else: ?>
        <?php foreach ($morning_bookings as $b): 
            $q = getQueueNo($conn, $b);
            $wait = ($q > 1) ? ($q - 1) * 15 : 0;
        ?>
            <div class="ticket">
                <div class="ticket-head">
                    <span class="ticket-id">#<?= htmlspecialchars($b['booking_code']) ?></span>
                    <span class="status <?= $b['status'] ?>"><?= $status_th[$b['status']] ?? $b['status'] ?></span>
                </div>
                <div class="info">บริการ: <b><?= htmlspecialchars($b['product_name'] ?: 'ทั่วไป') ?></b></div>
                <div class="info">เวลา: <b><?= date('H:i', strtotime($b['booking_time'])) ?> น.</b></div>
                <?php if ($q !== null): ?>
                    <div class="queue-summary">
                        <div class="q-item"><span>ลำดับคิวรอบเช้า</span><strong><?= $q ?></strong></div>
                        <div class="q-item"><span>รอประมาณ</span><strong>⏳ <?= $wait ?> นาที</strong></div>
                    </div>
                <?php endif; ?>
            </div>
        <?php endforeach; ?>
    <?php endif; ?>

    <div class="session-header">☁️ รอบบ่าย (13:00 เป็นต้นไป)</div>
    <?php if (empty($afternoon_bookings)): ?>
        <div class="empty-msg">ไม่มีรายการจองในรอบบ่าย</div>
    <?php else: ?>
        <?php foreach ($afternoon_bookings as $b): 
            $q = getQueueNo($conn, $b);
            $wait = ($q > 1) ? ($q - 1) * 15 : 0;
        ?>
            <div class="ticket">
                <div class="ticket-head">
                    <span class="ticket-id">#<?= htmlspecialchars($b['booking_code']) ?></span>
                    <span class="status <?= $b['status'] ?>"><?= $status_th[$b['status']] ?? $b['status'] ?></span>
                </div>
                <div class="info">บริการ: <b><?= htmlspecialchars($b['product_name'] ?: 'ทั่วไป') ?></b></div>
                <div class="info">เวลา: <b><?= date('H:i', strtotime($b['booking_time'])) ?> น.</b></div>
                <?php if ($q !== null): ?>
                    <div class="queue-summary">
                        <div class="q-item"><span>ลำดับคิวรอบบ่าย</span><strong><?= $q ?></strong></div>
                        <div class="q-item"><span>รอประมาณ</span><strong>⏳ <?= $wait ?> นาที</strong></div>
                    </div>
                <?php endif; ?>
            </div>
        <?php endforeach; ?>
    <?php endif; ?>

    <a href="recommended.php" class="back-link">← กลับหน้าหลัก</a>
</div>

</body>
</html>