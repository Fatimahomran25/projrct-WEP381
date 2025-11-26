<?php
session_start();

if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'Administrator') {
    header("Location: login.php");
    exit;
}

require 'CONFIG-DB.php';

// CONNECT TO DB
$conn = new mysqli(DBHOST, DBUSER, DBPWD, DBNAME);
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// -------- DELETE COURSE --------
if (isset($_GET['courseCode'])) {

    $code = $_GET['courseCode'];

    $sql = "DELETE FROM swecourses WHERE courseCode = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $code);

    if ($stmt->execute()) {
        header("Location: ManageCourseList.php?deleted=1");
        exit;
    } else {
        echo "Error deleting: " . $stmt->error;
    }

    $stmt->close();
}
?>
