<?php
include 'config.php';
session_start();

if (isLoggedIn()) {
    // تسجيل نشاط تسجيل الخروج
    logActivity($db, $_SESSION['user_id'], 'logout', 'تسجيل خروج من النظام');
}

// تدمير الجلسة
session_destroy();

// إعادة التوجيه إلى الصفحة الرئيسية
header("Location: index.php?logged_out=1");
exit();
?>
