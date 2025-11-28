<?php
require_once 'auth.php';
Auth::requireAuth();

$conn = getDBConnection();

// Get filter parameters
$stockStatus = $_GET['stock_status'] ?? '';
$productStatus = $_GET['product_status'] ?? 'active';
$searchTerm = $_GET['search'] ?? '';

// Build the query with filters
$sql = "SELECT 
    p.product_id,
    p.product_name,
    p.generic_name,
    p.unit,
    p.reorder_level,
    p.status,
    COALESCE(SUM(pb.quantity_in_stock), 0) as total_stock,
    COUNT(pb.batch_id) as batch_count
FROM products p
LEFT JOIN product_batches pb ON p.product_id = pb.product_id";

$whereClauses = [];
$params = [];
$types = "";

if ($productStatus !== 'all') {
    $whereClauses[] = "p.status = ?";
    $params[] = $productStatus;
    $types .= "s";
}

if (!empty($searchTerm)) {
    $whereClauses[] = "(p.product_name LIKE ? OR p.generic_name LIKE ?)";
    $searchParam = "%$searchTerm%";
    $params[] = $searchParam;
    $params[] = $searchParam;
    $types .= "ss";
}

if (!empty($whereClauses)) {
    $sql .= " WHERE " . implode(" AND ", $whereClauses);
}

$sql .= " GROUP BY p.product_id";

if ($stockStatus === 'out_of_stock') {
    $sql .= " HAVING total_stock = 0";
} elseif ($stockStatus === 'low_stock') {
    $sql .= " HAVING total_stock > 0 AND total_stock <= p.reorder_level";
} elseif ($stockStatus === 'in_stock') {
    $sql .= " HAVING total_stock > p.reorder_level";
}

$sql .= " ORDER BY p.product_name";

$stmt = $conn->prepare($sql);
if (!empty($params)) {
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$products = $stmt->get_result();
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
                <div class="col-sm-12">
                    <div class="card">
                        <div class="card-header">
                            <div class="row align-items-center">
                                <div class="col-12 col-sm-auto mb-2 mb-sm-0">
                                    <h4 class="card-title mb-0">Products Management</h4>
                                </div>

                                <div class="col"></div>

                                <div class="col-sm-auto mt-sm-2">
                                    <div class="d-flex flex-column flex-sm-row gap-2">
                                        <button class="btn btn-success w-sm-auto text-nowrap" onclick="exportProducts()">
                                            📊 Export Products
                                        </button>
                                        <button class="btn btn-primary w-sm-auto text-nowrap" onclick="showAddProductModal()">
                                            <i class="icon">
                                                <svg width="20" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                                    <path d="M12 5V19M5 12H19" stroke="currentColor" stroke-width="2" stroke-linecap="round" />
                                                </svg>
                                            </i>
                                            Add New Product
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="card-body">
                            <!-- Filters -->
                            <form method="GET" class="row g-3 mb-4">
                                <div class="col-md-3">
                                    <label class="form-label">Search Product</label>
                                    <input type="text" class="form-control" name="search"
                                        placeholder="Product name or generic name"
                                        value="<?php echo htmlspecialchars($searchTerm); ?>">
                                </div>
                                <div class="col-md-3">
                                    <label class="form-label">Product Status</label>
                                    <select class="form-select" name="product_status">
                                        <option value="active" <?php echo $productStatus === 'active' ? 'selected' : ''; ?>>Active Only</option>
                                        <option value="inactive" <?php echo $productStatus === 'inactive' ? 'selected' : ''; ?>>Inactive Only</option>
                                        <option value="all" <?php echo $productStatus === 'all' ? 'selected' : ''; ?>>All Products</option>
                                    </select>
                                </div>
                                <div class="col-md-3">
                                    <label class="form-label">Stock Status</label>
                                    <select class="form-select" name="stock_status">
                                        <option value="">All Status</option>
                                        <option value="in_stock" <?php echo $stockStatus === 'in_stock' ? 'selected' : ''; ?>>In Stock</option>
                                        <option value="low_stock" <?php echo $stockStatus === 'low_stock' ? 'selected' : ''; ?>>Low Stock</option>
                                        <option value="out_of_stock" <?php echo $stockStatus === 'out_of_stock' ? 'selected' : ''; ?>>Out of Stock</option>
                                    </select>
                                </div>
                                <div class="col-md-3 d-flex align-items-end gap-2">
                                    <button type="submit" class="btn btn-primary">Filter</button>
                                    <a href="products.php" class="btn btn-secondary">Reset</a>
                                </div>
                            </form>

                            <div class="table-responsive">
                                <table class="table table-striped" id="productsTable">
                                    <thead>
                                        <tr>
                                            <th>ID</th>
                                            <th>Product Name</th>
                                            <th>Generic Name</th>
                                            <th>Unit</th>
                                            <th>Total Stock</th>
                                            <th>Reorder Level</th>
                                            <th>Batches</th>
                                            <th>Stock Status</th>
                                            <th>Product Status</th>
                                            <th>Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php while ($product = $products->fetch_assoc()): ?>
                                            <?php
                                            $stockStatusText = '';
                                            $stockBadge = '';
                                            if ($product['total_stock'] == 0) {
                                                $stockStatusText = 'Out of Stock';
                                                $stockBadge = 'danger';
                                            } elseif ($product['total_stock'] <= $product['reorder_level']) {
                                                $stockStatusText = 'Low Stock';
                                                $stockBadge = 'warning';
                                            } else {
                                                $stockStatusText = 'In Stock';
                                                $stockBadge = 'success';
                                            }
                                            
                                            $isActive = $product['status'] === 'active';
                                            ?>
                                            <tr class="<?php echo !$isActive ? 'table-secondary' : ''; ?>">
                                                <td><?php echo $product['product_id']; ?></td>
                                                <td>
                                                    <strong><?php echo htmlspecialchars($product['product_name']); ?></strong>
                                                    <?php if (!$isActive): ?>
                                                        <span class="badge bg-secondary ms-2">Inactive</span>
                                                    <?php endif; ?>
                                                </td>
                                                <td><?php echo htmlspecialchars($product['generic_name'] ?? '-'); ?></td>
                                                <td><?php echo htmlspecialchars($product['unit'] ?? '-'); ?></td>
                                                <td><?php echo $product['total_stock']; ?></td>
                                                <td><?php echo $product['reorder_level']; ?></td>
                                                <td><?php echo $product['batch_count']; ?></td>
                                                <td><span class="badge bg-<?php echo $stockBadge; ?>"><?php echo $stockStatusText; ?></span></td>
                                                <td>
                                                    <span class="badge bg-<?php echo $isActive ? 'success' : 'secondary'; ?>">
                                                        <?php echo $isActive ? 'Active' : 'Inactive'; ?>
                                                    </span>
                                                </td>
                                                <td>
                                                    <button class="btn btn-sm btn-icon btn-warning" onclick="editProduct(<?php echo $product['product_id']; ?>)" title="Edit">
                                                        <svg width="20" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                                            <path d="M13.7476 20.4428H21.0002" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"></path>
                                                            <path fill-rule="evenodd" clip-rule="evenodd" d="M12.78 3.79479C13.5557 2.86779 14.95 2.73186 15.8962 3.49173C15.9485 3.53296 17.6295 4.83879 17.6295 4.83879C18.669 5.46719 18.992 6.80311 18.3494 7.82259C18.3153 7.87718 8.81195 19.7645 8.81195 19.7645C8.49578 20.1589 8.01583 20.3918 7.50291 20.3973L3.86353 20.443L3.04353 16.9723C2.92866 16.4843 3.04353 15.9718 3.3597 15.5773L12.78 3.79479Z" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"></path>
                                                            <path d="M11.021 6.00098L16.4732 10.1881" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"></path>
                                                        </svg>
                                                    </button>
                                                    <?php if ($isActive): ?>
                                                        <button class="btn btn-sm btn-icon btn-danger" onclick="toggleProductStatus(<?php echo $product['product_id']; ?>, 'inactive')" title="Deactivate Product">
                                                            <svg width="20" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                                                <path d="M12 22C17.5228 22 22 17.5228 22 12C22 6.47715 17.5228 2 12 2C6.47715 2 2 6.47715 2 12C2 17.5228 6.47715 22 12 22Z" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
                                                                <path d="M4.92896 4.92896L19.071 19.071" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
                                                            </svg>
                                                        </button>
                                                    <?php else: ?>
                                                        <button class="btn btn-sm btn-icon btn-success" onclick="toggleProductStatus(<?php echo $product['product_id']; ?>, 'active')" title="Activate Product">
                                                            <svg width="20" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                                                <path d="M9 12L11 14L15 10M21 12C21 16.9706 16.9706 21 12 21C7.02944 21 3 16.9706 3 12C3 7.02944 7.02944 3 12 3C16.9706 3 21 7.02944 21 12Z" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
                                                            </svg>
                                                        </button>
                                                    <?php endif; ?>
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

    <!-- Add/Edit Product Modal -->
    <div class="modal fade" id="productModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="productModalTitle">Add New Product</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <form id="productForm">
                        <input type="hidden" id="productId" name="product_id">
                        
                        <!-- Product Information Section -->
                        <div class="border-bottom pb-3 mb-3">
                            <h6 class="text-primary mb-3">Product Information</h6>
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label for="productName" class="form-label">Product Name *</label>
                                    <input type="text" class="form-control" id="productName" name="product_name" required>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label for="genericName" class="form-label">Generic Name</label>
                                    <input type="text" class="form-control" id="genericName" name="generic_name">
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-md-4 mb-3">
                                    <label for="unit" class="form-label">Unit</label>
                                    <input type="text" class="form-control" id="unit" name="unit" placeholder="e.g., Tablet, Capsule">
                                </div>
                                <div class="col-md-4 mb-3">
                                    <label for="reorderLevel" class="form-label">Reorder Level *</label>
                                    <input type="number" class="form-control" id="reorderLevel" name="reorder_level" value="10" required>
                                </div>
                                <div class="col-md-4 mb-3">
                                    <label for="productStatus" class="form-label">Status *</label>
                                    <select class="form-select" id="productStatus" name="status" required>
                                        <option value="active">Active</option>
                                        <option value="inactive">Inactive</option>
                                    </select>
                                </div>
                            </div>
                        </div>

                        <!-- Initial Stock Section (Only for new products) -->
                        <div id="initialStockSection">
                            <div class="d-flex justify-content-between align-items-center mb-3">
                                <h6 class="text-primary mb-0">Initial Stock Details (Optional)</h6>
                                <div class="form-check form-switch">
                                    <input class="form-check-input" type="checkbox" id="addInitialStock" onchange="toggleInitialStock()">
                                    <label class="form-check-label" for="addInitialStock">
                                        Add initial stock now
                                    </label>
                                </div>
                            </div>
                            
                            <div id="stockFields" style="display: none;">
                                <div class="alert alert-info">
                                    <small><i class="icon">ℹ️</i> You can add stock details now or add them later from Stock Management page.</small>
                                </div>
                                <div class="row">
                                    <div class="col-md-6 mb-3">
                                        <label for="batchNo" class="form-label">Batch Number *</label>
                                        <input type="text" class="form-control" id="batchNo" name="batch_no">
                                    </div>
                                    <div class="col-md-6 mb-3">
                                        <label for="expiryDate" class="form-label">Expiry Date *</label>
                                        <input type="date" class="form-control" id="expiryDate" name="expiry_date">
                                    </div>
                                </div>
                                <div class="row">
                                    <div class="col-md-4 mb-3">
                                        <label for="costPrice" class="form-label">Cost Price (Rs.) </label>
                                        <input type="number" class="form-control" id="costPrice" name="cost_price" step="0.01">
                                    </div>
                                    <div class="col-md-4 mb-3">
                                        <label for="sellingPrice" class="form-label">Selling Price (Rs.) *</label>
                                        <input type="number" class="form-control" id="sellingPrice" name="selling_price" step="0.01">
                                    </div>
                                    <div class="col-md-4 mb-3">
                                        <label for="quantity" class="form-label">Quantity *</label>
                                        <input type="number" class="form-control" id="quantity" name="quantity_in_stock">
                                    </div>
                                </div>
                            </div>
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="button" class="btn btn-primary" onclick="saveProduct()">Save Product</button>
                </div>
            </div>
        </div>
    </div>

    <script src="assets/js/core/libs.min.js"></script>
    <script src="assets/js/core/external.min.js"></script>
    <script src="assets/js/hope-ui.js" defer></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/sweetalert2/11.10.5/sweetalert2.all.min.js"></script>

    <script>
        const productModal = new bootstrap.Modal(document.getElementById('productModal'));

        function toggleInitialStock() {
            const checkbox = document.getElementById('addInitialStock');
            const stockFields = document.getElementById('stockFields');
            const fields = stockFields.querySelectorAll('input');
            
            if (checkbox.checked) {
                stockFields.style.display = 'block';
                fields.forEach(field => {
                    if (field.name !== 'batch_no') {
                        field.setAttribute('required', 'required');
                    }
                });
            } else {
                stockFields.style.display = 'none';
                fields.forEach(field => {
                    field.removeAttribute('required');
                    field.value = '';
                });
            }
        }

        function showAddProductModal() {
            document.getElementById('productModalTitle').textContent = 'Add New Product';
            document.getElementById('productForm').reset();
            document.getElementById('productId').value = '';
            document.getElementById('productStatus').value = 'active';
            document.getElementById('initialStockSection').style.display = 'block';
            document.getElementById('addInitialStock').checked = false;
            document.getElementById('stockFields').style.display = 'none';
            productModal.show();
        }

        function exportProducts() {
            const urlParams = new URLSearchParams(window.location.search);
            window.location.href = 'export_products.php?' + urlParams.toString();
        }

        function editProduct(productId) {
            fetch('api/get_product.php?id=' + productId)
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        document.getElementById('productModalTitle').textContent = 'Edit Product';
                        document.getElementById('productId').value = data.product.product_id;
                        document.getElementById('productName').value = data.product.product_name;
                        document.getElementById('genericName').value = data.product.generic_name || '';
                        document.getElementById('unit').value = data.product.unit || '';
                        document.getElementById('reorderLevel').value = data.product.reorder_level;
                        document.getElementById('productStatus').value = data.product.status;
                        document.getElementById('initialStockSection').style.display = 'none';
                        productModal.show();
                    } else {
                        Swal.fire('Error', 'Failed to load product data', 'error');
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    Swal.fire('Error', 'An error occurred', 'error');
                });
        }

        function saveProduct() {
            const form = document.getElementById('productForm');
            const formData = new FormData(form);
            const addStock = document.getElementById('addInitialStock').checked;
            
            // Add flag to indicate if initial stock should be added
            formData.append('add_initial_stock', addStock ? '1' : '0');

            fetch('api/save_product.php', {
                    method: 'POST',
                    body: formData
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        Swal.fire('Success', data.message, 'success').then(() => {
                            location.reload();
                        });
                    } else {
                        Swal.fire('Error', data.message, 'error');
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    Swal.fire('Error', 'An error occurred', 'error');
                });
        }

        function toggleProductStatus(productId, newStatus) {
            const action = newStatus === 'active' ? 'activate' : 'deactivate';
            const actionText = newStatus === 'active' ? 'Activate' : 'Deactivate';
            
            Swal.fire({
                title: `${actionText} Product?`,
                text: newStatus === 'inactive' 
                    ? 'This product will not appear in POS and new batch additions. Existing stock will remain.' 
                    : 'This product will be available again in POS and for new batches.',
                icon: 'question',
                showCancelButton: true,
                confirmButtonColor: newStatus === 'active' ? '#28a745' : '#dc3545',
                cancelButtonColor: '#6c757d',
                confirmButtonText: `Yes, ${action} it!`
            }).then((result) => {
                if (result.isConfirmed) {
                    fetch('api/toggle_product_status.php', {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json'
                            },
                            body: JSON.stringify({
                                product_id: productId,
                                status: newStatus
                            })
                        })
                        .then(response => response.json())
                        .then(data => {
                            if (data.success) {
                                Swal.fire('Success!', data.message, 'success').then(() => {
                                    location.reload();
                                });
                            } else {
                                Swal.fire('Error', data.message, 'error');
                            }
                        })
                        .catch(error => {
                            console.error('Error:', error);
                            Swal.fire('Error', 'An error occurred', 'error');
                        });
                }
            });
        }
    </script>
</body>

</html>