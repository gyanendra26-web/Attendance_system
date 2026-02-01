<?php
require_once '../config/database.php';
require_once '../includes/functions.php';
requireLogin();

if (!isAdmin()) {
    header("Location: ../login.php");
    exit();
}

$message = '';

// Handle leave approval/rejection
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['approve_leave']) || isset($_POST['reject_leave'])) {
        $leave_id = intval($_POST['leave_id']);
        $status = isset($_POST['approve_leave']) ? 'approved' : 'rejected';
        $admin_comment = sanitize($_POST['admin_comment'] ?? '');
        $admin_id = $_SESSION['user_id'];
        
        // Update leave status
        $query = "UPDATE leave_requests 
                  SET status = ?, admin_comment = ?, reviewed_by = ?, reviewed_at = NOW() 
                  WHERE id = ?";
        $stmt = mysqli_prepare($conn, $query);
        mysqli_stmt_bind_param($stmt, "ssii", $status, $admin_comment, $admin_id, $leave_id);
        
        if (mysqli_stmt_execute($stmt)) {
            // If approved, update attendance records
            if ($status == 'approved') {
                $query = "SELECT employee_id, start_date, end_date FROM leave_requests WHERE id = ?";
                $stmt = mysqli_prepare($conn, $query);
                mysqli_stmt_bind_param($stmt, "i", $leave_id);
                mysqli_stmt_execute($stmt);
                $result = mysqli_stmt_get_result($stmt);
                $leave = mysqli_fetch_assoc($result);
                
                // Mark attendance as leave for each day
                $start = new DateTime($leave['start_date']);
                $end = new DateTime($leave['end_date']);
                $end->modify('+1 day'); // Include end date
                $interval = new DateInterval('P1D');
                $period = new DatePeriod($start, $interval, $end);
                
                foreach ($period as $date) {
                    $attendance_date = $date->format('Y-m-d');
                    
                    // Check if record exists
                    $query = "SELECT id FROM attendance 
                              WHERE employee_id = ? AND attendance_date = ?";
                    $stmt = mysqli_prepare($conn, $query);
                    mysqli_stmt_bind_param($stmt, "is", $leave['employee_id'], $attendance_date);
                    mysqli_stmt_execute($stmt);
                    $result = mysqli_stmt_get_result($stmt);
                    
                    if (mysqli_num_rows($result) > 0) {
                        // Update existing record
                        $query = "UPDATE attendance SET status = 'leave' 
                                  WHERE employee_id = ? AND attendance_date = ?";
                        $stmt = mysqli_prepare($conn, $query);
                        mysqli_stmt_bind_param($stmt, "is", $leave['employee_id'], $attendance_date);
                        mysqli_stmt_execute($stmt);
                    } else {
                        // Insert new record
                        $query = "INSERT INTO attendance (employee_id, attendance_date, status) 
                                  VALUES (?, ?, 'leave')";
                        $stmt = mysqli_prepare($conn, $query);
                        mysqli_stmt_bind_param($stmt, "is", $leave['employee_id'], $attendance_date);
                        mysqli_stmt_execute($stmt);
                    }
                }
            }
            
            $message = '<div class="alert success">Leave request ' . $status . ' successfully!</div>';
        } else {
            $message = '<div class="alert error">Error updating leave request!</div>';
        }
    }
}

// Get pending leave requests
$query = "SELECT lr.*, e.name, e.employee_id, e.department 
          FROM leave_requests lr
          JOIN employees e ON lr.employee_id = e.id
          WHERE lr.status = 'pending'
          ORDER BY lr.applied_at DESC";
$pending_leaves = mysqli_query($conn, $query);

// Get leave history
$query = "SELECT lr.*, e.name, e.employee_id, e.department 
          FROM leave_requests lr
          JOIN employees e ON lr.employee_id = e.id
          WHERE lr.status != 'pending'
          ORDER BY lr.reviewed_at DESC
          LIMIT 50";
$leave_history = mysqli_query($conn, $query);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Leave Management</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <style>
        .leave-details {
            background: #f8f9fa;
            padding: 15px;
            border-radius: 5px;
            margin: 10px 0;
        }
        
        .leave-item {
            border: 1px solid #ddd;
            border-radius: 5px;
            padding: 15px;
            margin-bottom: 15px;
            background: white;
        }
        
        .leave-actions {
            display: flex;
            gap: 10px;
            margin-top: 15px;
        }
        
        .leave-status {
            font-weight: 600;
            padding: 5px 10px;
            border-radius: 3px;
            display: inline-block;
        }
        
        .status-pending { background: #fff3cd; color: #856404; }
        .status-approved { background: #d4edda; color: #155724; }
        .status-rejected { background: #f8d7da; color: #721c24; }
    </style>
</head>
<body>
    <?php 
    $page = 'leave_management';
    include 'header.php'; 
    ?>
    
    <div class="container">
        <div class="dashboard-grid">
            <?php include 'sidebar.php'; ?>
            
            <main class="main-content">
                <h2>Leave Management</h2>
                
                <?php echo $message; ?>
                
                <!-- Pending Leave Requests -->
                <div class="card">
                    <h3>Pending Leave Requests</h3>
                    
                    <?php if (mysqli_num_rows($pending_leaves) > 0): ?>
                        <?php while ($leave = mysqli_fetch_assoc($pending_leaves)): ?>
                        <div class="leave-item">
                            <div class="leave-header">
                                <h4><?php echo $leave['name']; ?> (<?php echo $leave['employee_id']; ?>)</h4>
                                <span class="leave-status status-pending">Pending</span>
                            </div>
                            
                            <div class="leave-details">
                                <p><strong>Type:</strong> <?php echo ucfirst($leave['leave_type']); ?> Leave</p>
                                <p><strong>Period:</strong> <?php echo date('M d, Y', strtotime($leave['start_date'])); ?> 
                                   to <?php echo date('M d, Y', strtotime($leave['end_date'])); ?></p>
                                <p><strong>Total Days:</strong> 
                                    <?php 
                                    $start = new DateTime($leave['start_date']);
                                    $end = new DateTime($leave['end_date']);
                                    echo $start->diff($end)->days + 1;
                                    ?>
                                </p>
                                <p><strong>Department:</strong> <?php echo $leave['department']; ?></p>
                                <p><strong>Reason:</strong> <?php echo nl2br($leave['reason']); ?></p>
                                <p><strong>Applied On:</strong> <?php echo date('M d, Y h:i A', strtotime($leave['applied_at'])); ?></p>
                            </div>
                            
                            <form method="POST" action="" class="leave-actions">
                                <input type="hidden" name="leave_id" value="<?php echo $leave['id']; ?>">
                                
                                <div class="form-group">
                                    <label>Comments (Optional)</label>
                                    <textarea name="admin_comment" rows="2" 
                                              placeholder="Add comments for employee..."></textarea>
                                </div>
                                
                                <div class="action-buttons">
                                    <button type="submit" name="approve_leave" 
                                            class="btn btn-success"
                                            onclick="return confirm('Approve this leave request?')">
                                        Approve
                                    </button>
                                    
                                    <button type="submit" name="reject_leave" 
                                            class="btn btn-danger"
                                            onclick="return confirm('Reject this leave request?')">
                                        Reject
                                    </button>
                                </div>
                            </form>
                        </div>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <p class="no-data">No pending leave requests.</p>
                    <?php endif; ?>
                </div>
                
                <!-- Leave History -->
                <div class="card">
                    <h3>Leave History</h3>
                    <div class="table-container">
                        <table class="data-table">
                            <thead>
                                <tr>
                                    <th>Employee</th>
                                    <th>Type</th>
                                    <th>From</th>
                                    <th>To</th>
                                    <th>Days</th>
                                    <th>Status</th>
                                    <th>Reviewed On</th>
                                    <th>Comments</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php while ($leave = mysqli_fetch_assoc($leave_history)): ?>
                                <tr>
                                    <td><?php echo $leave['name']; ?><br>
                                        <small><?php echo $leave['department']; ?></small>
                                    </td>
                                    <td><?php echo ucfirst($leave['leave_type']); ?></td>
                                    <td><?php echo date('M d', strtotime($leave['start_date'])); ?></td>
                                    <td><?php echo date('M d', strtotime($leave['end_date'])); ?></td>
                                    <td>
                                        <?php 
                                        $start = new DateTime($leave['start_date']);
                                        $end = new DateTime($leave['end_date']);
                                        echo $start->diff($end)->days + 1;
                                        ?>
                                    </td>
                                    <td>
                                        <span class="leave-status status-<?php echo $leave['status']; ?>">
                                            <?php echo ucfirst($leave['status']); ?>
                                        </span>
                                    </td>
                                    <td><?php echo $leave['reviewed_at'] ? date('M d, Y', strtotime($leave['reviewed_at'])) : '-'; ?></td>
                                    <td><?php echo $leave['admin_comment']; ?></td>
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