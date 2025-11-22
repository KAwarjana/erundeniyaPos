<?php
require_once 'auth.php';
Auth::requireAuth();

$conn = getDBConnection();

// Get filter parameters
$dateFrom = $_GET['date_from'] ?? date('Y-m-01');
$dateTo = $_GET['date_to'] ?? date('Y-m-d');
$paymentType = $_GET['payment_type'] ?? '';

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
LEFT JOIN sale_items si ON s.sale_id = si.sale_id
WHERE DATE(s.sale_date) BETWEEN ? AND ?";

$params = [$dateFrom, $dateTo];
$types = "ss";

if (!empty($paymentType)) {
    $sql .= " AND s.payment_type = ?";
    $params[] = $paymentType;
    $types .= "s";
}

$sql .= " GROUP BY s.sale_id ORDER BY s.sale_date DESC";

$stmt = $conn->prepare($sql);
$stmt->bind_param($types, ...$params);
$stmt->execute();
$sales = $stmt->get_result();

// Get summary
$summaryStmt = $conn->prepare("SELECT 
    COUNT(*) as total_sales,
    SUM(net_amount) as total_revenue,
    SUM(discount) as total_discount
FROM sales 
WHERE DATE(sale_date) BETWEEN ? AND ?" . (!empty($paymentType) ? " AND payment_type = ?" : ""));

if (!empty($paymentType)) {
    $summaryStmt->bind_param("sss", $dateFrom, $dateTo, $paymentType);
} else {
    $summaryStmt->bind_param("ss", $dateFrom, $dateTo);
}

$summaryStmt->execute();
$summary = $summaryStmt->get_result()->fetch_assoc();
?>
<!doctype html>
<html lang="en" dir="ltr" data-bs-theme="light">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <title>Sales History | Ayurvedic Pharmacy</title>
    
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
                                        <path d="M3 9L12 2L21 9V20C21 21.1046 20.1046 22 19 22H5C3.89543 22 3 21.1046 3 20V9Z" stroke="currentColor" stroke-width="2"/>
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
                                        <path d="M12 2V22M17 5H9.5C7.01472 5 5 7.01472 5 9.5C5 11.9853 7.01472 14 9.5 14H14.5C16.9853 14 19 16.0147 19 18.5C19 20.9853 16.9853 23 14.5 23H6" stroke="currentColor" stroke-width="2"/>
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
                                        <path d="M9 2L2 9L9 16M15 8L22 15L15 22" stroke="currentColor" stroke-width="2"/>
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
                        <div class="card-header">
                            <h4 class="card-title">Sales History</h4>
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
                                <div class="col-md-3 d-flex align-items-end">
                                    <button type="submit" class="btn btn-primary me-2">Filter</button>
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
                                        <?php while ($sale = $sales->fetch_assoc()): ?>
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
                                                    <button class="btn btn-sm btn-icon btn-info" onclick="viewSale(<?php echo $sale['sale_id']; ?>)" title="View">
                                                        <svg width="20" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                                            <path d="M15.5833 12C15.5833 14.0711 13.9044 15.75 11.8333 15.75C9.76221 15.75 8.08333 14.0711 8.08333 12C8.08333 9.92893 9.76221 8.25 11.8333 8.25C13.9044 8.25 15.5833 9.92893 15.5833 12Z" stroke="currentColor" stroke-width="1.5"/>
                                                            <path d="M11.8333 19.25C15.5083 19.25 18.8454 16.8714 20.9167 12C18.8454 7.12863 15.5083 4.75 11.8333 4.75C8.15835 4.75 4.82123 7.12863 2.75 12C4.82123 16.8714 8.15835 19.25 11.8333 19.25Z" stroke="currentColor" stroke-width="1.5"/>
                                                        </svg>
                                                    </button>
                                                    <button class="btn btn-sm btn-icon btn-success" onclick="printReceipt(<?php echo $sale['sale_id']; ?>)" title="Print">
                                                        <svg width="20" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                                            <path d="M7 17H17M7 10H17M9 3H15L17 5V21H7V5L9 3Z" stroke="currentColor" stroke-width="2"/>
                                                        </svg>
                                                    </button>
                                                </td>
                                            </tr>
                                        <?php endwhile; ?>
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

    <!-- View Sale Modal -->
    <div class="modal fade" id="viewSaleModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Sale Details</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body" id="saleDetailsContent">
                    <div class="text-center py-5">
                        <div class="spinner-border" role="status"></div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="assets/js/core/libs.min.js"></script>
    <script src="assets/js/core/external.min.js"></script>
    <script src="assets/js/hope-ui.js" defer></script>

    <script>
        const viewSaleModal = new bootstrap.Modal(document.getElementById('viewSaleModal'));

        function viewSale(saleId) {
            viewSaleModal.show();
            fetch('api/get_sale_details.php?id=' + saleId)
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        displaySaleDetails(data.sale, data.items);
                    }
                });
        }

        function displaySaleDetails(sale, items) {
            let html = `
                <div class="row mb-3">
                    <div class="col-md-6">
                        <strong>Sale ID:</strong> #${String(sale.sale_id).padStart(5, '0')}<br>
                        <strong>Date:</strong> ${new Date(sale.sale_date).toLocaleString()}<br>
                        <strong>Customer:</strong> ${sale.customer_name || 'Walk-in'}<br>
                    </div>
                    <div class="col-md-6 text-end">
                        <strong>Payment Type:</strong> ${sale.payment_type.toUpperCase()}<br>
                        <strong>Cashier:</strong> ${sale.user_name}<br>
                    </div>
                </div>
                <table class="table table-bordered">
                    <thead>
                        <tr>
                            <th>Product</th>
                            <th>Batch</th>
                            <th>Quantity</th>
                            <th>Price</th>
                            <th>Total</th>
                        </tr>
                    </thead>
                    <tbody>
            `;
            
            items.forEach(item => {
                html += `
                    <tr>
                        <td>${item.product_name}</td>
                        <td>${item.batch_no}</td>
                        <td>${item.quantity}</td>
                        <td>Rs. ${parseFloat(item.unit_price).toFixed(2)}</td>
                        <td>Rs. ${parseFloat(item.total_price).toFixed(2)}</td>
                    </tr>
                `;
            });

            html += `
                    </tbody>
                    <tfoot>
                        <tr>
                            <th colspan="4" class="text-end">Subtotal:</th>
                            <th>Rs. ${parseFloat(sale.total_amount).toFixed(2)}</th>
                        </tr>
                        <tr>
                            <th colspan="4" class="text-end">Discount:</th>
                            <th>Rs. ${parseFloat(sale.discount).toFixed(2)}</th>
                        </tr>
                        <tr>
                            <th colspan="4" class="text-end">Net Amount:</th>
                            <th class="text-success">Rs. ${parseFloat(sale.net_amount).toFixed(2)}</th>
                        </tr>
                    </tfoot>
                </table>
            `;

            document.getElementById('saleDetailsContent').innerHTML = html;
        }

        function printReceipt(saleId) {
            window.open('print_receipt.php?sale_id=' + saleId, '_blank');
        }
    </script>
</body>
</html>