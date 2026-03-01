<?php
session_start();
session_unset();
session_destroy();

// สั่งให้ Browser ลบ Cache ของหน้านี้และหน้าถัดไปทันที
header("Cache-Control: no-cache, no-store, must-revalidate"); 
header("Pragma: no-cache"); 
header("Expires: 0"); 

header("Location: user/Home.php");
exit();
?>