<?php
require_once 'auth.php';
Auth::requireAuth();

$conn = getDBConnection();

// Get purchases
$purchases = $conn->query("SELECT 
    p.*,
    s.name as supplier_name,
    u.full_name as user_name,
    COUNT(pi.purchase_item_id) as item_count
FROM purchases p
LEFT JOIN suppliers s ON p.supplier_id = s.supplier_id
LEFT JOIN users u ON p.user_id = u.user_id
LEFT JOIN purchase_items pi ON p.purchase_id = pi.purchase_id
GROUP BY p.purchase_id
ORDER BY p.purchase_date DESC");

// Get suppliers for dropdown
$suppliers = $conn->query("SELECT supplier_id, name FROM suppliers ORDER BY name");
?>
<!doctype html>
<html lang="en" dir="ltr" data-bs-theme="light">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <title>Purchases | Ayurvedic Pharmacy</title>
    
    <link rel="shortcut icon" href="assets/images/logo_white.png">
    <link rel="stylesheet" href="assets/css/core/libs.min.css">
    <link rel="stylesheet" href="assets/css/hope-ui.min.css?v=5.0.0">
    <link rel="stylesheet" href="assets/css/custom.min.css?v=5.0.0">
</head>
<body>
    <div id="loading">
        <div class="loader simple-loader"><div class="loader-body"></div></div>
    </div>

    <?php include 'includes/sidebar.php'; ?>

    <main class="main-content">
        <?php include 'includes/header.php'; ?>
        
        <div class="conatiner-fluid content-inner mt-n5 py-0">
            <div class="row">
                <div class="col-sm-12">
                    <div class="card">
                        <div class="card-header d-flex justify-content-between">
                            <div class="header-title">
                                <h4 class="card-title">Purchase History</h4>
                            </div>
                            <div>
                                <p class="text-muted mb-0"><i>Note: New purchases are created through Stock Management by adding new batches.</i></p>
                            </div>
                        </div>
                        <div class="card-body">
                            <div class="table-responsive">
                                <table class="table table-striped">
                                    <thead>
                                        <tr>
                                            <th>Purchase ID</th>
                                            <th>Invoice No</th>
                                            <th>Date</th>
                                            <th>Supplier</th>
                                            <th>Items</th>
                                            <th>Total Amount</th>
                                            <th>Purchased By</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php if ($purchases->num_rows > 0): ?>
                                            <?php while ($purchase = $purchases->fetch_assoc()): ?>
                                                <tr>
                                                    <td><strong>#<?php echo str_pad($purchase['purchase_id'], 5, '0', STR_PAD_LEFT); ?></strong></td>
                                                    <td><?php echo htmlspecialchars($purchase['invoice_no'] ?? '-'); ?></td>
                                                    <td><?php echo date('M d, Y h:i A', strtotime($purchase['purchase_date'])); ?></td>
                                                    <td><?php echo htmlspecialchars($purchase['supplier_name'] ?? 'Unknown'); ?></td>
                                                    <td><?php echo $purchase['item_count']; ?></td>
                                                    <td><strong>Rs. <?php echo number_format($purchase['total_amount'], 2); ?></strong></td>
                                                    <td><?php echo htmlspecialchars($purchase['user_name']); ?></td>
                                                </tr>
                                            <?php endwhile; ?>
                                        <?php else: ?>
                                            <tr>
                                                <td colspan="7" class="text-center py-5">
                                                    <p class="text-muted">No purchases recorded yet</p>
                                                    <small>Add new product batches in Stock Management to create purchase records</small>
                                                </td>
                                            </tr>
                                        <?php endif; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Quick Guide Card -->
            <div class="row">
                <div class="col-sm-12">
                    <div class="card bg-soft-info">
                        <div class="card-body">
                            <h5 class="card-title">How to Record Purchases</h5>
                            <p class="card-text mb-2">To record a new purchase in the system:</p>
                            <ol class="mb-0">
                                <li>Go to <strong>Stock Management</strong> page</li>
                                <li>Click <strong>"Add New Batch"</strong> button</li>
                                <li>Select the product and enter batch details (Batch No, Expiry Date, Cost Price, Selling Price, Quantity)</li>
                                <li>The system will automatically create a purchase record when you add a new batch</li>
                            </ol>
                            <div class="mt-3">
                                <a href="stock_management.php" class="btn btn-info">Go to Stock Management</a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <?php include 'includes/footer.php'; ?>
    </main>

    <script src="assets/js/core/libs.min.js"></script>
    <script src="assets/js/core/external.min.js"></script>
    <script src="assets/js/hope-ui.js" defer></script>
</body>
</html>