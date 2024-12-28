<?php
// db.php

$host = 'localhost'; // Database host
$user = 'root';      // Database username
$pass = 'Adnan@66202';          // Database password
$dbname = 'class_cloud'; // Database name

// Create connection
$conn = new mysqli($host, $user, $pass, $dbname);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}
?>
