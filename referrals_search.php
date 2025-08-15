<?php
include 'db_sqlite.php';
$term = $_GET['term'] ?? '';
$field = $_GET['field'] ?? 'doctor';

if ($field === 'doctor') {
    // Search for distinct doctor names from patients table
    $sql = "SELECT DISTINCT ref_doctors AS doctor_name FROM patients WHERE ref_doctors LIKE ? AND ref_doctors != '' ORDER BY ref_doctors LIMIT 10";
    $stmt = $pdo->prepare($sql);
    $stmt->execute(["%$term%"]);
    
    // Format results for autocomplete
    $results = [];
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $results[] = [
            'label' => $row['doctor_name'],
            'value' => $row['doctor_name']
        ];
    }
} else {
    // Search for distinct ref names from patients table
    $sql = "SELECT DISTINCT ref_name FROM patients WHERE ref_name LIKE ? AND ref_name != '' ORDER BY ref_name LIMIT 10";
    $stmt = $pdo->prepare($sql);
    $stmt->execute(["%$term%"]);
    
    // Format results for autocomplete
    $results = [];
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $results[] = [
            'label' => $row['ref_name'],
            'value' => $row['ref_name']
        ];
    }
}

echo json_encode($results);