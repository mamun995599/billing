<?php
// Simple test script for backup
date_default_timezone_set('Asia/Dhaka');

// Configuration
$sourceFile = __DIR__ . '/clinic_bill.db';
$gdriveFolder = 'clinic';
$tempDir = __DIR__ . '/backups';

// Create temp directory if it doesn't exist
if (!file_exists($tempDir)) {
    mkdir($tempDir, 0777, true);
}

// Create a test backup file
$timestamp = date('Y-m-d_H-i-s');
$backupFile = "$tempDir/clinic_$timestamp.db";

echo "Creating backup file: $backupFile\n";

// Copy the source file
if (!copy($sourceFile, $backupFile)) {
    die("Failed to copy source file\n");
}

echo "Backup file created successfully\n";

// Test rclone command
$uploadCmd = "rclone copy \"$backupFile\" gdrive:$gdriveFolder/ --verbose";
echo "Executing: $uploadCmd\n";

// Execute the command and display output
$output = [];
$retval = 0;
exec($uploadCmd, $output, $retval);

echo "Return code: $retval\n";
echo "Output:\n";
foreach ($output as $line) {
    echo $line . "\n";
}

if ($retval === 0) {
    echo "Backup uploaded successfully!\n";
} else {
    echo "Backup failed!\n";
}

// Clean up
if (file_exists($backupFile)) {
    unlink($backupFile);
    echo "Test file cleaned up\n";
}
?>