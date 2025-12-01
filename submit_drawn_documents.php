<?php
header('Content-Type: application/json');

$host = 'localhost';
$user = 'root';
$pass = '';

$practice = new mysqli($host, $user, $pass, 'practice_db');
$orgportal = new mysqli($host, $user, $pass, 'orgportal');

if ($practice->connect_error) {
    echo json_encode(['status' => 'error', 'message' => 'practice_db ERROR: '.$practice->connect_error]);
    exit;
}
if ($orgportal->connect_error) {
    echo json_encode(['status' => 'error', 'message' => 'orgportal ERROR: '.$orgportal->connect_error]);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['status' => 'error', 'message' => 'Invalid request method']);
    exit;
}

/* ------------------------------------------------------
   VALIDATE INPUTS
------------------------------------------------------ */
$request_id = intval($_POST['request_id'] ?? 0);
$role       = trim($_POST['role'] ?? "");

if ($request_id <= 0) {
    echo json_encode(['status' => 'error', 'message' => 'Invalid request_id']);
    exit;
}
if ($role === "") {
    echo json_encode(['status' => 'error', 'message' => 'Invalid role']);
    exit;
}

/* ------------------------------------------------------
   SAVE MERGED PDF (final)
------------------------------------------------------ */
$abs_base = "C:/xampp/htdocs/officerDashboardCopy/create_org";
$upload_dir = "$abs_base/annotated_docs/request_$request_id/";

if (!is_dir($upload_dir)) mkdir($upload_dir, 0777, true);

$matched_doc_type = null;
$final_web_path   = null;

if (isset($_FILES['final_pdf']) && $_FILES['final_pdf']['error'] === UPLOAD_ERR_OK) {

    $originalPath = $_POST['original_path'] ?? '';
    $originalName = basename($originalPath);

    $targetFilename = "signed_by_{$role}_" . $originalName;
    $target = $upload_dir . $targetFilename;

    // path stored in DB
    $final_web_path = "annotated_docs/request_$request_id/" . $targetFilename;

    if (!move_uploaded_file($_FILES['final_pdf']['tmp_name'], $target)) {
        echo json_encode(['status' => 'error', 'message' => 'Failed to save annotated PDF']);
        exit;
    }

    /* MAP DOC TYPE */
    $doc_type_map = [
        'applicationletter'      => 'Application Letter',
        'constitutionandby-laws' => 'Constitution and By-laws',
        'listofofficers'         => 'List of Officers and Members',
        'advisershipletter'      => 'Advisership Letter',
        'annualoperationalplan'  => 'Annual Operational Plan',
        'additionaldocument'     => 'Additional Document'
    ];

    $lower_name = strtolower(str_replace(['_', '-', '.pdf'], '', $originalName));

    foreach ($doc_type_map as $key => $label) {
        if (strpos($lower_name, str_replace(' ', '', $key)) !== false) {
            $matched_doc_type = $label;
            break;
        }
    }

    if ($matched_doc_type) {
        $stmt = $practice->prepare("
            UPDATE document_files 
            SET file_path = ?
            WHERE request_id = ? AND doc_type = ?
        ");
        $stmt->bind_param("sis", $final_web_path, $request_id, $matched_doc_type);
        $stmt->execute();
        $stmt->close();
    }
}

/* ------------------------------------------------------
   UPDATE SIGNATURE STATUS (OSA = SIGNED)
------------------------------------------------------ */
$now = date("Y-m-d H:i:s");

$stmt = $practice->prepare("
    UPDATE signature_flow 
    SET signed_at = ?, status = 'signed'
    WHERE request_id = ? AND role = ?
");
$stmt->bind_param("sis", $now, $request_id, $role);
$stmt->execute();
$stmt->close();

/* ------------------------------------------------------
   GET org_code FOR FINAL OSA ACTIONS
------------------------------------------------------ */
$orgCode = null;
$stmt = $practice->prepare("SELECT org_code FROM document_files WHERE request_id = ? LIMIT 1");
$stmt->bind_param("i", $request_id);
$stmt->execute();
$stmt->bind_result($orgCode);
$stmt->fetch();
$stmt->close();

/* ------------------------------------------------------
   FINAL OSA ACTIONS (LAST SIGNATORY)
------------------------------------------------------ */
if ($role === "OSA" && $orgCode) {

    /* Determine org type */
    $orgType = null;

    $q = $orgportal->prepare("SELECT org_code FROM dtp_organization WHERE org_code = ? LIMIT 1");
    $q->bind_param("s", $orgCode);
    $q->execute();
    $q->store_result();
    if ($q->num_rows > 0) $orgType = "academic";
    $q->close();

    if (!$orgType) {
        $q = $orgportal->prepare("SELECT org_code FROM nonacad_organization WHERE org_code = ? LIMIT 1");
        $q->bind_param("s", $orgCode);
        $q->execute();
        $q->store_result();
        if ($q->num_rows > 0) $orgType = "non-academic";
        $q->close();
    }

    /* Approve organization */
    if ($orgType === "academic") {
        $stmt = $orgportal->prepare("UPDATE dtp_organization SET org_status='approved' WHERE org_code=?");
    } else {
        $stmt = $orgportal->prepare("UPDATE nonacad_organization SET org_status='approved' WHERE org_code=?");
    }
    $stmt->bind_param("s", $orgCode);
    $stmt->execute();
    $stmt->close();

    /* Update officer.organization */
    $stmt = $orgportal->prepare("UPDATE officer SET organization=? WHERE id_no=?");
    $stmt->bind_param("si", $orgCode, $request_id);
    $stmt->execute();
    $stmt->close();

    /* Update adviser.organization */
    $signatoryId = null;
    $stmt = $practice->prepare("SELECT signatory_id FROM signature_flow WHERE request_id=? AND role='Adviser'");
    $stmt->bind_param("i", $request_id);
    $stmt->execute();
    $stmt->bind_result($signatoryId);
    $stmt->fetch();
    $stmt->close();

    if ($signatoryId) {
        $stmt = $orgportal->prepare("UPDATE adviser SET organization=? WHERE id_no=?");
        $stmt->bind_param("si", $orgCode, $signatoryId);
        $stmt->execute();
        $stmt->close();
    }

    /* ARCHIVE PDF */
    if ($matched_doc_type && isset($target) && file_exists($target)) {

        $timestamp = date("Ymd_His");
        $archiveFilename = "{$matched_doc_type}_{$role}_{$timestamp}.pdf";

        // archive dirs
        $arch1 = "C:/xampp/htdocs/officerDashboardCopy/archives/archive/$orgCode";
        $arch2 = "C:/xampp/htdocs/osaDashboard/archive/$orgCode";

        if (!is_dir($arch1)) mkdir($arch1, 0777, true);
        if (!is_dir($arch2)) mkdir($arch2, 0777, true);

        copy($target, "$arch1/$archiveFilename");
        copy($target, "$arch2/$archiveFilename");
    }
}

echo json_encode(['status' => 'success', 'message' => 'OSA final approval completed.']);
exit;

?>
