<?php
// Set timezone to Asia/Dhaka
date_default_timezone_set('Asia/Dhaka');
// For demo, enable PHP error reporting (disable in production)
error_reporting(E_ALL);
ini_set('display_errors', 1);
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
// Check if user_conf.xml exists, if not create it with default credentials
$userConfFile = 'user_conf.xml';
if (!file_exists($userConfFile)) {
    $defaultUser = 'admin';
    $defaultPass = password_hash('admin123', PASSWORD_DEFAULT); // Using password_hash for security
    $xml = new SimpleXMLElement('<config/>');
    $xml->addChild('username', $defaultUser);
    $xml->addChild('password', $defaultPass);
    $xml->asXML($userConfFile);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Investigation Billing</title>
  <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css" />
  <link rel="stylesheet" href="https://code.jquery.com/ui/1.12.1/themes/base/jquery-ui.css" />
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.1/css/all.min.css" />
  <style>
    /* All existing CSS styles remain the same */
    :root {
      --bg-color: #f8f9fa;
      --text-color: #000;
      --card-bg: #ffffff;
      --card-shadow: rgba(0, 0, 0, 0.1);
      --input-bg: #ffffff;
      --input-border: #ced4da;
      --input-focus-border: #667eea;
      --input-focus-shadow: rgba(102, 126, 234, 0.25);
      --input-focus-bg: #ffffff;
      --readonly-bg: #e9ecef;
      --readonly-text: #000;
      --table-header-bg: #6c757d;
      --table-header-text: white;
      --table-hover: #f1f3f5;
      --summary-bg: #f8f9fa;
      --footer-text: #000;
      --clock-text: #ffffff;
      --table-footer-bg: #e9ecef;
      --table-footer-text: #000;
    }
    
    [data-theme="dark"] {
      --bg-color: #1a1a1a;
      --text-color: #e0e0e0;
      --card-bg: #2d2d2d;
      --card-shadow: rgba(0, 0, 0, 0.3);
      --input-bg: #3a3a3a;
      --input-border: #555;
      --input-focus-border: #8a9cff;
      --input-focus-shadow: rgba(138, 156, 255, 0.25);
      --input-focus-bg: #3a3a3a;
      --readonly-bg: #2a2a2a;
      --readonly-text: #e0e0e0;
      --table-header-bg: #444;
      --table-header-text: #e0e0e0;
      --table-hover: #333;
      --summary-bg: #2d2d2d;
      --footer-text: #a0a0a0;
      --clock-text: #ffffff;
      --table-footer-bg: #3a3a3a;
      --table-footer-text: #e0e0e0;
    }
    
    body {
      background-color: var(--bg-color);
      font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
      color: var(--text-color);
      transition: background-color 0.3s, color 0.3s;
    }
    
    .form-section {
      background-color: var(--card-bg);
      padding: 25px;
      border-radius: 10px;
      box-shadow: 0 4px 12px var(--card-shadow);
      margin-bottom: 30px;
      transition: background-color 0.3s, box-shadow 0.3s;
    }
    
    .title-bar {
      background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
      color: white;
      padding: 4px;
      text-align: center;
      font-weight: bold;
      font-size: 1.8rem;
      border-radius: 10px;
      margin-bottom: 30px;
      box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
      position: relative;
      display: flex;
      justify-content: space-between;
      align-items: center;
      padding-left: 20px;
      padding-right: 20px;
    }
    
    .title-center {
      flex-grow: 1;
      text-align: center;
    }
    
    .live-clock {
      font-size: 1.5rem;
      font-weight: normal;
      color: var(--clock-text);
    }
    
    .theme-toggle {
      display: flex;
      align-items: center;
    }
    
    .theme-toggle-label {
      margin-right: 10px;
      font-size: 0.9rem;
    }
    
    .dashboard-link {
      color: white;
      font-size: 1.5rem;
      margin-right: 15px;
    }
    
    .dashboard-link:hover {
      color: #f0f0f0;
    }
    
    .table th, .table td {
      padding: .3rem;
      vertical-align: middle;
      color: var(--text-color);
      transition: color 0.3s;
    }
    
    .table thead th {
      background-color: var(--table-header-bg);
      color: var(--table-header-text);
      border: none;
      transition: background-color 0.3s, color 0.3s;
    }
    
    .table-hover tbody tr:hover {
      background-color: var(--table-hover);
      transition: background-color 0.3s;
    }
    
    .table tfoot td {
      background-color: var(--table-footer-bg);
      color: var(--table-footer-text);
      font-weight: 600;
      border-top: 2px solid var(--input-border);
      transition: background-color 0.3s, color 0.3s;
    }
    
    .form-control {
      background-color: var(--input-bg);
      color: var(--text-color);
      border-color: var(--input-border);
      transition: background-color 0.3s, color 0.3s, border-color 0.3s;
    }
    
    .form-control:focus {
      background-color: var(--input-focus-bg) !important;
      color: var(--text-color) !important;
      border-color: var(--input-focus-border) !important;
      box-shadow: 0 0 0 0.2rem var(--input-focus-shadow) !important;
    }
    
    /* Specific styles for readonly inputs */
    .form-control[readonly] {
      background-color: var(--readonly-bg) !important;
      color: var(--readonly-text) !important;
    }
    
    .form-control::placeholder {
      color: #adb5bd;
      opacity: 1;
    }
    
    [data-theme="dark"] .form-control::placeholder {
      color: #6c757d;
    }
    
    .btn-primary {
      background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
      border: none;
      padding: 10px 25px;
      font-weight: 600;
      color: white;
    }
    
    .btn-primary:hover {
      background: linear-gradient(135deg, #5a6fd8 0%, #6a4190 100%);
    }
    
    .btn-success {
      background: linear-gradient(135deg, #11998e 0%, #38ef7d 100%);
      border: none;
      color: white;
    }
    
    .btn-danger {
      background: linear-gradient(135deg, #eb3349 0%, #f45c43 100%);
      border: none;
      color: white;
    }
    
    .btn-info {
      background: linear-gradient(135deg, #2196F3 0%, #21CBF3 100%);
      border: none;
      color: white;
    }
    
    .btn-secondary {
      background: linear-gradient(135deg, #6c757d 0%, #5a6268 100%);
      border: none;
      color: white;
    }
    
    .btn-warning {
      background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);
      border: none;
      color: white;
    }
    
    .btn-sm {
      padding: 0.25rem 0.5rem;
      font-size: 0.75rem;
      line-height: 1.2;
      border-radius: 0.2rem;
    }
    
    .card {
      border: none;
      border-radius: 10px;
      box-shadow: 0 4px 12px rgba(0, 0, 0, 0.08);
      margin-bottom: 25px;
      background-color: var(--card-bg);
      transition: background-color 0.3s;
    }
    
    .card-header {
      background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
      color: white;
      font-weight: 600;
      border-radius: 10px 10px 0 0 !important;
      padding: 12px 20px;
    }
    
    .summary-box {
      background-color: var(--summary-bg);
      border-radius: 10px;
      padding: 20px;
      box-shadow: 0 4px 12px rgba(0, 0, 0, 0.05);
      transition: background-color 0.3s;
    }
    
    .summary-box .form-control[readonly] {
      background-color: var(--readonly-bg) !important;
      font-weight: 600;
      color: var(--readonly-text) !important;
      transition: background-color 0.3s, color 0.3s;
    }
    
    .form-group label {
      font-weight: 600;
      color: var(--text-color);
      margin-bottom: 8px;
      transition: color 0.3s;
    }
    
    .ui-autocomplete {
      max-height: 200px;
      overflow-y: auto;
      overflow-x: hidden;
      border-radius: 8px;
      box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
      background-color: var(--card-bg);
      color: var(--text-color);
    }
    
    .service-table-container {
      border-radius: 10px;
      overflow: hidden;
      box-shadow: 0 4px 12px rgba(0, 0, 0, 0.08);
    }
    
    .action-buttons {
      display: flex;
      justify-content: center;
      gap: 15px;
      margin-top: 30px;
    }
    
    .action-buttons .btn {
      min-width: 120px;
      padding: 12px;
      font-weight: 600;
      border-radius: 8px;
    }
    
    .form-row .form-group {
      margin-bottom: 20px;
    }
    
    .custom-switch {
      padding-left: 30px;
    }
    
    .custom-switch .custom-control-label {
      font-weight: 500;
      color: var(--text-color);
      transition: color 0.3s;
    }
    
    .custom-control-input:checked ~ .custom-control-label::before {
      background-color: #667eea;
      border-color: #667eea;
    }
    
    .age-inputs .form-control {
      text-align: center;
      color: var(--text-color);
    }
    
    .age-inputs .form-control::placeholder {
      text-align: center;
      font-weight: 600;
      color: #6c757d;
    }
    
    .table-responsive {
      border-radius: 10px;
      overflow: hidden;
    }
    
    .footer-note {
      font-size: 0.85rem;
      color: var(--footer-text);
      font-style: italic;
      margin-top: 20px;
      text-align: center;
      transition: color 0.3s;
    }
    
    .edit-mode-container {
      display: flex;
      align-items: center;
      margin-top: 10px;
    }
    
    .edit-mode-label {
      margin-right: 15px;
      font-weight: 600;
      color: var(--text-color);
      transition: color 0.3s;
    }
    
    .toggle-switch {
      position: relative;
      display: inline-block;
      width: 60px;
      height: 34px;
    }
    
    .toggle-switch input {
      opacity: 0;
      width: 0;
      height: 0;
    }
    
    .slider {
      position: absolute;
      cursor: pointer;
      top: 0;
      left: 0;
      right: 0;
      bottom: 0;
      background-color: #ccc;
      transition: .4s;
      border-radius: 34px;
    }
    
    .slider:before {
      position: absolute;
      content: "";
      height: 26px;
      width: 26px;
      left: 4px;
      bottom: 4px;
      background-color: white;
      transition: .4s;
      border-radius: 50%;
    }
    
    input:checked + .slider {
      background-color: #667eea;
    }
    
    input:checked + .slider:before {
      transform: translateX(26px);
    }
    
    .patient-id-group {
      display: flex;
      flex-direction: column;
    }
    
    .patient-id-controls {
      display: flex;
      align-items: center;
      margin-top: 10px;
      position: relative;
      z-index: 5;
    }
    
    .patient-id-controls .toggle-container {
      display: flex;
      align-items: center;
      margin-right: 15px;
    }
    
    .patient-id-controls .update-btn-container {
      margin-left: auto;
      position: relative;
      z-index: 10;
      display: flex;
      gap: 10px;
    }
    
    /* Loading and retry styles */
    .loading-overlay {
      position: fixed;
      top: 0;
      left: 0;
      width: 100%;
      height: 100%;
      background-color: rgba(0, 0, 0, 0.7);
      z-index: 9999;
      display: flex;
      justify-content: center;
      align-items: center;
      flex-direction: column;
    }
    
    .loading-spinner {
      width: 50px;
      height: 50px;
      border: 5px solid #f3f3f3;
      border-top: 5px solid #667eea;
      border-radius: 50%;
      animation: spin 1s linear infinite;
      margin-bottom: 15px;
    }
    
    @keyframes spin {
      0% { transform: rotate(0deg); }
      100% { transform: rotate(360deg); }
    }
    
    .loading-text {
      color: white;
      font-size: 18px;
      font-weight: bold;
    }
    
    .retry-container {
      display: none;
      background-color: #f8d7da;
      border: 1px solid #f5c6cb;
      border-radius: 5px;
      padding: 15px;
      margin-bottom: 20px;
    }
    
    [data-theme="dark"] .retry-container {
      background-color: #721c24;
      border: 1px solid #a71e2a;
    }
    
    .retry-container h5 {
      color: #721c24;
      margin-bottom: 10px;
    }
    
    [data-theme="dark"] .retry-container h5,
    [data-theme="dark"] .retry-container p {
      color: #f8d7da;
    }
    
    .retry-container p {
      color: #721c24;
      margin-bottom: 15px;
    }
    
    .retry-buttons {
      display: flex;
      gap: 10px;
    }
    
    .btn-retry {
      background-color: #28a745;
      color: white;
    }
    
    .btn-cancel {
      background-color: #6c757d;
      color: white;
    }
    
    .sms-config {
      display: none;
      background-color: var(--summary-bg);
      border: 1px solid var(--input-border);
      border-radius: 5px;
      padding: 10px;
      margin-top: 10px;
      transition: background-color 0.3s, border-color 0.3s;
    }
    
    .sms-config-row {
      display: flex;
      gap: 10px;
    }
    
    .sms-config .form-group {
      margin-bottom: 0;
      flex: 1;
    }
    
    /* Email configuration styles */
    .email-config {
      display: none;
      background-color: var(--summary-bg);
      border: 1px solid var(--input-border);
      border-radius: 5px;
      padding: 10px;
      margin-top: 10px;
      transition: background-color 0.3s, border-color 0.3s;
    }
    
    .email-config-row {
      display: flex;
      gap: 10px;
    }
    
    .email-config .form-group {
      margin-bottom: 0;
      flex: 1;
    }
    
    /* Dark mode toggle switch */
    .dark-mode-toggle {
      position: relative;
      display: inline-block;
      width: 50px;
      height: 24px;
    }
    
    .dark-mode-toggle input {
      opacity: 0;
      width: 0;
      height: 0;
    }
    
    .dark-mode-slider {
      position: absolute;
      cursor: pointer;
      top: 0;
      left: 0;
      right: 0;
      bottom: 0;
      background-color: #ccc;
      transition: .4s;
      border-radius: 24px;
    }
    
    .dark-mode-slider:before {
      position: absolute;
      content: "";
      height: 16px;
      width: 16px;
      left: 4px;
      bottom: 4px;
      background-color: white;
      transition: .4s;
      border-radius: 50%;
    }
    
    input:checked + .dark-mode-slider {
      background-color: #667eea;
    }
    
    input:checked + .dark-mode-slider:before {
      transform: translateX(26px);
    }
    
    /* Dark mode icon */
    .theme-icon {
      margin-right: 5px;
    }
    
    /* Fix for select dropdown in dark mode */
    [data-theme="dark"] select.form-control option {
      background-color: #3a3a3a;
      color: #e0e0e0;
    }
    
    /* Fix for jQuery UI autocomplete in dark mode */
    .ui-menu-item {
      background-color: var(--card-bg);
      color: var(--text-color);
    }
    
    .ui-menu-item:hover {
      background-color: var(--table-hover);
    }
    
    /* Custom styles for payable and due fields */
    #payable.form-control.payable-blue,
    #payable.form-control.payable-blue[readonly] {
      background-color: #007bff !important;
      color: white !important;
      font-weight: 600;
    }
    
    #due.form-control.due-red,
    #due.form-control.due-red[readonly] {
      background-color: #dc3545 !important;
      color: white !important;
      font-weight: 600;
    }
    
    /* Additional override for dark mode */
    [data-theme="dark"] #payable.form-control.payable-blue,
    [data-theme="dark"] #payable.form-control.payable-blue[readonly] {
      background-color: #0056b3 !important;
      color: white !important;
    }
    
    [data-theme="dark"] #due.form-control.due-red,
    [data-theme="dark"] #due.form-control.due-red[readonly] {
      background-color: #a02622 !important;
      color: white !important;
    }
    
    /* Fix for update button not being clickable in mobile browsers */
    #update-btn, #print-btn, #delete-btn {
      position: relative;
      z-index: 15;
      pointer-events: auto;
      touch-action: manipulation;
    }
    
    /* Adjust column widths for summary section */
    .summary-box .form-group {
      flex: 0 0 auto;
      width: 16.666667%;
    }
    
    @media (max-width: 768px) {
      .summary-box .form-group {
        width: 50%;
      }
    }
    
    /* Remove increment/decrement arrows from number inputs */
    input[type="number"]::-webkit-outer-spin-button,
    input[type="number"]::-webkit-inner-spin-button {
      -webkit-appearance: none;
      margin: 0;
    }
    
    input[type="number"] {
      -moz-appearance: textfield;
    }
    
    /* Keep arrows for Patient ID and Old ID */
    #patient_id::-webkit-outer-spin-button,
    #patient_id::-webkit-inner-spin-button,
    #old_id::-webkit-outer-spin-button,
    #old_id::-webkit-inner-spin-button {
      -webkit-appearance: auto;
      margin: 0;
    }
    
    #patient_id,
    #old_id {
      -moz-appearance: number-input;
    }
    
    /* Wider service name column */
    #service-table th:nth-child(2),
    #service-table td:nth-child(2) {
      min-width: 250px;
      max-width: 300px;
    }
    
    /* Toast notification styles */
    .toast-container {
      position: fixed;
      top: 20px;
      right: 20px;
      z-index: 9999;
    }
    
    .toast {
      background-color: #28a745;
      color: white;
      padding: 15px 25px;
      border-radius: 5px;
      box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
      margin-bottom: 10px;
      opacity: 0;
      transform: translateY(-20px);
      transition: opacity 0.3s, transform 0.3s;
    }
    
    .toast.error {
      background-color: #dc3545;
    }
    
    .toast.warning {
      background-color: #ffc107;
      color: #212529;
    }
    
    .toast.show {
      opacity: 1;
      transform: translateY(0);
    }
    
    .toast-header {
      display: flex;
      align-items: center;
      justify-content: space-between;
      margin-bottom: 5px;
    }
    
    .toast-title {
      font-weight: bold;
    }
    
    .toast-close {
      background: none;
      border: none;
      color: white;
      font-size: 1.2rem;
      cursor: pointer;
      opacity: 0.7;
    }
    
    .toast-close:hover {
      opacity: 1;
    }
    
    /* Delete confirmation modal */
    .modal-content {
      color: var(--text-color);
      background-color: var(--card-bg);
    }
    
    .modal-header, .modal-footer {
      border-color: var(--input-border);
    }
    
    .modal-title {
      color: #dc3545;
    }
    
    .modal-body {
      color: var(--text-color);
      font-size: 16px;
    }
    
    .modal-footer .btn {
      color: white;
    }
    
    /* Auto-save indicator */
    .auto-save-indicator {
      position: fixed;
      bottom: 20px;
      right: 20px;
      background-color: rgba(40, 167, 69, 0.9);
      color: white;
      padding: 8px 15px;
      border-radius: 4px;
      font-size: 14px;
      z-index: 1000;
      display: none;
      box-shadow: 0 2px 10px rgba(0, 0, 0, 0.2);
    }
    
    .auto-save-indicator.show {
      display: block;
      animation: fadeInOut 2s ease-in-out;
    }
    
    @keyframes fadeInOut {
      0% { opacity: 0; }
      20% { opacity: 1; }
      80% { opacity: 1; }
      100% { opacity: 0; }
    }
    
    /* SMS Server Status Indicator */
    .sms-status-indicator {
      margin-top: 10px;
      padding: 8px 12px;
      border-radius: 5px;
      background-color: var(--summary-bg);
      border: 1px solid var(--input-border);
      transition: background-color 0.3s, border-color 0.3s;
    }
    
    .status-circle {
      width: 12px;
      height: 12px;
      border-radius: 50%;
      display: inline-block;
    }
    
    .status-online {
      background-color: #28a745;
      box-shadow: 0 0 5px #28a745;
    }
    
    .status-offline {
      background-color: #dc3545;
      box-shadow: 0 0 5px #dc3545;
    }
    
    .status-checking {
      background-color: #ffc107;
      box-shadow: 0 0 5px #ffc107;
      animation: pulse 1.5s infinite;
    }
    
    @keyframes pulse {
      0% { opacity: 1; }
      50% { opacity: 0.5; }
      100% { opacity: 1; }
    }
    
    #refreshSmsStatus {
      padding: 0.15rem 0.5rem;
      font-size: 0.75rem;
    }
  </style>
</head>
<body>
<div class="container mt-4">
  <div class="title-bar">
    <div class="live-clock" id="liveClock"></div>
    <div class="title-center">
      <i class="fas fa-file-medical-alt mr-2"></i> INVESTIGATION BILLING
    </div>
    <div class="theme-toggle">
      <a href="index.php" class="dashboard-link" title="Dashboard">
        <i class="fas fa-tachometer-alt"></i>
      </a>
      <span class="theme-toggle-label"><i class="fas fa-sun theme-icon"></i> Light</span>
      <label class="dark-mode-toggle">
        <input type="checkbox" id="darkModeToggle">
        <span class="dark-mode-slider"></span>
      </label>
      <span class="theme-toggle-label"><i class="fas fa-moon theme-icon"></i> Dark</span>
    </div>
  </div>
  
  <!-- Retry Container (initially hidden) -->
  <div class="retry-container" id="retryContainer">
    <h5><i class="fas fa-exclamation-triangle mr-2"></i> Submission Failed</h5>
    <p id="retryMessage">The system is busy processing other requests. Please try again.</p>
    <div class="retry-buttons">
      <button type="button" class="btn btn-retry" id="retryBtn">
        <i class="fas fa-redo mr-1"></i> Retry
      </button>
      <button type="button" class="btn btn-cancel" id="cancelRetryBtn">
        <i class="fas fa-times mr-1"></i> Cancel
      </button>
    </div>
  </div>
  
  <form action="save.php" method="post" class="form-section" id="billing-form">
    <!-- Patient Information Card -->
    <div class="card">
      <div class="card-header">
        <i class="fas fa-user-injured mr-2"></i> Patient Information
      </div>
      <div class="card-body">
        <div class="form-row">
          <div class="form-group col-md-3 patient-id-group">
            <label for="patient_id">Patient ID</label>
            <input type="text" name="patient_id" id="patient_id" class="form-control" readonly />
            
            <!-- Patient ID Controls -->
            <div class="patient-id-controls">
              <div class="toggle-container">
                <span class="edit-mode-label">Edit Mode:</span>
                <label class="toggle-switch">
                  <input type="checkbox" id="edit_patient_id">
                  <span class="slider"></span>
                </label>
              </div>
              
              <div class="update-btn-container">
                <button type="button" class="btn btn-info btn-sm" id="print-btn" style="display:none;">
                  <i class="fas fa-print mr-1"></i> PRINT
                </button>
                <button type="button" class="btn btn-danger btn-sm" id="delete-btn" style="display:none;">
                  <i class="fas fa-trash-alt mr-1"></i> DELETE
                </button>
                <button type="button" class="btn btn-warning btn-sm" id="update-btn" style="display:none;">
                  <i class="fas fa-save mr-1"></i> UPDATE
                </button>
              </div>
            </div>
          </div>
          <div class="form-group col-md-3">
            <label for="old_id">Old ID</label>
            <input type="text" name="old_id" id="old_id" class="form-control" />
          </div>
          <div class="form-group col-md-3">
            <label for="date">Date</label>
            <input type="date" name="date" class="form-control" value="<?= date('Y-m-d') ?>" />
          </div>
          <div class="form-group col-md-3">
            <label for="phone">Phone</label>
            <input type="tel" name="phone" id="phone" class="form-control" required pattern="[0-9\+]+" title="Enter a valid phone number" />
          </div>
        </div>
        
        <div class="form-row">
          <div class="form-group col-md-4">
            <label for="patient_name">Patient Name</label>
            <input type="text" name="patient_name" id="patient_name" class="form-control capitalize-input" required pattern="[A-Za-z0-9\s]+" title="Only English letters and numbers allowed" />
          </div>
          <div class="form-group col-md-2">
            <label for="sex">Sex</label>
            <select name="sex" id="sex" class="form-control" required>
              <option value="">Select</option>
              <option>Male</option>
              <option>Female</option>
            </select>
          </div>
          <div class="form-group col-md-3">
            <label>Age</label>
            <div class="d-flex age-inputs">
              <input type="number" name="age_year" class="form-control mr-1" placeholder="Y" min="0" />
              <input type="number" name="age_month" class="form-control mr-1" placeholder="M" min="0" max="11" />
              <input type="number" name="age_day" class="form-control" placeholder="D" min="0" max="30" />
            </div>
          </div>
          <div class="form-group col-md-3">
            <label for="email">Email</label>
            <input type="email" name="email" id="email" class="form-control" />
          </div>
        </div>
        
        <div class="form-row">
          <div class="form-group col-md-12">
            <label for="address">Address</label>
            <input type="text" name="address" id="address" class="form-control capitalize-input" pattern="[A-Za-z0-9\s,.-]+" title="Only English characters allowed" />
          </div>
        </div>
      </div>
    </div>
    
    <!-- Referral & Delivery Card -->
    <div class="card">
      <div class="card-header">
        <i class="fas fa-user-md mr-2"></i> Referral & Delivery Information
      </div>
      <div class="card-body">
        <div class="form-row">
          <div class="form-group col-md-4">
            <label for="ref_doctors">Ref. Doctors</label>
            <input type="text" name="ref_doctors" id="ref_doctors" class="form-control capitalize-input" />
          </div>
          <div class="form-group col-md-4">
            <label for="ref_name">Ref Name</label>
            <input type="text" name="ref_name" id="ref_name" class="form-control capitalize-input" />
          </div>
          <div class="form-group col-md-2">
            <label for="delivery_date">Delivery Date</label>
            <input type="date" name="delivery_date" class="form-control" value="<?= date('Y-m-d') ?>" />
          </div>
          <div class="form-group col-md-2">
            <label for="delivery_time">Time</label>
            <input type="time" name="delivery_time" class="form-control" value="20:00" />
          </div>
        </div>
      </div>
    </div>
    
    <!-- Services Card -->
    <div class="card">
      <div class="card-header">
        <i class="fas fa-procedures mr-2"></i> Services & Tests
      </div>
      <div class="card-body">
        <div class="service-table-container">
          <div class="table-responsive">
            <table class="table table-hover table-bordered" id="service-table">
              <thead>
                <tr>
                  <th>Service ID</th>
                  <th>Service Name</th>
                  <th>Price</th>
                  <th>Unit</th>
                  <th>Less</th>
                  <th>Less(%)</th>
                  <th>Final Price</th>
                  <th>Action</th>
                </tr>
              </thead>
              <tbody>
                <tr>
                  <td><input type="text" name="service_id[]" class="form-control" readonly /></td>
                  <td><input type="text" name="service_name[]" class="form-control" /></td>
                  <td><input type="number" name="price[]" class="form-control price" step="0.01" min="0" /></td>
                  <td><input type="number" name="unit[]" class="form-control unit" value="1" min="0" /></td>
                  <td><input type="number" name="less[]" class="form-control less" step="0.01" min="0" /></td>
                  <td><input type="number" name="less_percent[]" class="form-control less-percent" step="0.01" min="0" max="100" /></td>
                  <td><input type="number" name="final_price[]" class="form-control final-price" step="0.01" readonly /></td>
                  <td><button type="button" class="btn btn-danger btn-sm remove-row"><i class="fas fa-trash-alt"></i></button></td>
                </tr>
              </tbody>
              <tfoot>
                <tr>
                  <td colspan="6" class="text-right"><strong>Total:</strong></td>
                  <td><input type="text" id="table-total" class="form-control" readonly /></td>
                  <td></td>
                </tr>
              </tfoot>
            </table>
          </div>
        </div>
        <div class="text-center mt-3">
          <button type="button" class="btn btn-info" id="add-row">
            <i class="fas fa-plus-circle mr-1"></i> Add Service
          </button>
        </div>
      </div>
    </div>
    
    <!-- Summary Card -->
    <div class="card">
      <div class="card-header">
        <i class="fas fa-calculator mr-2"></i> Billing Summary
      </div>
      <div class="card-body">
        <div class="summary-box">
          <div class="form-row">
            <div class="form-group">
              <label for="total">Total</label>
              <input type="text" name="total" id="total" class="form-control" readonly />
            </div>
            <div class="form-group">
              <label for="less_total">Less</label>
              <input type="number" name="less_total" id="less_total" class="form-control summary-less" value="0" step="0.01" min="0" />
            </div>
            <div class="form-group">
              <label for="less_percent_total">Less(%)</label>
              <input type="number" name="less_percent_total" id="less_percent_total" class="form-control summary-less-percent" value="0" step="0.01" min="0" max="100" />
            </div>
            <div class="form-group">
              <label for="payable">Payable</label>
              <input type="text" name="payable" id="payable" class="form-control payable-blue" readonly />
            </div>
            <div class="form-group">
              <label for="paid">Paid</label>
              <input type="number" name="paid" id="paid" class="form-control bg-success text-white" value="0" step="0.01" min="0" />
            </div>
            <div class="form-group">
              <label for="due">Due</label>
              <input type="text" name="due" id="due" class="form-control due-red" readonly />
            </div>
          </div>
        </div>
      </div>
    </div>
    
    <!-- Remarks & SMS Card -->
    <div class="card">
      <div class="card-header">
        <i class="fas fa-comment-alt mr-2"></i> Additional Information
      </div>
      <div class="card-body">
        <div class="form-row align-items-center">
          <div class="form-group col-md-8">
            <label for="remarks">Remarks</label>
            <input type="text" name="remarks" class="form-control capitalize-input" />
          </div>
          <div class="form-group col-md-4">
            <div class="custom-control custom-switch custom-switch-lg mt-4">
              <input type="checkbox" class="custom-control-input" id="send_sms" name="send_sms">
              <label class="custom-control-label" for="send_sms">
                <i class="fas fa-sms mr-1"></i> Send SMS Notification
              </label>
            </div>
            
            <!-- SMS Server Status Indicator -->
            <div class="sms-status-indicator" id="smsStatusIndicator" style="display: none;">
              <div class="d-flex align-items-center">
                <span class="mr-2">SMS Server Status:</span>
                <div class="status-circle" id="smsStatusCircle"></div>
                <span id="smsStatusText" class="ml-2"></span>
                <button type="button" class="btn btn-sm btn-outline-secondary ml-2" id="refreshSmsStatus">
                  <i class="fas fa-sync-alt"></i>
                </button>
              </div>
            </div>
            
            <!-- SMS Configuration Fields (initially hidden) -->
            <div class="sms-config" id="smsConfig">
              <div class="sms-config-row">
                <div class="form-group">
                  <label for="sms_host">SMS Host</label>
                  <input type="text" name="sms_host" id="sms_host" class="form-control config-field" data-config-type="sms" data-config-field="host" value="<?= htmlspecialchars($smsHost) ?>" />
                </div>
                <div class="form-group">
                  <label for="sms_port">SMS Port</label>
                  <input type="text" name="sms_port" id="sms_port" class="form-control config-field" data-config-type="sms" data-config-field="port" value="<?= htmlspecialchars($smsPort) ?>" />
                </div>
              </div>
            </div>
            
            <div class="custom-control custom-switch custom-switch-lg mt-3">
              <input type="checkbox" class="custom-control-input" id="send_email" name="send_email">
              <label class="custom-control-label" for="send_email">
                <i class="fas fa-envelope mr-1"></i> Send Email Notification
              </label>
            </div>
            
            <!-- Email Configuration Fields (initially hidden) -->
            <div class="email-config" id="emailConfig">
              <div class="email-config-row">
                <div class="form-group">
                  <label for="email_host">SMTP Host</label>
                  <input type="text" name="email_host" id="email_host" class="form-control config-field" data-config-type="email" data-config-field="host" value="<?= htmlspecialchars($emailHost) ?>" />
                </div>
                <div class="form-group">
                  <label for="email_port">SMTP Port</label>
                  <input type="text" name="email_port" id="email_port" class="form-control config-field" data-config-type="email" data-config-field="port" value="<?= htmlspecialchars($emailPort) ?>" />
                </div>
              </div>
              <div class="email-config-row mt-2">
                <div class="form-group">
                  <label for="email_username">SMTP Username</label>
                  <input type="text" name="email_username" id="email_username" class="form-control config-field" data-config-type="email" data-config-field="username" value="<?= htmlspecialchars($emailUsername) ?>" />
                </div>
                <div class="form-group">
                  <label for="email_password">SMTP Password</label>
                  <input type="password" name="email_password" id="email_password" class="form-control config-field" data-config-type="email" data-config-field="password" value="<?= htmlspecialchars($emailPassword) ?>" />
                </div>
              </div>
              <div class="email-config-row mt-2">
                <div class="form-group">
                  <label for="email_from">From Email</label>
                  <input type="email" name="email_from" id="email_from" class="form-control config-field" data-config-type="email" data-config-field="from" value="<?= htmlspecialchars($emailFrom) ?>" />
                </div>
                <div class="form-group">
                  <label for="email_from_name">From Name</label>
                  <input type="text" name="email_from_name" id="email_from_name" class="form-control config-field" data-config-type="email" data-config-field="from_name" value="<?= htmlspecialchars($emailFromName) ?>" />
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
    
    <!-- Action Buttons -->
    <div class="action-buttons">
      <button type="submit" class="btn btn-primary" id="save-btn">
        <i class="fas fa-save mr-1"></i> SAVE
      </button>
      <button type="reset" class="btn btn-secondary" id="clear-btn">
        <i class="fas fa-redo mr-1"></i> CLEAR
      </button>
    </div>
    
    <div class="footer-note">
      <i class="fas fa-info-circle mr-1"></i> All fields marked with * are required
    </div>
  </form>
</div>
<!-- Toast Container -->
<div class="toast-container" id="toastContainer"></div>
<!-- Auto-save Indicator -->
<div class="auto-save-indicator" id="autoSaveIndicator">
  <i class="fas fa-check-circle mr-1"></i> Configuration saved
</div>
<!-- Login Modal -->
<div class="modal fade" id="loginModal" tabindex="-1" aria-labelledby="loginModalLabel" aria-hidden="true">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="loginModalLabel">Authentication Required</h5>
        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
          <span aria-hidden="true">&times;</span>
        </button>
      </div>
      <div class="modal-body">
        <form id="loginForm">
          <div class="form-group">
            <label for="username">Username</label>
            <input type="text" class="form-control" id="username" required>
          </div>
          <div class="form-group">
            <label for="password">Password</label>
            <input type="password" class="form-control" id="password" required>
          </div>
          <div id="loginError" class="text-danger" style="display: none;">Invalid username or password</div>
        </form>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
        <button type="button" class="btn btn-primary" id="loginSubmit">Login</button>
      </div>
    </div>
  </div>
</div>
<!-- Delete Confirmation Modal -->
<div class="modal fade" id="deleteConfirmModal" tabindex="-1" aria-labelledby="deleteConfirmModalLabel" aria-hidden="true">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="deleteConfirmModalLabel">Confirm Delete</h5>
        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
          <span aria-hidden="true">&times;</span>
        </button>
      </div>
      <div class="modal-body">
        <p>Are you sure you want to delete this patient record? This action cannot be undone.</p>
        <p><strong>Patient ID:</strong> <span id="deletePatientId"></span></p>
        <p><strong>Patient Name:</strong> <span id="deletePatientName"></span></p>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
        <button type="button" class="btn btn-danger" id="confirmDelete">Delete</button>
      </div>
    </div>
  </div>
</div>
<!-- Loading Overlay (initially hidden) -->
<div class="loading-overlay" id="loadingOverlay" style="display: none;">
  <div class="loading-spinner"></div>
  <div class="loading-text">Processing your request...</div>
</div>
<script src="https://code.jquery.com/jquery-3.5.1.min.js"></script>
<script src="https://code.jquery.com/ui/1.12.1/jquery-ui.js"></script>
<script src="https://cdn.jsdelivr.net/npm/popper.js@1.16.1/dist/umd/popper.min.js"></script>
<script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>
<script>
// Live clock functionality
function updateClock() {
  const now = new Date();
  
  // Get day of week
  const days = ['Sunday', 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday'];
  const dayOfWeek = days[now.getDay()];
  
  // Get month
  const months = ['January', 'February', 'March', 'April', 'May', 'June', 'July', 'August', 'September', 'October', 'November', 'December'];
  const month = months[now.getMonth()];
  
  // Get day, year
  const day = now.getDate();
  const year = now.getFullYear();
  
  // Get time in 12-hour format
  let hours = now.getHours();
  const minutes = now.getMinutes().toString().padStart(2, '0');
  const seconds = now.getSeconds().toString().padStart(2, '0');
  const ampm = hours >= 12 ? 'PM' : 'AM';
  
  // Convert to 12-hour format
  hours = hours % 12;
  hours = hours ? hours : 12; // the hour '0' should be '12'
  
  // Format the date and time string
  const dateTimeString = `${dayOfWeek}, ${month} ${day}, ${year} ${hours}:${minutes}:${seconds} ${ampm}`;
  
  // Update the clock element
  document.getElementById('liveClock').textContent = dateTimeString;
}
// Update the clock immediately and then every second
updateClock();
setInterval(updateClock, 1000);
// Function to capitalize the first letter of each word
function toTitleCase(str) {
  return str.replace(/\w\S*/g, function(txt) {
    return txt.charAt(0).toUpperCase() + txt.substr(1).toLowerCase();
  });
}
// Function to calculate row total
function calculateRow(row) {
  const price = parseFloat(row.find('.price').val()) || 0;
  const unit = parseFloat(row.find('.unit').val()) || 1;
  const subtotal = price * unit;
  
  // Get values from less and less_percent fields
  const lessManual = parseFloat(row.find('.less').val()) || 0;
  const percent = parseFloat(row.find('.less-percent').val()) || 0;
  
  // Calculate less amount based on which field was changed
  let lessAmount = 0;
  if (row.data('lastChanged') === 'less') {
    // If less field was changed, calculate percentage
    lessAmount = lessManual;
    if (subtotal > 0) {
      const calculatedPercent = (lessAmount / subtotal) * 100;
      row.find('.less-percent').val(calculatedPercent.toFixed(2));
    }
  } else if (row.data('lastChanged') === 'less-percent') {
    // If less_percent field was changed, calculate amount
    lessAmount = (percent / 100) * subtotal;
    row.find('.less').val(lessAmount.toFixed(2));
  } else {
    // Initial calculation or other fields changed
    lessAmount = lessManual + (percent / 100) * subtotal;
  }
  
  const final = subtotal - lessAmount;
  row.find('.final-price').val(final.toFixed(2));
}
// Function to update all totals
function updateTotal() {
  let total = 0;
  $('.final-price').each(function () {
    total += parseFloat($(this).val()) || 0;
  });
  $('#total').val(total.toFixed(2));
  $('#table-total').val(total.toFixed(2)); // Update table footer total
  
  // Handle summary discount fields
  const lessTotal = parseFloat($('#less_total').val()) || 0;
  const lessPercentTotal = parseFloat($('#less_percent_total').val()) || 0;
  
  // Check which summary field was changed
  if ($('body').data('lastChangedSummary') === 'less_total') {
    // If less_total was changed, calculate percentage
    if (total > 0) {
      const calculatedPercent = (lessTotal / total) * 100;
      $('#less_percent_total').val(calculatedPercent.toFixed(2));
    }
  } else if ($('body').data('lastChangedSummary') === 'less_percent_total') {
    // If less_percent_total was changed, calculate amount
    const calculatedAmount = (lessPercentTotal / 100) * total;
    $('#less_total').val(calculatedAmount.toFixed(2));
  }
  
  // Calculate payable after discounts (use only one discount type)
  const discountAmount = $('body').data('lastChangedSummary') === 'less_percent_total' 
    ? (lessPercentTotal / 100) * total 
    : lessTotal;
  
  const payable = total - discountAmount;
  $('#payable').val(payable.toFixed(2));
  
  const paid = parseFloat($('#paid').val()) || 0;
  const due = payable - paid;
  $('#due').val(due.toFixed(2));
}
// Function to bind events
function bindEvents() {
  // Track which field was changed in table rows
  $('#service-table').on('focusin', '.less, .less-percent', function() {
    $(this).closest('tr').data('lastChanged', $(this).hasClass('less') ? 'less' : 'less-percent');
  });
  
  // Track which field was changed in summary
  $('.summary-less, .summary-less-percent').on('focusin', function() {
    $('body').data('lastChangedSummary', $(this).hasClass('summary-less') ? 'less_total' : 'less_percent_total');
  });
  
  $('#service-table').on('input', '.price, .unit, .less, .less-percent', function () {
    const row = $(this).closest('tr');
    calculateRow(row);
    updateTotal();
  });
  
  $('#less_total, #less_percent_total, #paid').on('input', updateTotal);
  
  $('.remove-row').off('click').on('click', function () {
    $(this).closest('tr').remove();
    updateTotal();
  });
  
  // Capitalize input fields with the capitalize-input class
  $('.capitalize-input').on('input', function() {
    const cursorPos = this.selectionStart;
    const value = $(this).val();
    $(this).val(toTitleCase(value));
    this.setSelectionRange(cursorPos, cursorPos);
  });
}
// Service Name autocomplete and autofill
function bindServiceAutocomplete(row) {
  row.find('input[name="service_name[]"]').autocomplete({
    source: function(request, response) {
      $.ajax({
        url: 'services_search.php',
        dataType: 'json',
        data: { term: request.term },
        success: function(data) {
          response($.map(data, function(item) {
            return {
              label: item.service_name + ' (' + item.service_id + ')',
              value: item.service_name,
              service_id: item.service_id,
              price: item.price
            };
          }));
        }
      });
    },
    select: function(event, ui) {
      const tr = $(this).closest('tr');
      tr.find('input[name="service_id[]"]').val(ui.item.service_id);
      tr.find('input[name="price[]"]').val(ui.item.price).trigger('input');
    },
    minLength: 1
  });
}
// Referral Doctors autocomplete
function bindReferralAutocomplete() {
  $('#ref_doctors').autocomplete({
    source: function(request, response) {
      $.ajax({
        url: 'referrals_search.php',
        dataType: 'json',
        data: { 
          term: request.term,
          field: 'doctor'
        },
        success: function(data) {
          response(data);
        }
      });
    },
    minLength: 1
  });
  
  $('#ref_name').autocomplete({
    source: function(request, response) {
      $.ajax({
        url: 'referrals_search.php',
        dataType: 'json',
        data: { 
          term: request.term,
          field: 'ref_name'
        },
        success: function(data) {
          response(data);
        }
      });
    },
    minLength: 1
  });
}
// Dark mode functionality
function initDarkMode() {
  const darkModeToggle = document.getElementById('darkModeToggle');
  const htmlElement = document.documentElement;
  
  // Check for saved user preference or default to light mode
  const currentTheme = localStorage.getItem('theme') || 'light';
  
  // Apply the saved theme
  if (currentTheme === 'dark') {
    htmlElement.setAttribute('data-theme', 'dark');
    darkModeToggle.checked = true;
  }
  
  // Listen for toggle changes
  darkModeToggle.addEventListener('change', function() {
    if (this.checked) {
      htmlElement.setAttribute('data-theme', 'dark');
      localStorage.setItem('theme', 'dark');
    } else {
      htmlElement.removeAttribute('data-theme');
      localStorage.setItem('theme', 'light');
    }
  });
}
// Function to clear all form fields including service table
function clearForm() {
  // Clear all input fields
  $('#billing-form')[0].reset();
  
  // Clear service table and add one empty row
  $('#service-table tbody').empty();
  const newRow = $(`
    <tr>
      <td><input type="text" name="service_id[]" class="form-control" readonly /></td>
      <td><input type="text" name="service_name[]" class="form-control" /></td>
      <td><input type="number" name="price[]" class="form-control price" step="0.01" min="0" /></td>
      <td><input type="number" name="unit[]" class="form-control unit" value="1" min="0" /></td>
      <td><input type="number" name="less[]" class="form-control less" step="0.01" min="0" /></td>
      <td><input type="number" name="less_percent[]" class="form-control less-percent" step="0.01" min="0" max="100" /></td>
      <td><input type="number" name="final_price[]" class="form-control final-price" step="0.01" readonly /></td>
      <td><button type="button" class="btn btn-danger btn-sm remove-row"><i class="fas fa-trash-alt"></i></button></td>
    </tr>
  `);
  $('#service-table tbody').append(newRow);
  bindServiceAutocomplete(newRow);
  bindEvents();
  
  // Reset date fields to today
  $('input[name="date"]').val(new Date().toISOString().split('T')[0]);
  $('input[name="delivery_date"]').val(new Date().toISOString().split('T')[0]);
  
  // Reset totals
  updateTotal();
}
// Function to show toast notification
function showToast(message, type = 'success') {
  const toastContainer = $('#toastContainer');
  const toastId = 'toast-' + Date.now();
  
  const toast = $(`
    <div id="${toastId}" class="toast ${type === 'error' ? 'error' : ''} ${type === 'warning' ? 'warning' : ''}">
      <div class="toast-header">
        <span class="toast-title">${type === 'success' ? 'Success' : type === 'error' ? 'Error' : 'Warning'}</span>
        <button type="button" class="toast-close" data-dismiss="toast" aria-label="Close">
          <span aria-hidden="true">&times;</span>
        </button>
      </div>
      <div class="toast-body">
        ${message}
      </div>
    </div>
  `);
  
  toastContainer.append(toast);
  
  // Show the toast
  setTimeout(() => {
    toast.addClass('show');
  }, 10);
  
  // Auto-hide after 3 seconds
  setTimeout(() => {
    toast.removeClass('show');
    setTimeout(() => {
      toast.remove();
    }, 300);
  }, 3000);
  
  // Handle close button click
  toast.find('.toast-close').on('click', function() {
    toast.removeClass('show');
    setTimeout(() => {
      toast.remove();
    }, 300);
  });
}
// Function to save configuration automatically
function saveConfiguration(configType, field, value) {
  $.ajax({
    url: 'save_config.php',
    type: 'POST',
    data: {
      config_type: configType,
      field: field,
      value: value
    },
    dataType: 'json',
    success: function(response) {
      if (response.success) {
        // Show auto-save indicator
        const indicator = $('#autoSaveIndicator');
        indicator.addClass('show');
        setTimeout(() => {
          indicator.removeClass('show');
        }, 2000);
      } else {
        showToast('Failed to save configuration: ' + response.message, 'error');
      }
    },
    error: function() {
      showToast('Error saving configuration', 'error');
    }
  });
}
// Function to check SMS server status
function checkSmsServerStatus() {
  const statusCircle = $('#smsStatusCircle');
  const statusText = $('#smsStatusText');
  
  // Set checking status
  statusCircle.removeClass('status-online status-offline').addClass('status-checking');
  statusText.text('Checking...');
  
  // Make AJAX request to check SMS server status
  $.ajax({
    url: 'check_sms_server.php',
    type: 'GET',
    dataType: 'json',
    timeout: 5000, // 5 seconds timeout
    success: function(response) {
      if (response.online) {
        statusCircle.removeClass('status-checking status-offline').addClass('status-online');
        statusText.text('Online');
      } else {
        statusCircle.removeClass('status-checking status-online').addClass('status-offline');
        statusText.text('Offline - ' + (response.message || 'Connection failed'));
      }
    },
    error: function() {
      statusCircle.removeClass('status-checking status-online').addClass('status-offline');
      statusText.text('Offline - Could not check status');
    }
  });
}
$(document).ready(function () {
  // Initialize dark mode
  initDarkMode();
  
  // Load saved toggle states
  const sendSmsState = localStorage.getItem('send_sms') === 'true';
  const sendEmailState = localStorage.getItem('send_email') === 'true';
  
  // Set initial states
  $('#send_sms').prop('checked', sendSmsState);
  $('#send_email').prop('checked', sendEmailState);
  
  // Show/hide configurations based on saved states
  if (sendSmsState) {
    $('#smsConfig').show();
    $('#smsStatusIndicator').show();
    // Check initial status
    checkSmsServerStatus();
  } else {
    $('#smsConfig').hide();
    $('#smsStatusIndicator').hide();
  }
  
  if (sendEmailState) {
    $('#emailConfig').show();
  } else {
    $('#emailConfig').hide();
  }
  
  // Show/hide SMS configuration and status indicator based on SMS switch
  $('#send_sms').change(function() {
    localStorage.setItem('send_sms', $(this).is(':checked'));
    if ($(this).is(':checked')) {
      $('#smsConfig').slideDown();
      $('#smsStatusIndicator').slideDown();
      // Check SMS server status when switch is turned on
      checkSmsServerStatus();
    } else {
      $('#smsConfig').slideUp();
      $('#smsStatusIndicator').slideUp();
    }
  });
  
  // Refresh SMS status button click handler
  $(document).on('click', '#refreshSmsStatus', function() {
    checkSmsServerStatus();
  });
  
  $('#send_email').change(function() {
    localStorage.setItem('send_email', $(this).is(':checked'));
    if ($(this).is(':checked')) {
      $('#emailConfig').slideDown();
    } else {
      $('#emailConfig').slideUp();
    }
  });
  
  bindEvents();
  
  // Bind autocomplete on existing rows
  $('#service-table tbody tr').each(function() {
    bindServiceAutocomplete($(this));
  });
  
  // Bind referral autocomplete
  bindReferralAutocomplete();
  
  // Add new row
  $('#add-row').click(function () {
    const newRow = $(`
      <tr>
        <td><input type="text" name="service_id[]" class="form-control" readonly /></td>
        <td><input type="text" name="service_name[]" class="form-control" /></td>
        <td><input type="number" name="price[]" class="form-control price" step="0.01" min="0" /></td>
        <td><input type="number" name="unit[]" class="form-control unit" value="1" min="0" /></td>
        <td><input type="number" name="less[]" class="form-control less" step="0.01" min="0" /></td>
        <td><input type="number" name="less_percent[]" class="form-control less-percent" step="0.01" min="0" max="100" /></td>
        <td><input type="number" name="final_price[]" class="form-control final-price" step="0.01" readonly /></td>
        <td><button type="button" class="btn btn-danger btn-sm remove-row"><i class="fas fa-trash-alt"></i></button></td>
      </tr>
    `);
    $('#service-table tbody').append(newRow);
    bindServiceAutocomplete(newRow);
    bindEvents();
  });
  
  // Toggle Patient ID edit mode
  $('#edit_patient_id').change(function() {
    if ($(this).is(':checked')) {
      $('#patient_id').prop('readonly', false).attr('type', 'number');
      $('#save-btn').hide();
      $('#clear-btn').hide();
      $('#update-btn').show();
      $('#print-btn').show();
      $('#delete-btn').show();
    } else {
      $('#patient_id').prop('readonly', true).attr('type', 'text');
      $('#save-btn').show();
      $('#clear-btn').show();
      $('#update-btn').hide();
      $('#print-btn').hide();
      $('#delete-btn').hide();
      
      // Clear form when switching off edit mode
      clearForm();
    }
  });
  
  // Load patient data when old_id is entered
  $('#old_id').on('change', function() {
    const oldId = $(this).val().trim();
    if (oldId) {
      $.getJSON('get_patient_info_by_id.php', { old_id: oldId }, function(data) {
        if (data && data.patient) {
          const p = data.patient;
          $('#phone').val(p.phone || '');
          $('#patient_name').val(p.patient_name || '');
          $('#sex').val(p.sex || 'Male');
          $('#address').val(p.address || '');
          $('#email').val(p.email || '');
        }
      });
    }
  });
  
  // Auto-save configuration when fields lose focus
  $('.config-field').on('blur', function() {
    const configType = $(this).data('config-type');
    const field = $(this).data('config-field');
    const value = $(this).val();
    
    if (configType && field && value !== undefined) {
      saveConfiguration(configType, field, value);
    }
  });
  
  // Auto-load patient data when typing ID in edit mode
  let patientIdTimeout;
  $('#patient_id').on('input', function() {
    if ($('#edit_patient_id').is(':checked')) {
      clearTimeout(patientIdTimeout);
      const patientId = $(this).val().trim();
      
      // Only fetch if we have at least 1 digit
      if (patientId.length >= 1) {
        patientIdTimeout = setTimeout(function() {
          $.getJSON('get_patient_billing.php', { patient_id: patientId }, function(data) {
            if (!data || !data.patient) {
              return; // No data found, do nothing
            }
            const p = data.patient;
            // Fill patient info
            $('input[name="old_id"]').val(p.old_id || '');
            $('input[name="date"]').val(p.date || '');
            $('input[name="patient_name"]').val(p.patient_name || '');
            $('select[name="sex"]').val(p.sex || 'Male');
            const ageString = p.age || '';
            const ageMatch = ageString.match(/(?:(\d+)y)?\s*(?:(\d+)m)?\s*(?:(\d+)d)?/);
            $('input[name="age_year"]').val(ageMatch && ageMatch[1] ? ageMatch[1] : '');
            $('input[name="age_month"]').val(ageMatch && ageMatch[2] ? ageMatch[2] : '');
            $('input[name="age_day"]').val(ageMatch && ageMatch[3] ? ageMatch[3] : '');
            $('input[name="phone"]').val(p.phone || '');
            $('input[name="email"]').val(p.email || '');
            $('input[name="address"]').val(p.address || '');
            $('input[name="ref_doctors"]').val(p.ref_doctors || '');
            $('input[name="ref_name"]').val(p.ref_name || '');
            $('input[name="delivery_date"]').val(p.delivery_date || '');
            $('input[name="delivery_time"]').val(p.delivery_time || '');
            $('input[name="remarks"]').val(p.remarks || '');
            $('input[name="less_total"]').val(p.less_total || '0');
            $('input[name="less_percent_total"]').val(p.less_percent_total || '0');
            $('input[name="paid"]').val(p.paid || '0');
            $('input[name="send_sms"]').prop('checked', p.send_sms ? true : false);
            $('input[name="send_email"]').prop('checked', p.send_email ? true : false);
            
            // Set the flag for which discount was last changed
            if (p.less_total > 0) {
              $('body').data('lastChangedSummary', 'less_total');
            } else if (p.less_percent_total > 0) {
              $('body').data('lastChangedSummary', 'less_percent_total');
            }
            
            // Clear existing service rows
            $('#service-table tbody').empty();
            // Add billing rows
            (data.billing || []).forEach(service => {
              const row = $(`
                <tr>
                  <td><input type="text" name="service_id[]" class="form-control" readonly value="${service.service_id}" /></td>
                  <td><input type="text" name="service_name[]" class="form-control" value="${service.service_name}" /></td>
                  <td><input type="number" name="price[]" class="form-control price" step="0.01" min="0" value="${service.price}" /></td>
                  <td><input type="number" name="unit[]" class="form-control unit" value="${service.unit}" min="0" /></td>
                  <td><input type="number" name="less[]" class="form-control less" step="0.01" min="0" value="${service.less}" /></td>
                  <td><input type="number" name="less_percent[]" class="form-control less-percent" step="0.01" min="0" max="100" value="${service.less_percent}" /></td>
                  <td><input type="number" name="final_price[]" class="form-control final-price" step="0.01" readonly value="${service.final_price}" /></td>
                  <td><button type="button" class="btn btn-danger btn-sm remove-row"><i class="fas fa-trash-alt"></i></button></td>
                </tr>
              `);
              $('#service-table tbody').append(row);
              bindServiceAutocomplete(row);
            });
            bindEvents();
            updateTotal();
          });
        }, 500); // 500ms delay to avoid excessive API calls
      }
    }
  });
  
  // Update button click: validate patient ID first, then show login modal
  $('#update-btn').click(function(e) {
    e.preventDefault();
    
    // Check if patient ID is empty
    const patientId = $('#patient_id').val().trim();
    if (!patientId) {
      showToast('Please enter a valid Patient ID', 'error');
      return;
    }
    
    // Set action type to update
    $('#loginModal').data('action', 'update');
    
    // Show login modal
    $('#loginModal').modal('show');
  });
  
  // Delete button click: validate patient ID first, then show confirmation modal
  $('#delete-btn').click(function(e) {
    e.preventDefault();
    
    // Check if patient ID is empty
    const patientId = $('#patient_id').val().trim();
    if (!patientId) {
      showToast('Please enter a valid Patient ID', 'error');
      return;
    }
    
    // Set patient info in confirmation modal
    $('#deletePatientId').text(patientId);
    $('#deletePatientName').text($('#patient_name').val() || 'N/A');
    
    // Show delete confirmation modal
    $('#deleteConfirmModal').modal('show');
  });
  
  // Confirm delete button click: show login modal
  $('#confirmDelete').click(function() {
    // Hide delete confirmation modal
    $('#deleteConfirmModal').modal('hide');
    
    // Set action type to delete
    $('#loginModal').data('action', 'delete');
    
    // Show login modal
    $('#loginModal').modal('show');
  });
  
  // Print button click: validate patient ID first, then go to receipt.php
  $('#print-btn').click(function() {
    const patientId = $('#patient_id').val().trim();
    if (!patientId) {
      showToast('Please enter a valid Patient ID', 'error');
      return;
    }
    
    window.open('receipt.php?patient_id=' + encodeURIComponent(patientId), '_blank');
  });
  
  // Handle login form submission
  $('#loginSubmit').click(function() {
    const username = $('#username').val();
    const password = $('#password').val();
    const action = $('#loginModal').data('action') || 'update';
    
    // Validate credentials via AJAX
    $.ajax({
      url: 'check_credentials.php',
      type: 'POST',
      data: { username: username, password: password },
      dataType: 'json',
      success: function(response) {
        if (response.valid) {
          $('#loginModal').modal('hide');
          
          if (action === 'update') {
            // Submit the form for update
            $('#billing-form').submit();
          } else if (action === 'delete') {
            // Perform delete operation
            deletePatient();
          }
        } else {
          $('#loginError').show();
        }
      },
      error: function() {
        $('#loginError').text('Error validating credentials. Please try again.').show();
      }
    });
  });
  
  // Function to delete patient record
  function deletePatient() {
    const patientId = $('#patient_id').val().trim();
    
    // Show loading overlay
    $('#loadingOverlay').show();
    
    // Disable buttons
    $('#update-btn').prop('disabled', true);
    $('#delete-btn').prop('disabled', true);
    $('#print-btn').prop('disabled', true);
    
    $.ajax({
      url: 'delete_patient.php',
      type: 'POST',
      data: { patient_id: patientId },
      dataType: 'json',
      success: function(response) {
        // Hide loading overlay
        $('#loadingOverlay').hide();
        
        // Re-enable buttons
        $('#update-btn').prop('disabled', false);
        $('#delete-btn').prop('disabled', false);
        $('#print-btn').prop('disabled', false);
        
        if (response.success) {
          // Show success message
          showToast('Patient record deleted successfully');
          
          // Turn off edit mode
          $('#edit_patient_id').prop('checked', false);
          $('#patient_id').prop('readonly', true).attr('type', 'text');
          $('#save-btn').show();
          $('#clear-btn').show();
          $('#update-btn').hide();
          $('#print-btn').hide();
          $('#delete-btn').hide();
          
          // Clear the form
          clearForm();
        } else {
          // Show error message
          showToast(response.message || 'Error deleting patient record', 'error');
        }
      },
      error: function(xhr, status, error) {
        // Hide loading overlay
        $('#loadingOverlay').hide();
        
        // Re-enable buttons
        $('#update-btn').prop('disabled', false);
        $('#delete-btn').prop('disabled', false);
        $('#print-btn').prop('disabled', false);
        
        let errorMessage = 'Error deleting patient record';
        
        if (xhr.responseJSON && xhr.responseJSON.message) {
          errorMessage = xhr.responseJSON.message;
        }
        
        // Show error message
        showToast(errorMessage, 'error');
      }
    });
  }
  
  // Clear button functionality
  $('#clear-btn').on('click', function(e) {
    e.preventDefault();
    clearForm();
  });
  
  // Form submission with loading indicator and retry mechanism
  $('#billing-form').on('submit', function(e) {
    e.preventDefault();
    
    // Show loading overlay
    $('#loadingOverlay').show();
    
    // Disable form buttons to prevent multiple submissions
    $('#save-btn').prop('disabled', true);
    $('#clear-btn').prop('disabled', true);
    $('#update-btn').prop('disabled', true);
    $('#print-btn').prop('disabled', true);
    $('#delete-btn').prop('disabled', true);
    
    // Serialize form data
    const formData = $(this).serialize();
    
    // Function to attempt form submission
    function attemptSubmission() {
      $.ajax({
        url: 'save.php',
        type: 'POST',
        data: formData,
        dataType: 'json',
        timeout: 15000, // 15 seconds timeout
        success: function(response) {
          // Hide loading overlay
          $('#loadingOverlay').hide();
          
          // Re-enable form buttons
          $('#save-btn').prop('disabled', false);
          $('#clear-btn').prop('disabled', false);
          $('#update-btn').prop('disabled', false);
          $('#print-btn').prop('disabled', false);
          $('#delete-btn').prop('disabled', false);
          
          if (response.success) {
            // If in edit mode, show toast message
            if ($('#edit_patient_id').is(':checked')) {
              showToast('Patient information updated successfully!');
              // Reload the data to ensure we have the latest
              const patientId = $('#patient_id').val().trim();
              $.getJSON('get_patient_billing.php', { patient_id: patientId }, function(data) {
                if (data && data.patient) {
                  // Data is already loaded, just show confirmation
                  console.log('Data reloaded successfully');
                }
              });
            } else {
              // Redirect to receipt page on success for new entries
              window.location.href = 'receipt.php?patient_id=' + encodeURIComponent(response.patient_id);
            }
            
            // Check for notification errors
            if (response.notification_errors) {
              if (response.notification_errors.email) {
                showToast('Email could not be sent: ' + response.notification_errors.email, 'warning');
              }
              if (response.notification_errors.sms) {
                showToast('SMS could not be sent: ' + response.notification_errors.sms, 'warning');
              }
            }
          } else {
            // Show error message
            $('#retryMessage').text(response.message || 'An error occurred while saving your data.');
            $('#retryContainer').show();
          }
        },
        error: function(xhr, status, error) {
          // Hide loading overlay
          $('#loadingOverlay').hide();
          
          // Re-enable form buttons
          $('#save-btn').prop('disabled', false);
          $('#clear-btn').prop('disabled', false);
          $('#update-btn').prop('disabled', false);
          $('#print-btn').prop('disabled', false);
          $('#delete-btn').prop('disabled', false);
          
          let errorMessage = 'An error occurred while saving your data.';
          
          if (status === 'timeout') {
            errorMessage = 'The request timed out. Please check your connection and try again.';
          } else if (xhr.status === 0) {
            errorMessage = 'Network error. Please check your internet connection.';
          } else if (xhr.status === 503 || xhr.status === 500) {
            errorMessage = 'The server is busy. Please try again.';
          } else if (xhr.responseJSON && xhr.responseJSON.message) {
            errorMessage = xhr.responseJSON.message;
          }
          
          // Show retry container
          $('#retryMessage').text(errorMessage);
          $('#retryContainer').show();
        }
      });
    }
    
    // Attempt initial submission
    attemptSubmission();
  });
  
  // Retry button click handler
  $('#retryBtn').on('click', function() {
    // Hide retry container
    $('#retryContainer').hide();
    
    // Show loading overlay
    $('#loadingOverlay').show();
    
    // Disable form buttons
    $('#save-btn').prop('disabled', true);
    $('#clear-btn').prop('disabled', true);
    $('#update-btn').prop('disabled', true);
    $('#print-btn').prop('disabled', true);
    $('#delete-btn').prop('disabled', true);
    
    // Attempt submission again
    attemptSubmission();
  });
  
  // Cancel retry button click handler
  $('#cancelRetryBtn').on('click', function() {
    // Hide retry container
    $('#retryContainer').hide();
  });
  
  // Initial total calculation trigger
  $('.price, .unit, .less, .less-percent').trigger('input');
});
</script>
</body>
</html>