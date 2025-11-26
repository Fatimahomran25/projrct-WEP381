<?php
session_start();

if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'Administrator') {
    header("Location: login.php");
    exit;
}

require 'CONFIG-DB.php';

// ----------------------------
// ADD NEW COURSE
// ----------------------------
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['add_course'])) {

    $course_code = $_POST['course_code'];
    $course_name = $_POST['course_name'];
    $lecture     = $_POST['lecture'];
    $tutorial    = $_POST['tutorial'];
    $lab         = $_POST['lab'];
    $labSessions = $_POST['noSessionHours'];

    $sql = "INSERT INTO swecourses 
        (courseCode, name, lectureHours, tutorialHours, labHours, noSessionHours) 
        VALUES (?, ?, ?, ?, ?, ?)";

    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ssiiii", $course_code, $course_name, $lecture, $tutorial, $lab, $labSessions);

    if ($stmt->execute()) {
        $success = "Course added successfully.";
    } else {
        $error = "Error: " . $stmt->error;
    }

    $stmt->close();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Manage Course List</title>

  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>

  <link href="style.css" rel="stylesheet">
</head>

<body>

<!-- Navbar -->
<nav class="navbar navbar-expand-lg fixed-top">
    <div class="container">
      <a class="navbar-brand"><img src="assets/icon.png" alt="icon"></a>
      <span class="fw-bold text-white">KSU Smart Schedule</span>
        <ul class="navbar-nav ms-auto">
           <li class="nav-item"><a class="nav-Home" href="AdministratorMain.php"><img src="assets/icons8-home-page-30.png" alt="Home icon"></a></li>
           <li class="nav-item"><a class="nav-link fw-bold" href="logout.php"><img src="assets/logout.png" alt="logout">Log out</a></li>
        </ul>
    </div>
</nav>

<?php
if (isset($success)) echo "<div class='alert alert-success text-center'>$success</div>";
if (isset($error))   echo "<div class='alert alert-danger text-center'>$error</div>";
?>

<div class="container d-flex align-items-start">

    <!-- Sidebar -->
    <div class="menu p-4 shadow-lg align-items-start">
      <ul class="list-group">
        <li class="list-group-item bg-transparent border-0">
          <a href="ManageLoadANDUploadScedule.php">
            <img src="assets/load.png" alt="icon Load & Schedule"> Load & Schedule
          </a>
        </li>

        <li class="list-group-item bg-transparent border-0 selected">
          <a href="ManageCourseList.php">
            <img src="assets/courses.png" alt="icon Courses"> Courses
          </a>
        </li>

        <li class="list-group-item bg-transparent border-0">
          <a href="ManageRequests.php">
            <img src="assets/icons8-form-50.png" alt="icon Requests"> Requests
          </a>
        </li>

        <li class="list-group-item bg-transparent border-0">
          <a href="CourseAssignmentANDConflictDetection.php">
            <img src="assets/icons8-edit-property-50.png" alt="icon Assignments"> Assignments
          </a>
        </li>
      </ul>
    </div>

    <!-- Main Content -->
    <div class="container d-flex align-items-start" style="min-height: calc(100vh - 200px); padding-bottom: 100px;">
      <div class="welcom shadow-lg mt-5 p-4 text-center">
        <h1 class="page-title">Manage Course List</h1>

        <div class="mb-3 text-end">
            <button class="btn btn-success" data-bs-toggle="modal" data-bs-target="#addCourseModal">
                + Add Course
            </button>
        </div>

        <table class="table table-striped table-hover course-table rounded shadow-sm text-white">
          <thead class="table-dark">
            <tr>
              <th>Course Code</th>
              <th>Course Name</th>
              <th>Hours</th>
              <th colspan="2">Actions</th>
            </tr>
          </thead>

          <tbody>
          <?php
          $sql = "SELECT * FROM swecourses";
          $result = $conn->query($sql);

          while ($row = $result->fetch_assoc()) {
              echo "
              <tr>
                  <td>{$row['courseCode']}</td>
                  <td>{$row['name']}</td>
                  <td>Lecture {$row['lectureHours']} + Tutorial {$row['tutorialHours']} + Lab {$row['labHours']}</td>

                  <td>
                      <button 
                          class='btn btn-warning btn-sm editBtn'
                          data-bs-toggle='modal'
                          data-bs-target='#editCourseModal'
                          data-code='{$row['courseCode']}'
                          data-name='{$row['name']}'
                          data-lecture='{$row['lectureHours']}'
                          data-tutorial='{$row['tutorialHours']}'
                          data-lab='{$row['labHours']}'
                          data-session='{$row['noSessionHours']}'
                      >Edit</button>
                  </td>

                  <td>
                      <a href='deleteCourse.php?courseCode={$row['courseCode']}' class='btn btn-danger btn-sm'>Delete</a>
                  </td>
              </tr>";
          }
          ?>
          </tbody>

        </table>
      </div>
    </div>
</div>

<!-- Footer -->
<footer class="footer glass-nav fixed-bottom">
    <div class="footer-logo">
      <img src="assets/Vision.png" alt="Vision 2030">
    </div>
    <a class="text-white text-decoration-none" href="mailto:KSU_SmartSchedl@gmail.com">
        KSU_SmartSchedl@gmail.com
    </a>
    <p class="text-white">© 2025 King Saud University</p>
</footer>

<!-- Add Course Modal -->
<div class="modal fade" id="addCourseModal">
<div class="modal-dialog">
<div class="modal-content">
<form method="POST">

<div class="modal-header">
<h5 class="modal-title">Add Course</h5>
<button type="button" class="btn-close" data-bs-dismiss="modal"></button>
</div>

<div class="modal-body">

<div class="mb-3">
<label class="form-label">Course Code</label>
<input type="text" name="course_code" class="form-control" required>
</div>

<div class="mb-3">
<label class="form-label">Course Name</label>
<input type="text" name="course_name" class="form-control" required>
</div>

<div class="row">
<div class="col mb-3">
<label class="form-label">Lecture Hours</label>
<input type="number" name="lecture" class="form-control" min="0" required>
</div>

<div class="col mb-3">
<label class="form-label">Tutorial Hours</label>
<input type="number" name="tutorial" class="form-control" min="0" required>
</div>

<div class="col mb-3">
<label class="form-label">Lab Hours</label>
<input type="number" name="lab" class="form-control" min="0" required>
</div>

<div class="col mb-3">
<label class="form-label">Lab Session Hours</label>
<input type="number" name="noSessionHours" class="form-control" min="0" required>
</div>
</div>

</div>

<div class="modal-footer">
<button type="submit" name="add_course" class="btn btn-primary">Save</button>
</div>

</form>
</div>
</div>
</div>

<!-- Edit Modal -->
<div class="modal fade" id="editCourseModal">
<div class="modal-dialog">
<div class="modal-content">
<form method="POST" action="updateCourse.php">

<div class="modal-header">
<h5 class="modal-title">Edit Course</h5>
<button type="button" class="btn-close" data-bs-dismiss="modal"></button>
</div>

<div class="modal-body">

<input type="hidden" name="old_courseCode" id="edit_old_code">

<div class="mb-3">
<label class="form-label">Course Code</label>
<input type="text" name="course_code" id="edit_code" class="form-control" required>
</div>

<div class="mb-3">
<label class="form-label">Course Name</label>
<input type="text" name="course_name" id="edit_name" class="form-control" required>
</div>

<div class="row">
<div class="col mb-3">
<label class="form-label">Lecture Hours</label>
<input type="number" name="lecture" id="edit_lecture" class="form-control" required>
</div>

<div class="col mb-3">
<label class="form-label">Tutorial Hours</label>
<input type="number" name="tutorial" id="edit_tutorial" class="form-control" required>
</div>

<div class="col mb-3">
<label class="form-label">Lab Hours</label>
<input type="number" name="lab" id="edit_lab" class="form-control" required>
</div>

<div class="col mb-3">
<label class="form-label">Lab Session Hours</label>
<input type="number" name="noSessionHours" id="edit_session" class="form-control" required>
</div>
</div>

</div>

<div class="modal-footer">
<button type="submit" name="edit_course" class="btn btn-primary">Save Changes</button>
</div>

</form>
</div>
</div>
</div>

<script>
// fill edit modal
document.querySelectorAll(".editBtn").forEach(btn => {
    btn.addEventListener("click", () => {
        document.getElementById("edit_old_code").value = btn.dataset.code;
        document.getElementById("edit_code").value     = btn.dataset.code;
        document.getElementById("edit_name").value     = btn.dataset.name;
        document.getElementById("edit_lecture").value  = btn.dataset.lecture;
        document.getElementById("edit_tutorial").value = btn.dataset.tutorial;
        document.getElementById("edit_lab").value      = btn.dataset.lab;
        document.getElementById("edit_session").value  = btn.dataset.session;
    });
});
</script>

</body>
</html>
