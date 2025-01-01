<?php
ini_set('session.gc_maxlifetime', 3600); // 1 hour
session_set_cookie_params(3600); // Cookie lasts 1 hour

session_start();
require 'db.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $type = $_POST['type'];
    $userId = $_SESSION['id'];

    if (!in_array($type, ['individual', 'grouping', 'guidance'])) {
        die('Invalid report type.');
    }

    $uploadedFile = $_FILES['uploaded_file']['name'];
    $targetDir = "uploads/";
    $targetFile = $targetDir . basename($uploadedFile);

    // Insert into Report table to generate reportId
    $reportStmt = $conn->prepare("INSERT INTO Report (userId) VALUES (?)");
    $reportStmt->bind_param("i", $userId);
    $reportStmt->execute();
    $reportId = $conn->insert_id;

    // Move uploaded file
    if (!move_uploaded_file($_FILES['uploaded_file']['tmp_name'], $targetFile)) {
        die('File upload failed.');
    }

    // Insert data based on type
    if ($type === 'individual') {
        $stmt = $conn->prepare("INSERT INTO Individual (reportId, referenceNo, session, hoursSubmitted, uploadedFile, status) VALUES (?, ?, ?, ?, ?, 'Pending')");
    } elseif ($type === 'grouping') {
        $stmt = $conn->prepare("INSERT INTO Grouping (reportId, referenceNo, session, hoursSubmitted, uploadedFile, status) VALUES (?, ?, ?, ?, ?, 'Pending')");
    } elseif ($type === 'guidance') {
        $stmt = $conn->prepare("INSERT INTO Guidance (reportId, referenceNo, activityProgram, documentationReflection, hoursSubmitted, uploadedFile, status) VALUES (?, ?, ?, ?, ?, ?, 'Pending')");
    }

    // Bind parameters
    $referenceNo = $_POST['reference_no'];
    $sessionOrActivity = $_POST['session'] ?? $_POST['activity_program']; // Activity for guidance
    $hoursSubmitted = $_POST['hours_submitted'];

    if ($type === 'guidance') {
        $documentationReflection = $_POST['documentation_reflection'];
        $stmt->bind_param(
            "isssss",
            $reportId,
            $referenceNo,
            $sessionOrActivity,
            $documentationReflection,
            $hoursSubmitted,
            $uploadedFile
        );
    } else {
        $stmt->bind_param(
            "issss",
            $reportId,
            $referenceNo,
            $sessionOrActivity,
            $hoursSubmitted,
            $uploadedFile
        );
    }

    if ($stmt->execute()) {
        header("Location: " . $type . ".php");
        exit();
    } else {
        die('Error submitting report.');
    }
}
?>
