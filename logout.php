<?php
session_start();
session_unset();    // يمسح كل متغيرات السشن
session_destroy();  // يمسح السشن بالكامل

header("Location: login.php");// يرجع صفحة اللوق إن

exit;
?>
