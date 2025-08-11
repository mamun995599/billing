<?php
date_default_timezone_set('Asia/Dhaka');
// Load configuration from XML file
$configFile = __DIR__ . '/backup_conf.xml';
if (!file_exists($configFile)) {
    die("Configuration file not found: $configFile\n");
}
$config = simplexml_load_file($configFile);
$sourceFile = __DIR__ . '/' . $config->source_file;
$gdriveFolder = $config->gdrive_folder;
$keepDays = (int)$config->keep_days;
$checkInterval = (int)$config->check_interval;
$backupType = (string)$config->backup_type;

// Set up logging
$logFile = __DIR__ . '/backup_log.txt';
$pidFile = __DIR__ . '/backup.pid';

// Define a local config file path in the script directory
$localConfigFile = __DIR__ . '/rclone.conf';

// Write PID to file
file_put_contents($pidFile, getmypid());

// Register shutdown function to remove PID file
register_shutdown_function(function() use ($pidFile) {
    if (file_exists($pidFile)) {
        unlink($pidFile);
    }
});

// Function to log messages
function logMessage($message) {
    global $logFile;
    $timestamp = date('Y-m-d H:i:s');
    $logEntry = "[$timestamp] $message\n";
    file_put_contents($logFile, $logEntry, FILE_APPEND);
}

logMessage("🟢 backup.php started. Backup type: $backupType");

// Initialize variables based on backup type
$backupDoneToday = false;
$lastBackupTime = 0;
$dailyBackups = [];
$intervalSeconds = 0;
if ($backupType === 'daily') {
    foreach ($config->daily_backups->time as $time) {
        $dailyBackups[] = (string)$time;
    }
    logMessage("Daily backup times: " . implode(', ', $dailyBackups));
} elseif ($backupType === 'interval') {
    $intervalSeconds = (int)$config->interval_backup->interval_seconds;
    logMessage("Interval backup: every $intervalSeconds seconds");
}

// Check if rclone is available
function checkRclone() {
    $output = shell_exec('rclone version 2>&1');
    if ($output === null || strpos($output, 'rclone') === false) {
        logMessage("❌ rclone not found in PATH. Please install rclone or add it to PATH.");
        return false;
    }
    logMessage("✅ rclone found: " . substr($output, 0, strpos($output, "\n")));
    return true;
}

// Check rclone at startup
if (!checkRclone()) {
    die("rclone not available. Exiting.\n");
}

// Function to copy rclone config from user profile to script directory
function setupRcloneConfig() {
    global $localConfigFile, $logFile;
    
    // Check if config already exists in script directory
    if (file_exists($localConfigFile)) {
        logMessage("✅ rclone config already exists in script directory");
        return true;
    }
    
    // Try to find the user's rclone config in Termux
    $homeDir = getenv('HOME');
    if ($homeDir === false) {
        logMessage("❌ Could not get home directory path");
        return false;
    }
    
    $userConfigPath = "$homeDir/.config/rclone/rclone.conf";
    
    if (!file_exists($userConfigPath)) {
        logMessage("❌ User rclone config not found at: $userConfigPath");
        return false;
    }
    
    // Copy the config file to script directory
    if (!copy($userConfigPath, $localConfigFile)) {
        logMessage("❌ Failed to copy rclone config to script directory");
        return false;
    }
    
    logMessage("✅ Copied rclone config to script directory");
    return true;
}

// Function to convert path for rclone (no conversion needed on Android)
function convertPathForRclone($path) {
    // Get real path to resolve any relative paths
    $realPath = realpath($path);
    if ($realPath === false) {
        logMessage("❌ Failed to get real path for: $path");
        return false;
    }
    
    return $realPath;
}

// Setup rclone config at startup
if (!setupRcloneConfig()) {
    logMessage("❌ Failed to setup rclone config. Exiting.");
    die("Failed to setup rclone config.\n");
}

// Main loop
while (true) {
    clearstatcache();
    $now = time();
    $currentDate = date('Y-m-d');
    $currentTime = date('H:i');
    
    // Reset backup flag at midnight for daily backups
    if ($backupType === 'daily' && $currentTime === '00:00') {
        $backupDoneToday = false;
    }
    
    // Check if source file exists
    if (!file_exists($sourceFile)) {
        logMessage("⚠️ Source file not found: $sourceFile");
        sleep($checkInterval);
        continue;
    }
    
    // Perform backup based on type
    if ($backupType === 'daily') {
        // Daily backup logic
        foreach ($dailyBackups as $scheduledTime) {
            $scheduledTimestamp = strtotime("$currentDate $scheduledTime");
            
            // If current time is past scheduled time, and not yet backed up today
            if ($now >= $scheduledTimestamp && ($now - $scheduledTimestamp) <= 3600 && !$backupDoneToday) {
                // Check if today's backup already exists remotely
                $backupPrefix = "clinic_$currentDate";
                $listCmd = "rclone --config \"$localConfigFile\" lsf gdrive:$gdriveFolder/";
                $remoteFiles = [];
                exec($listCmd, $remoteFiles, $retval);
                
                if ($retval !== 0) {
                    logMessage("❌ Failed to list remote files. Return code: $retval");
                    $backupDoneToday = true; // Skip this time to avoid continuous errors
                    continue;
                }
                
                $alreadyBackedUp = false;
                foreach ($remoteFiles as $file) {
                    if (strpos($file, $backupPrefix) === 0) {
                        $alreadyBackedUp = true;
                        break;
                    }
                }
                
                if (!$alreadyBackedUp) {
                    if (performBackup($currentDate)) {
                        $backupDoneToday = true;
                    }
                } else {
                    logMessage("✅ Today's backup already exists on Google Drive. Skipping backup.");
                    $backupDoneToday = true;
                }
            }
        }
    } elseif ($backupType === 'interval') {
        // Interval backup logic
        if (($now - $lastBackupTime) >= $intervalSeconds) {
            if (performBackup(date('Y-m-d_H-i-s'))) {
                $lastBackupTime = $now;
            }
        }
    }
    
    // Cleanup old backups
    cleanupOldBackups();
    
    logMessage("⏳ Waiting... Next check in $checkInterval seconds.");
    sleep($checkInterval);
}

// Function to perform backup
function performBackup($timestamp) {
    global $sourceFile, $gdriveFolder, $logFile, $localConfigFile;
    
    $tempDir = __DIR__ . '/backups';
    if (!file_exists($tempDir)) {
        if (!mkdir($tempDir, 0777, true)) {
            logMessage("❌ Failed to create temp directory: $tempDir");
            return false;
        }
    }
    
    $backupFile = "$tempDir/clinic_$timestamp.db";
    
    logMessage("🔄 Creating backup: $backupFile");
    
    if (!copy($sourceFile, $backupFile)) {
        logMessage("❌ Failed to copy file to temp directory: $backupFile");
        return false;
    }
    
    // Verify the backup file was created and has content
    if (!file_exists($backupFile) || filesize($backupFile) === 0) {
        logMessage("❌ Backup file is empty or not created: $backupFile");
        return false;
    }
    
    logMessage("📊 Backup file size: " . number_format(filesize($backupFile)) . " bytes");
    
    // Convert path for rclone
    $rclonePath = convertPathForRclone($backupFile);
    if ($rclonePath === false) {
        return false;
    }
    
    logMessage("📤 Using rclone path: $rclonePath");
    logMessage("📤 Using config file: $localConfigFile");
    
    // Upload to Google Drive with explicit config file
    $uploadCmd = "rclone --config \"$localConfigFile\" copy \"$rclonePath\" gdrive:$gdriveFolder/ --verbose 2>&1";
    logMessage("📤 Executing: $uploadCmd");
    
    // Execute the command and capture output
    $output = [];
    $retval = 0;
    exec($uploadCmd, $output, $retval);
    
    // Log the output
    if (!empty($output)) {
        foreach ($output as $line) {
            logMessage("📤 rclone: $line");
        }
    }
    
    if ($retval !== 0) {
        logMessage("❌ Upload failed with return code: $retval");
        return false;
    } else {
        logMessage("✅ Backup uploaded: clinic_$timestamp.db");
    }
    
    // Remove local temp
    if (file_exists($backupFile)) {
        unlink($backupFile);
    }
    
    return true;
}

// Function to cleanup old backups
function cleanupOldBackups() {
    global $gdriveFolder, $keepDays, $logFile, $localConfigFile;
    
    logMessage("🧹 Cleaning old backups...");
    
    $listCmd = "rclone --config \"$localConfigFile\" lsf --format pt --files-only gdrive:$gdriveFolder/";
    $remoteFiles = [];
    exec($listCmd, $remoteFiles, $retval);
    
    if ($retval !== 0) {
        logMessage("❌ Failed to list remote files. Return code: $retval");
        return;
    }
    
    $now = time();
    foreach ($remoteFiles as $fileInfo) {
        if (strpos($fileInfo, ';') !== false) {
            [$filename, $modified] = explode(';', $fileInfo);
            $modifiedTime = strtotime(trim($modified));
            if ($now - $modifiedTime > ($keepDays * 86400)) {
                logMessage("🗑️ Deleting old backup: $filename");
                $deleteCmd = "rclone --config \"$localConfigFile\" deletefile gdrive:$gdriveFolder/$filename";
                exec($deleteCmd, $deleteOutput, $deleteRetval);
                if ($deleteRetval !== 0) {
                    logMessage("❌ Failed to delete $filename. Return code: $deleteRetval");
                } else {
                    logMessage("✅ Deleted old backup: $filename");
                }
            }
        }
    }
}