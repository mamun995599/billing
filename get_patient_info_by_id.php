<?php
// get_patient_by_old_id.php
header('Content-Type: application/json');
include 'db_sqlite.php';
if (empty($_GET['old_id'])) {
    echo json_encode(null);
    exit;
}
$old_id = $_GET['old_id'];
try {
    // Fetch patient info by old_id
    $stmt = $pdo->prepare("SELECT patient_id, phone, patient_name, sex, address, email FROM patients WHERE patient_id = ?");
    $stmt->execute([$old_id]);
    $patient = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$patient) {
        echo json_encode(null);
        exit;
    }
    
    // Return patient data
    echo json_encode([
        'patient' => $patient
    ]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Server error']);
}