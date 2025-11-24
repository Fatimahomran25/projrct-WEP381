<?php
session_start();

// حماية الصفحة – أدمن فقط
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'Administrator') {
    header("Location: login.php");
    exit;
}

// نكوّن الاسم الكامل
$first  = $_SESSION['FName'] ?? '';
$middle = $_SESSION['MName'] ?? '';
$last   = $_SESSION['LName'] ?? '';

$fullName = $first;

// إذا الميد نيم مو فاضي، ضيفيه
if (!empty($middle)) {
    $fullName .= ' ' . $middle;
}

$fullName .= ' ' . $last;
$fullName = trim($fullName);
?>


<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="style.css"  rel="stylesheet">
    
   
    <title>Administrator Main page</title>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</head>
<body>
    <!-- Navbar -->
  <nav class="navbar navbar-expand-lg fixed-top">
    <div class="container">
      <a class="navbar-brand"><img src="assets/icon.png" alt="icon"></a>
      <span class="fw-bold text-white">KSU Smart Schedule</span>
      
        <ul class="navbar-nav ms-auto">
          
           <li class="nav-item"><a class="nav-link fw-bold" href="logout.php"><img src="assets/logout.png"  alt="logout">Log out</a></li>
        </ul>
      </div>
    
  </nav>
<!--  Administrator main-->

<div class="container d-flex  align-items-start " style="height: 100vh;">
  <!-- list Administrator container 1-->
  <div class="menu p-4 shadow-lg d-flex  align-items-start" >
  <ul class="list-group  ">

    <li class="list-group-item bg-transparent border-0">
      <a href="ManageLoadANDUploadScedule.php">
        <img src="assets/load.png" alt="icon Load & Schedule" > Load & Schedule
      </a>
    </li>

    <li class="list-group-item bg-transparent border-0">
      <a href="ManageCourseList.php">
        <img src="assets/courses.png" alt="icon Courses" > Courses
      </a>
    </li>

    <li class="list-group-item bg-transparent border-0">
      <a href="ManageRequests.php">
        <img src="assets/icons8-form-50.png" alt="icom Requests"> Requests
      </a>
    </li>

    <li class="list-group-item bg-transparent border-0">
      <a href="CourseAssignmentANDConflictDetection.php">
        <img src="assets/icons8-edit-property-50.png" alt="icon Assignments  Conflicts"> Assignments 
      </a>
    </li>

  </ul>
</div>
<!-- welcome container 2-->
<div class="welcom  shadow-lg " >
    
    <img src="assets/icons8-admin-64.png" alt="icons admin" >
    <h1>Welcome Back <?php echo htmlspecialchars($fullName); ?></h1>

    <h4>Admin Dashboard</h4>
    

</div>

</div>

<!-- footer -->
<footer class="footer glass-nav   fixed-bottom ">
  
    
    <div class="footer-logo ">
      <img src="assets/Vision.png" alt="Vision 2030" >
    </div>
    <a class="text-white text-decoration-none" href="mailto:KSU_SmartSchedl@gmail.com">KSU_SmartSchedl@gmail.com</a>
    <p class=" text-white">&copy; 2025 King Saud University</p>
    
  
</footer>

    


   
    
</body>
</html>