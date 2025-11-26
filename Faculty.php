<?php

session_start();

require_once("CONFIG-DB.php");


$conn = mysqli_connect(DBHOST, DBUSER, DBPWD, DBNAME);
if (!$conn) {
    die("DB connection failed: " . mysqli_connect_error());
}


if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'Faculty') {
    header("Location: login.php");
    exit;
}
if (!isset($_SESSION['userID']) || empty($_SESSION['userID'])) {
    header("Location: logout.php");
    exit;
}
$userID = intval($_SESSION['userID']);

function is_auto_increment($conn, $table, $column) {
    $res = @mysqli_query($conn, "SHOW COLUMNS FROM `{$table}` LIKE '{$column}'");
    if (!$res) return false;
    $row = mysqli_fetch_assoc($res);
    return (isset($row['Extra']) && stripos($row['Extra'], 'auto_increment') !== false);
}
function next_id($conn, $table, $column) {
    $res = @mysqli_query($conn, "SELECT COALESCE(MAX(`{$column}`),0) + 1 AS nextid FROM `{$table}`");
    if (!$res) return 1;
    $r = mysqli_fetch_assoc($res);
    return intval($r['nextid']);
}

$facultyID = null;
$stmt = mysqli_prepare($conn, "SELECT FacultyID FROM facultymember WHERE userID = ? LIMIT 1");
if ($stmt) {
    mysqli_stmt_bind_param($stmt, "i", $userID);
    mysqli_stmt_execute($stmt);
    $res = mysqli_stmt_get_result($stmt);
    if ($res && mysqli_num_rows($res) > 0) {
        $facultyID = intval(mysqli_fetch_assoc($res)['FacultyID']);
    }
    mysqli_stmt_close($stmt);
}

if ($facultyID === null) {
    $attemptCreated = false;
    $colsRes = @mysqli_query($conn, "SHOW COLUMNS FROM facultymember");
    if ($colsRes) {
        $cols = [];
        while ($c = mysqli_fetch_assoc($colsRes)) $cols[] = $c['Field'];
        if (in_array('FacultyID', $cols) && in_array('userID', $cols)) {
            $newFacultyID = next_id($conn, 'facultymember', 'FacultyID');
            $insSql = "INSERT INTO facultymember (FacultyID, userID) VALUES (?, ?)";
            $ins = @mysqli_prepare($conn, $insSql);
            if ($ins) {
                mysqli_stmt_bind_param($ins, "ii", $newFacultyID, $userID);
                if (mysqli_stmt_execute($ins)) {
                    $facultyID = $newFacultyID;
                    $attemptCreated = true;
                }
                mysqli_stmt_close($ins);
            }
        }
    }
    if (!$attemptCreated && $facultyID === null) {
        die("Faculty record not found for this user (UserID {$userID}). Please ask the administrator to add a 'facultymember' mapping for your account, or allow auto-creation by ensuring the 'facultymember' table has FacultyID and userID columns.");
    }
}

$semesterID = null;
$q = "SELECT ID, name FROM semesters WHERE isActiveForLoadRequest = 1 ORDER BY ID DESC LIMIT 1";
$r = mysqli_query($conn, $q);
if ($r && mysqli_num_rows($r) > 0) {
    $semRow = mysqli_fetch_assoc($r);
    $semesterID = intval($semRow['ID']);
    $semesterName = htmlspecialchars($semRow['name']);
} else {
    $semesterID = 0;
    $semesterName = "No Active Semester";
}

$isFormSubmitted = false;
if ($semesterID > 0) {
    $checkQ = "SELECT FormID FROM form WHERE FacultyID = ? AND SemesterID = ? LIMIT 1";
    $checkStmt = mysqli_prepare($conn, $checkQ);
    if ($checkStmt) {
        mysqli_stmt_bind_param($checkStmt, "ii", $facultyID, $semesterID);
        mysqli_stmt_execute($checkStmt);
        $checkRes = mysqli_stmt_get_result($checkStmt);
        if ($checkRes && mysqli_num_rows($checkRes) > 0) {
            $isFormSubmitted = true;
        }
        mysqli_stmt_close($checkStmt);
    }
}
$submitButtonText = $isFormSubmitted ? 'Submitted' : 'Submit Preferences';

$courseList = [];
$courseQ = "
    SELECT sc.courseCode, sc.name, sc.lectureHours, sc.tutorialHours, sc.labHours, sc.noSessionHours
    FROM swecourses sc
    INNER JOIN (
        SELECT DISTINCT courseCode FROM coursesections WHERE semesterID = ?
    ) cs ON cs.courseCode = sc.courseCode
    ORDER BY sc.courseCode ASC
";
if ($semesterID > 0) {
    $stmtCourse = mysqli_prepare($conn, $courseQ);
    if ($stmtCourse) {
        mysqli_stmt_bind_param($stmtCourse, "i", $semesterID);
        mysqli_stmt_execute($stmtCourse);
        $courseR = mysqli_stmt_get_result($stmtCourse);
        if ($courseR) {
            while ($row = mysqli_fetch_assoc($courseR)) {
                $totalHours = intval($row['lectureHours']) + intval($row['tutorialHours']) + intval($row['labHours']) + intval($row['noSessionHours']);
                $parts = [];
                if (intval($row['lectureHours']) > 0) $parts[] = "Lecture " . intval($row['lectureHours']);
                if (intval($row['tutorialHours']) > 0) $parts[] = "Tutorial " . intval($row['tutorialHours']);
                if (intval($row['labHours']) > 0) $parts[] = "Lab " . intval($row['labHours']);
                $hoursDisplay = implode(" + ", $parts);
                if ($hoursDisplay === "") $hoursDisplay = "No scheduled hours";

                $courseList[] = [
                    'CourseCode' => $row['courseCode'],
                    'CourseName' => $row['name'],
                    'HoursDisplay' => $hoursDisplay,
                    'LectureHours' => intval($row['lectureHours']),
                    'TutorialHours' => intval($row['tutorialHours']),
                    'LabHours' => intval($row['labHours']),
                    'TotalHours' => $totalHours
                ];
            }
        }
        mysqli_stmt_close($stmtCourse);
    }
}
$totalCourseCount = count($courseList);

$form_table_columns = ['academicRank', 'maxHours']; 
if (@mysqli_query($conn, "SELECT academic_rank, max_hours FROM form LIMIT 1") !== false) {
     $form_table_columns = ['academic_rank', 'max_hours']; 
}
$academicRankCol = $form_table_columns[0];
$maxHoursCol = $form_table_columns[1];

// --- Academic rank maps & max hours
$rank_map = [
    'Professor'=>'PROF','Associate Professor'=>'ASCP','Assistant Professor'=>'ASST',
    'Lecturer'=>'LECT','Teaching Assistant'=>'TAST'
];
$maxHoursMap = ['PROF'=>10,'ASCP'=>12,'ASST'=>14,'LECT'=>16,'TAST'=>16];

// Partial load rule: floor(FullLoad/2)
function partial_load($full) {
    return intdiv($full, 2); // floor division
}

// 6. Handle POST submission (The core logic)
$submissionMessage = "";
if ($_SERVER['REQUEST_METHOD'] === 'POST' && !$isFormSubmitted) {
    
    $academic_rank_raw = $_POST['academic_rank'] ?? '';
    $availability_raw = $_POST['availability'] ?? '';
    $courseRanks = $_POST['rank'] ?? []; 
    
    if (trim($academic_rank_raw) === '') {
        $submissionMessage = "Academic rank is required.";
    } elseif (trim($availability_raw) === '') {
        $submissionMessage = "Availability is required.";
    }

    if (empty($submissionMessage)) {
        $recheckQ = "SELECT FormID FROM form WHERE FacultyID = ? AND SemesterID = ? LIMIT 1";
        $recheckStmt = mysqli_prepare($conn, $recheckQ);
        if ($recheckStmt) {
            mysqli_stmt_bind_param($recheckStmt, "ii", $facultyID, $semesterID);
            mysqli_stmt_execute($recheckStmt);
            $reRes = mysqli_stmt_get_result($recheckStmt);
            if ($reRes && mysqli_num_rows($reRes) > 0) {
                $submissionMessage = "You have already submitted preferences for this semester.";
                $isFormSubmitted = true;
            }
            mysqli_stmt_close($recheckStmt);
        }
    }

    $availability_map = ['available'=>'A','Partially'=>'P','unavailable'=>'U'];
    $availability = $availability_map[$availability_raw] ?? null;
    $academicRank = $rank_map[$academic_rank_raw] ?? null;
    $maxHours = $academicRank ? ($maxHoursMap[$academicRank] ?? 14) : 14;
    $partialMaxHours = partial_load($maxHours);

    if (empty($submissionMessage)) {
        if ($availability === null) {
            $submissionMessage = "Invalid availability selected.";
        } elseif ($academicRank === null) {
            $submissionMessage = "Invalid academic rank selected.";
        }
    }

    $validatedPrefs = [];
    if (empty($submissionMessage) && $availability !== 'U') {
        $used = [];
        // build map of offered courses for quick check
        $offered = [];
        foreach ($courseList as $c) $offered[$c['CourseCode']] = $c;

        foreach ($courseRanks as $code => $rv) {
            $code = trim($code);
            $rv = trim($rv);
            if ($rv === '') continue;
            if (!preg_match('/^[0-9]+$/', $rv)) {
                $submissionMessage = "Ranks must be positive integers.";
                break;
            }
            $rint = intval($rv);
            if ($rint < 1) { $submissionMessage = "Ranks must be >= 1."; break; }
            if (in_array($rint, $used, true)) { $submissionMessage = "Duplicate ranks detected. Use unique ranks."; break; }
            if (!isset($offered[$code])) { $submissionMessage = "Course {$code} is not offered this semester."; break; }
            $used[] = $rint;
            $validatedPrefs[$code] = $rint;
        }
        if (empty($submissionMessage) && count($validatedPrefs) === 0) {
            $submissionMessage = "You are available but you did not rank any course.";
        }
    }

    if (empty($submissionMessage) && $availability !== 'U') {
        $totalRequested = 0;
        foreach ($validatedPrefs as $code => $rk) {
            foreach ($courseList as $c) {
                if ($c['CourseCode'] === $code) { $totalRequested += intval($c['TotalHours']); break; }
            }
        }
        $allowed = ($availability === 'Partially') ? $partialMaxHours : $maxHours;
        if ($totalRequested > $allowed) {
            $submissionMessage = "Requested total hours ({$totalRequested}) exceed your allowed hours ({$allowed}) for the selected availability.";
        }
    }

    if (empty($submissionMessage)) {
        mysqli_begin_transaction($conn);
        try {
            $form_auto = is_auto_increment($conn, 'form', 'FormID');
            $formID = $form_auto ? null : next_id($conn, 'form', 'FormID');

            $cols = $form_auto ? "FacultyID, SemesterID, availability, {$academicRankCol}, {$maxHoursCol}" : "FormID, FacultyID, SemesterID, availability, {$academicRankCol}, {$maxHoursCol}";
            $placeholders = $form_auto ? "?, ?, ?, ?, ?" : "?, ?, ?, ?, ?, ?";
            $bindTypes = $form_auto ? "iissi" : "iiissi";
            $bindValues = $form_auto ? [$facultyID, $semesterID, $availability, $academicRank, $maxHours] : [$formID, $facultyID, $semesterID, $availability, $academicRank, $maxHours];

            $sql_insert = "INSERT INTO form ({$cols}) VALUES ({$placeholders})";
            $stmtForm = mysqli_prepare($conn, $sql_insert);
            
            if (!$stmtForm) {
                throw new Exception("SQL INSERT Prepare Failed: " . mysqli_error($conn));
            } else {
                mysqli_stmt_bind_param($stmtForm, $bindTypes, ...$bindValues);
                if (!mysqli_stmt_execute($stmtForm)) {
                    throw new Exception("DB Execute Error (Form): " . mysqli_stmt_error($stmtForm));
                } else {
                    $formID = $form_auto ? mysqli_insert_id($conn) : $formID;

                    if (!empty($validatedPrefs)) {
                        $pref_auto = is_auto_increment($conn, 'preferences', 'PrefID');
                        $pref_cols = $pref_auto ? "FormID, CourseCode, preferenceRank" : "PrefID, FormID, CourseCode, preferenceRank";
                        $pref_ph = $pref_auto ? "?, ?, ?" : "?, ?, ?, ?";
                        $pref_types = $pref_auto ? "isi" : "iisi";

                        $insPref = mysqli_prepare($conn, "INSERT INTO preferences ({$pref_cols}) VALUES ({$pref_ph})");
                        if (!$insPref) { throw new Exception("DB Error (Pref Prepare): " . mysqli_error($conn)); }
                        
                        foreach ($validatedPrefs as $code => $rv) {
                            $rankInt = intval($rv);
                            if ($rankInt < 1) continue; 
                            
                            $prefID = $pref_auto ? null : next_id($conn, 'preferences', 'PrefID');
                            if ($pref_auto) {
                                mysqli_stmt_bind_param($insPref, $pref_types, $formID, $code, $rankInt);
                            } else {
                                mysqli_stmt_bind_param($insPref, $pref_types, $prefID, $formID, $code, $rankInt);
                            }
                            if (!mysqli_stmt_execute($insPref)) {
                                throw new Exception("DB Execute Error (Pref): " . mysqli_stmt_error($insPref));
                            }
                        }
                        mysqli_stmt_close($insPref);
                    }
                }
                mysqli_stmt_close($stmtForm);
            }

            mysqli_commit($conn);
            $isFormSubmitted = true;
            mysqli_close($conn);
            header("Location: Faculty.php?submitted=1");
            exit;

        } catch (Exception $ex) {
            mysqli_rollback($conn);
            $submissionMessage = "Submission failed: " . $ex->getMessage();
        }
    }
}
if (isset($conn)) mysqli_close($conn);
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="utf-8">

  <!-- Bootstrap CSS -->
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">

  <link href="style.css" rel="stylesheet">

  <title>Faculty Page </title>

  <!-- Bootstrap JS -->
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</head>


<body>

  <!-- NAVBAR -->
  <div class="menu p-4 shadow-lg d-flex">
    <nav class="navbar navbar-expand-lg fixed-top">
      <div class="container">
        <a class="navbar-brand"><img src="assets/icon.png" alt="icon"></a>
        <span class="fw-bold text-white">KSU Smart Schedule</span>

        <ul class="navbar-nav ms-auto">
          <li class="nav-item">
            <a class="nav-link fw-bold" href="logout.php">
              <img src="assets/logout.png" alt="logout"> Log out
            </a>
          </li>
        </ul>
      </div>
    </nav>
  </div>


  <!-- MAIN PAGE -->
  <div class="container-fluid d-flex justify-content-center align-items-start mt-0 pt-5">
    <div class="menu p-4 shadow-lg w-100 d-flex flex-column align-items-center">
      <div class="content-area w-100">
        <div class="view-list">

          <h2 class="view-list-heading">View Upcoming Semester List</h2>

          <?php if (isset($_GET['submitted'])): ?>
            <div class="alert alert-success">Preferences submitted successfully.</div>
          <?php endif; ?>

          <?php if (!empty($submissionMessage)): ?>
            <div class="alert alert-danger"><?php echo htmlspecialchars($submissionMessage); ?></div>
          <?php endif; ?>

          <!-- Card 1 -->
          <div class="card shadow-sm mb-3">
            <div class="card-body d-flex justify-content-between align-items-center">
              <div>
                <h5 class="card-title">Fall 2026 Semester</h5>
                <p class="mb-0 text-success">Preference Submission Open</p>
              </div>
              <!-- Button to show/hide the preference -->
              <?php if (!$isFormSubmitted): ?>
                <button class="btn btn-success" data-bs-toggle="collapse" data-bs-target="#preferenceForm">
                  Add course preferences
                </button>
              <?php else: ?>
                <button class="btn btn-success" disabled>Submitted</button>
              <?php endif; ?>
            </div>
          </div>


          <!-- Card 2 -->
          <div class="card shadow-sm mb-4">
            <div class="card-body d-flex justify-content-between align-items-center">
              <div>
                <h5 class="card-title">Spring 2027 Semester</h5>
                <p class="mb-0 text-danger">Submission Closed (Not Yet Activated)</p>
              </div>
            </div>
          </div>


          <!-- Collapse Form -->
          <div class="collapse" id="preferenceForm">
            <hr>
            <h2 class="submit mb-3">Submit Preferences for <?php echo htmlspecialchars($semesterName); ?></h2>

            <?php if ($isFormSubmitted): ?>
              <div class="alert alert-info">You have already submitted your preferences for <?php echo htmlspecialchars($semesterName); ?>.</div>
            <?php else: ?>
            <form id="preference-form" method="POST" action="" onsubmit="return validateFormSubmission();">

              <div id="messageBox" style="display:none;"></div>

              <!-- Academic Rank Selection -->
              <fieldset class="p-3 border rounded mb-3 bg-light">
                <legend class="fs-5">1. Select Academic Rank</legend>
                <label for="academic-rank" class="form-label">Select your Rank:</label>

                <!-- Dropdown for selecting rank (name added) -->
                <select id="academic-rank" name="academic_rank" class="form-select mb-2" onchange="updateUIState()" required>
                  <option value="">-- Choose rank --</option>
                  <option value="Professor">Professor (Max 10 hrs)</option>
                  <option value="Associate Professor">Associate Professor (Max 12 hrs)</option>
                  <option value="Assistant Professor">Assistant Professor (Max 14 hrs)</option>
                  <option value="Lecturer">Lecturer (Max 16 hrs)</option>
                  <option value="Teaching Assistant">Teaching Assistant (Max 16 hrs - tutorials/labs only)</option>
                </select>
                <div class="small text-muted">Max hours are enforced. If you choose "Partially Available" your load will be half of the maximum.</div>
              </fieldset>

              <!--Availability Selection -->
              <fieldset class="p-3 border rounded mb-3 bg-light">
                <legend class="fs-5">Confirm Availability</legend>
             

                <div class="form-check">
                  <input class="form-check-input" type="radio" name="availability" id="avail_full" value="available" onchange="updateUIState()" checked required>
                  <label class="form-check-label" for="avail_full">Available: Full teaching load</label>
                </div>

                <div class="form-check">
                  <input class="form-check-input" type="radio" name="availability" id="avail_partial" value="Partially" onchange="updateUIState()">
                  <label class="form-check-label" for="avail_partial">Partially Available: Reduced load </label>
                </div>

                <div class="form-check">
                  <input class="form-check-input" type="radio" name="availability" id="avail_none" value="unavailable" onchange="updateUIState()">
                  <label class="form-check-label" for="avail_none">Unavailable: On leave (load = 0 hours)</label>
                </div>
              </fieldset>

              <!-- Course Preferences Table -->
              <div id="course-preferences-container">
                <fieldset class="p-3 border rounded mb-3 bg-light">
                  <legend class="fs-5">Add Course Preferences</legend>
                  <p class="small text-danger">Teaching Assistants are restricted to tutorials/labs only.</p>

                  <!-- Table for entering course preferences -->
                  <div class="table">
                    <table class="table table-bordered align-middle" id="course-preference-table">
                      <thead class="table-dark text-center">
                        <tr>
                          <th>Rank</th>
                          <th>Course Code</th>
                          <th>Course Name</th>
                          <th>Hours</th>
                        </tr>
                      </thead>

                      <tbody>
                        <?php foreach ($courseList as $course):
                            $courseCode = htmlspecialchars($course['CourseCode']);
                            $courseName = htmlspecialchars($course['CourseName']);
                            $hoursDisplay = htmlspecialchars($course['HoursDisplay']);
                            $totalHours = intval($course['TotalHours']);
                            $lec = intval($course['LectureHours']);
                            $tut = intval($course['TutorialHours']);
                            $lab = intval($course['LabHours']);
                        ?>
                        <tr data-course-code="<?php echo $courseCode; ?>" data-total-hours="<?php echo $totalHours; ?>"
                            data-lec="<?php echo $lec; ?>" data-tut="<?php echo $tut; ?>" data-lab="<?php echo $lab; ?>">
                          <td>
                            <input type="number" name="rank[<?php echo $courseCode; ?>]"
                              class="form-control form-control-sm course-rank-input"
                              min="1" max="<?php echo $totalCourseCount; ?>"
                              oninput="validateRanks(this); calculateTotalHours();"
                              >
                          </td>
                          <td><?php echo $courseCode; ?></td>
                          <td><?php echo $courseName; ?></td>
                          <td><?php echo $hoursDisplay; ?></td>
                        </tr>
                        <?php endforeach; ?>
                      </tbody>
                    </table>
                  </div>


                  <div class="alert alert-warning text-center">
                    <strong>Total Requested Hours:</strong>
                    <span id="total-hours-display">0</span> hours
                    <div id="allowed-hours-note" class="small mt-1 text-white"></div>
                  </div>
                </fieldset>
              </div>
                <button type="submit" id="submitBtn" class="btn btn-success w-100 btn-lg mb-4" <?php echo $isFormSubmitted ? 'disabled' : ''; ?>><?php echo $isFormSubmitted ? 'Submitted' : 'Submit Preferences'; ?></button>
            </form>
            <?php endif; ?>
          </div>
        </div>
      </div>
    </div>
  </div>

  <!-- FOOTER  -->
  <footer class="footer glass-nav fixed-bottom">
    <div class="footer-logo">
      <img src="assets/Vision.png" alt="Vision 2030">
    </div>
    <a class="text-white text-decoration-none" href="mailto:KSU_SmartSchedl@gmail.com">KSU_SmartSchedl@gmail.com</a>
    <p class="text-white">&copy; <?php echo date('Y'); ?> King Saud University</p>
  </footer>


  <script>
    // Client-side JS: keep your original functions and add allowed-hours checks
    const MAX_RANK = <?php echo $totalCourseCount; ?>;

    // map full loads (must match server)
    const FULL_LOAD = {
      'Professor': 10,
      'Associate Professor': 12,
      'Assistant Professor': 14,
      'Lecturer': 16,
      'Teaching Assistant': 16
    };

    function getAllRankInputs() {
        return document.querySelectorAll('.course-rank-input');
    }
    function calculateTotalHours() {
        const rows = document.querySelectorAll('#course-preference-table tbody tr');
        let total = 0;
        rows.forEach(row => {
            const input = row.querySelector('.course-rank-input');
            const v = parseInt(input.value);
            if (!isNaN(v) && v >= 1 && v <= parseInt(input.getAttribute('max'))) {
                const h = parseInt(row.getAttribute('data-total-hours')) || 0;
                total += h;
            }
        });
        document.getElementById('total-hours-display').textContent = total;

        // show allowed hours note
        const allowedNote = document.getElementById('allowed-hours-note');
        const rank = getAcademicRankValue();
        const availability = getAvailabilityValue();
        const full = FULL_LOAD[rank] || 14;
        const allowed = (availability === 'Partially') ? Math.floor(full / 2) : (availability === 'unavailable' ? 0 : full);
        if (allowedNote) {
            allowedNote.textContent = `Allowed hours for selected rank/availability: ${allowed} hrs`;
        }
        // mark warning if exceed
        const totalDisplay = document.getElementById('total-hours-display');
        if (total > allowed) {
            totalDisplay.parentElement.classList.add('border', 'border-danger');
        } else {
            totalDisplay.parentElement.classList.remove('border', 'border-danger');
        }
    }

    function validateRanks(currentInput) {
        const inputs = getAllRankInputs();
        const used = new Set();
        let ok = true;
        inputs.forEach(i => i.setCustomValidity(''));
        inputs.forEach(i => {
            const v = i.value.trim();
            if (v !== '') {
                const n = parseInt(v);
                if (isNaN(n) || n < 1) return;
                if (used.has(n)) {
                    i.setCustomValidity('Duplicate rank');
                    ok = false;
                }
                used.add(n);
            }
        });
        if (currentInput && currentInput.reportValidity) currentInput.reportValidity();
        return ok;
    }

    function getAvailabilityValue() {
        const r = document.querySelector('input[name="availability"]:checked');
        return r ? r.value : 'available';
    }
    function getAcademicRankValue() {
        const s = document.getElementById('academic-rank');
        return s ? s.value : 'Assistant Professor';
    }

    function updateUIState() {
        const availability = getAvailabilityValue();
        const rank = getAcademicRankValue();
        const isTA = (rank === 'Teaching Assistant');
        const rows = document.querySelectorAll('#course-preference-table tbody tr');
        let visibleCount = 0;
        rows.forEach(row => {
            const input = row.querySelector('.course-rank-input');

            // Use data attributes (set server-side) to detect lecture/tutorial/lab
            const L = parseInt(row.getAttribute('data-lec')) || 0;
            const T = parseInt(row.getAttribute('data-tut')) || 0;
            const Lab = parseInt(row.getAttribute('data-lab')) || 0;

            // Teaching Assistant restriction: hide course if TA AND the course has NO T and NO Lab
            const restrictedForTA = (isTA && (T === 0 && Lab === 0));

            if (restrictedForTA) {
                row.style.display = 'none';
                input.value = '';
            } else {
                row.style.display = '';
                visibleCount++;
            }
input.setAttribute('max', MAX_RANK);        });

        // hide whole container if unavailable
        const container = document.getElementById('course-preferences-container');
        if (availability === 'unavailable') container.style.display = 'none';
        else container.style.display = 'block';

        calculateTotalHours();
        validateRanks();
    }

    // --- New: inline message box functions (replaces alert) ---
    function showMessage(text, type = 'error') {
        const box = document.getElementById('messageBox');
        if (!box) {
            alert(text); // fallback
            return;
        }
        box.style.display = 'block';
        box.className = ''; // reset classes
        if (type === 'error') box.classList.add('alert', 'alert-danger');
        else if (type === 'success') box.classList.add('alert', 'alert-success');
        else box.classList.add('alert', 'alert-info');
        box.innerText = text;
        box.scrollIntoView({behavior: 'smooth', block: 'center'});
        // auto-hide after 5s for info/success, keep for error until user changes something
        if (type !== 'error') {
            setTimeout(()=>{ box.style.display = 'none'; }, 4000);
        }
    }
    function clearMessage() {
        const box = document.getElementById('messageBox');
        if (box) { box.style.display = 'none'; box.innerText = ''; box.className = ''; }
    }

    function validateFormSubmission() {
        clearMessage();
        // Ensure academic rank and availability are chosen
        const rankSel = document.getElementById('academic-rank');
        if (!rankSel || rankSel.value.trim() === '') {
            showMessage('Please select your academic rank.', 'error');
            if (rankSel) rankSel.focus();
            return false;
        }
        const availability = getAvailabilityValue();
        if (!availability) {
            showMessage('Please select your availability.', 'error');
            return false;
        }

        if (!validateRanks()) {
            showMessage('Please fix duplicate ranks before submitting.', 'error');
            return false;
        }

        if (availability !== 'unavailable') {
            const ranked = Array.from(getAllRankInputs()).filter(i=>{
                const v = parseInt(i.value);
                return !isNaN(v) && v >=1 && i.closest('tr').style.display !== 'none';
            });
            if (ranked.length === 0) {
                showMessage('You are available but you did not rank any course.', 'error');
                return false;
            }

            // client-side total hours check
            const total = parseInt(document.getElementById('total-hours-display').textContent) || 0;
            const rank = getAcademicRankValue();
            const full = FULL_LOAD[rank] || 14;
            const allowed = (availability === 'Partially') ? Math.floor(full / 2) : full;
            if (total > allowed) {
                showMessage('Total requested hours (' + total + ') exceed your allowed hours (' + allowed + '). Please reduce selections.', 'error');
                return false;
            }
        }

        // disable the button to prevent double-submit
        const btn = document.getElementById('submitBtn');
        if (btn) btn.disabled = true;
        return true;
    }

    document.addEventListener('DOMContentLoaded', () => {
        document.querySelectorAll('input[name="availability"]').forEach(r=>r.addEventListener('change', updateUIState));
        const ar = document.getElementById('academic-rank');
        if (ar) ar.addEventListener('change', updateUIState);
        updateUIState();
    });
  </script>

</body>
</html>

