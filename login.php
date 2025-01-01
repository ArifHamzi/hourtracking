<?php
session_start();
require 'db.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = $_POST['email'];
    $password = $_POST['password'];

    // Check email in both tables
    $query = $conn->prepare("SELECT id, fullname, username, password, 'supervisee' AS role FROM User WHERE email = ?
                             UNION
                             SELECT id, fullname, username, password, 'supervisor' AS role FROM Supervisor WHERE email = ?");
    $query->bind_param('ss', $email, $email);
    $query->execute();
    $result = $query->get_result();

    if ($user = $result->fetch_assoc()) {
        if (password_verify($password, $user['password'])) {
            // Store session variables
            $_SESSION['role'] = $user['role'];
            $_SESSION['id'] = $user['id'];
            $_SESSION['fullname'] = $user['fullname'];
            $_SESSION['username'] = $user['username'];
            $_SESSION['email'] = $email;
            $_SESSION['password'] = $password;

            // Redirect based on role
            $dashboard = $user['role'] === 'supervisee' ? 'supervisee_dashboard.php' : 'supervisor_dashboard.php';
            header("Location: $dashboard");
            exit();
        } else {
            $error = "Invalid password.";
        }
    } else {
        $error = "Email not found.";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login</title>
    <link rel="stylesheet" href="styles.css"> 
    <script>
        function setTitle(role) {
            document.getElementById('form-title').innerText = role.charAt(0).toUpperCase() + role.slice(1) + ' Login';
            document.getElementById('role').value = role;
        }

        document.addEventListener('DOMContentLoaded', function() {
            const role = new URLSearchParams(window.location.search).get('role');
            if (role) {
                setTitle(role);
            }
        });
    </script>
</head>
<body>
    <form method="post" action="">
        <h2 id="form-title">Login</h2>
        <input type="hidden" name="role" id="role" value="">
        <input type="email" name="email" placeholder="Email" required>
        <input type="password" name="password" placeholder="Password" required>
        <button type="submit">Login</button>

        <p>Not registered? <a href="#">Contact Admin</a></p>
    </form>
    <?php if (isset($error)) echo "<p style='color:red;'>$error</p>"; ?>
</body>
</html>
