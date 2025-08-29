<?php
// Set timezone to Asia/Dhaka
date_default_timezone_set('Asia/Dhaka');

// Load SMS configuration
$smsConfig = simplexml_load_file('sms_conf.xml');
$smsHost = (string)$smsConfig->sms->host;
$smsPort = (string)$smsConfig->sms->port;

// Function to check if a WebSocket server is available
function checkWebSocketServer($host, $port, $timeout = 5) {
    // Create a socket
    $socket = @fsockopen($host, $port, $errno, $errstr, $timeout);
    
    if ($socket) {
        // If connection was successful, close it and return true
        fclose($socket);
        return [
            'online' => true,
            'message' => 'Connection successful'
        ];
    } else {
        // If connection failed, return false with error message
        return [
            'online' => false,
            'message' => $errstr ?: 'Connection failed'
        ];
    }
}

// Check SMS server status
$result = checkWebSocketServer($smsHost, $smsPort);

// Return JSON response
header('Content-Type: application/json');
echo json_encode($result);
?>