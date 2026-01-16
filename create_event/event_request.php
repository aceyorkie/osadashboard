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

$vp_query = "SELECT id_no, name FROM vp LIMIT 1";
$vp_result = $osaDb->query($vp_query);
$vp = $vp_result->fetch_assoc();

$event_id = isset($_GET['event_id']) ? intval($_GET['event_id']) : 0;

if ($event_id <= 0) {
    die("Invalid access: missing or invalid event_id");
}

$practice = new mysqli("localhost", "root", "", "practice_db");
if ($practice->connect_error) {
    die("Practice DB connection failed: " . $practice->connect_error);
}

$getRequestId = $practice->prepare("SELECT request_id FROM event_signature_flow WHERE event_id = ? LIMIT 1");
$getRequestId->bind_param("i", $event_id);
$getRequestId->execute();
$getRequestId->bind_result($request_id);
$getRequestId->fetch();
$getRequestId->close();

if (!$request_id) {
    die("Invalid access: missing request_id for event_id " . htmlspecialchars($event_id));
}

$presQuery = $practice->prepare(" SELECT signatory_id, status FROM event_signature_flow WHERE event_id = ? AND role = 'President' LIMIT 1");
$presQuery->bind_param("i", $event_id);
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

$advQuery = $practice->prepare(" SELECT signatory_id, status FROM event_signature_flow WHERE event_id = ? AND role = 'Adviser' LIMIT 1");
$advQuery->bind_param("i", $event_id);
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

$phQuery = $practice->prepare(" SELECT signatory_id, status FROM event_signature_flow WHERE event_id = ? AND role = 'Program Head' LIMIT 1");
$phQuery->bind_param("i", $event_id);
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
  $getPh = $org2->prepare("SELECT name, signature FROM adviser WHERE id_no = ?");
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

$deanQuery = $practice->prepare(" SELECT signatory_id, status FROM event_signature_flow WHERE event_id = ? AND role = 'Dean' LIMIT 1");
$deanQuery->bind_param("i", $event_id);
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

$signatoryDb = new mysqli('localhost', 'root', '', 'practice_db');
if ($signatoryDb->connect_error) {
    die("Connection failed (practice_db): " . $signatoryDb->connect_error);
}

$docDetailsSql = "
  SELECT request_id, doc_type, event_type, event_name, file_path
  FROM event_document_files 
  WHERE request_id = ? AND event_id = ?
";
$docStmt = $signatoryDb->prepare($docDetailsSql);
$docStmt->bind_param("ii", $request_id, $event_id);
$docStmt->execute();
$docDetailsResult = $docStmt->get_result();

$eventDocs = [];
$organizationType = null;
if ($docDetailsResult && $docDetailsResult->num_rows > 0) {
    while ($row = $docDetailsResult->fetch_assoc()) {
        $eventDocs[] = $row;
        $organizationType = $row['event_type'];
    }
}
$docStmt->close();

$eventDetails = null;

if (!empty($organizationType)) {
  $orgDb = new mysqli('localhost', 'root', '', 'orgportal');
  if ($orgDb->connect_error) {
      die("Connection failed (orgportal): " . $orgDb->connect_error);
  }

  if ($organizationType === 'Institutional') {
      $eventSql = "SELECT * FROM institutional_events WHERE request_id = ? AND event_id = ? LIMIT 1";
  } elseif ($organizationType === 'Organizational') {
      $eventSql = "SELECT * FROM organizational_events WHERE request_id = ? AND event_id = ? LIMIT 1";
  }

  if (isset($eventSql)) {
      $eventStmt = $orgDb->prepare($eventSql);
      $eventStmt->bind_param("ii", $request_id, $event_id);
      $eventStmt->execute();
      $eventResult = $eventStmt->get_result();
      if ($eventResult && $eventResult->num_rows > 0) {
          $eventDetails = $eventResult->fetch_assoc();
      }
      $eventStmt->close();
  }
  $orgDb->close();
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Signatory Dashboard</title>
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

  .toast {
    position: fixed;
    top: 20px;
    right: 20px;
    background: #2ecc71;
    color: #fff;
    padding: 14px 20px;
    border-radius: 8px;
    font-family: Poppins, sans-serif;
    font-size: 14px;
    box-shadow: 0 10px 25px rgba(0,0,0,0.15);
    opacity: 0;
    transform: translateY(-10px);
    transition: all 0.3s ease;
    z-index: 9999;
  }

  .toast.show {
    opacity: 1;
    transform: translateY(0);
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
    <h2 class="title-request">Event Request — <?php echo htmlspecialchars($eventDetails['event_name'] ?: 'Preview'); ?></h2>

    <div class="Documents-box">
      <input type="hidden" id="request_id" value="<?php echo htmlspecialchars($request_id); ?>">
      <input type="hidden" id="event_id" value="<?php echo htmlspecialchars($event_id); ?>">
      <input type="hidden" name="role" value="OSA">
      <input type="hidden" id="current_role" value="OSA">

      <form method="POST" action="submit_event_drawn_documents.php" enctype="multipart/form-data" id="adviserForm">
        <input type="hidden" name="request_id" id="form_request_id" value="<?php echo htmlspecialchars($request_id); ?>">
        <input type="hidden" name="event_id" value="<?php echo htmlspecialchars($event_id); ?>">
        <input type="hidden" name="role" value="OSA">
        <div class="org-details-flex">
          <div class="org-fields">
            <input type="text" name="event_name" placeholder="Event Name" value="<?php echo htmlspecialchars(strtoupper($eventDetails['event_name'])); ?>" readonly>
            <input type="text" name="event_location" placeholder="Event Location" value="<?php echo htmlspecialchars(strtoupper($eventDetails['event_location'])); ?>" readonly>
            <input type="time" name="event_time" placeholder="Event Time" value="<?php echo htmlspecialchars(strtoupper($eventDetails['event_time'])); ?>" readonly>
            <input type="date" name="event_date" placeholder="Event Date" value="<?php echo htmlspecialchars(strtoupper($eventDetails['event_date'])); ?>" readonly>
            <input type="text" name="event_course" placeholder="Course/Department" value="<?php echo htmlspecialchars(strtoupper($eventDetails['event_course'])); ?>" readonly>
            <input type="text" name="organization_type" placeholder="Organization Type" value="<?php echo strtoupper($organizationType ?? ''); ?>" readonly>
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
                    <span>Pending</span>
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
                    <span>Pending</span>
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
              <div class="signature-placeholder" id="programhead-signature-box" onclick="<?= $programheadSigned ? '' : "triggerFileInput('programhead')" ?>">
                <?php if ($programheadSigned && !empty($programheadSignedSignature)): ?>
                  <!-- SHOW SIGNED ADVISER SIGNATURE -->
                  <img src="<?= htmlspecialchars($programheadSignedSignature) ?>" style="width:100px; height:auto; display:block; margin:auto;">
              
                  <?php elseif (!empty($programheadSignature)): ?>
                  <img src="<?= htmlspecialchars($programheadSignature) ?>" style="width:100px; height:auto; display:block; margin:auto;">

                  <?php else: ?>
                    <span>Pending</span>
                  <?php endif; ?>
                  <input type="file" id="programhead_file" accept="image/*" style="display:none"onchange="previewSignature(this, 'programhead')" <?= $programheadSigned ? 'disabled' : '' ?>>
              </div>

              <div class="sign-info" style="width:100%;">
                <input list="programheadList" id="programhead_name" name="programhead_name" placeholder="Select Program Head"autocomplete="off" value="<?= $programheadSigned ? htmlspecialchars($programheadSignedName) : htmlspecialchars($programhead['name'] ?? '') ?>" readonly>
                <input type="hidden" id="programhead_id" name="programhead_id" value="<?= $programheadSigned ? htmlspecialchars($programheadSignedID) : htmlspecialchars($programhead['id_no'] ?? '') ?>">
                <datalist id="programheadList"></datalist>
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
                    <span>Pending</span>
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
            <!-- osa -->
            <div class="signatory-card">
              <div class="signature-placeholder" id="osa-signature-box" onclick="triggerFileInput('osa')">
                <?php if (!empty($osa['signature'])): ?>
                  <img src="<?php echo htmlspecialchars($osa['signature']); ?>" alt="Signature" style="width:100px; height:auto; display:block; margin:auto;">
                <?php else: ?>
                  <span>Pending</span>
                <?php endif; ?>
                <input type="file" id="osa_file" accept="image/*" style="display:none" onchange="previewSignature(this, 'osa')">
              </div>
              <div class="sign-info" style="width:100%;">
                <input type="text" 
                      id="osa_name" 
                      name="osa_name" 
                      placeholder="Select Osa" 
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
            <!-- vp -->
            <div class="signatory-card">
              <div class="signature-placeholder" onclick="triggerFileInput('vp')">
                <span>Pending</span>
                <input type="file" id="vp_file" accept="image/*" style="display:none" onchange="previewSignature(this, 'vp')">
              </div>
              <div class="sign-info" style="width:100%;">
                <input  type="text" id="vp_name" name="vp_name" value="<?php echo htmlspecialchars($vp['name']); ?>" readonly>
                <input type="hidden" id="vp_id" name="vp_id" value="<?php echo htmlspecialchars($vp['id_no']); ?>">
                <datalist id="vpList"></datalist>
                <div class="role">Branch Director</div>
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

  <div id="rejectModal" class="reject-modal">
    <div class="reject-modal-content" style="max-width:500px;">
      <h2>Reject Event Documents</h2>
      <p>Please provide comments or suggestions for revision.</p>

      <textarea
        id="rejectComment"
        rows="6"
        placeholder="Enter comments and suggestions..."
        style="width:100%; padding:10px; font-family:Poppins;"
      ></textarea>

      <div style="margin-top:15px; text-align:right;">
        <button type="button" onclick="closeRejectModal()">Cancel</button>
        <button type="button" onclick="submitRejection()" style="background:#e74c3c;color:#fff;">
          Submit Rejection
        </button>
      </div>
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

  <div id="successToast" class="toast">
    <span id="toastMessage"></span>
  </div>

  <script>
    let canvasRefs = {};  // pageNum => canvas
    let annotationsStore = {}; // path => { pageNum: imageDataURL }

    function loadDocs() {
      const reqId = document.getElementById("request_id").value;
      const eventId = document.getElementById("event_id").value;
      const signatoryId = "<?php echo htmlspecialchars($osa['id_no']); ?>";

      console.log("Loading docs with:", { reqId, eventId, signatoryId });

      fetch("view_events_documents.php?request_id=" + reqId + "&event_id=" + eventId + "&signatory_id=" + signatoryId)
      .then(res => {
        console.log("Response status:", res.status); // ✅ DEBUG
        return res.json();
      })
      .then(data => {
        console.log("Documents fetched:", data); // ✅ DEBUG
        const list = document.getElementById("docList");
        if (!data || data.length === 0) {
          list.innerHTML = "<p>No documents found.</p>";
          return;
        }
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
        console.error("Fetch error:", err); // ✅ DEBUG
        alert("Failed to load documents: " + err.message);
      });
    }

    function previewDoc(path) {
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
                ctx.strokeStyle = "#000000ff";
                ctx.lineTo(x, y);
                ctx.stroke();
                ctx.beginPath();
                ctx.moveTo(x, y);
              });

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
      const eventId = document.getElementById("event_id").value;

      console.log("Submit clicked with:", { requestId, eventId }); // ✅ DEBUG

      if (!annotationsStore || Object.keys(annotationsStore).length === 0) {
        alert("No documents to submit.");
        return;
      }

      for (const [path, pageCanvases] of Object.entries(annotationsStore)) {
        try {
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
          formData.append("event_id", eventId);
          formData.append("role", "OSA");
          formData.append("original_path", path);
          formData.append("final_pdf", finalBlob, path.split('/').pop());

          console.log("Sending FormData with:", { requestId, eventId, role: "OSA" });

          const response = await fetch("submit_event_drawn_documents.php", {
            method: "POST",
            body: formData
          });

          const result = await response.json();
            console.log("Submission result:", result); // ✅ DEBUG
            
            if (result.status !== 'success') {
              alert("Error: " + result.message);
              return;
            }
          } catch (err) {
            console.error("Error:", err);
            alert("Submission failed: " + err.message);
          }
      }
      showSuccessToast(`Head OSA approval completed successfully.`);
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

      fetch("submit_event_drawn_documents.php", {
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

    document.getElementById("rejectBtn").addEventListener("click", () => {
      document.getElementById("rejectModal").style.display = "flex";
    });

    function closeRejectModal() {
      document.getElementById("rejectModal").style.display = "none";
      document.getElementById("rejectComment").value = "";
    }

    function submitRejection() {
      const comment = document.getElementById("rejectComment").value.trim();
      const requestId = document.getElementById("request_id").value;
      const eventId = document.getElementById("event_id").value;
      const role = document.getElementById("current_role").value;

      if (!comment) {
        alert("Please enter comments or suggestions.");
        return;
      }

      fetch("/officerDashboardCopy/create_event/reject_events_documents.php", {
        method: "POST",
        headers: { "Content-Type": "application/x-www-form-urlencoded" },
        body: new URLSearchParams({
          request_id: requestId,
          event_id: eventId,
          role: role,
          comment: comment
        })
      })
      .then(res => res.json())
      .then(result => {
        if (result.status === "success") {
          showSuccessToast("Documents rejected successfully.");
          closeRejectModal();
          loadDocs();
          document.getElementById("docPreview").innerHTML = "";
        } else {
          alert(result.message || "Rejection failed.");
        }
      })
      .catch(err => {
        console.error(err);
        alert("Failed to submit rejection.");
      });
    }

    function showSuccessToast(message) {
      const toast = document.getElementById("successToast");
      const text = document.getElementById("toastMessage");

      text.textContent = message;
      toast.classList.add("show");

      setTimeout(() => {
        toast.classList.remove("show");
      }, 3000);
    }
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

<?php $signatoryDb->close(); ?>
