<?php
// Start output buffering to prevent any accidental output
date_default_timezone_set('Asia/Dhaka');
ob_start();
include 'db_sqlite.php';

// Load SMS configuration
$smsConfig = simplexml_load_file('sms_conf.xml');
$smsHost = (string)$smsConfig->sms->host;
$smsPort = (string)$smsConfig->sms->port;

// Load email configuration
$emailConfig = simplexml_load_file('email_conf.xml');
$emailHost = (string)$emailConfig->email->host;
$emailPort = (string)$emailConfig->email->port;
$emailUsername = (string)$emailConfig->email->username;
$emailPassword = (string)$emailConfig->email->password;
$emailFrom = (string)$emailConfig->email->from;
$emailFromName = (string)$emailConfig->email->from_name;


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
    $email = $_POST['email'] ?? null;
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
    $send_email = isset($_POST['send_email']) ? 1 : 0;
    $timestamp = date('Y-m-d H:i:s');
    
    // Get SMS configuration from form if SMS is enabled
    $sms_host = $_POST['sms_host'] ?? null;
    $sms_port = $_POST['sms_port'] ?? null;
    
    // Get email configuration from form if email is enabled
    $email_host = $_POST['email_host'] ?? null;
    $email_port = $_POST['email_port'] ?? null;
    $email_username = $_POST['email_username'] ?? null;
    $email_password = $_POST['email_password'] ?? null;
    $email_from = $_POST['email_from'] ?? null;
    $email_from_name = $_POST['email_from_name'] ?? null;
    
    if ($is_update) {
        // UPDATE existing patient
        $stmt = $pdo->prepare("UPDATE patients SET old_id=?, date=?, patient_name=?, sex=?, age=?, phone=?, email=?, address=?, ref_doctors=?, ref_name=?, delivery_date=?, delivery_time=?, remarks=?, less_total=?, less_percent_total=?, paid=?, send_sms=?, send_email=?, updated_at=? WHERE patient_id=?");
        $stmt->execute([
            $old_id, $date, $patient_name, $sex, $age, $phone, $email, $address, $ref_doctors, $ref_name,
            $delivery_date, $delivery_time, $remarks, $less_total, $less_percent_total, $paid, $send_sms, $send_email, $timestamp, $patient_id
        ]);
        
        $stmt = $pdo->prepare("DELETE FROM billing WHERE patient_id = ?");
        $stmt->execute([$patient_id]);
    } else {
        // INSERT new patient (auto-generate patient_id)
        $stmt = $pdo->prepare("INSERT INTO patients (old_id, date, patient_name, sex, age, phone, email, address, ref_doctors, ref_name, delivery_date, delivery_time, remarks, less_total, less_percent_total, paid, send_sms, send_email, created_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->execute([
            $old_id, $date, $patient_name, $sex, $age, $phone, $email, $address, $ref_doctors, $ref_name,
            $delivery_date, $delivery_time, $remarks, $less_total, $less_percent_total, $paid, $send_sms, $send_email, $timestamp
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
    
    // Commit the database transaction before sending notifications
    $pdo->commit();
    
    // Notification errors will be collected here
    $notification_errors = [];
    
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
        
        try {
            $fp = stream_socket_client("tcp://$sms_host:$sms_port", $errno, $errstr, 5);
            if (!$fp) {
                $notification_errors['sms'] = "SMS connection failed: $errstr ($errno)";
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
        } catch (Exception $e) {
            $notification_errors['sms'] = "SMS sending failed: " . $e->getMessage();
        }
    }
    
    // SEND EMAIL if checked
    if ($send_email && !empty($email) && !empty($email_host) && !empty($email_port) && !empty($email_username) && !empty($email_password) && !empty($email_from) && !empty($email_from_name)) {
        // Check if PHPMailer files exist
        $phpmailerPath = 'PhpMailer/src/';
        $phpMailerFiles = [
            'Exception.php',
            'PHPMailer.php',
            'SMTP.php'
        ];
        
        $filesExist = true;
        foreach ($phpMailerFiles as $file) {
            if (!file_exists($phpmailerPath . $file)) {
                $filesExist = false;
                break;
            }
        }
        
        if (!$filesExist) {
            $notification_errors['email'] = "PHPMailer files not found. Please make sure the PhpMailer/src directory exists with the required files.";
        } else {
            try {
                // Include PHPMailer files in the correct order
                require_once $phpmailerPath . 'Exception.php';
                require_once $phpmailerPath . 'SMTP.php';
                require_once $phpmailerPath . 'PHPMailer.php';
                
                // Create a new PHPMailer instance
                $mail = new PHPMailer\PHPMailer\PHPMailer(true);
                
                // Server settings
                $mail->isSMTP();
                $mail->Host = $email_host;
                $mail->Port = $email_port;
                $mail->SMTPAuth = true;
                $mail->Username = $email_username;
                $mail->Password = $email_password;
                $mail->SMTPSecure = PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_SMTPS; // SSL
                $mail->SMTPDebug = 0; // Set to 2 for debugging
                
                // Recipients
                $mail->setFrom($email_from, $email_from_name);
                $mail->addAddress($email, $patient_name);
                
                // Content
                $mail->isHTML(true);
                $mail->Subject = 'Your Clinic Bill Information';
                
                $emailBody = "
                <html>
                <head>
                    <style>
                        body { font-family: Arial, sans-serif; line-height: 1.6; }
                        .container { max-width: 600px; margin: 0 auto; padding: 20px; }
                        .header { background-color: #667eea; color: white; padding: 10px; text-align: center; }
                        .content { padding: 20px; border: 1px solid #ddd; }
                        .footer { margin-top: 20px; font-size: 12px; color: #777; text-align: center; }
                        table { width: 100%; border-collapse: collapse; margin-top: 20px; }
                        th, td { padding: 8px; text-align: left; border-bottom: 1px solid #ddd; }
                        th { background-color: #f2f2f2; }
                        .total { font-weight: bold; }
                    </style>
                </head>
                <body>
                    <div class='container'>
                        <div class='header'>
                            <h2>Your Clinic Bill</h2>
                        </div>
                        <div class='content'>
                            <p>Dear $patient_name,</p>
                            <p>Thank you for visiting our clinic. Here is your bill information:</p>
                            
                            <table>
                                <tr>
                                    <th>Description</th>
                                    <th>Amount</th>
                                </tr>
                                <tr>
                                    <td>Total Bill</td>
                                    <td>BDT " . number_format($total, 2) . "</td>
                                </tr>
                                <tr>
                                    <td>Paid</td>
                                    <td>BDT " . number_format($paid, 2) . "</td>
                                </tr>";
                
                if ($due > 0) {
                    $emailBody .= "
                                <tr>
                                    <td>Due</td>
                                    <td>BDT " . number_format($due, 2) . "</td>
                                </tr>";
                }
                
                $emailBody .= "
                            </table>
                            
                            <p>If you have any questions about your bill, please contact us.</p>
                            <p>Thank you for choosing our clinic.</p>
                        </div>
                        <div class='footer'>
                            <p>&copy; " . date('Y') . " Your Clinic Name. All rights reserved.</p>
                        </div>
                    </div>
                </body>
                </html>";
                
                $mail->Body = $emailBody;
                $mail->AltBody = "Dear $patient_name,\n\nYour total bill is BDT " . number_format($total, 2) . 
                                 ", paid BDT " . number_format($paid, 2);
                
                if ($due > 0) {
                    $mail->AltBody .= " and your due is BDT " . number_format($due, 2);
                }
                
                $mail->AltBody .= ".\n\nThank you for choosing our clinic.";
                
                $mail->send();
            } catch (Exception $e) {
                $notification_errors['email'] = "Email sending failed: " . $mail->ErrorInfo;
            }
        }
    }
    
    // Clean any output buffering and return JSON response
    ob_end_clean();
    header('Content-Type: application/json');
    
    $response = [
        'success' => true, 
        'patient_id' => $patient_id,
        'message' => 'Patient information saved successfully'
    ];
    
    // Add notification errors if any
    if (!empty($notification_errors)) {
        $response['notification_errors'] = $notification_errors;
    }
    
    echo json_encode($response);
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