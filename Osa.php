<?php
session_start();

if (!isset($_SESSION['id_no'])) {
    header("Location:osa_login.php");
    exit();
}

$id_no = $_SESSION['id_no'];
$role = null;
$userData = null;

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

if ($result && $result->num_rows > 0) {
  $role = 'osa';
  $userData = $result->fetch_assoc();
} else {
  // 2. Check DEAN
  $sql = "SELECT id_no, name, profile_image FROM dean WHERE id_no='$id_no'";
  $result = $conn->query($sql);

  if ($result && $result->num_rows > 0) {
    $role = 'dean';
    $userData = $result->fetch_assoc();
  } else {
    // 3. Check VP
    $sql = "SELECT id_no, name, profile_image FROM vp WHERE id_no='$id_no'";
    $result = $conn->query($sql);

    if ($result && $result->num_rows > 0) {
        $role = 'vp';
        $userData = $result->fetch_assoc();
    } else {
        // Not authorized
        session_destroy();
        header("Location: osa_login.php");
        exit();
    }
  }
}

if (!$result) {
    die("Query failed (orgportal): " . $conn->error);
}

$academicData = [];
$sqlAcademic = "
SELECT 
    o.org_code,
    o.org_name,
    (
        SELECT COUNT(*) FROM organizational_events 
        WHERE organization = o.org_code AND status = 'approved'
    ) AS org_events,
    (
        SELECT COUNT(*) FROM institutional_events 
        WHERE organization = o.org_code AND status = 'approved'
    ) AS inst_events,
    (
        SELECT COUNT(*) FROM user_organizations 
        WHERE organization_name = o.org_code AND status = 'approved'
    ) AS total_members
FROM dtp_organization o
WHERE o.org_status = 'approved';
";

$resultA = $conn->query($sqlAcademic);
while ($row = $resultA->fetch_assoc()) {
    $row["total_events"] = $row["org_events"] + $row["inst_events"];
    $academicData[] = $row;
}

$nonAcadData = [];
$sqlNonAcad = "
SELECT 
    o.org_code,
    o.org_name,
    (
        SELECT COUNT(*) FROM organizational_events 
        WHERE organization = o.org_code AND status = 'approved'
    ) AS org_events,
    (
        SELECT COUNT(*) FROM institutional_events 
        WHERE organization = o.org_code AND status = 'approved'
    ) AS inst_events,
    (
        SELECT COUNT(*) FROM user_organizations 
        WHERE organization_name = o.org_code AND status = 'approved'
    ) AS total_members
FROM nonacad_organization o
WHERE o.org_status = 'approved';
";

$resultNA = $conn->query($sqlNonAcad);
while ($row = $resultNA->fetch_assoc()) {
    $row["total_events"] = $row["org_events"] + $row["inst_events"];
    $nonAcadData[] = $row;
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
usort($academicData, function ($a, $b) {
    return ($b['total_events'] + $b['total_members']) 
         - ($a['total_events'] + $a['total_members']);
});

usort($nonAcadData, function ($a, $b) {
    return ($b['total_events'] + $b['total_members']) 
         - ($a['total_events'] + $a['total_members']);
});

$academicTop5 = array_slice($academicData, 0, 5);
$nonAcadTop5  = array_slice($nonAcadData, 0, 5);

$maxAcademicScore = 0;
foreach ($academicData as $org) {
  $score = $org['total_events'] + $org['total_members'];
  if ($score > $maxAcademicScore) {
    $maxAcademicScore = $score;
  }
}

$maxNonAcadScore = 0;
foreach ($nonAcadData as $org) {
  $score = $org['total_events'] + $org['total_members'];
  if ($score > $maxNonAcadScore) {
    $maxNonAcadScore = $score;
  }
}
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
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.7.2/css/all.min.css"/>   
    <link rel="stylesheet" href="css/Osa.css">
    <link rel="stylesheet" href="/userHomeCopy/UserHome/css/nav.css">
</head>
<body>
    <header class="top-nav">
      <div class="top-nav-left">
        <img src="greetings/umdc-logo.png" alt="Logo" class="top-logo">
      </div>

      <div class="top-nav-right">
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
    </header>
    <nav>
      <div class="nav-center">
        <a href="Osa.php"><i class="fa-solid fa-house"></i><span>Departments</span></a>
        <a href="approvals.php"><i class="fa-solid fa-archive"></i><span>Approvals</span></a>
        <a href="php/calendar.php"><i class="fa-solid fa-calendar-days"></i><span>Calendar</span></a>
      </div>
    </nav>

    <div class="main-content">

      <div class="page-container">
        <div class="osa-profile-card">
          <div class="osa-info">
            <h5 style="margin: 0; color: maroon;">Welcome back!</h5>
            <h3><?php echo htmlspecialchars($userData['name']); ?></h3>
            <p>ID No: <?php echo htmlspecialchars($userData['id_no']); ?></p>
          </div>

          <div class="school-year-box">
            <h2 id="schoolYear"
              style="margin: 0; font-size: 16px; cursor: pointer; fon"
              title="Click to change school year">
            </h2>
            <select id="yearSelect"
              style="display:none; margin-top: 5px; font-size: 16px;">
            </select>
          </div>
        </div>

        <div class="grid-container">
          <a href="dept/department1.php?department=Department of Technical Programs" class="card">
            <img src="departments/DTP_Logo.png" class="card-icon" alt="DTP">
            <div class="card-text">
                <h2>DTP</h2>
                <p>DEPARTMENT OF TECHNICAL PROGRAMS</p>
            </div>
          </a>

          <a href="dept/department1.php?department=Department of Business Administration" class="card">
            <img src="departments/DBA.png" class="card-icon" alt="DBA">
            <div class="card-text">
                <h2>DBA</h2>
                <p>DEPARTMENT OF BUSINESS ADMINISTRATION</p>
            </div>
          </a>

          <a href="dept/department1.php?department=Department of Accounting Education" class="card">
            <img src="departments/DAE_logo.png" class="card-icon" alt="DAE">
            <div class="card-text">
                <h2>DAE</h2>
                <p>DEPARTMENT OF ACCOUNTING EDUCATION</p>
            </div>
          </a>

          <a href="dept/department1.php?department=Department of" class="card">
            <img src="departments/DTE_logo.png" class="card-icon" alt="DTE">
            <div class="card-text">
                <h2>DTE</h2>
                <p>DEPARTMENT OF TECHEARS EDUCATION</p>
            </div>
          </a>

        </div>

        <div class="grid-container">
          <a href="dept/department1.php?department=Department of Criminal Justice Education" class="card">
            <img src="departments/DCJE_Logo.png" class="card-icon" alt="DCJE">
            <div class="card-text">
                <h2>DCJE</h2>
                <p>DEPARTMENT OF CRIMINAL JUSTICE EDUCATION</p>
            </div>
          </a>

          <a href="dept/department1.php?department=Department of Arts and Sciences" class="card">
            <img src="departments/DAS_Logo_New.png" class="card-icon" alt="DAS">
            <div class="card-text">
                <h2>DAS</h2>
                <p>DEPARTMENT OF ART AND SCIENCES</p>
            </div>
          </a>

          <a href="dept/department1.php?department=Department of Hospitality Education" class="card">
            <img src="departments/dhe_digos.jpg" class="card-icon" alt="DHE">
            <div class="card-text">
                <h2>DHE</h2>
                <p>DEPARTMENT OF HOSPITALITY EDUCATION</p>
            </div>
          </a>

          <a href="dept/non-acad.php" class="card" style="background-image: linear-gradient(rgb(218, 206, 78), maroon);">
            <h2 style="font-size: 23px; color: white;">Non Academic Organizations</h2>
          </a>
        </div> 

        <div class="ranking-section">

          <div class="ranking-card academic">
            <div class="ranking-header">
              <h3>Top 5 Academic Organizations</h3>
              <span class="badge academic-badge">Academic</span>
            </div>

            <table class="ranking-table">
              <thead>
                <tr>
                  <th>#</th>
                  <th>Org</th>
                  <th>Events</th>
                  <th>Members</th>
                  <th>Score</th>
                </tr>
              </thead>
              <tbody>
                <?php foreach ($academicTop5 as $i => $org): ?>
                <tr>
                  <td class="rank"><?= $i + 1 ?></td>
                  <td>
                    <strong><?= htmlspecialchars($org['org_code']) ?></strong><br>
                    <small><?= htmlspecialchars($org['org_name']) ?></small>
                  </td>
                  <td><?= $org['total_events'] ?></td>
                  <td><?= $org['total_members'] ?></td>
                  <?php
                  $rawScore = $org['total_events'] + $org['total_members'];
                  $percent = $maxAcademicScore > 0 
                      ? round(($rawScore / $maxAcademicScore) * 100, 1) 
                      : 0;
                  ?>
                  <td class="score"><?= $percent ?>%</td>
                </tr>
                <?php endforeach; ?>
              </tbody>
            </table>
          </div>

          <div class="ranking-card non-academic">
            <div class="ranking-header">
              <h3>Top 5 Non-Academic Organizations</h3>
              <span class="badge nonacad-badge">Non-Acad</span>
            </div>

            <table class="ranking-table">
              <thead>
                <tr>
                  <th>#</th>
                  <th>Org</th>
                  <th>Events</th>
                  <th>Members</th>
                  <th>Score</th>
                </tr>
              </thead>
              <tbody>
                <?php foreach ($nonAcadTop5 as $i => $org): ?>
                <tr>
                  <td class="rank"><?= $i + 1 ?></td>
                  <td>
                    <strong><?= htmlspecialchars($org['org_code']) ?></strong><br>
                    <small><?= htmlspecialchars($org['org_name']) ?></small>
                  </td>
                  <td><?= $org['total_events'] ?></td>
                  <td><?= $org['total_members'] ?></td>
                  <?php
                  $rawScore = $org['total_events'] + $org['total_members'];
                  $percent = $maxNonAcadScore > 0 
                      ? round(($rawScore / $maxNonAcadScore) * 100, 1) 
                      : 0;
                  ?>
                  <td class="score"><?= $percent ?>%</td>
                </tr>
                <?php endforeach; ?>
              </tbody>
            </table>
          </div>

        </div>
      </div>

    </div>

    <!-- <script>
      const academicData = <?= json_encode($academicData); ?>;
      const nonAcadData = <?= json_encode($nonAcadData); ?>;

      function buildChart(canvasId, data) {
        data.sort((a, b) => (b.total_events + b.total_members) - (a.total_events + a.total_members));
        const labels = data.map(d => d.org_code);
        const events = data.map(d => d.total_events);
        const members = data.map(d => d.total_members);

        new Chart(document.getElementById(canvasId), {
            type: 'bar',
            data: {
                labels: labels,
                datasets: [
                    {
                        label: "Events",
                        data: events,
                        backgroundColor: "maroon",
                        barThickness: 12
                    },
                    {
                        label: "Members",
                        data: members,
                        backgroundColor: "yellow",
                        barThickness: 12
                    }
                ]
            },
            options: {
                responsive: true,
                scales: {
                    y: { beginAtZero: true }
                }
            }
        });
      }

      buildChart("academicChart", academicData);
      buildChart("nonAcadChart", nonAcadData);
    </script> -->

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

  const BASE_URL = window.location.origin;

function notification() {
  const dropdown = document.getElementById('notif-dropdown');
  dropdown.style.display =
  (dropdown.style.display === 'none' || dropdown.style.display === '')
  ? 'block' : 'none';

  if (dropdown.style.display === 'block') {
    let notifUrl = '';

    <?php if ($role === 'osa'): ?>
      notifUrl = BASE_URL + '/osaDashboard/osa_notification.php';
    <?php elseif ($role === 'dean'): ?>
      notifUrl = BASE_URL + '/PH_DN_VP_Dashboard/dean/dean_notification.php';
    <?php elseif ($role === 'vp'): ?>
      notifUrl = BASE_URL + '/PH_DN_VP_Dashboard/vp_branch/vp_notification.php';
    <?php endif; ?>

    fetch(notifUrl)
    .then(response => response.text())
    .then(data => {
      document.getElementById('notif-list').innerHTML = data;
    })
    .catch(() => {
      document.getElementById('notif-list').innerHTML =
      '<div style="padding:10px;">Failed to load notifications.</div>';
    });
  }
}

// Close when clicking outside
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
