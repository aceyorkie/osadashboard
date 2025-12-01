<?php
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = $_POST['id'];
    $action = $_POST['action'];

    $status = $action === 'approve' ? 'approved' : 'rejected';

    $host = 'localhost';
    $user = 'root';
    $password = '';
    $dbname = 'orgportal';
    $conn = new mysqli($host, $user, $password, $dbname);

    if ($conn->connect_error) {
        die("Database connection failed: " . $conn->connect_error);
    }

    $stmt = $conn->prepare("UPDATE posts SET status = ? WHERE id = ?");
    $stmt->bind_param("si", $status, $id);

    if ($stmt->execute()) {
        header('Location: approvals.php');
    } else {
        echo "Error updating post status.";
    }

    $stmt->close();
    $conn->close();
}
?>
