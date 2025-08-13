<?php
include 'db_sqlite.php';
$patient_id = $_GET['patient_id'] ?? '';
$stmt = $pdo->prepare("SELECT * FROM patients WHERE patient_id = ?");
$stmt->execute([$patient_id]);
$patient = $stmt->fetch(PDO::FETCH_ASSOC);
$stmt = $pdo->prepare("SELECT * FROM billing WHERE patient_id = ?");
$stmt->execute([$patient_id]);
$services = $stmt->fetchAll(PDO::FETCH_ASSOC);
// Calculate totals
$total = 0;
foreach ($services as $s) {
    $total += $s['final_price'];
}
$less = $patient['less_total'];
$paid = $patient['paid'];
$due = ($total - $less) - $paid;
// Function to convert number to words
function numberToWords($number) {
    $hyphen      = '-';
    $conjunction = ' and ';
    $separator   = ', ';
    $negative    = 'negative ';
    $decimal     = ' point ';
    $dictionary  = array(
        0                   => 'zero',
        1                   => 'one',
        2                   => 'two',
        3                   => 'three',
        4                   => 'four',
        5                   => 'five',
        6                   => 'six',
        7                   => 'seven',
        8                   => 'eight',
        9                   => 'nine',
        10                  => 'ten',
        11                  => 'eleven',
        12                  => 'twelve',
        13                  => 'thirteen',
        14                  => 'fourteen',
        15                  => 'fifteen',
        16                  => 'sixteen',
        17                  => 'seventeen',
        18                  => 'eighteen',
        19                  => 'nineteen',
        20                  => 'twenty',
        30                  => 'thirty',
        40                  => 'forty',
        50                  => 'fifty',
        60                  => 'sixty',
        70                  => 'seventy',
        80                  => 'eighty',
        90                  => 'ninety',
        100                 => 'hundred',
        1000                => 'thousand',
        1000000             => 'million',
        1000000000          => 'billion',
        1000000000000       => 'trillion',
        1000000000000000    => 'quadrillion',
        1000000000000000000 => 'quintillion'
    );
    if (!is_numeric($number)) {
        return false;
    }
    if (($number >= 0 && (int) $number < 0) || (int) $number < 0 - PHP_INT_MAX) {
        // overflow
        trigger_error(
            'numberToWords only accepts numbers between -' . PHP_INT_MAX . ' and ' . PHP_INT_MAX,
            E_USER_WARNING
        );
        return false;
    }
    if ($number < 0) {
        return $negative . numberToWords(abs($number));
    }
    $string = $fraction = null;
    if (strpos($number, '.') !== false) {
        list($number, $fraction) = explode('.', $number);
    }
    switch (true) {
        case $number < 21:
            $string = $dictionary[$number];
            break;
        case $number < 100:
            $tens   = ((int) ($number / 10)) * 10;
            $units   = $number % 10;
            $string = $dictionary[$tens];
            if ($units) {
                $string .= $hyphen . $dictionary[$units];
            }
            break;
        case $number < 1000:
            $hundreds  = $number / 100;
            $remainder = $number % 100;
            $string = $dictionary[$hundreds] . ' ' . $dictionary[100];
            if ($remainder) {
                $string .= $conjunction . numberToWords($remainder);
            }
            break;
        default:
            $baseUnit = pow(1000, floor(log($number, 1000)));
            $numBaseUnits = (int) ($number / $baseUnit);
            $remainder = $number % $baseUnit;
            $string = numberToWords($numBaseUnits) . ' ' . $dictionary[$baseUnit];
            if ($remainder) {
                $string .= $remainder < 100 ? $conjunction : $separator;
                $string .= numberToWords($remainder);
            }
            break;
    }
    if (null !== $fraction && is_numeric($fraction)) {
        $string .= $decimal;
        $words = array();
        foreach (str_split((string) $fraction) as $number) {
            $words[] = $dictionary[$number];
        }
        $string .= implode(' ', $words);
    }
    return $string;
}
// Format the total amount in words
$totalInWords = numberToWords($total) . ' taka only';
?>
<!DOCTYPE html>
<html>
<head>
  <title>Receipt</title>
  <style>
    body { 
      font-family: Arial, sans-serif; 
      padding: 20px;
      background-color: #f5f5f5;
    }
    .receipt {
      position: relative;
      border: 1px solid #000;
      padding: 20px;
      width: 700px;
      margin: auto;
      background-color: white;
      box-shadow: 0 0 10px rgba(0,0,0,0.1);
    }
    .title { 
      text-align: center; 
      font-size: 24px; 
      font-weight: bold; 
      margin-bottom: 20px;
      color: #333;
    }
    table { 
      width: 100%; 
      border-collapse: collapse; 
      margin-top: 10px; 
    }
    td, th { 
      border: 1px solid #000; 
      padding: 3px; 
      font-size: 13px;
    }
    .text-right { 
      text-align: right; 
    }
    .btn-print { 
      margin: 10px auto; 
      display: block; 
      padding: 10px 20px;
      background-color: #007bff;
      color: white;
      border: none;
      border-radius: 5px;
      cursor: pointer;
      text-decoration: none;
      text-align: center;
    }
    .btn-print:hover {
      background-color: #0056b3;
    }
    /* Paid / Due Status */
    .status-box {
      position: absolute;
      bottom: 20px;
      right: 30px;
    }
    .paid {
      font-size: 25px;
      font-weight: bold;
      color: green;
      border: 2px solid green;
      padding: 8px 16px;
      display: inline-block;
      border-radius: 10px;
      background-color: rgba(0, 128, 0, 0.1);
    }
    .due {
      font-size: 25px;
      font-weight: bold;
      color: red;
      border: 2px solid red;
      padding: 8px 16px;
      display: inline-block;
      border-radius: 10px;
      background-color: rgba(255, 0, 0, 0.1);
    }
    .receipt-info {
      margin-bottom: 2px;
      font-size: 14px;
      text-align: left; /* Ensure left alignment for the entire div */
    }
    .receipt-info strong {
      display: inline-block;
      width: 120px;
      text-align: right; /* Right-align the labels */
      padding-right: 2px; /* Add space between label and value */
    }
    .footer-info {
      margin-top: 10px;
      font-size: 14px;
      text-align: left; /* Ensure left alignment for the entire div */
    }
    .footer-info strong {
      display: inline-block;
      width: 120px;
      text-align: right; /* Right-align the labels */
      padding-right: 2px; /* Add space between label and value */
    }
    @media print {
      body { background-color: white; }
      .btn-print { display: none; }
      .receipt { box-shadow: none; }
    }
  </style>
</head>
<body>
<div class="receipt" id="printArea">
  <div class="title">MONEY RECEIPT</div>
  
  <div class="receipt-info">
    <strong>Patient Id:</strong> <?= $patient['patient_id'] ?> <strong>Prev ID. NO:</strong> <?= $patient['old_id'] ?> <strong>Date:</strong> <?= date('d/m/Y', strtotime($patient['date'])) ?> <strong>Time:</strong> <?= date('h:i A', strtotime($patient['created_at'])) ?>
  </div>
  
  <div class="receipt-info">
    <strong>Name:</strong> <?= $patient['patient_name'] ?> <strong>Sex:</strong> <?= $patient['sex'] ?> <strong>Age:</strong> <?= $patient['age'] ?> 
  </div>
  <div class="receipt-info">
    <strong>Address:</strong> <?= $patient['address'] ?>
  </div>
  <div class="receipt-info">
    <strong>Mobile:</strong> <?= $patient['phone'] ?>
  </div>
  <div class="receipt-info">
    <strong>Email:</strong> <?= $patient['email'] ?? 'N/A' ?>
  </div>
  <div class="receipt-info">
    <strong>Ref.Doctor:</strong> <?= $patient['ref_doctors'] ?>
  </div>
  
  <table>
    <thead>
      <tr>
        <th>SL</th>
        <th>Investigation Name</th>
        <th>Qty</th>
        <th>Amount</th>
      </tr>
    </thead>
    <tbody>
      <?php
        foreach ($services as $index => $s) {
          $lineTotal = $s['final_price'];
          echo "<tr>
                  <td>" . ($index + 1) . "</td>
                  <td>{$s['service_name']}</td>
                  <td>{$s['unit']}</td>
                  <td class='text-right'>" . number_format($lineTotal, 2) . "</td>
                </tr>";
        }
      ?>
    </tbody>
    <tfoot>
      <tr><th colspan="3" class="text-right">Total</th><th class="text-right"><?= number_format($total, 2) ?></th></tr>
      <tr><th colspan="3" class="text-right">Less</th><th class="text-right"><?= number_format($patient['less_total'], 2) ?></th></tr>
      <tr><th colspan="3" class="text-right">Payable</th><th class="text-right"><?= number_format($total - $patient['less_total'], 2) ?></th></tr>
      <tr><th colspan="3" class="text-right">Paid</th><th class="text-right"><?= number_format($patient['paid'], 2) ?></th></tr>
      <tr><th colspan="3" class="text-right">Due</th><th class="text-right"><?= number_format(($total - $patient['less_total']) - $patient['paid'], 2) ?></th></tr>
    </tfoot>
  </table>
  
  <div class="footer-info">
    <strong>Delivery Date:</strong> <?= date('d/m/Y', strtotime($patient['delivery_date'])) ?> <strong>Time:</strong> <?= date('h:i A', strtotime($patient['delivery_time'])) ?>
  </div>
  
  <div class="footer-info">
    <strong>Inwords:</strong> <?= ucwords($totalInWords) ?>
  </div>
  
  <div class="footer-info">
    <strong>Remarks:</strong> <?= $patient['remarks'] ?>
  </div>
  
  <!-- Paid / Due status -->
  <div class="status-box">
    <?php if ($due <= 0): ?>
      <div class="paid">PAID</div>
    <?php else: ?>
      <div class="due">DUE: <?= number_format($due, 2) ?></div>
    <?php endif; ?>
  </div>
</div>
<button class="btn-print" onclick="window.print()">🖨️ Print</button>
<a href="bill.php" class="btn-print">➕ New Billing</a>
</body>
</html>