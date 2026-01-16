<?php

$host = 'localhost';
$user = 'root';
$password = '';
$dbname = 'orgportal';
$conn = new mysqli($host, $user, $password, $dbname);

if ($conn->connect_error) {
    die("Database connection failed: " . $conn->connect_error);
}

$query = "SELECT * FROM posts WHERE status = 'pending'";
$result = $conn->query($query);

$limit = 6;
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$offset = ($page - 1) * $limit;

$totalResult = $conn->query("SELECT COUNT(*) AS total FROM posts WHERE status='pending'");
$totalRows = $totalResult->fetch_assoc()['total'];
$totalPages = ceil($totalRows / $limit);

$query = "SELECT * FROM posts 
          WHERE status='pending'
          ORDER BY id DESC
          LIMIT $limit OFFSET $offset";
$result = $conn->query($query);

?>

<!DOCTYPE html>
<html>
<head>
    <title>Approvals</title>
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Roboto:wght@400;700&display=swap">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@100..900&family=Roboto:ital,wght@0,100;0,300;0,400;0,500;0,700;0,900&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.7.2/css/all.min.css"/>    
    <link rel="stylesheet" href="approval.css">
    <link rel="stylesheet" href="/userHomeCopy/UserHome/css/nav.css">    
</head>
<body>
    <header class="top-nav">
      <div class="top-nav-left">
        <img src="/osaDashboard/greetings/umdc-logo.png" alt="Logo" class="top-logo">
      </div>
    </header>
    <nav>
      <div class="nav-center">
        <a href="/osaDashboard/Osa.php"><i class="fa-solid fa-house"></i><span>Departments</span></a>
        <a href="approvals.php"><i class="fa-solid fa-archive"></i><span>Approvals</span></a>
        <a href="php/calendar.php"><i class="fa-solid fa-calendar-days"></i><span>Calendar</span></a>
      </div>
    </nav>

    <div class="main-content">
        <div class="page-container">
            <h1 class="orgname">Pending Posts</h1>
            <h3 class="text">Approve post submitted by the organization officers.</h3>
            <div class="search-box">
                <input type="text" id="searchInput" placeholder="Search title or organization...">
            </div>
            <table>
                <tr>
                    <th>Title</th>
                    <th>Content</th>
                    <th>Organization</th>
                    <th>Action</th>
                </tr>
                <?php while ($row = $result->fetch_assoc()) { ?>
                <tr>
                    <td><?= htmlspecialchars($row['title']) ?></td>
                    <td><?= htmlspecialchars($row['content']) ?></td>
                    <td><?= htmlspecialchars($row['organization']) ?></td>

                    <td>
                        <form method="POST" action="updatePostStatus.php">
                            <input type="hidden" name="id" value="<?= $row['id'] ?>">
                            <button type="button"
                                    onclick="openModal(this.form, 'approve')">
                                Approve
                            </button>

                            <button type="button"
                                    onclick="openModal(this.form, 'reject')">
                                Reject
                            </button>
                        </form>
                    </td>
                </tr>
                <?php } ?>
            </table>
            <div class="pagination">
                <?php for ($i = 1; $i <= $totalPages; $i++) { ?>
                    <a href="?page=<?= $i ?>"
                    class="<?= ($i == $page) ? 'active' : '' ?>">
                        <?= $i ?>
                    </a>
                <?php } ?>
            </div>
        </div>
        <!-- Confirmation Modal -->
        <div id="confirmModal" class="modal-overlay">
            <div class="modal-box">
                <h2 id="modalTitle">Confirm Action</h2>
                <p id="modalMessage">Are you sure?</p>

                <div class="modal-actions">
                    <button id="confirmBtn" class="btn-confirm">Confirm</button>
                    <button onclick="closeModal()" class="btn-cancel">Cancel</button>
                </div>
            </div>
        </div>
    </div>

    <script>
        let selectedForm = null;
        let selectedAction = null;

        function openModal(form, action) {
            selectedForm = form;
            selectedAction = action;

            document.getElementById('modalMessage').innerText =
                `Are you sure you want to ${action} this post?`;

            document.getElementById('confirmBtn').onclick = function () {
                const input = document.createElement('input');
                input.type = 'hidden';
                input.name = 'action';
                input.value = selectedAction;
                selectedForm.appendChild(input);
                selectedForm.submit();
            };

            document.getElementById('confirmModal').style.display = 'flex';
        }

        function closeModal() {
            document.getElementById('confirmModal').style.display = 'none';
        }
    </script>

    <script>
        document.getElementById('searchInput').addEventListener('keyup', function () {
            const value = this.value.toLowerCase();
            const rows = document.querySelectorAll('tbody tr');

            rows.forEach(row => {
                row.style.display = row.innerText.toLowerCase().includes(value)
                    ? ''
                    : 'none';
            });
        });
    </script>


</body>
</html>

<?php
$conn->close();
?>
