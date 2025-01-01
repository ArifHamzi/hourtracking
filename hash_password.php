<?php
require 'db.php';

// Hash passwords in User table
$query = "SELECT id, password FROM User";
$result = $conn->query($query);

while ($row = $result->fetch_assoc()) {
    $hashedPassword = password_hash($row['password'], PASSWORD_DEFAULT);
    $updateQuery = $conn->prepare("UPDATE User SET password = ? WHERE id = ?");
    $updateQuery->bind_param("si", $hashedPassword, $row['id']);
    $updateQuery->execute();
}

// Hash passwords in Supervisor table
$query = "SELECT id, password FROM Supervisor";
$result = $conn->query($query);

while ($row = $result->fetch_assoc()) {
    $hashedPassword = password_hash($row['password'], PASSWORD_DEFAULT);
    $updateQuery = $conn->prepare("UPDATE Supervisor SET password = ? WHERE id = ?");
    $updateQuery->bind_param("si", $hashedPassword, $row['id']);
    $updateQuery->execute();
}

echo "Passwords hashed successfully!";
?>
