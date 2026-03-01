<?php
session_start();
require_once("../db.php");   // ← connect DB ตรงนี้

// รับ service_id จาก URL
$selected_service_id = isset($_GET['service_id']) ? (int)$_GET['service_id'] : 0;

// 🔥 วางโค้ดตรงนี้
$page_title = "จองคิว Fanier Beauty Style";
$page_desc  = "เลือกบริการและกรอกข้อมูลเพื่อทำการจอง";

if ($selected_service_id > 0) {
    $q = $conn->query("SELECT service_name FROM services WHERE service_id = $selected_service_id");
    if ($q && $q->num_rows > 0) {
        $row = $q->fetch_assoc();
        $page_title = "จองคิว " . $row['service_name'];
        $page_desc  = "บริการ " . $row['service_name'];
    }
}




            
// ดึงรายการบริการทั้งหมดสำหรับแสดงในฟอร์ม
$services_result = $conn->query("SELECT * FROM services ORDER BY service_name");
if ($services_result && $services_result->num_rows > 0) {
    $has_services = true;
}

if ($_SERVER["REQUEST_METHOD"] == "POST" && $has_services) {
    // 1. รับข้อมูลจากฟอร์ม
    $customer_name = trim($conn->real_escape_string($_POST['customer_name']));
    $customer_phone = trim($conn->real_escape_string($_POST['customer_phone']));
    // ตรวจสอบว่า service_id เป็นตัวเลขและไม่ใช่ค่าว่าง
    $service_id = isset($_POST['service_id']) ? (int)$_POST['service_id'] : 0; 
    $booking_date = $conn->real_escape_string($_POST['booking_date']);
    $booking_time = $conn->real_escape_string($_POST['booking_time']);
    $booking_code = generateBookingCode($conn);

    // 2. ตรวจสอบข้อมูลเบื้องต้น
    if (empty($customer_name) || empty($customer_phone) || empty($booking_date) || empty($booking_time) || $service_id === 0) {
         $message = "<div class='bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative mb-4' role='alert'>กรุณากรอกข้อมูลให้ครบถ้วนและเลือกบริการ.</div>";
    } else {
        // 3. เตรียมคำสั่ง SQL เพื่อบันทึกข้อมูล
        $sql = "INSERT INTO bookings (booking_code, customer_name, customer_phone, service_id, booking_date, booking_time) 
                VALUES (?, ?, ?, ?, ?, ?)";
        
        $stmt = $conn->prepare($sql);
        
        // ************************************************
        // * แก้ไขข้อผิดพลาด bind_param จาก "sssisss" เป็น "sssiss" *
        // ************************************************
        $stmt->bind_param("sssiss", $booking_code, $customer_name, $customer_phone, $service_id, $booking_date, $booking_time);

        // 4. บันทึกข้อมูลและตรวจสอบผลลัพธ์
        if ($stmt->execute()) {
            // สำเร็จ! ส่งลูกค้าไปหน้ายืนยัน
            header("Location: confirm.php?code=" . $booking_code);
            exit();
        } else {
            // ปรับปรุงการแสดงข้อผิดพลาดให้เป็นมิตรมากขึ้น
            $error_msg = $stmt->error;
            if (strpos($error_msg, 'Foreign key constraint fails') !== false) {
                 $message = "<div class='bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative mb-4' role='alert'>เกิดข้อผิดพลาด: ไม่พบรายการบริการที่เลือก กรุณาลองเลือกบริการใหม่ (หรือติดต่อผู้ดูแลระบบ).</div>";
            } else {
                 $message = "<div class='bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative mb-4' role='alert'>เกิดข้อผิดพลาดในการจอง: " . htmlspecialchars($error_msg) . "</div>";
            }
        }
        $stmt->close();
    }
    
    
}
?>

<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>จองคิว Fanier Beauty Style</title>
    <script src="https://cdn.tailwindcss.com"></script>
   
</head>
<body class="bg-pink-100 min-h-screen flex items-center justify-center p-4">
    <div class="bg-white p-8 rounded-lg shadow-xl w-full max-w-lg">
        <h1 class="text-3xl font-bold text-center text-pink-600 mb-6">จองคิว Fanier Beauty Style</h1>
        <p class="text-center text-gray-600 mb-6">เลือกบริการและกรอกข้อมูลเพื่อทำการจอง</p>
        
        <?= $message ?> <?php if (!$has_services): ?>
            <div class='bg-yellow-100 border border-yellow-400 text-yellow-700 px-4 py-3 rounded relative mb-4' role='alert'>
                <p class="font-bold">❌ ยังไม่พร้อมให้บริการ</p>
                <p class="text-sm">กรุณาเพิ่มรายการบริการในตาราง `services` ก่อนจึงจะเปิดรับจองได้.</p>
            </div>
        <?php else: ?>
            <form action="index.php" method="POST" class="space-y-4">
                <div>
                    <label for="customer_name" class="block text-sm font-medium text-gray-700">ชื่อ-นามสกุล:</label>
                    <input type="text" name="customer_name" id="customer_name" required class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-pink-500 focus:border-pink-500">
                </div>
                <div>
                    <label for="customer_phone" class="block text-sm font-medium text-gray-700">เบอร์โทรศัพท์:</label>
                    <input type="tel" name="customer_phone" id="customer_phone" required class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-pink-500 focus:border-pink-500">
                </div>

                <div>
                    <label for="service_id" class="block text-sm font-medium text-gray-700">เลือกบริการ:</label>
                    <select name="service_id" id="service_id" required class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-pink-500 focus:border-pink-500 bg-white">
                        <?php 
                        // เนื่องจากมีการตรวจสอบ $has_services แล้ว จึงมั่นใจว่ามีข้อมูลให้แสดง
                        $services_result->data_seek(0); // รีเซ็ตตัวชี้ผลลัพธ์
                       while($row = $services_result->fetch_assoc()) {
    $selected = ($row['service_id'] == $selected_service_id) ? 'selected' : '';
    echo "<option value='{$row['service_id']}' $selected>
        {$row['service_name']} (฿{$row['price']} - {$row['duration_minutes']} นาที)
    </option>";
}

                        ?>
                    </select>
                </div>

                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label for="booking_date" class="block text-sm font-medium text-gray-700">วันที่จอง:</label>
                        <input type="date" name="booking_date" id="booking_date" required min="<?= date('Y-m-d', strtotime('+1 day')) ?>" class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-pink-500 focus:border-pink-500">
                    </div>
                    <div>
                        <label for="booking_time" class="block text-sm font-medium text-gray-700">เวลาที่จอง:</label>
                        <input type="time" name="booking_time" id="booking_time" required min="10:00" max="18:00" class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-pink-500 focus:border-pink-500">
                    </div>
                </div>
                
               <button type="submit"
class="w-full flex justify-center py-3 px-4 rounded-lg shadow-md
text-sm font-semibold text-white
bg-gradient-to-r from-pink-500 to-purple-500
hover:from-pink-600 hover:to-purple-600
focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-pink-400
transition duration-200 ease-in-out">
    ยืนยันการจองคิว
</button>
            </form>
        <?php endif; ?>
        
       

<?php if (isset($_SESSION['role']) && $_SESSION['role'] === 'admin'): ?>
    <p class="mt-6 text-center text-sm text-gray-500">
        สำหรับผู้ดูแลระบบ: 
        <a href="admin/index.php" class="font-medium text-blue-600 hover:text-blue-500">
            เข้าสู่ระบบแอดมิน
        </a>
    </p>
<?php endif; ?>
    </div>
</body>
</html>

<?php $conn->close(); ?>