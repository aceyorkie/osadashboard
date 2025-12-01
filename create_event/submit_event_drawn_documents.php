<?php
header('Content-Type: application/json');

error_log("REQUEST_METHOD: " . $_SERVER['REQUEST_METHOD']);
error_log("POST data: " . json_encode($_POST));
error_log("FILES data: " . json_encode(array_keys($_FILES)));

$host = 'localhost';
$db = 'practice_db';
$user = 'root';
$pass = '';
$conn = new mysqli($host, $user, $pass, $db);

if ($conn->connect_error) {
    echo json_encode(['status' => 'error', 'message' => 'DB Connection failed: ' . $conn->connect_error]);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $request_id = intval($_POST['request_id'] ?? 0);
    $event_id   = intval($_POST['event_id'] ?? 0);
    $role       = isset($_POST['role']) ? trim($_POST['role']) : '';

    // ✅ VALIDATION with detailed error messages
    if ($request_id <= 0) {
        echo json_encode(['status' => 'error', 'message' => 'Invalid request_id: ' . $_POST['request_id']]);
        exit;
    }
    if ($event_id <= 0) {
        echo json_encode(['status' => 'error', 'message' => 'Invalid event_id: ' . $_POST['event_id']]);
        exit;
    }
    if ($role === '') {
        echo json_encode(['status' => 'error', 'message' => 'Invalid role: ' . $_POST['role']]);
        exit;
    }

    error_log("DEBUG: request_id=$request_id, event_id=$event_id, role=$role");

    if (isset($_FILES['final_pdf']) && $_FILES['final_pdf']['error'] === UPLOAD_ERR_OK) {
        $originalPath = $_POST['original_path'] ?? '';
        $originalName = basename($originalPath);

        $dbPath = $originalPath;

        // 🗂 Document type map
        $doc_type_map = [
            'cover letter' => 'Cover Letter',
            'activity proposal' => 'Activity Proposal',
            'financial due diligence' => 'Financial Due Diligence',
            'letter of withdrawal' => 'Letter of Withdrawal',
            'documentation report' => 'Documentation Report',
            'minutes of meeting' => 'Minutes of Meeting'
        ];

        $lower_name = strtolower($originalName);
        $matched_doc_type = null;
        foreach ($doc_type_map as $key => $label) {
            if (strpos($lower_name, str_replace(' ', '', $key)) !== false || strpos($lower_name, $key) !== false) {
                $matched_doc_type = $label;
                break;
            }
        }

        if ($matched_doc_type) {
            $stmt = $conn->prepare("
                UPDATE event_document_files 
                SET file_path = ? 
                WHERE request_id = ? AND doc_type = ? AND event_id = ?
            ");
            if ($stmt === false) {
                echo json_encode(['status' => 'error', 'message' => 'Prepare failed: ' . $conn->error]);
                exit;
            }
            $stmt->bind_param("sisi", $dbPath, $request_id, $matched_doc_type, $event_id);
            if (!$stmt->execute()) {
                echo json_encode(['status' => 'error', 'message' => 'File path update failed: ' . $stmt->error]);
                $stmt->close();
                exit;
            }
            error_log("DEBUG: Updated file path for $matched_doc_type");
            $stmt->close();

        } else {
            error_log("DEBUG: No matching doc type found for $originalName");
        }
        } else {
            error_log("DEBUG: No file uploaded or upload error");
        }

    // ✅ Update VP signature status
    $now = date("Y-m-d H:i:s");
    $stmt = $conn->prepare("
        UPDATE event_signature_flow 
        SET signed_at = ?, status = 'signed' 
        WHERE request_id = ? AND role = ? AND event_id = ?
    ");
    if ($stmt === false) {
        echo json_encode(['status' => 'error', 'message' => 'Prepare failed: ' . $conn->error]);
        exit;
    }
    $stmt->bind_param("sisi", $now, $request_id, $role, $event_id);
    if (!$stmt->execute()) {
        echo json_encode(['status' => 'error', 'message' => 'Signature update failed: ' . $stmt->error]);
        $stmt->close();
        $conn->close();
        exit;
    }
    
    // ✅ Check how many rows were affected
    $affected_rows = $stmt->affected_rows;
    error_log("DEBUG: Signature update affected $affected_rows rows");
    $stmt->close();

    // ✅ Get VP’s sequence order
    $stmt = $conn->prepare("
        SELECT sequence_order 
        FROM event_signature_flow 
        WHERE request_id = ? AND role = ? AND event_id = ?
    ");
    if ($stmt === false) {
        echo json_encode(['status' => 'error', 'message' => 'Prepare failed: ' . $conn->error]);
        exit;
    }
    $stmt->bind_param("isi", $request_id, $role, $event_id);
    $stmt->execute();
    $stmt->bind_result($order);
    if ($stmt->fetch()) {
        error_log("DEBUG: Found order=$order for request_id=$request_id, role=$role, event_id=$event_id");
    } else {
        error_log("DEBUG: No order found for request_id=$request_id, role=$role, event_id=$event_id");
    }
    $stmt->close();

    // ✅ Activate next role
    if (!empty($order)) {
        $next = $order + 1;
        $stmt = $conn->prepare("
            UPDATE event_signature_flow 
            SET status = 'pending' 
            WHERE request_id = ? AND sequence_order = ? AND event_id = ?
        ");
        if ($stmt === false) {
            echo json_encode(['status' => 'error', 'message' => 'Prepare failed: ' . $conn->error]);
            exit;
        }
        $stmt->bind_param("iii", $request_id, $next, $event_id);
        if (!$stmt->execute()) {
            echo json_encode(['status' => 'error', 'message' => 'Next role update failed: ' . $stmt->error]);
            $stmt->close();
            exit;
        }
        $next_affected = $stmt->affected_rows;
        error_log("DEBUG: Next role update affected $next_affected rows (order=$order, next=$next)");
        $stmt->close();
        } else {
            error_log("DEBUG: No next order (this is the last role)");
        }

        echo json_encode(['status' => 'success', 'message' => 'Documents submitted successfully']);
        $conn->close();
        exit;
    }

echo json_encode(['status' => 'error', 'message' => 'Invalid request method']);
?>
