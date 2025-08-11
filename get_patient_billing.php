<?php
header('Content-Type: application/json');
include 'db_sqlite.php';
if (empty($_GET['patient_id'])) {
    echo json_encode(null);
    exit;
}
$patient_id = $_GET['patient_id'];
try {
    // Fetch patient info
    $stmt = $pdo->prepare("SELECT * FROM patients WHERE patient_id = ?");
    $stmt->execute([$patient_id]);
    $patient = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$patient) {
        echo json_encode(null);
        exit;
    }
    
    // Fetch billing info
    $stmt = $pdo->prepare("SELECT service_id, service_name, price, unit, less_percent, less, final_price FROM billing WHERE patient_id = ?");
    $stmt->execute([$patient_id]);
    $billing = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Return combined data
    echo json_encode([
        'patient' => $patient,
        'billing' => $billing
    ]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Server error']);
}