<?php
require_once 'auth.php';
Auth::requireAuth();

$saleId = $_GET['sale_id'] ?? 0;
$paidAmount = floatval($_GET['paid'] ?? 0);
$changeAmount = floatval($_GET['change'] ?? 0);

if ($saleId <= 0) {
    die('Invalid sale ID');
}

$conn = getDBConnection();

// Get sale details
$stmt = $conn->prepare("SELECT 
    s.*,
    c.name as customer_name,
    c.contact_no,
    c.address,
    u.full_name as user_name
FROM sales s
LEFT JOIN customers c ON s.customer_id = c.customer_id
LEFT JOIN users u ON s.user_id = u.user_id
WHERE s.sale_id = ?");

$stmt->bind_param("i", $saleId);
$stmt->execute();
$saleResult = $stmt->get_result();

if ($saleResult->num_rows === 0) {
    die('Sale not found');
}

$sale = $saleResult->fetch_assoc();

// Get sale items
$stmt = $conn->prepare("SELECT 
    si.*,
    p.product_name,
    p.generic_name,
    p.unit,
    pb.batch_no
FROM sale_items si
JOIN product_batches pb ON si.batch_id = pb.batch_id
JOIN products p ON pb.product_id = p.product_id
WHERE si.sale_id = ?
ORDER BY p.product_name");

$stmt->bind_param("i", $saleId);
$stmt->execute();
$items = $stmt->get_result();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>View Sale - #<?php echo str_pad($saleId, 5, '0', STR_PAD_LEFT); ?></title>
    
    <link rel="stylesheet" href="assets/css/core/libs.min.css">
    <link rel="stylesheet" href="assets/css/hope-ui.min.css?v=5.0.0">
    
    <style>
        body {
            background: #f8f9fa;
            padding: 20px;
        }
        
        .sale-container {
            max-width: 900px;
            margin: 0 auto;
            background: white;
            border-radius: 10px;
            padding: 30px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        
        .sale-header {
            border-bottom: 2px solid #dee2e6;
            padding-bottom: 20px;
            margin-bottom: 20px;
        }
        
        .sale-header h2 {
            color: #495057;
            margin: 0;
        }
        
        .info-grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 20px;
            margin-bottom: 30px;
        }
        
        .info-card {
            background: #f8f9fa;
            padding: 15px;
            border-radius: 8px;
            border-left: 4px solid #007bff;
        }
        
        .info-label {
            font-size: 12px;
            color: #6c757d;
            text-transform: uppercase;
            font-weight: 600;
            margin-bottom: 5px;
        }
        
        .info-value {
            font-size: 16px;
            color: #212529;
            font-weight: 500;
        }
        
        .items-section {
            margin-top: 30px;
        }
        
        .section-title {
            font-size: 18px;
            font-weight: 600;
            color: #495057;
            margin-bottom: 15px;
            padding-bottom: 10px;
            border-bottom: 2px solid #dee2e6;
        }
        
        .table {
            margin-bottom: 20px;
        }
        
        .table thead th {
            background: #e9ecef;
            font-weight: 600;
            border-bottom: 2px solid #dee2e6;
        }
        
        .totals-card {
            background: #f8f9fa;
            padding: 20px;
            border-radius: 8px;
            margin-top: 20px;
        }
        
        .total-row {
            display: flex;
            justify-content: space-between;
            padding: 8px 0;
            font-size: 16px;
        }
        
        .total-row.grand-total {
            border-top: 2px solid #dee2e6;
            padding-top: 15px;
            margin-top: 10px;
            font-size: 20px;
            font-weight: bold;
            color: #28a745;
        }
        
        .action-buttons {
            margin-top: 30px;
            display: flex;
            gap: 10px;
            justify-content: center;
        }
        
        .btn-custom {
            padding: 12px 30px;
            font-size: 16px;
            font-weight: 600;
            border-radius: 8px;
        }
        
        .status-badge {
            display: inline-block;
            padding: 5px 15px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
        }
        
        .badge-cash {
            background: #d4edda;
            color: #155724;
        }
        
        .badge-credit {
            background: #fff3cd;
            color: #856404;
        }
    </style>
</head>
<body>
    <div class="sale-container">
        <!-- Header -->
        <div class="sale-header">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <h2>Sale Details</h2>
                    <p class="text-muted mb-0">Invoice #<?php echo str_pad($saleId, 5, '0', STR_PAD_LEFT); ?></p>
                </div>
                <div>
                    <span class="status-badge badge-<?php echo $sale['payment_type']; ?>">
                        <?php echo strtoupper($sale['payment_type']); ?>
                    </span>
                </div>
            </div>
        </div>

        <!-- Sale Information -->
        <div class="info-grid">
            <div class="info-card">
                <div class="info-label">Date & Time</div>
                <div class="info-value"><?php echo date('d M Y, h:i A', strtotime($sale['sale_date'])); ?></div>
            </div>
            
            <div class="info-card">
                <div class="info-label">Cashier</div>
                <div class="info-value"><?php echo htmlspecialchars($sale['user_name']); ?></div>
            </div>
            
            <div class="info-card">
                <div class="info-label">Customer</div>
                <div class="info-value"><?php echo htmlspecialchars($sale['customer_name'] ?? 'Walk-in Customer'); ?></div>
                <?php if ($sale['contact_no']): ?>
                    <div class="text-muted" style="font-size: 14px; margin-top: 5px;">
                        📱 <?php echo htmlspecialchars($sale['contact_no']); ?>
                    </div>
                <?php endif; ?>
            </div>
            
            <div class="info-card">
                <div class="info-label">Payment Method</div>
                <div class="info-value"><?php echo ucfirst($sale['payment_type']); ?></div>
            </div>
        </div>

        <!-- Items Section -->
        <div class="items-section">
            <h3 class="section-title">Items Purchased</h3>
            
            <div class="table-responsive">
                <table class="table table-hover">
                    <thead>
                        <tr>
                            <th style="width: 5%;">#</th>
                            <th style="width: 40%;">Product</th>
                            <th style="width: 15%;">Batch No</th>
                            <th style="width: 12%;" class="text-center">Quantity</th>
                            <th style="width: 14%;" class="text-end">Unit Price</th>
                            <th style="width: 14%;" class="text-end">Total</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php 
                        $itemNo = 1;
                        while ($item = $items->fetch_assoc()): 
                        ?>
                            <tr>
                                <td><?php echo $itemNo++; ?></td>
                                <td>
                                    <strong><?php echo htmlspecialchars($item['product_name']); ?></strong>
                                    <?php if ($item['generic_name']): ?>
                                        <br><small class="text-muted"><?php echo htmlspecialchars($item['generic_name']); ?></small>
                                    <?php endif; ?>
                                </td>
                                <td><?php echo htmlspecialchars($item['batch_no']); ?></td>
                                <td class="text-center">
                                    <strong><?php echo floatval($item['quantity']); ?></strong>
                                </td>
                                <td class="text-end">Rs. <?php echo number_format($item['unit_price'], 2); ?></td>
                                <td class="text-end"><strong>Rs. <?php echo number_format($item['total_price'], 2); ?></strong></td>
                            </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Totals -->
        <div class="totals-card">
            <div class="total-row">
                <span>Subtotal:</span>
                <span>Rs. <?php echo number_format($sale['total_amount'], 2); ?></span>
            </div>
            
            <?php if ($sale['discount'] > 0): ?>
                <div class="total-row">
                    <span>Discount:</span>
                    <span class="text-danger">- Rs. <?php echo number_format($sale['discount'], 2); ?></span>
                </div>
            <?php endif; ?>
            
            <div class="total-row grand-total">
                <span>TOTAL AMOUNT:</span>
                <span>Rs. <?php echo number_format($sale['net_amount'], 2); ?></span>
            </div>
        </div>

        <!-- Action Buttons -->
        <div class="action-buttons">
            <button class="btn btn-success btn-custom" onclick="printSale()">
                🖨️ Print Receipt
            </button>
            <button class="btn btn-secondary btn-custom" onclick="window.close()">
                ✖️ Close
            </button>
        </div>
    </div>

    <script src="assets/js/core/libs.min.js"></script>
    
    <script>
        function printSale() {
            window.open('print_receipt.php?sale_id=<?php echo $saleId; ?>', '_blank');
        }
        
        // Keyboard shortcuts
        document.addEventListener('keydown', function(e) {
            // Ctrl+P to print
            if ((e.ctrlKey || e.metaKey) && e.key === 'p') {
                e.preventDefault();
                printSale();
            }
            // ESC to close
            if (e.key === 'Escape') {
                window.close();
            }
        });
    </script>
</body>
</html>