<?php
require_once '../config/database.php';
require_once '../includes/functions.php';
requireLogin();

if (!isAdmin()) {
    header("Location: ../login.php");
    exit();
}

// Filter parameters
$month = $_GET['month'] ?? date('m');
$year = $_GET['year'] ?? date('Y');
$employee_id = $_GET['employee_id'] ?? '';
$status = $_GET['status'] ?? '';

// Build query for overtime report
$conditions = [];
$params = [];
$types = '';

if ($employee_id) {
    $conditions[] = "e.id = ?";
    $params[] = $employee_id;
    $types .= 'i';
}

if ($status) {
    $conditions[] = "o.status = ?";
    $params[] = $status;
    $types .= 's';
}

$where_clause = $conditions ? 'WHERE ' . implode(' AND ', $conditions) : '';

// Get overtime report
$query = "SELECT 
            o.*,
            e.name,
            e.employee_id,
            e.department,
            u.username as approved_by_name
          FROM overtime o
          JOIN employees e ON o.employee_id = e.id
          LEFT JOIN users u ON o.approved_by = u.id
          $where_clause
          ORDER BY o.overtime_date DESC";

if ($types) {
    $stmt = mysqli_prepare($conn, $query);
    mysqli_stmt_bind_param($stmt, $types, ...$params);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
} else {
    $result = mysqli_query($conn, $query);
}

// Handle overtime approval
if (isset($_POST['approve_overtime'])) {
    $overtime_id = intval($_POST['overtime_id']);
    $admin_id = $_SESSION['user_id'];
    
    $query = "UPDATE overtime 
              SET status = 'approved', approved_by = ?, approved_at = NOW() 
              WHERE id = ?";
    $stmt = mysqli_prepare($conn, $query);
    mysqli_stmt_bind_param($stmt, "ii", $admin_id, $overtime_id);
    
    if (mysqli_stmt_execute($stmt)) {
        // Also update attendance record
        $query = "SELECT employee_id, overtime_date, hours 
                  FROM overtime WHERE id = ?";
        $stmt = mysqli_prepare($conn, $query);
        mysqli_stmt_bind_param($stmt, "i", $overtime_id);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        $ot = mysqli_fetch_assoc($result);
        
        // Update attendance record
        $query = "UPDATE attendance 
                  SET overtime_hours = ? 
                  WHERE employee_id = ? AND attendance_date = ?";
        $stmt = mysqli_prepare($conn, $query);
        mysqli_stmt_bind_param($stmt, "dis", $ot['hours'], $ot['employee_id'], $ot['overtime_date']);
        mysqli_stmt_execute($stmt);
        
        header("Location: overtime_report.php?approved=1");
        exit();
    }
}

// Get employees for filter
$employees = mysqli_query($conn, "SELECT id, name, employee_id FROM employees ORDER BY name");

// Get overtime summary
$query = "SELECT 
            SUM(CASE WHEN status = 'approved' THEN hours ELSE 0 END) as approved_hours,
            SUM(CASE WHEN status = 'pending' THEN hours ELSE 0 END) as pending_hours,
            SUM(CASE WHEN status = 'rejected' THEN hours ELSE 0 END) as rejected_hours,
            COUNT(*) as total_requests
          FROM overtime";
$summary_result = mysqli_query($conn, $query);
$summary = mysqli_fetch_assoc($summary_result);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Overtime Report</title>
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>
    <?php 
    $page = 'overtime_report';
    include 'header.php'; 
    ?>
    
    <div class="container">
        <div class="dashboard-grid">
            <?php include 'sidebar.php'; ?>
            
            <main class="main-content">
                <h2>Overtime Management</h2>
                
                <?php if (isset($_GET['approved'])): ?>
                <div class="alert success">Overtime approved successfully!</div>
                <?php endif; ?>
                
                <!-- Overtime Summary -->
                <div class="dashboard-cards">
                    <div class="card">
                        <h4>Approved OT Hours</h4>
                        <div class="stat-number"><?php echo $summary['approved_hours'] ?? 0; ?></div>
                        <span class="stat-label">Hours</span>
                    </div>
                    
                    <div class="card">
                        <h4>Pending OT Hours</h4>
                        <div class="stat-number warning"><?php echo $summary['pending_hours'] ?? 0; ?></div>
                        <span class="stat-label">Hours</span>
                    </div>
                    
                    <div class="card">
                        <h4>Total Requests</h4>
                        <div class="stat-number info"><?php echo $summary['total_requests'] ?? 0; ?></div>
                        <span class="stat-label">Requests</span>
                    </div>
                </div>
                
                <!-- Filters -->
                <div class="card">
                    <h3>Filters</h3>
                    <form method="GET" action="" class="filter-form">
                        <div class="form-row">
                            <div class="form-group">
                                <label>Employee</label>
                                <select name="employee_id">
                                    <option value="">All Employees</option>
                                    <?php while ($emp = mysqli_fetch_assoc($employees)): ?>
                                    <option value="<?php echo $emp['id']; ?>"
                                        <?php echo $emp['id'] == $employee_id ? 'selected' : ''; ?>>
                                        <?php echo $emp['name'] . ' (' . $emp['employee_id'] . ')'; ?>
                                    </option>
                                    <?php endwhile; ?>
                                </select>
                            </div>
                            
                            <div class="form-group">
                                <label>Status</label>
                                <select name="status">
                                    <option value="">All Status</option>
                                    <option value="pending" <?php echo $status == 'pending' ? 'selected' : ''; ?>>Pending</option>
                                    <option value="approved" <?php echo $status == 'approved' ? 'selected' : ''; ?>>Approved</option>
                                    <option value="rejected" <?php echo $status == 'rejected' ? 'selected' : ''; ?>>Rejected</option>
                                </select>
                            </div>
                        </div>
                        
                        <div class="form-actions">
                            <button type="submit" class="btn btn-primary">Apply Filters</button>
                            <a href="?" class="btn btn-secondary">Reset</a>
                        </div>
                    </form>
                </div>
                
                <!-- Overtime Report -->
                <div class="card">
                    <h3>Overtime Requests</h3>
                    <div class="table-container">
                        <table class="data-table">
                            <thead>
                                <tr>
                                    <th>Employee</th>
                                    <th>Date</th>
                                    <th>Hours</th>
                                    <th>Rate</th>
                                    <th>Total OT</th>
                                    <th>Reason</th>
                                    <th>Status</th>
                                    <th>Approved By</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php while ($ot = mysqli_fetch_assoc($result)): ?>
                                <tr>
                                    <td><?php echo $ot['name']; ?><br>
                                        <small><?php echo $ot['department']; ?></small>
                                    </td>
                                    <td><?php echo date('M d, Y', strtotime($ot['overtime_date'])); ?></td>
                                    <td><?php echo $ot['hours']; ?></td>
                                    <td><?php echo $ot['rate_multiplier']; ?>x</td>
                                    <td><?php echo $ot['hours'] * $ot['rate_multiplier']; ?> hours</td>
                                    <td><?php echo $ot['reason']; ?></td>
                                    <td>
                                        <span class="status-badge <?php echo $ot['status']; ?>">
                                            <?php echo ucfirst($ot['status']); ?>
                                        </span>
                                    </td>
                                    <td>
                                        <?php if ($ot['approved_by_name']): ?>
                                            <?php echo $ot['approved_by_name']; ?><br>
                                            <small><?php echo date('M d', strtotime($ot['approved_at'])); ?></small>
                                        <?php else: ?>
                                            -
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?php if ($ot['status'] == 'pending'): ?>
                                        <form method="POST" action="" style="display:inline;">
                                            <input type="hidden" name="overtime_id" value="<?php echo $ot['id']; ?>">
                                            <button type="submit" name="approve_overtime" 
                                                    class="btn btn-sm btn-success"
                                                    onclick="return confirm('Approve this overtime?')">
                                                Approve
                                            </button>
                                        </form>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                                <?php endwhile; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </main>
        </div>
    </div>
</body>
</html>