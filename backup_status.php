<?php
$logFile = __DIR__ . '/backup_log.txt';
$backupDir = __DIR__ . '/backups';
$pidFile = __DIR__ . '/backup.pid';
$configFile = __DIR__ . '/backup_conf.xml';

// Create backup directory if it doesn't exist
if (!file_exists($backupDir)) {
    mkdir($backupDir, 0777, true);
}

// Function to check if the process is running
function isProcessRunning() {
    global $pidFile;
    if (!file_exists($pidFile)) {
        return false;
    }
    
    $pid = (int)file_get_contents($pidFile);
    
    // Check if the process is running (Windows compatible)
    exec("tasklist /FI \"PID eq $pid\" /FO CSV /NH", $output, $retval);
    return $retval === 0 && count($output) > 0;
}

// Function to start the backup process
function startBackupProcess() {
    global $logFile, $pidFile;
    
    // Start the process in the background (Windows compatible)
    $phpPath = 'php'; // Assumes PHP is in PATH
    $scriptPath = __DIR__ . '/backup.php';
    $command = "start /B $phpPath $scriptPath";
    
    // Execute the command
    pclose(popen($command, 'r'));
    
    // Wait a moment for the process to start and create the PID file
    sleep(2);
    
    // Check if PID file was created
    if (file_exists($pidFile)) {
        $pid = (int)file_get_contents($pidFile);
        
        // Log the action
        $logEntry = "[" . date('Y-m-d H:i:s') . "] Backup process started with PID: $pid\n";
        file_put_contents($logFile, $logEntry, FILE_APPEND);
        
        return $pid;
    } else {
        // Log the failure
        $logEntry = "[" . date('Y-m-d H:i:s') . "] Failed to start backup process\n";
        file_put_contents($logFile, $logEntry, FILE_APPEND);
        
        return false;
    }
}

// Function to stop the backup process
function stopBackupProcess() {
    global $logFile, $pidFile;
    
    if (!file_exists($pidFile)) {
        return false;
    }
    
    $pid = (int)file_get_contents($pidFile);
    
    // Terminate the process (Windows compatible)
    exec("taskkill /PID $pid /F", $output, $retval);
    
    // Remove the PID file
    if (file_exists($pidFile)) {
        unlink($pidFile);
    }
    
    // Log the action
    $logEntry = "[" . date('Y-m-d H:i:s') . "] Backup process stopped (PID: $pid)\n";
    file_put_contents($logFile, $logEntry, FILE_APPEND);
    
    return $retval === 0;
}

// Function to save configuration
function saveConfig($data) {
    global $configFile, $logFile;
    
    $xml = new SimpleXMLElement('<backup_config/>');
    $xml->addChild('source_file', $data['source_file']);
    $xml->addChild('gdrive_folder', $data['gdrive_folder']);
    $xml->addChild('keep_days', $data['keep_days']);
    $xml->addChild('check_interval', $data['check_interval']);
    $xml->addChild('backup_type', $data['backup_type']);
    
    if ($data['backup_type'] === 'daily') {
        $dailyBackups = $xml->addChild('daily_backups');
        foreach ($data['daily_times'] as $time) {
            $dailyBackups->addChild('time', $time);
        }
    } elseif ($data['backup_type'] === 'interval') {
        $intervalBackup = $xml->addChild('interval_backup');
        $intervalBackup->addChild('interval_seconds', $data['interval_seconds']);
    }
    
    $xml->asXML($configFile);
    
    // Log the action
    $logEntry = "[" . date('Y-m-d H:i:s') . "] Backup configuration updated\n";
    file_put_contents($logFile, $logEntry, FILE_APPEND);
}

// Handle configuration form submission
if (isset($_POST['save_config'])) {
    $configData = [
        'source_file' => $_POST['source_file'],
        'gdrive_folder' => $_POST['gdrive_folder'],
        'keep_days' => $_POST['keep_days'],
        'check_interval' => $_POST['check_interval'],
        'backup_type' => $_POST['backup_type']
    ];
    
    if ($_POST['backup_type'] === 'daily') {
        $configData['daily_times'] = $_POST['daily_times'];
    } elseif ($_POST['backup_type'] === 'interval') {
        $configData['interval_seconds'] = $_POST['interval_seconds'];
    }
    
    saveConfig($configData);
    
    // Redirect to avoid form resubmission
    header("Location: " . $_SERVER['PHP_SELF']);
    exit;
}

// Handle toggle request
if (isset($_POST['toggle_backup'])) {
    $action = $_POST['toggle_backup'];
    
    if ($action === 'start') {
        startBackupProcess();
    } elseif ($action === 'stop') {
        stopBackupProcess();
    }
    
    // Redirect to avoid form resubmission
    header("Location: " . $_SERVER['PHP_SELF']);
    exit;
}

// Load configuration
$config = simplexml_load_file($configFile);
$sourceFile = (string)$config->source_file;
$gdriveFolder = (string)$config->gdrive_folder;
$keepDays = (int)$config->keep_days;
$checkInterval = (int)$config->check_interval;
$backupType = (string)$config->backup_type;

$dailyTimes = [];
if ($backupType === 'daily') {
    foreach ($config->daily_backups->time as $time) {
        $dailyTimes[] = (string)$time;
    }
}

$intervalSeconds = 0;
if ($backupType === 'interval') {
    $intervalSeconds = (int)$config->interval_backup->interval_seconds;
}

// Check if the process is currently running
$isRunning = isProcessRunning();

// Get the last 50 log entries
$logEntries = [];
if (file_exists($logFile)) {
    $logs = file($logFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    $logEntries = array_slice($logs, -50); // Get last 50 entries
    $logEntries = array_reverse($logEntries); // Show newest first
}

// Get backup files
$backupFiles = [];
if (is_dir($backupDir)) {
    $files = scandir($backupDir);
    foreach ($files as $file) {
        if (strpos($file, 'clinic_') === 0 && strpos($file, '.db') !== false) {
            $filePath = $backupDir . '/' . $file;
            $backupFiles[] = [
                'name' => $file,
                'size' => filesize($filePath),
                'modified' => filemtime($filePath)
            ];
        }
    }
    // Sort by modified time (newest first)
    usort($backupFiles, function($a, $b) {
        return $b['modified'] - $a['modified'];
    });
    $backupFiles = array_slice($backupFiles, 0, 10); // Show last 10 files
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Backup Status Monitor</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
    <style>
        body {
            background-color: #f8f9fa;
            padding: 20px;
        }
        .card {
            margin-bottom: 20px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        }
        .card-header {
            font-weight: bold;
        }
        .log-entry {
            padding: 8px;
            margin-bottom: 5px;
            border-radius: 4px;
            font-family: monospace;
        }
        .log-success {
            background-color: #d4edda;
            color: #155724;
        }
        .log-error {
            background-color: #f8d7da;
            color: #721c24;
        }
        .log-info {
            background-color: #d1ecf1;
            color: #0c5460;
        }
        .log-warning {
            background-color: #fff3cd;
            color: #856404;
        }
        .status-running {
            color: #28a745;
        }
        .status-stopped {
            color: #dc3545;
        }
        .backup-file {
            padding: 10px;
            border-bottom: 1px solid #eee;
        }
        .backup-file:last-child {
            border-bottom: none;
        }
        .refresh-btn {
            position: fixed;
            bottom: 20px;
            right: 20px;
            width: 60px;
            height: 60px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            box-shadow: 0 4px 8px rgba(0,0,0,0.2);
        }
        .timestamp {
            font-size: 0.8em;
            color: #6c757d;
        }
        .status-indicator {
            display: inline-block;
            width: 12px;
            height: 12px;
            border-radius: 50%;
            margin-right: 8px;
        }
        .status-indicator.running {
            background-color: #28a745;
            animation: pulse 2s infinite;
        }
        .status-indicator.stopped {
            background-color: #dc3545;
        }
        @keyframes pulse {
            0% {
                box-shadow: 0 0 0 0 rgba(40, 167, 69, 0.4);
            }
            70% {
                box-shadow: 0 0 0 10px rgba(40, 167, 69, 0);
            }
            100% {
                box-shadow: 0 0 0 0 rgba(40, 167, 69, 0);
            }
        }
        .control-panel {
            background-color: #e9ecef;
            padding: 15px;
            border-radius: 5px;
            margin-bottom: 20px;
        }
        .btn-start {
            background-color: #28a745;
            border-color: #28a745;
        }
        .btn-start:hover {
            background-color: #218838;
            border-color: #1e7e34;
        }
        .btn-stop {
            background-color: #dc3545;
            border-color: #dc3545;
        }
        .btn-stop:hover {
            background-color: #c82333;
            border-color: #bd2130;
        }
        .config-section {
            margin-bottom: 20px;
        }
        .time-input {
            margin-bottom: 10px;
        }
        .nav-tabs .nav-link {
            color: #495057;
        }
        .nav-tabs .nav-link.active {
            font-weight: bold;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1 class="mb-4 text-center">
            <i class="bi bi-cloud-arrow-up"></i> Backup Status Monitor
        </h1>
        
        <!-- Control Panel -->
        <div class="control-panel">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <h5 class="mb-0">Backup Process Control</h5>
                    <p class="mb-0">Start or stop the backup process</p>
                </div>
                <div>
                    <form method="post" class="d-flex align-items-center">
                        <?php if ($isRunning): ?>
                            <input type="hidden" name="toggle_backup" value="stop">
                            <button type="submit" class="btn btn-stop">
                                <i class="bi bi-stop-circle"></i> Stop Process
                            </button>
                        <?php else: ?>
                            <input type="hidden" name="toggle_backup" value="start">
                            <button type="submit" class="btn btn-start">
                                <i class="bi bi-play-circle"></i> Start Process
                            </button>
                        <?php endif; ?>
                    </form>
                </div>
            </div>
        </div>
        
        <!-- Configuration Panel -->
        <div class="card">
            <div class="card-header">
                <i class="bi bi-gear"></i> Backup Configuration
            </div>
            <div class="card-body">
                <form method="post">
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="source_file" class="form-label">Source File</label>
                                <input type="text" class="form-control" id="source_file" name="source_file" value="<?= htmlspecialchars($sourceFile) ?>" required>
                            </div>
                            <div class="mb-3">
                                <label for="gdrive_folder" class="form-label">Google Drive Folder</label>
                                <input type="text" class="form-control" id="gdrive_folder" name="gdrive_folder" value="<?= htmlspecialchars($gdriveFolder) ?>" required>
                            </div>
                            <div class="mb-3">
                                <label for="keep_days" class="form-label">Keep Days</label>
                                <input type="number" class="form-control" id="keep_days" name="keep_days" value="<?= $keepDays ?>" min="1" required>
                            </div>
                            <div class="mb-3">
                                <label for="check_interval" class="form-label">Check Interval (seconds)</label>
                                <input type="number" class="form-control" id="check_interval" name="check_interval" value="<?= $checkInterval ?>" min="1" required>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label">Backup Type</label>
                                <div class="form-check">
                                    <input class="form-check-input" type="radio" name="backup_type" id="backup_type_daily" value="daily" <?= $backupType === 'daily' ? 'checked' : '' ?> required>
                                    <label class="form-check-label" for="backup_type_daily">
                                        Daily (at specific times)
                                    </label>
                                </div>
                                <div class="form-check">
                                    <input class="form-check-input" type="radio" name="backup_type" id="backup_type_interval" value="interval" <?= $backupType === 'interval' ? 'checked' : '' ?> required>
                                    <label class="form-check-label" for="backup_type_interval">
                                        Interval (every X seconds)
                                    </label>
                                </div>
                            </div>
                            
                            <!-- Daily Backup Configuration -->
                            <div id="daily_config" class="config-section <?= $backupType === 'daily' ? '' : 'd-none' ?>">
                                <label class="form-label">Backup Times</label>
                                <div id="daily_times_container">
                                    <?php foreach ($dailyTimes as $index => $time): ?>
                                        <div class="input-group time-input mb-2">
                                            <input type="time" class="form-control" name="daily_times[]" value="<?= $time ?>" required>
                                            <button type="button" class="btn btn-outline-danger remove-time">
                                                <i class="bi bi-trash"></i>
                                            </button>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                                <button type="button" class="btn btn-sm btn-outline-primary" id="add_time">
                                    <i class="bi bi-plus-circle"></i> Add Time
                                </button>
                            </div>
                            
                            <!-- Interval Backup Configuration -->
                            <div id="interval_config" class="config-section <?= $backupType === 'interval' ? '' : 'd-none' ?>">
                                <label for="interval_seconds" class="form-label">Interval (seconds)</label>
                                <input type="number" class="form-control" id="interval_seconds" name="interval_seconds" value="<?= $intervalSeconds ?>" min="1" required>
                                <small class="form-text text-muted">
                                    Examples: 120 = 2 minutes, 3600 = 1 hour, 7200 = 2 hours, 86400 = 1 day
                                </small>
                            </div>
                        </div>
                    </div>
                    <div class="text-center">
                        <button type="submit" name="save_config" class="btn btn-primary">
                            <i class="bi bi-save"></i> Save Configuration
                        </button>
                    </div>
                </form>
            </div>
        </div>
        
        <div class="row">
            <div class="col-md-6">
                <div class="card">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <span>Backup Process Status</span>
                        <span id="processStatus">
                            <span class="status-indicator <?= $isRunning ? 'running' : 'stopped' ?>"></span>
                            <span class="<?= $isRunning ? 'status-running' : 'status-stopped' ?>">
                                <?= $isRunning ? 'Running' : 'Stopped' ?>
                            </span>
                        </span>
                    </div>
                    <div class="card-body">
                        <div class="mb-3">
                            <h5>Current Configuration</h5>
                            <ul class="list-group">
                                <li class="list-group-item">Source: <code><?= htmlspecialchars($sourceFile) ?></code></li>
                                <li class="list-group-item">Destination: <code>gdrive:<?= htmlspecialchars($gdriveFolder) ?>/</code></li>
                                <li class="list-group-item">Retention: <code><?= $keepDays ?> days</code></li>
                                <li class="list-group-item">Check Interval: <code><?= $checkInterval ?> seconds</code></li>
                                <li class="list-group-item">Backup Type: <code><?= $backupType ?></code></li>
                                <?php if ($backupType === 'daily'): ?>
                                    <li class="list-group-item">Schedule Times: <code><?= implode(', ', $dailyTimes) ?></code></li>
                                <?php else: ?>
                                    <li class="list-group-item">Interval: <code><?= $intervalSeconds ?> seconds</code></li>
                                <?php endif; ?>
                            </ul>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="col-md-6">
                <div class="card">
                    <div class="card-header">
                        <i class="bi bi-file-earmark-text"></i> Recent Backup Files
                    </div>
                    <div class="card-body p-0">
                        <?php if (empty($backupFiles)): ?>
                            <div class="p-3 text-center text-muted">No backup files found</div>
                        <?php else: ?>
                            <?php foreach ($backupFiles as $file): ?>
                                <div class="backup-file">
                                    <div class="d-flex justify-content-between">
                                        <div><?= $file['name'] ?></div>
                                        <div class="text-end">
                                            <div><?= number_format($file['size'] / 1024 / 1024, 2) ?> MB</div>
                                            <div class="timestamp"><?= date('Y-m-d H:i:s', $file['modified']) ?></div>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="card">
            <div class="card-header">
                <i class="bi bi-journal-text"></i> Backup Logs
            </div>
            <div class="card-body">
                <?php if (empty($logEntries)): ?>
                    <div class="text-center text-muted">No log entries found</div>
                <?php else: ?>
                    <?php foreach ($logEntries as $entry): ?>
                        <?php
                        // Determine log entry type
                        $class = 'log-info';
                        if (strpos($entry, '✅') !== false) {
                            $class = 'log-success';
                        } elseif (strpos($entry, '❌') !== false) {
                            $class = 'log-error';
                        } elseif (strpos($entry, '⏳') !== false || strpos($entry, '🔄') !== false) {
                            $class = 'log-warning';
                        }
                        ?>
                        <div class="<?= $class ?>">
                            <?= htmlspecialchars($entry) ?>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>
    </div>
    
    <button class="btn btn-primary refresh-btn" onclick="location.reload()">
        <i class="bi bi-arrow-clockwise"></i>
    </button>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Handle backup type change
        document.querySelectorAll('input[name="backup_type"]').forEach(radio => {
            radio.addEventListener('change', function() {
                if (this.value === 'daily') {
                    document.getElementById('daily_config').classList.remove('d-none');
                    document.getElementById('interval_config').classList.add('d-none');
                } else {
                    document.getElementById('daily_config').classList.add('d-none');
                    document.getElementById('interval_config').classList.remove('d-none');
                }
            });
        });
        
        // Add time input
        document.getElementById('add_time').addEventListener('click', function() {
            const container = document.getElementById('daily_times_container');
            const newTimeInput = document.createElement('div');
            newTimeInput.className = 'input-group time-input mb-2';
            newTimeInput.innerHTML = `
                <input type="time" class="form-control" name="daily_times[]" required>
                <button type="button" class="btn btn-outline-danger remove-time">
                    <i class="bi bi-trash"></i>
                </button>
            `;
            container.appendChild(newTimeInput);
            
            // Add event listener to the new remove button
            newTimeInput.querySelector('.remove-time').addEventListener('click', function() {
                newTimeInput.remove();
            });
        });
        
        // Remove time input
        document.querySelectorAll('.remove-time').forEach(button => {
            button.addEventListener('click', function() {
                this.closest('.time-input').remove();
            });
        });
        
        // Auto-refresh every 30 seconds
        setInterval(function() {
            location.reload();
        }, 30000);
    </script>
</body>
</html>