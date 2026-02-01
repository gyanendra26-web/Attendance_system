<?php
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'admin') {
    header("Location: ../login.php");
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Panel</title>
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>
    <div class="container">
        <header class="header">
            <div class="header-left">
                <h1>Admin Dashboard</h1>
                <div class="current-time">
                    <span id="current-date"></span>
                    <span id="current-time"></span>
                </div>
            </div>
            
            <div class="user-info">
                <span>Welcome, <?php echo $_SESSION['username']; ?> (Admin)</span>
                <a href="../logout.php" class="btn-logout">Logout</a>
            </div>
        </header>