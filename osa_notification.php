<?php
session_start();
if (!isset($_SESSION['id_no'])) {
    echo "<div style='padding:10px;'>Not logged in.</div>";
    exit;
}

$osa_id = $_SESSION['id_no'];

$conn = new mysqli("localhost", "root", "", "practice_db");
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

if (isset($_GET['event_id'])) {

    $raw_event = $_GET['event_id']; 
    $event_id  = ($raw_event === "N/A" || $raw_event === "" || $raw_event === "null") 
                 ? null 
                 : intval($raw_event);

    if ($event_id !== null && $event_id > 0) {
        $updateStmt = $conn->prepare("
            UPDATE notifications 
            SET status='read' 
            WHERE recipient_id=? AND event_id=?
        ");
        $updateStmt->bind_param("ii", $osa_id, $event_id);
        $updateStmt->execute();
        $updateStmt->close();

        header("Location: /osa/create_event/event_request.php?event_id={$event_id}");
        exit();

    } else {
        $updateOrg = $conn->prepare("
            UPDATE notifications 
            SET status='read' 
            WHERE recipient_id=? AND event_id IS NULL
        ");
        $updateOrg->bind_param("i", $osa_id);
        $updateOrg->execute();
        $updateOrg->close();

        $reqStmt = $conn->prepare("
            SELECT request_id 
            FROM notifications 
            WHERE recipient_id=? AND event_id IS NULL 
            ORDER BY created_at DESC LIMIT 1
        ");
        $reqStmt->bind_param("i", $osa_id);
        $reqStmt->execute();
        $reqResult = $reqStmt->get_result();
        $reqRow = $reqResult->fetch_assoc();
        $reqStmt->close();

        $latest_request_id = $reqRow['request_id'] ?? null;

        header("Location: /osaDashboard/request.php?request_id={$latest_request_id}");
        exit();
    }
}

$stmt = $conn->prepare("SELECT * FROM notifications WHERE recipient_id=? ORDER BY created_at DESC");
$stmt->bind_param("i", $osa_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result && $result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $event_id = $row['event_id']; // Can be NULL
        $status = htmlspecialchars($row['status']);
        $created_at = htmlspecialchars($row['created_at']);

        // ✅ FIXED: Check if event_id exists before querying
        if ($event_id !== null) {
            $checkEvent = $conn->prepare("SELECT event_name FROM event_document_files WHERE event_id=? LIMIT 1");
            $checkEvent->bind_param("i", $event_id);
            $checkEvent->execute();
            $eventResult = $checkEvent->get_result();

            if ($eventResult && $eventResult->num_rows > 0) {
                $event = $eventResult->fetch_assoc();
                $title = htmlspecialchars($event['event_name']);
                $badge = "<span class='badge bg-success'>Event</span>";
                $message = "You have a pending event request: <strong>$title</strong>";
            } else {
                $checkOrg = $conn->prepare("SELECT org_code FROM document_files WHERE event_id=? LIMIT 1");
                $checkOrg->bind_param("i", $event_id);
                $checkOrg->execute();
                $orgResult = $checkOrg->get_result();

                if ($orgResult && $orgResult->num_rows > 0) {
                    $org = $orgResult->fetch_assoc();
                    $title = htmlspecialchars($org['org_code']);
                    $badge = "<span class='badge bg-primary'>Organization</span>";
                    $message = "You have a pending organization request: <strong>$title</strong>";
                } else {
                    $badge = "<span class='badge bg-secondary'>Notice</span>";
                    $title = "Unknown";
                    $message = "You have a new pending request";
                }
                $checkOrg->close();
            }
            $checkEvent->close();
        } else {
            $badge = "<span class='badge bg-secondary'>Notice</span>";
            $title = "Unknown";
            $message = "You have a new pending request";
        }

        $itemClass = ($status === 'unread') ? 'notif-item unread' : 'notif-item';
        $event_id_display = $event_id ?? 'N/A';

        echo "
        <a href='osa_notification.php?event_id={$event_id_display}' class='{$itemClass}'>
            {$badge} {$message}<br>
            <small class='text-muted'>{$created_at}</small>
        </a>";
    }
} else {
    echo "<div style='padding:10px;'>No notifications found.</div>";
}

$stmt->close();
$conn->close();
?>