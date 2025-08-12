<?php
include 'db_sqlite.php'; // Changed to SQLite database connection
// Fetch doctor list
$doctor_stmt = $pdo->query("SELECT DISTINCT ref_doctors FROM patients WHERE ref_doctors IS NOT NULL AND ref_doctors != '' ORDER BY ref_doctors");
$doctors = $doctor_stmt->fetchAll(PDO::FETCH_COLUMN);
// Default: current month start and end
$startDate = $_GET['start_date'] ?? date('Y-m-01');
$endDate = $_GET['end_date'] ?? date('Y-m-t');
// Build query with optional doctor filter
$sql = "
  SELECT 
    p.date,
    p.patient_id,
    p.patient_name,
    p.phone,
    p.ref_doctors AS doctor,
    p.ref_name,
    GROUP_CONCAT(b.service_name, '; ') AS services,
    SUM(b.final_price) AS total,
    p.less_total,
    p.paid,
    (SUM(b.final_price) - p.less_total - p.paid) AS due
  FROM billing b
  JOIN patients p ON b.patient_id = p.patient_id
  WHERE p.date BETWEEN ? AND ?
";
$params = [$startDate, $endDate];
// Add doctor filter if specified
if (!empty($_GET['doctor'])) {
    if ($_GET['doctor'] === 'EMPTY') {
        // Filter for patients with empty doctor
        $sql .= " AND (p.ref_doctors IS NULL OR p.ref_doctors = '')";
    } else {
        // Filter for patients with specific doctor
        $sql .= " AND p.ref_doctors = ?";
        $params[] = $_GET['doctor'];
    }
}
$sql .= " GROUP BY p.patient_id, p.date, p.patient_name, p.phone, p.ref_doctors, p.ref_name, p.paid, p.less_total
  ORDER BY p.date DESC";
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html>
<head>
  <title>Billing Timeline</title>
  <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
  <link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/jquery.dataTables.min.css">
  <link rel="stylesheet" href="https://cdn.datatables.net/buttons/2.4.1/css/buttons.dataTables.min.css">
  <style>
    body { font-family: Arial; padding: 20px; }
    .container { max-width: 1400px; }
    .table th, .table td { padding: .5rem; }
    .form-control { font-size: 0.9rem; }
    .btn { font-size: 0.9rem; }
    #billingTable { font-size: 0.85rem; }
    .dataTables_wrapper { padding: 10px; }
    .dt-buttons { margin-bottom: 10px; }
    .discount-column { background-color: #f8f9fa; }
  </style>
</head>
<body>
<div class="container py-4">
  <h3 class="mb-4 text-center">Investigation Billing Report</h3>
  
  <form method="GET" class="form-inline mb-4 bg-light p-3 rounded">
    <label class="mr-2">Doctor</label>
    <select name="doctor" id="doctorFilter" class="form-control mr-3">
      <option value="">All Doctors</option>
      <option value="EMPTY" <?= (isset($_GET['doctor']) && $_GET['doctor'] === 'EMPTY') ? 'selected' : '' ?>>Blank</option>
      <?php foreach ($doctors as $doc): ?>
        <option value="<?= htmlspecialchars($doc) ?>" <?= (isset($_GET['doctor']) && $_GET['doctor'] === $doc) ? 'selected' : '' ?>>
          <?= htmlspecialchars($doc) ?>
        </option>
      <?php endforeach ?>
    </select>
    <label class="mr-2">Start Date</label>
    <input type="date" name="start_date" class="form-control mr-3" value="<?= $startDate ?>">
    <label class="mr-2">End Date</label>
    <input type="date" name="end_date" class="form-control mr-3" value="<?= $endDate ?>">
    <button class="btn btn-primary" type="submit">Filter</button>
  </form>
  
  <table id="billingTable" class="table table-bordered table-striped">
    <thead class="thead-dark">
      <tr>
        <th>Date</th>
        <th>Patient ID</th>
        <th>Patient Name</th>
        <th>Phone</th>
        <th>Doctor</th>
        <th>Ref Name</th>
        <th>Services</th>
        <th>Total</th>
        <th>Less</th>
        <th>Paid</th>
        <th>Due</th>
      </tr>
    </thead>
    <tbody>
      <?php foreach ($rows as $row): ?>
        <tr>
          <td><?= $row['date'] ?></td>
          <td><?= $row['patient_id'] ?></td>
          <td><?= htmlspecialchars($row['patient_name']) ?></td>
          <td><?= htmlspecialchars($row['phone']) ?></td>
          <td><?= htmlspecialchars($row['doctor'] ?? 'N/A') ?></td>
          <td><?= htmlspecialchars($row['ref_name'] ?? 'N/A') ?></td>
          <td><?= htmlspecialchars($row['services']) ?></td>
          <td><?= number_format($row['total'], 2) ?></td>
          <td class="discount-column"><?= number_format($row['less_total'], 2) ?></td>
          <td><?= number_format($row['paid'], 2) ?></td>
          <td><?= number_format($row['due'], 2) ?></td>
        </tr>
      <?php endforeach ?>
    </tbody>
    <tfoot>
      <tr>
        <th colspan="7" class="text-right">Total</th>
        <th></th>
        <th></th>
        <th></th>
        <th></th>
      </tr>
    </tfoot>
  </table>
  
  <div class="text-center mt-3">
    <a href="bill.php" class="btn btn-primary">Back to Billing</a>
  </div>
</div>
<!-- Scripts -->
<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
<script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/buttons/2.4.1/js/dataTables.buttons.min.js"></script>
<script src="https://cdn.datatables.net/buttons/2.4.1/js/buttons.html5.min.js"></script>
<script src="https://cdn.datatables.net/buttons/2.4.1/js/buttons.print.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/jszip/3.10.1/jszip.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.2.7/pdfmake.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.2.7/vfs_fonts.js"></script>
<script>
$(document).ready(function () {
  $('#billingTable').DataTable({
    dom: 'Bfrtip',
    buttons: ['excel', 'csv', 'pdf', 'print'],
    pageLength: 25,
    footerCallback: function (row, data, start, end, display) {
      const intVal = i => typeof i === 'string' ? parseFloat(i.replace(/,/g, '')) : (typeof i === 'number' ? i : 0);
      const api = this.api();
      
      // Update totals for columns 7 (Total), 8 (Less), 9 (Paid), 10 (Due)
      const total = api.column(7, { page: 'current' }).data().reduce((a, b) => intVal(a) + intVal(b), 0);
      const discount = api.column(8, { page: 'current' }).data().reduce((a, b) => intVal(a) + intVal(b), 0);
      const paid = api.column(9, { page: 'current' }).data().reduce((a, b) => intVal(a) + intVal(b), 0);
      const due = api.column(10, { page: 'current' }).data().reduce((a, b) => intVal(a) + intVal(b), 0);
      
      $(api.column(7).footer()).html(total.toFixed(2));
      $(api.column(8).footer()).html(discount.toFixed(2));
      $(api.column(9).footer()).html(paid.toFixed(2));
      $(api.column(10).footer()).html(due.toFixed(2));
    }
  });
});
</script>
</body>
</html>