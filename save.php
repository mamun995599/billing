<?php
// Start output buffering to prevent any accidental output
ob_start();
include 'db_sqlite.php';
try {
    $pdo->beginTransaction();
    
    $patient_id = $_POST['patient_id'] ?? '';
    $is_update = !empty($patient_id);
    
    $old_id = $_POST['old_id'] ?? null;
    $date = $_POST['date'] ?? null;
    $patient_name = $_POST['patient_name'] ?? null;
    $sex = $_POST['sex'] ?? null;
    $age_year = intval($_POST['age_year'] ?? 0);
    $age_month = intval($_POST['age_month'] ?? 0);
    $age_day = intval($_POST['age_day'] ?? 0);
    $age_parts = [];
    if ($age_year > 0) $age_parts[] = $age_year . 'y';
    if ($age_month > 0) $age_parts[] = $age_month . 'm';
    if ($age_day > 0) $age_parts[] = $age_day . 'd';
    $age = implode(' ', $age_parts);
    $phone = $_POST['phone'] ?? null;
    $address = $_POST['address'] ?? null;
    $ref_doctors = $_POST['ref_doctors'] ?? null;
    $ref_name = $_POST['ref_name'] ?? null;
    $delivery_date = $_POST['delivery_date'] ?? null;
    $delivery_time = $_POST['delivery_time'] ?? null;
    $remarks = $_POST['remarks'] ?? null;
    $less_total = floatval($_POST['less_total'] ?? 0);
    $less_percent_total = floatval($_POST['less_percent_total'] ?? 0);
    $paid = floatval($_POST['paid'] ?? 0);
    $send_sms = isset($_POST['send_sms']) ? 1 : 0;
    $timestamp = date('Y-m-d H:i:s');
    
    // Get SMS configuration from form if SMS is enabled
    $sms_host = $_POST['sms_host'] ?? null;
    $sms_port = $_POST['sms_port'] ?? null;
    
    if ($is_update) {
        // UPDATE existing patient
        $stmt = $pdo->prepare("UPDATE patients SET old_id=?, date=?, patient_name=?, sex=?, age=?, phone=?, address=?, ref_doctors=?, ref_name=?, delivery_date=?, delivery_time=?, remarks=?, less_total=?, less_percent_total=?, paid=?, send_sms=?, updated_at=? WHERE patient_id=?");
        $stmt->execute([
            $old_id, $date, $patient_name, $sex, $age, $phone, $address, $ref_doctors, $ref_name,
            $delivery_date, $delivery_time, $remarks, $less_total, $less_percent_total, $paid, $send_sms, $timestamp, $patient_id
        ]);
        
        $stmt = $pdo->prepare("DELETE FROM billing WHERE patient_id = ?");
        $stmt->execute([$patient_id]);
    } else {
        // INSERT new patient (auto-generate patient_id)
        $stmt = $pdo->prepare("INSERT INTO patients (old_id, date, patient_name, sex, age, phone, address, ref_doctors, ref_name, delivery_date, delivery_time, remarks, less_total, less_percent_total, paid, send_sms, created_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->execute([
            $old_id, $date, $patient_name, $sex, $age, $phone, $address, $ref_doctors, $ref_name,
            $delivery_date, $delivery_time, $remarks, $less_total, $less_percent_total, $paid, $send_sms, $timestamp
        ]);
        
        // Get the auto-generated patient_id
        $patient_id = $pdo->lastInsertId();
    }
    
    $service_ids = $_POST['service_id'] ?? [];
    $service_names = $_POST['service_name'] ?? [];
    $prices = $_POST['price'] ?? [];
    $units = $_POST['unit'] ?? [];
    $less_percents = $_POST['less_percent'] ?? [];
    $lesses = $_POST['less'] ?? [];
    $final_prices = $_POST['final_price'] ?? [];
    
    $stmt = $pdo->prepare("INSERT INTO billing (patient_id, service_id, service_name, price, unit, less_percent, less, final_price) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
    $total = 0;
    
    for ($i = 0; $i < count($service_ids); $i++) {
        if (empty(trim($service_names[$i]))) continue;
        $price = floatval($prices[$i] ?? 0);
        $unit = intval($units[$i] ?? 1);
        $final_price = floatval($final_prices[$i] ?? 0);
        $total += $final_price;
        
        $stmt->execute([
            $patient_id,
            $service_ids[$i] ?: null,
            $service_names[$i],
            $price,
            $unit,
            floatval($less_percents[$i] ?? 0),
            floatval($lesses[$i] ?? 0),
            $final_price,
        ]);
    }
    
    $due = $total - $less_total - $paid;
    $pdo->commit();
    
    // SEND SMS if checked
    if ($send_sms && !empty($phone) && !empty($sms_host) && !empty($sms_port)) {
        $sms_message = "Dear $patient_name, your total bill is BDT " . number_format($total, 2);
		$sms_message .= ", paid BDT " . number_format($paid, 2);
        if ($due > 0) {
            $sms_message .= " and your due is BDT " . number_format($due, 2);
        }
        $sms_message .= ". Thank you.";
        
        $data = json_encode([
            "phone" => $phone,
            "message" => $sms_message
        ]);
        
        $fp = stream_socket_client("tcp://$sms_host:$sms_port", $errno, $errstr, 5);
        if (!$fp) {
            // Don't echo error, just log it if needed
            error_log("SMS connection failed: $errstr ($errno)");
        } else {
            // Send WebSocket handshake
            fwrite($fp,
                "GET / HTTP/1.1\r\n" .
                "Host: $sms_host:$sms_port\r\n" .
                "Upgrade: websocket\r\n" .
                "Connection: Upgrade\r\n" .
                "Sec-WebSocket-Key: x3JJHMbDL1EzLkh9GBhXDw==\r\n" .
                "Sec-WebSocket-Version: 13\r\n\r\n"
            );
            
            // Read and ignore handshake response
            fread($fp, 1500);
            
            // WebSocket frame encoding
            function encode($payload) {
                $frame = [];
                $frame[0] = 0x81; // FIN + text frame
                $length = strlen($payload);
                if ($length <= 125) {
                    $frame[1] = $length;
                } elseif ($length < 65536) {
                    $frame[1] = 126;
                    $frame[] = ($length >> 8) & 255;
                    $frame[] = $length & 255;
                } else {
                    $frame[1] = 127;
                    for ($i = 7; $i >= 0; $i--) {
                        $frame[] = ($length >> ($i * 8)) & 255;
                    }
                }
                return implode(array_map("chr", $frame)) . $payload;
            }
            
            fwrite($fp, encode($data));
            fclose($fp);
        }
    }
    
    // Clean any output buffering and return JSON response
    ob_end_clean();
    header('Content-Type: application/json');
    echo json_encode([
        'success' => true, 
        'patient_id' => $patient_id,
        'message' => 'Patient information saved successfully'
    ]);
    exit;
    
} catch (PDOException $e) {
    $pdo->rollBack();
    ob_end_clean();
    http_response_code(503); // Service Unavailable for database lock errors
    header('Content-Type: application/json');
    
    // Check if it's a database lock error
    if (strpos($e->getMessage(), 'database is locked') !== false) {
        echo json_encode([
            'success' => false, 
            'message' => 'The system is busy processing other requests. Please try again.'
        ]);
    } else {
        echo json_encode([
            'success' => false, 
            'message' => 'Database error: ' . $e->getMessage()
        ]);
    }
    exit;
    
} catch (Exception $e) {
    $pdo->rollBack();
    ob_end_clean();
    http_response_code(500);
    header('Content-Type: application/json');
    echo json_encode([
        'success' => false, 
        'message' => $e->getMessage()
    ]);
    exit;
}