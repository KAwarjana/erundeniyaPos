<?php
require_once 'auth.php';
Auth::requireAuth();

$conn = getDBConnection();

// Get all product batches with product info
$batches = $conn->query("SELECT 
    pb.batch_id,
    pb.batch_no,
    pb.expiry_date,
    pb.cost_price,
    pb.selling_price,
    pb.quantity_in_stock,
    p.product_id,
    p.product_name,
    p.generic_name,
    p.unit,
    DATEDIFF(pb.expiry_date, CURDATE()) as days_to_expiry
FROM product_batches pb
JOIN products p ON pb.product_id = p.product_id
ORDER BY p.product_name, pb.expiry_date");

// Get products for dropdown
$products = $conn->query("SELECT product_id, product_name, generic_name FROM products ORDER BY product_name");
?>
<!doctype html>
<html lang="en" dir="ltr" data-bs-theme="light">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <title>Stock Management | Ayurvedic Pharmacy</title>
    
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
                                <h4 class="card-title">Stock Management - Product Batches</h4>
                            </div>
                            <button class="btn btn-primary" onclick="showAddBatchModal()">
                                <i class="icon">
                                    <svg width="20" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                        <path d="M12 5V19M5 12H19" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
                                    </svg>
                                </i>
                                Add New Batch
                            </button>
                        </div>
                        <div class="card-body">
                            <div class="table-responsive">
                                <table class="table table-striped" id="stockTable">
                                    <thead>
                                        <tr>
                                            <th>Product Name</th>
                                            <th>Batch No</th>
                                            <th>Expiry Date</th>
                                            <th>Cost Price</th>
                                            <th>Selling Price</th>
                                            <th>Stock</th>
                                            <th>Status</th>
                                            <th>Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php while ($batch = $batches->fetch_assoc()): ?>
                                            <?php
                                            $statusBadge = 'success';
                                            $statusText = 'Good';
                                            
                                            if ($batch['days_to_expiry'] < 0) {
                                                $statusBadge = 'dark';
                                                $statusText = 'Expired';
                                            } elseif ($batch['days_to_expiry'] <= 30) {
                                                $statusBadge = 'danger';
                                                $statusText = 'Expiring Soon';
                                            } elseif ($batch['days_to_expiry'] <= 90) {
                                                $statusBadge = 'warning';
                                                $statusText = 'Near Expiry';
                                            }
                                            
                                            if ($batch['quantity_in_stock'] == 0) {
                                                $statusBadge = 'secondary';
                                                $statusText = 'Out of Stock';
                                            }
                                            ?>
                                            <tr>
                                                <td>
                                                    <strong><?php echo htmlspecialchars($batch['product_name']); ?></strong>
                                                    <?php if ($batch['generic_name']): ?>
                                                        <br><small class="text-muted"><?php echo htmlspecialchars($batch['generic_name']); ?></small>
                                                    <?php endif; ?>
                                                </td>
                                                <td><?php echo htmlspecialchars($batch['batch_no']); ?></td>
                                                <td>
                                                    <?php echo date('M d, Y', strtotime($batch['expiry_date'])); ?>
                                                    <br><small class="text-muted"><?php echo abs($batch['days_to_expiry']); ?> days <?php echo $batch['days_to_expiry'] < 0 ? 'ago' : 'left'; ?></small>
                                                </td>
                                                <td>Rs. <?php echo number_format($batch['cost_price'], 2); ?></td>
                                                <td>Rs. <?php echo number_format($batch['selling_price'], 2); ?></td>
                                                <td><strong><?php echo $batch['quantity_in_stock']; ?></strong></td>
                                                <td><span class="badge bg-<?php echo $statusBadge; ?>"><?php echo $statusText; ?></span></td>
                                                <td>
                                                    <button class="btn btn-sm btn-icon btn-info" onclick="adjustStock(<?php echo $batch['batch_id']; ?>, '<?php echo htmlspecialchars($batch['product_name']); ?>', '<?php echo htmlspecialchars($batch['batch_no']); ?>', <?php echo $batch['quantity_in_stock']; ?>)" title="Adjust Stock">
                                                        <svg width="20" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                                            <path d="M12 5V19M5 12H19" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
                                                        </svg>
                                                    </button>
                                                    <button class="btn btn-sm btn-icon btn-warning" onclick="editBatch(<?php echo $batch['batch_id']; ?>)" title="Edit">
                                                        <svg width="20" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                                            <path d="M13.7476 20.4428H21.0002" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"></path>
                                                            <path fill-rule="evenodd" clip-rule="evenodd" d="M12.78 3.79479C13.5557 2.86779 14.95 2.73186 15.8962 3.49173C15.9485 3.53296 17.6295 4.83879 17.6295 4.83879C18.669 5.46719 18.992 6.80311 18.3494 7.82259C18.3153 7.87718 8.81195 19.7645 8.81195 19.7645C8.49578 20.1589 8.01583 20.3918 7.50291 20.3973L3.86353 20.443L3.04353 16.9723C2.92866 16.4843 3.04353 15.9718 3.3597 15.5773L12.78 3.79479Z" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"></path>
                                                            <path d="M11.021 6.00098L16.4732 10.1881" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"></path>
                                                        </svg>
                                                    </button>
                                                    <button class="btn btn-sm btn-icon btn-danger" onclick="deleteBatch(<?php echo $batch['batch_id']; ?>)" title="Delete">
                                                        <svg width="20" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                                            <path d="M19.3248 9.46826C19.3248 9.46826 18.7818 16.2033 18.4668 19.0403C18.3168 20.3953 17.4798 21.1893 16.1088 21.2143C13.4998 21.2613 10.8878 21.2643 8.27979 21.2093C6.96079 21.1823 6.13779 20.3783 5.99079 19.0473C5.67379 16.1853 5.13379 9.46826 5.13379 9.46826" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"></path>
                                                            <path d="M20.708 6.23975H3.75" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"></path>
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

    <!-- Add/Edit Batch Modal -->
    <div class="modal fade" id="batchModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="batchModalTitle">Add New Batch</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <form id="batchForm">
                        <input type="hidden" id="batchId" name="batch_id">
                        <div class="mb-3">
                            <label for="productId" class="form-label">Product *</label>
                            <select class="form-select" id="productId" name="product_id" required>
                                <option value="">Select Product</option>
                                <?php 
                                $products->data_seek(0);
                                while ($product = $products->fetch_assoc()): 
                                ?>
                                    <option value="<?php echo $product['product_id']; ?>">
                                        <?php echo htmlspecialchars($product['product_name']); ?>
                                        <?php if ($product['generic_name']): ?>
                                            - <?php echo htmlspecialchars($product['generic_name']); ?>
                                        <?php endif; ?>
                                    </option>
                                <?php endwhile; ?>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label for="batchNo" class="form-label">Batch Number *</label>
                            <input type="text" class="form-control" id="batchNo" name="batch_no" required>
                        </div>
                        <div class="mb-3">
                            <label for="expiryDate" class="form-label">Expiry Date *</label>
                            <input type="date" class="form-control" id="expiryDate" name="expiry_date" required>
                        </div>
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="costPrice" class="form-label">Cost Price *</label>
                                <input type="number" class="form-control" id="costPrice" name="cost_price" step="0.01" required>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="sellingPrice" class="form-label">Selling Price *</label>
                                <input type="number" class="form-control" id="sellingPrice" name="selling_price" step="0.01" required>
                            </div>
                        </div>
                        <div class="mb-3">
                            <label for="quantity" class="form-label">Quantity *</label>
                            <input type="number" class="form-control" id="quantity" name="quantity_in_stock" required>
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="button" class="btn btn-primary" onclick="saveBatch()">Save Batch</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Stock Adjustment Modal -->
    <div class="modal fade" id="adjustmentModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Adjust Stock</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div id="adjustmentInfo" class="mb-3"></div>
                    <form id="adjustmentForm">
                        <input type="hidden" id="adjustBatchId" name="batch_id">
                        <div class="mb-3">
                            <label class="form-label">Adjustment Type *</label>
                            <select class="form-select" id="adjustmentType" name="adjustment_type" required>
                                <option value="increase">Increase Stock</option>
                                <option value="decrease">Decrease Stock</option>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label for="adjustQuantity" class="form-label">Quantity *</label>
                            <input type="number" class="form-control" id="adjustQuantity" name="quantity" required min="1">
                        </div>
                        <div class="mb-3">
                            <label for="adjustReason" class="form-label">Reason *</label>
                            <textarea class="form-control" id="adjustReason" name="reason" rows="3" required></textarea>
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="button" class="btn btn-primary" onclick="saveAdjustment()">Save Adjustment</button>
                </div>
            </div>
        </div>
    </div>

    <script src="assets/js/core/libs.min.js"></script>
    <script src="assets/js/core/external.min.js"></script>
    <script src="assets/js/hope-ui.js" defer></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/sweetalert2/11.10.5/sweetalert2.all.min.js"></script>

    <script>
        const batchModal = new bootstrap.Modal(document.getElementById('batchModal'));
        const adjustmentModal = new bootstrap.Modal(document.getElementById('adjustmentModal'));

        function showAddBatchModal() {
            document.getElementById('batchModalTitle').textContent = 'Add New Batch';
            document.getElementById('batchForm').reset();
            document.getElementById('batchId').value = '';
            batchModal.show();
        }

        function editBatch(batchId) {
            fetch('api/get_batch.php?id=' + batchId)
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        document.getElementById('batchModalTitle').textContent = 'Edit Batch';
                        document.getElementById('batchId').value = data.batch.batch_id;
                        document.getElementById('productId').value = data.batch.product_id;
                        document.getElementById('batchNo').value = data.batch.batch_no;
                        document.getElementById('expiryDate').value = data.batch.expiry_date;
                        document.getElementById('costPrice').value = data.batch.cost_price;
                        document.getElementById('sellingPrice').value = data.batch.selling_price;
                        document.getElementById('quantity').value = data.batch.quantity_in_stock;
                        batchModal.show();
                    } else {
                        Swal.fire('Error', 'Failed to load batch data', 'error');
                    }
                });
        }

        function saveBatch() {
            const form = document.getElementById('batchForm');
            const formData = new FormData(form);

            fetch('api/save_batch.php', {
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
            });
        }

        function deleteBatch(batchId) {
            Swal.fire({
                title: 'Delete Batch?',
                text: 'This action cannot be undone!',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#d33',
                cancelButtonColor: '#6c757d',
                confirmButtonText: 'Yes, delete it!'
            }).then((result) => {
                if (result.isConfirmed) {
                    fetch('api/delete_batch.php', {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json' },
                        body: JSON.stringify({ batch_id: batchId })
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
                    });
                }
            });
        }

        function adjustStock(batchId, productName, batchNo, currentStock) {
            document.getElementById('adjustBatchId').value = batchId;
            document.getElementById('adjustmentInfo').innerHTML = `
                <div class="alert alert-info">
                    <strong>Product:</strong> ${productName}<br>
                    <strong>Batch:</strong> ${batchNo}<br>
                    <strong>Current Stock:</strong> ${currentStock}
                </div>
            `;
            document.getElementById('adjustmentForm').reset();
            adjustmentModal.show();
        }

        function saveAdjustment() {
            const form = document.getElementById('adjustmentForm');
            const formData = new FormData(form);

            fetch('api/save_adjustment.php', {
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
            });
        }
    </script>
</body>
</html>