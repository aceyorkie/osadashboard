<?php
session_start();
$host = "localhost";
$user = "root";   
$pass = "";            
$db   = "orgportal";

$conn = new mysqli($host, $user, $pass, $db);

if ($conn->connect_error) {
    die("Database connection failed: " . $conn->connect_error);
}

$conn->set_charset("utf8mb4");

if (!isset($_SESSION['id_no'])) {
    die("Unauthorized.");
}

$org_code = $_GET['org'] ?? '';

if (empty($org_code)) {
    die("Organization not specified.");
}

$docStmt = $conn->prepare("
    SELECT id, organization
    FROM extracted_documents
    WHERE organization = ?
    ORDER BY id DESC
    LIMIT 1
");
$docStmt->bind_param("s", $org_code);
$docStmt->execute();
$docResult = $docStmt->get_result();
$docData = $docResult->fetch_assoc();
$docStmt->close();

if (!$docData) {
    die("No AOP uploaded for this organization yet.");
}

$doc_id = $docData['id'];

$sql = "
    SELECT 
        etd.row_index,
        etd.cell_value,
        etd.status,
        ar.id AS report_id,
        ar.report_file,
        ar.approval_status
    FROM extracted_table_data etd
    LEFT JOIN aop_reports ar
        ON ar.document_id = etd.document_id
        AND ar.row_index = etd.row_index
    WHERE etd.document_id = ?
    ORDER BY etd.row_index
";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $doc_id);
$stmt->execute();
$result = $stmt->get_result();

/* Group rows */
$rows = [];
while ($r = $result->fetch_assoc()) {
    $rowIndex = $r['row_index'];

    if (!isset($rows[$rowIndex])) {
        $rows[$rowIndex] = [
            'cells'           => [],
            'status'          => $r['status'],
            'report_id'       => $r['report_id'],
            'report_file'     => $r['report_file'],
            'approval_status' => $r['approval_status']
        ];
    }

    $rows[$rowIndex]['cells'][] = $r['cell_value'];
}

?>

<!DOCTYPE html>
<html>
<head>
    <title>AOP View</title>
    <link rel="stylesheet" href="/orgportal/aop.css">
</head>
<body>
    <nav>
        <div class="nav-left">
          <img src="/osaDashboard/greetings/umdc-logo.png" alt="Logo" class="logo">
        </div>
        <div class="nav-center">
          <a href="/osaDashboard/Osa.php" onclick="dept()">Departments</a>
          <a href="/osaDashboard/approval.php" onclick="approvals()">Approvals</a>
          <a href="#about" onclick="about()">Calendar</a>
        </div>
    </nav>

    <div class="page-wrapper">

        <div class="card">
            <div class="section-title">Annual Operational Plan</div>
            <div class="subtitle"><?= htmlspecialchars($org_code) ?></div>

            <div class="table-wrapper">
                <table class="data-table">
                    <?php foreach ($rows as $rowIndex => $row): ?>
                        <tr class="<?= $rowIndex == 0 ? 'table-header' : '' ?>">

                            <?php foreach ($row['cells'] as $cell): ?>
                                <td><?= htmlspecialchars($cell) ?></td>
                            <?php endforeach; ?>

                            <!-- STATUS COLUMN (VIEW ONLY) -->
                            <td class="action-cell">
                                <?php if ($rowIndex == 0): ?>
                                    Action
                                <?php elseif ($row['report_id']): ?>

                                    <!-- VIEW REPORT -->
                                    <a 
                                        href="/orgportal/view_report.php?doc=<?= $doc_id ?>&row=<?= $rowIndex ?>"
                                        class="view-btn"
                                        target="_blank">
                                        View Report
                                    </a>

                                    <!-- APPROVAL STATE -->
                                    <?php if ($row['approval_status'] === 'approved'): ?>
                                        <div class="done-label">✔ Approved</div>

                                    <?php elseif ($row['approval_status'] === 'rejected'): ?>
                                        <div class="rejected-label">✖ Rejected</div>

                                    <?php else: ?>
                                        <!-- APPROVE / REJECT -->
                                        <form method="POST" action="php/approve_report.php" class="approval-form">
                                            <input type="hidden" name="report_id" value="<?= $row['report_id'] ?>">
                                            <button name="action" value="approved" class="approve-btn">Approve</button>
                                            <button name="action" value="rejected" class="reject-btn">Reject</button>
                                        </form>
                                    <?php endif; ?>

                                <?php else: ?>
                                    <span class="pending-label">No report submitted</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </table>
            </div>
        </div>
    </div>

</body>
</html>
