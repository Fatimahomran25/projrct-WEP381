<?php
session_start();

// نقرأ رسالة الخطأ من السشن (إذا فيه)
$error = isset($_SESSION['error']) ? $_SESSION['error'] : "";

// نمسحها عشان لو رجعنا للصفحة بدون خطأ ما تظهر
unset($_SESSION['error']);
?>




<!DOCTYPE html>
<html lang="en">

<head >
    <meta charset="UTF-8">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="style.css"  rel="stylesheet">
    
   
    <title>Loin page</title>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</head>
<body>
  
  <nav class="navbar fixed-top">
  <div class="container d-flex align-items-center">
    
    <div class="d-flex align-items-center">

      <a class=" navbar-brand d-flex align-items-center">
        <img src="assets/icon.png" alt="icon" style="height:40px;">
      </a>
      <span class="fw-bold text-white ms-2">KSU Smart Schedule</span>
    </div>

  </div>
</nav>
 
  <div class="container d-flex align-items-center">
    <div class="card p-4 shadow-lg">

      <div class="part1">
        <img src="assets/kinglogin.png" alt="icon email">
        <h3>Log In</h3>
      </div>

      <form action="doLogin.php" method="post">

        <div class="mb-3">
          <label for="username" class="form-label text-white">Username</label>
          <input type="text" class="form-control" name="username" id="username" placeholder="Enter username" required>
        </div>

        <div class="mb-3">
          <label for="password" class="form-label text-white">Password</label>
          <input type="password" name="password" class="form-control" id="password" placeholder="Enter password" required>
        </div>

        <!-- رسالة الخطأ تحت الباسورد -->
        <?php if (!empty($error)): ?>
          <div class="alert alert-danger py-2 mt-1">
            <?php echo htmlspecialchars($error); ?>
          </div>
        <?php endif; ?>

        <button type="submit" class="btn btn-dark w-100 mt-3">Log In</button>
      </form>

    </div>
  </div>
<!-- footer -->
<footer class="footer glass-nav   fixed-bottom ">
  
    
    <div class="footer-logo">
      <img src="assets/Vision.png" alt="Vision 2030" >
    </div>
    <a class="text-white text-decoration-none" href="mailto:KSU_SmartSchedl@gmail.com">KSU_SmartSchedl@gmail.com</a>
    <p class=" text-white">&copy; 2025 King Saud University</p>
    
  
</footer>

    
</body>
</html>