<?php
require_once '../config/database.php';
require_once '../includes/functions.php';
requireLogin();

if (!isAdmin()) {
    header("Location: ../login.php");
    exit();
}

$message = '';
$action = $_GET['action'] ?? '';

// Handle actions
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['add_employee'])) {
        $employee_id = 'EMP' . date('Ym') . str_pad(mt_rand(1, 999), 3, '0', STR_PAD_LEFT);
        $name = sanitize($_POST['name']);
        $email = sanitize($_POST['email']);
        $phone = sanitize($_POST['phone']);
        $department = sanitize($_POST['department']);
        $position = sanitize($_POST['position']);
        $password = hashPassword('password123'); // Default password
        
        $query = "INSERT INTO employees (employee_id, name, email, phone, department, position, password) 
                  VALUES (?, ?, ?, ?, ?, ?, ?)";
        $stmt = mysqli_prepare($conn, $query);
        mysqli_stmt_bind_param($stmt, "sssssss", $employee_id, $name, $email, $phone, $department, $position, $password);
        
        if (mysqli_stmt_execute($stmt)) {
            $message = '<div class="alert success">Employee added successfully! Default password: password123</div>';
        } else {
            $message = '<div class="alert error">Error adding employee!</div>';
        }
    }
    
    if (isset($_POST['update_employee'])) {
        $id = intval($_POST['id']);
        $name = sanitize($_POST['name']);
        $email = sanitize($_POST['email']);
        $phone = sanitize($_POST['phone']);
        $department = sanitize($_POST['department']);
        $position = sanitize($_POST['position']);
        $status = sanitize($_POST['status']);
        
        $query = "UPDATE employees SET name = ?, email = ?, phone = ?, 
                  department = ?, position = ?, status = ? WHERE id = ?";
        $stmt = mysqli_prepare($conn, $query);
        mysqli_stmt_bind_param($stmt, "ssssssi", $name, $email, $phone, 
                             $department, $position, $status, $id);
        
        if (mysqli_stmt_execute($stmt)) {
            $message = '<div class="alert success">Employee updated successfully!</div>';
        } else {
            $message = '<div class="alert error">Error updating employee!</div>';
        }
    }
    
    if (isset($_POST['reset_password'])) {
        $id = intval($_POST['id']);
        $password = hashPassword('password123');
        
        $query = "UPDATE employees SET password = ? WHERE id = ?";
        $stmt = mysqli_prepare($conn, $query);
        mysqli_stmt_bind_param($stmt, "si", $password, $id);
        
        if (mysqli_stmt_execute($stmt)) {
            $message = '<div class="alert success">Password reset to: password123</div>';
        }
    }
}

// Delete employee
if (isset($_GET['delete'])) {
    $id = intval($_GET['delete']);
    $query = "UPDATE employees SET status = 'inactive' WHERE id = ?";
    $stmt = mysqli_prepare($conn, $query);
    mysqli_stmt_bind_param($stmt, "i", $id);
    mysqli_stmt_execute($stmt);
    
    header("Location: employees.php");
    exit();
}

// Get all employees
$query = "SELECT * FROM employees ORDER BY created_at DESC";
$employees = mysqli_query($conn, $query);

// Get unique departments
$dept_query = "SELECT DISTINCT department FROM employees WHERE department IS NOT NULL ORDER BY department";
$departments = mysqli_query($conn, $dept_query);

// Get employee for editing
$edit_employee = null;
if (isset($_GET['edit'])) {
    $id = intval($_GET['edit']);
    $query = "SELECT * FROM employees WHERE id = ?";
    $stmt = mysqli_prepare($conn, $query);
    mysqli_stmt_bind_param($stmt, "i", $id);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $edit_employee = mysqli_fetch_assoc($result);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Employee Management</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <style>
        .employee-actions {
            display: flex;
            gap: 5px;
            flex-wrap: wrap;
        }
        
        .btn-sm {
            padding: 5px 10px;
            font-size: 12px;
        }
        
        .status-active {
            color: #27ae60;
            font-weight: 600;
        }
        
        .status-inactive {
            color: #e74c3c;
            font-weight: 600;
        }
    </style>
</head>
<body>
    <?php 
    $page = 'employees';
    include 'header.php'; 
    ?>
    
    <div class="container">
        <div class="dashboard-grid">
            <?php include 'sidebar.php'; ?>
            
            <main class="main-content">
                <h2>Employee Management</h2>
                
                <?php echo $message; ?>
                
                <div class="card">
                    <h3><?php echo $edit_employee ? 'Edit Employee' : 'Add New Employee'; ?></h3>
                    <form method="POST" action="">
                        <?php if ($edit_employee): ?>
                        <input type="hidden" name="id" value="<?php echo $edit_employee['id']; ?>">
                        <?php endif; ?>
                        
                        <div class="form-row">
                            <div class="form-group">
                                <label>Full Name *</label>
                                <input type="text" name="name" required 
                                       value="<?php echo $edit_employee['name'] ?? ''; ?>">
                            </div>
                            
                            <div class="form-group">
                                <label>Email *</label>
                                <input type="email" name="email" required 
                                       value="<?php echo $edit_employee['email'] ?? ''; ?>">
                            </div>
                        </div>
                        
                        <div class="form-row">
                            <div class="form-group">
                                <label>Phone Number</label>
                                <input type="text" name="phone" 
                                       value="<?php echo $edit_employee['phone'] ?? ''; ?>">
                            </div>
                            
                            <div class="form-group">
                                <label>Department</label>
                                <select name="department">
                                    <option value="">Select Department</option>
                                    <?php while ($dept = mysqli_fetch_assoc($departments)): ?>
                                    <option value="<?php echo $dept['department']; ?>"
                                        <?php echo ($edit_employee['department'] ?? '') == $dept['department'] ? 'selected' : ''; ?>>
                                        <?php echo $dept['department']; ?>
                                    </option>
                                    <?php endwhile; ?>
                                </select>
                            </div>
                        </div>
                        
                        <div class="form-row">
                            <div class="form-group">
                                <label>Position/Designation</label>
                                <input type="text" name="position" 
                                       value="<?php echo $edit_employee['position'] ?? ''; ?>">
                            </div>
                            
                            <?php if ($edit_employee): ?>
                            <div class="form-group">
                                <label>Status</label>
                                <select name="status">
                                    <option value="active" <?php echo ($edit_employee['status'] ?? '') == 'active' ? 'selected' : ''; ?>>Active</option>
                                    <option value="inactive" <?php echo ($edit_employee['status'] ?? '') == 'inactive' ? 'selected' : ''; ?>>Inactive</option>
                                </select>
                            </div>
                            <?php endif; ?>
                        </div>
                        
                        <div class="form-actions">
                            <?php if ($edit_employee): ?>
                            <button type="submit" name="update_employee" class="btn btn-primary">
                                Update Employee
                            </button>
                            <a href="employees.php" class="btn btn-secondary">Cancel</a>
                            <?php else: ?>
                            <button type="submit" name="add_employee" class="btn btn-primary">
                                Add Employee
                            </button>
                            <?php endif; ?>
                        </div>
                    </form>
                </div>
                
                <!-- Employee List -->
                <div class="card">
                    <h3>All Employees</h3>
                    <div class="table-container">
                        <table class="data-table">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Name</th>
                                    <th>Email</th>
                                    <th>Phone</th>
                                    <th>Department</th>
                                    <th>Position</th>
                                    <th>Status</th>
                                    <th>Joined</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php while ($employee = mysqli_fetch_assoc($employees)): ?>
                                <tr>
                                    <td><?php echo $employee['employee_id']; ?></td>
                                    <td><?php echo $employee['name']; ?></td>
                                    <td><?php echo $employee['email']; ?></td>
                                    <td><?php echo $employee['phone']; ?></td>
                                    <td><?php echo $employee['department']; ?></td>
                                    <td><?php echo $employee['position']; ?></td>
                                    <td>
                                        <span class="status-<?php echo $employee['status']; ?>">
                                            <?php echo ucfirst($employee['status']); ?>
                                        </span>
                                    </td>
                                    <td><?php echo date('Y-m-d', strtotime($employee['created_at'])); ?></td>
                                    <td>
                                        <div class="employee-actions">
                                            <a href="?edit=<?php echo $employee['id']; ?>" 
                                               class="btn btn-sm btn-info">Edit</a>
                                            
                                            <form method="POST" action="" style="display:inline;">
                                                <input type="hidden" name="id" value="<?php echo $employee['id']; ?>">
                                                <button type="submit" name="reset_password" 
                                                        class="btn btn-sm btn-warning"
                                                        onclick="return confirm('Reset password to default?')">
                                                    Reset Password
                                                </button>
                                            </form>
                                            
                                            <a href="?delete=<?php echo $employee['id']; ?>" 
                                               class="btn btn-sm btn-danger"
                                               onclick="return confirm('Deactivate this employee?')">
                                                Deactivate
                                            </a>
                                        </div>
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