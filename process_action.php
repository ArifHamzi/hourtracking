<?php
ini_set('session.gc_maxlifetime', 3600); // 1 hour
session_set_cookie_params(3600); // Cookie lasts 1 hour

session_start();
require 'db.php';
require 'vendor/autoload.php'; // Ensure PHPMailer is included
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;


$mail = new PHPMailer(true);

if ($_SESSION['role'] !== 'supervisor') {
    echo "Access denied.";
    exit();
}

if (!isset($_SESSION['email']) || !isset($_SESSION['password'])) {
    echo "Supervisor email credentials are missing. Please log in again.";
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $reportId = $_POST['reportId'];
    $action = $_POST['action']; // 'approve' or 'reject'
    $reportType = $_POST['reportType']; // 'individual', 'group', or 'guidance'
    $status = ($action === 'approve') ? 'Approved' : 'Rejected';

    // Determine the table name based on the report type
    $tableName = '';
    if ($reportType === 'individual') {
        $tableName = 'Individual';
    } elseif ($reportType === 'grouping') {
        $tableName = 'Grouping';
    } elseif ($reportType === 'guidance') {
        $tableName = 'Guidance';
    } else {
        echo "Invalid report type.";
        exit();
    }

    // Update report status in the database
    $query = "UPDATE $tableName SET status = '$status' WHERE id = $reportId";
    
    if ($conn->query($query)) {
        // Fetch supervisee's email and reference number
        $superviseeQuery = $conn->prepare("
            SELECT U.email, I.referenceNo
            FROM Report R
            JOIN User U ON R.userId = U.id
            JOIN $tableName I ON R.id = I.reportId
            WHERE I.id = ?
        ");
        $superviseeQuery->bind_param("i", $reportId);
        $superviseeQuery->execute();
        $result = $superviseeQuery->get_result();
        $supervisee = $result->fetch_assoc();

        if ($supervisee) {
            $to = $supervisee['email'];
            $subject = "Report $status Notification";
            $message = "Dear Supervisee,\n\nYour report with Reference No: {$supervisee['referenceNo']} has been $status.\n\nThank you.";

            // Fetch supervisor email from the session
            $supervisorEmail = $_SESSION['email'];

            // Create a new PHPMailer instance
            $mail = new PHPMailer(true);

            try {
                // Server settings
                $mail->isSMTP();
                $mail->SMTPDebug = SMTP::DEBUG_SERVER; // Enable verbose debug output
                // $mail->SMTPSecure = 'ssl'; // Enable TLS encryption
                $mail->SMTPAuth = true; // Enable SMTP authentication
                $mail->Host = 'smtp.gmail.my'; // Set SMTP server
                $mail->SMTPAuth = true;
                $mail->Username = $supervisorEmail; // Supervisor's email as sender
                $mail->Password = 'hgcb pcmw keep aoou'; // Supervisor's email password
                $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
                $mail->Port = 587; // SMTP port for TLS
                // $mail->SMTPAutoTLS = false; // Disable TLS encryption

                // Recipients
                $mail->setFrom($supervisorEmail, 'Supervisor');
                $mail->addAddress($to); // Supervisee email

                // Content
                $mail->isHTML(true); // Set email format to HTML
                $mail->Subject = $subject;
                $mail->Body = $message;

                // Send email
                if ($mail->send()) {
                    echo "success"; // Send success response back to AJAX
                    echo "<script>setTimeout(function(){ window.location.reload(); }, 2000);</script>";
                } else {
                    echo "Failed to send email.";
                }
            } catch (Exception $e) {
                echo "Mailer Error: " . $mail->ErrorInfo;
            }
        } else {
            echo "Supervisee email not found.";
        }
    } else {
        echo "Failed to update report status. Error: " . $conn->error;
    }

    exit();
}
