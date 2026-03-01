<?php
session_start();
require_once("../db.php");

$booking_code = $_GET['code'] ?? '';

if (empty($booking_code)) {
    header("Location: booking.php");
    exit();
}

/** * ปรับปรุง SQL: 
 * ดึงราคาจาก bookings (b.price) เป็นหลัก (ราคา ณ วันที่จอง)
 * และ Join กับ products (g) เพื่อเอาชื่อบริการล่าสุด
 */
$sql = "
    SELECT 
        b.booking_code,
        b.customer_name,
        b.customer_phone,
        b.booking_date,
        b.booking_time,
        b.status,
        b.price AS snapshot_price,
        COALESCE(g.product_name, b.product_name) AS product_name
    FROM bookings b
    LEFT JOIN products g ON b.product_id = g.product_id
    WHERE b.booking_code = ?
    LIMIT 1
";

$stmt = $conn->prepare($sql);
$stmt->bind_param("s", $booking_code);
$stmt->execute();
$result = $stmt->get_result();

if (!$booking = $result->fetch_assoc()) {
    die("ไม่พบข้อมูลการจอง");
}

// ดึงรายการคิวของผู้ใช้ (ถ้ามี session user_id)
$queue_items = [];
$current_position = null;
if (!empty($_SESSION['user_id'])) {
    $queue_stmt = $conn->prepare("SELECT booking_code, product_name, booking_date, booking_time, status, total_price FROM bookings WHERE user_id = ? ORDER BY booking_date ASC, booking_time ASC, booking_id ASC");
    $queue_stmt->bind_param("i", $_SESSION['user_id']);
    $queue_stmt->execute();
    $queue_result = $queue_stmt->get_result();
    $i = 0;
    while ($row = $queue_result->fetch_assoc()) {
        $i++;
        if ($row['booking_code'] === $booking_code && $current_position === null) {
            $current_position = $i;
        }
        $queue_items[] = $row;
    }
}
?>

<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <title>Booking Confirmed | Fanier</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Prompt:wght@300;400;600&display=swap" rel="stylesheet">
    <style>
        body {
            font-family: 'Prompt', sans-serif;
            color: #e5e5e5;
            background: radial-gradient(circle at 20% 20%, rgba(212, 175, 55, 0.08), transparent 35%),
                        radial-gradient(circle at 80% 10%, rgba(255, 255, 255, 0.06), transparent 30%),
                        #0a0a0a;
        }

        .receipt-card {
            background: radial-gradient(circle at top right, #1a1a1a, #151515);
            border: 1px solid rgba(212, 175, 55, 0.2);
        }

        .main-shell {
            max-width: 720px;
            width: 100%;
        }

        .gold-gradient {
            background: linear-gradient(135deg, #fdfc97, #d4af37, #b8860b);
        }

        .dashed-line {
            border-top: 2px dashed rgba(255,255,255,0.1);
        }

        .queue-card {
            max-height: 320px;
            overflow: auto;
            background: #0f0f0f;
            border: 1px solid rgba(255,255,255,0.05);
        }

        .queue-card::-webkit-scrollbar { width: 8px; }
        .queue-card::-webkit-scrollbar-track { background: rgba(255,255,255,0.04); border-radius: 999px; }
        .queue-card::-webkit-scrollbar-thumb { background: rgba(212, 175, 55, 0.5); border-radius: 999px; }
    </style>
</head>

<body class="min-h-screen flex items-center justify-center p-4">

    <div class="receipt-card main-shell w-full p-8 rounded-[30px] shadow-2xl relative overflow-hidden">
        <div class="absolute -top-10 -right-10 w-32 h-32 bg-[#d4af37] opacity-10 blur-3xl"></div>

        <div class="text-center mb-8">
            <div class="w-16 h-16 bg-green-500/20 text-green-500 rounded-full flex items-center justify-center mx-auto mb-4 border border-green-500/30">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-8 w-8" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7" />
                </svg>
            </div>
            <h1 class="text-2xl font-bold text-white mb-1" data-i18n-confirm="title">จองคิวสำเร็จ!</h1>
            <p class="text-gray-500 text-xs uppercase tracking-widest" data-i18n-confirm="subtitle">Fanier Beauty Style</p>
        </div>

        <div class="space-y-4">
            <div class="bg-black/30 p-4 rounded-2xl border border-white/5">
                <p class="text-[10px] text-[#D4AF37] uppercase tracking-widest mb-1" data-i18n-confirm="service_label">Service Selected</p>
                <h2 class="text-lg font-semibold text-white"><?= htmlspecialchars($booking['product_name']) ?></h2>
            </div>

            <div class="grid grid-cols-2 gap-4 text-sm">
                <div class="space-y-2">
                    <p class="text-[10px] text-gray-500 uppercase" data-i18n-confirm="customer">Customer</p>
                    <p class="text-white"><?= htmlspecialchars($booking['customer_name']) ?></p>

                    <p class="text-[10px] text-gray-500 uppercase" data-i18n-confirm="phone">Phone</p>
                    <p class="text-white"><?= htmlspecialchars($booking['customer_phone']) ?></p>
                </div>
                <div class="space-y-2 text-right">
                    <p class="text-[10px] text-gray-500 uppercase" data-i18n-confirm="date">Date</p>
                    <p class="text-white"><?= date('d M Y', strtotime($booking['booking_date'])) ?></p>

                    <p class="text-[10px] text-gray-500 uppercase" data-i18n-confirm="time">Time</p>
                    <p class="text-white"><span id="time-text"><?= substr($booking['booking_time'], 0, 5) ?></span> <span id="time-suffix" data-i18n-confirm="time_suffix">น.</span></p>
                </div>
            </div>


            <div class="dashed-line my-6"></div>

            <div class="flex justify-between items-end">
                <div>
                    <p class="text-[10px] text-gray-500 uppercase" data-i18n-confirm="total">Total Price</p>
                    <p class="text-xs text-gray-400" data-i18n-confirm="pay_on_site">ชำระที่หน้าร้าน</p>
                </div>
                <div class="text-right">
                    <span class="text-3xl font-bold bg-gradient-to-r from-[#fdfc97] to-[#d4af37] bg-clip-text text-transparent">
                        ฿<?= number_format($booking['snapshot_price']) ?>
                    </span>
                </div>
            </div>

            <div class="mt-8 bg-white p-6 rounded-2xl text-center relative">
                <div class="absolute -left-3 top-1/2 -translate-y-1/2 w-6 h-6 bg-[#151515] rounded-full border-r border-white/5"></div>
                <div class="absolute -right-3 top-1/2 -translate-y-1/2 w-6 h-6 bg-[#151515] rounded-full border-l border-white/5"></div>
                
                <p class="text-[10px] text-gray-400 uppercase tracking-[3px] mb-1" data-i18n-confirm="code">Booking Code</p>
                <p class="text-3xl font-black text-black tracking-tighter">
                    <?= $booking['booking_code'] ?>
                </p>
                <div class="mt-2 flex justify-center items-center gap-2">
                    <span class="w-2 h-2 rounded-full bg-yellow-500"></span>
                    <p class="text-[10px] text-gray-500 font-bold uppercase" data-i18n-confirm="status"><?= $booking['status'] ?></p>
                </div>
            </div>

            <div class="mt-6 text-center space-y-2">
                <div class="inline-flex items-center gap-2 px-3 py-2 rounded-full bg-white/5 text-sm text-gray-100">
                    <span data-i18n-confirm="your_status">สถานะของคุณ</span>
                    <span class="px-2 py-1 rounded-full bg-white/10 text-[12px] uppercase tracking-tight text-white"><?= htmlspecialchars($booking['status']) ?></span>
                </div>
                
            </div>
        </div>

        <div class="flex gap-3 mt-8">
            <a href="recommended.php" class="flex-[2] text-center py-4 rounded-2xl font-bold text-black gold-gradient shadow-lg shadow-[#D4AF37]/20 hover:brightness-110 transition-all active:scale-95" data-i18n-confirm="back_home">
                กลับหน้าหลัก
            </a>
        </div>

        <p class="text-center text-gray-600 text-[10px] mt-6 italic" data-i18n-confirm="note">กรุณาแสดงรหัสนี้เมื่อมาถึงหน้าร้าน</p>
    </div>

    <script>
        (function() {
            const dict = {
                th: {
                    'title': 'จองคิวสำเร็จ!',
                    'subtitle': 'Fanier Beauty Style',
                    'service_label': 'บริการที่เลือก',
                    'customer': 'ลูกค้า',
                    'phone': 'เบอร์โทร',
                    'date': 'วันที่',
                    'time': 'เวลา',
                    'time_suffix': 'น.',
                    'total': 'ยอดรวม',
                    'pay_on_site': 'ชำระที่หน้าร้าน',
                    'code': 'รหัสจอง',
                    'status': 'สถานะ',
                    'back_home': 'กลับหน้าหลัก',
                    'note': 'กรุณาแสดงรหัสนี้เมื่อมาถึงหน้าร้าน',
                    'page_title': 'ยืนยันการจอง | Fanier',
                    'queue_title': 'คิวทั้งหมดของคุณ',
                    'queue_hint': 'อัปเดตล่าสุด',
                    'queue_code': 'รหัส',
                    'queue_no': 'ลำดับ',
                    'queue_service': 'บริการ',
                    'queue_date': 'วันที่',
                    'queue_time': 'เวลา',
                    'queue_status': 'สถานะ',
                    'queue_price': 'ยอด',
                    'your_status': 'สถานะของคุณ',
                    'your_position': 'คุณอยู่คิวลำดับที่'
                },
                en: {
                    'title': 'Booking Confirmed!',
                    'subtitle': 'Fanier Beauty Style',
                    'service_label': 'Service Selected',
                    'customer': 'Customer',
                    'phone': 'Phone',
                    'date': 'Date',
                    'time': 'Time',
                    'time_suffix': 'hrs',
                    'total': 'Total Price',
                    'pay_on_site': 'Pay at the store',
                    'code': 'Booking Code',
                    'status': 'Status',
                    'back_home': 'Back to Home',
                    'note': 'Please show this code upon arrival',
                    'page_title': 'Booking Confirmed | Fanier',
                    'queue_title': 'Your bookings',
                    'queue_hint': 'Latest update',
                    'queue_code': 'Code',
                    'queue_no': 'No.',
                    'queue_service': 'Service',
                    'queue_date': 'Date',
                    'queue_time': 'Time',
                    'queue_status': 'Status',
                    'queue_price': 'Total',
                    'your_status': 'Your status',
                    'your_position': 'Your queue position'
                }
            };

            function applyLang(lang) {
                const locale = dict[lang] ? lang : 'th';
                const t = dict[locale];
                document.documentElement.setAttribute('lang', locale);
                document.title = t['page_title'];

                document.querySelectorAll('[data-i18n-confirm]').forEach(el => {
                    const key = el.getAttribute('data-i18n-confirm');
                    if (t[key]) el.textContent = t[key];
                    if (key === 'your_position' && el.dataset.pos) {
                        const pos = el.dataset.pos;
                        if (t[key]) {
                            el.textContent = `${t[key]} ${pos}`;
                        }
                    }
                });

                // Preserve the time value while swapping suffix
                const timeText = document.getElementById('time-text');
                const timeSuffix = document.getElementById('time-suffix');
                if (timeText && timeSuffix && t['time_suffix']) {
                    timeSuffix.textContent = t['time_suffix'];
                }
            }

            document.addEventListener('DOMContentLoaded', () => {
                const lang = localStorage.getItem('app_lang') || 'th';
                applyLang(lang);

                window.addEventListener('storage', (e) => {
                    if (e.key === 'app_lang') {
                        applyLang(e.newValue || 'th');
                    }
                });
            });
        })();
    </script>

<?php
if (isset($queue_stmt)) {
    $queue_stmt->close();
}
$stmt->close();
$conn->close();
?>
</body>
</html>
