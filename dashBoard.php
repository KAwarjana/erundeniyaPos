<?php
require_once 'auth.php';
Auth::requireAuth();

$conn = getDBConnection();
$userInfo = Auth::getUserInfo();

// Get dashboard statistics
$stats = [];

// Total Products
$result = $conn->query("SELECT COUNT(DISTINCT product_id) as total FROM products");
$stats['total_products'] = intval($result->fetch_assoc()['total'] ?? 0);

// Total Stock Value
$result = $conn->query("SELECT SUM(pb.quantity_in_stock * pb.cost_price) as total_value 
                        FROM product_batches pb");
$stats['stock_value'] = floatval($result->fetch_assoc()['total_value'] ?? 0);

// Today's Sales
$result = $conn->query("SELECT COUNT(*) as total_sales, IFNULL(SUM(net_amount), 0) as total_amount 
                        FROM sales 
                        WHERE DATE(sale_date) = CURDATE()");
$todaySales = $result->fetch_assoc();
$stats['today_sales'] = intval($todaySales['total_sales'] ?? 0);
$stats['today_revenue'] = floatval($todaySales['total_amount'] ?? 0);

// Low Stock Products
$result = $conn->query("SELECT COUNT(*) as low_stock FROM (
                        SELECT p.product_id, p.reorder_level, SUM(pb.quantity_in_stock) as total_stock
                        FROM products p
                        LEFT JOIN product_batches pb ON p.product_id = pb.product_id
                        GROUP BY p.product_id, p.reorder_level
                        HAVING total_stock <= p.reorder_level
                        ) as low_stock_items");
$lowStockResult = $result->fetch_assoc();
$stats['low_stock'] = $lowStockResult['low_stock'] ?? 0;

// Recent Sales
$recentSales = $conn->query("SELECT s.sale_id, s.sale_date, c.name as customer_name, 
                             s.net_amount, s.payment_type 
                             FROM sales s
                             LEFT JOIN customers c ON s.customer_id = c.customer_id
                             ORDER BY s.sale_date DESC
                             LIMIT 5");

// Expiring Soon Products
$expiringSoon = $conn->query("SELECT p.product_name, pb.batch_no, pb.expiry_date, pb.quantity_in_stock
                              FROM product_batches pb
                              JOIN products p ON pb.product_id = p.product_id
                              WHERE pb.expiry_date <= DATE_ADD(CURDATE(), INTERVAL 3 MONTH)
                              AND pb.quantity_in_stock > 0
                              ORDER BY pb.expiry_date ASC
                              LIMIT 5");
?>
<!doctype html>
<html lang="en" dir="ltr" data-bs-theme="light">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <title>Dashboard | Ayurvedic Pharmacy POS</title>
    
    <link rel="shortcut icon" href="assets/images/logo_white.png">
    <link rel="stylesheet" href="assets/css/core/libs.min.css">
    <link rel="stylesheet" href="assets/css/hope-ui.min.css?v=5.0.0">
    <link rel="stylesheet" href="assets/css/custom.min.css?v=5.0.0">
    <link rel="stylesheet" href="assets/css/customizer.min.css?v=5.0.0">
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
            <div class="row">
                <!-- Statistics Cards -->
                <div class="col-md-3 col-sm-6">
                    <div class="card">
                        <div class="card-body">
                            <div class="d-flex align-items-center justify-content-between">
                                <div>
                                    <span class="text-muted d-block mb-1">Total Products</span>
                                    <h4 class="mb-0"><?php echo number_format($stats['total_products']); ?></h4>
                                </div>
                                <div class="rounded-circle bg-soft-primary p-3">
                                    <svg width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                        <path d="M12 2L2 7L12 12L22 7L12 2Z" fill="currentColor"/>
                                        <path d="M2 17L12 22L22 17" stroke="currentColor" stroke-width="2"/>
                                        <path d="M2 12L12 17L22 12" stroke="currentColor" stroke-width="2"/>
                                    </svg>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-md-3 col-sm-6">
                    <div class="card">
                        <div class="card-body">
                            <div class="d-flex align-items-center justify-content-between">
                                <div>
                                    <span class="text-muted d-block mb-1">Stock Value</span>
                                    <h4 class="mb-0">Rs. <?php echo number_format($stats['stock_value'], 2); ?></h4>
                                </div>
                                <div class="rounded-circle bg-soft-success p-3">
                                    <svg width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                        <path d="M12 2V22M17 5H9.5C8.57174 5 7.6815 5.36875 7.02513 6.02513C6.36875 6.6815 6 7.57174 6 8.5C6 9.42826 6.36875 10.3185 7.02513 10.9749C7.6815 11.6313 8.57174 12 9.5 12H14.5C15.4283 12 16.3185 12.3687 16.9749 13.0251C17.6313 13.6815 18 14.5717 18 15.5C18 16.4283 17.6313 17.3185 16.9749 17.9749C16.3185 18.6313 15.4283 19 14.5 19H6" stroke="currentColor" stroke-width="2"/>
                                    </svg>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-md-3 col-sm-6">
                    <div class="card">
                        <div class="card-body">
                            <div class="d-flex align-items-center justify-content-between">
                                <div>
                                    <span class="text-muted d-block mb-1">Today's Revenue</span>
                                    <h4 class="mb-0">Rs. <?php echo number_format($stats['today_revenue'], 2); ?></h4>
                                    <small class="text-muted"><?php echo $stats['today_sales']; ?> sales</small>
                                </div>
                                <div class="rounded-circle bg-soft-info p-3">
                                    <svg width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                        <path d="M13 2L3 14H12L11 22L21 10H12L13 2Z" fill="currentColor"/>
                                    </svg>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-md-3 col-sm-6">
                    <div class="card">
                        <div class="card-body">
                            <div class="d-flex align-items-center justify-content-between">
                                <div>
                                    <span class="text-muted d-block mb-1">Low Stock Items</span>
                                    <h4 class="mb-0 text-danger"><?php echo number_format($stats['low_stock']); ?></h4>
                                    <small class="text-muted">Need reorder</small>
                                </div>
                                <div class="rounded-circle bg-soft-danger p-3">
                                    <svg width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                        <path d="M12 9V13M12 17H12.01M21 12C21 16.9706 16.9706 21 12 21C7.02944 21 3 16.9706 3 12C3 7.02944 7.02944 3 12 3C16.9706 3 21 7.02944 21 12Z" stroke="currentColor" stroke-width="2"/>
                                    </svg>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="row">
                <!-- Recent Sales -->
                <div class="col-lg-8">
                    <div class="card">
                        <div class="card-header d-flex justify-content-between">
                            <div class="header-title">
                                <h4 class="card-title">Recent Sales</h4>
                            </div>
                            <a href="sales_history.php" class="btn btn-sm btn-primary">View All</a>
                        </div>
                        <div class="card-body">
                            <div class="table-responsive">
                                <table class="table table-striped">
                                    <thead>
                                        <tr>
                                            <th>Sale ID</th>
                                            <th>Customer</th>
                                            <th>Date</th>
                                            <th>Amount</th>
                                            <th>Payment</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php if ($recentSales->num_rows > 0): ?>
                                            <?php while ($sale = $recentSales->fetch_assoc()): ?>
                                            <tr>
                                                <td>#<?php echo str_pad($sale['sale_id'], 5, '0', STR_PAD_LEFT); ?></td>
                                                <td><?php echo htmlspecialchars($sale['customer_name'] ?? 'Walk-in'); ?></td>
                                                <td><?php echo date('M d, Y h:i A', strtotime($sale['sale_date'])); ?></td>
                                                <td>Rs. <?php echo number_format($sale['net_amount'], 2); ?></td>
                                                <td>
                                                    <span class="badge bg-<?php echo $sale['payment_type'] === 'cash' ? 'success' : 'warning'; ?>">
                                                        <?php echo ucfirst($sale['payment_type']); ?>
                                                    </span>
                                                </td>
                                            </tr>
                                            <?php endwhile; ?>
                                        <?php else: ?>
                                            <tr>
                                                <td colspan="5" class="text-center">No sales recorded yet</td>
                                            </tr>
                                        <?php endif; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Expiring Soon -->
                <div class="col-lg-4">
                    <div class="card">
                        <div class="card-header d-flex justify-content-between">
                            <div class="header-title">
                                <h4 class="card-title">Expiring Soon</h4>
                            </div>
                        </div>
                        <div class="card-body">
                            <?php if ($expiringSoon->num_rows > 0): ?>
                                <?php while ($item = $expiringSoon->fetch_assoc()): ?>
                                    <?php 
                                    $expiryDate = new DateTime($item['expiry_date']);
                                    $today = new DateTime();
                                    $daysLeft = $today->diff($expiryDate)->days;
                                    $badgeClass = $daysLeft < 30 ? 'danger' : 'warning';
                                    ?>
                                    <div class="d-flex mb-3 align-items-center">
                                        <div class="flex-grow-1">
                                            <h6 class="mb-0"><?php echo htmlspecialchars($item['product_name']); ?></h6>
                                            <small class="text-muted">Batch: <?php echo htmlspecialchars($item['batch_no']); ?></small>
                                        </div>
                                        <span class="badge bg-<?php echo $badgeClass; ?>">
                                            <?php echo $daysLeft; ?> days
                                        </span>
                                    </div>
                                <?php endwhile; ?>
                            <?php else: ?>
                                <p class="text-center text-muted">No items expiring soon</p>
                            <?php endif; ?>
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