<?php
header('Content-Type: application/json');

$host = 'localhost';
$db = 'practice_db';
$user = 'root';
$pass = '';
$conn = new mysqli($host, $user, $pass, $db);

if ($conn->connect_error) {
    die(json_encode(['error' => 'Database connection failed.']));
}

$request_id = isset($_GET['request_id']) ? intval($_GET['request_id']) : 0;
$event_id   = isset($_GET['event_id']) ? intval($_GET['event_id']) : 0; // ✅ ADDED: Read event_id from GET
$signatory_id = isset($_GET['signatory_id']) ? intval($_GET['signatory_id']) : 0;

if ($request_id <= 0 || $event_id <= 0 || $signatory_id <= 0) {
    echo json_encode(['error' => 'Invalid request ID, event ID, or signatory ID.']);
    exit;
}

// ✅ ADDED: Verify adviser has access to this event
$checkSql = "SELECT 1 FROM event_signature_flow WHERE request_id = ? AND event_id = ? AND role = 'OSA' AND signatory_id = ?";
$checkStmt = $conn->prepare($checkSql);
$checkStmt->bind_param("iii", $request_id, $event_id, $signatory_id);
$checkStmt->execute();
$checkStmt->store_result();

if ($checkStmt->num_rows === 0) {
    echo json_encode([]);
    $checkStmt->close();
    $conn->close();
    exit;
}
$checkStmt->close();

$basePath = 'http://localhost/officerDashboardCopy/create_event/';


$stmt = $conn->prepare("SELECT file_name, file_path, doc_type FROM event_document_files WHERE request_id = ? AND event_id = ?");
$stmt->bind_param("ii", $request_id, $event_id);
$stmt->execute();
$result = $stmt->get_result();

$docs = [];
while ($row = $result->fetch_assoc()) {
    $row['file_path'] = preg_match('/^https?:\/\//', $row['file_path'])
        ? $row['file_path']
        : $basePath . ltrim($row['file_path'], '/');
    $docs[] = $row; 
    
}

echo json_encode($docs);
$stmt->close();
$conn->close();
?>
