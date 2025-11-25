<?php
require_once 'config.php';
require_once 'auth.php';

Auth::requireAuth();

$conn = getDBConnection();

// Get all product batches with product info
$batches = $conn->query("SELECT 
    pb.batch_id,
    pb.batch_no,
    pb.expiry_date,
    pb.cost_price,
    pb.selling_price,
    pb.quantity_in_stock,
    p.product_id,
    p.product_name,
    p.generic_name,
    p.unit,
    DATEDIFF(pb.expiry_date, CURDATE()) as days_to_expiry
FROM product_batches pb
JOIN products p ON pb.product_id = p.product_id
ORDER BY p.product_name, pb.expiry_date");

// Set headers for CSV download
header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename=stock_report_' . date('Y-m-d_His') . '.csv');
header('Pragma: no-cache');
header('Expires: 0');

// Create output stream
$output = fopen('php://output', 'w');

// Add BOM for proper UTF-8 encoding in Excel
fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF));

// Add CSV headers
fputcsv($output, [
    'Product ID',
    'Product Name',
    'Generic Name',
    'Batch Number',
    'Expiry Date',
    'Days to Expiry',
    'Cost Price (Rs.)',
    'Selling Price (Rs.)',
    'Stock Quantity',
    'Unit',
    'Status'
]);

// Add data rows
while ($batch = $batches->fetch_assoc()) {
    // Determine status
    $status = 'Good';
    if ($batch['days_to_expiry'] < 0) {
        $status = 'Expired';
    } elseif ($batch['days_to_expiry'] <= 30) {
        $status = 'Expiring Soon';
    } elseif ($batch['days_to_expiry'] <= 90) {
        $status = 'Near Expiry';
    }
    
    if ($batch['quantity_in_stock'] == 0) {
        $status = 'Out of Stock';
    }
    
    fputcsv($output, [
        $batch['product_id'],
        $batch['product_name'],
        $batch['generic_name'] ?? '',
        $batch['batch_no'],
        date('Y-m-d', strtotime($batch['expiry_date'])),
        $batch['days_to_expiry'],
        number_format($batch['cost_price'], 2),
        number_format($batch['selling_price'], 2),
        $batch['quantity_in_stock'],
        $batch['unit'] ?? '',
        $status
    ]);
}

fclose($output);
exit;
?>