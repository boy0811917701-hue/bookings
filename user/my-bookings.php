<?php
session_start();
include '../db.php'; 
function dateThai($strDate) {
    $strYear = date("Y", strtotime($strDate)) + 543; // เปลี่ยนเป็น พ.ศ.
    $strMonth = date("n", strtotime($strDate));
    $strDay = date("j", strtotime($strDate));
    $strMonthCut = Array("", "ม.ค.", "ก.พ.", "มี.ค.", "เม.ย.", "พ.ค.", "มิ.ย.", "ก.ค.", "ส.ค.", "ก.ย.", "ต.ค.", "พ.ย.", "ธ.ค.");
    $strMonthThai = $strMonthCut[$strMonth];
    return "$strDay $strMonthThai $strYear";
}
// 1. ตรวจสอบการ Login
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

// 2. ดึงค่าจาก Session (ใช้ NULL Coalescing เพื่อความปลอดภัย)
$user_phone = $_SESSION['phone'] ?? '';
$username = $_SESSION['username'] ?? 'User';

// ถ้าไม่มีเบอร์ใน Session ให้แจ้งเตือน (สาเหตุส่วนใหญ่มาจากการลืมเก็บค่าตอน Login)
if (empty($user_phone)) {
    die("<div style='color:white; background:#222; padding:20px; text-align:center; font-family:sans-serif;'>
            <h2>ไม่พบข้อมูลเบอร์โทรศัพท์</h2>
            <p>กรุณา <a href='logout.php' style='color:gold;'>ออกจากระบบ</a> แล้วเข้าใหม่อีกครั้ง</p>
         </div>");
}

$bookings = [];

// 3. เตรียม SQL Query (ดึงข้อมูลการจองที่ผูกกับเบอร์โทรนี้)
$bookings = [];

$sql = "
    SELECT 
        booking_id,
        booking_code,
        customer_name,
        customer_phone,
        booking_date,
        booking_time,
        status,
        created_at,
        product_id,
        source,
        product_name,
        price,
        product_source,
        user_id
    FROM bookings
    WHERE customer_phone = ?
    ORDER BY created_at DESC
";

$stmt = $conn->prepare($sql);
if (!$stmt) {
    die("SQL Error: " . $conn->error);
}

$stmt->bind_param("s", $user_phone);
$stmt->execute();
$result = $stmt->get_result();

while ($row = $result->fetch_assoc()) {
    $bookings[] = $row;
}

$stmt->close();
?>

<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>รายการจองของฉัน - Fanier Beauty Style</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="../css/bar_menu.css">
    <style>
        body { background: #0a0a0a; color: #d4af37; font-family: 'Inter', sans-serif; }
        .booking-card { background: #151515; border: 1px solid #222; transition: all 0.3s ease; }
        .booking-card:hover { border-color: #d4af37; transform: translateY(-2px); }
        .gold-gradient { background: linear-gradient(45deg, #d4af37, #fdfc97); -webkit-background-clip: text; -webkit-text-fill-color: transparent; }
    </style>
</head>
<body class="p-6 min-h-screen">
    <?php include '../bar_menu.php'; ?>
    <div class="max-w-2xl mx-auto">
        <div class="flex justify-between items-end mb-10 border-b border-zinc-800 pb-4">
            <div>
                <h1 class="text-3xl font-bold gold-gradient italic">My Bookings</h1>
                <p class="text-gray-500 text-xs mt-1 uppercase tracking-widest">ประวัติการรับบริการของคุณ</p>
            </div>
            <div class="text-right">
                <p class="text-gray-400 text-sm">สวัสดี, <span class="text-white"><?= htmlspecialchars($username) ?></span></p>
                <p class="text-[10px] text-zinc-600"><?= htmlspecialchars($user_phone) ?></p>
            </div>
        </div>

        <?php if (empty($bookings)): ?>
            <div class="text-center p-12 bg-zinc-900/50 border border-zinc-800 rounded-2xl">
                <svg class="mx-auto h-12 w-12 text-zinc-700 mb-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10" />
                </svg>
                <p class="text-gray-500 mb-6">คุณยังไม่มีรายการจองในขณะนี้</p>
                <a href="booking.php" class="inline-block bg-yellow-600 text-black font-bold px-8 py-3 rounded-full hover:bg-yellow-500 transition">
                    จองบริการเลย
                </a>
            </div>
        <?php else: ?>
            <div class="grid gap-5">
                <?php foreach ($bookings as $row): ?>
                    <div class="booking-card p-6 rounded-2xl shadow-2xl border-l-4 <?= $row['status'] == 'pending' ? 'border-l-orange-500' : 'border-l-green-500' ?>">
                        <div class="flex justify-between items-start">
                            <div>
                                <span class="text-[10px] font-mono text-zinc-500">ID: <?= $row['booking_code'] ?></span>
                                <h2 class="text-xl font-bold text-white mt-1"><?= htmlspecialchars($row['product_name']) ?></h2>
                            </div>
                            <div class="text-right">
                                <span class="px-3 py-1 rounded-full text-[10px] font-bold uppercase tracking-tighter <?= $row['status'] == 'pending' ? 'bg-orange-500/10 text-orange-500' : 'bg-green-500/10 text-green-500' ?>">
                                    <?= $row['status'] == 'pending' ? 'รอรับบริการ' : 'สำเร็จแล้ว' ?>
                                </span>
                            </div>
                        </div>
                        
                       <div class="mt-6 flex flex-wrap gap-4 text-sm border-t border-zinc-800/50 pt-4">
    <div class="flex items-center text-zinc-400">
        <span class="mr-2">📅</span> 
        <?= dateThai($row['booking_date']) ?>
    </div>
    
    <div class="flex items-center text-zinc-400">
        <span class="mr-2">⏰</span> 
        <?= date('H:i', strtotime($row['booking_time'])) ?> น.
    </div>

    <div class="ml-auto text-xl font-black text-white">
        <span class="text-xs font-normal text-zinc-500 mr-1">฿</span>
        <?= number_format($row['price']) ?>
    </div>
</div>
                        
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
        
        
    </div>
</body>
</html>