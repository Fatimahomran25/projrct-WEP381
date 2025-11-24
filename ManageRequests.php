<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Manage Requests</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="style.css" rel="stylesheet">
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
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

  <!-- Content -->
  <div class="container d-flex align-items-start" style="height: 100vh;">
    <!-- Sidebar -->
    <div class="menu p-4 shadow-lg d-flex align-items-start">
      <ul class="list-group">
        <li class="list-group-item bg-transparent border-0">
          <a href="ManageLoadANDUploadScedule.HTML">
            <img src="assets/load.png" alt="Load & Schedule"> Load & Schedule
          </a>
        </li>
        <li class="list-group-item bg-transparent border-0">
          <a href="ManageCourseList.html">
            <img src="assets/courses.png" alt="Courses"> Courses
          </a>
        </li>
        <li class="list-group-item bg-transparent border-0 selected">
          <a href="ManageRequests.html">
            <img src="assets/icons8-form-50.png" alt="Requests"> Requests
          </a>
        </li>
        <li class="list-group-item bg-transparent border-0">
          <a href="CourseAssignmentANDConflictDetection.html">
            <img src="assets/icons8-edit-property-50.png" alt="Assignments"> Assignments
          </a>
        </li>
      </ul>
    </div>

    <!-- Requests Table -->
    <div class="requests shadow-lg">
      <img src="assets/icons8-form-50.png" alt="requests icon">
      <h2>Manage Faculty Requests</h2>

      <div class="table-responsive mt-4">
        <table class="table table-dark table-hover align-middle">
          <thead>
            <tr>
              <th>Faculty Name</th>
              <th>Rank</th>
              <th>Availability</th>
              <th>Preferences</th>
              <th>Status</th>
              <th>Actions</th>
            </tr>
          </thead>
<tbody>
  <!-- Ahmed -->
  <tr>
    <td>Dr. Ahmed Ali</td>
    <td>Professor</td>
    <td>Full Load</td>
    <td>SWE 381, SWE 321</td>
    <td><span class="badge bg-warning text-dark">Pending</span></td>
    <td>
      <button class="btn btn-sm btn-primary" data-bs-toggle="modal" data-bs-target="#modalAhmed">View</button>
      <button class="btn btn-sm btn-danger">Delete</button>
    </td>
  </tr>

  <!-- Sara -->
  <tr>
    <td>Dr. Sara Saleh</td>
    <td>Assistant Prof.</td>
    <td>Partially Available</td>
    <td>SWE 312, SWE 314</td>
    <td><span class="badge bg-success">Approved</span></td>
    <td>
      <button class="btn btn-sm btn-primary" data-bs-toggle="modal" data-bs-target="#modalSara">View</button>
      <button class="btn btn-sm btn-danger">Delete</button>
    </td>
  </tr>

  <!-- Khalid -->
  <tr>
    <td>Mr. Khalid Omar</td>
    <td>Lecturer</td>
    <td>Unavailable</td>
    <td>-</td>
    <td><span class="badge bg-secondary">Closed</span></td>
    <td>
      <button class="btn btn-sm btn-primary" data-bs-toggle="modal" data-bs-target="#modalKhalid">View</button>
      <button class="btn btn-sm btn-danger">Delete</button>
    </td>
  </tr>
</tbody>
        </table>
      </div>
    </div>
  </div>

<!-- Modal: Dr. Ahmed Ali -->
<div class="modal fade" id="modalAhmed" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered modal-lg">
    <div class="modal-content bg-dark text-light">
      <div class="modal-header border-secondary">
        <h5 class="modal-title">Faculty Request - Dr. Ahmed Ali</h5>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
      </div>

      <div class="modal-body">
        <div class="row">
          <div class="col-md-6">
            <p><strong>Faculty ID:</strong> 102345</p>
            <p><strong>Rank:</strong> Professor</p>
            <p><strong>Availability:</strong> Full Load</p>
            <p><strong>Maximum Hours:</strong> 10 hrs</p>
          </div>
          <div class="col-md-6">
            <p><strong>Semester:</strong> Fall 2025</p>
            <p><strong>Submitted On:</strong> Sept 25, 2025</p>
            <p><strong>Preferred Teaching Time:</strong> Morning</p>
          </div>
        </div>

        <hr class="border-secondary">

        <p><strong>Preferred Courses:</strong></p>
        <ul>
          <li>SWE 381 – Web Applications Development</li>
          <li>SWE 321 – Software Design and Architecture</li>
        </ul>

        <p><strong>Comments:</strong> Prefers morning classes, no weekends.</p>
      </div>

      <div class="modal-footer border-secondary">
        <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Close</button>
      </div>
    </div>
  </div>
</div>

<!-- Modal: Dr. Sara Saleh -->
<div class="modal fade" id="modalSara" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered modal-lg">
    <div class="modal-content bg-dark text-light">
      <div class="modal-header border-secondary">
        <h5 class="modal-title">Faculty Request - Dr. Sara Saleh</h5>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
      </div>

      <div class="modal-body">
        <div class="row">
          <div class="col-md-6">
            <p><strong>Faculty ID:</strong> 104876</p>
            <p><strong>Rank:</strong> Assistant Professor</p>
            <p><strong>Availability:</strong> Partially Available (50%)</p>
            <p><strong>Maximum Hours:</strong> 7 hrs</p>
          </div>
          <div class="col-md-6">
            <p><strong>Semester:</strong> Fall 2025</p>
            <p><strong>Submitted On:</strong> Sept 26, 2025</p>
            <p><strong>Preferred Teaching Time:</strong> After 10 AM</p>
          </div>
        </div>

        <hr class="border-secondary">

        <p><strong>Preferred Courses:</strong></p>
        <ul>
          <li>SWE 312 – Software Requirements Engineering</li>
          <li>SWE 314 – Software Security Engineering</li>
        </ul>

        <p><strong>Comments:</strong> Handling postgraduate advising duties, prefers lighter teaching schedule.</p>
      </div>

      <div class="modal-footer border-secondary">
        <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Close</button>
      </div>
    </div>
  </div>
</div>

<!-- Modal: Mr. Khalid Omar -->
<div class="modal fade" id="modalKhalid" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered modal-lg">
    <div class="modal-content bg-dark text-light">
      <div class="modal-header border-secondary">
        <h5 class="modal-title">Faculty Request - Mr. Khalid Omar</h5>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
      </div>

      <div class="modal-body">
        <div class="row">
          <div class="col-md-6">
            <p><strong>Faculty ID:</strong> 109234</p>
            <p><strong>Rank:</strong> Lecturer</p>
            <p><strong>Availability:</strong> Unavailable</p>
            <p><strong>Maximum Hours:</strong> 16 hrs</p>
          </div>
          <div class="col-md-6">
            <p><strong>Semester:</strong> Fall 2025</p>
            <p><strong>Submitted On:</strong> Sept 20, 2025</p>
            <p><strong>Preferred Teaching Time:</strong> –</p>
          </div>
        </div>

        <hr class="border-secondary">

        <p><strong>Preferred Courses:</strong></p>
        <ul>
          <li>– (No preferences submitted)</li>
        </ul>

        <p><strong>Comments:</strong> On academic leave this semester.</p>
      </div>

      <div class="modal-footer border-secondary">
        <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Close</button>
      </div>
    </div>
  </div>
</div>


  <!-- Footer -->
<footer class="footer glass-nav">
    <div class="footer-logo ">
      <img src="assets/Vision.png" alt="Vision 2030" >
    </div>
    <a class="text-white text-decoration-none" href="mailto:KSU_SmartSchedl@gmail.com">KSU_SmartSchedl@gmail.com</a>
    <p class=" text-white">&copy; 2025 King Saud University</p>
</footer>

</body>
</html>