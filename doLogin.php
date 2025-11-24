<?php
session_start();
require_once("CONFIG-DB.php");

// 1) الاتصال بقاعدة البيانات
$con = mysqli_connect(DBHOST, DBUSER, DBPWD, DBNAME);
if (!$con) {
    die("Fail to connect to database: " . mysqli_connect_error());
}

// 2) جلب القيم من الفورم
$username = trim($_POST['username'] ?? '');
$password = trim($_POST['password'] ?? '');

// لو فاضيين رجعيه للّوق إن
if ($username === '' || $password === '') {
    $_SESSION['error'] = "Please enter username and password";
    header("Location: login.php");
    exit;
}

// 3) الاستعلام عن المستخدم من جدول users
$sql = "SELECT userID, username, FName, MName, LName, role 
        FROM users 
        WHERE username = ? AND password = ?";

$stmt = mysqli_prepare($con, $sql);
mysqli_stmt_bind_param($stmt, "ss", $username, $password);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);

// 4) لو لقينا مستخدم واحد
if ($result && mysqli_num_rows($result) == 1) {
    $row = mysqli_fetch_assoc($result);

    // نخزن بياناته في السشن
   $_SESSION['userID']   = $row['userID'];
$_SESSION['username'] = $row['username'];
$_SESSION['FName']    = $row['FName'];
$_SESSION['MName']    = $row['MName'];   // ممكن تكون NULL
$_SESSION['LName']    = $row['LName'];
$_SESSION['role']     = $row['role'];


    // 🔴 هنا التوجيه حسب الدور
    if ($row['role'] === 'Administrator') {
        header("Location: AdministratorMain.PHP");
        exit;
    } elseif ($row['role'] === 'Faculty') {
        header("Location: Faculty.PHP");
        exit;
    } else {
        header("Location: login.php?error=Unknown+role");
        exit;
    }

} else {
    $_SESSION['error'] = "Invalid username or password";
    header("Location: login.php");
    exit;
}
?>
