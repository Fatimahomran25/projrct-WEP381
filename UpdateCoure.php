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

// ----------- UPDATE COURSE -----------
if ($_SERVER["REQUEST_METHOD"] == "POST") {

    $oldCode  = $_POST['old_courseCode'];  // original primary key
    $newCode  = $_POST['course_code'];
    $name     = $_POST['course_name'];
    $lecture  = $_POST['lecture'];
    $tutorial = $_POST['tutorial'];
    $lab      = $_POST['lab'];
    $session  = $_POST['noSessionHours'];

    $sql = "UPDATE swecourses SET 
                courseCode = ?, 
                name = ?, 
                lectureHours = ?, 
                tutorialHours = ?, 
                labHours = ?, 
                noSessionHours = ?
            WHERE courseCode = ?";

    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ssiiiss",
        $newCode,
        $name,
        $lecture,
        $tutorial,
        $lab,
        $session,
        $oldCode
    );

    if ($stmt->execute()) {
        header("Location: ManageCourseList.php?updated=1");
        exit;
    } else {
        echo "Error updating: " . $stmt->error;
    }

    $stmt->close();
}
?>
