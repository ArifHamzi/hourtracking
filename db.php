<?php
$servername = "localhost";
$username = "arif"; // Replace with your DB username
$password = "arif123"; // Replace with your DB password
$dbname = "hourtracking";

$conn = new mysqli($servername, $username, $password, $dbname);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}
?>
