<?php
ini_set('session.gc_maxlifetime', 3600); // 1 hour
session_set_cookie_params(3600); // Cookie lasts 1 hour

session_start();
require 'db.php';

if ($_SESSION['role'] !== 'supervisor') {
    header("Location: login.php");
    exit();
}

$superviseeId = $_GET['superviseeId'] ?? null;

if (!$superviseeId) {
    echo "Supervisee not selected.";
    exit();
}

// Fetch Group reports
$query = $conn->prepare("
    SELECT G.id, G.referenceNo, G.session, G.hoursSubmitted, G.uploadedFile, G.status
    FROM Report R
    JOIN Grouping G ON R.id = G.reportId
    WHERE R.userId = ?
");
$query->bind_param("i", $superviseeId);
$query->execute();
$result = $query->get_result();

$reports = $result->fetch_all(MYSQLI_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <title>Group Reports</title>
    <link rel="stylesheet" href="styles.css">
</head>
<body>
    <div class="toolbar">
        <p>Welcome <?= $_SESSION['fullname'] ?> (Supervisor)</p>
        <a href="logout.php" class="btn">Logout</a>
    </div>
    <?php include 'sidebar.php'; ?>
    <div class="content">
        <h1>Group Counselling Reports</h1>
        <table>
            <tr>
                <th>Reference No</th>
                <th>Session</th>
                <th>Hours Submitted</th>
                <th>Uploaded File</th>
                <th>Status</th>
                <th>Action</th>
            </tr>
            <?php foreach ($reports as $report): ?>
                <tr>
                    <td><?= $report['referenceNo'] ?></td>
                    <td><?= $report['session'] ?></td>
                    <td><?= $report['hoursSubmitted'] ?></td>
                    <td>
                        <?php if ($report['uploadedFile']): ?>
                            <a href="uploads/<?= $report['uploadedFile'] ?>" target="_blank">View File</a>
                        <?php endif; ?>
                    </td>
                    <td><?= $report['status'] ?></td>
                    <td>
                        <a href="process_action.php?action=approve&reportId=<?= $report['id'] ?>" class="btn approve-btn">Approve</a>
                        <a href="process_action.php?action=reject&reportId=<?= $report['id'] ?>" class="btn reject-btn">Reject</a>
                    </td>
                </tr>
            <?php endforeach; ?>
        </table>
    </div>
</body>
</html>
