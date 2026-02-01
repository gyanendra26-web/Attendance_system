<?php
require_once '../config/database.php';
require_once '../includes/functions.php';
requireLogin();

if (!isAdmin()) {
    header("Location: ../login.php");
    exit();
}

$message = '';

// Detect whether holidays table supports ranges (start_date/end_date)
$has_range = columnExists('holidays', 'start_date') && columnExists('holidays', 'end_date');

// Add new holiday (supports single-day and multi-day ranges)
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['add_holiday'])) {
    $holiday_name = sanitize($_POST['holiday_name']);
    $holiday_type = sanitize($_POST['holiday_type']);
    $description = sanitize($_POST['description']);

    if ($has_range) {
        $start_date = sanitize($_POST['start_date'] ?? '');
        $end_date = sanitize($_POST['end_date'] ?? $start_date);

        // Normalize dates: if end_date empty, set equal to start_date
        if (empty($end_date)) {
            $end_date = $start_date;
        }

        // Check for overlapping holidays (either existing range overlapping or single-date in range)
        $check_query = "SELECT id FROM holidays WHERE (
            (start_date IS NOT NULL AND end_date IS NOT NULL AND NOT (end_date < ? OR start_date > ?))
            OR (holiday_date BETWEEN ? AND ?)
        ) LIMIT 1";
        $check_stmt = mysqli_prepare($conn, $check_query);
        mysqli_stmt_bind_param($check_stmt, "ssss", $start_date, $end_date, $start_date, $end_date);
        mysqli_stmt_execute($check_stmt);
        $check_result = mysqli_stmt_get_result($check_stmt);

        if (mysqli_num_rows($check_result) > 0) {
            $message = '<div class="alert error">This holiday overlaps with an existing holiday. Please check the holiday list below.</div>';
        } else {
            // Insert as range and store holiday_date for compatibility as start_date
            $query = "INSERT INTO holidays (holiday_name, holiday_date, start_date, end_date, holiday_type, description) VALUES (?, ?, ?, ?, ?, ?)";
            $stmt = mysqli_prepare($conn, $query);
            mysqli_stmt_bind_param($stmt, "ssssss", $holiday_name, $start_date, $start_date, $end_date, $holiday_type, $description);

            if (mysqli_stmt_execute($stmt)) {
                $message = '<div class="alert success">Holiday added successfully!</div>';
            } else {
                $error = mysqli_stmt_error($stmt);
                $message = '<div class="alert error">Error adding holiday: ' . htmlspecialchars($error) . '</div>';
            }
        }
    } else {
        // Legacy single-date holidays
        $holiday_date = sanitize($_POST['start_date'] ?? $_POST['holiday_date'] ?? '');

        // Check if holiday already exists
        $check_query = "SELECT id FROM holidays WHERE holiday_date = ? AND holiday_name = ?";
        $check_stmt = mysqli_prepare($conn, $check_query);
        mysqli_stmt_bind_param($check_stmt, "ss", $holiday_date, $holiday_name);
        mysqli_stmt_execute($check_stmt);
        $check_result = mysqli_stmt_get_result($check_stmt);

        if (mysqli_num_rows($check_result) > 0) {
            $message = '<div class="alert error">This holiday already exists! Please check the holiday list below.</div>';
        } else {
            $query = "INSERT INTO holidays (holiday_name, holiday_date, holiday_type, description) VALUES (?, ?, ?, ?)";
            $stmt = mysqli_prepare($conn, $query);
            mysqli_stmt_bind_param($stmt, "ssss", $holiday_name, $holiday_date, $holiday_type, $description);

            if (mysqli_stmt_execute($stmt)) {
                $message = '<div class="alert success">Holiday added successfully!</div>';
            } else {
                $error = mysqli_stmt_error($stmt);
                $message = '<div class="alert error">Error adding holiday: ' . htmlspecialchars($error) . '</div>';
            }
        }
    }
}

// Delete holiday
if (isset($_GET['delete'])) {
    $id = intval($_GET['delete']);
    $query = "DELETE FROM holidays WHERE id = ?";
    $stmt = mysqli_prepare($conn, $query);
    mysqli_stmt_bind_param($stmt, "i", $id);
    mysqli_stmt_execute($stmt);
    
    header("Location: holidays.php");
    exit();
}

// Get all holidays (use start_date if present, otherwise holiday_date)
if ($has_range) {
    $query = "SELECT * FROM holidays 
              WHERE YEAR(COALESCE(start_date, holiday_date)) >= YEAR(CURDATE()) 
              ORDER BY COALESCE(start_date, holiday_date)";
} else {
    $query = "SELECT * FROM holidays 
              WHERE YEAR(holiday_date) >= YEAR(CURDATE()) 
              ORDER BY holiday_date";
}
$holidays = mysqli_query($conn, $query);

// Get Nepali festivals for next year
$nepali_festivals = [
    ['Maha Shivaratri', '2024-03-08', 'festival', 'Maha Shivaratri'],
    ['Holi', '2024-03-25', 'festival', 'Festival of Colors'],
    ['Buddha Jayanti', '2024-05-23', 'national', 'Birthday of Lord Buddha'],
    ['Dashain Start', '2024-10-10', 'festival', 'Ghatasthapana'],
    ['Fulpati', '2024-10-15', 'festival', 'Fulpati'],
    ['Maha Asthami', '2024-10-16', 'festival', 'Maha Asthami'],
    ['Maha Navami', '2024-10-17', 'festival', 'Maha Navami'],
    ['Vijaya Dashami', '2024-10-18', 'festival', 'Vijaya Dashami'],
    ['Tihar Start', '2024-11-01', 'festival', 'Kaag Tihar'],
    ['Laxmi Puja', '2024-11-03', 'festival', 'Laxmi Puja'],
    ['Gobardhan Puja', '2024-11-04', 'festival', 'Gobardhan Puja'],
    ['Bhai Tika', '2024-11-05', 'festival', 'Bhai Tika'],
    ['Chhath', '2024-11-07', 'festival', 'Chhath Parva'],
    ['Christmas', '2024-12-25', 'national', 'Christmas Day']
];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Holiday Management</title>
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>
    <?php include 'header.php'; ?>
    
    <div class="container">
        <div class="dashboard-grid">
            <?php include 'sidebar.php'; ?>
            
            <main class="main-content">
                <h2>Holiday Management</h2>
                
                <?php echo $message; ?>
                
                <div class="card">
                    <h3>Add New Holiday</h3>
                    <form method="POST" action="">
                        <div class="form-row">
                            <div class="form-group">
                                <label>Holiday Name</label>
                                <input type="text" name="holiday_name" required>
                            </div>
                            
                            <div class="form-group">
                                <label>Start Date</label>
                                <input type="date" name="start_date" required>
                            </div>
                            <div class="form-group">
                                <label>End Date</label>
                                <input type="date" name="end_date">
                            </div>
                        </div>
                        
                        <div class="form-row">
                            <div class="form-group">
                                <label>Type</label>
                                <select name="holiday_type" required>
                                    <option value="national">National Holiday</option>
                                    <option value="festival">Festival</option>
                                    <option value="weekly">Weekly (Saturday)</option>
                                    <option value="company">General Holiday given by Company</option>
                                </select>
                            </div>
                            
                            <div class="form-group">
                                <label>Description</label>
                                <input type="text" name="description">
                            </div>
                        </div>
                        
                        <button type="submit" name="add_holiday" class="btn btn-primary">
                            Add Holiday
                        </button>
                    </form>
                </div>
                
                <!-- Quick Add Nepali Festivals -->
                <div class="card">
                    <h3>Quick Add: Nepali Festivals 2024</h3>
                    <div class="festival-grid">
                        <?php foreach ($nepali_festivals as $festival): ?>
                        <div class="festival-item">
                            <span><?php echo $festival[0]; ?></span>
                            <span><?php echo date('M d', strtotime($festival[1])); ?></span>
                            <form method="POST" action="" style="display:inline;">
                                <input type="hidden" name="holiday_name" value="<?php echo $festival[0]; ?>">
                                <input type="hidden" name="start_date" value="<?php echo $festival[1]; ?>">
                                <input type="hidden" name="end_date" value="<?php echo $festival[1]; ?>">
                                <input type="hidden" name="holiday_type" value="<?php echo $festival[2]; ?>">
                                <input type="hidden" name="description" value="<?php echo $festival[3]; ?>">
                                <button type="submit" name="add_holiday" class="btn btn-sm btn-success">
                                    Add
                                </button>
                            </form>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>
                
                <!-- Holiday List -->
                <div class="card">
                    <h3>Upcoming Holidays</h3>
                    <div class="table-container">
                        <table class="data-table">
                            <thead>
                                <tr>
                                    <th>Date</th>
                                    <th>Holiday Name</th>
                                    <th>Type</th>
                                    <th>Description</th>
                                    <th>Day</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php while ($holiday = mysqli_fetch_assoc($holidays)): ?>
                                <tr>
                                    <?php
                                        $start = $holiday['start_date'] ?? $holiday['holiday_date'];
                                        $end = $holiday['end_date'] ?? $holiday['holiday_date'];
                                    ?>
                                    <td><?php echo $start == $end ? date('Y-m-d', strtotime($start)) : date('Y-m-d', strtotime($start)) . ' - ' . date('Y-m-d', strtotime($end)); ?></td>
                                    <td><?php echo $holiday['holiday_name']; ?></td>
                                    <td>
                                        <span class="status-badge <?php echo $holiday['holiday_type']; ?>">
                                            <?php echo ucfirst($holiday['holiday_type']); ?>
                                        </span>
                                    </td>
                                    <td><?php echo $holiday['description']; ?></td>
                                    <td><?php echo $start == $end ? date('l', strtotime($start)) : date('l', strtotime($start)) . ' - ' . date('l', strtotime($end)); ?></td>
                                    <td>
                                        <a href="?delete=<?php echo $holiday['id']; ?>" 
                                           class="btn btn-danger btn-sm"
                                           onclick="return confirm('Delete this holiday?')">
                                            Delete
                                        </a>
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