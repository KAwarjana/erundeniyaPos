<?php
require_once 'auth.php';
Auth::requireAuth();

$conn = getDBConnection();
$suppliers = $conn->query("SELECT 
    s.*,
    COUNT(p.purchase_id) as total_purchases,
    COALESCE(SUM(p.total_amount), 0) as total_purchased
FROM suppliers s
LEFT JOIN purchases p ON s.supplier_id = p.supplier_id
GROUP BY s.supplier_id
ORDER BY s.name");
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
                                    <h4 class="card-title mb-0">Suppliers Management</h4>
                                </div>

                                <div class="col"></div>

                                <!-- button -->
                                <div class="col-12 col-sm-auto mt-sm-2">
                                    <button class="btn btn-primary w-sm-auto text-nowrap" onclick="showAddSupplierModal()">
                                        <i class="icon">
                                            <svg width="20" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                                <path d="M12 5V19M5 12H19" stroke="currentColor" stroke-width="2" stroke-linecap="round" />
                                            </svg>
                                        </i>
                                        Add New Supplier
                                    </button>
                                </div>
                            </div>
                        </div>
                        <div class="card-body">
                            <div class="table-responsive">
                                <table class="table table-striped">
                                    <thead>
                                        <tr>
                                            <th>ID</th>
                                            <th>Name</th>
                                            <th>Contact</th>
                                            <th>Email</th>
                                            <th>Address</th>
                                            <th>Total Purchases</th>
                                            <th>Total Amount</th>
                                            <th>Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php while ($supplier = $suppliers->fetch_assoc()): ?>
                                            <tr>
                                                <td><?php echo $supplier['supplier_id']; ?></td>
                                                <td><strong><?php echo htmlspecialchars($supplier['name']); ?></strong></td>
                                                <td><?php echo htmlspecialchars($supplier['contact_no'] ?? '-'); ?></td>
                                                <td><?php echo htmlspecialchars($supplier['email'] ?? '-'); ?></td>
                                                <td><?php echo htmlspecialchars($supplier['address'] ?? '-'); ?></td>
                                                <td><?php echo $supplier['total_purchases']; ?></td>
                                                <td>Rs. <?php echo number_format($supplier['total_purchased'], 2); ?></td>
                                                <td>
                                                    <button class="btn btn-sm btn-icon btn-warning" onclick="editSupplier(<?php echo $supplier['supplier_id']; ?>)">
                                                        <svg width="20" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                                            <path d="M13.7476 20.4428H21.0002" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"></path>
                                                            <path fill-rule="evenodd" clip-rule="evenodd" d="M12.78 3.79479C13.5557 2.86779 14.95 2.73186 15.8962 3.49173L17.6295 4.83879C18.669 5.46719 18.992 6.80311 18.3494 7.82259L8.81195 19.7645C8.49578 20.1589 8.01583 20.3918 7.50291 20.3973L3.86353 20.443L3.04353 16.9723C2.92866 16.4843 3.04353 15.9718 3.3597 15.5773L12.78 3.79479Z" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"></path>
                                                        </svg>
                                                    </button>
                                                    <button class="btn btn-sm btn-icon btn-danger" onclick="deleteSupplier(<?php echo $supplier['supplier_id']; ?>)">
                                                        <svg width="20" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                                            <path d="M19.3248 9.46826C19.3248 9.46826 18.7818 16.2033 18.4668 19.0403C18.3168 20.3953 17.4798 21.1893 16.1088 21.2143C13.4998 21.2613 10.8878 21.2643 8.27979 21.2093C6.96079 21.1823 6.13779 20.3783 5.99079 19.0473C5.67379 16.1853 5.13379 9.46826 5.13379 9.46826" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"></path>
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

    <!-- Supplier Modal -->
    <div class="modal fade" id="supplierModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="supplierModalTitle">Add New Supplier</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <form id="supplierForm">
                        <input type="hidden" id="supplierId" name="supplier_id">
                        <div class="mb-3">
                            <label for="supplierName" class="form-label">Name *</label>
                            <input type="text" class="form-control" id="supplierName" name="name" required>
                        </div>
                        <div class="mb-3">
                            <label for="contactNo" class="form-label">Contact Number</label>
                            <input type="text" class="form-control" id="contactNo" name="contact_no">
                        </div>
                        <div class="mb-3">
                            <label for="email" class="form-label">Email</label>
                            <input type="email" class="form-control" id="email" name="email">
                        </div>
                        <div class="mb-3">
                            <label for="address" class="form-label">Address</label>
                            <textarea class="form-control" id="address" name="address" rows="3"></textarea>
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="button" class="btn btn-primary" onclick="saveSupplier()">Save Supplier</button>
                </div>
            </div>
        </div>
    </div>

    <script src="assets/js/core/libs.min.js"></script>
    <script src="assets/js/core/external.min.js"></script>
    <script src="assets/js/hope-ui.js" defer></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/sweetalert2/11.10.5/sweetalert2.all.min.js"></script>

    <script>
        const supplierModal = new bootstrap.Modal(document.getElementById('supplierModal'));

        function showAddSupplierModal() {
            document.getElementById('supplierModalTitle').textContent = 'Add New Supplier';
            document.getElementById('supplierForm').reset();
            document.getElementById('supplierId').value = '';
            supplierModal.show();
        }

        function editSupplier(supplierId) {
            fetch('api/get_supplier.php?id=' + supplierId)
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        document.getElementById('supplierModalTitle').textContent = 'Edit Supplier';
                        document.getElementById('supplierId').value = data.supplier.supplier_id;
                        document.getElementById('supplierName').value = data.supplier.name;
                        document.getElementById('contactNo').value = data.supplier.contact_no || '';
                        document.getElementById('email').value = data.supplier.email || '';
                        document.getElementById('address').value = data.supplier.address || '';
                        supplierModal.show();
                    }
                });
        }

        function saveSupplier() {
            const form = document.getElementById('supplierForm');
            const formData = new FormData(form);

            fetch('api/save_supplier.php', {
                    method: 'POST',
                    body: formData
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        Swal.fire('Success', data.message, 'success').then(() => location.reload());
                    } else {
                        Swal.fire('Error', data.message, 'error');
                    }
                });
        }

        function deleteSupplier(supplierId) {
            Swal.fire({
                title: 'Delete Supplier?',
                text: 'This action cannot be undone!',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#d33',
                confirmButtonText: 'Yes, delete!'
            }).then((result) => {
                if (result.isConfirmed) {
                    fetch('api/delete_supplier.php', {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json'
                            },
                            body: JSON.stringify({
                                supplier_id: supplierId
                            })
                        })
                        .then(response => response.json())
                        .then(data => {
                            if (data.success) {
                                Swal.fire('Deleted!', data.message, 'success').then(() => location.reload());
                            } else {
                                Swal.fire('Error', data.message, 'error');
                            }
                        });
                }
            });
        }
    </script>
</body>

</html>