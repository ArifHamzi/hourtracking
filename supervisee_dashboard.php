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

// Fetch Pending, Approved, and Rejected reports from Individual, Grouping, and Guidance tables
function fetchReportsByStatus($conn, $userId, $status) {
    $query = $conn->prepare("
        SELECT referenceNo 
        FROM (
            SELECT referenceNo FROM Individual WHERE status = ? AND reportId IN (SELECT id FROM Report WHERE userId = ?)
            UNION ALL
            SELECT referenceNo FROM Grouping WHERE status = ? AND reportId IN (SELECT id FROM Report WHERE userId = ?)
            UNION ALL
            SELECT referenceNo FROM Guidance WHERE status = ? AND reportId IN (SELECT id FROM Report WHERE userId = ?)
        ) AS ReportsByStatus
    ");
    $query->bind_param("sisisi", $status, $userId, $status, $userId, $status, $userId);
    $query->execute();
    $result = $query->get_result();
    return $result->fetch_all(MYSQLI_ASSOC);
}

$pendingReports = fetchReportsByStatus($conn, $userId, "Pending");
$approvedReports = fetchReportsByStatus($conn, $userId, "Approved");
$rejectedReports = fetchReportsByStatus($conn, $userId, "Rejected");
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <title>Supervisee Dashboard</title>
    <link rel="stylesheet" href="styles.css">
</head>
<body>
    <div class="toolbar">
        <p>Welcome <?= htmlspecialchars($_SESSION['fullname']) ?> (Supervisee)</p>
        <a href="logout.php" class="btn">Logout</a>
    </div>

    <div class="content">
        <h1>Supervisee Dashboard</h1>

        <div class="report-status-container">
            <!-- Pending Reports -->
            <div class="report-box">
                <h3>Pending Reports</h3>
                <div class="report-content">
                    <?php if (empty($pendingReports)): ?>
                        <p>No pending reports</p>
                    <?php else: ?>
                        <?php foreach ($pendingReports as $report): ?>
                            <p><?= htmlspecialchars($report['referenceNo']) ?></p>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Approved Reports -->
            <div class="report-box">
                <h3>Approved Reports</h3>
                <div class="report-content">
                    <?php if (empty($approvedReports)): ?>
                        <p>No approved reports</p>
                    <?php else: ?>
                        <?php foreach ($approvedReports as $report): ?>
                            <p><?= htmlspecialchars($report['referenceNo']) ?></p>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Rejected Reports -->
            <div class="report-box">
                <h3>Rejected Reports</h3>
                <div class="report-content">
                    <?php if (empty($rejectedReports)): ?>
                        <p>No rejected reports</p>
                    <?php else: ?>
                        <?php foreach ($rejectedReports as $report): ?>
                            <p><?= htmlspecialchars($report['referenceNo']) ?></p>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- Redirect Containers -->
        <div>
            <div class="dashboard-container" onclick="window.location.href='individual.php'">
                <h3>Individual Reports</h3>
                <p>View or manage your Individual reports.</p>
            </div>
            <div class="dashboard-container" onclick="window.location.href='group.php'">
                <h3>Grouping Reports</h3>
                <p>View or manage your Grouping reports.</p>
            </div>
            <div class="dashboard-container" onclick="window.location.href='guidance.php'">
                <h3>Guidance Reports</h3>
                <p>View or manage your Guidance reports.</p>
            </div>
        </div>
    </div>
</body>
</html>
