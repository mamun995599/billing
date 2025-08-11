<?php
include 'db_sqlite.php'; // Changed to SQLite database connection
$edit_service = null;

// Handle Add or Update
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $service_id = $_POST['service_id'];
    $service_name = $_POST['service_name'];
    $price = floatval($_POST['price']);
    
    if (isset($_POST['action']) && $_POST['action'] === 'update') {
        // UPDATE
        $stmt = $pdo->prepare("UPDATE services SET service_name = ?, price = ? WHERE service_id = ?");
        $stmt->execute([$service_name, $price, $service_id]);
        $message = "Service updated.";
    } else {
        // ADD
        $stmt = $pdo->prepare("INSERT INTO services (service_id, service_name, price) VALUES (?, ?, ?)");
        $stmt->execute([$service_id, $service_name, $price]);
        $message = "Service added.";
    }
    header("Location: add_services.php");
    exit;
}


// Handle Delete
if (isset($_GET['delete'])) {
    $stmt = $pdo->prepare("DELETE FROM services WHERE service_id = ?");
    $stmt->execute([$_GET['delete']]);
    header("Location: add_services.php");
    exit;
}

// Handle Edit
if (isset($_GET['edit'])) {
    $stmt = $pdo->prepare("SELECT * FROM services WHERE service_id = ?");
    $stmt->execute([$_GET['edit']]);
    $edit_service = $stmt->fetch(PDO::FETCH_ASSOC);
}

// Fetch all services
$stmt = $pdo->query("SELECT * FROM services ORDER BY service_name");
$services = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html>
<head>
    <title>Service Management</title>
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
    <style>
        body { font-family: Arial; padding: 20px; }
        .container { max-width: 1000px; }
        .table th, .table td { padding: .5rem; }
        .btn-sm { padding: .25rem .5rem; font-size: .875rem; }
    </style>
</head>
<body>
<div class="container">
    <h3 class="mb-4 text-center">Manage Services</h3>
    
    <!-- Form to Add / Update -->
    <form method="post" class="mb-4">
        <input type="hidden" name="action" value="<?= $edit_service ? 'update' : 'add' ?>">
        <div class="form-row">
            <div class="form-group col-md-3">
                <label>Service ID</label>
                <input type="text" name="service_id" class="form-control" required
                       value="<?= $edit_service['service_id'] ?? '' ?>" <?= $edit_service ? 'readonly' : '' ?>>
            </div>
            <div class="form-group col-md-5">
                <label>Service Name</label>
                <input type="text" name="service_name" class="form-control" required
                       value="<?= $edit_service['service_name'] ?? '' ?>">
            </div>
            <div class="form-group col-md-2">
                <label>Price</label>
                <input type="number" name="price" class="form-control" step="0.01" required
                       value="<?= $edit_service['price'] ?? '' ?>">
            </div>
            <div class="form-group col-md-2 d-flex align-items-end">
                <?php if ($edit_service): ?>
                    <button type="submit" class="btn btn-success mr-2">Update</button>
                    <a href="add_services.php" class="btn btn-secondary">Cancel</a>
                <?php else: ?>
                    <button type="submit" class="btn btn-primary btn-block">Add</button>
                <?php endif ?>
            </div>
        </div>
    </form>
    
    <!-- Service Table -->
    <table class="table table-bordered table-sm">
        <thead class="thead-dark">
        <tr>
            <th>Service ID</th>
            <th>Service Name</th>
            <th>Price (৳)</th>
            <th width="160">Actions</th>
        </tr>
        </thead>
        <tbody>
        <?php foreach ($services as $row): ?>
            <tr>
                <td><?= htmlspecialchars($row['service_id']) ?></td>
                <td><?= htmlspecialchars($row['service_name']) ?></td>
                <td><?= number_format($row['price'], 2) ?></td>
                <td>
                    <a href="?edit=<?= urlencode($row['service_id']) ?>" class="btn btn-sm btn-warning">Edit</a>
                    <a href="?delete=<?= urlencode($row['service_id']) ?>" class="btn btn-sm btn-danger"
                       onclick="return confirm('Are you sure to delete this service?')">Delete</a>
                </td>
            </tr>
        <?php endforeach ?>
        </tbody>
    </table>
    
    <!-- Back to Billing Button -->
    <div class="text-center mt-4">
        <a href="bill.php" class="btn btn-primary">Back to Billing</a>
    </div>
</div>
</body>
</html>