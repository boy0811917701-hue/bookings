<?php
session_start();
if (!isset($_SESSION['user_id']) || ($_SESSION['role'] ?? '') !== 'admin') {
    header("Location: ../login.php");
    exit();
}

require_once("../db.php");

// 1. รับค่าวันที่กรอง (Default: ช่วงที่มีข้อมูลจริง)
$rangeRes = $conn->query("SELECT MIN(booking_date) AS min_date, MAX(booking_date) AS max_date FROM bookings");
$rangeRow = $rangeRes ? $rangeRes->fetch_assoc() : [];
$today = date('Y-m-d');
$default_start = $rangeRow['min_date'] ?: date('Y-m-01');
$default_end = $rangeRow['max_date'] ? max($rangeRow['max_date'], $today) : $today;

$start_date = $_GET['start_date'] ?? $default_start; 
$end_date = $_GET['end_date'] ?? $default_end;

// กันเคสกรองผิดลำดับ
if ($start_date > $end_date) {
    $tmp = $start_date;
    $start_date = $end_date;
    $end_date = $tmp;
}

/** * 2. ดึงข้อมูลสรุปรวม (รายได้ + จำนวนสถานะ)
 */
$summarySql = "
    SELECT 
        COUNT(*) AS total,
        SUM(CASE WHEN LOWER(status) = 'pending' THEN 1 ELSE 0 END) AS pending_count,
        SUM(CASE WHEN LOWER(status) = 'confirmed' THEN 1 ELSE 0 END) AS checkin_count,
        SUM(CASE WHEN LOWER(status) = 'completed' THEN 1 ELSE 0 END) AS completed_count,
        COALESCE(SUM(CASE WHEN LOWER(status) IN ('confirmed', 'completed') THEN total_price ELSE 0 END), 0) AS total_income
    FROM bookings
    WHERE booking_date BETWEEN ? AND ?
";

$summaryStmt = $conn->prepare($summarySql);
$summaryStmt->bind_param("ss", $start_date, $end_date);
$summaryStmt->execute();
$summaryResult = $summaryStmt->get_result()->fetch_assoc();

$total = (int)($summaryResult['total'] ?? 0);
$pending = (int)($summaryResult['pending_count'] ?? 0);
$checkin = (int)($summaryResult['checkin_count'] ?? 0); // confirmed = เช็คอิน
$completed = (int)($summaryResult['completed_count'] ?? 0);
$income = (float)($summaryResult['total_income'] ?? 0);

/**
 * 3. ข้อมูลกราฟรายได้ (7 วันถัดไป นับจากวันนี้) รีเซตอัตโนมัติทุกวัน
 * นับยอดรวมจากสถานะ pending / processing / confirmed / completed (ตัด cancelled)
 */
$incomeLabels = [];
$incomeData = [];
$allowedStatuses = ['pending','processing','confirmed','completed'];

for ($i = 0; $i <= 6; $i++) {
    $date = date('Y-m-d', strtotime("+$i days"));
    $incomeLabels[] = date('d/m', strtotime($date));

    $checkIncome = $conn->prepare(
        "SELECT SUM(total_price) as daily FROM bookings WHERE booking_date = ? AND LOWER(status) IN ('pending','processing','confirmed','completed')"
    );
    $checkIncome->bind_param("s", $date);
    $checkIncome->execute();
    $res = $checkIncome->get_result()->fetch_assoc();
    $incomeData[] = (float)($res['daily'] ?? 0);
}

/**
 * 4. รายการจองล่าสุด
 */
$listStmt = $conn->prepare("
    SELECT booking_code, customer_name, product_name, total_price, status, booking_date, booking_time 
    FROM bookings 
    WHERE booking_date BETWEEN ? AND ? 
    ORDER BY COALESCE(created_at, booking_date) DESC, booking_id DESC LIMIT 15
");
$listStmt->bind_param("ss", $start_date, $end_date);
$listStmt->execute();
$list = $listStmt->get_result(); 
?>

<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <title>Dashboard | Fanier Admin</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="../css/bar.css">
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <link href="https://fonts.googleapis.com/css2?family=Prompt:wght@300;400;600&display=swap" rel="stylesheet">
    <style>
       body { 
            font-family: 'Prompt', sans-serif; 
            background-color: #121212; 
            color: #eee; 
            margin: 0;
        }

        /* 2. เพิ่มระยะห่างด้านบนให้ Content ไม่โดน Navbar ทับ */
        .main-content {
            padding-top: 120px; /* ปรับค่านี้เพิ่ม/ลด ตามความสูงของ Navbar คุณ */
            padding-bottom: 50px;
        }

        .gold-glow { text-shadow: 0 0 15px rgba(212, 175, 55, 0.4); }
        
        /* ปรับ Card ให้สว่างกว่าพื้นหลังเล็กน้อยเพื่อสร้างมิติ */
        .card-dark { 
            background: #1c1c1c; 
            border: 1px solid rgba(255,255,255,0.05); 
            transition: transform 0.2s ease;
        }
        .card-dark:hover { transform: translateY(-5px); }
      
    </style>
</head>
<body>
        <?php include '../bar.php'; ?>

    <div class="max-w-7xl mx-auto">
        <div class="flex flex-col md:flex-row justify-between items-start md:items-center mb-8 gap-4">
            <div>
                <h1 class="text-3xl font-bold text-white">รายละเอียด</h1>
                <p class="text-gray-500 text-sm">ภาพรวมระบบจอง Fanier Beauty Style</p>
            </div>
            
            <form class="flex flex-wrap gap-2 items-center bg-[#151515] p-2 rounded-xl border border-white/5" method="GET">
                <input type="date" name="start_date" value="<?= $start_date ?>" class="bg-transparent text-sm p-2 outline-none">
                <span class="text-gray-600">-</span>
                <input type="date" name="end_date" value="<?= $end_date ?>" class="bg-transparent text-sm p-2 outline-none">
                <button type="submit" class="bg-[#D4AF37] text-black px-4 py-2 rounded-lg font-bold text-sm hover:brightness-110 transition">คำนวณรายได้</button>
            </form>
        </div>

        <?php if ($total === 0): ?>
            <div class="mb-6 text-sm text-yellow-400 bg-yellow-500/10 border border-yellow-500/30 px-4 py-3 rounded-xl">
                ไม่พบข้อมูลในช่วงวันที่ที่เลือก (<?= htmlspecialchars($start_date) ?> - <?= htmlspecialchars($end_date) ?>)
            </div>
        <?php endif; ?>

        <div class="grid grid-cols-1 md:grid-cols-3 xl:grid-cols-5 gap-6 mb-8">
            <div class="card-dark p-6 rounded-2xl">
                <p class="text-gray-500 text-xs uppercase tracking-widest mb-2">รายได้รวม (รับแล้ว)</p>
                <h3 class="text-3xl font-bold text-[#D4AF37] gold-glow">฿<?= number_format($income) ?></h3>
                <p class="text-xs text-gray-600 mt-2">รวมสถานะ Confirmed + Completed</p>
            </div>
            <div class="card-dark p-6 rounded-2xl">
                <p class="text-gray-500 text-xs uppercase tracking-widest mb-2">การจองทั้งหมด</p>
                <h3 class="text-3xl font-bold text-white"><?= number_format($total) ?></h3>
                <p class="text-xs text-gray-600 mt-2">ในช่วงวันที่ที่เลือก</p>
            </div>
            <div class="card-dark p-6 rounded-2xl border-l-4 border-blue-500/50">
                <p class="text-gray-500 text-xs uppercase tracking-widest mb-2">เช็คอิน (Confirmed)</p>
                <h3 class="text-3xl font-bold text-blue-400"><?= number_format($checkin) ?></h3>
                <p class="text-xs text-gray-600 mt-2">ลูกค้ามาถึงแล้ว</p>
            </div>
            <div class="card-dark p-6 rounded-2xl border-l-4 border-green-500/50">
                <p class="text-gray-500 text-xs uppercase tracking-widest mb-2">เสร็จสิ้น (Completed)</p>
                <h3 class="text-3xl font-bold text-green-400"><?= number_format($completed) ?></h3>
                <p class="text-xs text-gray-600 mt-2">บริการเสร็จสมบูรณ์</p>
            </div>
            <div class="card-dark p-6 rounded-2xl border-l-4 border-yellow-500/50 md:col-span-2 xl:col-span-1">
                <p class="text-gray-500 text-xs uppercase tracking-widest mb-2">รอดำเนินการ (Pending)</p>
                <h3 class="text-3xl font-bold text-yellow-400"><?= number_format($pending) ?></h3>
                <p class="text-xs text-gray-600 mt-2">ลูกค้ายังไม่เช็คอิน</p>
            </div>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
            <div class="lg:col-span-2 card-dark rounded-2xl overflow-hidden">
                <div class="p-6 border-b border-white/5 flex justify-between items-center">
                    <h3 class="font-bold">รายการจองล่าสุด</h3>
                    <a href="index.php" class="text-[#D4AF37] text-xs hover:underline">ดูทั้งหมด</a>
                </div>
                <div class="overflow-x-auto">
                    <table class="w-full text-left text-sm">
                        <thead class="text-gray-500 uppercase text-[10px] bg-black/20">
                            <tr>
                                <th class="p-4">รหัส</th>
                                <th class="p-4">ลูกค้า</th>
                                <th class="p-4">บริการ</th>
                                <th class="p-4">วันที่/เวลา</th>
                                <th class="p-4">ราคา</th>
                                <th class="p-4 text-center">สถานะ</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-white/5">
                            <?php while($row = $list->fetch_assoc()): ?>
                            <tr class="hover:bg-white/[0.02] transition">
                                <td class="p-4 font-mono text-[#D4AF37]"><?= $row['booking_code'] ?></td>
                                <td class="p-4 font-semibold"><?= htmlspecialchars($row['customer_name']) ?></td>
                                <td class="p-4 text-gray-400"><?= htmlspecialchars($row['product_name']) ?></td>
                                <td class="p-4 text-gray-300 text-xs">
                                    <?= htmlspecialchars(date('d/m/Y', strtotime($row['booking_date']))) ?><br>
                                    <span class="text-gray-500"><?= htmlspecialchars(substr($row['booking_time'], 0, 5)) ?> น.</span>
                                </td>
                                <td class="p-4">฿<?= number_format($row['total_price']) ?></td>
                                <td class="p-4 text-center">
                                    <?php 
                                        $s = strtolower($row['status']);
                                        $color = 'text-gray-300 bg-gray-400/10 border-gray-400/20';
                                        if ($s === 'confirmed') $color = 'text-blue-300 bg-blue-400/10 border-blue-400/20';
                                        elseif ($s === 'completed') $color = 'text-green-300 bg-green-400/10 border-green-400/20';
                                        elseif ($s === 'pending') $color = 'text-yellow-300 bg-yellow-400/10 border-yellow-400/20';
                                    ?>
                                    <span class="px-2 py-1 rounded-md text-[10px] border <?= $color ?> uppercase font-bold">
                                        <?= $s ?>
                                    </span>
                                </td>
                            </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <div class="card-dark p-6 rounded-2xl">
                <h3 class="font-bold mb-6">สถิติรายได้ 7 วัน</h3>
                <div class="h-[300px]">
                    <canvas id="incomeChart"></canvas>
                </div>
                <div class="mt-6 p-4 bg-black/30 rounded-xl border border-white/5">
                    <p class="text-xs text-gray-500 leading-relaxed italic">
                        * แสดงยอดรวม 7 วันถัดไป (รีเซตทุกวัน) รวมสถานะ <span class="text-yellow-400">Pending</span>, <span class="text-blue-400">Processing</span>, <span class="text-green-400">Confirmed</span>, <span class="text-emerald-400">Completed</span>
                    </p>
                </div>
            </div>
        </div>
    </div>

    <script>
    const ctx = document.getElementById('incomeChart');
    new Chart(ctx, {
        type: 'line',
        data: {
            labels: <?= json_encode($incomeLabels) ?>,
            datasets: [{
                data: <?= json_encode($incomeData) ?>,
                borderColor: '#D4AF37',
                backgroundColor: (context) => {
                    const chart = context.chart;
                    const {ctx, chartArea} = chart;
                    if (!chartArea) return null;
                    const gradient = ctx.createLinearGradient(0, chartArea.bottom, 0, chartArea.top);
                    gradient.addColorStop(0, 'rgba(212, 175, 55, 0)');
                    gradient.addColorStop(1, 'rgba(212, 175, 55, 0.2)');
                    return gradient;
                },
                fill: true,
                tension: 0.4,
                pointRadius: 4,
                pointBackgroundColor: '#D4AF37',
                borderWidth: 3
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: { legend: { display: false } },
            scales: {
                y: { beginAtZero: true, grid: { color: 'rgba(255,255,255,0.05)' }, ticks: { color: '#666' } },
                x: { grid: { display: false }, ticks: { color: '#666' } }
            }
        }
    });
    </script>
</body>
</html>