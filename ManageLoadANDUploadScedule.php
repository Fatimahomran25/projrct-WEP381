
<?php
session_start();

if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'Administrator') {
    header("Location: login.php");
    exit;
}
?>


<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="style.css" rel="stylesheet">
    <title>Manage Load and Upload Schedule</title>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</head>
<body>
<!-- Fixed Navbar -->
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

<div class="container d-flex justify-content-start align-items-center">
    <!-- List -->
    <div class="menu p-4 shadow-lg d-flex">
        <ul class="list-group">
            <li class="list-group-item bg-transparent border-0 selected">
                <a href="ManageLoadANDUploadScedule.php"><img src="assets/load.png" alt="icon Load & Schedule"> Load & Schedule</a>
            </li>
            <li class="list-group-item bg-transparent border-0">
                <a href="ManageCourseList.php"><img src="assets/courses.png" alt="icon Courses"> Courses</a>
            </li>
            <li class="list-group-item bg-transparent border-0">
                <a href="ManageRequests.php"><img src="assets/icons8-form-50.png" alt="icon Requests"> Requests</a>
            </li>
            <li class="list-group-item bg-transparent border-0">
                <a href="CourseAssignmentANDConflictDetection.php">
                    <img src="assets/icons8-edit-property-50.png" alt="icon Assignments Conflicts"> Assignments
                </a>
            </li>
        </ul>
    </div>

<?php
use PhpOffice\PhpSpreadsheet\IOFactory; //مكتبه الاكسل الي رح تقرأ منه 
require 'vendor/autoload.php';

$databaseSemeters = mysqli_connect("localhost", "root", "", "faculty_load_db");// اعداد الاتصال بالديبي 
if (!$databaseSemeters) {
    die("<p>Could not connect to the database: " . mysqli_connect_error() . "</p>");
}

// 1 هنا الي بس اضغط زر كريت نيو سمستر بعد ما اعبي الخانات رح يشتغل
if (isset($_POST['saveSemester'])) { //يفحص انه موجود اساسا 
    $semesterType = $_POST['semesterType']; 
    $academicYear = $_POST['academicYear'];
    $isActive = isset($_POST['activateSemester']) ? 1 : 0;
    $isVisible = isset($_POST['makeVisible']) ? 1 : 0;

    $semesterCode = ($semesterType === "Fall") ? 1 : 2; // عشان احوله لرقم عشان اقدر اعمل الاي دي حقه
    list($yearStart, $yearEnd) = explode('/', $academicYear); // هذه رح تاخذ رقم السنه من القائمة وتفصله بليست عشان اقدر اكون الاي دي
    $semesterID = $semesterCode . $yearStart . $yearEnd;

    $checkQuery = "SELECT id FROM semesters WHERE id = '$semesterID'";
    $checkResult = mysqli_query($databaseSemeters, $checkQuery);

    if (mysqli_num_rows($checkResult) > 0) { // عشان ينبه الادمن لو السمستر موجود
        echo "<script>alert('This semester already exists!');</script>";
    } else {//لو مو موجود الفصل
        $insertQuery = "INSERT INTO semesters (id, name, isActiveForLoadRequest, isAvailableForFaculty) VALUES ('$semesterID', '$semesterType $academicYear', $isActive, $isVisible)";
        if (mysqli_query($databaseSemeters, $insertQuery)) {
            echo "<script>alert('Semester added successfully!');</script>";
        } else {
            echo "<script>alert('Error: " . mysqli_error($databaseSemeters) . "');</script>";
        }
    }
}

// 2 هنا بس عشان اتعامل مع ملف الاكسل واقدر اخزنه 
if (isset($_POST['uploadExcel'])) {
    
    $semesterName = $_POST['semester']; //هذه هيدن انبت حطيتها لان حضيف السكشن وهو مرتبط بالفصل تبعه 
    

        $ID = "SELECT id FROM semesters WHERE name='$semesterName'";
        $idResult = mysqli_query($databaseSemeters, $ID);
        
        $idRow = mysqli_fetch_assoc($idResult);
        $semesterID = $idRow['id']; // كل هذا عشان استخرج الفصل الي فيه تم الرفع

            if (isset($_FILES['courseFile']) && $_FILES['courseFile']['error'] === 0) { // اتاكد انه الملف انرفع وانه مافيه اخطاء 
                $filePath = $_FILES['courseFile']['tmp_name']; // باث مؤقت

                try {
                    $spreadsheet = IOFactory::load($filePath); // يقرا الملف 
                    $sheet = $spreadsheet->getActiveSheet(); 
                    $rows = $sheet->toArray(); // يحول كل صف ل اراي 

                    // تجاهل أول صف (عناوين الأعمدة الي بالاكسل شيت مافيها قيم تهمني)
                    array_shift($rows);

                    $successCount = 0; // هذا احذفه بس لي للتست 
                    foreach ($rows as $r) { // يبدا يقرا صف صف من الي حولهم 
                        if (count($r) >= 6) { //هذه بس احتياط انه كل صف فيه 6 اعمده وهي البيانات الي احتاجها 
                            $courseCode  = mysqli_real_escape_string($databaseSemeters, $r[0]); // هذه بس تنظيف عشام الحماية ولا ممكن اعمل اسناد طبيعي 
                            $sectionNumber = mysqli_real_escape_string($databaseSemeters, $r[1]);
                            $type        = mysqli_real_escape_string($databaseSemeters, $r[2]);
                            $day         = mysqli_real_escape_string($databaseSemeters, $r[3]);
                            $startTime   = mysqli_real_escape_string($databaseSemeters, $r[4]);
                            $endTime     = mysqli_real_escape_string($databaseSemeters, $r[5]);

                            $checkExist = "SELECT * FROM coursesections 
                                          WHERE courseCode='$courseCode' 
                                          AND sectionNumber='$sectionNumber' 
                                          AND day='$day'
                                          AND semesterID='$semesterID'
                                          AND type='$type'
                                          AND startTime='$startTime'
                                          AND endTime='$endTime'";
                            $existResult = mysqli_query($databaseSemeters, $checkExist); // عشان لو الصف موجود اساسا 

                            if(mysqli_num_rows($existResult) > 0){
                                   $duplicates[] = "$courseCode - $sectionNumber"; // تخزين الصف المكرر
                            } 
                            else {
                            $insert = "INSERT INTO coursesections (courseCode, sectionNumber, type, day, startTime, endTime, semesterID) 
                            VALUES ('$courseCode', '$sectionNumber', '$type', '$day', '$startTime', '$endTime', '$semesterID')";
                            if (mysqli_query($databaseSemeters, $insert)) {
                            $successCount++;
                            }
                         }
                        }
                    }
                    $message = "Excel uploaded successfully! $successCount records inserted.";
                    if(!empty($duplicates)){
                        $dupList = implode(", ", $duplicates);
                        $message .= "\\nThese rows were skipped because they already exist: $dupList";
                    }
                    echo "<script>alert('$message');</script>";
                } catch (Exception $e) {
                    echo "<script>alert('Error reading Excel file.');</script>";
                }
        } else {
            echo "<script>alert('Semester not found!');</script>";
        }
    }

// 3 هذه عشان احدث قسم الازرار في الديبي واعرضها بعد ما احفظ التغييرات في الديبي بالقيم الجديده لما استدعي 
$selectedSemester = $_GET['semester'] ?? null;

if ($selectedSemester) { // اذا عنجد اختار فصل 
    if (isset($_GET['Activation'])) {
        $activationValue = ($_GET['Activation'] == 'activate') ? 1 : 0; //قيمه الزر
        mysqli_query($databaseSemeters, "UPDATE semesters SET isActiveForLoadRequest=$activationValue WHERE name='$selectedSemester'");
    }

    if (isset($_GET['Visible'])) {
        $visibleValue = ($_GET['Visible'] == 'visible') ? 1 : 0;
        mysqli_query($databaseSemeters, "UPDATE semesters SET isAvailableForFaculty=$visibleValue WHERE name='$selectedSemester'");
    }

    // جلب القيم الحالية
    $retrieveValues = "SELECT isActiveForLoadRequest, isAvailableForFaculty FROM semesters WHERE name = '$selectedSemester'";
    $result = mysqli_query($databaseSemeters, $retrieveValues);
    $rowStatus = $result ? mysqli_fetch_assoc($result) : null;
}
?>

<!-- Page Content -->
<div class="page-content">
    <h4>Choose Semester to Edit Visibility and Requests</h4>
    <p>Current Semester: <?php echo $selectedSemester ?? 'None'; ?></p> <!--عشان ابي اسم الفصل الي اختاره يبان -->
    
    <div class="d-flex justify-content-center align-items-center">
        <!-- Dropdown -->
        <div class="dropdown me-2">
            <button class="btn cusombtn dropdown-toggle" type="button" data-bs-toggle="dropdown">
                Choose Semester
            </button>
            <?php
            $retrieveSemestersQuery = "SELECT name FROM semesters";
            $retrieveSemestersResult = mysqli_query($databaseSemeters, $retrieveSemestersQuery);
            ?>
            <ul class="dropdown-menu">
                <?php
                while($row = mysqli_fetch_row($retrieveSemestersResult)) {
                    $semester = urlencode($row[0]);
                    echo "<li><a class='dropdown-item' href='?semester=$semester'>" . $row[0] . "</a></li>";
                }
                ?>
            </ul>
        </div>
        
        <!-- Button to create new semester-->
        <button class="btn cusombtn" type="button" data-bs-toggle="modal" data-bs-target="#createSemesterModal">Create New</button>
    </div>

    <?php if ($selectedSemester): ?>
    
    <!-- Form for Activation/Visibility -->
    <form method="get">
        <input type="hidden" name="semester" value="<?php echo $selectedSemester; ?>"> 
        <div class="semester-section align-items-left">
            <div>
                <div class="d-flex align-items-left m-4">
                    <p class="me-3 mb-0">Activation Options:</p>
                    <div class="btn-group mb-2" role="group">
                        <input type="radio" class="btn-check" name="Activation" value="activate" id="ActiveBT" <?php if($rowStatus['isActiveForLoadRequest'] ) echo 'checked'; ?>>
                        <label class="btn positiveBT" for="ActiveBT">Activate</label>
                        <input type="radio" class="btn-check" name="Activation" value="deactivate" id="notActiveBT" <?php if(!($rowStatus['isActiveForLoadRequest'] )) echo 'checked'; ?>>
                        <label class="btn negativeBT" for="notActiveBT">Deactivate</label>
                    </div>
                </div>

                <div class="d-flex align-items-left m-4">
                    <p class="me-3 mb-0">Visibility Options:</p>
                    <div class="btn-group mb-2" role="group">
                        <input type="radio" class="btn-check" name="Visible" value="visible" id="VisibleBT" <?php if($rowStatus['isAvailableForFaculty'] ) echo 'checked'; ?>>
                        <label class="btn positiveBT" for="VisibleBT">Visible</label>
                        <input type="radio" class="btn-check" name="Visible" value="unvisible" id="notVisibleBT" <?php if(!($rowStatus['isAvailableForFaculty'] )) echo 'checked'; ?>>
                        <label class="btn negativeBT" for="notVisibleBT">Unvisible</label>
                    </div>
                </div>
            </div>
        </div>
        <div class="d-flex justify-content-center form-save-button">
            <button class="btn cusombtn" type="submit">Save Changes</button> 
        </div>
    </form>

    <!-- Form for Excel Upload -->
    <div class="upload-wrapper">
        <form method="post" enctype="multipart/form-data">
            <input type="hidden" name="semester" value="<?php echo $selectedSemester; ?>">
            <div class="card uploadcard p-3 mb-4" style="max-width: 1000px;">
                <div class="card-body">
                    <h5 class="card-title text-center">Upload Course File</h5>
                    <input class="form-control" type="file" name="courseFile" accept=".xlsx,.xls">
                    <button class="btn cusombtn mt-3 w-100" name="uploadExcel" type="submit">Upload</button>
                </div>
            </div>
        </form>
    </div>

    <?php endif; ?>
</div>
</div>

<!-- Fixed Footer -->
<footer class="footer glass-nav">
    <div class="footer-logo">
        <img src="assets/Vision.png" alt="Vision 2030">
    </div>
    <a class="text-white text-decoration-none" href="mailto:KSU_SmartSchedl@gmail.com">KSU_SmartSchedl@gmail.com</a>
    <p class="text-white">&copy; 2025 King Saud University</p>
</footer>

<!-- Modal for Create New Semester -->
<div class="modal fade" id="createSemesterModal" tabindex="-1" aria-labelledby="createSemesterModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="createSemesterModalLabel">Create New Semester</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form id="semesterForm" method="post">
                    <div class="mb-3">
                        <label for="academicYear" class="form-label">Academic Year</label>
                        <select class="form-select" id="academicYear" name="academicYear" required>
                            <option value="">Select Year</option>
                        </select>
                    </div>

                    <script src="https://cdn.jsdelivr.net/npm/hijri-now/hijriNow.min.js"></script> <!--هذا لتوليد الفصول عشان ما اخليه يكتب كتابه -->
                    <script>
                        const hijriYear = parseInt(HijriNow.year())-1; 
                        const numberOfYears = 10;
                        const yearSelect = document.getElementById('academicYear');
                        for (let i = 0; i < numberOfYears; i++) {
                            const start = hijriYear + i;
                            const end = start + 1;
                            const opt = document.createElement('option');
                            opt.value = start + '/' + end;
                            opt.textContent = start + '/' + end;
                            yearSelect.appendChild(opt);
                        }
                    </script>

                    <div class="mb-3">
                        <label for="semesterType" class="form-label">Semester Type</label>
                        <select class="form-select" id="semesterType" name="semesterType" required>
                            <option value="">Select Semester</option>
                            <option value="Fall">Fall</option>
                            <option value="Spring">Spring</option>
                        </select>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Initial Status</label>
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" id="activateSemester" name="activateSemester">
                            <label class="form-check-label" for="activateSemester">Activate Semester</label>
                        </div>
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" id="makeVisible" name="makeVisible">
                            <label class="form-check-label" for="makeVisible">Make Visible</label>
                        </div>
                    </div>
                    
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" name="saveSemester" class="btn cusombtn">Create Semester</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

</body>
</html>
