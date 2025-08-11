<?php
include 'db_sqlite.php';
$term = $_GET['term'] ?? '';
$sql = "SELECT service_id, service_name, price FROM services WHERE service_name LIKE ? LIMIT 10";
$stmt = $pdo->prepare($sql);
$stmt->execute(["%$term%"]);
$results = $stmt->fetchAll(PDO::FETCH_ASSOC);
echo json_encode($results);