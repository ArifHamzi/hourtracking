<?php
ini_set('session.gc_maxlifetime', 3600); // 1 hour
session_set_cookie_params(3600); // Cookie lasts 1 hour

session_start();
require 'db.php';

if ($_SESSION['role'] !== 'supervisor') {
    header("Location: login.php");
    exit();
}

if (isset($_SESSION['message'])) {
    echo "<p style='color: green;'>".$_SESSION['message']."</p>";
    unset($_SESSION['message']);  // Clear the message after showing
}

if (isset($_SESSION['error'])) {
    echo "<p style='color: red;'>".$_SESSION['error']."</p>";
    unset($_SESSION['error']);  // Clear the error message after showing
}

$superviseeId = $_GET['superviseeId'] ?? null;

if (!$superviseeId) {
    echo "Supervisee not selected.";
    exit();
}

// Fetch Individual reports
$query = $conn->prepare("
    SELECT I.id, I.referenceNo, I.session, I.hoursSubmitted, I.uploadedFile, I.status
    FROM Report R
    JOIN Individual I ON R.id = I.reportId
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
    <title>Individual Reports</title>
    <link rel="stylesheet" href="styles.css">

    <script>
function changeStatus(reportId, action) {
    var xhr = new XMLHttpRequest();
    xhr.open("POST", "process_action.php", true);
    xhr.setRequestHeader("Content-Type", "application/x-www-form-urlencoded");

    var params = "reportId=" + reportId + "&action=" + action + "&reportType=individual";

    xhr.onreadystatechange = function () {
        if (xhr.readyState == 4 && xhr.status == 200) {
            var response = xhr.responseText;
            if (response === "success") {
                // Update the status text dynamically
                var statusCell = document.getElementById("status-" + reportId);
                statusCell.textContent = action === "approve" ? "Approved" : "Rejected";

                // Optionally, you can display a success message (you can customize this)
                alert("Report has been " + action + "ed successfully!");
            } else {
                // Handle other responses (e.g., failure message)
                alert(response); // Shows the error message returned from PHP
            }
        }
    };
    xhr.send(params);
}
</script>

</head>
<body>
    <div class="toolbar">
        <p>Welcome <?= $_SESSION['fullname'] ?> (Supervisor)</p>
        <a href="logout.php" class="btn">Logout</a>
    </div>
    <?php include 'sidebar.php'; ?>
    <div class="content">
        <h1>Individual Reports</h1>
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
                    <td style="display: flex; justify-content: space-between; align-items: center;">
                        <?php if (!empty($report['uploadedFile'])): ?>
                            <input type="text" value="<?= htmlspecialchars($report['uploadedFile']) ?>" readonly style="border: none; background: transparent; color: inherit; width: 100%; padding: 10px;">
                            <a href="uploads/<?= htmlspecialchars($report['uploadedFile']) ?>" target="_blank" class="btn view-btn">View</a>
                        <?php endif; ?>
                    </td>
                    <td><?= $report['status'] ?></td>
                    <td>
                        <button class="btn approve-btn" onclick="changeStatus(<?= $report['id'] ?>, 'approve')">Approve</button>
                        <button class="btn reject-btn" onclick="changeStatus(<?= $report['id'] ?>, 'reject')">Reject</button>
                    </td>
                </tr>
            <?php endforeach; ?>
        </table>
    </div>
</body>
</html>
