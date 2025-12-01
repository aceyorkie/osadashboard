<?php
$host = 'localhost';
$user = 'root';
$password = '';
$dbname = 'orgportal';

$conn = new mysqli($host, $user, $password, $dbname);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

$organization = $_GET['organization'];

$stmt = $conn->prepare("SELECT user_id, username, joined_at FROM user_organizations WHERE organization_name = ?");
$stmt->bind_param("s", $organization);
$stmt->execute();
$result = $stmt->get_result();

$members = [];
while ($row = $result->fetch_assoc()) {
    $members[] = $row;
}

$stmt->close();

$total_members = count($members);

$conn->close();

header('Content-Type: application/json');
echo json_encode([
    'members' => $members,
    'total_members' => $total_members,
]);
?>
