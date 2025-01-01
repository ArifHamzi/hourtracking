<?php

ini_set('session.gc_maxlifetime', 3600); // 1 hour
session_set_cookie_params(3600); // Cookie lasts 1 hour

session_start();
require 'db.php';

if ($_SESSION['role'] !== 'supervisee') {
    header("Location: login.php");
    exit();
}

$userId = $_SESSION['id'];

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $referenceNo = $_POST['referenceNo'] ?? '';
    $activityProgram = $_POST['activityProgram'] ?? '';
    $documentationReflection = $_POST['documentationReflection'] ?? '';
    $hoursSubmitted = $_POST['hoursSubmitted'] ?? 0;
    $uploadedFile = '';

    // Handle file upload
    if (isset($_FILES['uploadedFile']) && $_FILES['uploadedFile']['error'] === UPLOAD_ERR_OK) {
        $uploadDir = 'uploads/';
        $uploadedFile = basename($_FILES['uploadedFile']['name']);
        $uploadPath = $uploadDir . $uploadedFile;

        if (!move_uploaded_file($_FILES['uploadedFile']['tmp_name'], $uploadPath)) {
            $uploadedFile = ''; // Reset if upload fails
        }
    }

    // Insert report into the database
    $reportId = null; // Create a new report first
    $reportQuery = $conn->prepare("INSERT INTO Report (userId) VALUES (?)");
    $reportQuery->bind_param("i", $userId);
    $reportQuery->execute();
    $reportId = $conn->insert_id;

    $query = $conn->prepare("
        INSERT INTO Guidance (reportId, referenceNo, activityProgram, documentationReflection, hoursSubmitted, uploadedFile)
        VALUES (?, ?, ?, ?, ?, ?)
    ");
    $query->bind_param("isssis", $reportId, $referenceNo, $activityProgram, $documentationReflection, $hoursSubmitted, $uploadedFile);
    $query->execute();
}

// Fetch existing reports
$query = $conn->prepare("
    SELECT G.referenceNo, G.activityProgram, G.documentationReflection, G.hoursSubmitted, G.uploadedFile, G.status
    FROM Report R
    JOIN Guidance G ON R.id = G.reportId
    WHERE R.userId = ?
");
$query->bind_param("i", $userId);
$query->execute();
$result = $query->get_result();
$reports = $result->fetch_all(MYSQLI_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <title>Guidance Reports</title>
    <link rel="stylesheet" href="styles.css">
</head>
<body>
    <div class="toolbar">
        <p>Welcome <?= htmlspecialchars($_SESSION['fullname']) ?> (Supervisee)</p>
        <a href="logout.php" class="btn">Logout</a>
    </div>
    <div class="sidebar">
        <a href="supervisee_dashboard.php">Dashboard</a>
        <a href="individual.php">Individual</a>
        <a href="group.php">Grouping</a>
        <a href="guidance.php" class="active">Guidance</a>
    </div>
    <div class="content">
        <h1>Guidance Reports</h1>

        <a href="add_report.php?type=guidance" class="btn add-report">Add Report</a>


        <table>
            <tr>
                <th>Reference No</th>
                <th>Activity/Program</th>
                <th>Documentation/Reflection</th>
                <th>Hours Submitted</th>
                <th>Uploaded File</th>
                <th>Status</th>
                <th>Action</th>
            </tr>
            <?php foreach ($reports as $report): ?>
                <tr>
                    <td><?= htmlspecialchars($report['referenceNo']) ?></td>
                    <td><?= htmlspecialchars($report['activityProgram']) ?></td>
                    <td><?= htmlspecialchars($report['documentationReflection']) ?></td>
                    <td><?= htmlspecialchars($report['hoursSubmitted']) ?></td>
                    <td>
                        <?php if (!empty($report['uploadedFile'])): ?>
                            <a href="uploads/<?= htmlspecialchars($report['uploadedFile']) ?>" target="_blank">View File</a>
                        <?php endif; ?>
                    </td>
                    <td><?= htmlspecialchars($report['status']) ?></td>
                    <td>
                        <form method="post" action="delete_report.php">
                            <input type="hidden" name="reportId" value="<?= $report['id'] ?>">
                            <button type="submit" class="delete-btn">Delete</button>
                        </form>
                    </td>
                </tr>
            <?php endforeach; ?>
        </table>
    </div>
</body>
</html>
