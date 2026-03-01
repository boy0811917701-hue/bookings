<?php
require_once("../db.php");

$selectedDate = $_GET['date'] ?? date('Y-m-d');

/* ================== กราฟสถิติการจอง ================== */
$sql = "
SELECT s.service_name, COUNT(b.booking_id) AS total_booking
FROM bookings b
JOIN services s ON b.service_id = s.service_id
WHERE b.status != 'cancelled'
AND DATE(b.created_at) = '$selectedDate'
GROUP BY s.service_id
";

$result = $conn->query($sql);

$serviceNames = [];
$bookingCounts = [];

if ($result) {
    while ($row = $result->fetch_assoc()) {
        $serviceNames[] = $row['service_name'];
        $bookingCounts[] = $row['total_booking'];
    }
}


/* ================== สรุปผลตัวเลข ================== */

// 1. จำนวนผู้ใช้งานทั้งหมด
$sqlUser = "SELECT COUNT(*) AS total_user FROM users";
$userResult = $conn->query($sqlUser);
$totalUser = $userResult->fetch_assoc()['total_user'] ?? 0;

// 2. ผู้ที่ทำการจอง (รายวัน)
$sqlBookingUser = "
SELECT COUNT(*) AS total_booking_user
FROM bookings
WHERE status IS NOT NULL
AND status != 'cancelled'
AND DATE(created_at) = '$selectedDate'
";

$bookingUserResult = $conn->query($sqlBookingUser);
$totalBookingUser = $bookingUserResult->fetch_assoc()['total_booking_user'] ?? 0;

// 3. การจองที่เสร็จสิ้น (จาก booking_logs)
$sqlCompleted = "
SELECT COUNT(*) AS total_completed
FROM booking_logs
WHERE DATE(created_at) = '$selectedDate'
";

$completedResult = $conn->query($sqlCompleted);
$totalCompleted = $completedResult->fetch_assoc()['total_completed'] ?? 0;


$sql = "
SELECT s.service_name, COUNT(b.booking_id) AS total_booking
FROM bookings b
JOIN services s ON b.service_id = s.service_id
WHERE b.status != 'cancelled'
GROUP BY s.service_id
";

$result = $conn->query($sql);

$serviceNames = [];
$bookingCounts = [];

while ($row = $result->fetch_assoc()) {
    $serviceNames[] = $row['service_name'];
    $bookingCounts[] = $row['total_booking'];
}
?>



<!DOCTYPE html>
<html lang="th">

<head>
    <meta charset="UTF-8">
    <title>กราฟสถิติการจอง</title>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script src="https://cdn.jsdelivr.net/npm/chartjs-plugin-datalabels@2"></script>

   <link rel="stylesheet" href="../css/dashboard.css">
</head>

<body>
    <?php include '../bar.php' ?>

  <h2>สรุปผลการใช้งานระบบ</h2>

<div class="summary-container">
    <div class="summary-card pink">
        <div class="summary-title">ผู้ใช้งานทั้งหมด</div>
        <div class="summary-number"><?= $totalUser ?></div>
        <div class="summary-unit">คน</div>
    </div>

    <div class="summary-card rose">
        <div class="summary-title" >ผู้ที่ทำการจอง</div>
        <div class="summary-number"><?= $totalBookingUser ?></div>
        <div class="summary-unit">คน</div>
    </div>

    <div class="summary-card orange">
        <div class="summary-title">การจองที่เสร็จสิ้น</div>
        <div class="summary-number"><?= $totalCompleted ?></div>
        <div class="summary-unit">รายการ</div>
    </div>
    
</div>
<<div class="date-control">
    <form method="get" class="date-form">

        <button type="submit" name="date" value="<?= date('Y-m-d') ?>" class="btn btn-today">
            📅 วันนี้
        </button>

        <button type="button"
            onclick="location.href='?date=<?= date('Y-m-d', strtotime($selectedDate . ' -1 day')) ?>'"
            class="btn btn-nav">
            ◀ เมื่อวาน
        </button>

        <input type="date" name="date" value="<?= $selectedDate ?>" class="date-input">

        <button type="submit" class="btn btn-view">
            🔍 ดูข้อมูล
        </button>

        <button type="button"
            onclick="location.href='?date=<?= date('Y-m-d', strtotime($selectedDate . ' +1 day')) ?>'"
            class="btn btn-nav">
            พรุ่งนี้ ▶
        </button>

    </form>
</div>




<script>
const serviceLabels = <?= json_encode($serviceNames) ?>;
const bookingData = <?= json_encode($bookingCounts) ?>;

const ctx = document.getElementById('bookingChart').getContext('2d');
new Chart(ctx, {
    type: 'bar',
    data: {
        labels: serviceLabels,
        datasets: [{
            label: 'จำนวนการจอง',
            data: bookingData,
            borderWidth: 1
        }]
    },
    options: {
        responsive: true,
        plugins: {
            legend: { display: false },
            title: {
                display: true,
                text: 'สถิติการจองตามบริการ'
            }
        },
        scales: {
            y: {
                beginAtZero: true,
                ticks: { stepSize: 1 }
            }
        }
    }
});
</script>

<h2>กราฟสถิติการจองบริการ</h2>

<div class="chart-wrapper">
    <div class="chart-container">
        <canvas id="pieChart"></canvas>
    </div>
</div>

<script>
const labels = <?= json_encode($serviceNames) ?>;
const dataValues = <?= json_encode($bookingCounts) ?>;
const total = dataValues.reduce((a, b) => a + b, 0);

const isMobile = window.innerWidth <= 768;

const pieCtx = document.getElementById('pieChart');

new Chart(pieCtx, {
    type: 'pie',
    data: {
        labels,
        datasets: [{
            data: dataValues,
            backgroundColor: [
                '#f472b6',
                '#fb7185',
                '#f9a8d4',
                '#fdba74',
                '#a5b4fc'
            ]
        }]
    },
    options: {
        responsive: true,
        maintainAspectRatio: true,
        plugins: {
            legend: {
                position: isMobile ? 'bottom' : 'right',
                labels: {
                    padding: 12,
                    generateLabels: function(chart) {
                        const dataset = chart.data.datasets[0];
                        return chart.data.labels.map((label, i) => {
                            const value = dataset.data[i];
                            const percent = ((value / total) * 100).toFixed(1);
                            return {
                                text: `${label} (${percent}%)`,
                                fillStyle: dataset.backgroundColor[i],
                                strokeStyle: dataset.backgroundColor[i],
                                index: i
                            };
                        });
                    }
                }
            }
        }
    }
});
</script>


</body>

</html>