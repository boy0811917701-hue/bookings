<?php
require_once(__DIR__ . "/../db.php");
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
// ดึงข้อมูลสินค้าแนะนำ
$sql = "SELECT * FROM products 
        WHERE status = 'active' 
        AND display_type IN ('recommended', 'both') 
        ORDER BY created_at DESC";
$products = $conn->query($sql);

// ส่วนของ Dynamic Title
$page_title = "สินค้าแนะนำ | Fanier Beauty Style";
if (isset($_GET['id'])) {
    $p_id = intval($_GET['id']);
    $res_name = $conn->prepare("SELECT product_name FROM products WHERE product_id = ?");
    $res_name->bind_param("i", $p_id);
    $res_name->execute();
    $result_name = $res_name->get_result();
    if ($p_single = $result_name->fetch_assoc()) {
        $page_title = $p_single['product_name'] . " - Fanier";
    }
    $res_name->close();
}
?>

<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=5.0">
    <title><?= $page_title ?></title>
    <link rel="stylesheet" href="../css/componan.css">
    <link rel="stylesheet" href="../css/bar_menu.css">
    <link href="https://fonts.googleapis.com/css2?family=Prompt:wght@300;400;600&display=swap" rel="stylesheet">
    <style>
        :root {
            --gold-primary: #D4AF37;
            --gold-gradient: linear-gradient(135deg, #fdfc97 0%, #d4af37 100%);
            --bg-dark: #0a0a0a;
            --card-bg: #151515;
        }

        body { 
            background-color: var(--bg-dark); 
            color: #fff; 
            font-family: 'Prompt', sans-serif;
            margin: 0;
            line-height: 1.6;
        }

        /* --- Main Layout --- */
        .container {
            width: 100%;
            max-width: 1400px;
            margin: 0 auto;
            padding: 0 15px;
            box-sizing: border-box;
        }

        .section-header {
            text-align: center;
            margin: clamp(40px, 8vw, 80px) 0 clamp(20px, 5vw, 40px);
        }

        .section-header h2 {
            color: var(--gold-primary);
            font-size: clamp(1.8rem, 5vw, 2.5rem); /* ขนาดปรับตามจออัตโนมัติ */
            margin-bottom: 8px;
            font-weight: 600;
        }

        .section-header p {
            color: #888;
            text-transform: uppercase;
            font-size: clamp(0.65rem, 2vw, 0.8rem);
            letter-spacing: 3px;
        }

        /* --- Responsive Grid System --- */
        .grid {
            display: grid;
            /* ปรับจำนวนคอลัมน์อัตโนมัติ: จอเล็กสุด 1 คอลัมน์, จอกลาง 2, จอใหญ่ 3-4 */
            grid-template-columns: repeat(auto-fill, minmax(260px, 1fr));
            gap: clamp(15px, 3vw, 30px);
            padding-bottom: 60px;
        }

        /* --- Premium Card Design --- */
        .card {
            background: var(--card-bg);
            border-radius: 20px;
            overflow: hidden;
            transition: all 0.4s cubic-bezier(0.165, 0.84, 0.44, 1);
            border: 1px solid rgba(255,255,255,0.05);
            display: flex;
            flex-direction: column;
            height: 100%;
        }

        .card:hover {
            transform: translateY(-8px);
            border-color: var(--gold-primary);
            box-shadow: 0 15px 35px rgba(0,0,0,0.5), 0 0 15px rgba(212,175,55,0.1);
        }

        .card-img-wrapper {
            position: relative;
            width: 100%;
            aspect-ratio: 3 / 4; /* ล็อกสัดส่วนรูปภาพให้เท่ากันทุกใบ */
            overflow: hidden;
        }

        .card img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            transition: transform 0.6s ease;
        }

        .card:hover img {
            transform: scale(1.08);
        }

        .card-content {
            padding: 20px;
            text-align: center;
            flex-grow: 1;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
        }

        .card-content h3 {
            font-size: 1.1rem;
            margin: 0 0 10px 0;
            font-weight: 500;
            min-height: 2.8em; /* กันชื่อยาวไม่เท่ากันแล้วบรรทัดเคลื่อน */
            display: -webkit-box;
            -webkit-line-clamp: 2;
            -webkit-box-orient: vertical;
            overflow: hidden;
        }

        .price {
            color: var(--gold-primary);
            font-weight: 600;
            font-size: 1.3rem;
            margin-bottom: 20px;
        }

        .btn-booking {
            display: block;
            background: var(--gold-gradient);
            color: #000;
            padding: 12px 15px;
            border-radius: 12px;
            text-decoration: none;
            font-weight: 600;
            font-size: 0.9rem;
            transition: 0.3s;
            text-transform: uppercase;
        }

        .btn-booking:active {
            transform: scale(0.96); /* เอฟเฟกต์ตอนกดบนมือถือ */
        }

        /* --- Media Queries สำหรับกรณีพิเศษ --- */
        
        /* หน้าจอเล็กมาก (มือถือรุ่นเก่า) */
        @media (max-width: 350px) {
            .grid {
                grid-template-columns: 1fr;
            }
        }

        /* หน้าจอ Tablet และ Desktop เล็ก */
        @media (min-width: 768px) and (max-width: 1024px) {
            .grid {
                grid-template-columns: repeat(2, 1fr);
            }
        }

        footer {
            text-align: center;
            padding: 40px 0;
            color: #444;
            font-size: 0.8rem;
            border-top: 1px solid rgba(255,255,255,0.05);
        }
    </style>
</head>

<body>

<?php include '../bar_menu.php'; ?>

<main class="container">
    <header class="section-header">
        <h2 data-i18n="recommended.title">บริการแนะนำ</h2>
        <p>Fanier Luxury Selection</p>
    </header>

    <section class="grid">
        <?php if ($products && $products->num_rows > 0): ?>
            <?php while ($p = $products->fetch_assoc()): ?>
                <article class="card">
                    <div class="card-img-wrapper">
                        <?php $img_path = "../uploads/" . ($p['image'] ?: 'no-image.png'); ?>
                        <img src="<?= $img_path ?>" 
                             alt="<?= htmlspecialchars($p['product_name']) ?>"
                             loading="lazy"
                             onerror="this.src='../uploads/no-image.png';">
                    </div>

                    <div class="card-content">
                        <div>
                            <h3><?= htmlspecialchars($p['product_name']) ?></h3>
                            <div class="price">฿<?= number_format($p['price']) ?></div>
                        </div>
                        
                        <a href="booking.php?product_id=<?= $p['product_id'] ?>" class="btn-booking" data-i18n="recommended.book">
                            จองบริการนี้
                        </a>
                    </div>
                </article>
            <?php endwhile; ?>
        <?php else: ?>
            <div style="grid-column: 1/-1; text-align: center; padding: 100px 0; color: #555;">
                <p>ขออภัย ขณะนี้ยังไม่มีรายการบริการแนะนำ</p>
            </div>
        <?php endif; ?>
    </section>
</main>

<footer>
    © 2026 Fanier Beauty Style — All Rights Reserved
</footer>

</body>
</html>
<?php $conn->close(); ?>