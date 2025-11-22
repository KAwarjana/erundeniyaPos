<?php
require_once 'auth.php';
Auth::requireAuth();

$saleId = $_GET['sale_id'] ?? 0;

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
    <title>Receipt - Sale #<?php echo str_pad($saleId, 5, '0', STR_PAD_LEFT); ?></title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Courier New', monospace;
            max-width: 80mm;
            margin: 0 auto;
            padding: 10mm;
            background: #fff;
        }
        
        .receipt {
            width: 100%;
        }
        
        .header {
            text-align: center;
            border-bottom: 2px dashed #000;
            padding-bottom: 10px;
            margin-bottom: 15px;
        }
        
        .header h1 {
            font-size: 20px;
            margin-bottom: 5px;
            font-weight: bold;
        }
        
        .header p {
            font-size: 11px;
            margin: 3px 0;
        }
        
        .info-section {
            margin-bottom: 15px;
            font-size: 12px;
            line-height: 1.6;
        }
        
        .info-section .label {
            font-weight: bold;
        }
        
        .divider {
            border-top: 1px dashed #000;
            margin: 10px 0;
        }
        
        .items-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 10px;
            font-size: 11px;
        }
        
        .items-table th {
            border-bottom: 1px solid #000;
            padding: 5px 0;
            text-align: left;
            font-weight: bold;
        }
        
        .items-table td {
            padding: 5px 0;
            vertical-align: top;
        }
        
        .items-table .item-name {
            width: 45%;
        }
        
        .items-table .item-qty {
            width: 15%;
            text-align: center;
        }
        
        .items-table .item-price {
            width: 20%;
            text-align: right;
        }
        
        .items-table .item-total {
            width: 20%;
            text-align: right;
        }
        
        .item-row {
            border-bottom: 1px dotted #ddd;
        }
        
        .item-details {
            font-size: 10px;
            color: #666;
        }
        
        .totals-section {
            border-top: 2px solid #000;
            padding-top: 10px;
            margin-top: 10px;
            font-size: 12px;
        }
        
        .totals-row {
            display: flex;
            justify-content: space-between;
            padding: 3px 0;
        }
        
        .totals-row.grand-total {
            font-size: 14px;
            font-weight: bold;
            border-top: 2px dashed #000;
            padding-top: 8px;
            margin-top: 5px;
        }
        
        .footer {
            text-align: center;
            margin-top: 20px;
            border-top: 2px dashed #000;
            padding-top: 10px;
            font-size: 11px;
        }
        
        .footer p {
            margin: 5px 0;
        }
        
        .thank-you {
            font-weight: bold;
            font-size: 13px;
            margin: 10px 0;
        }
        
        .barcode {
            text-align: center;
            margin: 15px 0;
            font-size: 10px;
        }
        
        .print-button {
            text-align: center;
            margin: 20px 0;
        }
        
        .print-button button {
            padding: 10px 20px;
            margin: 0 5px;
            font-size: 14px;
            cursor: pointer;
            border: 1px solid #333;
            background: #fff;
            border-radius: 3px;
        }
        
        .print-button button:hover {
            background: #f0f0f0;
        }
        
        @media print {
            body {
                margin: 0;
                padding: 5mm;
            }
            
            .print-button {
                display: none;
            }
            
            @page {
                size: 80mm auto;
                margin: 0;
            }
        }
    </style>
</head>
<body>
    <div class="print-button">
        <button onclick="window.print()">🖨️ Print Receipt</button>
        <button onclick="window.close()">✖️ Close</button>
    </div>

    <div class="receipt">
        <!-- Header -->
        <div class="header">
            <h1>AYURVEDIC PHARMACY</h1>
            <p>Quality Ayurvedic Products & Medicines</p>
            <p>No. 123, Main Street, Negombo, Sri Lanka</p>
            <p>Tel: +94 31 222 3333 | +94 77 123 4567</p>
            <p>Email: info@ayurvedapharmacy.lk</p>
        </div>

        <!-- Receipt Info -->
        <div class="info-section">
            <div><span class="label">Receipt No:</span> #<?php echo str_pad($saleId, 5, '0', STR_PAD_LEFT); ?></div>
            <div><span class="label">Date:</span> <?php echo date('d M Y, h:i A', strtotime($sale['sale_date'])); ?></div>
            <?php if ($sale['customer_name']): ?>
                <div class="divider"></div>
                <div><span class="label">Customer:</span> <?php echo htmlspecialchars($sale['customer_name']); ?></div>
                <?php if ($sale['contact_no']): ?>
                    <div><span class="label">Contact:</span> <?php echo htmlspecialchars($sale['contact_no']); ?></div>
                <?php endif; ?>
            <?php else: ?>
                <div><span class="label">Customer:</span> Walk-in Customer</div>
            <?php endif; ?>
            <div><span class="label">Cashier:</span> <?php echo htmlspecialchars($sale['user_name']); ?></div>
            <div><span class="label">Payment:</span> <?php echo strtoupper($sale['payment_type']); ?></div>
        </div>

        <div class="divider"></div>

        <!-- Items Table -->
        <table class="items-table">
            <thead>
                <tr>
                    <th class="item-name">Item</th>
                    <th class="item-qty">Qty</th>
                    <th class="item-price">Price</th>
                    <th class="item-total">Total</th>
                </tr>
            </thead>
            <tbody>
                <?php 
                $itemCount = 0;
                while ($item = $items->fetch_assoc()): 
                    $itemCount++;
                ?>
                    <tr class="item-row">
                        <td class="item-name">
                            <strong><?php echo htmlspecialchars($item['product_name']); ?></strong>
                            <?php if ($item['generic_name']): ?>
                                <div class="item-details"><?php echo htmlspecialchars($item['generic_name']); ?></div>
                            <?php endif; ?>
                            <?php if ($item['unit']): ?>
                                <div class="item-details"><?php echo htmlspecialchars($item['unit']); ?></div>
                            <?php endif; ?>
                        </td>
                        <td class="item-qty"><?php echo $item['quantity']; ?></td>
                        <td class="item-price"><?php echo number_format($item['unit_price'], 2); ?></td>
                        <td class="item-total"><?php echo number_format($item['total_price'], 2); ?></td>
                    </tr>
                <?php endwhile; ?>
            </tbody>
        </table>

        <!-- Totals Section -->
        <div class="totals-section">
            <div class="totals-row">
                <span>Subtotal:</span>
                <span>Rs. <?php echo number_format($sale['total_amount'], 2); ?></span>
            </div>
            <?php if ($sale['discount'] > 0): ?>
                <div class="totals-row">
                    <span>Discount:</span>
                    <span>- Rs. <?php echo number_format($sale['discount'], 2); ?></span>
                </div>
            <?php endif; ?>
            <div class="totals-row grand-total">
                <span>TOTAL:</span>
                <span>Rs. <?php echo number_format($sale['net_amount'], 2); ?></span>
            </div>
        </div>

        <div class="divider"></div>

        <!-- Additional Info -->
        <div class="info-section" style="font-size: 10px;">
            <div>Total Items: <?php echo $itemCount; ?></div>
            <?php if ($sale['payment_type'] === 'cash'): ?>
                <div>Payment Method: Cash</div>
            <?php else: ?>
                <div>Payment Method: Credit</div>
                <div style="font-style: italic; color: #666;">Please settle the payment within 30 days</div>
            <?php endif; ?>
        </div>

        <div class="divider"></div>

        <!-- Barcode (Sale ID) -->
        <div class="barcode">
            <div style="letter-spacing: 3px; font-size: 18px; font-weight: bold;">
                *<?php echo str_pad($saleId, 5, '0', STR_PAD_LEFT); ?>*
            </div>
        </div>

        <!-- Footer -->
        <div class="footer">
            <p class="thank-you">Thank You for Your Purchase!</p>
            <p>Please visit us again</p>
            <p style="margin-top: 10px; font-size: 10px;">
                For inquiries or concerns, please contact us at:<br>
                info@ayurvedapharmacy.lk | +94 77 123 4567
            </p>
            <div class="divider" style="margin-top: 10px;"></div>
            <p style="font-size: 9px; margin-top: 10px;">
                © <?php echo date('Y'); ?> Ayurvedic Pharmacy. All rights reserved.<br>
                Goods once sold cannot be returned or exchanged.
            </p>
        </div>
    </div>

    <script>
        // Optional: Auto-print on load (uncomment if needed)
        // window.onload = function() {
        //     window.print();
        // }
        
        // Print and close
        function printAndClose() {
            window.print();
            setTimeout(function() {
                window.close();
            }, 500);
        }
        
        // Keyboard shortcuts
        document.addEventListener('keydown', function(e) {
            // Ctrl+P or Cmd+P to print
            if ((e.ctrlKey || e.metaKey) && e.key === 'p') {
                e.preventDefault();
                window.print();
            }
            // ESC to close
            if (e.key === 'Escape') {
                window.close();
            }
        });
    </script>
</body>
</html>