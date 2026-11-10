<?php

require_once "../includes/db.php";
require_once "../includes/auth.php";

// Validate date format
function isValidDate($date)
{
    $d = DateTime::createFromFormat('!Y-m-d', $date);

    return $d && $d->format('Y-m-d') === $date;
}

// Date filters
$from_date = $_GET['from_date'] ?? date('Y-m-01');
$to_date = $_GET['to_date'] ?? date('Y-m-d');

// Validate dates
if (!isValidDate($from_date)) {
    $from_date = date('Y-m-01');
}

if (!isValidDate($to_date)) {
    $to_date = date('Y-m-d');
}

// Ensure dates are in correct order
if ($from_date > $to_date) {
    $temp = $from_date;
    $from_date = $to_date;
    $to_date = $temp;
}


// Fetch orders
$stmt = $pdo->prepare("
    SELECT
        o.order_number,
        c.customer_name,
        c.mobile,
        c.address,
        o.total_amount,
        o.delivery_charge,
        o.payment_method,
        o.payment_status,
        o.order_status,
        o.created_at
    FROM orders o
    LEFT JOIN customers c ON o.customer_id = c.id
    WHERE DATE(o.created_at) BETWEEN ? AND ?
    ORDER BY o.created_at DESC
");

$stmt->execute([$from_date, $to_date]);

$orders = $stmt->fetchAll(PDO::FETCH_ASSOC);


// Set download headers
header('Content-Type: text/csv; charset=UTF-8');

header(
    'Content-Disposition: attachment; filename="dubey-kirana-orders.csv"'
);

header('Cache-Control: no-store, no-cache, must-revalidate');


// Open output stream
$output = fopen('php://output', 'w');


// UTF-8 BOM for Excel compatibility
fwrite($output, "\xEF\xBB\xBF");


// CSV headings
fputcsv($output, [
    'Order Number',
    'Customer Name',
    'Mobile',
    'Address',
    'Total Amount',
    'Delivery Charge',
    'Payment Method',
    'Payment Status',
    'Order Status',
    'Order Date'
]);


// Export data
foreach ($orders as $order) {

    fputcsv($output, [
        $order['order_number'],
        $order['customer_name'],
        $order['mobile'],
        $order['address'],
        $order['total_amount'],
        $order['delivery_charge'],
        $order['payment_method'],
        $order['payment_status'],
        $order['order_status'],
        $order['created_at']
    ]);

}


// Close output stream
fclose($output);

exit;

?>