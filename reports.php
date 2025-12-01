<?php
require_once 'auth.php';
Auth::requireAuth();

$conn = getDBConnection();

// Get date range - empty by default to show all data
$dateFrom = $_GET['date_from'] ?? '';
$dateTo = $_GET['date_to'] ?? '';

// Sales Report with dynamic date filter
$salesSQL = "SELECT 
    DATE(sale_date) as date,
    COUNT(*) as total_sales,
    SUM(total_amount) as gross_sales,
    SUM(discount) as total_discount,
    SUM(net_amount) as net_sales
FROM sales";

if (!empty($dateFrom) && !empty($dateTo)) {
    $salesSQL .= " WHERE DATE(sale_date) BETWEEN ? AND ?";
}

$salesSQL .= " GROUP BY DATE(sale_date) ORDER BY date DESC";

if (!empty($dateFrom) && !empty($dateTo)) {
    $salesReport = $conn->prepare($salesSQL);
    $salesReport->bind_param("ss", $dateFrom, $dateTo);
    $salesReport->execute();
    $salesData = $salesReport->get_result();
} else {
    $salesData = $conn->query($salesSQL);
}

// Top Selling Products with dynamic date filter
$topProductsSQL = "SELECT 
    p.product_name,
    SUM(si.quantity) as total_quantity,
    SUM(si.total_price) as total_revenue
FROM sale_items si
JOIN product_batches pb ON si.batch_id = pb.batch_id
JOIN products p ON pb.product_id = p.product_id
JOIN sales s ON si.sale_id = s.sale_id";

if (!empty($dateFrom) && !empty($dateTo)) {
    $topProductsSQL .= " WHERE DATE(s.sale_date) BETWEEN ? AND ?";
}

$topProductsSQL .= " GROUP BY p.product_id ORDER BY total_quantity DESC LIMIT 10";

if (!empty($dateFrom) && !empty($dateTo)) {
    $topProducts = $conn->prepare($topProductsSQL);
    $topProducts->bind_param("ss", $dateFrom, $dateTo);
    $topProducts->execute();
    $topProductsData = $topProducts->get_result();
} else {
    $topProductsData = $conn->query($topProductsSQL);
}

// Low Stock Products (not date dependent)
$lowStock = $conn->query("SELECT 
    p.product_name,
    p.reorder_level,
    SUM(pb.quantity_in_stock) as current_stock
FROM products p
JOIN product_batches pb ON p.product_id = pb.product_id
GROUP BY p.product_id
HAVING current_stock <= p.reorder_level
ORDER BY current_stock ASC");

// Expiring Products (not date dependent)
$expiringProducts = $conn->query("SELECT 
    p.product_name,
    pb.batch_no,
    pb.expiry_date,
    pb.quantity_in_stock,
    DATEDIFF(pb.expiry_date, CURDATE()) as days_left
FROM product_batches pb
JOIN products p ON pb.product_id = p.product_id
WHERE pb.expiry_date <= DATE_ADD(CURDATE(), INTERVAL 3 MONTH)
AND pb.quantity_in_stock > 0
ORDER BY pb.expiry_date ASC");
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

    <style>
        @media print {

            /* Hide elements that shouldn't be printed */
            #loading,
            .sidebar,
            .navbar,
            .no-print,
            .btn,
            button,
            .card-body form {
                display: none !important;
            }

            /* Adjust main content for print */
            .main-content {
                margin-left: 0 !important;
                padding: 0 !important;
            }

            .content-inner {
                margin-top: 0 !important;
            }

            /* Card adjustments */
            .card {
                border: 1px solid #ddd !important;
                box-shadow: none !important;
                page-break-inside: avoid;
                margin-bottom: 20px;
            }

            .card-header {
                background-color: #f8f9fa !important;
                border-bottom: 2px solid #000 !important;
                padding: 10px 15px !important;
            }

            /* Table styling for print */
            table {
                width: 100% !important;
                font-size: 12px !important;
            }

            table thead {
                background-color: #f0f0f0 !important;
            }

            table th,
            table td {
                padding: 8px !important;
                border: 1px solid #ddd !important;
            }

            /* Print header */
            @page {
                margin: 15mm;
            }

            body {
                print-color-adjust: exact;
                -webkit-print-color-adjust: exact;
            }

            .badge {
                border: 1px solid #000 !important;
                padding: 3px 6px !important;
            }
        }

        /* Print header styles */
        .print-header {
            display: none;
        }

        @media print {
            .print-header {
                display: block !important;
                text-align: center;
                margin-bottom: 30px;
                border-bottom: 3px solid #000;
                padding-bottom: 15px;
            }

            .print-header h1 {
                margin: 0;
                font-size: 24px;
                color: #000;
            }

            .print-header p {
                margin: 5px 0;
                font-size: 14px;
            }
        }
    </style>
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
            <!-- Print Header (only visible when printing) -->
            <div class="print-header">
                <h1>Ayurvedic Pharmacy - Sales Report</h1>
                <?php if (!empty($dateFrom) && !empty($dateTo)): ?>
                    <p>Report Period: <?php echo date('M d, Y', strtotime($dateFrom)); ?> to <?php echo date('M d, Y', strtotime($dateTo)); ?></p>
                <?php else: ?>
                    <p>Report Period: All Time</p>
                <?php endif; ?>
                <p>Generated on: <?php echo date('F d, Y h:i A'); ?></p>
            </div>

            <!-- Date Range Filter -->
            <div class="row mb-4 no-print">
                <div class="col-12">
                    <div class="card">
                        <div class="card-body">
                            <form method="GET" class="row g-2 align-items-end">
                                <!-- DATE FIELDS -->
                                <div class="col-sm-6 col-md-auto flex-fill">
                                    <label class="form-label mb-1">Date From</label>
                                    <input type="date" class="form-control" name="date_from"
                                        value="<?php echo $dateFrom; ?>">
                                </div>
                                <div class="col-sm-6 col-md-auto flex-fill">
                                    <label class="form-label mb-1">Date To</label>
                                    <input type="date" class="form-control" name="date_to"
                                        value="<?php echo $dateTo; ?>">
                                </div>

                                <!-- BUTTONS -->
                                <div class="col-md-auto d-flex flex-wrap gap-2 mt-md-3 mt-3">
                                    <button type="submit" class="btn btn-primary flex-fill">
                                        Generate Report
                                    </button>
                                    <a href="reports.php" class="btn btn-secondary flex-fill">
                                        Reset
                                    </a>
                                    <button type="button" class="btn btn-success flex-fill"
                                        onclick="window.print()">
                                        <i class="icon">
                                            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                                <path d="M19 8H5C3.34 8 2 9.34 2 11V17H6V21H18V17H22V11C22 9.34 20.66 8 19 8ZM16 19H8V14H16V19ZM19 12C18.45 12 18 11.55 18 11C18 10.45 18.45 10 19 10C19.55 10 20 10.45 20 11C20 11.55 19.55 12 19 12ZM18 3H6V7H18V3Z" fill="currentColor" />
                                            </svg>
                                        </i>
                                        Print Report
                                    </button>
                                    <button type="button" class="btn btn-info flex-fill"
                                        onclick="exportStockReport()">
                                        📊 Export Stock
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Daily Sales Report -->
            <div class="row">
                <div class="col-sm-12">
                    <div class="card">
                        <div class="card-header">
                            <h4 class="card-title">Daily Sales Report</h4>
                        </div>
                        <div class="card-body">
                            <div class="table-responsive">
                                <table class="table table-striped">
                                    <thead>
                                        <tr>
                                            <th>Date</th>
                                            <th>Total Sales</th>
                                            <th>Gross Sales</th>
                                            <th>Discount</th>
                                            <th>Net Sales</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php
                                        $totalSales = 0;
                                        $totalGross = 0;
                                        $totalDiscount = 0;
                                        $totalNet = 0;
                                        
                                        if ($salesData->num_rows > 0):
                                            while ($row = $salesData->fetch_assoc()):
                                                $totalSales += $row['total_sales'];
                                                $totalGross += $row['gross_sales'];
                                                $totalDiscount += $row['total_discount'];
                                                $totalNet += $row['net_sales'];
                                        ?>
                                            <tr>
                                                <td><?php echo date('M d, Y', strtotime($row['date'])); ?></td>
                                                <td><?php echo $row['total_sales']; ?></td>
                                                <td>Rs. <?php echo number_format($row['gross_sales'], 2); ?></td>
                                                <td>Rs. <?php echo number_format($row['total_discount'], 2); ?></td>
                                                <td><strong>Rs. <?php echo number_format($row['net_sales'], 2); ?></strong></td>
                                            </tr>
                                        <?php 
                                            endwhile;
                                        else:
                                        ?>
                                            <tr>
                                                <td colspan="5" class="text-center py-4">
                                                    <div class="alert alert-info mb-0">
                                                        <strong>No sales data found</strong><br>
                                                        <?php if (!empty($dateFrom) || !empty($dateTo)): ?>
                                                            There are no sales records for the selected date range.
                                                        <?php else: ?>
                                                            There are no sales records in the system yet.
                                                        <?php endif; ?>
                                                    </div>
                                                </td>
                                            </tr>
                                        <?php endif; ?>
                                    </tbody>
                                    <?php if ($salesData->num_rows > 0): ?>
                                    <tfoot>
                                        <tr class="table-primary">
                                            <th>TOTAL</th>
                                            <th><?php echo $totalSales; ?></th>
                                            <th>Rs. <?php echo number_format($totalGross, 2); ?></th>
                                            <th>Rs. <?php echo number_format($totalDiscount, 2); ?></th>
                                            <th>Rs. <?php echo number_format($totalNet, 2); ?></th>
                                        </tr>
                                    </tfoot>
                                    <?php endif; ?>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="row">
                <!-- Top Selling Products -->
                <div class="col-lg-6">
                    <div class="card">
                        <div class="card-header">
                            <h4 class="card-title">Top Selling Products</h4>
                        </div>
                        <div class="card-body">
                            <div class="table-responsive">
                                <table class="table table-sm">
                                    <thead>
                                        <tr>
                                            <th>Product</th>
                                            <th>Quantity Sold</th>
                                            <th>Revenue</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php 
                                        if ($topProductsData->num_rows > 0):
                                            while ($product = $topProductsData->fetch_assoc()): 
                                        ?>
                                            <tr>
                                                <td><?php echo htmlspecialchars($product['product_name']); ?></td>
                                                <td><strong><?php echo $product['total_quantity']; ?></strong></td>
                                                <td>Rs. <?php echo number_format($product['total_revenue'], 2); ?></td>
                                            </tr>
                                        <?php 
                                            endwhile;
                                        else:
                                        ?>
                                            <tr>
                                                <td colspan="3" class="text-center py-3 text-muted">No products sold yet</td>
                                            </tr>
                                        <?php endif; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Low Stock Alert -->
                <div class="col-lg-6">
                    <div class="card">
                        <div class="card-header">
                            <h4 class="card-title">Low Stock Alert</h4>
                        </div>
                        <div class="card-body">
                            <div class="table-responsive">
                                <table class="table table-sm">
                                    <thead>
                                        <tr>
                                            <th>Product</th>
                                            <th>Current Stock</th>
                                            <th>Reorder Level</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php 
                                        if ($lowStock->num_rows > 0):
                                            while ($item = $lowStock->fetch_assoc()): 
                                        ?>
                                            <tr>
                                                <td><?php echo htmlspecialchars($item['product_name']); ?></td>
                                                <td><span class="badge bg-danger"><?php echo $item['current_stock']; ?></span></td>
                                                <td><?php echo $item['reorder_level']; ?></td>
                                            </tr>
                                        <?php 
                                            endwhile;
                                        else:
                                        ?>
                                            <tr>
                                                <td colspan="3" class="text-center py-3 text-muted">All products have sufficient stock</td>
                                            </tr>
                                        <?php endif; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Expiring Products -->
            <div class="row">
                <div class="col-sm-12">
                    <div class="card">
                        <div class="card-header">
                            <h4 class="card-title">Products Expiring in Next 3 Months</h4>
                        </div>
                        <div class="card-body">
                            <div class="table-responsive">
                                <table class="table table-striped">
                                    <thead>
                                        <tr>
                                            <th>Product</th>
                                            <th>Batch No</th>
                                            <th>Expiry Date</th>
                                            <th>Days Left</th>
                                            <th>Stock</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php 
                                        if ($expiringProducts->num_rows > 0):
                                            while ($exp = $expiringProducts->fetch_assoc()): 
                                                $badgeClass = 'warning';
                                                if ($exp['days_left'] < 0) $badgeClass = 'danger';
                                                elseif ($exp['days_left'] <= 30) $badgeClass = 'warning';
                                        ?>
                                            <tr>
                                                <td><?php echo htmlspecialchars($exp['product_name']); ?></td>
                                                <td><?php echo htmlspecialchars($exp['batch_no']); ?></td>
                                                <td><?php echo date('M d, Y', strtotime($exp['expiry_date'])); ?></td>
                                                <td><span class="badge bg-<?php echo $badgeClass; ?>"><?php echo abs($exp['days_left']); ?> days <?php echo $exp['days_left'] < 0 ? 'ago' : 'left'; ?></span></td>
                                                <td><?php echo $exp['quantity_in_stock']; ?></td>
                                            </tr>
                                        <?php 
                                            endwhile;
                                        else:
                                        ?>
                                            <tr>
                                                <td colspan="5" class="text-center py-3 text-muted">No products expiring in the next 3 months</td>
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
        function exportStockReport() {
            window.location.href = 'export_stock.php';
        }
    </script>
</body>

</html>