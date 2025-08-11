<?php
include 'db_sqlite.php'; // Changed to SQLite database connection
// Get start and end date (default to current month)
$startDate = $_GET['start_date'] ?? date('Y-m-01');
$endDate = $_GET['end_date'] ?? date('Y-m-t');

// Fetch summary per service
$sql = "
SELECT 
    b.service_name,
    SUM(b.unit) AS total_quantity,
    SUM(b.price * b.unit) AS total_amount,
    SUM(b.less) AS total_less,
    (SUM(b.price * b.unit) - SUM(b.less)) AS net_amount
FROM billing b
JOIN patients p ON b.patient_id = p.patient_id
WHERE p.date BETWEEN ? AND ?
GROUP BY b.service_name
ORDER BY b.service_name
";
$stmt = $pdo->prepare($sql);
$stmt->execute([$startDate, $endDate]);
$rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Calculate grand totals
$grand_total_quantity = 0;
$grand_total_amount = 0;
$grand_total_less = 0;
$grand_net_amount = 0;

foreach ($rows as $row) {
    $grand_total_quantity += $row['total_quantity'];
    $grand_total_amount += $row['total_amount'];
    $grand_total_less += $row['total_less'];
    $grand_net_amount += $row['net_amount'];
}
?>
<!DOCTYPE html>
<html>
<head>
  <title>Service Billing Summary</title>
  <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
  <link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/jquery.dataTables.min.css">
  <link rel="stylesheet" href="https://cdn.datatables.net/buttons/2.4.1/css/buttons.dataTables.min.css">
  <style>
    body { font-family: Arial; padding: 20px; background-color: #f8f9fa; }
    .container { max-width: 1200px; }
    .table th, .table td { padding: .5rem; }
    .table-striped tbody tr:nth-of-type(odd) { background-color: rgba(0,0,0,.02); }
    .thead-dark th { background-color: #343a40; color: white; }
    .text-primary { color: #007bff !important; }
    .text-danger { color: #dc3545 !important; }
    .text-success { color: #28a745 !important; }
    .font-weight-bold { font-weight: 700; }
    .bg-light { background-color: #f8f9fa !important; }
    .btn { font-size: 0.9rem; }
    .form-control { font-size: 0.9rem; }
    .dataTables_wrapper { padding: 10px; }
    .dt-buttons { margin-bottom: 10px; }
    .less-column { background-color: #fff8f8; }
    .net-column { background-color: #f8fff8; }
  </style>
</head>
<body>
<div class="container mt-4">
  <h3 class="mb-4 text-center">Billing Summary by Service</h3>
  
  <form method="get" class="form-inline mb-3 justify-content-center">
    <label class="mr-2">Start Date:</label>
    <input type="date" name="start_date" class="form-control mr-3" value="<?= $startDate ?>">
    <label class="mr-2">End Date:</label>
    <input type="date" name="end_date" class="form-control mr-3" value="<?= $endDate ?>">
    <button type="submit" class="btn btn-primary">Filter</button>
  </form>
  
  <table id="servicesTable" class="table table-bordered table-striped table-sm">
    <thead class="thead-dark">
      <tr>
        <th>SL</th>
        <th>Service Name</th>
        <th>Total Quantity</th>
        <th class="text-primary">Total Amount</th>
        <th class="text-danger">Total Less</th>
        <th class="text-success">Net Amount</th>
      </tr>
    </thead>
    <tbody>
      <?php
      foreach ($rows as $i => $row):
      ?>
      <tr>
        <td><?= $i + 1 ?></td>
        <td><?= htmlspecialchars($row['service_name']) ?></td>
        <td><?= $row['total_quantity'] ?></td>
        <td class="text-primary"><?= number_format($row['total_amount'], 2) ?></td>
        <td class="text-danger less-column"><?= number_format($row['total_less'], 2) ?></td>
        <td class="text-success net-column"><?= number_format($row['net_amount'], 2) ?></td>
      </tr>
      <?php endforeach; ?>
    </tbody>
    <tfoot class="font-weight-bold bg-light">
      <tr>
        <td colspan="2" class="text-right">Total:</td>
        <td><?= $grand_total_quantity ?></td>
        <td class="text-primary"><?= number_format($grand_total_amount, 2) ?></td>
        <td class="text-danger"><?= number_format($grand_total_less, 2) ?></td>
        <td class="text-success"><?= number_format($grand_net_amount, 2) ?></td>
      </tr>
    </tfoot>
  </table>
  
  <div class="text-center mt-4">
    <a href="bill.php" class="btn btn-primary">Back to Billing</a>
    <a href="billing_report.php" class="btn btn-secondary ml-2">Billing Report</a>
  </div>
</div>
<!-- Scripts for DataTables -->
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
  $('#servicesTable').DataTable({
    dom: 'Bfrtip',
    buttons: ['excel', 'csv', 'pdf', 'print'],
    pageLength: 25,
    footerCallback: function (row, data, start, end, display) {
      const intVal = i => typeof i === 'string' ? parseFloat(i.replace(/,/g, '')) : (typeof i === 'number' ? i : 0);
      const api = this.api();
      
      // Calculate totals for columns 2, 3, 4, 5 (Quantity, Amount, Less, Net)
      const totalQuantity = api.column(2, { page: 'current' }).data().reduce((a, b) => intVal(a) + intVal(b), 0);
      const totalAmount = api.column(3, { page: 'current' }).data().reduce((a, b) => intVal(a) + intVal(b), 0);
      const totalLess = api.column(4, { page: 'current' }).data().reduce((a, b) => intVal(a) + intVal(b), 0);
      const totalNet = api.column(5, { page: 'current' }).data().reduce((a, b) => intVal(a) + intVal(b), 0);
      
      // Update footer
      $(api.column(2).footer()).html(totalQuantity);
      $(api.column(3).footer()).html(totalAmount.toFixed(2));
      $(api.column(4).footer()).html(totalLess.toFixed(2));
      $(api.column(5).footer()).html(totalNet.toFixed(2));
    }
  });
});
</script>
</body>
</html>