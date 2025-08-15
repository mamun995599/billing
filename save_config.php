<?php
// Start output buffering to prevent any accidental output
ob_start();

// Check if this is a valid POST request
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405); // Method Not Allowed
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
    exit;
}

// Get the configuration data
$configType = $_POST['config_type'] ?? '';
$field = $_POST['field'] ?? '';
$value = $_POST['value'] ?? '';

// Validate the inputs
if (empty($configType) || empty($field) || $value === '') {
    http_response_code(400); // Bad Request
    echo json_encode(['success' => false, 'message' => 'Missing required parameters']);
    exit;
}

// Only allow specific configuration types and fields
$allowedConfigTypes = ['sms', 'email'];
$allowedFields = [
    'sms' => ['host', 'port'],
    'email' => ['host', 'port', 'username', 'password', 'from', 'from_name']
];

if (!in_array($configType, $allowedConfigTypes) || !in_array($field, $allowedFields[$configType])) {
    http_response_code(400); // Bad Request
    echo json_encode(['success' => false, 'message' => 'Invalid configuration type or field']);
    exit;
}

try {
    // Determine which configuration file to update
    $configFile = $configType . '_conf.xml';
    
    // Load the existing configuration
    if (file_exists($configFile)) {
        $config = simplexml_load_file($configFile);
    } else {
        // Create a new configuration if the file doesn't exist
        $config = new SimpleXMLElement('<config/>');
        $config->addChild($configType);
    }
    
    // Update the specific field
    $config->{$configType}->{$field} = $value;
    
    // Save the configuration
    $result = $config->asXML($configFile);
    
    if ($result) {
        echo json_encode(['success' => true, 'message' => 'Configuration saved successfully']);
    } else {
        throw new Exception('Failed to save configuration file');
    }
} catch (Exception $e) {
    http_response_code(500); // Internal Server Error
    echo json_encode(['success' => false, 'message' => 'Error saving configuration: ' . $e->getMessage()]);
}

// Clean output buffering
ob_end_flush();