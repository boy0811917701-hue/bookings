<?php
require_once("../db.php"); 

// ดึงชื่อสินค้าและจำนวนที่ถูกจอง (ตัดรายการที่เป็นค่าว่าง หรือ NULL ออก)
$product_query = "
    SELECT 
        product_name, 
        SUM(quantity) as total_qty 
    FROM bookings 
    WHERE product_name IS NOT NULL 
      AND product_name != '' 
      AND product_name != ' '
    GROUP BY product_name 
    ORDER BY total_qty DESC
    LIMIT 10
";
$product_res = $conn->query($product_query);

$p_labels = []; 
$p_counts = [];

if($product_res) {
    while($row = $product_res->fetch_assoc()){
        $p_labels[] = $row['product_name'];
        $p_counts[] = (int)$row['total_qty'];
    }
}
?>

<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>สถิติสินค้า | Fanier Admin</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Prompt:wght@300;400;600&display=swap" rel="stylesheet">
    <style>
        body {
            font-family: 'Prompt', sans-serif;
            background: radial-gradient(circle at 20% 20%, rgba(212, 175, 55, 0.08), transparent 35%),
                        radial-gradient(circle at 80% 10%, rgba(255, 255, 255, 0.05), transparent 30%),
                        #0a0a0a;
            color: #e5e5e5;
        }
    </style>
</head>
<body class="min-h-screen pb-10">
    <?php include '../bar.php'; ?>

    <div class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8 pt-10 space-y-6">
        <div class="flex flex-wrap items-center justify-between gap-3">
            <div>
                <p class="text-xs uppercase tracking-[4px] text-gray-500">Analytics</p>
                <h1 class="text-2xl font-bold text-white">สถิติการจองแยกตามบริการ</h1>
                <p class="text-sm text-gray-400 mt-1">10 อันดับบริการที่ถูกจองมากที่สุด</p>
            </div>
            <div class="flex gap-2">
                <a href="dashboard.php" class="px-4 py-2 rounded-xl border border-white/10 text-sm text-white hover:bg-white/10 transition">ย้อนกลับ รายละเอียด</a>
                <a href="index.php" class="px-4 py-2 rounded-xl bg-[#D4AF37] text-black font-semibold shadow-lg shadow-[#D4AF37]/30 hover:brightness-110 transition">ไปหน้าออเดอร์</a>
            </div>
        </div>

        <div class="bg-[#0f0f0f] border border-white/10 rounded-2xl p-6 shadow-2xl">
            <?php if (empty($p_labels)): ?>
                <div class="text-center text-gray-500 py-16">ยังไม่มีข้อมูลการจองเพียงพอสำหรับแสดงสถิติ</div>
            <?php else: ?>
                <div class="flex items-center justify-between mb-4">
                    <h2 class="text-lg font-semibold text-white">Top Services</h2>
                    <span class="text-xs text-gray-500">แสดงสูงสุด 10 รายการ</span>
                </div>
                <div class="relative h-[420px]">
                    <canvas id="productChart"></canvas>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script>
    const labels = <?= json_encode($p_labels) ?>;
    const dataPoints = <?= json_encode($p_counts) ?>;

    if (labels.length) {
        const palette = ['#D4AF37','#A9A9A9','#CD7F32','#4A90E2','#50E3C2','#B8E986','#BD10E0','#F5A623','#E2CF44','#9B9B9B'];
        const colors = labels.map((_, i) => palette[i % palette.length]);

        const ctxP = document.getElementById('productChart').getContext('2d');
        new Chart(ctxP, {
            type: 'bar',
            data: {
                labels: labels,
                datasets: [{
                    label: 'จำนวนที่ถูกจอง',
                    data: dataPoints,
                    backgroundColor: colors,
                    borderRadius: 8,
                    borderWidth: 0
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { display: false },
                    tooltip: {
                        backgroundColor: '#1a1a1a',
                        titleColor: '#D4AF37',
                        padding: 12
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        grid: { color: 'rgba(255, 255, 255, 0.05)' },
                        ticks: { color: '#999', stepSize: 1 }
                    },
                    x: {
                        grid: { display: false },
                        ticks: { 
                            color: '#D4AF37', 
                            font: { size: 12, weight: '500' },
                            padding: 8
                        }
                    }
                }
            }
        });
    }
    </script>
</body>
</html>