<?php
require_once 'auth.php';
Auth::requireAuth();

$conn = getDBConnection();

// Get filter parameters
$dateFrom = $_GET['date_from'] ?? '';
$dateTo = $_GET['date_to'] ?? '';
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
LEFT JOIN purchase_items pi ON p.purchase_id = pi.purchase_id";

$whereConditions = [];
$params = [];
$types = "";

// Add date filter only if both dates are provided
if (!empty($dateFrom) && !empty($dateTo)) {
    $whereConditions[] = "DATE(p.purchase_date) BETWEEN ? AND ?";
    $params[] = $dateFrom;
    $params[] = $dateTo;
    $types .= "ss";
}

if (!empty($supplierId)) {
    $whereConditions[] = "p.supplier_id = ?";
    $params[] = $supplierId;
    $types .= "i";
}

// Add WHERE clause if there are conditions
if (!empty($whereConditions)) {
    $sql .= " WHERE " . implode(" AND ", $whereConditions);
}

$sql .= " GROUP BY p.purchase_id ORDER BY p.purchase_date DESC";

$stmt = $conn->prepare($sql);

// Bind parameters only if there are any
if (!empty($params)) {
    $stmt->bind_param($types, ...$params);
}

$stmt->execute();
$purchases = $stmt->get_result();

// Get summary
$summarySQL = "SELECT 
    COUNT(*) as total_purchases,
    SUM(total_amount) as total_amount
FROM purchases";

$summaryWhere = [];
$summaryParams = [];
$summaryTypes = "";

if (!empty($dateFrom) && !empty($dateTo)) {
    $summaryWhere[] = "DATE(purchase_date) BETWEEN ? AND ?";
    $summaryParams[] = $dateFrom;
    $summaryParams[] = $dateTo;
    $summaryTypes .= "ss";
}

if (!empty($supplierId)) {
    $summaryWhere[] = "supplier_id = ?";
    $summaryParams[] = $supplierId;
    $summaryTypes .= "i";
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
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/sweetalert2/11.10.5/sweetalert2.min.css">
    <link rel="stylesheet" href="assets/css/custom.css">
    
    <style>
        .supplier-search-wrapper {
            position: relative;
        }
        
        .supplier-dropdown {
            position: absolute;
            top: 100%;
            left: 0;
            right: 0;
            max-height: 250px;
            overflow-y: auto;
            background: white;
            border: 1px solid #dee2e6;
            border-top: none;
            border-radius: 0 0 0.375rem 0.375rem;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            z-index: 1000;
            display: none;
        }
        
        .supplier-dropdown.show {
            display: block;
        }
        
        .supplier-dropdown-item {
            padding: 10px 15px;
            cursor: pointer;
            border-bottom: 1px solid #f0f0f0;
            transition: background-color 0.2s;
        }
        
        .supplier-dropdown-item:hover {
            background-color: #f8f9fa;
        }
        
        .supplier-dropdown-item:last-child {
            border-bottom: none;
        }
        
        .supplier-dropdown-item.selected {
            background-color: #e7f3ff;
            font-weight: 500;
        }
        
        .no-results {
            padding: 15px;
            text-align: center;
            color: #6c757d;
            font-style: italic;
        }
        
        #supplierSearchInput {
            border-radius: 0.375rem;
        }
        
        #supplierSearchInput:focus {
            border-color: #0d6efd;
            box-shadow: 0 0 0 0.25rem rgba(13, 110, 253, 0.25);
        }
    </style>
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
                        <div class="card-header">
                            <div class="row align-items-center">
                                <div class="col-12 col-md-auto mb-2 mb-md-0">
                                    <h4 class="card-title mb-0">Purchase History</h4>
                                    <p class="text-muted mb-0 small"><i>Note: New purchases are created through Stock Management by adding new batches.</i></p>
                                </div>
                                <div class="col"></div>
                                <div class="col-12 col-md-auto">
                                    <button class="btn btn-success w-100 w-md-auto text-nowrap" onclick="exportPurchases()">
                                        📊 Export to CSV
                                    </button>
                                </div>
                            </div>
                        </div>
                        <div class="card-body">
                            <!-- Filters -->
                            <form method="GET" id="filterForm" class="row g-3 mb-4">
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
                                    <div class="supplier-search-wrapper">
                                        <input 
                                            type="text" 
                                            class="form-control" 
                                            id="supplierSearchInput" 
                                            placeholder="Type to search supplier..."
                                            autocomplete="off"
                                        >
                                        <input type="hidden" name="supplier_id" id="supplierIdInput" value="<?php echo $supplierId; ?>">
                                        <div class="supplier-dropdown" id="supplierDropdown"></div>
                                    </div>
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
                                                    <div class="alert alert-info mb-0">
                                                        <strong>No purchases found</strong><br>
                                                        <?php if (!empty($dateFrom) || !empty($dateTo) || !empty($supplierId)): ?>
                                                            There are no purchase records for the selected filters. Try adjusting your search criteria.
                                                        <?php else: ?>
                                                            There are no purchase records in the system yet. Add new product batches in Stock Management to create purchases.
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
    <script src="https://cdnjs.cloudflare.com/ajax/libs/sweetalert2/11.10.5/sweetalert2.all.min.js"></script>
    
    <script>
        function exportPurchases() {
            Swal.fire({
                title: 'Exporting...',
                text: 'Preparing your purchases report',
                allowOutsideClick: false,
                allowEscapeKey: false,
                showConfirmButton: false,
                didOpen: () => {
                    Swal.showLoading();
                }
            });

            const urlParams = new URLSearchParams(window.location.search);
            window.location.href = 'export_purchases.php?' + urlParams.toString();

            setTimeout(() => {
                Swal.close();
                Swal.fire({
                    icon: 'success',
                    title: 'Success!',
                    text: 'Purchases report exported successfully',
                    timer: 2000,
                    showConfirmButton: false
                });
            }, 1000);
        }
    
        // Supplier data from PHP
        const suppliers = [
            { id: '', name: 'All Suppliers' },
            <?php 
            $suppliers->data_seek(0);
            while ($supplier = $suppliers->fetch_assoc()): 
            ?>
            ,{ 
                id: <?php echo $supplier['supplier_id']; ?>, 
                name: '<?php echo addslashes($supplier['name']); ?>' 
            },
            <?php endwhile; ?>
        ];

        const searchInput = document.getElementById('supplierSearchInput');
        const dropdown = document.getElementById('supplierDropdown');
        const hiddenInput = document.getElementById('supplierIdInput');

        // Set initial value if supplier is selected
        const selectedSupplierId = '<?php echo $supplierId; ?>';
        if (selectedSupplierId) {
            const selectedSupplier = suppliers.find(s => s.id == selectedSupplierId);
            if (selectedSupplier) {
                searchInput.value = selectedSupplier.name;
            }
        } else {
            searchInput.value = 'All Suppliers';
        }

        // Filter and display suppliers
        function filterSuppliers(searchTerm) {
            const filtered = suppliers.filter(supplier => 
                supplier.name.toLowerCase().includes(searchTerm.toLowerCase())
            );

            if (filtered.length === 0) {
                dropdown.innerHTML = '<div class="no-results">No suppliers found</div>';
            } else {
                dropdown.innerHTML = filtered.map(supplier => {
                    const isSelected = supplier.id == hiddenInput.value;
                    return `
                        <div class="supplier-dropdown-item ${isSelected ? 'selected' : ''}" 
                             data-id="${supplier.id}" 
                             data-name="${supplier.name}">
                            ${supplier.name}
                        </div>
                    `;
                }).join('');
            }

            dropdown.classList.add('show');
        }

        // Show all suppliers on focus
        searchInput.addEventListener('focus', function() {
            this.select();
            filterSuppliers('');
        });

        // Filter as user types
        searchInput.addEventListener('input', function() {
            const searchTerm = this.value.trim();
            filterSuppliers(searchTerm);
        });

        // Handle supplier selection
        dropdown.addEventListener('click', function(e) {
            const item = e.target.closest('.supplier-dropdown-item');
            if (item) {
                const supplierId = item.dataset.id;
                const supplierName = item.dataset.name;
                
                searchInput.value = supplierName;
                hiddenInput.value = supplierId;
                dropdown.classList.remove('show');
            }
        });

        // Close dropdown when clicking outside
        document.addEventListener('click', function(e) {
            if (!searchInput.contains(e.target) && !dropdown.contains(e.target)) {
                dropdown.classList.remove('show');
            }
        });

        // Handle keyboard navigation
        searchInput.addEventListener('keydown', function(e) {
            if (e.key === 'Escape') {
                dropdown.classList.remove('show');
            }
        });
    </script>
</body>
</html>