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

$allSemesters = [];
$semQ = "SELECT ID, name, isActiveForLoadRequest, isAvailableForFaculty FROM semesters ORDER BY ID DESC";
$semR = mysqli_query($conn, $semQ);
if ($semR) {
    while ($sr = mysqli_fetch_assoc($semR)) $allSemesters[] = $sr;
}

$form_table_columns = ['academicRank', 'maxHours']; 
if (@mysqli_query($conn, "SELECT academic_rank, max_hours FROM form LIMIT 1") !== false) {
     $form_table_columns = ['academic_rank', 'max_hours']; 
}
$academicRankCol = $form_table_columns[0];
$maxHoursCol = $form_table_columns[1];

$rank_map = [
    'Professor'=>'PROF','Associate Professor'=>'ASCP','Assistant Professor'=>'ASST',
    'Lecturer'=>'LECT','Teaching Assistant'=>'TAST'
];
$maxHoursMap = ['PROF'=>10,'ASCP'=>12,'ASST'=>14,'LECT'=>16,'TAST'=>16];

function partial_load($full) {
    return intdiv($full, 2); // floor division
}

$ALL_SEMESTER_COURSE_DATA = [];

foreach ($allSemesters as $sem) {
    $semesterID = intval($sem['ID']);
    $courseListLocal = [];

    $courseQ = "
        SELECT sc.courseCode, sc.name, sc.lectureHours, sc.tutorialHours, sc.labHours, sc.noSessionHours
        FROM swecourses sc
        ORDER BY sc.courseCode ASC
    ";
    $stmtCourseAll = mysqli_prepare($conn, $courseQ);
    $courseR = mysqli_stmt_get_result($stmtCourseAll);
    if ($stmtCourseAll) {
        mysqli_stmt_execute($stmtCourseAll);
        $courseR = mysqli_stmt_get_result($stmtCourseAll);
        if ($courseR) {
            while ($row = mysqli_fetch_assoc($courseR)) {
                
                $courseCode = $row['courseCode'];
                $lectureHours = intval($row['lectureHours']);
                $tutorialHours = intval($row['tutorialHours']);
                $labHours = intval($row['labHours']);
                $noSessionHours = intval($row['noSessionHours']);

                if ($courseCode === 'SWE 496' || $courseCode === 'SWE 497' || $courseCode === 'GP1' || $courseCode === 'GP2') {
                    $totalHours = 3;
                    $hoursDisplay = "3 hrs (Fixed Load)";
                    $lectureHours = 0;
                    $tutorialHours = 0;
                    $labHours = 0;
                } else {
                    $totalHours = $lectureHours + $tutorialHours + $labHours + $noSessionHours;
                    
                    $parts = [];
                    if ($lectureHours > 0) $parts[] = "Lecture " . $lectureHours;
                    if ($tutorialHours > 0) $parts[] = "Tutorial " . $tutorialHours;
                    if ($labHours > 0) $parts[] = "Lab " . $labHours;
                    $hoursDisplay = implode(" + ", $parts);
                    if ($hoursDisplay === "") $hoursDisplay = "No scheduled hours";
                }
                // --- END Override ---

                $courseListLocal[] = [
                    'CourseCode' => $courseCode,
                    'CourseName' => $row['name'],
                    'HoursDisplay' => $hoursDisplay,
                    'LectureHours' => $lectureHours,
                    'TutorialHours' => $tutorialHours,
                    'LabHours' => $labHours,
                    'TotalHours' => $totalHours 
                ];
            }
        }
        mysqli_stmt_close($stmtCourseAll);
    }
    $ALL_SEMESTER_COURSE_DATA[$semesterID] = $courseListLocal;
}

$actionSemesterID = 0;
$submissionMessage = "";
$courseList = [];
$totalCourseCount = 0;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    
    $academic_rank_raw = $_POST['academic_rank'] ?? '';
    $availability_raw = $_POST['availability'] ?? '';
    $courseRanks = $_POST['rank'] ?? []; 
    $actionSemesterID = intval($_POST['semester_id'] ?? 0); 
    $reason_raw = $_POST['availability_reason'] ?? ''; 
    
    $actionSemesterName = "N/A";
    $isSubmissionOpen = false;

    if ($actionSemesterID === 0) {
        $submissionMessage = "Submission failed: Semester ID is missing.";
    } else {
        // Find semester details from pre-loaded array
        $semInfo = null;
        foreach ($allSemesters as $s) {
            if (intval($s['ID']) === $actionSemesterID) {
                $semInfo = $s;
                $actionSemesterName = htmlspecialchars($s['name']);
                $isSubmissionOpen = (intval($s['isActiveForLoadRequest']) === 1 && intval($s['isAvailableForFaculty']) === 1);
                break;
            }
        }

        if (!$isSubmissionOpen) {
             $submissionMessage = "Submission failed: Preferences are currently closed for {$actionSemesterName}.";
        }
        
        $isAlreadySubmitted = false;
        $recheckQ = "SELECT FormID FROM form WHERE FacultyID = ? AND SemesterID = ? LIMIT 1";
        $recheckStmt = mysqli_prepare($conn, $recheckQ);
        if ($recheckStmt) {
            mysqli_stmt_bind_param($recheckStmt, "ii", $facultyID, $actionSemesterID);
            mysqli_stmt_execute($recheckStmt);
            $reRes = mysqli_stmt_get_result($recheckStmt);
            if ($reRes && mysqli_num_rows($reRes) > 0) {
                $submissionMessage = "You have already submitted preferences for {$actionSemesterName}.";
                $isAlreadySubmitted = true;
            }
            mysqli_stmt_close($recheckStmt);
        }
        
        $courseList = $ALL_SEMESTER_COURSE_DATA[$actionSemesterID] ?? [];
        $totalCourseCount = count($courseList);
        
        if (!$isAlreadySubmitted && $isSubmissionOpen && empty($submissionMessage)) {
            
            if (trim($academic_rank_raw) === '') {
                $submissionMessage = "Academic rank is required.";
            } elseif (trim($availability_raw) === '') {
                $submissionMessage = "Availability is required.";
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
            
            if (($availability === 'P' || $availability === 'U') && trim($reason_raw) === '') {
                $submissionMessage = "Reason for partial availability or unavailability is required.";
            }


            $validatedPrefs = [];
            if (empty($submissionMessage) && $availability !== 'U') {
                $used = [];
                $offered = [];
                foreach ($courseList as $c) $offered[$c['CourseCode']] = $c;

                foreach ($courseRanks as $code => $rv) {
                    $code = trim($code);
                    $rv = trim($rv);
                    if ($rv === '' || $rv === '0') continue;
                    
                    if (!preg_match('/^[0-9]+$/', $rv)) {
                        $submissionMessage = "Ranks must be positive integers.";
                        break;
                    }
                    $rint = intval($rv);
                    if ($rint < 1) { $submissionMessage = "Ranks must be >= 1 (or 0/empty to skip)."; break; } 
                    if (in_array($rint, $used, true)) { $submissionMessage = "Duplicate ranks detected. Use unique ranks."; break; }
                    if (!isset($offered[$code])) { $submissionMessage = "Course {$code} is not offered this semester."; break; }
                    $used[] = $rint;
                    $validatedPrefs[$code] = $rint;
                }
                if (empty($submissionMessage) && count($validatedPrefs) === 0 && $totalCourseCount > 0) {
                    $submissionMessage = "You are available but you did not rank any course.";
                } elseif (empty($submissionMessage) && $totalCourseCount === 0) {
                    $submissionMessage = "No courses are available for ranking in this semester.";
                }
            }

            if (empty($submissionMessage) && $availability !== 'U') {
                $totalRequested = 0;
                foreach ($courseRanks as $code => $rv) {
                    if (trim($rv) !== '' && intval($rv) > 0) {
                        foreach ($courseList as $c) {
                            if ($c['CourseCode'] === $code) { $totalRequested += intval($c['TotalHours']); break; }
                        }
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
                    $bindValues = $form_auto ? [$facultyID, $actionSemesterID, $availability, $academicRank, $maxHours] : [$formID, $facultyID, $actionSemesterID, $availability, $academicRank, $maxHours];

                    $sql_insert = "INSERT INTO form ({$cols}) VALUES ({$placeholders})";
                    $stmtForm = mysqli_prepare($conn, $sql_insert);
                    
                    if (!$stmtForm) { throw new Exception("SQL INSERT Prepare Failed: " . mysqli_error($conn)); }
                    
                    mysqli_stmt_bind_param($stmtForm, $bindTypes, ...$bindValues);
                    if (!mysqli_stmt_execute($stmtForm)) { throw new Exception("DB Execute Error (Form): " . mysqli_stmt_error($stmtForm)); }
                    
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
                            $prefBindValues = $pref_auto ? [$formID, $code, $rankInt] : [$prefID, $formID, $code, $rankInt];

                            mysqli_stmt_bind_param($insPref, $pref_types, ...$prefBindValues);
                            if (!mysqli_stmt_execute($insPref)) {
                                throw new Exception("DB Execute Error (Pref): " . mysqli_stmt_error($insPref));
                            }
                        }
                        mysqli_stmt_close($insPref);
                    }

                    mysqli_commit($conn);
                    
                    mysqli_close($conn); 
                    header("Location: Faculty.php");
                    exit;

                } catch (Exception $ex) {
                    mysqli_rollback($conn);
                    $submissionMessage = "Submission failed: " . $ex->getMessage();
                }
            }
        }
    }
} 

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">

    <link href="style.css" rel="stylesheet">

    <title>Faculty Page</title>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>

    <script>
        const FULL_LOAD = {
            'Professor': 10,
            'Associate Professor': 12,
            'Assistant Professor': 14,
            'Lecturer': 16,
            'Teaching Assistant': 16
        };
        const ALL_SEMESTER_COURSES = <?php echo json_encode($ALL_SEMESTER_COURSE_DATA); ?>;
    </script>
</head>


<body>

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


    <div class="container-fluid d-flex justify-content-center align-items-start mt-0 pt-5">
        <div class="menu p-4 shadow-lg w-100 d-flex flex-column align-items-center">
            <div class="content-area w-100">
                <div class="view-list">

                    <h2 class="view-list-heading">View Upcoming Semester List</h2>

                    <?php if (!empty($submissionMessage) && $actionSemesterID !== 0): ?>
                        <div class="alert alert-danger"><?php echo htmlspecialchars($submissionMessage); ?></div>
                    <?php endif; ?>
                    
                    <div id="semesterAccordion">

                    <?php if (count($allSemesters) === 0): ?>
                        <div class="alert alert-warning">No semesters defined in the system.</div>
                    <?php else: ?>
                        <?php foreach ($allSemesters as $sem): 
                            $semesterID = intval($sem['ID']);
                            $semesterName = htmlspecialchars($sem['name']);
                            
                            $isActiveForLoad = intval($sem['isActiveForLoadRequest']) === 1;
                            $isAvailableForFaculty = intval($sem['isAvailableForFaculty']) === 1;
                            $isSubmissionOpen = $isActiveForLoad && $isAvailableForFaculty;

                            $isFormSubmittedLocal = false;
                            $checkQ = "SELECT FormID FROM form WHERE FacultyID = ? AND SemesterID = ? LIMIT 1";
                            $checkStmt = mysqli_prepare($conn, $checkQ); 
                            if ($checkStmt) {
                                mysqli_stmt_bind_param($checkStmt, "ii", $facultyID, $semesterID);
                                mysqli_stmt_execute($checkStmt);
                                $checkRes = mysqli_stmt_get_result($checkStmt);
                                if ($checkRes && mysqli_num_rows($checkRes) > 0) {
                                    $isFormSubmittedLocal = true;
                                }
                                mysqli_stmt_close($checkStmt);
                            }

                            if ($isFormSubmittedLocal) {
                                $statusText = "Submitted";
                                $statusClass = "text-success";
                                $buttonDisabled = 'disabled';
                                $buttonText = 'Submitted';
                                $buttonClass = 'btn-success';
                            } elseif ($isSubmissionOpen) {
                                $statusText = "Preference Submission Open";
                                $statusClass = "text-success";
                                $buttonDisabled = '';
                                $buttonText = 'Add course preferences';
                                $buttonClass = 'btn-success';
                            } else {
                                $statusText = "Submission Closed (Not activated)";
                                $statusClass = "text-danger";
                                $buttonDisabled = 'disabled';
                                $buttonText = 'Not available';
                                $buttonClass = 'btn-secondary';
                            }

                            $currentCourseList = $ALL_SEMESTER_COURSE_DATA[$semesterID];
                            $currentTotalCount = count($currentCourseList);
                        ?>
                        <div class="card shadow-sm mb-3 semester-card" data-semester-id="<?php echo $semesterID; ?>">
                            <div class="card-body d-flex justify-content-between align-items-center">
                                <div>
                                    <h5 class="card-title"><?php echo $semesterName; ?></h5>
                                    <p class="mb-0 <?php echo $statusClass; ?>"><?php echo $statusText; ?></p>
                                </div>
                                
                                <button class="<?php echo $buttonClass; ?> toggle-form-btn" type="button" 
                                        data-bs-toggle="collapse" 
                                        data-bs-target="#preferenceForm_<?php echo $semesterID; ?>"
                                        aria-expanded="false" 
                                        aria-controls="preferenceForm_<?php echo $semesterID; ?>"
                                        data-course-count="<?php echo $currentTotalCount; ?>"
                                        <?php echo $buttonDisabled; ?>>
                                    <?php echo $buttonText; ?>
                                </button>
                            </div>
                        </div>

                        <?php 
                        $isErrorSemester = ($actionSemesterID === $semesterID && !empty($submissionMessage));

                        if ($isSubmissionOpen && !$isFormSubmittedLocal): 
                        ?>
                            <div class="collapse preference-form-collapse <?php echo $isErrorSemester ? 'show' : ''; ?>" 
                                 id="preferenceForm_<?php echo $semesterID; ?>" 
                                 data-bs-parent="#semesterAccordion">
                                <hr>
                                <h2 class="submit mb-3">Submit Preferences for <?php echo $semesterName; ?></h2>

                                <form id="preference-form-<?php echo $semesterID; ?>" method="POST" action="" onsubmit="return validateFormSubmission(this, <?php echo $semesterID; ?>);">

                                    <div id="messageBox_<?php echo $semesterID; ?>" class="alert" 
                                         style="display:<?php echo $isErrorSemester ? 'block' : 'none'; ?>;">
                                        <?php if ($isErrorSemester) echo htmlspecialchars($submissionMessage); ?>
                                    </div>
                                    <input type="hidden" name="semester_id" value="<?php echo $semesterID; ?>">
                                    <input type="hidden" id="course_count_<?php echo $semesterID; ?>" value="<?php echo $currentTotalCount; ?>">

                                    <fieldset class="p-3 border rounded mb-3 bg-light">
                                        <legend class="fs-5">1. Select Academic Rank</legend>
                                        <label for="academic-rank-<?php echo $semesterID; ?>" class="form-label">Select your Rank:</label>

                                        <select id="academic-rank-<?php echo $semesterID; ?>" name="academic_rank" class="form-select mb-2" onchange="updateUIState(<?php echo $semesterID; ?>)" required>
                                            <option value="">-- Choose rank --</option>
                                            <option value="Professor">Professor (Max 10 hrs)</option>
                                            <option value="Associate Professor">Associate Professor (Max 12 hrs)</option>
                                            <option value="Assistant Professor">Assistant Professor (Max 14 hrs)</option>
                                            <option value="Lecturer">Lecturer (Max 16 hrs)</option>
                                            <option value="Teaching Assistant">Teaching Assistant (Max 16 hrs )</option>
                                        </select>
                                        <div class="small text-muted">Max hours are enforced. If you choose "Partially Available" your load will be half of the maximum.</div>
                                    </fieldset>

                                    <fieldset class="p-3 border rounded mb-3 bg-light">
                                        <legend class="fs-5">Confirm Availability</legend>
                                    
                                        <div class="form-check">
                                            <input class="form-check-input" type="radio" name="availability" id="avail_full_<?php echo $semesterID; ?>" value="available" onchange="updateUIState(<?php echo $semesterID; ?>)" checked required>
                                            <label class="form-check-label" for="avail_full_<?php echo $semesterID; ?>">Available: Full teaching load</label>
                                        </div>

                                        <div class="form-check">
                                            <input class="form-check-input" type="radio" name="availability" id="avail_partial_<?php echo $semesterID; ?>" value="Partially" onchange="updateUIState(<?php echo $semesterID; ?>)">
                                            <label class="form-check-label" for="avail_partial_<?php echo $semesterID; ?>">Partially Available: Reduced load </label>
                                        </div>

                                        <div class="form-check">
                                            <input class="form-check-input" type="radio" name="availability" id="avail_none_<?php echo $semesterID; ?>" value="unavailable" onchange="updateUIState(<?php echo $semesterID; ?>)">
                                            <label class="form-check-label" for="avail_none_<?php echo $semesterID; ?>">Unavailable: On leave </label>
                                        </div>
                                        
                                        <div id="reason-box-<?php echo $semesterID; ?>" style="display:none; margin-top: 10px;">
                                            <label for="availability_reason_<?php echo $semesterID; ?>" class="form-label small">Reason (Required):</label>
                                            <textarea class="form-control form-control-sm" name="availability_reason" id="availability_reason_<?php echo $semesterID; ?>" rows="2" maxlength="255"></textarea>
                                        </div>
                                        
                                    </fieldset>

                                    <div id="course-preferences-container-<?php echo $semesterID; ?>">
                                        <fieldset class="p-3 border rounded mb-3 bg-light">
                                            <legend class="fs-5">Add Course Preferences</legend>
                                            <p class="small text-danger">Teaching Assistants are restricted to tutorials/labs only.</p>

                                            <div class="table">
                                                <table class="table table-bordered align-middle" id="course-preference-table-<?php echo $semesterID; ?>">
                                                    <thead class="table-dark text-center">
                                                        <tr>
                                                            <th>Rank</th>
                                                            <th>Course Code</th>
                                                            <th>Course Name</th>
                                                            <th>Hours</th>
                                                        </tr>
                                                    </thead>
                                                    <tbody>
                                                        <?php 
                                                        if (!empty($currentCourseList)): 
                                                            foreach ($currentCourseList as $course):
                                                                $code = htmlspecialchars($course['CourseCode']);
                                                                $name = htmlspecialchars($course['CourseName']);
                                                                $hours = htmlspecialchars($course['HoursDisplay']);
                                                                $total = intval($course['TotalHours']);
                                                                $lec = intval($course['LectureHours']);
                                                                $tut = intval($course['TutorialHours']);
                                                                $lab = intval($course['LabHours']);
                                                        ?>
                                                        <tr data-course-code="<?php echo $code; ?>" data-total-hours="<?php echo $total; ?>"
                                                            data-lec="<?php echo $lec; ?>" data-tut="<?php echo $tut; ?>" data-lab="<?php echo $lab; ?>">
                                                            <td>
                                                                <input type="number" name="rank[<?php echo $code; ?>]"
                                                                    class="form-control form-control-sm course-rank-input-<?php echo $semesterID; ?>"
                                                                    max="<?php echo $currentTotalCount; ?>" 
                                                                    oninput="validateRanks(this, <?php echo $semesterID; ?>); calculateTotalHours(<?php echo $semesterID; ?>);"
                                                                    value="<?php echo ($isErrorSemester && isset($courseRanks[$code]) ? htmlspecialchars($courseRanks[$code]) : ''); ?>"
                                                                    >
                                                            </td>
                                                            <td><?php echo $code; ?></td>
                                                            <td><?php echo $name; ?></td>
                                                            <td><?php echo $hours; ?></td>
                                                        </tr>
                                                        <?php 
                                                            endforeach;
                                                        else: 
                                                        ?>
                                                        <tr><td colspan="4">No courses are available for ranking in this semester.</td></tr>
                                                        <?php endif; ?>
                                                    </tbody>
                                                </table>
                                            </div>

                                            <div class="alert alert-warning text-center">
                                                <strong>Total Requested Hours:</strong>
                                                <span id="total-hours-display-<?php echo $semesterID; ?>">0</span> hours
                                                <div id="allowed-hours-note-<?php echo $semesterID; ?>" class="small mt-1 text-white"></div>
                                            </div>
                                        </fieldset>
                                    </div>
                                    <button type="submit" id="submitBtn_<?php echo $semesterID; ?>" class="btn btn-success w-100 btn-lg mb-4">Submit Preferences</button>
                                </form>
                            </div>
                        <?php endif; ?>
                        <?php endforeach; ?>
                    </div>
                    </div> 
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <footer class="footer glass-nav fixed-bottom">
        <div class="footer-logo">
            <img src="assets/Vision.png" alt="Vision 2030">
        </div>
        <a class="text-white text-decoration-none" href="mailto:KSU_SmartSchedl@gmail.com">KSU_SmartSchedl@gmail.com</a>
        <p class="text-white">&copy; <?php echo date('Y'); ?> King Saud University</p>
    </footer>


    <script>
        // --- Core JS Functions ---
        function getAllRankInputs(semesterID) {
            return document.querySelectorAll(`#course-preference-table-${semesterID} .course-rank-input-${semesterID}`);
        }
        
        function getAcademicRankValue(semesterID) {
            const s = document.getElementById(`academic-rank-${semesterID}`);
            return s ? s.value : '';
        }
        
        function getAvailabilityValue(semesterID) {
            const r = document.querySelector(`#preference-form-${semesterID} input[name="availability"]:checked`);
            return r ? r.value : 'available';
        }

        function calculateTotalHours(semesterID) {
            const rows = document.querySelectorAll(`#course-preference-table-${semesterID} tbody tr`);
            let total = 0;
            rows.forEach(row => {
                if (row.style.display !== 'none') {
                    const input = row.querySelector(`.course-rank-input-${semesterID}`);
                    const v = input.value.trim();
                    
                    const rint = parseInt(v);
                    if (!isNaN(rint) && rint >= 1) { 
                        const h = parseInt(row.getAttribute('data-total-hours')) || 0;
                        total += h;
                    }
                }
            });
            document.getElementById(`total-hours-display-${semesterID}`).textContent = total;

            const allowedNote = document.getElementById(`allowed-hours-note-${semesterID}`);
            const rank = getAcademicRankValue(semesterID);
            const availability = getAvailabilityValue(semesterID);
            
            let fullLoad = 0;
            if (rank && FULL_LOAD[rank]) {
                fullLoad = FULL_LOAD[rank];
            } 
            
            const allowed = (availability === 'Partially') ? Math.floor(fullLoad / 2) : (availability === 'unavailable' ? 0 : fullLoad);
            
            if (allowedNote) {
                allowedNote.textContent = `Allowed hours for selected rank/availability: ${allowed} hrs`;
            }
            
            const totalDisplayParent = document.getElementById(`total-hours-display-${semesterID}`).parentElement;
            if (total > allowed) {
                totalDisplayParent.classList.add('alert-danger');
                totalDisplayParent.classList.remove('alert-warning');
            } else {
                totalDisplayParent.classList.add('alert-warning');
                totalDisplayParent.classList.remove('alert-danger');
            }
        }

        function validateRanks(currentInput, semesterID) {
            const inputs = getAllRankInputs(semesterID);
            const used = new Set();
            let ok = true;
            inputs.forEach(i => i.setCustomValidity('')); 
            
            inputs.forEach(i => {
                if (i.closest('tr').style.display === 'none') return; 
                
                const v = i.value.trim();
                if (v !== '' && v !== '0') { 
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

        function updateUIState(semesterID) {
            const availability = getAvailabilityValue(semesterID);
            const rank = getAcademicRankValue(semesterID);
            const isTA = (rank === 'Teaching Assistant');
            const table = document.getElementById(`course-preference-table-${semesterID}`);
            
            // Get the reason box element
            const reasonBox = document.getElementById(`reason-box-${semesterID}`);
            const reasonInput = document.getElementById(`availability_reason_${semesterID}`);

            // 1. Handle Reason Box Visibility
            if (availability === 'Partially' || availability === 'unavailable') {
                if (reasonBox) reasonBox.style.display = 'block';
                // Add required attribute for validation only when visible
                if (reasonInput) reasonInput.setAttribute('required', 'required');
            } else {
                if (reasonBox) reasonBox.style.display = 'none';
                // Remove required attribute when hidden
                if (reasonInput) reasonInput.removeAttribute('required');
            }


            // 2. Handle Course Table Visibility (if unavailable, hide table)
            if (table) {
                const rows = table.querySelectorAll('tbody tr');
                
                rows.forEach(row => {
                    const input = row.querySelector(`.course-rank-input-${semesterID}`);
                    
                    const L = parseInt(row.getAttribute('data-lec')) || 0;
                    const T = parseInt(row.getAttribute('data-tut')) || 0;
                    const Lab = parseInt(row.getAttribute('data-lab')) || 0;

                    const restrictedForTA = (isTA && (T === 0 && Lab === 0) && (L > 0)); 
                    
                    if (restrictedForTA) {
                        row.style.display = 'none';
                        if(input.value.trim() !== '' && input.value.trim() !== '0') {
                             input.value = '0';
                        }
                    } else {
                        row.style.display = '';
                    }
                });
            }

            const container = document.getElementById(`course-preferences-container-${semesterID}`);
            if (availability === 'unavailable') {
                container.style.display = 'none';
                if (table) getAllRankInputs(semesterID).forEach(i => i.value = '0');
            }
            else {
                container.style.display = 'block';
            }

            calculateTotalHours(semesterID);
            validateRanks(null, semesterID);
        }
        
        // --- Form Validation and Message Functions ---
        function showMessage(text, type, semesterID) {
            const box = document.getElementById(`messageBox_${semesterID}`);
            if (!box) { alert(text); return; }
            box.style.display = 'block';
            box.className = ''; 
            if (type === 'error') box.classList.add('alert', 'alert-danger');
            else if (type === 'success') box.classList.add('alert', 'alert-success');
            else box.classList.add('alert', 'alert-info');
            box.innerText = text;
            box.scrollIntoView({behavior: 'smooth', block: 'center'});
            if (type !== 'error') { setTimeout(()=>{ box.style.display = 'none'; }, 4000); }
        }
        function clearMessage(semesterID) {
            const box = document.getElementById(`messageBox_${semesterID}`);
            if (box) { box.style.display = 'none'; box.innerText = ''; box.className = ''; }
        }
        
        function validateFormSubmission(formElement, semesterID) {
            clearMessage(semesterID);
            
            const rankSel = document.getElementById(`academic-rank-${semesterID}`);
            const selectedRank = getAcademicRankValue(semesterID);
            const availability = getAvailabilityValue(semesterID);
            const reasonInput = document.getElementById(`availability_reason_${semesterID}`);
            
            // 1. Validate Academic Rank
            if (!selectedRank) { 
                showMessage('Please select your academic rank.', 'error', semesterID); 
                if (rankSel) rankSel.focus(); 
                return false; 
            }
            
            // 2. Validate Availability
            if (!availability) { showMessage('Please select your availability.', 'error', semesterID); return false; }
            
            // 3. Validate Reason (if Partially or Unavailable)
            if ((availability === 'Partially' || availability === 'unavailable') && reasonInput && reasonInput.value.trim() === '') {
                showMessage('Reason for partial availability or unavailability is required.', 'error', semesterID); 
                reasonInput.focus();
                return false;
            }


            // 4. Validate Ranks and Hours (Only if available)
            if (availability !== 'unavailable') {
                if (!validateRanks(null, semesterID)) { showMessage('Please fix duplicate ranks before submitting.', 'error', semesterID); return false; }

                const total = parseInt(document.getElementById(`total-hours-display-${semesterID}`).textContent) || 0;
                
                const full = FULL_LOAD[selectedRank] || 0;
                const allowed = (availability === 'Partially') ? Math.floor(full / 2) : full;
                
                if (total > allowed) { showMessage('Total requested hours (' + total + ') exceed your allowed hours (' + allowed + '). Please reduce selections.', 'error', semesterID); return false; }
                
                const courses = ALL_SEMESTER_COURSES[semesterID] || [];

                if (courses.length > 0) {
                    const ranked = Array.from(getAllRankInputs(semesterID)).filter(i=>{
                        const v = i.value.trim();
                        const rint = parseInt(v);
                        return !isNaN(rint) && rint >= 1 && i.closest('tr').style.display !== 'none';
                    });

                    if (ranked.length === 0) { 
                        showMessage('You are available but you did not rank any course.', 'error', semesterID); return false; 
                    }
                }
            }

            const btn = document.getElementById(`submitBtn_${semesterID}`);
            if (btn) btn.disabled = true;
            return true;
        }


        // *** Hide other cards when one form opens/Restore visibility on close ***
        function setupFormVisibility(semesterID) {
             document.querySelectorAll('.card').forEach(card => {
                 card.style.display = 'none';
             });
             const currentCard = document.querySelector(`.card[data-semester-id="${semesterID}"]`);
             if(currentCard) {
                 currentCard.style.display = 'block';
             }
        }
        
        function restoreFormVisibility() {
            document.querySelectorAll('.card').forEach(card => {
                card.style.display = 'block';
            });
        }

        // --- Event Listeners and Initial State ---
        document.addEventListener('DOMContentLoaded', () => {
            document.querySelectorAll('div[id^="preferenceForm_"]').forEach(formDiv => {
                const semesterID = parseInt(formDiv.id.split('_')[1]);

                // Bind change listeners
                document.querySelectorAll(`#preference-form-${semesterID} input[name="availability"]`).forEach(r=>r.addEventListener('change', () => updateUIState(semesterID)));
                const ar = document.getElementById(`academic-rank-${semesterID}`);
                if (ar) ar.addEventListener('change', () => updateUIState(semesterID));

                // Bind collapse events to control card visibility
                formDiv.addEventListener('shown.bs.collapse', function() {
                    setupFormVisibility(semesterID);
                });
                formDiv.addEventListener('hidden.bs.collapse', function() {
                    restoreFormVisibility();
                });

                // Initial UI state update (needed for initial hour calculation/TA restriction)
                updateUIState(semesterID);
            });
            
             // Handle initial load if an error caused a form to be "show"
             const errorForm = document.querySelector('.preference-form-collapse.show');
             if (errorForm) {
                 const semesterID = parseInt(errorForm.id.split('_')[1]);
                 setupFormVisibility(semesterID);
             }
        });
    </script>

</body>
</html>
