

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


          <!-- Card 1 -->
          <div class="card shadow-sm mb-3">
            <div class="card-body d-flex justify-content-between align-items-center">
              <div>
                <h5 class="card-title">Fall 2026 Semester</h5>
                <p class="mb-0 text-success">Preference Submission Open</p>
              </div>
              <!-- Button to show/hide the preference -->
              <button class="btn btn-success" data-bs-toggle="collapse" data-bs-target="#preferenceForm">
                Add course preferences
              </button>
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
            <h2 class="submit mb-3">Submit Preferences for Fall 2026</h2>

            <form id="preference-form" onsubmit="return validateRanks();">

              <!-- Academic Rank Selection -->
              <fieldset class="p-3 border rounded mb-3 bg-light">
                <legend class="fs-5">1. Select Academic Rank</legend>
                <label for="academic-rank" class="form-label">Select your Rank:</label>

                <!-- Dropdown for selecting rank -->
                <select id="academic-rank" class="form-select mb-2" onchange="AcademicRank()">
                  <option value="Professor">Professor (Max 10 hrs)</option>
                  <option value="Associate Professor">Associate Professor (Max 12 hrs)</option>
                  <option value="Assistant Professor" selected>Assistant Professor (Max 14 hrs)</option>
                  <option value="Lecturer">Lecturer (Max 16 hrs)</option>
                  <option value="Teaching Assistant">Teaching Assistant (Max 16 hrs - tutorials/labs only)</option>
                </select>
              </fieldset>

              <!--Availability Selection -->
              <fieldset class="p-3 border rounded mb-3 bg-light">
                <legend class="fs-5">Confirm Availability</legend>

                <!-- Radio buttons to choose teaching availability -->
                <div class="form-check">
                  <input class="form-check-input" type="radio" name="availability" value="available" onchange="AvailabilityChange()" checked>
                  <label class="form-check-label">Available: Full teaching load</label>
                </div>

                <div class="form-check">
                  <input class="form-check-input" type="radio" name="availability" value="Partially" onchange="AvailabilityChange()">
                  <label class="form-check-label">Partially Available: Reduced load</label>
                </div>

                <div class="form-check">
                  <input class="form-check-input" type="radio" name="availability" value="unavailable" onchange="AvailabilityChange()">
                  <label class="form-check-label">Unavailable: On leave (load = 0 hours)</label>
                </div>
              </fieldset>

              <!-- Course Preferences Table -->
              <div id="course-preferences-container">
                <fieldset class="p-3 border rounded mb-3 bg-light">
                  <legend class="fs-5">Add Course Preferences</legend>
                  <p class="small text-danger">Teaching Assistants are restricted to tutorials/labs only.</p>

                  <!-- Table for entering course preferences -->
                  <div class="table">
                    <table class="table table-bordered align-middle">
                      <thead class="table-primary text-center">
                        <tr>
                          <th>Rank</th>
                          <th>Course Code</th>
                          <th>Course Name</th>
                          <th>Hours</th>
                        </tr>
                      </thead>

                      <tbody>
                        <tr><td><input type="number" class="form-control form-control-sm" min="1" max="19"></td><td>SWE 211</td><td>Introduction to Software Engineering</td><td>Lecture 3 + Tutorial 1</td></tr>
                        <tr><td><input type="number" class="form-control form-control-sm" min="1" max="19"></td><td>CEN 303</td><td>Data Communications and Computer Networks</td><td>Lecture 3 + Tutorial 1</td></tr>
                        <tr><td><input type="number" class="form-control form-control-sm" min="1" max="19"></td><td>SWE 312</td><td>Software Requirements Engineering</td><td>Lecture 3 + Tutorial 1</td></tr>
                        <tr><td><input type="number" class="form-control form-control-sm" min="1" max="19"></td><td>SWE 314</td><td>Software Security Engineering</td><td>Lecture 3 + Tutorial 1</td></tr>
                        <tr><td><input type="number" class="form-control form-control-sm" min="1" max="19"></td><td>SWE 381</td><td>Web Applications Development</td><td>Lecture 3 + Tutorial 1</td></tr>
                        <tr><td><input type="number" class="form-control form-control-sm" min="1" max="19"></td><td>SWE 333</td><td>Software Quality Assurance</td><td>Lecture 2 + Tutorial 1</td></tr>
                        <tr><td><input type="number" class="form-control form-control-sm" min="1" max="19"></td><td>SWE 321</td><td>Software Design and Architecture</td><td>Lecture 3 + Tutorial 1</td></tr>
                        <tr><td><input type="number" class="form-control form-control-sm" min="1" max="19"></td><td>SWE 444</td><td>Software Construction Laboratory</td><td>Lab 4</td></tr>
                        <tr><td><input type="number" class="form-control form-control-sm" min="1" max="19"></td><td>SWE 482</td><td>Human-Computer Interaction</td><td>Lecture 3 + Tutorial 1</td></tr>
                        <tr><td><input type="number" class="form-control form-control-sm" min="1" max="19"></td><td>SWE 434</td><td>Software Testing and Validation</td><td>Lecture 3 + Tutorial 1</td></tr>
                        <tr id="SWE477"><td><input type="number" class="form-control form-control-sm" min="1" max="19"></td><td>SWE 477</td><td>Software Engineering Code of Ethics</td><td>Lecture 2</td></tr>
                        <tr><td><input type="number" class="form-control form-control-sm" min="1" max="19"></td><td>SWE 466</td><td>Software Project Management</td><td>Lecture 3 + Tutorial 1</td></tr>
                        <tr><td><input type="number" class="form-control form-control-sm" min="1" max="19"></td><td>SWE 455</td><td>Software Maintenance and Evolution</td><td>Lecture 2 + Tutorial 1</td></tr>
                        <tr><td><input type="number" class="form-control form-control-sm" min="1" max="19"></td><td>SWE 481</td><td>Advanced Web Applications Engineering</td><td>Lecture 3 + Tutorial 1</td></tr>
                        <tr><td><input type="number" class="form-control form-control-sm" min="1" max="19"></td><td>SWE 486</td><td>Cloud Computing and Big Data</td><td>Lecture 3 + Tutorial 1</td></tr>
                        <tr><td><input type="number" class="form-control form-control-sm" min="1" max="19"></td><td>SWE 485</td><td>Artificial Intelligence</td><td>Lecture 3 + Tutorial 1</td></tr>
                        <tr><td><input type="number" class="form-control form-control-sm" min="1" max="19"></td><td>SWE 483</td><td>Mobile Application Design</td><td>Lecture 3 + Tutorial 1</td></tr>
                        <tr id="SWE496"><td><input type="number" class="form-control form-control-sm" min="1" max="19"></td><td>SWE 496</td><td>Graduation Project 1</td><td>3 hrs</td></tr>
                        <tr id="SWE497"><td><input type="number" class="form-control form-control-sm" min="1" max="19"></td><td>SWE 497</td><td>Graduation Project 2</td><td>3 hrs</td></tr>
                      </tbody>
                    </table>
                  </div>


                  <div class="alert alert-warning text-center">
                    <strong>Total Requested Hours:</strong> 12 hours
                  </div>
                </fieldset>
              </div>
                <button type="submit" class="btn btn-success w-100 btn-lg mb-4">Submit Preferences</button>     
            </form>
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
    <p class="text-white">&copy; 2025 King Saud University</p>
  </footer>


  <script>
    // Function to find all rank input fields in the course table.
    function getAllRankInputs() {
        return document.querySelectorAll('.table tbody tr td:first-child input[type="number"]');
    }

    // Function to set the maximum rank allowed on all inputs (either 19 or 16).
    function updateMaxRankValue(maxRank) {
        const rankInputs = getAllRankInputs();
        rankInputs.forEach(input => {
            input.setAttribute('max', maxRank);
            
            if (parseInt(input.value) > maxRank) {
                input.value = ''; 
            }
        });
    }


    // Checks duplicates for ranks.
    function validateRanks(currentInput) {
        const rankInputs = getAllRankInputs();
        const enteredRanks = new Set(); 
        let isGlobalValid = true;
        rankInputs.forEach(input => input.setCustomValidity(''));
        rankInputs.forEach(input => {
            const value = input.value.trim();
            
            if (value !== '') {
                const rankNumber = parseInt(value);
                
                // rankNumber is a valid, positive number.
                if (isNaN(rankNumber) || rankNumber < 1) return;

                // Check if this rank number is already in our Set.
                if (enteredRanks.has(rankNumber)) {
                    // DUPLICATE FOUND: Set an error message on the input field.
                    input.setCustomValidity(`Unique number required`);
                    isGlobalValid = false;
                }
                enteredRanks.add(rankNumber);
            }
        });
        
        if (currentInput && currentInput.reportValidity && currentInput.value.trim() !== '' && !isGlobalValid) {
            currentInput.reportValidity();
        }
        return isGlobalValid; 
    }


    // Function that runs when the Academic Rank dropdown changes.
    function AcademicRank() {
        const rankSelect = document.getElementById('academic-rank');
        const selectedRank = rankSelect.value;

        // References to the rows that must be hidden for Teaching Assistants.
        const SWE477 = document.getElementById('SWE477');
        const SWE496 = document.getElementById('SWE496');
        const SWE497 = document.getElementById('SWE497');
        
        let maxRank = 19; // Default max rank is 19 (total subjects).

        if (selectedRank === 'Teaching Assistant') {
            // If Teaching Assistant is selected, hide courses they cannot teach by setting display to 'none'.
            if (SWE477) SWE477.style.display = 'none';
            if (SWE496) SWE496.style.display = 'none';
            if (SWE497) SWE497.style.display = 'none';
            
            // Set the maximum rank allowed to 16 (19 total - 3 hidden courses).
            maxRank = 16;
            
        } else {
            // If any other rank is selected, ensure all courses are visible.
            if (SWE477) SWE477.style.display = ''; 
            if (SWE496) SWE496.style.display = '';
            if (SWE497) SWE497.style.display = '';
            
            maxRank = 19;
        }
        
        updateMaxRankValue(maxRank);
        validateRanks(); 
    }


    // Function that runs when the Availability radio buttons change.
    function AvailabilityChange() {
        // Gets the value of the currently checked availability radio button.
        const selectedValue = document.querySelector('input[name="availability"]:checked').value;
        const preferencesContainer = document.getElementById('course-preferences-container');
        
        // Hides the course preference section if the user is unavailable.
        if (selectedValue === 'unavailable') {
            preferencesContainer.style.display = 'none';
        } else {
            preferencesContainer.style.display = 'block';
        }
    }


    document.addEventListener('DOMContentLoaded', () => {
        AvailabilityChange();
        AcademicRank();
        
        const rankInputs = getAllRankInputs();
        rankInputs.forEach(input => {
            input.addEventListener('input', (e) => validateRanks(e.target));
        });
    });
</script>


</body>
</html>
