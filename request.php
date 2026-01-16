<?php
session_start();

if (!isset($_SESSION['id_no'])) {
  header("Location: osa_login.php");
  exit();
}

$osa_id = $_SESSION['id_no'];

$osaDb = new mysqli('localhost', 'root', '', 'orgportal');
if ($osaDb->connect_error) {
  die("Connection failed (osa): " . $osaDb->connect_error);
}

$checkSig = $osaDb->prepare("SELECT signature FROM osa WHERE id_no = ?");
$checkSig->bind_param("i", $osa_id);
$checkSig->execute();
$sigResult = $checkSig->get_result()->fetch_assoc();
$osaSignature = !empty($sigResult['signature']) ? $sigResult['signature'] : null;
$checkSig->close();

$id_no = $osa_id; 
$osaSql = "SELECT id_no, name, signature FROM osa WHERE id_no = ?";
$osaStmt = $osaDb->prepare($osaSql);
$osaStmt->bind_param("i", $id_no);
$osaStmt->execute();
$osaResult = $osaStmt->get_result();
$osa = $osaResult->fetch_assoc();
$osaStmt->close();

$practice = new mysqli("localhost", "root", "", "practice_db");
if ($practice->connect_error) {
    die("Practice DB connection failed: " . $practice->connect_error);
}

$org_id = $_GET['org_id'] ?? null;

if (!$org_id) {
    die("Invalid access: missing org_id");
}

$getRequest = $practice->prepare("
    SELECT request_id
    FROM document_files
    WHERE org_id = ?
    ORDER BY request_id DESC
    LIMIT 1
");
$getRequest->bind_param("i", $org_id);
$getRequest->execute();
$getRequest->bind_result($request_id);
$getRequest->fetch();
$getRequest->close();

if (!$request_id) {
    die("No documents found for this organization.");
}

$presQuery = $practice->prepare(" SELECT signatory_id, status FROM signature_flow WHERE request_id = ? AND role = 'President' LIMIT 1");
$presQuery->bind_param("i", $request_id);
$presQuery->execute();
$presResult = $presQuery->get_result()->fetch_assoc();
$presQuery->close();

$presidentSigned = false;
$presidentSignedName = "";
$presidentSignedID = "";
$presidentSignedSignature = "";

if ($presResult && $presResult['status'] === 'signed') {
    $presidentSigned = true;
    $presidentID = $presResult['signatory_id'];

    $org = new mysqli("localhost", "root", "", "orgportal");
    $getPres = $org->prepare("SELECT name, signature FROM officer WHERE id_no = ?");
    $getPres->bind_param("s", $presidentID);
    $getPres->execute();
    $fetchPres = $getPres->get_result()->fetch_assoc();

    if ($fetchPres) {
        $presidentSignedName = $fetchPres['name'];
        $presidentSignedID = $presidentID;
        $presidentSignedSignature = $fetchPres['signature'];
    }

    $getPres->close();
    $org->close();
}

$advQuery = $practice->prepare(" SELECT signatory_id, status FROM signature_flow WHERE request_id = ? AND role = 'Adviser' LIMIT 1");
$advQuery->bind_param("i", $request_id);
$advQuery->execute();
$advResult = $advQuery->get_result()->fetch_assoc();
$advQuery->close();

$adviserSigned = false;
$adviserSignedName = "";
$adviserSignedID = "";
$adviserSignedSignature = "";

if ($advResult && $advResult['status'] === 'signed') {
  $adviserSigned = true;
  $adviserID = $advResult['signatory_id'];

  $org2 = new mysqli("localhost", "root", "", "orgportal");
  $getAdv = $org2->prepare("SELECT name, signature FROM adviser WHERE id_no = ?");
  $getAdv->bind_param("i", $adviserID);
  $getAdv->execute();
  $fetchAdv = $getAdv->get_result()->fetch_assoc();

  if ($fetchAdv) {
      $adviserSignedName = $fetchAdv['name'];
      $adviserSignedID = $adviserID;
      $adviserSignedSignature = $fetchAdv['signature'];
  }

  $getAdv->close();
  $org2->close();
}

$phQuery = $practice->prepare(" SELECT signatory_id, status FROM signature_flow WHERE request_id = ? AND role = 'Program Head' LIMIT 1");
$phQuery->bind_param("i", $request_id);
$phQuery->execute();
$phResult = $phQuery->get_result()->fetch_assoc();
$phQuery->close();

$programheadSigned = false;
$programheadSignedName = "";
$programheadSignedID = "";
$programheadSignedSignature = "";

if ($phResult && $phResult['status'] === 'signed') {
  $programheadSigned = true;
  $programheadID = $phResult['signatory_id'];

  $org2 = new mysqli("localhost", "root", "", "orgportal");
  $getPh = $org2->prepare("SELECT name, signature FROM programhead WHERE id_no = ?");
  $getPh->bind_param("i", $programheadID);
  $getPh->execute();
  $fetchPh = $getPh->get_result()->fetch_assoc();

  if ($fetchPh) {
    $programheadSignedName = $fetchPh['name'];
    $programheadSignedID = $programheadID;
    $programheadSignedSignature = $fetchPh['signature'];
  }

  $getPh->close();
  $org2->close();
}

$deanQuery = $practice->prepare(" SELECT signatory_id, status FROM signature_flow WHERE request_id = ? AND role = 'Dean' LIMIT 1");
$deanQuery->bind_param("i", $request_id);
$deanQuery->execute();
$deanResult = $deanQuery->get_result()->fetch_assoc();
$deanQuery->close();

$deanSigned = false;
$deanSignedName = "";
$deanSignedID = "";
$deanSignedSignature = "";

if ($deanResult && $deanResult['status'] === 'signed') {
  $deanSigned = true;
  $deanID = $deanResult['signatory_id'];

  $org2 = new mysqli("localhost", "root", "", "orgportal");
  $getDean = $org2->prepare("SELECT name, signature FROM dean WHERE id_no = ?");
  $getDean->bind_param("i", $deanID);
  $getDean->execute();
  $fetchDean = $getDean->get_result()->fetch_assoc();

  if ($fetchDean) {
    $deanSignedName = $fetchDean['name'];
    $deanSignedID = $deanID;
    $deanSignedSignature = $fetchDean['signature'];
  }

  $getDean->close();
  $org2->close();
}

$vpQuery = $practice->prepare(" SELECT signatory_id, status FROM signature_flow WHERE request_id = ? AND role = 'VP' LIMIT 1");
$vpQuery->bind_param("i", $request_id);
$vpQuery->execute();
$vpResult = $vpQuery->get_result()->fetch_assoc();
$vpQuery->close();

$vpSigned = false;
$vpSignedName = "";
$vpSignedID = "";
$vpSignedSignature = "";

if ($vpResult && $vpResult['status'] === 'signed') {
  $vpSigned = true;
  $vpID = $vpResult['signatory_id'];

  $org2 = new mysqli("localhost", "root", "", "orgportal");
  $getVp = $org2->prepare("SELECT name, signature FROM vp WHERE id_no = ?");
  $getVp->bind_param("i", $vpID);
  $getVp->execute();
  $fetchVp = $getVp->get_result()->fetch_assoc();

  if ($fetchVp) {
    $vpSignedName = $fetchVp['name'];
    $vpSignedID = $vpID;
    $vpSignedSignature = $fetchVp['signature'];
  }

  $getVp->close();
  $org2->close();
}

$orgDb = new mysqli('localhost', 'root', '', 'orgportal');
if ($orgDb->connect_error) {
    die("Connection failed (orgportal): " . $orgDb->connect_error);
}

$orgQuery = $practice->prepare("
    SELECT org_code, request_type, organization_type
    FROM document_files
    WHERE org_id = ?
    LIMIT 1
");
$orgQuery->bind_param("i", $org_id);
$orgQuery->execute();
$orgInfo = $orgQuery->get_result()->fetch_assoc();
$orgQuery->close();

$orgCode          = $orgInfo['org_code'] ?? null;
$requestType      = $orgInfo['request_type'] ?? null;
$organizationType = $orgInfo['organization_type'] ?? null;

$orgData = null;

if ($orgCode) {

  if ($organizationType === 'Academic') {

    // Academic org → dtp_organization
    $stmt = $orgDb->prepare("
        SELECT org_name, org_code, org_description, org_course, org_logo
        FROM dtp_organization
        WHERE org_code = ?
        LIMIT 1
    ");
    $stmt->bind_param("s", $orgCode);

  } elseif ($organizationType === "Non-Academic"){

      // Non-academic org → nonacad_organization
      $stmt = $orgDb->prepare("
          SELECT org_name, org_code, org_description, org_logo
          FROM nonacad_organization
          WHERE org_code = ?
          LIMIT 1
      ");
      $stmt->bind_param("s", $orgCode);
  } elseif ($organizationType === "Department") {
    $stmt = $orgDb->prepare("
        SELECT dept_name AS org_name, dept_code AS org_code, 
               dept_description AS org_description, dept_logo AS org_logo 
        FROM department 
        WHERE dept_code = ?
    ");
    $stmt->bind_param("s", $orgCode);
  }

    $stmt->execute();
    $result = $stmt->get_result();

        if ($result && $result->num_rows > 0) {
            $orgData = $result->fetch_assoc();
        }

    $stmt->close();
}

$orgDb = new mysqli('localhost', 'root', '', 'orgportal');
if ($orgDb->connect_error) {
    die("Connection failed (orgportal): " . $orgDb->connect_error);
}
$orgQuery = $practice->prepare("
    SELECT DISTINCT org_code, request_type, organization_type
    FROM document_files 
    WHERE request_id = ?
    LIMIT 1
");
$orgQuery->bind_param("i", $request_id);
$orgQuery->execute();
$orgInfo = $orgQuery->get_result()->fetch_assoc();
$orgQuery->close();

$org_code = $orgInfo['org_code'] ?? null;
$requestType = $orgInfo['request_type'] ?? null;       
$organizationType = $orgInfo['organization_type'] ?? null;  

$phQuery = $practice->prepare("
    SELECT signatory_id, status
    FROM signature_flow
    WHERE request_id = ?
      AND role = 'Program Head'
    LIMIT 1
");
$phQuery->bind_param("i", $request_id);
$phQuery->execute();
$phResult = $phQuery->get_result()->fetch_assoc();
$phQuery->close();

$programheadSigned = false;
$programheadSignedName = "";
$programheadSignedID = "";
$programheadSignedSignature = "";

if ($phResult && $phResult['status'] === 'signed') {

    $programheadSigned = true;
    $programheadSignedID = $phResult['signatory_id'];

    // ✅ FETCH FROM ADVISER TABLE (CORRECT)
    $phStmt = $osaDb->prepare("
        SELECT name, signature
        FROM adviser
        WHERE id_no = ?
        LIMIT 1
    ");
    $phStmt->bind_param("i", $programheadSignedID);
    $phStmt->execute();
    $phData = $phStmt->get_result()->fetch_assoc();
    $phStmt->close();

    if ($phData) {
        $programheadSignedName = $phData['name'];
        $programheadSignedSignature = $phData['signature'];
    }
}
$orgDb->close();
?>

<!DOCTYPE html>
<html>
<head>
    <title>OSA Signatory Dashboard</title>
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Roboto:wght@400;700&display=swap">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:ital,wght@0,100..900;1,100..900&display=swap" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@100..900&family=Roboto:ital,wght@0,100;0,300;0,400;0,500;0,700;0,900;1,100;1,300;1,400;1,500;1,700;1,900&display=swap" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@100..900&family=Poppins:ital,wght@0,100;0,200;0,300;0,400;0,500;0,600;0,700;0,800;0,900&family=Roboto:ital,wght@0,100;0,300;0,400;0,500;0,700;0,900;1,100;1,300;1,400;1,500;1,700;1,900&display=swap" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/signature_pad@2.3.2/dist/signature_pad.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/pdf.js/3.4.120/pdf.min.js"></script>
    <script src="https://unpkg.com/pdf-lib/dist/pdf-lib.min.js"></script>
    <link rel="stylesheet" href="/officerDashboardCopy/create_org/request.css">
</head>
<style>
  #docPreview {
    max-height: 600px;        /* Adjust the height as needed */
    overflow-y: auto;         /* Enables vertical scrolling */
    padding: 10px;
    border: 1px solid #ccc;
    background: #f9f9f9;
  }

  #docPreview canvas {
    display: block;
    margin-bottom: 20px;
    max-width: 100%;
  }
</style>

<body>
    <nav>
        <div class="nav-left">
          <img src="/officerDashboardCopy/greetings/umdc-logo.png" alt="Logo" class="logo">
        </div>
        <div class="nav-center">
          <a href="#home" onclick="home()">Home</a>
          <a href="#members" onclick="members()">Members</a>
          <a href="#requests" onclick="requests()">Requests</a>
          <a href="#about" onclick="about()">About</a>
        </div>
        <div class="nav-right">
          <a href="#profile" onclick="profile()">Profile</a>
        </div>
    </nav>

    <div class="main-container">
      <h2 class="title-request"><?php echo $orgData['org_name'] ?? ''; ?> <?php echo $requestType ?? ''; ?></h2>

      <div class="Documents-box">
        <input type="hidden" id="request_id" value="<?php echo $request_id; ?>">

        <form method="POST" action="submit_drawn_documents.php" enctype="multipart/form-data" id="adviserForm">
            <input type="hidden" name="request_id" id="form_request_id" value="<?php echo $requestType; ?>">
            <input type="hidden" name="role" value="Adviser">
            <div class="org-details-flex">
              <div class="org-logo-upload">
                <?php if (!empty($orgData['org_logo'])): ?>
                  <img src="/officerDashboardCopy/create_org/<?php echo $orgData['org_logo']; ?>" alt="Organization Logo" class="logo-preview-img">
                <?php else: ?>
                  <div class="logo-preview-box"><span class="plus-sign">+</span></div>
                <?php endif; ?>
              </div>

              <div class="org-fields">
                <input type="text" name="org_name" placeholder="Organization Name" required value="<?php echo $orgData['org_name'] ?? ''; ?>" readonly>
                <input type="text" name="org_code" placeholder="Organization Code" required value="<?php echo $orgData['org_code'] ?? ''; ?>" readonly>
                <textarea name="org_description" placeholder="Organization Description" required readonly><?php echo $orgData['org_description'] ?? ''; ?></textarea>
                <input type="text" name="org_course" placeholder="Course/Department" required value="<?php echo $orgData['org_course'] ?? ''; ?>" readonly>

                <input type="text" name="request_type" placeholder="Request Type" value="<?php echo $requestType ?? ''; ?>" readonly>
                <input type="text" name="organization_type" placeholder="Organization Type" value="<?php echo $organizationType ?? ''; ?>" readonly>
              </div>
            </div>

            <div class="flex-container">
              <div id="docList" class="doc-list"></div>
              <div id="docPreview" class="preview-container">
                <p style="color: #888;">Select a document to preview it here.</p>
              </div>
            </div>

            <div class="select-approvers-wrapper">
              <h4 style="font-family:Poppins;margin:10px 0;">Select Signatories</h4>
              <div class="select-approvers">
                <!-- president -->
                <div class="signatory-card">
                  <div class="signature-placeholder" id="president-signature-box" onclick="<?= $presidentSigned ? '' : "triggerFileInput('president')" ?>">
                    <?php if ($presidentSigned && !empty($presidentSignedSignature)): ?>
                      <!-- SHOW SIGNED PRESIDENT SIGNATURE -->
                      <img src="<?= htmlspecialchars($presidentSignedSignature) ?>" style="width:100px; height:auto; display:block; margin:auto;">

                      <?php elseif ($officerRole === 'President' && !empty($signaturePath)): ?>
                      <img src="<?= htmlspecialchars($signaturePath) ?>" style="width:100px; height:auto; display:block; margin:auto;">

                      <?php else: ?>
                        <span>+</span>
                      <?php endif; ?>
                      <input type="file" id="president_file" accept="image/*" style="display:none" onchange="previewSignature(this, 'president')" <?= $presidentSigned ? 'disabled' : '' ?>>
                  </div>

                  <div class="sign-info" style="width:100%;">
                    <input list="presidentList" id="president_name" name="president_name" placeholder="Select President"autocomplete="off" value="<?= $presidentSigned ? htmlspecialchars($presidentSignedName) : ($officerRole === 'President' ? htmlspecialchars($officerName) : '') ?>" <?= ($presidentSigned || $officerRole === 'President') ? 'readonly' : '' ?>>
                    <input type="hidden" id="president_id"name="president_id" value="<? $presidentSigned ? htmlspecialchars($presidentSignedID) : ($officerRole === 'President' ? htmlspecialchars($student_id) : '') ?>">
                    <datalist id="presidentList"></datalist>
                    <div class="role">President</div>
                  </div>
                </div>
                <!-- adviser -->
                <div class="signatory-card">
                  <div class="signature-placeholder" id="adviser-signature-box" onclick="<?= $adviserSigned ? '' : "triggerFileInput('adviser')" ?>">
                    <?php if ($adviserSigned && !empty($adviserSignedSignature)): ?>
                      <!-- SHOW SIGNED ADVISER SIGNATURE -->
                      <img src="<?= htmlspecialchars($adviserSignedSignature) ?>" style="width:100px; height:auto; display:block; margin:auto;">
                  
                      <?php elseif (!empty($adviserSignature)): ?>
                      <img src="<?= htmlspecialchars($adviserSignature) ?>" style="width:100px; height:auto; display:block; margin:auto;">

                      <?php else: ?>
                        <span>+</span>
                      <?php endif; ?>
                      <input type="file" id="adviser_file" accept="image/*" style="display:none"onchange="previewSignature(this, 'adviser')" <?= $adviserSigned ? 'disabled' : '' ?>>
                  </div>

                  <div class="sign-info" style="width:100%;">
                    <input list="adviserList" id="adviser_name" name="adviser_name" placeholder="Select Adviser"autocomplete="off" value="<?= $adviserSigned ? htmlspecialchars($adviserSignedName) : htmlspecialchars($adviser['name'] ?? '') ?>" readonly>
                    <input type="hidden" id="adviser_id" name="adviser_id" value="<?= $adviserSigned ? htmlspecialchars($adviserSignedID) : htmlspecialchars($adviser['id_no'] ?? '') ?>">
                    <datalist id="adviserList"></datalist>
                    <div class="role">Adviser</div>
                  </div>
                </div>
                <!-- program head -->
                <div class="signatory-card">
                  <div class="signature-placeholder">
                    <?php if ($programheadSigned && !empty($programheadSignedSignature)): ?>
                      <img src="<?= htmlspecialchars($programheadSignedSignature) ?>">
                    <?php else: ?>
                      <span>Pending</span>
                    <?php endif; ?>
                  </div>

                  <div class="sign-info" style="width:100%;">
                    <input type="text"
                          value="<?= htmlspecialchars($programheadSignedName ?: 'Program Head') ?>"
                          readonly>
                    <input type="hidden"
                          value="<?= htmlspecialchars($programheadSignedID) ?>">
                          <div class="role">Program Head</div>
                  </div>
                </div>
                <!-- dean -->
                <div class="signatory-card">
                  <div class="signature-placeholder" id="dean-signature-box" onclick="<?= $deanSigned ? '' : "triggerFileInput('dean')" ?>">
                    <?php if ($deanSigned && !empty($deanSignedSignature)): ?>
                      <!-- SHOW SIGNED ADVISER SIGNATURE -->
                      <img src="<?= htmlspecialchars($deanSignedSignature) ?>" style="width:100px; height:auto; display:block; margin:auto;">
                  
                      <?php elseif (!empty($deanSignature)): ?>
                      <img src="<?= htmlspecialchars($deanSignature) ?>" style="width:100px; height:auto; display:block; margin:auto;">

                      <?php else: ?>
                        <span>+</span>
                      <?php endif; ?>
                      <input type="file" id="dean_file" accept="image/*" style="display:none"onchange="previewSignature(this, 'dean')" <?= $deanSigned ? 'disabled' : '' ?>>
                  </div>

                  <div class="sign-info" style="width:100%;">
                    <input list="deanList" id="dean_name" name="dean_name" placeholder="Select Dean"autocomplete="off" value="<?= $deanSigned ? htmlspecialchars($deanSignedName) : htmlspecialchars($dean['name'] ?? '') ?>" readonly>
                    <input type="hidden" id="dean_id" name="dean_id" value="<?= $deanSigned ? htmlspecialchars($deanSignedID) : htmlspecialchars($dean['id_no'] ?? '') ?>">
                    <datalist id="deanList"></datalist>
                    <div class="role">Dean</div>
                  </div>
                </div>
                <!-- vp -->
                <div class="signatory-card">
                  <div class="signature-placeholder" id="vp-signature-box" onclick="<?= $vpSigned ? '' : "triggerFileInput('vp')" ?>">
                    <?php if ($vpSigned && !empty($vpSignedSignature)): ?>
                      <img src="<?= htmlspecialchars($vpSignedSignature) ?>" style="width:100px; height:auto; display:block; margin:auto;">
                  
                      <?php elseif (!empty($vpSignature)): ?>
                      <img src="<?= htmlspecialchars($vpSignature) ?>" style="width:100px; height:auto; display:block; margin:auto;">

                      <?php else: ?>
                        <span>+</span>
                      <?php endif; ?>
                      <input type="file" id="vp_file" accept="image/*" style="display:none"onchange="previewSignature(this, 'vp')" <?= $vpSigned ? 'disabled' : '' ?>>
                  </div>
                  <div class="sign-info" style="width:100%;">
                    <input list="vpList" id="vp_name" name="vp_name" placeholder="Select VP"autocomplete="off" value="<?= $vpSigned ? htmlspecialchars($vpSignedName) : htmlspecialchars($vp['name'] ?? '') ?>" readonly>
                    <input type="hidden" id="vp_id" name="vp_id" value="<?= $vpSigned ? htmlspecialchars($vpSignedID) : htmlspecialchars($vp['id_no'] ?? '') ?>">
                    <datalist id="vpList"></datalist>
                    <div class="role">Branch Director</div>
                  </div>
                </div>
                <!-- osa -->
                <div class="signatory-card">
                  <div class="signature-placeholder" id="osa-signature-box" onclick="triggerFileInput('osa')">
                    <?php if (!empty($osa['signature'])): ?>
                      <img src="<?php echo htmlspecialchars($osa['signature']); ?>" alt="Signature" style="width:100px; height:auto; display:block; margin:auto;">
                    <?php else: ?>
                      <span>+</span>
                    <?php endif; ?>
                    <input type="file" id="osa_file" accept="image/*" style="display:none" onchange="previewSignature(this, 'osa')">
                  </div>
                  <div class="sign-info" style="width:100%;">
                    <input type="text" 
                          id="osa_name" 
                          name="osa_name" 
                          placeholder="Select OSA" 
                          autocomplete="off"
                          value="<?php echo htmlspecialchars($osa['name']); ?>"
                          readonly>
                    <input type="hidden" 
                          id="osa_id" 
                          name="osa_id" 
                          value="<?php echo htmlspecialchars($osa['id_no']); ?>">
                    <datalist id="osaList"></datalist>
                    <div class="role">OSA</div>
                  </div>
                </div>
              </div>
            </div>
          <div class="button-row">
            <button type="button" id="submitBtn" class="btn primary">Approve & Sign Documents</button>
            <button type="button" id="rejectBtn" class="btn primary" style="background:#e74c3c;color:#fff;">Reject Documents</button>
          </div>
        </form>
      </div>
    </div>

    <div id="signatureModal" class="modal">
      <div class="modal-content">
        <h2>Register Your Signature</h2>
        <p>Please draw or upload your signature to continue.</p>

        <canvas id="signaturePad" width="400" height="200"></canvas>
        <br>
        <button id="clearBtn">Clear</button>
        <button id="saveSignatureBtn">Save Signature</button>
        <hr>
        <label>Or upload signature image:</label>
        <input type="file" id="uploadSignature" accept="image/*">
      </div>
    </div>

  <script>
    let canvasRefs = {};  // pageNum => canvas
    let annotationsStore = {}; // path => { pageNum: imageDataURL }

    function loadDocs() {
      const reqId = document.getElementById("request_id").value;
      document.getElementById("form_request_id").value = reqId;
      fetch("view_documents.php?request_id=" + reqId)
        .then(res => res.json())
        .then(data => {
          const list = document.getElementById("docList");
          if (!data.length) return list.innerHTML = "<p>No documents found.</p>";
          let html = "<table><tr><th>Type</th><th>Action</th></tr>";
          data.forEach(doc => {
            html += `<tr>
              <td>${doc.doc_type}</td>
              <td><button type="button" onclick="previewDoc('${doc.file_path}')">View</button></td>
            </tr>`;
          });
          list.innerHTML = html + "</table>";
          document.getElementById("docPreview").innerHTML = "";
        })
        .catch(err => {
          console.error(err);
          alert("Failed to load documents.");
        });
    }

    function previewDoc(path) {
      // Save current annotations before switching
      if (currentPDFPath && Object.keys(canvasRefs).length) {
        if (!annotationsStore[currentPDFPath]) annotationsStore[currentPDFPath] = {};
        Object.entries(canvasRefs).forEach(([page, canvas]) => {
          annotationsStore[currentPDFPath][page] = canvas.toDataURL();
        });
      }

      currentPDFPath = path;
      canvasRefs = {};
      const container = document.getElementById("docPreview");
      container.innerHTML = "";

      const ext = path.split(".").pop().toLowerCase();
      if (ext === "pdf") {
        pdfjsLib.getDocument(path).promise.then(pdf => {
          for (let pageNum = 1; pageNum <= pdf.numPages; pageNum++) {
            pdf.getPage(pageNum).then(page => {
              const scale = 1;
              const viewport = page.getViewport({
                scale,
                rotation: page.rotate || 0
              });

              const wrapper = document.createElement("div");
              wrapper.style.position = "relative";
              wrapper.style.marginBottom = "20px";

              const pdfCanvas = document.createElement("canvas");
              pdfCanvas.width = viewport.width;
              pdfCanvas.height = viewport.height;
              wrapper.appendChild(pdfCanvas);

              const drawCanvas = document.createElement("canvas");
              drawCanvas.width = viewport.width;
              drawCanvas.height = viewport.height;
              drawCanvas.style.width = viewport.width + "px";
              drawCanvas.style.height = viewport.height + "px"; 
              Object.assign(drawCanvas.style, {
                position: "absolute",
                top: 0,
                left: 0,
                zIndex: 2,
                cursor: "crosshair"
              });
              wrapper.appendChild(drawCanvas);
              container.appendChild(wrapper);

              // Render PDF page
              page.render({ canvasContext: pdfCanvas.getContext("2d"), viewport });

              const ctx = drawCanvas.getContext("2d");
              let drawing = false;
              drawCanvas.addEventListener("mousedown", () => {
                drawing = true;
                ctx.beginPath();
              });
              drawCanvas.addEventListener("mouseup", () => drawing = false);
              drawCanvas.addEventListener("mouseout", () => drawing = false);
              drawCanvas.addEventListener("mousemove", e => {
                if (!drawing) return;
                const rect = drawCanvas.getBoundingClientRect();
                const x = e.clientX - rect.left;
                const y = e.clientY - rect.top;
                ctx.lineWidth = 2;
                ctx.lineCap = "round";
                ctx.strokeStyle = "#ff0000";
                ctx.lineTo(x, y);
                ctx.stroke();
                ctx.beginPath();
                ctx.moveTo(x, y);
              });

              // Restore previous annotations if they exist
              const savedData = annotationsStore[path]?.[pageNum];
              if (savedData) {
                const img = new Image();
                img.onload = () => ctx.drawImage(img, 0, 0);
                img.src = savedData;
              }

              canvasRefs[pageNum] = drawCanvas;
            });
          }
        }).catch(err => {
          console.error(err);
          container.innerHTML = "<p>Failed to load PDF.</p>";
        });
      } else {
        container.innerHTML = `<img src="${path}" style="max-width:100%;">`;
      }
    }

    let currentPDFPath = null;

    document.getElementById("submitBtn").addEventListener("click", async () => {
      const requestId = document.getElementById("request_id").value;

      for (const [path, pageCanvases] of Object.entries(annotationsStore)) {
        const existingPdfBytes = await fetch(path).then(res => res.arrayBuffer());
        const pdfDoc = await PDFLib.PDFDocument.load(existingPdfBytes);

        for (const [pageNumStr, dataUrl] of Object.entries(pageCanvases)) {
          const pngImageBytes = await fetch(dataUrl).then(res => res.arrayBuffer());
          const pngImage = await pdfDoc.embedPng(pngImageBytes);
          const page = pdfDoc.getPage(parseInt(pageNumStr) - 1);

          const { width, height } = page.getSize();
          page.drawImage(pngImage, {
            x: 0,
            y: 0,
            width: width,
            height: height
          });
        }

        const finalPdfBytes = await pdfDoc.save();
        const finalBlob = new Blob([finalPdfBytes], { type: "application/pdf" });

        const formData = new FormData();
        formData.append("request_id", requestId);
        formData.append("role", "OSA");
        formData.append("original_path", path);
        formData.append("final_pdf", finalBlob, path.split('/').pop());

        await fetch("submit_drawn_documents.php", {
          method: "POST",
          body: formData
        });
      }

      alert("All documents submitted!");
      loadDocs();
      document.getElementById("docPreview").innerHTML = "";
    });


    window.onload = loadDocs;
    async function submitDrawnDocuments() {
      const requestId = document.getElementById("request_id").value;
      const formData = new FormData();
      formData.append("request_id", requestId);
      formData.append("role", "OSA");

      if (!currentPDFPath || Object.keys(canvasRefs).length === 0) {
        alert("No annotations to save.");
        return;
      }

      const existingPdfBytes = await fetch(currentPDFPath).then(res => res.arrayBuffer());
      const pdfDoc = await PDFLib.PDFDocument.load(existingPdfBytes);

      for (const [pageNumStr, canvas] of Object.entries(canvasRefs)) {
        const pageNum = parseInt(pageNumStr) - 1;
        const pngDataUrl = canvas.toDataURL("image/png");
        const pngImageBytes = await fetch(pngDataUrl).then(res => res.arrayBuffer());
        const pngImage = await pdfDoc.embedPng(pngImageBytes);

        const page = pdfDoc.getPage(pageNum);
        const { width, height } = page.getSize();
        const pngDims = pngImage.scale(1);

        page.drawImage(pngImage, {
          x: 0,
          y: 0,
          width: pngDims.width,
          height: pngDims.height,
        });
      }

      const finalPdfBytes = await pdfDoc.save();

      // Convert PDF to Blob and send to server
      const finalBlob = new Blob([finalPdfBytes], { type: "application/pdf" });
      formData.append("final_pdf", finalBlob, "annotated.pdf");

      fetch("submit_drawn_documents.php", {
        method: "POST",
        body: formData
      })
        .then(res => res.text())
        .then(msg => {
          alert(msg);
          loadDocs(); // Refresh list
          document.getElementById("docPreview").innerHTML = "";
        })
        .catch(err => {
          console.error("Upload failed", err);
          alert("Failed to submit annotated document.");
        });
    }

    document.getElementById("rejectBtn").addEventListener("click", function() {
  if (!confirm("Are you sure you want to reject and reset all submitted documents for this request?")) return;
  const requestId = document.getElementById("request_id").value;
  fetch("reject_documents.php", {
    method: "POST",
    body: new URLSearchParams({ request_id: requestId })
  })
    .then(res => res.text())
    .then(msg => {
      alert(msg);
      loadDocs();
      document.getElementById("docPreview").innerHTML = "";
    })
    .catch(err => {
      alert("Failed to reject documents.");
      console.error(err);
    });
});

  </script>

  <script>
    document.addEventListener("DOMContentLoaded", () => {
    const hasSignature = <?php echo (!empty($osa['signature'])) ? 'true' : 'false'; ?>; 
    const modal = document.getElementById('signatureModal');
    const canvas = document.getElementById('signaturePad');
    const ctx = canvas.getContext('2d');
    const clearBtn = document.getElementById('clearBtn');
    const saveBtn = document.getElementById('saveSignatureBtn');
    const uploadInput = document.getElementById('uploadSignature');
    let drawing = false;

    if (!hasSignature) modal.style.display = 'flex';

    // Drawing events
    canvas.addEventListener('mousedown', () => drawing = true);
    canvas.addEventListener('mouseup', () => drawing = false);
    canvas.addEventListener('mouseleave', () => drawing = false);
    canvas.addEventListener('mousemove', draw);

    function draw(e) {
      if (!drawing) return;
      ctx.lineWidth = 2;
      ctx.lineCap = 'round';
      ctx.strokeStyle = 'black';
      ctx.lineTo(e.offsetX, e.offsetY);
      ctx.stroke();
      ctx.beginPath();
      ctx.moveTo(e.offsetX, e.offsetY);
    }

    clearBtn.addEventListener('click', () => {
      ctx.clearRect(0, 0, canvas.width, canvas.height);
    });

    saveBtn.addEventListener('click', () => {
      const signatureData = canvas.toDataURL("image/png");
      saveSignature(signatureData);
    });

    uploadInput.addEventListener('change', function() {
      const file = this.files[0];
      if (file) {
        const reader = new FileReader();
        reader.onload = function(e) {
          saveSignature(e.target.result);
        };
        reader.readAsDataURL(file);
      }
    });

    function saveSignature(dataURL) {
      fetch('/officerDashboardCopy/create_event/save_signature.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: new URLSearchParams({ signature: dataURL })
      })
      .then(res => res.text())
      .then(response => {
        alert(response);
        modal.style.display = 'none';
        location.reload();
      })
      .catch(err => alert("Error saving signature: " + err));
    }
  });
  </script>
</body>
</html>
