<?php
session_start();

$host = 'localhost';
$user = 'root';
$pass = '';
$db = 'orgportal';
$conn = new mysqli($host, $user, $pass, $db);
if ($conn->connect_error) {
    die("DB connection failed: " . $conn->connect_error);
}

$user_id = $_SESSION['id_no'] ?? null;

if (!$user_id) {
    die("Not logged in.");
}

/* -------------------------------------
   2. FETCH INSTITUTIONAL EVENTS
-------------------------------------- */

$institutional = [];
$sql = "SELECT event_name AS title, event_date AS start, event_location AS location, 'Institutional' AS type 
        FROM institutional_events 
        WHERE status = 'approved'";

$result = $conn->query($sql);
while ($row = $result->fetch_assoc()) {
    $row['color'] = '#800000';
    $institutional[] = $row;
}

$organizational = [];
$sql = "
    SELECT 
        event_name AS title,
        event_date AS start,
        event_location AS location,
        organization,
        'Organizational' AS type
    FROM organizational_events
    WHERE status = 'approved'
";

$result = $conn->query($sql);
while ($row = $result->fetch_assoc()) {
    $row['color'] = '#b03060'; // organizational color
    $organizational[] = $row;
}


$events = array_merge($institutional, $organizational);

if (isset($_GET['json'])) {
    header('Content-Type: application/json');
    echo json_encode($events);
    exit;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Event Calendar</title>
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Roboto:wght@400;700&display=swap">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.10/index.global.min.css" rel="stylesheet" />
    <script src="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.10/index.global.min.js"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.7.2/css/all.min.css"/> 
    <link rel="stylesheet" href="/userHomeCopy/UserHome/css/nav.css">
    <link rel="stylesheet" href="/userHomeCopy/UserHome/css/calendar.css">
</head>
<body>
    <header class="top-nav">
      <div class="top-nav-left">
        <img src="/osaDashboard/greetings/umdc-logo.png" alt="Logo" class="top-logo">
      </div>
    </header>
    <nav>
      <div class="nav-center">
        <a href="/osaDashboard/Osa.php"><i class="fa-solid fa-house"></i><span>Departments</span></a>
        <a href="/osaDashboard/approvals.php"><i class="fa-solid fa-archive"></i><span>Approvals</span></a>
        <a href="php/calendar.php"><i class="fa-solid fa-calendar-days"></i><span>Calendar</span></a>
      </div>
    </nav>

    <div class="main-content">
        <div id="calendar"></div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
        const calendarEl = document.getElementById('calendar');
        const calendar = new FullCalendar.Calendar(calendarEl, {
            initialView: 'dayGridMonth',
            themeSystem: 'standard',
            events: 'calendar.php?json=1',
            eventClick: function(info) {
            const e = info.event.extendedProps;
            alert(
                `📌 ${info.event.title}
                Type: ${e.type}
                Organization: ${e.organization ?? '—'}
                Location: ${e.location}
                Date: ${info.event.start.toLocaleDateString()}`
                );
            },
            headerToolbar: {
            left: 'prev,next today',
            center: 'title',
            right: 'dayGridMonth,timeGridWeek,listWeek'
            },
            height: 'auto'
        });

        calendar.render();
        });
    </script>

</body>
</html>
