<?php
// Start buffering immediately so headers aren't flushed before session_start.
if (!headers_sent()) {
    ob_start();
}

// Ensure session available for showing username
if (session_status() === PHP_SESSION_NONE && !headers_sent()) {
    session_start();
}

$display_name = $_SESSION['username'] ?? 'Guest';
?>
<!DOCTYPE html>
<html lang="th" data-theme="dark">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Fanier Beauty Style</title>
    <link rel="stylesheet" href="../bar_menu.css">
    <link href="https://fonts.googleapis.com/css2?family=Prompt:wght@300;400;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="css/bar_menu.css">
    <style>
        /* Theme tokens */
        :root {
            --page-bg: #0b0b0b;
            --text-color: #f5f5f5;
            --muted-color: #cfcfcf;
            --card-bg: #121212;
            --border-color: rgba(255, 255, 255, 0.08);
            --chip-bg: rgba(255, 255, 255, 0.08);
            --header-bg: linear-gradient(90deg, #0f0f0f, #2b1d14, #000);
            --nav-text: #f5e6c8;
            --nav-hover: #d4af37;
            --accent: #d4af37;
            --accent-contrast: #000;
            --shadow: rgba(0, 0, 0, 0.45);
        }

        html[data-theme="dark"] {
            --page-bg: #0b0b0b;
            --text-color: #f5f5f5;
            --muted-color: #cfcfcf;
            --card-bg: #121212;
            --border-color: rgba(255, 255, 255, 0.08);
            --chip-bg: rgba(255, 255, 255, 0.08);
            --header-bg: linear-gradient(90deg, #0f0f0f, #2b1d14, #000);
            --nav-text: #f5e6c8;
            --nav-hover: #d4af37;
            --accent: #d4af37;
            --accent-contrast: #000;
            --shadow: rgba(0, 0, 0, 0.45);
        }

        html[data-theme="light"] {
            --page-bg: #f9f9f9;
            --text-color: #121212;
            --muted-color: #555;
            --card-bg: #ffffff;
            --border-color: rgba(0, 0, 0, 0.08);
            --chip-bg: rgba(0, 0, 0, 0.05);
            --header-bg: linear-gradient(90deg, #ffffff, #f5f5f5, #ededed);
            --nav-text: #222;
            --nav-hover: #d4af37;
            --accent: #d4af37;
            --accent-contrast: #000;
            --shadow: rgba(0, 0, 0, 0.12);
        }

        html[data-theme="gold"] {
            --page-bg: #120d04;
            --text-color: #fff6dc;
            --muted-color: #e5d2a3;
            --card-bg: #1b1409;
            --border-color: rgba(243, 211, 107, 0.22);
            --chip-bg: rgba(243, 211, 107, 0.12);
            --header-bg: linear-gradient(90deg, #1c1304, #2a1b06, #1a1203);
            --nav-text: #f3d36b;
            --nav-hover: #ffd56a;
            --accent: #f3d36b;
            --accent-contrast: #1a1203;
            --shadow: rgba(0, 0, 0, 0.5);
        }

        body {
            background: var(--page-bg);
            color: var(--text-color);
            transition: background 0.3s ease, color 0.3s ease;
        }

        .header {
            background: var(--header-bg);
            border-bottom-color: var(--accent);
            box-shadow: 0 4px 15px var(--shadow);
        }

        .navbar a {
            color: var(--nav-text);
        }

        .navbar a::after {
            background: var(--accent);
        }

        .navbar a:hover {
            color: var(--nav-hover);
        }

        .user-toggle {
            color: var(--text-color);
            background: var(--chip-bg);
            border: 1px solid var(--border-color);
        }

        .user-dropdown {
            background: var(--card-bg);
            border: 1px solid var(--border-color);
            box-shadow: 0 10px 30px var(--shadow);
        }

        .user-dropdown a {
            color: var(--text-color);
        }

        .user-dropdown a:hover {
            background: var(--chip-bg);
        }

        .modal-backdrop { background: rgba(0, 0, 0, 0.55); }

        .modal-card {
            background: var(--card-bg);
            color: var(--text-color);
            border-color: var(--border-color);
            box-shadow: 0 20px 50px var(--shadow);
        }

        .modal-header h3 { color: var(--accent); }
        .modal-body label { color: var(--muted-color); }
        .modal-body input, .theme-select {
            border-color: var(--border-color);
            background: var(--page-bg);
            color: var(--text-color);
        }

        .btn-close {
            background: var(--chip-bg);
            color: var(--text-color);
            border-color: var(--border-color);
        }

        .btn-save {
            background: var(--accent);
            color: var(--accent-contrast);
        }

        .user-menu { position: relative; margin-left: auto; }
        .user-toggle { display:flex; align-items:center; gap:8px; cursor:pointer; color:var(--text-color); padding:8px 12px; border-radius:12px; background:var(--chip-bg); border:1px solid var(--border-color); transition: background 0.2s ease, border-color 0.2s ease; }
        .user-avatar { width:32px; height:32px; border-radius:50%; background:linear-gradient(135deg,#d4af37,#fdfc97); color:#000; display:flex; align-items:center; justify-content:center; font-weight:700; }
        .user-dropdown { position:absolute; right:0; top:110%; background:var(--card-bg); border:1px solid var(--border-color); border-radius:12px; min-width:180px; display:none; box-shadow:0 10px 30px var(--shadow); }
        .user-dropdown a { display:block; padding:10px 14px; color:var(--text-color); text-decoration:none; transition: background 0.2s ease; }
        .user-dropdown a:hover { background:var(--chip-bg); }
        .user-menu.open .user-dropdown { display:block; }
        
            /* Popup settings */
                .modal-backdrop { position:fixed; inset:0; background:rgba(0,0,0,0.55); backdrop-filter: blur(6px); display:none; align-items:center; justify-content:center; z-index:999; }
                .modal-backdrop.show { display:flex; }
                .modal-card { background:var(--card-bg); color:var(--text-color); border:1px solid var(--border-color); border-radius:16px; padding:24px; width:90%; max-width:420px; box-shadow:0 20px 50px var(--shadow); }
                .modal-header { display:flex; justify-content:space-between; align-items:center; margin-bottom:12px; }
                .modal-header h3 { margin:0; color:var(--accent); }
                .modal-body label { display:block; margin-top:10px; font-size:14px; color:var(--muted-color); }
                .modal-body input { width:100%; margin-top:6px; padding:10px; border-radius:10px; border:1px solid var(--border-color); background:var(--page-bg); color:var(--text-color); }
                .modal-actions { display:flex; justify-content:flex-end; gap:10px; margin-top:18px; }
                .btn { padding:10px 14px; border-radius:10px; border:1px solid transparent; cursor:pointer; }
                .btn-close { background:var(--chip-bg); color:var(--text-color); border-color:var(--border-color); }
                .btn-save { background:var(--accent); color:var(--accent-contrast); font-weight:700; }
                .theme-select { width:100%; margin-top:6px; padding:10px; border-radius:10px; border:1px solid var(--border-color); background:var(--page-bg); color:var(--text-color); }
    </style>
</head>
<body>

<header class="header">
    <div class="header-content">
        <div class="logo">
            <img src="../image/5989a8f1-f87e-4174-a5f8-5a62e900f69b.jfif" alt="logo">
            <div class="logo-text">
                <h1>Fanier</h1>
                <span>Beauty Style</span>
            </div>
        </div>

        <div class="menu-toggle" id="mobile-menu">
            <span></span>
            <span></span>
            <span></span>
        </div>

        <nav class="navbar" id="navbar">
            <a href="recommended.php" data-i18n="nav.home">หน้าแรก</a>
            <a href="menu.php" data-i18n="nav.menu">เมนู</a>
            <a href="queue_check.php" data-i18n="nav.queue">รายละเอียดคิว</a>
            <a href="booking.php" data-i18n="nav.book">จองคิวหรือดูคิวได้</a>
            <div class="user-menu" id="user-menu">
                <div class="user-toggle" aria-haspopup="true" aria-expanded="false">
                    <div class="user-avatar"><?= strtoupper(mb_substr($display_name,0,1,'UTF-8')) ?></div>
                    <span><?= htmlspecialchars($display_name) ?></span>
                </div>
                <div class="user-dropdown" role="menu">
                    <a href="#" id="open-settings" role="menuitem" data-i18n="nav.settings">ตั้งค่า</a>
                    <a href="../logout.php" role="menuitem" data-i18n="nav.logout">ออกจากระบบ</a>
                </div>
            </div>
        </nav>
    </div>
</header>

<div class="modal-backdrop" id="settings-modal" aria-hidden="true">
    <div class="modal-card" role="dialog" aria-modal="true">
        <div class="modal-header">
            <h3 data-i18n="modal.title">ตั้งค่าบัญชี</h3>
            <button class="btn btn-close" id="close-settings">ปิด</button>
        </div>
        <div class="modal-body">
            <label data-i18n="modal.theme">ธีมสี</label>
            <select id="theme-select" class="theme-select">
                <option value="dark">Dark</option>
                <option value="gold">Gold</option>
                <option value="light">Light</option>
            </select>

            <label style="margin-top:14px;" data-i18n="modal.language">ภาษา</label>
            <select id="lang-select" class="theme-select">
                <option value="th">ไทย</option>
                <option value="en">English</option>
            </select>
        </div>
        <div class="modal-actions">
            <button class="btn btn-close" id="close-settings-footer" data-i18n="modal.close">ปิด</button>
            <button class="btn btn-save" id="save-settings" data-i18n="modal.save">บันทึก</button>
        </div>
    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        const menuToggle = document.getElementById('mobile-menu');
        const navbar = document.getElementById('navbar');
        const userMenu = document.getElementById('user-menu');
        const settingsModal = document.getElementById('settings-modal');
        const openSettings = document.getElementById('open-settings');
        const closeSettings = document.getElementById('close-settings');
        const closeSettingsFooter = document.getElementById('close-settings-footer');
        const saveSettings = document.getElementById('save-settings');
        const themeSelect = document.getElementById('theme-select');
        const langSelect = document.getElementById('lang-select');

        if (menuToggle && navbar) {
            menuToggle.addEventListener('click', function() {
                navbar.classList.toggle('active');
                menuToggle.classList.toggle('open');
            });

            const navLinks = navbar.querySelectorAll('a');
            navLinks.forEach(link => {
                link.addEventListener('click', () => {
                    navbar.classList.remove('active');
                    menuToggle.classList.remove('open');
                });
            });
        }

        if (userMenu) {
            const toggle = userMenu.querySelector('.user-toggle');
            toggle.addEventListener('click', () => {
                userMenu.classList.toggle('open');
                const expanded = userMenu.classList.contains('open');
                toggle.setAttribute('aria-expanded', expanded ? 'true' : 'false');
            });

            document.addEventListener('click', (e) => {
                if (!userMenu.contains(e.target)) {
                    userMenu.classList.remove('open');
                    toggle.setAttribute('aria-expanded', 'false');
                }
            });
        }

        const openModal = () => settingsModal.classList.add('show');
        const closeModal = () => settingsModal.classList.remove('show');
        if (openSettings) openSettings.addEventListener('click', (e) => { e.preventDefault(); openModal(); });
        if (closeSettings) closeSettings.addEventListener('click', closeModal);
        if (closeSettingsFooter) closeSettingsFooter.addEventListener('click', closeModal);
        if (settingsModal) settingsModal.addEventListener('click', (e) => {
            if (e.target === settingsModal) closeModal();
        });

        if (saveSettings) {
            saveSettings.addEventListener('click', () => {
                const theme = themeSelect?.value;
                const lang = langSelect?.value;
                if (lang) {
                    localStorage.setItem('app_lang', lang);
                    applyLanguage(lang);
                }
                if (theme) {
                    applyTheme(theme);
                }
                closeModal();
            });
        }

        function applyTheme(theme) {
            const allowed = ['dark', 'light', 'gold'];
            const nextTheme = allowed.includes(theme) ? theme : 'dark';
            document.documentElement.setAttribute('data-theme', nextTheme);
            if (themeSelect) themeSelect.value = nextTheme;
            localStorage.setItem('app_theme', nextTheme);
        }

        const i18n = {
            th: {
                'nav.home': 'หน้าแรก',
                'nav.menu': 'เมนู',
                'nav.queue': 'รายละเอียดคิว',
                'nav.book': 'จองคิว',
                'nav.settings': 'ตั้งค่า',
                'nav.logout': 'ออกจากระบบ',
                'modal.title': 'ตั้งค่าบัญชี',
                'modal.theme': 'ธีมสี',
                'modal.language': 'ภาษา',
                'modal.close': 'ปิด',
                'modal.save': 'บันทึก',
                'recommended.title': '✨ บริการแนะนำ',
                'recommended.subtitle': 'Fanier Luxury Selection',
                'recommended.book': 'จองบริการนี้',
                'recommended.empty': 'ขณะนี้ยังไม่มีสินค้าแนะนำในรายการ'
            },
            en: {
                'nav.home': 'Home',
                'nav.menu': 'Menu',
                'nav.queue': 'Queue Status',
                'nav.book': 'Book',
                'nav.settings': 'Settings',
                'nav.logout': 'Logout',
                'modal.title': 'Account Settings',
                'modal.theme': 'Theme',
                'modal.language': 'Language',
                'modal.close': 'Close',
                'modal.save': 'Save',
                'recommended.title': '✨ Recommended Services',
                'recommended.subtitle': 'Fanier Luxury Selection',
                'recommended.book': 'Book this service',
                'recommended.empty': 'No recommended services available right now'
            }
        };

        function applyLanguage(lang) {
            const dict = i18n[lang] || i18n.th;
            document.querySelectorAll('[data-i18n]').forEach(el => {
                const key = el.getAttribute('data-i18n');
                if (dict[key]) el.textContent = dict[key];
            });
            if (langSelect) langSelect.value = lang;
            // Notify page-level translators (e.g., booking page)
            if (window.applyBookingLanguage) {
                window.applyBookingLanguage(lang);
            }
        }

        // init theme & language from localStorage
        const storedTheme = localStorage.getItem('app_theme') || 'dark';
        applyTheme(storedTheme);

        const storedLang = localStorage.getItem('app_lang') || 'th';
        applyLanguage(storedLang);
    });
</script>

</body>
</html>