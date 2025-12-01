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
?>

<!DOCTYPE html>
<html>
<head>
    <title>Approvals</title>
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Roboto:wght@400;700&display=swap">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@100..900&family=Roboto:ital,wght@0,100;0,300;0,400;0,500;0,700;0,900&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="approval.css">
</head>
<body>
    <nav>
        <div class="nav-left">
          <img src="greetings/umdc-logo.png" alt="Logo" class="logo">
        </div>
        <div class="nav-center">
          <a href="Osa.html">Departments</a>
          <a href="approvals.php">Approvals</a>
          <a href="#about" onclick="about()">About</a>
        </div>
    </nav>

    <h1 class="orgname">Pending Posts</h1>
    <h3 class="text">Approve post submitted by the organization officers.</h3>
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
                    <button name="action" value="approve">Approve</button>
                    <button name="action" value="reject">Reject</button>
                </form>
            </td>
        </tr>
        <?php } ?>
    </table>
</body>
</html>

<?php
$conn->close();
?>
