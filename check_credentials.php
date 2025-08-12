<?php
// Set content type to JSON
header('Content-Type: application/json');

// Get username and password from POST request
$username = $_POST['username'] ?? '';
$password = $_POST['password'] ?? '';

// Path to user configuration file
$userConfFile = 'user_conf.xml';

// Check if the configuration file exists
if (file_exists($userConfFile)) {
    // Load the XML file
    $config = simplexml_load_file($userConfFile);
    
    // Get stored credentials
    $storedUsername = (string)$config->username;
    $storedPassword = (string)$config->password;
    
    // Verify the credentials
    if ($username === $storedUsername && password_verify($password, $storedPassword)) {
        // Authentication successful
        echo json_encode(['valid' => true]);
    } else {
        // Authentication failed
        echo json_encode(['valid' => false]);
    }
} else {
    // Configuration file doesn't exist
    echo json_encode(['valid' => false]);
}
?>