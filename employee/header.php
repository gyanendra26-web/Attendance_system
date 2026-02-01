<?php
if (!isset($_SESSION['employee_id'])) {
    header("Location: ../login.php");
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Employee Dashboard</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <script src="../assets/js/main.js" defer></script>
</head>
<body>
    <div class="container">
        <header class="header">
            <div class="header-left">
                <h1>Employee Dashboard</h1>
                <div class="current-time">
                    <span id="current-date"></span>
                    <span id="current-time"></span>
                </div>
            </div>
            
            <div class="user-info">
                <span>Welcome, <?php echo $_SESSION['employee_name']; ?></span>
                <a href="../logout.php" class="btn-logout">Logout</a>
            </div>
        </header>