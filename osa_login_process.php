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
    $id_no = $_POST['id_no'];
    $password = $_POST['password'];

    $sql = "SELECT * FROM osa WHERE id_no='$id_no'";
    $result = $conn->query($sql);

    if ($result->num_rows == 1) {
        $row = $result->fetch_assoc();

        if (password_verify($password, $row['password'])) {
            $_SESSION['id_no'] = $row['id_no'];

            header("Location: Osa.php");
            exit();
        } else {
            echo "Invalid password.";
        }
    } else {
        echo "ID No. not found.";
    }
}

$conn->close();
?>
