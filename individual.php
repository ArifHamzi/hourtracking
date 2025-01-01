<?php
if (isset($_GET['msg'])) {
    echo "<p style='color: green;'>{$_GET['msg']}</p>";
}
?>

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

// Handle form submission for adding reports
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $referenceNo = $_POST['referenceNo'] ?? '';
    $session = $_POST['session'] ?? '';
    $hoursSubmitted = $_POST['hoursSubmitted'] ?? 0;
    $uploadedFile = '';

    // Handle file upload
    if (isset($_FILES['uploadedFile']) && $_FILES['uploadedFile']['error'] === UPLOAD_ERR_OK) {
        $uploadDir = 'uploads/';
        $uploadedFile = basename($_FILES['uploadedFile']['name']);
        $uploadPath = $uploadDir . $uploadedFile;

        if (!move_uploaded_file($_FILES['uploadedFile']['tmp_name'], $uploadPath)) {
            $uploadedFile = ''; // Reset if the upload fails
        }
    }

    // Insert report into the database
    $query = $conn->prepare("
        INSERT INTO Individual (reportId, referenceNo, session, hoursSubmitted, uploadedFile)
        VALUES (?, ?, ?, ?, ?, 'Pending)
    ");
    $reportId = null; // Create a new report first
    $reportQuery = $conn->prepare("INSERT INTO Report (userId) VALUES (?)");
    $reportQuery->bind_param("i", $userId);
    $reportQuery->execute();
    $reportId = $conn->insert_id;

    $query->bind_param("issis", $reportId, $referenceNo, $session, $hoursSubmitted, $uploadedFile);
    $query->execute();
}

// Fetch existing reports
$query = $conn->prepare("
    SELECT I.referenceNo, I.session, I.hoursSubmitted, I.uploadedFile, I.status, I.reportId
    FROM Report R
    JOIN Individual I ON R.id = I.reportId
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
    <title>Individual Reports</title>
    <link rel="stylesheet" href="styles.css">

    <script>
        function deleteReport(reportId) {
            if (confirm("Are you sure you want to delete this report?")) {
                window.location.href = `delete_report.php?type=individual&id=${reportId}`;
            }
        }
    </script>
</head>
<body>
    <div class="toolbar">
        <p>Welcome <?= htmlspecialchars($_SESSION['fullname']) ?> (Supervisee)</p>
        <a href="logout.php" class="btn">Logout</a>
    </div>
    <div class="sidebar">
                <!-- Add the logo -->
        <div class="logo-container">
            <img src="img/logo.jpg" alt="Logo" class="sidebar-logo">
        </div>
        <a href="supervisee_dashboard.php">Dashboard</a>
        <a href="individual.php" class="active">Individual</a>
        <a href="group.php">Grouping</a>
        <a href="guidance.php">Guidance</a>
    </div>
    <div class="content">
        <h1>Individual Reports</h1>
        <a href="add_report.php?type=individual" class="btn add-report">Add Report</a>
        <table>
            <tr>
                <th>Reference No</th>
                <th>Session</th>
                <th>Hours Submitted</th>
                <th>Uploaded File</th>
                <th>Status</th>
                <th>Action</th> <!-- Add a "Delete" column here -->
            </tr>
            <?php foreach ($reports as $report): ?>
                <tr>
                    <td><?= htmlspecialchars($report['referenceNo']) ?></td>
                    <td><?= htmlspecialchars($report['session']) ?></td>
                    <td><?= htmlspecialchars($report['hoursSubmitted']) ?></td>
                    <td style="display: flex; justify-content: space-between; align-items: center;">
                        <?php if (!empty($report['uploadedFile'])): ?>
                            <input type="text" value="<?= htmlspecialchars($report['uploadedFile']) ?>" readonly style="border: none; background: transparent; color: inherit; width: 70%; padding: 5px;">
                            <a href="uploads/<?= htmlspecialchars($report['uploadedFile']) ?>" target="_blank" class="btn view-btn">View File</a>
                        <?php endif; ?>
                    </td>
                    <span class="<?= $report['status'] === 'Pending' ? 'status-pending' : '' ?>">
                    <td><?= htmlspecialchars($report['status']) ?></td>
                    <td>
                    <button class="delete-btn" onclick="deleteReport(<?= $report['reportId'] ?>)">Delete</button>
                    </td>
                </tr>
            <?php endforeach; ?>
        </table>
    </div>
</body>
</html>
