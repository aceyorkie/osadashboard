<?php
session_start();

$host = 'localhost';
$user = 'root';
$password = '';
$dbname = 'orgportal';

$conn = new mysqli($host, $user, $password, $dbname);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $password = $_POST['password'];

    $sql = "SELECT * FROM OSA WHERE password='$password'";
    $result = $conn->query($sql);

    if ($result->num_rows == 1) {
        header("Location: Osa.html");
    } else {
        echo "Invalid password.";
    }
}
$conn->close();
?>
