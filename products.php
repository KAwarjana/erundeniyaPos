<?php
require_once 'auth.php';
Auth::requireAuth();

$conn = getDBConnection();

// Get all products with total stock
$products = $conn->query("SELECT 
    p.product_id,
    p.product_name,
    p.generic_name,
    p.unit,
    p.reorder_level,
    COALESCE(SUM(pb.quantity_in_stock), 0) as total_stock,
    COUNT(pb.batch_id) as batch_count
FROM products p
LEFT JOIN product_batches pb ON p.product_id = pb.product_id
GROUP BY p.product_id
ORDER BY p.product_name");
?>
<!doctype html>
<html lang="en" dir="ltr" data-bs-theme="light">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <title>Products | Ayurvedic Pharmacy</title>
    
    <link rel="shortcut icon" href="assets/images/logo_white.png">
    <link rel="stylesheet" href="assets/css/core/libs.min.css">
    <link rel="stylesheet" href="assets/css/hope-ui.min.css?v=5.0.0">
    <link rel="stylesheet" href="assets/css/custom.min.css?v=5.0.0">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/sweetalert2/11.10.5/sweetalert2.min.css">
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
                        <div class="card-header d-flex justify-content-between">
                            <div class="header-title">
                                <h4 class="card-title">Products Management</h4>
                            </div>
                            <div>
                                <button class="btn btn-success me-2" onclick="exportProducts()">
                                    📊 Export Products
                                </button>
                                <button class="btn btn-primary" onclick="showAddProductModal()">
                                    <i class="icon">
                                        <svg width="20" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                            <path d="M12 5V19M5 12H19" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
                                        </svg>
                                    </i>
                                    Add New Product
                                </button>
                            </div>
                        </div>
                        <div class="card-body">
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
                                            <th>Status</th>
                                            <th>Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php while ($product = $products->fetch_assoc()): ?>
                                            <?php 
                                            $stockStatus = '';
                                            $statusBadge = '';
                                            if ($product['total_stock'] == 0) {
                                                $stockStatus = 'Out of Stock';
                                                $statusBadge = 'danger';
                                            } elseif ($product['total_stock'] <= $product['reorder_level']) {
                                                $stockStatus = 'Low Stock';
                                                $statusBadge = 'warning';
                                            } else {
                                                $stockStatus = 'In Stock';
                                                $statusBadge = 'success';
                                            }
                                            ?>
                                            <tr>
                                                <td><?php echo $product['product_id']; ?></td>
                                                <td><strong><?php echo htmlspecialchars($product['product_name']); ?></strong></td>
                                                <td><?php echo htmlspecialchars($product['generic_name'] ?? '-'); ?></td>
                                                <td><?php echo htmlspecialchars($product['unit'] ?? '-'); ?></td>
                                                <td><?php echo $product['total_stock']; ?></td>
                                                <td><?php echo $product['reorder_level']; ?></td>
                                                <td><?php echo $product['batch_count']; ?></td>
                                                <td><span class="badge bg-<?php echo $statusBadge; ?>"><?php echo $stockStatus; ?></span></td>
                                                <td>
                                                    <button class="btn btn-sm btn-icon btn-warning" onclick="editProduct(<?php echo $product['product_id']; ?>)" title="Edit">
                                                        <svg width="20" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                                            <path d="M13.7476 20.4428H21.0002" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"></path>
                                                            <path fill-rule="evenodd" clip-rule="evenodd" d="M12.78 3.79479C13.5557 2.86779 14.95 2.73186 15.8962 3.49173C15.9485 3.53296 17.6295 4.83879 17.6295 4.83879C18.669 5.46719 18.992 6.80311 18.3494 7.82259C18.3153 7.87718 8.81195 19.7645 8.81195 19.7645C8.49578 20.1589 8.01583 20.3918 7.50291 20.3973L3.86353 20.443L3.04353 16.9723C2.92866 16.4843 3.04353 15.9718 3.3597 15.5773L12.78 3.79479Z" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"></path>
                                                            <path d="M11.021 6.00098L16.4732 10.1881" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"></path>
                                                        </svg>
                                                    </button>
                                                    <button class="btn btn-sm btn-icon btn-danger" onclick="deleteProduct(<?php echo $product['product_id']; ?>)" title="Delete">
                                                        <svg width="20" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                                            <path d="M19.3248 9.46826C19.3248 9.46826 18.7818 16.2033 18.4668 19.0403C18.3168 20.3953 17.4798 21.1893 16.1088 21.2143C13.4998 21.2613 10.8878 21.2643 8.27979 21.2093C6.96079 21.1823 6.13779 20.3783 5.99079 19.0473C5.67379 16.1853 5.13379 9.46826 5.13379 9.46826" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"></path>
                                                            <path d="M20.708 6.23975H3.75" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"></path>
                                                            <path d="M17.4406 6.23973C16.6556 6.23973 15.9796 5.68473 15.8256 4.91573L15.5826 3.69973C15.4326 3.13873 14.9246 2.75073 14.3456 2.75073H10.1126C9.53358 2.75073 9.02558 3.13873 8.87558 3.69973L8.63258 4.91573C8.47858 5.68473 7.80258 6.23973 7.01758 6.23973" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"></path>
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

    <!-- Add/Edit Product Modal -->
    <div class="modal fade" id="productModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="productModalTitle">Add New Product</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <form id="productForm">
                        <input type="hidden" id="productId" name="product_id">
                        <div class="mb-3">
                            <label for="productName" class="form-label">Product Name *</label>
                            <input type="text" class="form-control" id="productName" name="product_name" required>
                        </div>
                        <div class="mb-3">
                            <label for="genericName" class="form-label">Generic Name</label>
                            <input type="text" class="form-control" id="genericName" name="generic_name">
                        </div>
                        <div class="mb-3">
                            <label for="unit" class="form-label">Unit</label>
                            <input type="text" class="form-control" id="unit" name="unit" placeholder="e.g., Tablet, Capsule, Syrup">
                        </div>
                        <div class="mb-3">
                            <label for="reorderLevel" class="form-label">Reorder Level *</label>
                            <input type="number" class="form-control" id="reorderLevel" name="reorder_level" value="10" required>
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

        function showAddProductModal() {
            document.getElementById('productModalTitle').textContent = 'Add New Product';
            document.getElementById('productForm').reset();
            document.getElementById('productId').value = '';
            productModal.show();
        }
        
        function exportProducts() {
            window.location.href = 'export_products.php';
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

        function deleteProduct(productId) {
            Swal.fire({
                title: 'Delete Product?',
                text: 'This will also delete all batches associated with this product. This action cannot be undone!',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#d33',
                cancelButtonColor: '#6c757d',
                confirmButtonText: 'Yes, delete it!'
            }).then((result) => {
                if (result.isConfirmed) {
                    fetch('api/delete_product.php', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json'
                        },
                        body: JSON.stringify({ product_id: productId })
                    })
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            Swal.fire('Deleted!', data.message, 'success').then(() => {
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