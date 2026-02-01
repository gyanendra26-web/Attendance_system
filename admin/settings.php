<?php
require_once '../config/database.php';
require_once '../includes/functions.php';
requireLogin();

if (!isAdmin()) {
    header("Location: ../login.php");
    exit();
}

$message = '';

// Get current settings
$query = "SELECT * FROM system_settings";
$result = mysqli_query($conn, $query);
$settings = [];
while ($row = mysqli_fetch_assoc($result)) {
    $settings[$row['setting_key']] = $row;
}

// Handle settings update
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['update_settings'])) {
        $working_hours_start = sanitize($_POST['working_hours_start']);
        $working_hours_end = sanitize($_POST['working_hours_end']);
        $working_hours_threshold = intval($_POST['working_hours_threshold']);
        $late_threshold_minutes = intval($_POST['late_threshold_minutes']);
        $overtime_rate = floatval($_POST['overtime_rate']);
        
        // Update each setting
        $settings_to_update = [
            'working_hours_start' => $working_hours_start,
            'working_hours_end' => $working_hours_end,
            'working_hours_threshold' => $working_hours_threshold,
            'late_threshold_minutes' => $late_threshold_minutes,
            'overtime_rate' => $overtime_rate
        ];
        
        $updated = true;
        foreach ($settings_to_update as $key => $value) {
            $query = "UPDATE system_settings SET setting_value = ? WHERE setting_key = ?";
            $stmt = mysqli_prepare($conn, $query);
            mysqli_stmt_bind_param($stmt, "ss", $value, $key);
            if (!mysqli_stmt_execute($stmt)) {
                $updated = false;
            }
        }
        
        if ($updated) {
            $message = '<div class="alert success">Settings updated successfully!</div>';
        } else {
            $message = '<div class="alert error">Error updating settings!</div>';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>System Settings</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <style>
        .settings-group {
            margin-bottom: 30px;
            padding: 20px;
            background: #f8f9fa;
            border-radius: 5px;
        }
        
        .settings-group h4 {
            color: #2c3e50;
            margin-bottom: 20px;
            padding-bottom: 10px;
            border-bottom: 1px solid #ddd;
        }
        
        .settings-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 20px;
        }
        
        .setting-item {
            background: white;
            padding: 15px;
            border-radius: 5px;
            border: 1px solid #e9ecef;
        }
        
        .setting-label {
            display: block;
            font-weight: 600;
            margin-bottom: 5px;
            color: #495057;
        }
        
        .setting-value {
            color: #6c757d;
            font-size: 14px;
        }
        
        .readonly-input {
            background: #e9ecef;
            cursor: not-allowed;
        }
    </style>
</head>
<body>
    <?php 
    $page = 'settings';
    include 'header.php'; 
    ?>
    
    <div class="container">
        <div class="dashboard-grid">
            <?php include 'sidebar.php'; ?>
            
            <main class="main-content">
                <h2>System Settings</h2>
                
                <?php echo $message; ?>
                
                <form method="POST" action="">
                    <!-- Working Hours Settings -->
                    <div class="settings-group">
                        <h4>Working Hours Configuration</h4>
                        <div class="settings-grid">
                            <div class="setting-item">
                                <label class="setting-label">Check-in Time (Start)</label>
                                <input type="time" name="working_hours_start" 
                                       value="<?php echo $settings['working_hours_start']['setting_value'] ?? '09:00'; ?>"
                                       class="form-control">
                                <small class="setting-value">Default check-in time for employees</small>
                            </div>
                            
                            <div class="setting-item">
                                <label class="setting-label">Check-out Time (End)</label>
                                <input type="time" name="working_hours_end" 
                                       value="<?php echo $settings['working_hours_end']['setting_value'] ?? '17:00'; ?>"
                                       class="form-control">
                                <small class="setting-value">Default check-out time for employees</small>
                            </div>
                            
                            <div class="setting-item">
                                <label class="setting-label">Full Day Threshold (Hours)</label>
                                <input type="number" name="working_hours_threshold" min="1" max="12" step="0.5"
                                       value="<?php echo $settings['working_hours_threshold']['setting_value'] ?? '6'; ?>"
                                       class="form-control">
                                <small class="setting-value">Minimum hours required for full day (less = half day)</small>
                            </div>
                            
                            <div class="setting-item">
                                <label class="setting-label">Late Threshold (Minutes)</label>
                                <input type="number" name="late_threshold_minutes" min="0" max="120"
                                       value="<?php echo $settings['late_threshold_minutes']['setting_value'] ?? '15'; ?>"
                                       class="form-control">
                                <small class="setting-value">Minutes after start time to mark as late</small>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Overtime Settings -->
                    <div class="settings-group">
                        <h4>Overtime Configuration</h4>
                        <div class="settings-grid">
                            <div class="setting-item">
                                <label class="setting-label">Overtime Rate Multiplier</label>
                                <input type="number" name="overtime_rate" min="1" max="3" step="0.1"
                                       value="<?php echo $settings['overtime_rate']['setting_value'] ?? '1.5'; ?>"
                                       class="form-control">
                                <small class="setting-value">Overtime pay rate (e.g., 1.5 = time and a half)</small>
                            </div>
                            
                            <div class="setting-item">
                                <label class="setting-label">Minimum OT Hours</label>
                                <input type="number" value="0.5" readonly class="form-control readonly-input">
                                <small class="setting-value">Minimum hours to count as overtime (fixed)</small>
                            </div>
                            
                            <div class="setting-item">
                                <label class="setting-label">Saturday OT Rate</label>
                                <input type="text" value="Same as above" readonly class="form-control readonly-input">
                                <small class="setting-value">Saturday work counted as overtime</small>
                            </div>
                            
                            <div class="setting-item">
                                <label class="setting-label">Holiday OT Rate</label>
                                <input type="text" value="Same as above" readonly class="form-control readonly-input">
                                <small class="setting-value">Holiday work counted as overtime</small>
                            </div>
                        </div>
                    </div>
                    
                    <!-- System Settings -->
                    <div class="settings-group">
                        <h4>System Configuration</h4>
                        <div class="settings-grid">
                            <div class="setting-item">
                                <label class="setting-label">Auto-mark Attendance</label>
                                <select name="auto_attendance" class="form-control">
                                    <option value="1" <?php echo ($settings['auto_attendance']['setting_value'] ?? '1') == '1' ? 'selected' : ''; ?>>Enabled</option>
                                    <option value="0" <?php echo ($settings['auto_attendance']['setting_value'] ?? '1') == '0' ? 'selected' : ''; ?>>Disabled</option>
                                </select>
                                <small class="setting-value">Automatically mark attendance daily</small>
                            </div>
                            
                            <div class="setting-item">
                                <label class="setting-label">Session Timeout (Minutes)</label>
                                <input type="number" value="30" readonly class="form-control readonly-input">
                                <small class="setting-value">Auto-logout after inactivity</small>
                            </div>
                            
                            <div class="setting-item">
                                <label class="setting-label">Default Casual Leave</label>
                                <input type="number" value="15" readonly class="form-control readonly-input">
                                <small class="setting-value">Casual leaves per year per employee</small>
                            </div>
                            
                            <div class="setting-item">
                                <label class="setting-label">Default Sick Leave</label>
                                <input type="number" value="10" readonly class="form-control readonly-input">
                                <small class="setting-value">Sick leaves per year per employee</small>
                            </div>
                        </div>
                    </div>
                    
                    <div class="form-actions">
                        <button type="submit" name="update_settings" class="btn btn-primary">
                            Save Settings
                        </button>
                        <button type="reset" class="btn btn-secondary">Reset Changes</button>
                    </div>
                </form>
                
                <!-- System Information -->
                <div class="card" style="margin-top: 30px;">
                    <h3>System Information</h3>
                    <div class="settings-grid">
                        <div class="setting-item">
                            <span class="setting-label">PHP Version</span>
                            <span class="setting-value"><?php echo phpversion(); ?></span>
                        </div>
                        
                        <div class="setting-item">
                            <span class="setting-label">MySQL Version</span>
                            <span class="setting-value">
                                <?php 
                                $version = mysqli_get_server_info($conn);
                                echo $version ?: 'Unknown';
                                ?>
                            </span>
                        </div>
                        
                        <div class="setting-item">
                            <span class="setting-label">Server Timezone</span>
                            <span class="setting-value"><?php echo date_default_timezone_get(); ?></span>
                        </div>
                        
                        <div class="setting-item">
                            <span class="setting-label">Current Time</span>
                            <span class="setting-value"><?php echo date('Y-m-d H:i:s'); ?></span>
                        </div>
                    </div>
                </div>
            </main>
        </div>
    </div>
</body>
</html>