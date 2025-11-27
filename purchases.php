<?php
require_once 'auth.php';
Auth::requireAuth();

$conn = getDBConnection();

// Get filter parameters
$dateFrom = $_GET['date_from'] ?? date('Y-m-01');
$dateTo = $_GET['date_to'] ?? date('Y-m-d');
$supplierId = $_GET['supplier_id'] ?? '';

// Build query with filters
$sql = "SELECT 
    p.*,
    s.name as supplier_name,
    u.full_name as user_name,
    COUNT(pi.purchase_item_id) as item_count
FROM purchases p
LEFT JOIN suppliers s ON p.supplier_id = s.supplier_id
LEFT JOIN users u ON p.user_id = u.user_id
LEFT JOIN purchase_items pi ON p.purchase_id = pi.purchase_id
WHERE DATE(p.purchase_date) BETWEEN ? AND ?";

$params = [$dateFrom, $dateTo];
$types = "ss";

if (!empty($supplierId)) {
    $sql .= " AND p.supplier_id = ?";
    $params[] = $supplierId;
    $types .= "i";
}

$sql .= " GROUP BY p.purchase_id ORDER BY p.purchase_date DESC";

$stmt = $conn->prepare($sql);
$stmt->bind_param($types, ...$params);
$stmt->execute();
$purchases = $stmt->get_result();

// Get summary
$summaryStmt = $conn->prepare("SELECT 
    COUNT(*) as total_purchases,
    SUM(total_amount) as total_amount
FROM purchases 
WHERE DATE(purchase_date) BETWEEN ? AND ?" . (!empty($supplierId) ? " AND supplier_id = ?" : ""));

if (!empty($supplierId)) {
    $summaryStmt->bind_param("ssi", $dateFrom, $dateTo, $supplierId);
} else {
    $summaryStmt->bind_param("ss", $dateFrom, $dateTo);
}

$summaryStmt->execute();
$summary = $summaryStmt->get_result()->fetch_assoc();

// Get suppliers for dropdown
$suppliers = $conn->query("SELECT supplier_id, name FROM suppliers ORDER BY name");
?>
<!doctype html>
<html lang="en" dir="ltr" data-bs-theme="light">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <title>E. W. D. Erundeniya</title>
    
    <link rel="shortcut icon" href="assets/images/logoblack.png">
    <link rel="stylesheet" href="assets/css/core/libs.min.css">
    <link rel="stylesheet" href="assets/css/hope-ui.min.css?v=5.0.0">
    <link rel="stylesheet" href="assets/css/custom.min.css?v=5.0.0">
    <link rel="stylesheet" href="assets/css/custom.css">
</head>
<body>
    <div id="loading">
        <div class="loader simple-loader"><div class="loader-body"></div></div>
    </div>

    <?php include 'includes/sidebar.php'; ?>

    <main class="main-content">
        <?php include 'includes/header.php'; ?>
        
        <div class="conatiner-fluid content-inner mt-n5 py-0">
            <!-- Summary Cards -->
            <div class="row mb-4">
                <div class="col-md-6">
                    <div class="card">
                        <div class="card-body">
                            <div class="d-flex align-items-center">
                                <div class="flex-grow-1">
                                    <p class="text-muted mb-1">Total Purchases</p>
                                    <h4 class="mb-0"><?php echo number_format(intval($summary['total_purchases'] ?? 0)); ?></h4>
                                </div>
                                <div class="rounded-circle bg-soft-primary p-3">
                                    <svg width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                        <path d="M3 9L12 2L21 9V20C21 21.1046 20.1046 22 19 22H5C3.89543 22 3 21.1046 3 20V9Z" stroke="currentColor" stroke-width="2" />
                                    </svg>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="card">
                        <div class="card-body">
                            <div class="d-flex align-items-center">
                                <div class="flex-grow-1">
                                    <p class="text-muted mb-1">Total Amount</p>
                                    <h4 class="mb-0 text-success">Rs. <?php echo number_format(floatval($summary['total_amount'] ?? 0), 2); ?></h4>
                                </div>
                                <div class="rounded-circle bg-soft-success p-3">
                                    <svg width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                        <path d="M12 2V22M17 5H9.5C7.01472 5 5 7.01472 5 9.5C5 11.9853 7.01472 14 9.5 14H14.5C16.9853 14 19 16.0147 19 18.5C19 20.9853 16.9853 23 14.5 23H6" stroke="currentColor" stroke-width="2" />
                                    </svg>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

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
                            <!-- Filters -->
                            <form method="GET" class="row g-3 mb-4">
                                <div class="col-md-3">
                                    <label class="form-label">Date From</label>
                                    <input type="date" class="form-control" name="date_from" value="<?php echo $dateFrom; ?>">
                                </div>
                                <div class="col-md-3">
                                    <label class="form-label">Date To</label>
                                    <input type="date" class="form-control" name="date_to" value="<?php echo $dateTo; ?>">
                                </div>
                                <div class="col-md-3">
                                    <label class="form-label">Supplier</label>
                                    <select class="form-select" name="supplier_id">
                                        <option value="">All Suppliers</option>
                                        <?php 
                                        $suppliers->data_seek(0);
                                        while ($supplier = $suppliers->fetch_assoc()): 
                                        ?>
                                            <option value="<?php echo $supplier['supplier_id']; ?>" 
                                                    <?php echo $supplierId == $supplier['supplier_id'] ? 'selected' : ''; ?>>
                                                <?php echo htmlspecialchars($supplier['name']); ?>
                                            </option>
                                        <?php endwhile; ?>
                                    </select>
                                </div>
                                <div class="col-md-3 d-flex align-items-end gap-2">
                                    <button type="submit" class="btn btn-primary">Filter</button>
                                    <a href="purchases.php" class="btn btn-secondary">Reset</a>
                                </div>
                            </form>

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
                                                    <p class="text-muted">No purchases found for the selected filters</p>
                                                    <small>Try adjusting your filter criteria or add new product batches in Stock Management</small>
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