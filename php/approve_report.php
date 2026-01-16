<?php
session_start();
$host = "localhost";
$user = "root";        // change if needed
$pass = "";            // change if needed
$db   = "orgportal";

$conn = new mysqli($host, $user, $pass, $db);

/* Check connection */
if ($conn->connect_error) {
    die("Database connection failed: " . $conn->connect_error);
}

/* Optional: set charset for safety */
$conn->set_charset("utf8mb4");

if (!isset($_SESSION['id_no'])) {
    die("Unauthorized.");
}

$report_id = intval($_POST['report_id'] ?? 0);
$action    = $_POST['action'] ?? '';

if ($report_id <= 0 || !in_array($action, ['approved', 'rejected'])) {
    die("Invalid request.");
}

$approved_by = $_SESSION['id_no'];
$approved_at = date('Y-m-d H:i:s');

$stmt = $conn->prepare("
    UPDATE aop_reports
    SET approval_status = ?, approved_by = ?, approved_at = ?
    WHERE id = ?
");
$stmt->bind_param("sssi", $action, $approved_by, $approved_at, $report_id);
$stmt->execute();
$stmt->close();

header("Location: " . $_SERVER['HTTP_REFERER']);
exit;
