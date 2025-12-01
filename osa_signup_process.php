<?php
session_start();

error_reporting(E_ALL);
ini_set('display_errors', 1);

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
    $name = $_POST['name'];
    $password = password_hash($_POST['password'], PASSWORD_DEFAULT);

    $check_query = $conn->prepare("SELECT id_no FROM osa WHERE id_no=?");
    $check_query->bind_param("s", $id_no);
    $check_query->execute();
    $result = $check_query->get_result();

    if ($result->num_rows > 0) {
        echo "ID No. already exists.";
    } else {
        $sql = $conn->prepare("INSERT INTO osa (id_no, name, password) VALUES (?, ?, ?)");
        $sql->bind_param("sss", $id_no, $name, $password);

        if ($sql->execute()) {
            $_SESSION['id_no'] = $id_no;
            $_SESSION['name'] = $name;
            header("Location: Osa.php");
            exit();
        } else {
            echo "Error: " . $conn->error;
        }
    }
}

$conn->close();
?>
