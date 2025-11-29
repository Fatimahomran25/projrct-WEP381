<?php
session_start();
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'Administrator') {
    header("Location: login.php");
    exit;
}

require_once 'CONFIG-DB.php';

$requests = []; // Start with empty array
$error = null;

try {
    $conn = new mysqli(DBHOST, DBUSER, DBPWD, DBNAME);
    
    if ($conn->connect_error) {
        throw new Exception("Connection failed: " . $conn->connect_error);
    }

    // The issue: Form.FacultyID links to facultymember.FacultyID, not directly to Users.userID
    // We need to join through the facultymember table
    $sql = "SELECT 
                f.FormID as id,
                u.FName as faculty_name,
                u.userID as faculty_id,
                f.academicRank as rank,
                f.availability,
                f.maxHours,
                s.name as semester,
                GROUP_CONCAT(CONCAT(p.CourseCode, ' (Rank: ', p.preferenceRank, ')') SEPARATOR ', ') as preferences
            FROM Form f
            JOIN facultymember fm ON f.FacultyID = fm.FacultyID
            JOIN Users u ON fm.userID = u.userID
            JOIN Semesters s ON f.SemesterID = s.ID
            LEFT JOIN Preferences p ON f.FormID = p.FormID
            GROUP BY f.FormID
            ORDER BY f.FormID DESC";

    $result = $conn->query($sql);
    
    if ($result === false) {
        throw new Exception("Query failed: " . $conn->error);
    }
    
    if ($result->num_rows > 0) {
        $requests = $result->fetch_all(MYSQLI_ASSOC);
    } else {
        // Debug information
        $test_sql = "SELECT COUNT(*) as total FROM form";
        $test_result = $conn->query($test_sql);
        $test_data = $test_result->fetch_assoc();
        
        // Test the joins separately
        $join_test = "SELECT 
                        f.FormID, 
                        f.FacultyID as form_faculty_id,
                        fm.FacultyID as member_faculty_id,
                        fm.userID as member_user_id,
                        u.userID as user_id,
                        u.FName
                    FROM Form f
                    LEFT JOIN facultymember fm ON f.FacultyID = fm.FacultyID
                    LEFT JOIN Users u ON fm.userID = u.userID";
        $join_result = $conn->query($join_test);
        
        $error = "No requests found, but there are " . $test_data['total'] . " forms in the database. ";
        $error .= "Join test returned " . $join_result->num_rows . " rows.";
    }
    
    $conn->close();
    
} catch (Exception $e) {
    $error = $e->getMessage();
}

// variable to store detailed request data
$detailedRequest = null;
if (isset($_GET['view_id'])) {
    try {
        $conn = new mysqli(DBHOST, DBUSER, DBPWD, DBNAME);
        
        if ($conn->connect_error) {
            throw new Exception("Connection failed: " . $conn->connect_error);
        }
        
        $viewId = intval($_GET['view_id']);
        $sql = "SELECT 
                    f.FormID as id,
                    u.FName as faculty_name,
                    u.userID as faculty_id,
                    f.academicRank as rank,
                    f.availability,
                    f.maxHours,
                    s.name as semester
                FROM Form f
                JOIN facultymember fm ON f.FacultyID = fm.FacultyID
                JOIN Users u ON fm.userID = u.userID
                JOIN Semesters s ON f.SemesterID = s.ID
                WHERE f.FormID = ?";
        
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("i", $viewId);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows > 0) {
            $detailedRequest = $result->fetch_assoc();
            
            // Get preferences
            $prefSql = "SELECT 
                            p.preferenceRank,
                            p.CourseCode,
                            c.name as courseName
                        FROM Preferences p
                        LEFT JOIN SWECourses c ON p.CourseCode = c.courseCode
                        WHERE p.FormID = ?
                        ORDER BY p.preferenceRank";
            
            $prefStmt = $conn->prepare($prefSql);
            $prefStmt->bind_param("i", $viewId);
            $prefStmt->execute();
            $prefResult = $prefStmt->get_result();
            
            $preferences = [];
            while($pref = $prefResult->fetch_assoc()) {
                $preferences[] = $pref;
            }
            $detailedRequest['preferences'] = $preferences;
        }
        $conn->close();
        
    } catch (Exception $e) {
        $error = $e->getMessage();
    }
}

// Handle delete button
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_id'])) {
    try {
        $conn = new mysqli(DBHOST, DBUSER, DBPWD, DBNAME);
        
        if ($conn->connect_error) {
            throw new Exception("Connection failed: " . $conn->connect_error);
        }
        
        $deleteId = intval($_POST['delete_id']);
        
        // Delete preferences first (child records)
        $deletePrefs = $conn->prepare("DELETE FROM Preferences WHERE FormID = ?");
        $deletePrefs->bind_param("i", $deleteId);
        $deletePrefs->execute();
        $deletePrefs->close();
        
        // Delete the main form record
        $deleteForm = $conn->prepare("DELETE FROM Form WHERE FormID = ?");
        $deleteForm->bind_param("i", $deleteId);
        
        if ($deleteForm->execute()) {
            $conn->close();
            header("Location: ManageRequests.php?deleted=1");
            exit;
        } else {
            throw new Exception("Delete failed: " . $conn->error);
        }
        
    } catch (Exception $e) {
        $error = $e->getMessage();
    }
}

// Function to convert availability code to text
function getAvailabilityText($code) {
    $map = [
        'A' => 'Available', 
        'P' => 'Partially Available', 
        'U' => 'Unavailable',
        'F' => 'Fully Available',
        'Y' => 'Available'
    ];
    return $map[$code] ?? $code;
}

// Function to convert academic rank code to full name
function getRankText($rank) {
    $map = [
        'PROF' => 'Professor',
        'ASCP' => 'Associate Professor', 
        'ASST' => 'Assistant Professor',
        'LECT' => 'Lecturer',
        'TAST' => 'Teaching Assistant',
        'Professor' => 'Professor',
        'Teaching Assistant' => 'Teaching Assistant'
    ];
    return $map[$rank] ?? $rank;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Manage Requests</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="style.css" rel="stylesheet">
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</head>
<body>

  <nav class="navbar navbar-expand-lg fixed-top">
    <div class="container">
      <a class="navbar-brand"><img src="assets/icon.png" alt="icon"></a>
      <span class="fw-bold text-white">KSU Smart Schedule</span>
      <ul class="navbar-nav ms-auto">
        <li class="nav-item"><a class="nav-Home" href="AdministratorMain.php"><img src="assets/icons8-home-page-30.png" alt="Home"></a></li>
        <li class="nav-item"><a class="nav-link fw-bold" href="logout.php"><img src="assets/logout.png" alt="logout">Log out</a></li>
      </ul>
    </div>
  </nav>

  <div class="container d-flex align-items-start" style="height: 100vh;">
    <!-- Sidebar -->
    <div class="menu p-4 shadow-lg d-flex align-items-start">
      <ul class="list-group">
        <li class="list-group-item bg-transparent border-0"><a href="ManageLoadANDUploadScedule.php"><img src="assets/load.png" alt="Load"> Load & Schedule</a></li>
        <li class="list-group-item bg-transparent border-0"><a href="ManageCourseList.php"><img src="assets/courses.png" alt="Courses"> Courses</a></li>
        <li class="list-group-item bg-transparent border-0 selected"><a href="ManageRequests.php"><img src="assets/icons8-form-50.png" alt="Requests"> Requests</a></li>
        <li class="list-group-item bg-transparent border-0"><a href="CourseAssignmentANDConflictDetection.php"><img src="assets/icons8-edit-property-50.png" alt="Assignments"> Assignments</a></li>
      </ul>
    </div>

    <!-- Main Content -->
    <div class="requests shadow-lg">
      <img src="assets/icons8-form-50.png" alt="requests icon">
      <h2>Manage Faculty Requests</h2>

      <?php if (isset($_GET['deleted'])): ?>
        <div class="alert alert-success">Request deleted successfully!</div>
      <?php endif; ?>

      <?php if (isset($error)): ?>
        <div class="alert alert-danger">Error: <?= htmlspecialchars($error) ?></div>
      <?php endif; ?>

      <div class="table-responsive mt-4">
        <table class="table table-dark table-hover align-middle">
          <thead>
            <tr>
              <th>ID</th>
              <th>Faculty Name</th>
              <th>Rank</th>
              <th>Availability</th>
              <th>Max Hours</th>
              <th>Semester</th>
              <th>Preferences</th>
              <th>Actions</th>
            </tr>
          </thead>
          <tbody>
            <?php if (empty($requests)): ?>
              <tr>
                <td colspan="8" class="text-center">No requests found in the database</td>
              </tr>
            <?php else: ?>
              <?php foreach ($requests as $request): ?>
              <tr>
                <td><?= htmlspecialchars($request['id'] ?? 'N/A') ?></td>
                <td><?= htmlspecialchars($request['faculty_name'] ?? 'N/A') ?></td>
                <td><?= getRankText($request['rank'] ?? 'N/A') ?></td>
                <td><?= getAvailabilityText($request['availability'] ?? '') ?></td>
                <td><?= htmlspecialchars($request['maxHours'] ?? 'N/A') ?></td>
                <td><?= htmlspecialchars($request['semester'] ?? 'N/A') ?></td>
                <td><?= htmlspecialchars($request['preferences'] ?? 'No preferences') ?></td>
                <td>
                  <!-- View Button -->
                  <a href="?view_id=<?= $request['id'] ?>" class="btn btn-sm btn-primary">View</a>
                  
                  <!-- Delete Button -->
                  <form method="POST" style="display: inline;" onsubmit="return confirm('Are you sure you want to delete request #<?= $request['id'] ?> for <?= htmlspecialchars($request['faculty_name'] ?? 'this faculty') ?>?')">
                    <input type="hidden" name="delete_id" value="<?= $request['id'] ?>">
                    <button type="submit" class="btn btn-sm btn-danger">Delete</button>
                  </form>
                </td>
              </tr>
              <?php endforeach; ?>
            <?php endif; ?>
          </tbody>
        </table>
      </div>
    </div>
  </div>

  <!-- View Modal -->
  <?php if ($detailedRequest): ?>
  <div class="modal fade show" style="display: block; background: rgba(0,0,0,0.5);" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered modal-lg">
      <div class="modal-content bg-dark text-light">
        <div class="modal-header border-secondary">
          <h5 class="modal-title">Faculty Request Details #<?= $detailedRequest['id'] ?></h5>
          <a href="ManageRequests.php" class="btn-close btn-close-white"></a>
        </div>
        <div class="modal-body">
          <div class="row mb-3">
            <div class="col-md-6"><strong>Faculty Name:</strong> <?= htmlspecialchars($detailedRequest['faculty_name'] ?? '-') ?></div>
            <div class="col-md-6"><strong>Faculty ID:</strong> <?= htmlspecialchars($detailedRequest['faculty_id'] ?? '-') ?></div>
          </div>
          <div class="row mb-3">
            <div class="col-md-6"><strong>Academic Rank:</strong> <?= getRankText($detailedRequest['rank'] ?? '-') ?></div>
            <div class="col-md-6"><strong>Availability:</strong> <?= getAvailabilityText($detailedRequest['availability'] ?? '') ?></div>
          </div>
          <div class="row mb-3">
            <div class="col-md-6"><strong>Max Hours:</strong> <?= htmlspecialchars($detailedRequest['maxHours'] ?? '-') ?></div>
            <div class="col-md-6"><strong>Semester:</strong> <?= htmlspecialchars($detailedRequest['semester'] ?? '-') ?></div>
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
                <tbody>
                  <?php if (!empty($detailedRequest['preferences'])): ?>
                    <?php foreach ($detailedRequest['preferences'] as $pref): ?>
                    <tr>
                      <td><?= htmlspecialchars($pref['preferenceRank']) ?></td>
                      <td><?= htmlspecialchars($pref['CourseCode']) ?></td>
                      <td><?= htmlspecialchars($pref['courseName'] ?? '-') ?></td>
                    </tr>
                    <?php endforeach; ?>
                  <?php else: ?>
                    <tr><td colspan="3" class="text-center">No preferences found</td></tr>
                  <?php endif; ?>
                </tbody>
              </table>
            </div>
          </div>
        </div>
        <div class="modal-footer border-secondary">
          <a href="ManageRequests.php" class="btn btn-secondary btn-sm">Close</a>
        </div>
      </div>
    </div>
  </div>
  <?php endif; ?>

  <footer class="footer glass-nav">
    <div class="footer-logo"><img src="assets/Vision.png" alt="Vision 2030"></div>
    <a class="text-white text-decoration-none" href="mailto:KSU_SmartSchedl@gmail.com">KSU_SmartSchedl@gmail.com</a>
    <p class="text-white">&copy; 2025 King Saud University</p>
  </footer>

</body>
</html>
