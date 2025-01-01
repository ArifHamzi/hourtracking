<?php
session_start();
require 'db.php';

// Validate the input
if (!isset($_GET['type']) || !isset($_GET['id']) || !is_numeric($_GET['id'])) {
    die("Invalid request.");
}

$type = $_GET['type']; // 'individual', 'group', or 'guidance'
$reportId = intval($_GET['id']); // Report ID

// Begin transaction for safety
$conn->begin_transaction();

try {
    // Define the mapping of types to tables
    $tableMap = [
        'individual' => 'Individual',
        'grouping' => 'Grouping',
        'guidance' => 'Guidance',
    ];

    if (!array_key_exists($type, $tableMap)) {
        throw new Exception("Invalid report type.");
    }

    $table = $tableMap[$type];

    // Delete the report from the specific table
    $stmt = $conn->prepare("DELETE FROM $table WHERE reportId = ?");
    $stmt->bind_param("i", $reportId);
    $stmt->execute();
    $stmt->close();

    // Delete the report from the `Report` table
    $stmt = $conn->prepare("DELETE FROM Report WHERE id = ?");
    $stmt->bind_param("i", $reportId);
    $stmt->execute();
    $stmt->close();

    // Commit the transaction
    $conn->commit();

    // Redirect with success message
    echo "<script>
        alert('Report deleted successfully');
        window.location.href='{$type}.php';
    </script>";
    exit();
} catch (Exception $e) {
    $conn->rollback();
    die("Error deleting report: " . $e->getMessage());
}
?>
