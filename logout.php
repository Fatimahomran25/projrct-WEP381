<?php
session_start();
session_unset();    // يمسح كل متغيرات السشن
session_destroy();  //    يمسح   يرجع صفحة اللوق إن

header("Location: login.php?logout=1");
exit;
?>
