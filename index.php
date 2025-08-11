<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Billing Dashboard</title>
  <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
  <style>
    body {
      background: #f2f8ff;
      font-family: 'Segoe UI', sans-serif;
    }
    .card {
      border: none;
      border-radius: 15px;
      box-shadow: 0 4px 12px rgba(0,0,0,0.1);
      transition: transform 0.2s ease;
      height: 100%;
    }
    .card:hover {
      transform: translateY(-5px);
    }
    .dashboard-title {
      font-size: 2rem;
      font-weight: bold;
      color: #0c5460;
      margin-bottom: 20px;
      text-align: center;
    }
    .btn {
      border-radius: 30px;
    }
    .card-title {
      font-size: 1.25rem;
      color: #004085;
    }
    .icon-large {
      font-size: 2.5rem;
      margin-bottom: 15px;
    }
    .backup-card {
      background: linear-gradient(135deg, #f5f7fa 0%, #e4efe9 100%);
    }
    .backup-card .card-title {
      color: #0c5460;
    }
    .backup-card .btn {
      background-color: #0c5460;
      border-color: #0c5460;
    }
    .backup-card .btn:hover {
      background-color: #0a3d42;
      border-color: #0a3d42;
    }
  </style>
</head>
<body>
<div class="container mt-5">
  <div class="dashboard-title">🩺 Medical Billing Dashboard</div>
  <div class="row">
    <div class="col-md-6 col-lg-3 mb-4">
      <div class="card text-center bg-light">
        <div class="card-body">
          <i class="bi bi-file-medical icon-large text-primary"></i>
          <h5 class="card-title">Billing Form</h5>
          <p class="card-text">Create or update patient bills.</p>
          <a href="bill.php" class="btn btn-primary btn-sm">Go to Billing</a>
        </div>
      </div>
    </div>
    <div class="col-md-6 col-lg-3 mb-4">
      <div class="card text-center bg-light">
        <div class="card-body">
          <i class="bi bi-file-earmark-text icon-large text-success"></i>
          <h5 class="card-title">Patient Billing Report</h5>
          <p class="card-text">View patient-wise bills and transactions.</p>
          <a href="billing_report.php" class="btn btn-success btn-sm">View Report</a>
        </div>
      </div>
    </div>
    <div class="col-md-6 col-lg-3 mb-4">
      <div class="card text-center bg-light">
        <div class="card-body">
          <i class="bi bi-list-check icon-large text-info"></i>
          <h5 class="card-title">Service Summary</h5>
          <p class="card-text">Get summary by service name.</p>
          <a href="billing_service_summary.php" class="btn btn-info btn-sm">View Summary</a>
        </div>
      </div>
    </div>
    <div class="col-md-6 col-lg-3 mb-4">
      <div class="card text-center bg-light">
        <div class="card-body">
          <i class="bi bi-gear icon-large text-warning"></i>
          <h5 class="card-title">Manage Services</h5>
          <p class="card-text">Add, update or delete service items.</p>
          <a href="add_services.php" class="btn btn-warning btn-sm text-white">Manage</a>
        </div>
      </div>
    </div>
    <div class="col-md-6 col-lg-3 mb-4">
      <div class="card text-center backup-card">
        <div class="card-body">
          <i class="bi bi-cloud-arrow-up icon-large"></i>
          <h5 class="card-title">Backup Status</h5>
          <p class="card-text">Monitor and control database backups.</p>
          <a href="backup_status.php" class="btn btn-sm">View Status</a>
        </div>
      </div>
    </div>
  </div>
</div>
</body>
</html>