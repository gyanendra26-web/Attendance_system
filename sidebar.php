<nav class="sidebar">
    <ul>
        <li><a href="dashboard.php" class="<?php echo $page == 'dashboard' ? 'active' : ''; ?>">Dashboard</a></li>
        <li><a href="employees.php" class="<?php echo $page == 'employees' ? 'active' : ''; ?>">Employees</a></li>
        <li><a href="attendance_report.php" class="<?php echo $page == 'attendance_report' ? 'active' : ''; ?>">Attendance Report</a></li>
        <li><a href="leave_management.php" class="<?php echo $page == 'leave_management' ? 'active' : ''; ?>">Leave Management</a></li>
        <li><a href="overtime_report.php" class="<?php echo $page == 'overtime_report' ? 'active' : ''; ?>">Overtime Report</a></li>
        <li><a href="holidays.php" class="<?php echo $page == 'holidays' ? 'active' : ''; ?>">Holidays</a></li>
        <li><a href="settings.php" class="<?php echo $page == 'settings' ? 'active' : ''; ?>">Settings</a></li>
    </ul>
</nav>