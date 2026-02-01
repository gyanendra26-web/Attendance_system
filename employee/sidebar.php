<nav class="sidebar">
    <ul>
        <li><a href="dashboard.php" class="<?php echo basename($_SERVER['PHP_SELF']) == 'dashboard.php' ? 'active' : ''; ?>">Dashboard</a></li>
        <li><a href="attendance.php" class="<?php echo basename($_SERVER['PHP_SELF']) == 'attendance.php' ? 'active' : ''; ?>">Attendance History</a></li>
        <li><a href="leave_request.php" class="<?php echo basename($_SERVER['PHP_SELF']) == 'leave_request.php' ? 'active' : ''; ?>">Leave Request</a></li>
        <li><a href="profile.php" class="<?php echo basename($_SERVER['PHP_SELF']) == 'profile.php' ? 'active' : ''; ?>">My Profile</a></li>
    </ul>
</nav>