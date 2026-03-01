<?php
session_start();
require_once("../db.php");

if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// 1. ตรวจสอบ Login
if (!isset($_SESSION['user_id'])) {
    header("Location: ../login.php");
    exit();
}

$user_id = $_SESSION['user_id'];

// รับค่า product_id จาก URL (กรณีมาจากหน้า Recommended)
$target_product_id = isset($_GET['product_id']) ? (int) $_GET['product_id'] : 0;

// ดึงข้อมูลผู้ใช้
$customer_phone = "";
$customer_name = "";
$message = "";
$user_query = $conn->prepare("SELECT username, phone FROM users WHERE id = ? LIMIT 1");
$user_query->bind_param("i", $user_id);
$user_query->execute();
$user_result = $user_query->get_result();
if ($user_row = $user_result->fetch_assoc()) {
    $customer_name = $user_row['username'];
    $customer_phone = $user_row['phone'];
}

// ดึงรายการบริการ (เอาเฉพาะที่สถานะเป็น active)
$products_result = $conn->query("SELECT product_id, product_name, price, display_type FROM products WHERE status = 'active' ORDER BY product_name ASC");
$has_products = ($products_result && $products_result->num_rows > 0);

// ดึงคิวของผู้ใช้ที่จองไว้เรียงตามวันเวลา
$queue_stmt = $conn->prepare("SELECT booking_code, product_name, booking_date, booking_time, status, total_price FROM bookings WHERE user_id = ? ORDER BY booking_date ASC, booking_time ASC, booking_id ASC");
$queue_stmt->bind_param("i", $user_id);
$queue_stmt->execute();
$queue_result = $queue_stmt->get_result();
$queue_items = [];
if ($queue_result && $queue_result->num_rows > 0) {
    $pos = 0;
    while ($row = $queue_result->fetch_assoc()) {
        $pos++;
        $row['queue_pos'] = $pos;
        $queue_items[] = $row;
    }
}
$has_queue = !empty($queue_items);
$user_queue_next = count($queue_items) + 1;

// จัดการการส่งข้อมูล (POST)
if ($_SERVER["REQUEST_METHOD"] == "POST" && $has_products) {
    if (!hash_equals($_SESSION['csrf_token'] ?? '', $_POST['csrf_token'] ?? '')) {
        $message = "<div class='bg-red-900/30 text-red-400 border border-red-500/50 p-3 rounded-xl text-sm flex items-center gap-2'>❌ <span data-i18n-booking-msg='invalid_request'>คำขอไม่ถูกต้อง</span></div>";
    } else {
        $p_name = trim($conn->real_escape_string($_POST['customer_name']));
        $p_phone = $customer_phone;
        $p_id = (int) ($_POST['product_id'] ?? 0);
        $booking_date = $_POST['booking_date'];
        $booking_time = $_POST['booking_time'];
        $qty = 1;

        if (empty($p_name) || empty($booking_date) || empty($booking_time)) {
            $message = "<div class='bg-red-900/30 text-red-400 border border-red-500/50 p-3 rounded-xl text-sm flex items-center gap-2'>❌ <span data-i18n-booking-msg='missing_fields'>ข้อมูลไม่ครบถ้วน</span></div>";
        } else {
            // ตรวจสอบเวลาเปิดทำการฝั่งเซิร์ฟเวอร์ (08:00 - 22:59)
            $time_ts = strtotime($booking_time);
            $hour = (int)date('G', $time_ts);
            if ($time_ts === false || $hour < 8 || $hour >= 23) {
                $message = "<div class='bg-red-900/30 text-red-400 border border-red-500/50 p-3 rounded-xl text-sm flex items-center gap-2'>❌ <span data-i18n-booking-msg='time_out_of_range'>เวลานอกช่วงให้บริการ (08:00 - 22:59)</span></div>";
            } else {
                $check_stmt = $conn->prepare("SELECT product_name, price, display_type FROM products WHERE product_id = ?");
                $check_stmt->bind_param("i", $p_id);
                $check_stmt->execute();
                $res_check = $check_stmt->get_result();

                if ($item = $res_check->fetch_assoc()) {
                    $final_price = $item['price'];
                    $final_product_name = $item['product_name'];
                    $final_source = ($item['display_type'] == 'recommended') ? 'recommended' : 'standard';
                    $total_price = $final_price * $qty;

                        // บันทึกคิวใหม่ โดยเพิ่ม created_at ให้ฐานข้อมูลมีเวลาบันทึกไว้ด้วย
                        $sql = "INSERT INTO bookings 
                            (booking_code, customer_name, customer_phone, product_id, product_name, price, quantity, total_price, source, booking_date, booking_time, user_id, status, created_at) 
                            VALUES ('TEMP', ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'pending', NOW())";

                    $stmt = $conn->prepare($sql);
                    $stmt->bind_param(
                        "ssisdidsssi",
                        $p_name,
                        $p_phone,
                        $p_id,
                        $final_product_name,
                        $final_price,
                        $qty,
                        $total_price,
                        $final_source,
                        $booking_date,
                        $booking_time,
                        $user_id
                    );

                    if ($stmt->execute()) {
                        $last_id = $conn->insert_id;
                        $new_booking_code = "A-" . str_pad($last_id, 2, "0", STR_PAD_LEFT);

                        $update_stmt = $conn->prepare("UPDATE bookings SET booking_code = ? WHERE booking_id = ?");
                        $update_stmt->bind_param("si", $new_booking_code, $last_id);
                        $update_stmt->execute();

                        header("Location: confirm.php?code=" . urlencode($new_booking_code));
                        exit();
                    } else {
                        $message = "<div class='bg-red-900/30 text-red-400 border border-red-500/50 p-3 rounded-xl text-sm flex items-center gap-2'>❌ <span>เกิดข้อผิดพลาดในการบันทึกคิว: " . htmlspecialchars($stmt->error) . "</span></div>";
                    }
                } else {
                    $message = "<div class='bg-red-900/30 text-red-400 border border-red-500/50 p-3 rounded-xl text-sm flex items-center gap-2'>❌ <span data-i18n-booking-msg='product_not_found'>ไม่พบบริการที่เลือก</span></div>";
                }
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="th">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>จองคิวบริการ | Fanier</title>
    <link rel="stylesheet" href="../css/bar_menu.css">
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Prompt:wght@300;400;600&display=swap" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <style>
        body {
            font-family: 'Prompt', sans-serif;
            background: #0a0a0a;
            color: #e5e5e5;
        }

        .gold-glow:focus {
            box-shadow: 0 0 15px rgba(212, 175, 55, 0.4);
            border-color: #D4AF37;
        }

        .btn-gold {
            background: linear-gradient(135deg, #fdfc97 0%, #d4af37 50%, #b8860b 100%);
            box-shadow: 0 4px 15px rgba(184, 134, 11, 0.4);
        }
    </style>
</head>

<body class="min-h-screen bg-[#0a0a0a]" data-next-pos="<?= (int)$user_queue_next ?>">
    <?php include '../bar_menu.php'; ?>

    <div class="container mx-auto px-4 py-12 flex justify-center">
        <div class="bg-[#151515] border border-white/5 p-8 rounded-[30px] w-full max-w-lg shadow-2xl">
            <div class="text-center mb-10">
                <h1
                    class="text-3xl font-bold bg-gradient-to-r from-[#d4af37] via-[#fdfc97] to-[#d4af37] bg-clip-text text-transparent"
                    data-i18n-booking="booking.title">
                    Reservation
                </h1>
                <div class="h-1 w-20 bg-[#d4af37] mx-auto mt-2 rounded-full"></div>
                <p class="text-gray-500 mt-4 text-[10px] uppercase tracking-[4px]" data-i18n-booking="booking.subtitle">Fanier Beauty Style</p>
            </div>

            <?php if (!empty($message)): ?>
                <div class="mb-6" id="booking-message"><?= $message ?></div>
            <?php endif; ?>

            <?php if (!$has_products): ?>
                <div class="text-center py-10 text-gray-500 border border-dashed border-white/10 rounded-2xl" data-i18n-booking-msg="no_products">
                    ไม่พบรายการบริการในขณะนี้
                </div>
            <?php else: ?>
                <form action="" method="POST" class="space-y-6" onsubmit="return confirmBooking(event);">
                    <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">

                    <div class="space-y-2">
                        <label class="text-[11px] text-[#D4AF37] uppercase tracking-tighter ml-1" data-i18n-booking="booking.customer_name">ชื่อผู้ใช้</label>
                        <input type="text" name="customer_name" required value="<?= htmlspecialchars($customer_name) ?>"
                            class="w-full bg-black/50 border border-white/10 text-white px-5 py-4 rounded-2xl gold-glow outline-none transition-all">
                    </div>

                    <div class="space-y-2 opacity-60">
                        <label class="text-[11px] text-[#D4AF37] uppercase tracking-tighter ml-1" data-i18n-booking="booking.phone">Phone Number</label>
                        <input type="text" value="<?= htmlspecialchars($customer_phone) ?>" readonly
                            class="w-full bg-white/5 border border-white/5 text-gray-400 px-5 py-4 rounded-2xl cursor-not-allowed">
                    </div>

                    <div class="space-y-2">
                        <label class="text-[11px] text-[#D4AF37] uppercase tracking-tighter ml-1" data-i18n-booking="booking.service">สินค้า</label>
                        <select name="product_id" required
                            class="w-full bg-black/50 border border-white/10 text-white px-5 py-4 rounded-2xl gold-glow outline-none transition-all appearance-none">
                            <option value="" id="service-placeholder">-- เลือกบริการ --</option>
                            <?php
                            $products_result->data_seek(0);
                            while ($row = $products_result->fetch_assoc()):
                                // เช็คว่า product_id ตรงกับที่ส่งมาจากหน้า Recommended หรือไม่
                                $selected = ($row['product_id'] == $target_product_id) ? 'selected' : '';
                                ?>
                                <option value="<?= $row['product_id'] ?>" <?= $selected ?>
                                    data-product-name="<?= htmlspecialchars($row['product_name'], ENT_QUOTES) ?>"
                                    data-product-price="<?= number_format($row['price']) ?>">
                                    <?= htmlspecialchars($row['product_name']) ?> — ฿<?= number_format($row['price']) ?>
                                </option>
                            <?php endwhile; ?>
                        </select>
                    </div>

                    <div class="grid grid-cols-2 gap-4">
                        <div class="space-y-2">
                            <label class="text-[11px] text-[#D4AF37] uppercase tracking-tighter ml-1" data-i18n-booking="booking.date">วัน/เดือน/ปี</label>
                            <input type="date" name="booking_date" required min="<?= date('Y-m-d') ?>"
                                class="w-full bg-black/50 border border-white/10 text-white px-4 py-4 rounded-2xl gold-glow outline-none [color-scheme:dark]">
                        </div>
                        <div class="space-y-2">
                            <label class="text-[11px] text-[#D4AF37] uppercase tracking-tighter ml-1" data-i18n-booking="booking.time">เวลา</label>
                            <input type="time" name="booking_time" id="booking_time" required
                                class="w-full bg-black/50 border border-white/10 text-white px-4 py-4 rounded-2xl gold-glow outline-none [color-scheme:dark]">
                        </div>
                    </div>

                    <p id="time_display" class="text-center text-[11px] text-gray-500 italic min-h-[1rem]"></p>

                    <button type="submit"
                        class="btn-gold w-full py-5 rounded-2xl font-bold text-black uppercase tracking-widest hover:scale-[1.02] active:scale-95 transition-all mt-4"
                        data-i18n-booking="booking.cta">
                        Confirm Reservation
                    </button>
                </form>
            <?php endif; ?>
        </div>
    </div>

    <div class="container mx-auto px-4 pb-12 flex justify-center">
        <div class="bg-[#151515] border border-white/5 p-6 rounded-[24px] w-full max-w-4xl shadow-2xl">
            <div class="flex items-center justify-between mb-4">
                <h2 class="text-xl font-semibold text-white" data-i18n-booking="queue.title">คิวที่คุณจอง</h2>
                <span class="text-xs text-gray-500" data-i18n-booking="queue.hint">อัปเดตอัตโนมัติ</span>
            </div>

            <?php if ($has_queue): ?>
                <div class="overflow-x-auto">
                    <table class="min-w-full text-sm text-left">
                        <thead class="text-gray-400 uppercase text-[11px] tracking-wide">
                            <tr class="border-b border-white/5">
                                <th class="py-2 pr-4" data-i18n-booking="queue.no">ลำดับ</th>
                                <th class="py-2 pr-4" data-i18n-booking="queue.code">รหัส</th>
                                <th class="py-2 pr-4" data-i18n-booking="queue.service">บริการ</th>
                                <th class="py-2 pr-4" data-i18n-booking="queue.date">วันที่</th>
                                <th class="py-2 pr-4" data-i18n-booking="queue.time">เวลา</th>
                                <th class="py-2 pr-4" data-i18n-booking="queue.status">สถานะ</th>
                                <th class="py-2 pr-4 text-right" data-i18n-booking="queue.price">ยอด</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-white/5">
                            <?php foreach ($queue_items as $q): ?>
                                <tr class="hover:bg-white/5 transition-colors">
                                    <td class="py-3 pr-4 text-gray-400 font-semibold">#<?= $q['queue_pos'] ?></td>
                                    <td class="py-3 pr-4 font-semibold text-white"><?= htmlspecialchars($q['booking_code']) ?></td>
                                    <td class="py-3 pr-4 text-gray-200"><?= htmlspecialchars($q['product_name']) ?></td>
                                    <td class="py-3 pr-4 text-gray-300"><?= htmlspecialchars(date('Y-m-d', strtotime($q['booking_date']))) ?></td>
                                    <td class="py-3 pr-4 text-gray-300"><?= htmlspecialchars(substr($q['booking_time'],0,5)) ?></td>
                                    <td class="py-3 pr-4">
                                        <span class="px-2 py-1 rounded-full text-[12px] uppercase tracking-tight bg-white/5 text-gray-200"><?= htmlspecialchars($q['status']) ?></span>
                                    </td>
                                    <td class="py-3 pr-4 text-right text-gray-100">฿<?= number_format($q['total_price']) ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php else: ?>
                <div class="text-center py-8 text-gray-500 border border-dashed border-white/10 rounded-2xl" data-i18n-booking="queue.empty">
                    ยังไม่มีคิวที่จอง
                </div>
            <?php endif; ?>
        </div>
    </div>

    <script>
        // Local i18n for this page (synced with bar_menu language selection)
        (function () {
            const bookingDict = {
                th: {
                    'booking.title': 'จองคิว',
                    'booking.subtitle': 'Fanier Beauty Style',
                    'booking.customer_name': 'ชื่อลูกค้า',
                    'booking.phone': 'เบอร์โทรศัพท์',
                    'booking.service': 'เลือกบริการ',
                    'booking.servicePlaceholder': '-- เลือกบริการ --',
                    'booking.date': 'วันที่',
                    'booking.time': 'เวลา',
                    'booking.cta': 'ยืนยันการจอง',
                    'booking.no_products': 'ไม่พบรายการบริการในขณะนี้',
                    'queue.title': 'คิวที่คุณจอง',
                    'queue.hint': 'อัปเดตอัตโนมัติ',
                    'queue.code': 'รหัส',
                    'queue.no': 'ลำดับ',
                    'queue.service': 'บริการ',
                    'queue.date': 'วันที่',
                    'queue.time': 'เวลา',
                    'queue.status': 'สถานะ',
                    'queue.price': 'ยอด',
                    'queue.empty': 'ยังไม่มีคิวที่จอง',
                    'summary.title': 'ตรวจสอบข้อมูลก่อนยืนยัน',
                    'summary.service': 'บริการ',
                    'summary.date': 'วันที่',
                    'summary.time': 'เวลา',
                    'summary.name': 'ชื่อ',
                    'summary.phone': 'เบอร์โทร',
                    'summary.price': 'ราคา',
                    'summary.queue': 'คิวของคุณ (หลังบันทึก)',
                    'msg.invalid_request': 'คำขอไม่ถูกต้อง',
                    'msg.missing_fields': 'ข้อมูลไม่ครบถ้วน',
                    'msg.time_out_of_range': 'เวลานอกช่วงให้บริการ (08:00 - 22:59)',
                    'msg.product_not_found': 'ไม่พบบริการที่เลือก',
                    'msg.time_prompt': 'กรุณาเลือกเวลา...',
                    'msg.time_invalid_title': 'นอกเวลาทำการ',
                    'msg.time_invalid_text': 'ร้านเปิดให้บริการเวลา 08:00 - 23:00 น.',
                    'msg.time_invalid_hint': '❌ เลือกเวลาใหม่ (08:00-22:59)',
                    'msg.time_label_prefix': 'เวลา',
                    'msg.confirm_title': 'ยืนยันการจอง',
                    'msg.confirm_text': 'ต้องการยืนยันการจองนี้หรือไม่?',
                    'msg.confirm_yes': 'ยืนยัน',
                    'msg.confirm_no': 'ยกเลิก'
                },
                en: {
                    'booking.title': 'Reservation',
                    'booking.subtitle': 'Fanier Beauty Style',
                    'booking.customer_name': 'Customer Name',
                    'booking.phone': 'Phone Number',
                    'booking.service': 'Select Service',
                    'booking.servicePlaceholder': '-- Select Service --',
                    'booking.date': 'Date',
                    'booking.time': 'Time',
                    'booking.cta': 'Confirm Reservation',
                    'booking.no_products': 'No services available right now',
                    'queue.title': 'Your bookings',
                    'queue.hint': 'Auto-updated',
                    'queue.code': 'Code',
                    'queue.no': 'No.',
                    'queue.service': 'Service',
                    'queue.date': 'Date',
                    'queue.time': 'Time',
                    'queue.status': 'Status',
                    'queue.price': 'Total',
                    'queue.empty': 'No bookings yet',
                    'summary.title': 'Review your booking',
                    'summary.service': 'Service',
                    'summary.date': 'Date',
                    'summary.time': 'Time',
                    'summary.name': 'Name',
                    'summary.phone': 'Phone',
                    'summary.price': 'Price',
                    'summary.queue': 'Your queue position (after save)',
                    'msg.invalid_request': 'Invalid request',
                    'msg.missing_fields': 'Please fill in all required fields',
                    'msg.time_out_of_range': 'Selected time is outside service hours (08:00 - 22:59)',
                    'msg.product_not_found': 'Selected service not found',
                    'msg.time_prompt': 'Please select a time...',
                    'msg.time_invalid_title': 'Outside service hours',
                    'msg.time_invalid_text': 'Our shop operates from 08:00 to 23:00.',
                    'msg.time_invalid_hint': '❌ Please pick a time between 08:00-22:59',
                    'msg.time_label_prefix': 'Time',
                    'msg.confirm_title': 'Confirm reservation',
                    'msg.confirm_text': 'Do you want to confirm this reservation?',
                    'msg.confirm_yes': 'Confirm',
                    'msg.confirm_no': 'Cancel'
                }
            };

            let currentLang = 'th';

            function getDict(langCode) {
                return bookingDict[langCode] || bookingDict.th;
            }

            function applyBookingLanguage(langCode) {
                currentLang = bookingDict[langCode] ? langCode : 'th';
                const dict = getDict(currentLang);

                document.documentElement.setAttribute('lang', currentLang);

                document.querySelectorAll('[data-i18n-booking]').forEach(el => {
                    const key = el.getAttribute('data-i18n-booking');
                    if (dict[key]) {
                        el.textContent = dict[key];
                    }
                });

                const placeholder = document.getElementById('service-placeholder');
                if (placeholder && dict['booking.servicePlaceholder']) {
                    placeholder.textContent = dict['booking.servicePlaceholder'];
                }

                document.querySelectorAll('[data-i18n-booking-msg]').forEach(el => {
                    const key = el.getAttribute('data-i18n-booking-msg');
                    const msgKey = key.startsWith('msg.') ? key : `msg.${key}`;
                    if (dict[msgKey]) {
                        el.textContent = dict[msgKey];
                    }
                });

                const emptyState = document.querySelector('[data-i18n-booking-msg="no_products"]');
                if (emptyState && dict['booking.no_products']) {
                    emptyState.textContent = dict['booking.no_products'];
                }

                updateTimeDisplay(document.getElementById('booking_time')?.value || '');
            }

            function updateTimeDisplay(timeValue) {
                const dict = getDict(currentLang);
                const display = document.getElementById('time_display');
                if (!display) return;

                if (!timeValue) {
                    display.innerHTML = `<i class="far fa-clock mr-1"></i> ${dict['msg.time_prompt']}`;
                    return;
                }

                const [hoursStr, minutesStr] = timeValue.split(':');
                const hours = Number(hoursStr);
                const minutes = Number(minutesStr);

                if (Number.isNaN(hours) || Number.isNaN(minutes) || hours < 8 || hours >= 23) {
                    Swal.fire({
                        icon: 'warning',
                        title: dict['msg.time_invalid_title'],
                        text: dict['msg.time_invalid_text'],
                        background: '#151515',
                        color: '#fff',
                        confirmButtonColor: '#D4AF37'
                    });
                    const timeInput = document.getElementById('booking_time');
                    if (timeInput) timeInput.value = '';
                    display.innerHTML = `<span class="text-red-500">${dict['msg.time_invalid_hint']}</span>`;
                    return;
                }

                const hh = hours.toString().padStart(2, '0');
                const mm = minutes.toString().padStart(2, '0');
                display.innerHTML = `<i class="fas fa-check-circle mr-1"></i> ${dict['msg.time_label_prefix']}: ${hh}:${mm}`;
            }

            function confirmBooking(event) {
    event.preventDefault();
    const dict = getDict(currentLang);
    const form = event.target;
    const productSelect = form.querySelector('select[name="product_id"]');
    const selectedOption = productSelect?.selectedOptions?.[0];
    const productName = selectedOption?.dataset?.productName || selectedOption?.textContent?.trim() || '-';
    const productPrice = selectedOption?.dataset?.productPrice;
    
    // --- ส่วนที่ลบออก ---
    // const nextPos = document.body?.dataset?.nextPos; 
    // ------------------

    const customerName = form.querySelector('input[name="customer_name"]')?.value || '-';
    const customerPhone = form.querySelector('input[readonly]')?.value || '-';
    const dateVal = form.querySelector('input[name="booking_date"]')?.value || '-';
    const timeVal = form.querySelector('input[name="booking_time"]')?.value || '-';

    const priceRow = productPrice ? `<div><strong>${dict['summary.price']}:</strong> ฿${productPrice}</div>` : '';
    
    // --- ส่วนที่ลบออก ---
    // const queueRow = nextPos ? `<div><strong>${dict['summary.queue']}:</strong> #${nextPos}</div>` : '';
    // ------------------

    const summaryHtml = `
        <div class="text-left space-y-2 text-sm">
            <div><strong>${dict['summary.service']}:</strong> ${productName}</div>
            <div><strong>${dict['summary.date']}:</strong> ${dateVal}</div>
            <div><strong>${dict['summary.time']}:</strong> ${timeVal}</div>
            <div><strong>${dict['summary.name']}:</strong> ${customerName}</div>
            <div><strong>${dict['summary.phone']}:</strong> ${customerPhone}</div>
            ${priceRow}
            </div>
                `;

                Swal.fire({
                    title: dict['summary.title'],
                    html: summaryHtml,
                    icon: 'question',
                    showCancelButton: true,
                    confirmButtonColor: '#D4AF37',
                    cancelButtonColor: '#444',
                    confirmButtonText: dict['msg.confirm_yes'],
                    cancelButtonText: dict['msg.confirm_no'],
                    background: '#151515',
                    color: '#fff'
                }).then((result) => {
                    if (result.isConfirmed) {
                        form.submit();
                    }
                });
                return false;
            }

            document.addEventListener('DOMContentLoaded', () => {
                const lang = localStorage.getItem('app_lang') || 'th';
                applyBookingLanguage(lang);

                const timeInput = document.getElementById('booking_time');
                if (timeInput) {
                    timeInput.addEventListener('change', (e) => updateTimeDisplay(e.target.value));
                }

                window.applyBookingLanguage = applyBookingLanguage;
                window.confirmBooking = confirmBooking;
            });

            window.addEventListener('storage', (e) => {
                if (e.key === 'app_lang') {
                    applyBookingLanguage(e.newValue || 'th');
                }
            });
        })();
    </script>
</body>

</html>