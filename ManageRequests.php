<?php
session_start();

if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'Administrator') {
    header("Location: login.php");
    exit;
}

// Handle API requests first, before any HTML output
if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['action'])) {
    // Set headers first to prevent any output
    header('Content-Type: application/json');
    
    try {
        // Include database configuration
        if (!file_exists('CONFIG-DB.php')) {
            throw new Exception("Database configuration file not found");
        }
        
        require_once 'CONFIG-DB.php';
        
        // Create database connection
        $conn = new mysqli(DBHOST, DBUSER, DBPWD, DBNAME);
        if ($conn->connect_error) {
            throw new Exception("Database connection failed: " . $conn->connect_error);
        }
        
        // Action to get all faculty requests
        if ($_GET['action'] === 'get_requests') {
            $sql = "SELECT 
                        f.FormID as id,
                        u.FName as faculty_name,
                        u.userID as faculty_id,
                        f.academicRank as rank,
                        f.availability,
                        f.maxHours,
                        s.name as semester,
                        f.FormID as submitted_date,
                        GROUP_CONCAT(CONCAT(p.CourseCode, ' (Rank: ', p.preferenceRank, ')') SEPARATOR ', ') as preferences
                    FROM Form f
                    JOIN Users u ON f.FacultyID = u.userID
                    JOIN Semesters s ON f.SemesterID = s.ID
                    LEFT JOIN Preferences p ON f.FormID = p.FormID
                    GROUP BY f.FormID
                    ORDER BY f.FormID DESC";

            $result = $conn->query($sql);
            
            // Check if query was successful
            if (!$result) {
                throw new Exception("SQL Error: " . $conn->error);
            }
            
            $requests = array();

            // Process each row from the result set
            if ($result->num_rows > 0) {
                while($row = $result->fetch_assoc()) {
                    $requests[] = $row;
                }
            }
            
            echo json_encode($requests);
            $conn->close();
            exit;
        }
        
        // Action to get detailed information for a specific request
        if ($_GET['action'] === 'get_request_details' && isset($_GET['id'])) {
            $requestId = $_GET['id'];
            
            $sql = "SELECT 
                        f.FormID as id,
                        u.FName as faculty_name,
                        u.userID as faculty_id,
                        f.academicRank as rank,
                        f.availability,
                        f.maxHours,
                        s.name as semester,
                        f.FormID as submitted_date
                    FROM Form f
                    JOIN Users u ON f.FacultyID = u.userID
                    JOIN Semesters s ON f.SemesterID = s.ID
                    WHERE f.FormID = ?";
            
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("i", $requestId);
            $stmt->execute();
            $result = $stmt->get_result();
            
            if ($result->num_rows > 0) {
                $request = $result->fetch_assoc();
                
                // Get course preferences for this request
                $prefSql = "SELECT 
                                p.preferenceRank,
                                p.CourseCode,
                                c.name as courseName
                            FROM Preferences p
                            LEFT JOIN SWECourses c ON p.CourseCode = c.courseCode
                            WHERE p.FormID = ?
                            ORDER BY p.preferenceRank";
                
                $prefStmt = $conn->prepare($prefSql);
                $prefStmt->bind_param("i", $requestId);
                $prefStmt->execute();
                $prefResult = $prefStmt->get_result();
                
                $preferences = array();
                while($pref = $prefResult->fetch_assoc()) {
                    $preferences[] = $pref;
                }
                
                $request['preferences'] = $preferences;
                echo json_encode($request);
            } else {
                echo json_encode(['error' => 'Request not found']);
            }
            
            $stmt->close();
            $conn->close();
            exit;
        }
    } catch (Exception $e) {
        echo json_encode(['error' => $e->getMessage()]);
        exit;
    }
}

// Handle POST requests for actions like delete
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    header('Content-Type: application/json');
    
    try {
        require_once 'CONFIG-DB.php';
        
        $conn = new mysqli(DBHOST, DBUSER, DBPWD, DBNAME);
        if ($conn->connect_error) {
            throw new Exception("Database connection failed: " . $conn->connect_error);
        }
        
        // Action to delete a faculty request
        if ($_POST['action'] === 'delete_request' && isset($_POST['id'])) {
            $formId = $_POST['id'];
            
            $conn->begin_transaction();
            
            try {
                // Delete preferences first (foreign key constraint)
                $sql1 = "DELETE FROM Preferences WHERE FormID = ?";
                $stmt1 = $conn->prepare($sql1);
                $stmt1->bind_param("i", $formId);
                $stmt1->execute();
                $stmt1->close();
                
                // Delete the form record
                $sql2 = "DELETE FROM Form WHERE FormID = ?";
                $stmt2 = $conn->prepare($sql2);
                $stmt2->bind_param("i", $formId);
                $stmt2->execute();
                $stmt2->close();
                
                $conn->commit();
                echo json_encode(['success' => true, 'message' => 'Request deleted successfully']);
                
            } catch (Exception $e) {
                $conn->rollback();
                echo json_encode(['success' => false, 'message' => 'Error deleting request: ' . $e->getMessage()]);
            }
            
            $conn->close();
            exit;
        }
    } catch (Exception $e) {
        echo json_encode(['error' => $e->getMessage()]);
        exit;
    }
}
?>

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

  <!-- Navigation Bar -->
  <nav class="navbar navbar-expand-lg fixed-top">
    <div class="container">
      <a class="navbar-brand"><img src="assets/icon.png" alt="icon"></a>
      <span class="fw-bold text-white">KSU Smart Schedule</span>
        <ul class="navbar-nav ms-auto">
           <li class="nav-item"><a class="nav-Home" href="AdministratorMain.php"><img src="assets/icons8-home-page-30.png"  alt="Home icon"></a></li>
           <li class="nav-item"><a class="nav-link fw-bold" href="logout.php"><img src="assets/logout.png"  alt="logout">Log out</a></li>
        </ul>
    </div>
  </nav>

  <!-- Main Content Area -->
  <div class="container d-flex align-items-start" style="height: 100vh;">
    <!-- Sidebar Navigation -->
    <div class="menu p-4 shadow-lg d-flex align-items-start">
      <ul class="list-group">
        <li class="list-group-item bg-transparent border-0">
          <a href="ManageLoadANDUploadScedule.php">
            <img src="assets/load.png" alt="Load & Schedule"> Load & Schedule
          </a>
        </li>
        <li class="list-group-item bg-transparent border-0">
          <a href="ManageCourseList.php">
            <img src="assets/courses.png" alt="Courses"> Courses
          </a>
        </li>
        <li class="list-group-item bg-transparent border-0 selected">
          <a href="ManageRequests.php">
            <img src="assets/icons8-form-50.png" alt="Requests"> Requests
          </a>
        </li>
        <li class="list-group-item bg-transparent border-0">
          <a href="CourseAssignmentANDConflictDetection.php">
            <img src="assets/icons8-edit-property-50.png" alt="Assignments"> Assignments
          </a>
        </li>
      </ul>
    </div>

    <!-- Main Content - Requests Table -->
    <div class="requests shadow-lg">
      <img src="assets/icons8-form-50.png" alt="requests icon">
      <h2>Manage Faculty Requests</h2>

      <div class="table-responsive mt-4">
        <table class="table table-dark table-hover align-middle" id="requestsTable">
          <thead>
            <tr>
              <th>Faculty Name</th>
              <th>Rank</th>
              <th>Availability</th>
              <th>Preferences</th>
              <th>Actions</th>
            </tr>
          </thead>
          <tbody id="requestsTableBody">
            <!-- Data will be loaded by JavaScript -->
          </tbody>
        </table>
      </div>
    </div>
  </div>

  <!-- Modal for displaying detailed request information -->
  <div class="modal fade" id="requestDetailsModal" tabindex="-1" aria-labelledby="requestDetailsModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
      <div class="modal-content bg-dark text-light">
        <div class="modal-header border-secondary">
          <h5 class="modal-title" id="requestDetailsModalLabel">Faculty Request Details</h5>
          <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>
        <div class="modal-body">
          <div class="row mb-3">
            <div class="col-md-6">
              <strong>Faculty Name:</strong>
              <span id="detail-faculty-name">-</span>
            </div>
            <div class="col-md-6">
              <strong>Faculty ID:</strong>
              <span id="detail-faculty-id">-</span>
            </div>
          </div>
          
          <div class="row mb-3">
            <div class="col-md-6">
              <strong>Academic Rank:</strong>
              <span id="detail-rank">-</span>
            </div>
            <div class="col-md-6">
              <strong>Availability:</strong>
              <span id="detail-availability">-</span>
            </div>
          </div>
          
          <div class="row mb-3">
            <div class="col-md-6">
              <strong>Max Hours:</strong>
              <span id="detail-max-hours">-</span>
            </div>
            <div class="col-md-6">
              <strong>Semester:</strong>
              <span id="detail-semester">-</span>
            </div>
          </div>
          
          <div class="row mb-3">
            <div class="col-md-6">
              <strong>Submission Date:</strong>
              <span id="detail-submission-date">-</span>
            </div>
          </div>
          
          <hr class="border-secondary">
          
          <div class="mt-4">
            <h6>Course Preferences</h6>
            <div class="table-responsive">
              <table class="table table-sm table-dark table-hover align-middle">
                <thead>
                  <tr>
                    <th>Rank</th>
                    <th>Course Code</th>
                    <th>Course Name</th>
                  </tr>
                </thead>
                <tbody id="detail-preferences">
                  <!-- Preferences will be loaded here dynamically -->
                </tbody>
              </table>
            </div>
          </div>
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

<script>
  document.addEventListener('DOMContentLoaded', function() {
    console.log("Page loaded, fetching requests...");
    loadFacultyRequests();
    
    document.getElementById('requestsTableBody').addEventListener('click', function(e) {
      if (e.target.classList.contains('view-btn')) {
        const requestId = e.target.getAttribute('data-id');
        viewFacultyRequest(requestId);
      }
      
      if (e.target.classList.contains('delete-btn')) {
        const requestId = e.target.getAttribute('data-id');
        const facultyName = e.target.getAttribute('data-name');
        deleteFacultyRequest(requestId, facultyName);
      }
    });
  });
  
  function loadFacultyRequests() {
    console.log("Loading faculty requests...");
    fetch('ManageRequests.php?action=get_requests')
      .then(response => {
        console.log("Response status:", response.status);
        if (!response.ok) {
          throw new Error('Network response was not ok: ' + response.status);
        }
        return response.json();
      })
      .then(data => {
        console.log("Data received:", data);
        const tableBody = document.getElementById('requestsTableBody');
        tableBody.innerHTML = '';
        
        if (data.error) {
          console.error("Error from server:", data.error);
          tableBody.innerHTML = `<tr><td colspan="5" class="text-center text-danger">Error: ${data.error}</td></tr>`;
          return;
        }
        
        if (data.length === 0) {
          console.log("No data received");
          tableBody.innerHTML = '<tr><td colspan="5" class="text-center">No requests found</td></tr>';
          return;
        }
        
        data.forEach(request => {
          const row = document.createElement('tr');
          row.innerHTML = `
            <td>${request.faculty_name || 'N/A'}</td>
            <td>${request.rank || 'N/A'}</td>
            <td>${getAvailabilityText(request.availability)}</td>
            <td>${request.preferences || '-'}</td>
            <td>
              <button class="btn btn-sm btn-primary view-btn" data-id="${request.id}">View</button>
              <button class="btn btn-sm btn-danger delete-btn" data-id="${request.id}" data-name="${request.faculty_name || 'Unknown'}">Delete</button>
            </td>
          `;
          tableBody.appendChild(row);
        });
      })
      .catch(error => {
        console.error('Error loading requests:', error);
        const tableBody = document.getElementById('requestsTableBody');
        tableBody.innerHTML = `<tr><td colspan="5" class="text-center text-danger">Error loading requests: ${error.message}</td></tr>`;
      });
  }

  function getAvailabilityText(availabilityCode) {
    switch(availabilityCode) {
      case 'F': return 'Full Load';
      case 'P': return 'Partially Available';
      case 'U': return 'Unavailable';
      default: return availabilityCode || 'Unknown';
    }
  }

  function viewFacultyRequest(requestId) {
    fetch(`ManageRequests.php?action=get_request_details&id=${requestId}`)
      .then(response => response.json())
      .then(data => {
        if (data.error) {
          alert('Error: ' + data.error);
          return;
        }
        
        document.getElementById('detail-faculty-name').textContent = data.faculty_name || '-';
        document.getElementById('detail-faculty-id').textContent = data.faculty_id || '-';
        document.getElementById('detail-rank').textContent = data.rank || '-';
        document.getElementById('detail-availability').textContent = getAvailabilityText(data.availability) || '-';
        document.getElementById('detail-max-hours').textContent = data.maxHours || '-';
        document.getElementById('detail-semester').textContent = data.semester || '-';
        document.getElementById('detail-submission-date').textContent = data.submitted_date || '-';
        
        const preferencesTable = document.getElementById('detail-preferences');
        preferencesTable.innerHTML = '';
        
        if (data.preferences && data.preferences.length > 0) {
          data.preferences.forEach(pref => {
            const row = document.createElement('tr');
            row.innerHTML = `
              <td>${pref.preferenceRank}</td>
              <td>${pref.CourseCode}</td>
              <td>${pref.courseName || '-'}</td>
            `;
            preferencesTable.appendChild(row);
          });
        } else {
          const row = document.createElement('tr');
          row.innerHTML = '<td colspan="3" class="text-center">No preferences found</td>';
          preferencesTable.appendChild(row);
        }
        
        const modal = new bootstrap.Modal(document.getElementById('requestDetailsModal'));
        modal.show();
      })
      .catch(error => {
        console.error('Error fetching request details:', error);
        alert('Error loading request details');
      });
  }
  
  function deleteFacultyRequest(requestId, facultyName) {
    if (confirm(`Are you sure you want to delete the request for ${facultyName}?`)) {
      const formData = new FormData();
      formData.append('action', 'delete_request');
      formData.append('id', requestId);
      
      fetch('ManageRequests.php', {
        method: 'POST',
        body: formData
      })
      .then(response => response.json())
      .then(data => {
        if (data.success) {
          alert(`Request for ${facultyName} has been deleted.`);
          loadFacultyRequests();
        } else {
          alert('Error deleting request: ' + data.message);
        }
      })
      .catch(error => {
        console.error('Error:', error);
        alert('Error deleting request.');
      });
    }
  }
</script>

</body>
</html>
