<?php
// Set timezone to Asia/Dhaka
date_default_timezone_set('Asia/Dhaka');

header('Content-Type: application/json');
include 'db_sqlite.php';

$patient_id = $_POST['patient_id'] ?? '';

if (empty($patient_id)) {
    echo json_encode(['success' => false, 'message' => 'Patient ID is required']);
    exit;
}

try {
    $pdo->beginTransaction();
    
    // Delete billing records first (due to foreign key constraint)
    $stmt = $pdo->prepare("DELETE FROM billing WHERE patient_id = ?");
    $stmt->execute([$patient_id]);
    
    // Then delete the patient
    $stmt = $pdo->prepare("DELETE FROM patients WHERE patient_id = ?");
    $stmt->execute([$patient_id]);
    
    $pdo->commit();
    
    echo json_encode(['success' => true, 'message' => 'Patient record deleted successfully']);
} catch (PDOException $e) {
    $pdo->rollBack();
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
}
?>