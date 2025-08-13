<?php
// Set timezone to Asia/Dhaka
date_default_timezone_set('Asia/Dhaka');

// Load email configuration
$emailConfig = simplexml_load_file('email_conf.xml');
$emailHost = (string)$emailConfig->email->host;
$emailPort = (string)$emailConfig->email->port;
$emailUsername = (string)$emailConfig->email->username;
$emailPassword = (string)$emailConfig->email->password;
$emailFrom = (string)$emailConfig->email->from;
$emailFromName = (string)$emailConfig->email->from_name;

// Process form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $toEmail = $_POST['email'] ?? '';
    $subject = $_POST['subject'] ?? 'Test Email from Clinic Billing System';
    $message = $_POST['message'] ?? 'This is a test email from the Clinic Billing System.';
    
    $result = '';
    
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
        $result = "PHPMailer files not found. Please make sure the PhpMailer/src directory exists with the required files.";
    } else {
        try {
            // Include PHPMailer files
            require_once $phpmailerPath . 'Exception.php';
            require_once $phpmailerPath . 'PHPMailer.php';
            require_once $phpmailerPath . 'SMTP.php';
            
            // Create a new PHPMailer instance
            $mail = new PHPMailer\PHPMailer\PHPMailer(true);
            
            // Server settings
            $mail->isSMTP();
            $mail->Host = $emailHost;
            $mail->Port = $emailPort;
            $mail->SMTPAuth = true;
            $mail->Username = $emailUsername;
            $mail->Password = $emailPassword;
            $mail->SMTPSecure = PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_SMTPS; // SSL
            $mail->SMTPDebug = 0; // Set to 2 for debugging
            
            // Recipients
            $mail->setFrom($emailFrom, $emailFromName);
            $mail->addAddress($toEmail);
            
            // Content
            $mail->isHTML(true);
            $mail->Subject = $subject;
            
            $emailBody = "
            <html>
            <head>
                <style>
                    body { font-family: Arial, sans-serif; line-height: 1.6; }
                    .container { max-width: 600px; margin: 0 auto; padding: 20px; }
                    .header { background-color: #667eea; color: white; padding: 10px; text-align: center; }
                    .content { padding: 20px; border: 1px solid #ddd; }
                    .footer { margin-top: 20px; font-size: 12px; color: #777; text-align: center; }
                </style>
            </head>
            <body>
                <div class='container'>
                    <div class='header'>
                        <h2>Test Email</h2>
                    </div>
                    <div class='content'>
                        <p>$message</p>
                    </div>
                    <div class='footer'>
                        <p>&copy; " . date('Y') . " Clinic Billing System. All rights reserved.</p>
                    </div>
                </div>
            </body>
            </html>";
            
            $mail->Body = $emailBody;
            $mail->AltBody = strip_tags($message);
            
            $mail->send();
            $result = "Email sent successfully!";
        } catch (Exception $e) {
            $result = "Email sending failed: " . $mail->ErrorInfo;
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Email Test - Clinic Billing System</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body {
            background-color: #f8f9fa;
            padding: 20px;
        }
        .container {
            max-width: 800px;
            margin: 0 auto;
            background-color: white;
            padding: 30px;
            border-radius: 10px;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
        }
        .form-group {
            margin-bottom: 20px;
        }
        .result {
            margin-top: 20px;
            padding: 15px;
            border-radius: 5px;
        }
        .success {
            background-color: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }
        .error {
            background-color: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }
        .config-info {
            background-color: #e9ecef;
            padding: 15px;
            border-radius: 5px;
            margin-bottom: 20px;
        }
        .config-info h5 {
            margin-bottom: 15px;
        }
        .config-info table {
            width: 100%;
        }
        .config-info th {
            text-align: left;
            width: 30%;
        }
        .file-check {
            background-color: #fff3cd;
            color: #856404;
            border: 1px solid #ffeeba;
            padding: 15px;
            border-radius: 5px;
            margin-bottom: 20px;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1 class="mb-4">Email Test</h1>
        
        <div class="config-info">
            <h5>Current Email Configuration</h5>
            <table>
                <tr>
                    <th>SMTP Host:</th>
                    <td><?= htmlspecialchars($emailHost) ?></td>
                </tr>
                <tr>
                    <th>SMTP Port:</th>
                    <td><?= htmlspecialchars($emailPort) ?></td>
                </tr>
                <tr>
                    <th>Username:</th>
                    <td><?= htmlspecialchars($emailUsername) ?></td>
                </tr>
                <tr>
                    <th>From Email:</th>
                    <td><?= htmlspecialchars($emailFrom) ?></td>
                </tr>
                <tr>
                    <th>From Name:</th>
                    <td><?= htmlspecialchars($emailFromName) ?></td>
                </tr>
            </table>
        </div>
        
        <div class="file-check">
            <h5>PHPMailer Files Check</h5>
            <p>Checking for required PHPMailer files in the PhpMailer/src/ directory:</p>
            <ul>
                <li>Exception.php: <?= file_exists('PhpMailer/src/Exception.php') ? '<span class="text-success">Found</span>' : '<span class="text-danger">Not Found</span>' ?></li>
                <li>PHPMailer.php: <?= file_exists('PhpMailer/src/PHPMailer.php') ? '<span class="text-success">Found</span>' : '<span class="text-danger">Not Found</span>' ?></li>
                <li>SMTP.php: <?= file_exists('PhpMailer/src/SMTP.php') ? '<span class="text-success">Found</span>' : '<span class="text-danger">Not Found</span>' ?></li>
            </ul>
            <?php if (!file_exists('PhpMailer/src/Exception.php') || !file_exists('PhpMailer/src/PHPMailer.php') || !file_exists('PhpMailer/src/SMTP.php')): ?>
                <div class="alert alert-danger mt-3">
                    <strong>Missing PHPMailer Files!</strong><br>
                    Please download the latest version of PHPMailer from <a href="https://github.com/PHPMailer/PHPMailer" target="_blank">GitHub</a> and extract it to a directory named "PhpMailer" in your project root.
                </div>
            <?php endif; ?>
        </div>
        
        <?php if (isset($result)): ?>
            <div class="result <?= strpos($result, 'successfully') !== false ? 'success' : 'error' ?>">
                <?= htmlspecialchars($result) ?>
            </div>
        <?php endif; ?>
        
        <form method="post" action="">
            <div class="form-group">
                <label for="email">To Email:</label>
                <input type="email" class="form-control" id="email" name="email" required>
            </div>
            <div class="form-group">
                <label for="subject">Subject:</label>
                <input type="text" class="form-control" id="subject" name="subject" value="Test Email from Clinic Billing System">
            </div>
            <div class="form-group">
                <label for="message">Message:</label>
                <textarea class="form-control" id="message" name="message" rows="5">This is a test email from the Clinic Billing System.</textarea>
            </div>
            <button type="submit" class="btn btn-primary">Send Test Email</button>
        </form>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>