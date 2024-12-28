<?php
// db.php
$host = "localhost";
$username = "root";
$password = "Adnan@66202";
$database = "classcloud";

$conn = new mysqli($host, $username, $password, $database);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}
?>
