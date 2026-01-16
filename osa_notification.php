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

$event_id = $_GET['event_id'] ?? null;
$org_id   = $_GET['org_id']   ?? null;

if (!empty($event_id) && $event_id !== "N/A") {

    $event_id = intval($event_id);

    $updateStmt = $conn->prepare("
        UPDATE notifications 
        SET status='read' 
        WHERE recipient_id=? AND event_id=?
    ");
    $updateStmt->bind_param("ii", $osa_id, $event_id);
    $updateStmt->execute();
    $updateStmt->close();

    header("Location: /osaDashboard/create_event/event_request.php?event_id={$event_id}");
    exit();
}

if (!empty($org_id) && $org_id !== "N/A") {

    $org_id = intval($org_id);

    $updateOrg = $conn->prepare("
        UPDATE notifications 
        SET status='read' 
        WHERE recipient_id=? AND org_id=?
    ");
    $updateOrg->bind_param("ii", $osa_id, $org_id);
    $updateOrg->execute();
    $updateOrg->close();

    header("Location: /osaDashboard/request.php?org_id={$org_id}");
    exit();
}

/* ================= LIST NOTIFICATIONS ================= */

$stmt = $conn->prepare("
    SELECT * 
    FROM notifications 
    WHERE recipient_id=? 
    ORDER BY created_at DESC
");
$stmt->bind_param("i", $osa_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result && $result->num_rows > 0) {

    while ($row = $result->fetch_assoc()) {

        $event_id = $row['event_id'];
        $org_id   = $row['org_id'];
        $status   = htmlspecialchars($row['status']);
        $created_at = htmlspecialchars($row['created_at']);

        /* ---------- EVENT NOTIFICATION ---------- */
        if (!empty($event_id)) {

            $checkEvent = $conn->prepare("
                SELECT event_name 
                FROM event_document_files 
                WHERE event_id=? LIMIT 1
            ");
            $checkEvent->bind_param("i", $event_id);
            $checkEvent->execute();
            $eventResult = $checkEvent->get_result();

            if ($eventResult && $eventResult->num_rows > 0) {
                $event = $eventResult->fetch_assoc();
                $title = htmlspecialchars($event['event_name']);
                $badge = "<span class='badge bg-success'>Event</span>";
                $message = "You have a pending event request: <strong>$title</strong>";
            }

            $checkEvent->close();

        }
        /* ---------- ORG / ACCREDITATION ---------- */
        elseif (!empty($org_id)) {

            $checkOrg = $conn->prepare("
                SELECT org_code 
                FROM document_files 
                WHERE org_id=? 
                LIMIT 1
            ");
            $checkOrg->bind_param("i", $org_id);
            $checkOrg->execute();
            $orgResult = $checkOrg->get_result();

            if ($orgResult && $orgResult->num_rows > 0) {
                $org = $orgResult->fetch_assoc();
                $title = htmlspecialchars($org['org_code']);
                $badge = "<span class='badge bg-primary'>Organization</span>";
                $message = "You have a pending organization request: <strong>$title</strong>";
            } else {
                $badge = "<span class='badge bg-secondary'>Notice</span>";
                $message = "You have a new organization request";
            }

            $checkOrg->close();

        }
        /* ---------- FALLBACK ---------- */
        else {
            $badge = "<span class='badge bg-secondary'>Notice</span>";
            $message = "You have a new pending request";
        }

        $itemClass = ($status === 'unread') ? 'notif-item unread' : 'notif-item';

        $link = !empty($event_id)
            ? "/osaDashboard/osa_notification.php?event_id={$event_id}"
            : "/osaDashboard/osa_notification.php?org_id={$org_id}";

        echo "
        <a href='{$link}' class='{$itemClass}'>
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
