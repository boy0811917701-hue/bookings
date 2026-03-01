<?php
session_start();
if (!isset($_SESSION['user_id']) || ($_SESSION['role'] ?? '') !== 'admin') {
    header("Location: ../login.php");
    exit();
}

// เตรียม CSRF token
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

include '../db.php';

// คำอธิบายสถานะ พร้อมสีสันที่ชัดเจน
$statusLabel = [
    'pending' => '<span class="status-badge bg-orange">รอดำเนินการ</span>',
    'processing' => '<span class="status-badge bg-cyan">กำลังให้บริการ</span>',
    'confirmed' => '<span class="status-badge bg-green">ดำเนินการเสร็จสิ้น</span>',
    'completed' => '<span class="status-badge bg-blue">เก็บประวัติแล้ว</span>',
    'cancelled' => '<span class="status-badge bg-red">ยกเลิก</span>'
];

/* =======================
    QUERY (รองรับค้นหา)
======================= */
$where = "1=1";
$params = [];
$types = "";

if (!empty($_GET['q'])) {
    $where .= " AND (b.customer_name LIKE ? OR b.booking_code LIKE ? OR b.customer_phone LIKE ?)";
    $like = "%" . trim($_GET['q']) . "%";
    $params = [$like, $like, $like];
    $types = "sss";
}

$sql = "
SELECT 
    b.booking_id,
    b.booking_code,
    b.customer_name,
    b.customer_phone,
    b.booking_date,
    b.booking_time,
    b.status,
    b.total_price,
    COALESCE(p.product_name, b.product_name) as product_name
FROM bookings b
LEFT JOIN products p ON b.product_id = p.product_id
WHERE $where
ORDER BY FIELD(b.status, 'processing','pending'), b.booking_date ASC, b.booking_time ASC, b.booking_id ASC
"; // ปรับให้เอาคิวที่ใกล้ที่สุดขึ้นก่อน

$stmt = $conn->prepare($sql);
if ($types !== "") {
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$result = $stmt->get_result();

$isSearch = !empty($_GET['q']);
?>

<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <title>Admin Dashboard | Fanier Beauty Style</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href="https://fonts.googleapis.com/css2?family=Prompt:wght@300;400;600&display=swap" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <style>
        :root {
            --primary: #D4AF37;
            --dark-bg: #0f0f0f;
            --card-bg: #1a1a1a;
        }
        body { font-family: 'Prompt', sans-serif; background: var(--dark-bg); color: #e5e5e5; margin: 0; }
        .container { max-width: 1200px; margin: 0 auto; padding: 20px; }
        
        /* Status Badges */
        .status-badge { padding: 4px 12px; border-radius: 50px; font-size: 0.75rem; font-weight: 600; }
        .bg-orange { background: rgba(255, 165, 0, 0.2); color: #FFA500; border: 1px solid rgba(255, 165, 0, 0.3); }
        .bg-green { background: rgba(40, 167, 69, 0.2); color: #28a745; border: 1px solid rgba(40, 167, 69, 0.3); }
        .bg-red { background: rgba(220, 53, 69, 0.2); color: #dc3545; border: 1px solid rgba(220, 53, 69, 0.3); }
        .bg-cyan { background: rgba(0, 188, 212, 0.2); color: #00bcd4; border: 1px solid rgba(0, 188, 212, 0.3); }
        
        /* Table Style */
        .booking-table { width: 100%; border-collapse: collapse; background: var(--card-bg); border-radius: 15px; overflow: hidden; margin-top: 20px; }
        .booking-table th { background: #252525; padding: 15px; text-align: left; color: var(--primary); font-size: 0.85rem; text-transform: uppercase; }
        .booking-table td { padding: 15px; border-bottom: 1px solid #222; font-size: 0.9rem; }
        .booking-table tr:hover { background: #222; }

        /* Search Box */
        .search-box { display: flex; gap: 10px; margin-bottom: 30px; }
        .search-box input { flex: 1; padding: 15px; border-radius: 12px; border: 1px solid #333; background: #151515; color: #fff; outline: none; }
        .search-box input:focus { border-color: var(--primary); }
        .search-box button { background: var(--primary); color: #000; padding: 0 25px; border-radius: 12px; border: none; font-weight: bold; cursor: pointer; }

        .action-btns { display: flex; gap: 10px; }
        .btn-icon { width: 35px; height: 35px; border-radius: 8px; border: none; cursor: pointer; display: flex; align-items: center; justify-content: center; transition: 0.3s; }
        .btn-done { background: #28a745; color: white; }
        .btn-delete { background: #dc3545; color: white; }
        .btn-pending { background: #666; color: white; }
        
        .auto-hide { opacity: 0; transform: translateX(50px); transition: 0.8s; }
        .fade-out {
        opacity: 0;
        transform: translateX(20px);
        transition: all 0.5s ease;
        background-color: rgba(40, 167, 69, 0.1) !important; /* ไฮไลท์สีเขียวอ่อนก่อนหาย */
    }
    </style>
</head>
<body>
    <?php include '../bar.php'; ?>

    <div class="container">
        <div style="display: flex; justify-content: space-between; align-items: center; gap: 16px; margin: 30px 0; flex-wrap: wrap;">
            <div style="display:flex; align-items:center; gap:12px;">
                <h2 style="margin: 0;">📋 รายการจองคิวลูกค้า</h2>
                <a href="dashboard.php" style="padding: 8px 14px; border-radius: 10px; background: #D4AF37; color: #000; font-weight: 700; text-decoration: none; box-shadow: 0 6px 20px rgba(212,175,55,0.25);">ดู รายละเอียด</a>
                <a href="dashboard_detail.php" style="padding: 8px 14px; border-radius: 10px; background: #1f2937; color: #fff; font-weight: 700; text-decoration: none; border: 1px solid #ffffff20;">สถิติสินค้า</a>
            </div>
            <div id="doneMsg" style="display:none; font-size: 0.8rem; color: #28a745; background: rgba(40,167,69,0.1); padding: 10px 20px; border-radius: 10px;">
                ✅ อัปเดตรายการสำเร็จ!
            </div>
        </div>

        <form method="GET" class="search-box">
            <input type="text" name="q" value="<?= htmlspecialchars($_GET['q'] ?? '') ?>"
                placeholder="ค้นหาชื่อลูกค้า / เบอร์โทร / รหัสจอง...">
            <button type="submit">ค้นหา</button>
        </form>

        <table class="booking-table">
            <thead>
                <tr>
                    <th>รหัสจอง</th>
                    <th>ชื่อลูกค้า</th>
                    <th>บริการ</th>
                    <th>วัน / เวลา</th>
                    <th style="text-align:right;">ยอดรวม</th>
                    <th>สถานะ</th>
                    <?php if (!$isSearch): ?><th>จัดการ</th><?php endif; ?>
                </tr>
            </thead>
            <tbody>
                <?php if ($result && $result->num_rows > 0): ?>
                    <?php while ($row = $result->fetch_assoc()): ?>
                        <?php if (in_array($row['status'], ['confirmed','completed'])) continue; // ซ่อนรายการที่เสร็จสิ้นแล้ว ?>
                        <tr id="row-<?= $row['booking_code'] ?>">
                            <td style="font-weight: bold; color: var(--primary);"><?= htmlspecialchars($row['booking_code']) ?></td>
                            <td>
                                <div><?= htmlspecialchars($row['customer_name']) ?></div>
                                <div style="font-size: 0.75rem; color: #666;"><?= htmlspecialchars($row['customer_phone']) ?></div>
                            </td>
                            <td><?= htmlspecialchars($row['product_name'] ?: 'ไม่ระบุ') ?></td>
                            <td>
                                <div style="font-weight: 600;">📅 <?= date('d/m/Y', strtotime($row['booking_date'])) ?></div>
                                <div style="font-size: 0.8rem; color: #888;">🕒 <?= substr($row['booking_time'], 0, 5) ?> น.</div>
                            </td>
                            <td style="text-align:right; font-weight:600; color:#fff;">฿<?= number_format($row['total_price']) ?></td>
                            <td><?= $statusLabel[$row['status']] ?? '-' ?></td>

                            <?php if (!$isSearch): ?>
                                <?php $actionable = in_array($row['status'], ['pending','processing']); ?>
                                <td class="action-btns">
                                    <?php if ($actionable): ?>
                                        <button type="button" class="btn-icon btn-done" title="เสร็จสิ้น" 
                                                onclick="handleStatus('<?= $row['booking_code'] ?>', 'confirmed')">✓</button>
                                        <button type="button" class="btn-icon btn-delete" title="ลบ" 
                                                onclick="confirmDelete('<?= $row['booking_code'] ?>')">✕</button>
                                    <?php else: ?>
                                        <span style="color:#666;">-</span>
                                    <?php endif; ?>
                                </td>
                            <?php endif; ?>
                        </tr>
                    <?php endwhile; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="7" align="center" style="padding: 50px; color: #555;">ไม่พบข้อมูลรายการจอง</td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>

    <script>
       function handleStatus(code, status) {
        Swal.fire({
            title: 'ยืนยันรายการ?',
            text: "ต้องการทำเครื่องหมายว่าเสร็จสิ้นใช่หรือไม่?",
            icon: 'question',
            background: '#1a1a1a',
            color: '#fff',
            showCancelButton: true,
            confirmButtonColor: '#28a745',
            confirmButtonText: 'ยืนยัน',
            cancelButtonText: 'ยกเลิก'
        }).then((res) => {
            if (res.isConfirmed) {
                const formData = new FormData();
                formData.append('booking_code', code);
                formData.append('status', status);
                formData.append('csrf_token', '<?= $_SESSION['csrf_token'] ?>');

                fetch('update_status.php', { method: 'POST', body: formData })
                .then(response => response.json())
                .then(data => {
                    if (data.status === 'success') {
                        const row = document.getElementById('row-' + code);
                        
                        // เริ่มการหายไป (Fade Out)
                        row.classList.add('fade-out'); 
                        
                        // แสดงข้อความแจ้งเตือนด้านบน
                        const doneMsg = document.getElementById('doneMsg');
                        doneMsg.style.display = 'block';
                        
                        // ลบแถวออกจากหน้าจอหลังจากทำ Animation เสร็จ (0.5 วินาที)
                        setTimeout(() => {
                            row.remove();
                            
                            // ตรวจสอบว่าตารางว่างหรือไม่ ถ้าว่างให้โชว์ข้อความ "ไม่พบข้อมูล"
                            const tbody = document.querySelector('.booking-table tbody');
                            if (tbody.querySelectorAll('tr').length === 0) {
                                tbody.innerHTML = '<tr><td colspan="6" align="center" style="padding: 50px; color: #555;">ไม่พบข้อมูลรายการจอง</td></tr>';
                            }
                            
                            // ซ่อนข้อความแจ้งเตือนหลังจากผ่านไป 3 วินาที
                            setTimeout(() => { doneMsg.style.display = 'none'; }, 3000);
                        }, 500);
                    } else {
                        Swal.fire('แจ้งเตือน', data.message, data.status);
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    Swal.fire('ผิดพลาด', 'ไม่สามารถเชื่อมต่อกับเซิร์ฟเวอร์ได้', 'error');
                });
            }
        });
    }

    // 2. ระบบลบข้อมูลพร้อม Undo (10 วินาที)
    let deleteTimer = null;
    function confirmDelete(code) {
        Swal.fire({
            title: 'กำลังจะลบข้อมูล...',
            html: 'จะลบถาวรใน <b>10</b> วินาที',
            icon: 'warning',
            background: '#1a1a1a',
            color: '#fff',
            showCancelButton: true,
            cancelButtonText: 'ยกเลิก (Undo)',
            confirmButtonColor: '#dc3545',
            confirmButtonText: 'ลบทันที',
            timer: 10000,
            timerProgressBar: true,
            didOpen: () => {
                const b = Swal.getHtmlContainer().querySelector('b');
                deleteTimer = setInterval(() => {
                    b.textContent = Math.ceil(Swal.getTimerLeft() / 1000);
                }, 100);
            },
            willClose: () => { clearInterval(deleteTimer); }
        }).then((result) => {
            if (result.isConfirmed || result.dismiss === Swal.DismissReason.timer) {
                const formData = new FormData();
                formData.append('booking_code', code);
                formData.append('csrf_token', '<?= $_SESSION['csrf_token'] ?>');
                
                fetch('delete_booking.php', { method: 'POST', body: formData })
                .then(() => {
                    const row = document.getElementById('row-' + code);
                    row.classList.add('fade-out');
                    setTimeout(() => {
                        row.remove();
                        const tbody = document.querySelector('.booking-table tbody');
                        if (tbody.querySelectorAll('tr').length === 0) {
                            tbody.innerHTML = '<tr><td colspan="6" align="center" style="padding: 50px; color: #555;">ไม่พบข้อมูลรายการจอง</td></tr>';
                        }
                    }, 500);
                });
            }
        });
    }
    </script>
</body>
</html>