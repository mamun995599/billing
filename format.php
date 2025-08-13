<?php
// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

include 'db_sqlite.php';

// Process form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    $result = '';
    
    try {
        $pdo->beginTransaction();
        
        switch ($action) {
            case 'empty_services':
                // Delete all services
                $stmt = $pdo->prepare("DELETE FROM services");
                $stmt->execute();
                
                // Reset auto-increment counter
                $pdo->exec("DELETE FROM sqlite_sequence WHERE name='services'");
                
                $result = "All services have been deleted successfully and auto-increment counter has been reset.";
                break;
                
            case 'empty_billing':
                // Delete all billing records
                $stmt = $pdo->prepare("DELETE FROM billing");
                $stmt->execute();
                
                // Reset auto-increment counter
                $pdo->exec("DELETE FROM sqlite_sequence WHERE name='billing'");
                
                $result = "All billing records have been deleted successfully and auto-increment counter has been reset.";
                break;
                
            case 'empty_patients':
                // First delete all billing records (due to foreign key constraint)
                $stmt = $pdo->prepare("DELETE FROM billing");
                $stmt->execute();
                
                // Then delete all patients
                $stmt = $pdo->prepare("DELETE FROM patients");
                $stmt->execute();
                
                // Reset auto-increment counters
                $pdo->exec("DELETE FROM sqlite_sequence WHERE name='patients'");
                $pdo->exec("DELETE FROM sqlite_sequence WHERE name='billing'");
                
                $result = "All patient records and associated billing data have been deleted successfully and auto-increment counters have been reset.";
                break;
                
            case 'empty_all':
                // Delete in correct order due to foreign key constraints
                // 1. Delete all billing records
                $stmt = $pdo->prepare("DELETE FROM billing");
                $stmt->execute();
                
                // 2. Delete all patients
                $stmt = $pdo->prepare("DELETE FROM patients");
                $stmt->execute();
                
                // 3. Delete all services
                $stmt = $pdo->prepare("DELETE FROM services");
                $stmt->execute();
                
                // 4. Reset auto-increment counters
                $pdo->exec("DELETE FROM sqlite_sequence WHERE name='patients'");
                $pdo->exec("DELETE FROM sqlite_sequence WHERE name='billing'");
                $pdo->exec("DELETE FROM sqlite_sequence WHERE name='services'");
                
                $result = "All data has been cleared successfully. All tables are now empty and auto-increment counters have been reset.";
                break;
                
            default:
                $result = "Invalid action specified.";
        }
        
        $pdo->commit();
        
    } catch (PDOException $e) {
        $pdo->rollBack();
        $result = "Database error: " . $e->getMessage();
    } catch (Exception $e) {
        $pdo->rollBack();
        $result = "Error: " . $e->getMessage();
    }
    
    // Redirect to the same page to prevent form resubmission
    header("Location: " . $_SERVER['PHP_SELF'] . "?result=" . urlencode($result));
    exit;
}

// Check for result in URL parameters
if (isset($_GET['result'])) {
    $result = $_GET['result'];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Database Management - Clinic Billing System</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
    <style>
        body {
            background-color: #f8f9fa;
            padding: 20px;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        .container {
            max-width: 1000px;
            margin: 0 auto;
            background-color: white;
            padding: 30px;
            border-radius: 10px;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
        }
        .page-header {
            text-align: center;
            margin-bottom: 30px;
            padding-bottom: 15px;
            border-bottom: 2px solid #e9ecef;
        }
        .page-header h1 {
            color: #343a40;
            font-weight: 600;
        }
        .page-header p {
            color: #6c757d;
            margin-top: 5px;
        }
        .action-card {
            background-color: #f8f9fa;
            border: 1px solid #dee2e6;
            border-radius: 8px;
            padding: 20px;
            margin-bottom: 20px;
        }
        .action-card h3 {
            color: #495057;
            margin-bottom: 15px;
            font-size: 1.25rem;
        }
        .action-card p {
            color: #6c757d;
            margin-bottom: 15px;
        }
        .btn-danger {
            background: linear-gradient(135deg, #dc3545 0%, #c82333 100%);
            border: none;
            padding: 10px 20px;
            font-weight: 600;
            color: white;
        }
        .btn-danger:hover {
            background: linear-gradient(135deg, #c82333 0%, #bd2130 100%);
        }
        .btn-warning {
            background: linear-gradient(135deg, #ffc107 0%, #e0a800 100%);
            border: none;
            padding: 10px 20px;
            font-weight: 600;
            color: #212529;
        }
        .btn-warning:hover {
            background: linear-gradient(135deg, #e0a800 0%, #d39e00 100%);
        }
        .result-message {
            margin-top: 20px;
            padding: 15px;
            border-radius: 5px;
            display: block;
        }
        .result-success {
            background-color: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }
        .result-error {
            background-color: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }
        .back-link {
            display: inline-block;
            margin-top: 20px;
            color: #007bff;
            text-decoration: none;
        }
        .back-link:hover {
            text-decoration: underline;
        }
        .icon-large {
            font-size: 3rem;
            color: #dc3545;
            margin-bottom: 15px;
        }
        .confirmation-modal .modal-content {
            border: none;
            border-radius: 10px;
        }
        .confirmation-modal .modal-header {
            background: linear-gradient(135deg, #dc3545 0%, #c82333 100%);
            color: white;
            border-radius: 10px 10px 0 0;
        }
        .confirmation-modal .modal-title {
            font-weight: 600;
        }
        .confirmation-modal .modal-footer {
            border-top: none;
        }
        .table-status {
            margin-top: 30px;
        }
        .table-status h4 {
            margin-bottom: 15px;
            color: #495057;
        }
        .table-status .table {
            font-size: 0.9rem;
        }
        .table-status .badge {
            font-size: 0.8rem;
        }
        .badge-success {
            background-color: #28a745;
        }
        .badge-danger {
            background-color: #dc3545;
        }
        .auto-increment-info {
            margin-top: 20px;
            padding: 15px;
            background-color: #e7f3ff;
            border-radius: 5px;
            border-left: 4px solid #2196F3;
        }
        .auto-increment-info h5 {
            color: #0d47a1;
            margin-bottom: 10px;
        }
        .auto-increment-info p {
            color: #0d47a1;
            margin-bottom: 0;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="page-header">
            <h1><i class="bi bi-database-fill-gear"></i> Database Management</h1>
            <p>Manage and format your clinic billing system database</p>
        </div>
        
        <?php if (isset($result)): ?>
            <div class="result-message <?= strpos($result, 'success') !== false ? 'result-success' : 'result-error' ?>">
                <?= htmlspecialchars($result) ?>
            </div>
        <?php endif; ?>
        
        <div class="auto-increment-info">
            <h5><i class="bi bi-info-circle-fill"></i> Auto-Increment Information</h5>
            <p>When you empty tables, the auto-increment counters will be reset. This means new records will start from ID 1 instead of continuing from the last ID before deletion.</p>
        </div>
        
        <div class="table-status">
            <h4>Current Table Status</h4>
            <table class="table table-striped table-bordered">
                <thead>
                    <tr>
                        <th>Table Name</th>
                        <th>Record Count</th>
                        <th>Next Auto-Increment ID</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    // Get record counts and next auto-increment values for each table
                    $tables = [
                        'patients' => 'Patients',
                        'billing' => 'Billing',
                        'services' => 'Services'
                    ];
                    
                    foreach ($tables as $table => $label) {
                        // Get record count
                        $stmt = $pdo->query("SELECT COUNT(*) as count FROM $table");
                        $count = $stmt->fetch()['count'];
                        
                        // Get next auto-increment value
                        $stmt = $pdo->query("SELECT seq FROM sqlite_sequence WHERE name='$table'");
                        $seqResult = $stmt->fetch();
                        $nextId = $seqResult ? ($seqResult['seq'] + 1) : 1;
                        
                        $statusClass = $count > 0 ? 'badge-danger' : 'badge-success';
                        $statusText = $count > 0 ? 'Contains Data' : 'Empty';
                        
                        echo "<tr>
                            <td>$label</td>
                            <td>$count</td>
                            <td>$nextId</td>
                            <td><span class='badge $statusClass'>$statusText</span></td>
                        </tr>";
                    }
                    ?>
                </tbody>
            </table>
        </div>
        
        <div class="action-card">
            <h3><i class="bi bi-exclamation-triangle-fill"></i> Danger Zone</h3>
            <p>These actions will permanently delete data from your database and reset auto-increment counters. Please be careful as these actions cannot be undone.</p>
            
            <form method="post" id="servicesForm">
                <input type="hidden" name="action" value="empty_services">
                <div class="d-grid gap-2">
                    <button type="button" class="btn btn-warning" onclick="confirmFormSubmit('servicesForm', 'services')">
                        <i class="bi bi-trash3-fill"></i> Empty Services Table
                    </button>
                </div>
            </form>
            <p class="text-muted small mt-2">This will delete all services from the services table and reset the auto-increment counter. Patient and billing data will remain intact.</p>
        </div>
        
        <div class="action-card">
            <h3><i class="bi bi-exclamation-triangle-fill"></i> Danger Zone</h3>
            <p>These actions will permanently delete data from your database and reset auto-increment counters. Please be careful as these actions cannot be undone.</p>
            
            <form method="post" id="billingForm">
                <input type="hidden" name="action" value="empty_billing">
                <div class="d-grid gap-2">
                    <button type="button" class="btn btn-warning" onclick="confirmFormSubmit('billingForm', 'billing')">
                        <i class="bi bi-trash3-fill"></i> Empty Billing Table
                    </button>
                </div>
            </form>
            <p class="text-muted small mt-2">This will delete all billing records and reset the auto-increment counter. Patient and services data will remain intact.</p>
        </div>
        
        <div class="action-card">
            <h3><i class="bi bi-exclamation-triangle-fill"></i> Danger Zone</h3>
            <p>These actions will permanently delete data from your database and reset auto-increment counters. Please be careful as these actions cannot be undone.</p>
            
            <form method="post" id="patientsForm">
                <input type="hidden" name="action" value="empty_patients">
                <div class="d-grid gap-2">
                    <button type="button" class="btn btn-danger" onclick="confirmFormSubmit('patientsForm', 'patients')">
                        <i class="bi bi-trash3-fill"></i> Empty Patients Table
                    </button>
                </div>
            </form>
            <p class="text-muted small mt-2">This will delete all patient records and their associated billing data, and reset all auto-increment counters. Services data will remain intact.</p>
        </div>
        
        <div class="action-card">
            <h3><i class="bi bi-exclamation-triangle-fill"></i> Danger Zone</h3>
            <p>These actions will permanently delete data from your database and reset auto-increment counters. Please be careful as these actions cannot be undone.</p>
            
            <form method="post" id="allForm">
                <input type="hidden" name="action" value="empty_all">
                <div class="d-grid gap-2">
                    <button type="button" class="btn btn-danger" onclick="confirmFormSubmit('allForm', 'all')">
                        <i class="bi bi-trash3-fill"></i> Empty All Tables
                    </button>
                </div>
            </form>
            <p class="text-muted small mt-2">This will delete ALL data from all tables and reset all auto-increment counters.</p>
        </div>
        
        <div class="text-center">
            <a href="index.php" class="back-link">
                <i class="bi bi-arrow-left-circle"></i> Back to Dashboard
            </a>
        </div>
    </div>
    
    <!-- Confirmation Modal -->
    <div class="modal fade confirmation-modal" id="confirmationModal" tabindex="-1" aria-labelledby="confirmationModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="confirmationModalLabel">Confirm Action</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="text-center mb-3">
                        <i class="bi bi-exclamation-triangle-fill icon-large"></i>
                    </div>
                    <h5 class="text-center">Are you sure you want to continue?</h5>
                    <p class="text-center" id="confirmationMessage">This action cannot be undone.</p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="button" class="btn btn-danger" id="confirmButton">Yes, Delete</button>
                </div>
            </div>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function confirmFormSubmit(formId, tableType) {
            const messages = {
                'services': 'This will delete all services from the services table and reset the auto-increment counter. This action cannot be undone.',
                'billing': 'This will delete all billing records and reset the auto-increment counter. This action cannot be undone.',
                'patients': 'This will delete all patient records and their associated billing data, and reset all auto-increment counters. This action cannot be undone.',
                'all': 'This will delete ALL data from all tables and reset all auto-increment counters. This action cannot be undone.'
            };
            
            document.getElementById('confirmationMessage').textContent = messages[tableType];
            
            const modal = new bootstrap.Modal(document.getElementById('confirmationModal'));
            
            document.getElementById('confirmButton').onclick = function() {
                modal.hide();
                document.getElementById(formId).submit();
            };
            
            modal.show();
        }
    </script>
</body>
</html>