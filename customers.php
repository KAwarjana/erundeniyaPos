<?php
require_once 'auth.php';
Auth::requireAuth();

$conn = getDBConnection();

// Get search parameter
$searchTerm = $_GET['search'] ?? '';

// Build query with search
$sql = "SELECT 
    c.*,
    COUNT(DISTINCT s.sale_id) as total_sales,
    COALESCE(SUM(s.net_amount), 0) as total_spent
FROM customers c
LEFT JOIN sales s ON c.customer_id = s.customer_id";

if (!empty($searchTerm)) {
    $sql .= " WHERE c.name LIKE ? OR c.contact_no LIKE ?";
}

$sql .= " GROUP BY c.customer_id ORDER BY c.name";

$stmt = $conn->prepare($sql);

if (!empty($searchTerm)) {
    $searchParam = "%$searchTerm%";
    $stmt->bind_param("ss", $searchParam, $searchParam);
}

$stmt->execute();
$customers = $stmt->get_result();
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
        /* Fix autocomplete background color */
        input:-webkit-autofill,
        input:-webkit-autofill:hover,
        input:-webkit-autofill:focus,
        input:-webkit-autofill:active {
            -webkit-box-shadow: 0 0 0 30px white inset !important;
            -webkit-text-fill-color: #000 !important;
            transition: background-color 5000s ease-in-out 0s;
        }
        
        /* For darker themes, adjust the color */
        input:-webkit-autofill {
            caret-color: #000;
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
            <div class="row">
                <div class="col-sm-12">
                    <div class="card">
                        <div class="card-header">
                            <div class="row align-items-center">
                                <div class="col-12 col-sm-auto mb-2 mb-sm-0">
                                    <h4 class="card-title mb-0">Customers Management</h4>
                                </div>

                                <div class="col"></div>

                                <!-- Buttons -->
                                <div class="col-12 col-sm-auto mt-sm-2">
                                    <div class="d-flex flex-column flex-sm-row gap-2">
                                        <button class="btn btn-success w-100 w-sm-auto text-nowrap" onclick="exportCustomers()">
                                            📊 Export to CSV
                                        </button>
                                        <button class="btn btn-primary w-100 w-sm-auto text-nowrap" onclick="showAddCustomerModal()">
                                            <i class="icon">
                                                <svg width="20" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                                    <path d="M12 5V19M5 12H19" stroke="currentColor" stroke-width="2" stroke-linecap="round" />
                                                </svg>
                                            </i>
                                            Add New Customer
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="card-body">
                            <!-- Search Bar -->
                            <form method="GET" class="row g-3 mb-4">
                                <div class="col-md-10">
                                    <div class="input-group">
                                        <span class="input-group-text">
                                            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                                <circle cx="11" cy="11" r="8" stroke="currentColor" stroke-width="2"/>
                                                <path d="M21 21L16.65 16.65" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
                                            </svg>
                                        </span>
                                        <input 
                                            type="text" 
                                            class="form-control" 
                                            name="search" 
                                            placeholder="Search by name or contact number..."
                                            value="<?php echo htmlspecialchars($searchTerm); ?>"
                                        >
                                    </div>
                                </div>
                                <div class="col-md-2 d-flex gap-2">
                                    <button type="submit" class="btn btn-primary flex-fill">Search</button>
                                    <?php if (!empty($searchTerm)): ?>
                                        <a href="customers.php" class="btn btn-secondary flex-fill">Clear</a>
                                    <?php endif; ?>
                                </div>
                            </form>

                            <div class="table-responsive">
                                <table class="table table-striped" id="customersTable">
                                    <thead>
                                        <tr>
                                            <th>ID</th>
                                            <th>Name</th>
                                            <th>Contact</th>
                                            <th>Address</th>
                                            <th>Credit Limit</th>
                                            <th>Total Sales</th>
                                            <th>Total Spent</th>
                                            <th>Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php if ($customers->num_rows > 0): ?>
                                            <?php while ($customer = $customers->fetch_assoc()): ?>
                                                <tr>
                                                    <td><?php echo $customer['customer_id']; ?></td>
                                                    <td><strong><?php echo htmlspecialchars($customer['name']); ?></strong></td>
                                                    <td><?php echo htmlspecialchars($customer['contact_no']); ?></td>
                                                    <td><?php echo htmlspecialchars($customer['address'] ?? '-'); ?></td>
                                                    <td>Rs. <?php echo number_format($customer['credit_limit'], 2); ?></td>
                                                    <td><?php echo $customer['total_sales']; ?></td>
                                                    <td>Rs. <?php echo number_format($customer['total_spent'], 2); ?></td>
                                                    <td>
                                                        <button class="btn btn-sm btn-icon btn-warning" onclick="editCustomer(<?php echo $customer['customer_id']; ?>)" title="Edit">
                                                            <svg width="20" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                                                <path d="M13.7476 20.4428H21.0002" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"></path>
                                                                <path fill-rule="evenodd" clip-rule="evenodd" d="M12.78 3.79479C13.5557 2.86779 14.95 2.73186 15.8962 3.49173C15.9485 3.53296 17.6295 4.83879 17.6295 4.83879C18.669 5.46719 18.992 6.80311 18.3494 7.82259C18.3153 7.87718 8.81195 19.7645 8.81195 19.7645C8.49578 20.1589 8.01583 20.3918 7.50291 20.3973L3.86353 20.443L3.04353 16.9723C2.92866 16.4843 3.04353 15.9718 3.3597 15.5773L12.78 3.79479Z" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"></path>
                                                            </svg>
                                                        </button>
                                                        <button class="btn btn-sm btn-icon btn-danger" onclick="deleteCustomer(<?php echo $customer['customer_id']; ?>)" title="Delete">
                                                            <svg width="20" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                                                <path d="M19.3248 9.46826C19.3248 9.46826 18.7818 16.2033 18.4668 19.0403C18.3168 20.3953 17.4798 21.1893 16.1088 21.2143C13.4998 21.2613 10.8878 21.2643 8.27979 21.2093C6.96079 21.1823 6.13779 20.3783 5.99079 19.0473C5.67379 16.1853 5.13379 9.46826 5.13379 9.46826" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"></path>
                                                                <path d="M20.708 6.23975H3.75" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"></path>
                                                            </svg>
                                                        </button>
                                                    </td>
                                                </tr>
                                            <?php endwhile; ?>
                                        <?php else: ?>
                                            <tr>
                                                <td colspan="8" class="text-center py-5">
                                                    <div class="alert alert-info mb-0">
                                                        <strong>No customers found</strong><br>
                                                        <?php if (!empty($searchTerm)): ?>
                                                            No customers match your search "<?php echo htmlspecialchars($searchTerm); ?>". Try a different search term.
                                                        <?php else: ?>
                                                            There are no customers in the system yet. Click "Add New Customer" to create one.
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

    <!-- Customer Modal -->
    <div class="modal fade" id="customerModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="customerModalTitle">Add New Customer</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <form id="customerForm">
                        <input type="hidden" id="customerId" name="customer_id">
                        <div class="mb-3">
                            <label for="customerName" class="form-label">Name *</label>
                            <input type="text" class="form-control" id="customerName" name="name" required>
                        </div>
                        <div class="mb-3">
                            <label for="contactNo" class="form-label">Contact Number *</label>
                            <input type="text" class="form-control" id="contactNo" name="contact_no" required>
                        </div>
                        <div class="mb-3">
                            <label for="address" class="form-label">Address</label>
                            <textarea class="form-control" id="address" name="address" rows="3"></textarea>
                        </div>
                        <div class="mb-3">
                            <label for="creditLimit" class="form-label">Credit Limit</label>
                            <input type="number" class="form-control" id="creditLimit" name="credit_limit" value="0" step="0.01">
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="button" class="btn btn-primary" onclick="saveCustomer()">Save Customer</button>
                </div>
            </div>
        </div>
    </div>

    <script src="assets/js/core/libs.min.js"></script>
    <script src="assets/js/core/external.min.js"></script>
    <script src="assets/js/hope-ui.js" defer></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/sweetalert2/11.10.5/sweetalert2.all.min.js"></script>

    <script>
        const customerModal = new bootstrap.Modal(document.getElementById('customerModal'));

        function exportCustomers() {
            Swal.fire({
                title: 'Exporting...',
                text: 'Preparing your customers report',
                allowOutsideClick: false,
                allowEscapeKey: false,
                showConfirmButton: false,
                didOpen: () => {
                    Swal.showLoading();
                }
            });

            const urlParams = new URLSearchParams(window.location.search);
            window.location.href = 'export_customers.php?' + urlParams.toString();

            setTimeout(() => {
                Swal.close();
                Swal.fire({
                    icon: 'success',
                    title: 'Success!',
                    text: 'Customers report exported successfully',
                    timer: 2000,
                    showConfirmButton: false
                });
            }, 1000);
        }

        function showAddCustomerModal() {
            document.getElementById('customerModalTitle').textContent = 'Add New Customer';
            document.getElementById('customerForm').reset();
            document.getElementById('customerId').value = '';
            customerModal.show();
        }

        function editCustomer(customerId) {
            fetch('api/get_customer.php?id=' + customerId)
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        document.getElementById('customerModalTitle').textContent = 'Edit Customer';
                        document.getElementById('customerId').value = data.customer.customer_id;
                        document.getElementById('customerName').value = data.customer.name;
                        document.getElementById('contactNo').value = data.customer.contact_no;
                        document.getElementById('address').value = data.customer.address || '';
                        document.getElementById('creditLimit').value = data.customer.credit_limit;
                        customerModal.show();
                    }
                });
        }

        function saveCustomer() {
            const form = document.getElementById('customerForm');
            const formData = new FormData(form);

            fetch('api/save_customer.php', {
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

        function deleteCustomer(customerId) {
            Swal.fire({
                title: 'Delete Customer?',
                text: 'This action cannot be undone!',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#d33',
                confirmButtonText: 'Yes, delete!'
            }).then((result) => {
                if (result.isConfirmed) {
                    fetch('api/delete_customer.php', {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json'
                            },
                            body: JSON.stringify({
                                customer_id: customerId
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