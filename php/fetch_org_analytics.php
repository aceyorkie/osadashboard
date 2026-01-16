<?php
$host = 'localhost';
$user = 'root';
$password = '';
$dbname = 'orgportal';

$conn = new mysqli($host, $user, $password, $dbname);
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

$org = $conn->real_escape_string($_GET['org']);

function emptyMonths() {
    return array_fill(0, 12, 0);
}

/* -----------------------------------------------------
   EVENTS PER MONTH (organizational + institutional)
------------------------------------------------------ */
$events = emptyMonths();

$res = $conn->query("
    SELECT MONTH(event_date) AS m, COUNT(*) AS c
    FROM organizational_events
    WHERE organization = '$org' AND status = 'approved'
    GROUP BY MONTH(event_date)
");

while ($row = $res->fetch_assoc()) {
    if ($row['m'] !== null) {
        $events[$row['m'] - 1] += intval($row['c']);
    }
}

$res = $conn->query("
    SELECT MONTH(event_date) AS m, COUNT(*) AS c
    FROM institutional_events
    WHERE organization = '$org' AND status = 'approved'
    GROUP BY MONTH(event_date)
");

while ($row = $res->fetch_assoc()) {
    if ($row['m'] !== null) {
        $events[$row['m'] - 1] += intval($row['c']);
    }
}

/* -----------------------------------------------------
   POSTS PER MONTH (from posts table)
------------------------------------------------------ */
$posts = emptyMonths();

$res = $conn->query("
    SELECT MONTH(created_at) AS m, COUNT(*) AS c
    FROM posts
    WHERE organization = '$org' AND status = 'approved'
    GROUP BY MONTH(created_at)
");

while ($row = $res->fetch_assoc()) {
    if ($row['m'] !== null) {
        $posts[$row['m'] - 1] = intval($row['c']);
    }
}

/* -----------------------------------------------------
   ANALYTICS SUMMARY
------------------------------------------------------ */

$months = [
    "January","February","March","April","May","June",
    "July","August","September","October","November","December"
];

$events_peak = max($events);
$events_peak_month = $events_peak > 0 ? $months[array_search($events_peak, $events)] : "None";

$posts_peak = max($posts);
$posts_peak_month = $posts_peak > 0 ? $months[array_search($posts_peak, $posts)] : "None";

$combined = [];
for ($i = 0; $i < 12; $i++) {
    $combined[$i] = $events[$i] + $posts[$i];
}

$best_month = $months[array_search(max($combined), $combined)];

$low_months = [];
foreach ($combined as $i => $value) {
    if ($value === 0) $low_months[] = $months[$i];
}

echo json_encode([
    "org" => $org,
    "events_per_month"   => $events,
    "posts_per_month"    => $posts,
    "events_peak_month"  => $events_peak_month,
    "events_peak"        => $events_peak,
    "posts_peak_month"   => $posts_peak_month,
    "posts_peak"         => $posts_peak,
    "best_month"         => $best_month,
    "low_months"         => implode(", ", $low_months)
]);
?>
