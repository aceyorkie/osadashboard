<?php
header('Content-Type: application/json');

$host = 'localhost';
$user = 'root';
$pass = '';

$practice  = new mysqli($host, $user, $pass, 'practice_db');
$orgportal = new mysqli($host, $user, $pass, 'orgportal');

if ($practice->connect_error || $orgportal->connect_error) {
    echo json_encode(['status'=>'error','message'=>'DB connection failed']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['status'=>'error','message'=>'Invalid request']);
    exit;
}

/* -----------------------------
   VALIDATE INPUT
------------------------------ */
$request_id = intval($_POST['request_id'] ?? 0);
$role       = trim($_POST['role'] ?? '');

if ($request_id <= 0 || $role !== 'OSA') {
    echo json_encode(['status'=>'error','message'=>'Invalid OSA request']);
    exit;
}

/* -----------------------------
   VERIFY OSA IS CURRENT SIGNER
------------------------------ */
$verify = $practice->prepare("
    SELECT sequence_order
    FROM signature_flow
    WHERE request_id = ?
      AND role = 'OSA'
      AND status = 'pending'
    LIMIT 1
");
$verify->bind_param("i", $request_id);
$verify->execute();
$verify->bind_result($order);
$verify->fetch();
$verify->close();

if (!$order) {
    echo json_encode(['status'=>'error','message'=>'OSA not allowed to sign yet']);
    exit;
}

/* -----------------------------
   SAVE FINAL SIGNED PDF
------------------------------ */
$abs_base = "C:/xampp/htdocs/officerDashboardCopy/create_org";
$upload_dir = "$abs_base/annotated_docs/request_$request_id/";
if (!is_dir($upload_dir)) mkdir($upload_dir, 0777, true);

$final_web_path = null;
$matched_doc_type = null;

if (!empty($_FILES['final_pdf']['tmp_name'])) {

    $originalPath = $_POST['original_path'] ?? '';
    $originalName = basename($originalPath);

    $targetName = "signed_by_OSA_" . $originalName;
    $targetPath = $upload_dir . $targetName;

    move_uploaded_file($_FILES['final_pdf']['tmp_name'], $targetPath);
    $final_web_path = "annotated_docs/request_$request_id/$targetName";

    /* Map doc type */
    $map = [
        'applicationletter'      => 'Application Letter',
        'constitutionandbylaws'  => 'Constitution and By-laws',
        'listofofficers'         => 'List of Officers and Members',
        'advisershipletter'      => 'Advisorship Letter',
        'annualoperationalplan'  => 'Annual Operational Plan',
        'additionaldocument'     => 'Additional Document'
    ];

    $clean = strtolower(preg_replace('/[^a-z]/', '', $originalName));
    foreach ($map as $k => $v) {
        if (strpos($clean, $k) !== false) {
            $matched_doc_type = $v;
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

/* -----------------------------
   MARK OSA AS SIGNED
------------------------------ */
$now = date("Y-m-d H:i:s");
$stmt = $practice->prepare("
    UPDATE signature_flow
    SET status = 'signed', signed_at = ?
    WHERE request_id = ? AND role = 'OSA'
");
$stmt->bind_param("si", $now, $request_id);
$stmt->execute();
$stmt->close();

/* -----------------------------
   FETCH ORG INFO
------------------------------ */
$stmt = $practice->prepare("
    SELECT org_code, organization_type
    FROM document_files
    WHERE request_id = ?
    LIMIT 1
");
$stmt->bind_param("i", $request_id);
$stmt->execute();
$stmt->bind_result($orgCode, $orgType);
$stmt->fetch();
$stmt->close();

/* -----------------------------
   FINAL APPROVAL (OSA)
------------------------------ */
if ($orgCode && $orgType) {

    if ($orgType === 'Academic') {
        $stmt = $orgportal->prepare("
            UPDATE dtp_organization
            SET org_status = 'approved'
            WHERE org_code = ?
        ");
    } elseif ($orgType === 'Non-Academic') {
        $stmt = $orgportal->prepare("
            UPDATE nonacad_organization
            SET org_status = 'approved'
            WHERE org_code = ?
        ");
    } elseif ($orgType === 'Department') {
        $stmt = $orgportal->prepare("
            UPDATE department
            SET dept_status = 'approved'
            WHERE dept_code = ?
        ");
    }

    if (isset($stmt)) {
        $stmt->bind_param("s", $orgCode);
        $stmt->execute();
        $stmt->close();
    }
}

/* -----------------------------
   ARCHIVE FINAL PDF
------------------------------ */
if ($final_web_path && file_exists("$abs_base/$final_web_path")) {

    $timestamp = date("Ymd_His");
    $archiveName = "FINAL_OSA_$timestamp.pdf";

    $paths = [
        "C:/xampp/htdocs/officerDashboardCopy/archives/$orgCode",
        "C:/xampp/htdocs/osaDashboard/archive/$orgCode"
    ];

    foreach ($paths as $p) {
        if (!is_dir($p)) mkdir($p, 0777, true);
        copy("$abs_base/$final_web_path", "$p/$archiveName");
    }
}

echo json_encode([
    'status'  => 'success',
    'message' => 'OSA final approval completed'
]);
exit;
