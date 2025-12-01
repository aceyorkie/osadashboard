<?php
// update_school_year.php
$host = "localhost";
$user = "root";
$pass = "";
$db = "orgportal"; // change to your database name

$conn = new mysqli($host, $user, $pass, $db);
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

if (isset($_POST['startYear'])) {
    $startYear = intval($_POST['startYear']);
    
    $conn->query("UPDATE dtp_organization SET org_status = 'Pending'");
    $conn->query("UPDATE nonacad_organization SET org_status = 'Pending'");

    echo "School year updated and all organizations set to Pending.";
} else {
    echo "No year received.";
}

$conn->close();
?>
