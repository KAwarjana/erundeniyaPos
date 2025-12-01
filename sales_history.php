<?php
require_once 'config.php';
require_once 'auth.php';
Auth::requireAuth();

$conn = getDBConnection();

// Get filter parameters
$dateFrom = $_GET['date_from'] ?? '';
$dateTo = $_GET['date_to'] ?? '';
$paymentType = $_GET['payment_type'] ?? '';

// Build the query
$sql = "SELECT 
    s.sale_id,
    s.sale_date,
    s.payment_type,
    s.total_amount,
    s.discount,
    s.net_amount,
    c.name as customer_name,
    u.full_name as user_name,
    COUNT(si.sale_item_id) as item_count
FROM sales s
LEFT JOIN customers c ON s.customer_id = c.customer_id
LEFT JOIN users u ON s.user_id = u.user_id
LEFT JOIN sale_items si ON s.sale_id = si.sale_id";

$whereConditions = [];
$params = [];
$types = "";

// Add date filter only if both dates are provided
if (!empty($dateFrom) && !empty($dateTo)) {
    $whereConditions[] = "DATE(s.sale_date) BETWEEN ? AND ?";
    $params[] = $dateFrom;
    $params[] = $dateTo;
    $types .= "ss";
}

// Add payment type filter if provided
if (!empty($paymentType)) {
    $whereConditions[] = "s.payment_type = ?";
    $params[] = $paymentType;
    $types .= "s";
}

// Add WHERE clause if there are conditions
if (!empty($whereConditions)) {
    $sql .= " WHERE " . implode(" AND ", $whereConditions);
}

$sql .= " GROUP BY s.sale_id ORDER BY s.sale_date DESC";

$stmt = $conn->prepare($sql);
if (!$stmt) {
    die("Query preparation failed: " . $conn->error);
}

// Bind parameters only if there are any
if (!empty($params)) {
    $stmt->bind_param($types, ...$params);
}

$stmt->execute();
$sales = $stmt->get_result();

// Get summary
$summarySQL = "SELECT 
    COUNT(*) as total_sales,
    SUM(net_amount) as total_revenue,
    SUM(discount) as total_discount
FROM sales";

$summaryWhere = [];
$summaryParams = [];
$summaryTypes = "";

if (!empty($dateFrom) && !empty($dateTo)) {
    $summaryWhere[] = "DATE(sale_date) BETWEEN ? AND ?";
    $summaryParams[] = $dateFrom;
    $summaryParams[] = $dateTo;
    $summaryTypes .= "ss";
}

if (!empty($paymentType)) {
    $summaryWhere[] = "payment_type = ?";
    $summaryParams[] = $paymentType;
    $summaryTypes .= "s";
}

if (!empty($summaryWhere)) {
    $summarySQL .= " WHERE " . implode(" AND ", $summaryWhere);
}

$summaryStmt = $conn->prepare($summarySQL);

if (!empty($summaryParams)) {
    $summaryStmt->bind_param($summaryTypes, ...$summaryParams);
}

$summaryStmt->execute();
$summary = $summaryStmt->get_result()->fetch_assoc();
?>
<!doctype html>
<html lang="en" dir="ltr" data-bs-theme="light">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <title>Sales History - E. W. D. Erundeniya</title>
    <link rel="shortcut icon" href="assets/images/logoblack.png">
    <link rel="stylesheet" href="assets/css/core/libs.min.css">
    <link rel="stylesheet" href="assets/css/hope-ui.min.css?v=5.0.0">
    <link rel="stylesheet" href="assets/css/custom.min.css?v=5.0.0">
    <link rel="stylesheet" href="assets/css/custom.css">
</head>
<body>
    <div id="loading">
        <div class="loader simple-loader">
            <div class="loader-body"></div>
        </div>
    </div>

    <?php include 'includes/sidebar.php'; ?>

    <main class="main-content">
        <?php include 'includes/header.php'; ?>

        <div class="conatiner-fluid content-inner mt-n5 py-0">
            <!-- Summary Cards -->
            <div class="row mb-4">
                <div class="col-md-4">
                    <div class="card">
                        <div class="card-body">
                            <div class="d-flex align-items-center">
                                <div class="flex-grow-1">
                                    <p class="text-muted mb-1">Total Sales</p>
                                    <h4 class="mb-0"><?php echo number_format(intval($summary['total_sales'] ?? 0)); ?></h4>
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
                <div class="col-md-4">
                    <div class="card">
                        <div class="card-body">
                            <div class="d-flex align-items-center">
                                <div class="flex-grow-1">
                                    <p class="text-muted mb-1">Total Revenue</p>
                                    <h4 class="mb-0 text-success">Rs. <?php echo number_format(floatval($summary['total_revenue'] ?? 0), 2); ?></h4>
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
                <div class="col-md-4">
                    <div class="card">
                        <div class="card-body">
                            <div class="d-flex align-items-center">
                                <div class="flex-grow-1">
                                    <p class="text-muted mb-1">Total Discount</p>
                                    <h4 class="mb-0 text-danger">Rs. <?php echo number_format(floatval($summary['total_discount'] ?? 0), 2); ?></h4>
                                </div>
                                <div class="rounded-circle bg-soft-danger p-3">
                                    <svg width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                        <path d="M9 2L2 9L9 16M15 8L22 15L15 22" stroke="currentColor" stroke-width="2" />
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
                        <div class="card-header d-flex justify-content-between align-items-center flex-wrap gap-2">
                            <h4 class="card-title mb-0">Sales History</h4>
                            <button type="button" class="btn btn-success" onclick="exportSalesReport()">
                                📊 Export
                            </button>
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
                                    <label class="form-label">Payment Type</label>
                                    <select class="form-select" name="payment_type">
                                        <option value="">All</option>
                                        <option value="cash" <?php echo $paymentType === 'cash' ? 'selected' : ''; ?>>Cash</option>
                                        <option value="credit" <?php echo $paymentType === 'credit' ? 'selected' : ''; ?>>Credit</option>
                                    </select>
                                </div>
                                <div class="col-md-3 d-flex align-items-end gap-2">
                                    <button type="submit" class="btn btn-primary">Filter</button>
                                    <a href="sales_history.php" class="btn btn-secondary">Reset</a>
                                </div>
                            </form>

                            <div class="table-responsive">
                                <table class="table table-striped">
                                    <thead>
                                        <tr>
                                            <th>Sale ID</th>
                                            <th>Date</th>
                                            <th>Customer</th>
                                            <th>Items</th>
                                            <th>Total</th>
                                            <th>Discount</th>
                                            <th>Net Amount</th>
                                            <th>Payment</th>
                                            <th>Cashier</th>
                                            <th>Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php 
                                        if ($sales->num_rows > 0):
                                            while ($sale = $sales->fetch_assoc()): 
                                        ?>
                                            <tr>
                                                <td><strong>#<?php echo str_pad($sale['sale_id'], 5, '0', STR_PAD_LEFT); ?></strong></td>
                                                <td><?php echo date('M d, Y h:i A', strtotime($sale['sale_date'])); ?></td>
                                                <td><?php echo htmlspecialchars($sale['customer_name'] ?? 'Walk-in'); ?></td>
                                                <td><?php echo $sale['item_count']; ?></td>
                                                <td>Rs. <?php echo number_format($sale['total_amount'], 2); ?></td>
                                                <td>Rs. <?php echo number_format($sale['discount'], 2); ?></td>
                                                <td><strong>Rs. <?php echo number_format($sale['net_amount'], 2); ?></strong></td>
                                                <td>
                                                    <span class="badge bg-<?php echo $sale['payment_type'] === 'cash' ? 'success' : 'warning'; ?>">
                                                        <?php echo ucfirst($sale['payment_type']); ?>
                                                    </span>
                                                </td>
                                                <td><?php echo htmlspecialchars($sale['user_name']); ?></td>
                                                <td>
                                                    <button class="btn btn-sm btn-icon btn-info" onclick="viewSale(<?php echo $sale['sale_id']; ?>)" title="View Details">
                                                        <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" fill="currentColor" class="bi bi-eye-fill" viewBox="0 0 16 16">
                                                            <path d="M10.5 8a2.5 2.5 0 1 1-5 0 2.5 2.5 0 0 1 5 0" />
                                                            <path d="M0 8s3-5.5 8-5.5S16 8 16 8s-3 5.5-8 5.5S0 8 0 8m8 3.5a3.5 3.5 0 1 0 0-7 3.5 3.5 0 0 0 0 7" />
                                                        </svg>
                                                    </button>
                                                    <button class="btn btn-sm btn-icon btn-success" onclick="printReceipt(<?php echo $sale['sale_id']; ?>)" title="Print Receipt">
                                                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                                            <path d="M19 8H5C3.34 8 2 9.34 2 11V17H6V21H18V17H22V11C22 9.34 20.66 8 19 8ZM16 19H8V14H16V19ZM19 12C18.45 12 18 11.55 18 11C18 10.45 18.45 10 19 10C19.55 10 20 10.45 20 11C20 11.55 19.55 12 19 12ZM18 3H6V7H18V3Z" fill="currentColor" />
                                                        </svg>
                                                    </button>
                                                </td>
                                            </tr>
                                        <?php 
                                            endwhile;
                                        else:
                                        ?>
                                            <tr>
                                                <td colspan="10" class="text-center py-4">
                                                    <div class="alert alert-info mb-0">
                                                        <strong>No sales found</strong><br>
                                                        <?php if (!empty($dateFrom) || !empty($dateTo) || !empty($paymentType)): ?>
                                                            There are no sales records for the selected filters. Try adjusting your search criteria.
                                                        <?php else: ?>
                                                            There are no sales records in the system yet.
                                                        <?php endif; ?>
                                                    </div>
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
        </div>

        <?php include 'includes/footer.php'; ?>
    </main>

    <script src="assets/js/core/libs.min.js"></script>
    <script src="assets/js/core/external.min.js"></script>
    <script src="assets/js/hope-ui.js" defer></script>

    <script>
        function viewSale(saleId) {
            window.open('view_sale.php?sale_id=' + saleId, '_blank', 'width=900,height=700');
        }

        function printReceipt(saleId) {
            window.open('print_receipt.php?sale_id=' + saleId, '_blank');
        }

        function exportSalesReport() {
            const urlParams = new URLSearchParams(window.location.search);
            let exportUrl = 'export_sales.php?';
            
            const dateFrom = urlParams.get('date_from');
            const dateTo = urlParams.get('date_to');
            const paymentType = urlParams.get('payment_type');
            
            if (dateFrom) exportUrl += 'date_from=' + dateFrom + '&';
            if (dateTo) exportUrl += 'date_to=' + dateTo + '&';
            if (paymentType) exportUrl += 'payment_type=' + paymentType;
            
            window.location.href = exportUrl;
        }
    </script>
</body>
</html>