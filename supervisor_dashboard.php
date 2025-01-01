<?php
ini_set('session.gc_maxlifetime', 3600); // 1 hour
session_set_cookie_params(3600); // Cookie lasts 1 hour

session_start();
require 'db.php';

if ($_SESSION['role'] !== 'supervisor') {
    header("Location: login.php");
    exit();
}

$supervisorId = $_SESSION['id']; // Logged-in supervisor ID

// Fetch supervisees assigned to the supervisor
$query = $conn->prepare("
    SELECT U.id, U.fullname, U.email
    FROM User U
    JOIN UserSupervisorMap USM ON U.id = USM.userId
    WHERE USM.supervisorId = ?
");
$query->bind_param("i", $supervisorId);
$query->execute();
$result = $query->get_result();

$supervisees = $result->fetch_all(MYSQLI_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <title>Supervisor Dashboard</title>
    <link rel="stylesheet" href="styles.css">
</head>
<body>
    <div class="toolbar">
        <p>Welcome <?= $_SESSION['fullname'] ?> (Supervisor)</p>
        <a href="logout.php" class="btn">Logout</a>
    </div>
    <div class="content">
        <h1>Supervisees</h1>
        <table>
            <tr>
                <th>Full Name</th>
                <th>Email</th>
                <th>Action</th>
            </tr>
            <?php foreach ($supervisees as $supervisee): ?>
                <tr>
                    <td><?= $supervisee['fullname'] ?></td>
                    <td><?= $supervisee['email'] ?></td>
                    <td>
                        <a href="supervisee_individual.php?superviseeId=<?= $supervisee['id'] ?>">View Reports</a>
                    </td>
                </tr>
            <?php endforeach; ?>
        </table>
    </div>
</body>
</html>
