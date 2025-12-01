<?php
session_start();

if (!isset($_SESSION['id_no'])) {
    header("Location:osa_login.php");
    exit();
}

$host = 'localhost';
$user = 'root';
$password = '';
$dbname = 'orgportal';
$conn = new mysqli($host, $user, $password, $dbname);

if ($conn->connect_error) {
    die("Connection failed to orgportal: " . $conn->connect_error);
}

$id_no = $conn->real_escape_string($_SESSION['id_no']);

$sql = "SELECT id_no, name, profile_image FROM osa WHERE id_no='$id_no'";
$result = $conn->query($sql);

if (!$result) {
    die("Query failed (orgportal): " . $conn->error);
}

$db_practice = 'practice_db';
$conn_practice = new mysqli($host, $user, $password, $db_practice);

if ($conn_practice->connect_error) {
    die("Connection failed to practice_db: " . $conn_practice->connect_error);
}

$sql_unread = "SELECT COUNT(*) AS unread_count 
               FROM notifications 
               WHERE recipient_id = '$id_no' 
               AND status = 'unread'";
$result_unread = $conn_practice->query($sql_unread);

$unread_count = 0;
if ($result_unread && $result_unread->num_rows > 0) {
    $row_unread = $result_unread->fetch_assoc();
    $unread_count = $row_unread['unread_count'];
}

$conn->close();
$conn_practice->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Departments</title>
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Roboto:wght@400;700&display=swap">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@100..900&family=Roboto:ital,wght@0,100;0,300;0,400;0,500;0,700;0,900&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="Osa.css">
</head>
<body>
    <nav>
      <div class="nav-left">
        <img src="greetings/umdc-logo.png" alt="Logo" class="logo">
      </div>
      <div class="nav-center">
        <a href="#organizations" onclick="dept()">Departments</a>
        <a href="approvals.php">Approvals</a>
        <a href="#about" onclick="about()">About</a>
      </div>
      <div class="nav-right" style="position: relative;">
        <button class="button" onclick="notification()" style="position: relative;">
            <svg viewBox="0 0 448 512" class="bell" width="24" height="24">
                <path d="M224 0c-17.7 0-32 14.3-32 32V49.9C119.5 61.4 64 124.2 
                64 200v33.4c0 45.4-15.5 89.5-43.8 124.9L5.3 377
                c-5.8 7.2-6.9 17.1-2.9 25.4S14.8 416 24 416H424
                c9.2 0 17.6-5.3 21.6-13.6s2.9-18.2-2.9-25.4l-14.9-18.6
                C399.5 322.9 384 278.8 384 233.4V200c0-75.8-55.5-138.6
                -128-150.1V32c0-17.7-14.3-32-32-32zm0 96h8c57.4 0 104 
                46.6 104 104v33.4c0 47.9 13.9 94.6 39.7 134.6H72.3
                C98.1 328 112 281.3 112 233.4V200c0-57.4 46.6-104 
                104-104h8zm64 352H224 160c0 17 6.7 33.3 18.7 45.3
                s28.3 18.7 45.3 18.7s33.3-6.7 45.3-18.7
                s18.7-28.3 18.7-45.3z"/>
            </svg>

            <?php if ($unread_count > 0): ?>
                <span class="notif-badge"><?php echo $unread_count; ?></span>
            <?php endif; ?>
        </button>

        <div id="notif-dropdown" class="notif-dropdown" style="display: none;">
            <div class="notif-header">Notifications</div>
            <div id="notif-list" class="notif-list">Loading...</div>
        </div>
      </div>
    </nav>

    <div>
      <img class="Greetings" src="greetings/osa greetings.png">
      <h2 id="schoolYear" 
        style="text-align:center; margin-top: 10px; font-family: 'Roboto', sans-serif; cursor: pointer;"
        title="Click to change school year">
      </h2>
      <select id="yearSelect" style="display:none; text-align:center; margin-top: 10px; font-size: 16px;"></select>
    </div>

    <div class="grid-container">
      <a href="department1.php" class="card">
        <p>View organizations of Department of Technical Programs</p>
        <h2>DTP</h2>
      </a>
      <a href="business.html" class="card">
        <p>View organizations of Department of Business Administration</p>
        <h2>DBA</h2>
      </a>
      <a href="accounting.html" class="card">
        <p>View organizations of Department of Accounting Education</p>
        <h2>DAE</h2>
      </a>
      <a href="teachers.html" class="card">
        <p>View organizations of Department of Teachers Education</p>
        <h2>DTE</h2>
      </a>
    </div>
    <div class="grid-container">
      <a href="criminaljustice.html" class="card">
        <p>View organizations of Department of Criminal Justice Education</p>
        <h2>DCJE</h2>
      </a>
      <a href="arts.html" class="card">
        <p>View organizations of Department of Art and Sciences</p>
        <h2>DAS</h2>
      </a>
      <a href="shs.html" class="card">
        <p>View organizations of Senior High School Department</p>
        <h2>SHS</h2>
      </a>
      <a href="nonacademic.html" class="card" style="background-image: linear-gradient(rgb(218, 206, 78), maroon);">
        <h2 style="font-size: 23px; margin-top: 115px; width: 110px; color: white;">Non Academic Organizations</h2>
      </a>
    </div>

<script>
  let startYear = 2025;
  const schoolYearEl = document.getElementById("schoolYear");
  const yearSelectEl = document.getElementById("yearSelect");

  // Populate dropdown with years from 2020 to 2035
  for (let y = 2020; y <= 2035; y++) {
    let option = document.createElement("option");
    option.value = y;
    option.textContent = y;
    yearSelectEl.appendChild(option);
  }

  // Function to update text
  function updateSchoolYear() {
    schoolYearEl.textContent = `School Year ${startYear} - ${startYear + 1}`;
  }

  // Initial display
  updateSchoolYear();

  // Show dropdown when clicking the school year text
  schoolYearEl.addEventListener("click", () => {
    yearSelectEl.style.display = "inline-block";
    yearSelectEl.value = startYear; // Pre-select current year
    yearSelectEl.focus();
  });

  // Update year when dropdown changes
  yearSelectEl.addEventListener("change", () => {
    startYear = parseInt(yearSelectEl.value);
    updateSchoolYear();
    yearSelectEl.style.display = "none";

    // Send new year to PHP backend
    fetch("update_school_year.php", {
      method: "POST",
      headers: { "Content-Type": "application/x-www-form-urlencoded" },
      body: "startYear=" + encodeURIComponent(startYear)
    })
    .then(response => response.text())
    .then(data => {
      console.log(data);
      alert("School Year updated and all organizations set to Pending!");
    })
    .catch(err => console.error(err));
  });

  // Hide dropdown if it loses focus
  yearSelectEl.addEventListener("blur", () => {
    yearSelectEl.style.display = "none";
  });
</script>

<script>
  function notification() {
    const dropdown = document.getElementById('notif-dropdown');
    dropdown.style.display = (dropdown.style.display === 'none' || dropdown.style.display === '') ? 'block' : 'none';

    if (dropdown.style.display === 'block') {
        // Fetch notifications via AJAX
      fetch('osa_notification.php')
        .then(response => response.text())
        .then(data => {
            document.getElementById('notif-list').innerHTML = data;
        })
        .catch(err => {
            document.getElementById('notif-list').innerHTML = '<div style="padding:10px;">Failed to load notifications.</div>';
        });
      }
    }

    // Optional: close dropdown when clicking outside
    document.addEventListener('click', function(e) {
        const dropdown = document.getElementById('notif-dropdown');
        const button = document.querySelector('.button');
        if (!dropdown.contains(e.target) && !button.contains(e.target)) {
            dropdown.style.display = 'none';
        }
    });
</script>
</body>
</html>
