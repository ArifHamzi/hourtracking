<?php
ini_set('session.gc_maxlifetime', 3600); // 1 hour
session_set_cookie_params(3600); // Cookie lasts 1 hour

session_start();
require 'db.php';

if (!isset($_GET['type']) || !in_array($_GET['type'], ['individual', 'group', 'guidance'])) {
    die('Invalid report type.');
}

$type = $_GET['type']; // individual, group, guidance
$returnPage = $type . ".php";

// Determine fields based on type
$fields = [];
if ($type === 'individual') {
    $fields = ['Reference No', 'Session', 'Hours Submitted'];
} elseif ($type === 'group') {
    $fields = ['Reference No', 'Session', 'Hours Submitted'];
} elseif ($type === 'guidance') {
    $fields = ['Reference No', 'Activity/Program', 'Documentation/Reflection', 'Hours Submitted'];
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <title>Add <?= ucfirst($type) ?> Report</title>
    <link rel="stylesheet" href="styles.css">
</head>
<body>
<div class="form-container">
    <form action="process_add_report.php" method="post" enctype="multipart/form-data">
    <h2>Add <?= ucfirst($type) ?> Report</h2>
        <?php foreach ($fields as $field): ?>
            <label><?= $field ?>:</label>
            <input type="text" name="<?= strtolower(str_replace(' ', '_', $field)) ?>" required>
        <?php endforeach; ?>
        <label>Upload File:</label>
        <input type="file" name="uploaded_file" required>
        <input type="hidden" name="type" value="<?= $type ?>">
        <button type="submit" class="btn">Submit</button>
        <button href="<?= $returnPage ?>" class="btn-cancel" style="background-color: red; color: white;">Cancel</button>
    </form>
</div>
</body>
</html>