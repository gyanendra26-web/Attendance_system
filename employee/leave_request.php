<?php
require_once '../config/database.php';
require_once '../includes/functions.php';
requireLogin();

if (!isEmployee()) {
    header("Location: ../login.php");
    exit();
}

$employee_id = $_SESSION['employee_id'];
$message = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $leave_type = sanitize($_POST['leave_type']);
    $start_date = sanitize($_POST['start_date']);
    $end_date = sanitize($_POST['end_date']);
    $reason = sanitize($_POST['reason']);
    
    // Calculate total leave days
    $start = new DateTime($start_date);
    $end = new DateTime($end_date);
    $interval = $start->diff($end);
    $total_days = $interval->days + 1;
    
    // Check for overlapping leave requests
    $query = "SELECT id FROM leave_requests 
              WHERE employee_id = ? 
                AND status = 'approved'
                AND (
                    (start_date BETWEEN ? AND ?)
                    OR (end_date BETWEEN ? AND ?)
                    OR (? BETWEEN start_date AND end_date)
                )";
    $stmt = mysqli_prepare($conn, $query);
    mysqli_stmt_bind_param($stmt, "isssss", $employee_id, $start_date, $end_date, 
                         $start_date, $end_date, $start_date);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    
    if (mysqli_num_rows($result) > 0) {
        $message = '<div class="alert error">You already have approved leave during this period!</div>';
    } else {
        // Insert leave request
        $query = "INSERT INTO leave_requests 
                  (employee_id, leave_type, start_date, end_date, reason) 
                  VALUES (?, ?, ?, ?, ?)";
        $stmt = mysqli_prepare($conn, $query);
        mysqli_stmt_bind_param($stmt, "issss", $employee_id, $leave_type, 
                             $start_date, $end_date, $reason);
        
        if (mysqli_stmt_execute($stmt)) {
            $message = '<div class="alert success">Leave request submitted successfully!</div>';
        } else {
            $message = '<div class="alert error">Error submitting leave request!</div>';
        }
    }
}

// Get leave balance (simplified)
$query = "SELECT 
            COUNT(CASE WHEN leave_type = 'casual' THEN 1 END) as casual_taken,
            COUNT(CASE WHEN leave_type = 'sick' THEN 1 END) as sick_taken
          FROM leave_requests 
          WHERE employee_id = ? 
            AND status = 'approved'
            AND YEAR(start_date) = YEAR(CURDATE())";
$stmt = mysqli_prepare($conn, $query);
mysqli_stmt_bind_param($stmt, "i", $employee_id);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$leave_taken = mysqli_fetch_assoc($result);

$casual_balance = 15 - ($leave_taken['casual_taken'] ?? 0); // 15 casual leaves per year
$sick_balance = 10 - ($leave_taken['sick_taken'] ?? 0); // 10 sick leaves per year
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Apply Leave</title>
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>
    <?php include 'header.php'; ?>
    
    <div class="container">
        <div class="dashboard-grid">
            <?php include 'sidebar.php'; ?>
            
            <main class="main-content">
                <h2>Apply for Leave</h2>
                
                <?php echo $message; ?>
                
                <div class="card">
                    <h3>Leave Balance</h3>
                    <div class="leave-balance">
                        <div class="balance-item">
                            <span class="balance-type">Casual Leave</span>
                            <span class="balance-count"><?php echo $casual_balance; ?> days remaining</span>
                        </div>
                        <div class="balance-item">
                            <span class="balance-type">Sick Leave</span>
                            <span class="balance-count"><?php echo $sick_balance; ?> days remaining</span>
                        </div>
                    </div>
                </div>
                
                <div class="card">
                    <h3>Leave Application Form</h3>
                    <form method="POST" action="">
                        <div class="form-group">
                            <label>Leave Type</label>
                            <select name="leave_type" required>
                                <option value="">Select Leave Type</option>
                                <option value="casual">Casual Leave</option>
                                <option value="sick">Sick Leave</option>
                                <option value="paid">Paid Leave</option>
                                <option value="unpaid">Unpaid Leave</option>
                            </select>
                        </div>
                        
                        <div class="form-row">
                            <div class="form-group">
                                <label>Start Date</label>
                                <input type="date" name="start_date" required 
                                       min="<?php echo date('Y-m-d'); ?>">
                            </div>
                            
                            <div class="form-group">
                                <label>End Date</label>
                                <input type="date" name="end_date" required 
                                       min="<?php echo date('Y-m-d'); ?>">
                            </div>
                        </div>
                        
                        <div class="form-group">
                            <label>Reason</label>
                            <textarea name="reason" rows="4" required 
                                      placeholder="Please provide reason for leave..."></textarea>
                        </div>
                        
                        <button type="submit" class="btn btn-primary">Submit Leave Request</button>
                    </form>
                </div>
                
                <!-- Leave History -->
                <div class="card">
                    <h3>Leave History</h3>
                    <div class="table-container">
                        <?php
                        $query = "SELECT * FROM leave_requests 
                                  WHERE employee_id = ? 
                                  ORDER BY applied_at DESC 
                                  LIMIT 10";
                        $stmt = mysqli_prepare($conn, $query);
                        mysqli_stmt_bind_param($stmt, "i", $employee_id);
                        mysqli_stmt_execute($stmt);
                        $result = mysqli_stmt_get_result($stmt);
                        ?>
                        
                        <table class="data-table">
                            <thead>
                                <tr>
                                    <th>Type</th>
                                    <th>Start Date</th>
                                    <th>End Date</th>
                                    <th>Days</th>
                                    <th>Status</th>
                                    <th>Applied On</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php while ($row = mysqli_fetch_assoc($result)): ?>
                                <tr>
                                    <td><?php echo ucfirst($row['leave_type']); ?></td>
                                    <td><?php echo date('M d, Y', strtotime($row['start_date'])); ?></td>
                                    <td><?php echo date('M d, Y', strtotime($row['end_date'])); ?></td>
                                    <td>
                                        <?php 
                                        $start = new DateTime($row['start_date']);
                                        $end = new DateTime($row['end_date']);
                                        echo $start->diff($end)->days + 1;
                                        ?>
                                    </td>
                                    <td>
                                        <span class="status-badge <?php echo $row['status']; ?>">
                                            <?php echo ucfirst($row['status']); ?>
                                        </span>
                                    </td>
                                    <td><?php echo date('M d, Y', strtotime($row['applied_at'])); ?></td>
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