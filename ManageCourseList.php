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
           <li class="nav-item"><a class="nav-Home" href="AdministratorMain.html"><img src="assets/icons8-home-page-30.png"  alt="Home icon"></a></li>
           <li class="nav-item"><a class="nav-link fw-bold" href="logout.php"><img src="assets/logout.png"  alt="logout">Log out</a></li>
        </ul>
    </div>
  </nav>


  <!-- Sidebar-->
  
  <div class="container d-flex align-items-start">

    
    <div class="menu p-4 shadow-lg align-items-start">
      <ul class="list-group">
        <li class="list-group-item bg-transparent border-0">
          <a href="ManageLoadANDUploadScedule.HTML">
            <img src="assets/load.png" alt="icon Load & Schedule"> Load & Schedule
          </a>
        </li>

        <li class="list-group-item bg-transparent border-0 selected">
          <a href="ManageCourseList.html">
            <img src="assets/courses.png" alt="icon Courses"> Courses
          </a>
        </li>

        <li class="list-group-item bg-transparent border-0">
          <a href="ManageRequests.html">
            <img src="assets/icons8-form-50.png" alt="icon Requests"> Requests
          </a>
        </li>

        <li class="list-group-item bg-transparent border-0">
          <a href="CourseAssignmentANDConflictDetection.html">
            <img src="assets/icons8-edit-property-50.png" alt="icon Assignments"> Assignments
          </a>
        </li>
      </ul>
    </div>

    
     <!--Main Content-->

    <div class="container d-flex align-items-start" style="min-height: calc(100vh - 200px); padding-bottom: 100px;">
      <div class="welcom shadow-lg mt-5 p-4 text-center">
        <h1 class="page-title text-center">Manage Course List</h1>

        <div class="mb-3 text-end">
          <button class="btn btn-success">+ Add Course</button>
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
            <tr>
              <td>SWE 211</td>
              <td>Introduction to Software Engineering</td>
              <td>Lecture3+Tutorial1</td>
              <td><button class="btn btn-warning btn-sm">Edit</button></td>
              <td><button class="btn btn-danger btn-sm">Delete</button></td>
            </tr>


            <tr>
              <td>CEN 303</td>
              <td>Data Communications and Computer Networks</td>
              <td>Lecture3+Tutorial1</td>
              <td><button class="btn btn-warning btn-sm">Edit</button></td>
              <td><button class="btn btn-danger btn-sm">Delete</button></td>
          </tr>


            <tr>
              <td>SWE 312</td>
              <td>Software Requirements Engineering</td>
              <td>Lecture3+Tutorial1</td>
              <td><button class="btn btn-warning btn-sm">Edit</button></td>
              <td><button class="btn btn-danger btn-sm">Delete</button></td>
            </tr>



            <tr>
              <td>SWE 314</td>
              <td>Software Security Engineering</td>
              <td>Lecture3+Tutorial1</td>
              <td><button class="btn btn-warning btn-sm">Edit</button></td>
              <td><button class="btn btn-danger btn-sm">Delete</button></td>
            </tr>



            <tr>
              <td>SWE 381</td>
              <td>Web Applications Development</td>
              <td>Lecture3+Tutorial1</td>
              <td><button class="btn btn-warning btn-sm">Edit</button></td>
              <td><button class="btn btn-danger btn-sm">Delete</button></td>
            </tr>



            <tr>
              <td>SWE 333</td>
              <td>Software Quality Assurance</td>
              <td>Lecture2+Tutorial1</td>
              <td><button class="btn btn-warning btn-sm">Edit</button></td>
              <td><button class="btn btn-danger btn-sm">Delete</button></td>
            </tr>



            <tr>
              <td>SWE 321</td>
              <td>Software Design And Architecture</td>
              <td>Lecture3+Tutorial1</td>
              <td><button class="btn btn-warning btn-sm">Edit</button></td>
              <td><button class="btn btn-danger btn-sm">Delete</button></td>
            </tr>



            <tr>
              <td>SWE 444</td>
              <td>Software Construction Laboratory</td>
              <td>Lab 4</td>
              <td><button class="btn btn-warning btn-sm">Edit</button></td>
              <td><button class="btn btn-danger btn-sm">Delete</button></td>
            </tr>



            <tr>
              <td>SWE 482</td>
              <td>Human-Computer Interaction</td>
              <td>Lecture3+Tutorial1</td>
              <td><button class="btn btn-warning btn-sm">Edit</button></td>
              <td><button class="btn btn-danger btn-sm">Delete</button></td>
            </tr>



            <tr>
              <td>SWE 434</td>
              <td>Software Testing and Validation</td>
              <td>Lecture3+Tutorial1</td>
              <td><button class="btn btn-warning btn-sm">Edit</button></td>
              <td><button class="btn btn-danger btn-sm">Delete</button></td>
            </tr>



            <tr>
              <td>SWE 477</td>
              <td>Software Engineering Code of Ethics & Professional Practice</td>
              <td>Lecture2</td><td><button class="btn btn-warning btn-sm">Edit</button></td>
              <td><button class="btn btn-danger btn-sm">Delete</button></td>
            </tr>




            <tr>
              <td>SWE 466</td>
              <td>Software Project Management</td>
              <td>Lecture3+Tutorial1</td>
              <td><button class="btn btn-warning btn-sm">Edit</button></td>
              <td><button class="btn btn-danger btn-sm">Delete</button></td>
            </tr>



            <tr>
              <td>SWE 455</td>
              <td>Software Maintenance and Evolution</td>
              <td>Lecture2+Tutorial1</td>
              <td><button class="btn btn-warning btn-sm">Edit</button></td>
              <td><button class="btn btn-danger btn-sm">Delete</button></td>
            </tr>



            <tr>
              <td>SWE 481</td>
              <td>Advanced Web Applications Engineering</td>
              <td>Lecture3+Tutorial1</td>
              <td><button class="btn btn-warning btn-sm">Edit</button></td>
              <td><button class="btn btn-danger btn-sm">Delete</button></td>
            </tr>



            <tr>
              <td>SWE 486</td>
              <td>Cloud Computing And Big Data</td>
              <td>Lecture3+Tutorial1</td>
              <td><button class="btn btn-warning btn-sm">Edit</button></td>
              <td><button class="btn btn-danger btn-sm">Delete</button></td>
            </tr>



            <tr>
              <td>SWE 485</td>
              <td>Artificial Intelligence</td>
              <td>Lecture3+Tutorial1</td>
              <td><button class="btn btn-warning btn-sm">Edit</button></td>
              <td><button class="btn btn-danger btn-sm">Delete</button></td>
            </tr>



            <tr>
              <td>SWE 483</td>
              <td>Introduction to Mobile Application Design and Implementation</td>
              <td>Lecture3+Tutorial1</td>
              <td><button class="btn btn-warning btn-sm">Edit</button></td>
              <td><button class="btn btn-danger btn-sm">Delete</button></td>
            </tr>




            <tr>
              <td>SWE 496</td>
              <td>Graduation Project 1 (No scheduled sessions)</td>
              <td>3 hrs</td>
              <td><button class="btn btn-warning btn-sm">Edit</button></td>
              <td><button class="btn btn-danger btn-sm">Delete</button></td>
            </tr>



            <tr>
              <td>SWE 497</td>
              <td>Graduation Project 2 (No scheduled sessions)</td>
              <td>3 hrs</td>
              <td><button class="btn btn-warning btn-sm">Edit</button></td>
              <td><button class="btn btn-danger btn-sm">Delete</button></td>
            </tr>


            
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
    <a class="text-white text-decoration-none" href="mailto:KSU_SmartSchedl@gmail.com">KSU_SmartSchedl@gmail.com</a>
    <p class="text-white">&copy; 2025 King Saud University</p>
  </footer>
</body>
</html>
