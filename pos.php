<?php
require_once 'auth.php';
Auth::requireAuth();

$conn = getDBConnection();

// Get all customers for dropdown
$customers = $conn->query("SELECT customer_id, name, contact_no FROM customers ORDER BY name");
?>
<!doctype html>
<html lang="en" dir="ltr" data-bs-theme="light">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <title>POS | Ayurvedic Pharmacy</title>
    
    <link rel="shortcut icon" href="assets/images/logo_white.png">
    <link rel="stylesheet" href="assets/css/core/libs.min.css">
    <link rel="stylesheet" href="assets/css/hope-ui.min.css?v=5.0.0">
    <link rel="stylesheet" href="assets/css/custom.min.css?v=5.0.0">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/sweetalert2/11.10.5/sweetalert2.min.css">
    
    <style>
        .product-search-box {
            position: relative;
        }
        .search-results {
            position: absolute;
            top: 100%;
            left: 0;
            right: 0;
            background: white;
            border: 1px solid #ddd;
            border-radius: 4px;
            max-height: 300px;
            overflow-y: auto;
            z-index: 1000;
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
        }
        .search-result-item {
            padding: 10px;
            cursor: pointer;
            border-bottom: 1px solid #f0f0f0;
        }
        .search-result-item:hover {
            background-color: #f8f9fa;
        }
        .cart-item {
            padding: 15px;
            border-bottom: 1px solid #e9ecef;
        }
        .quantity-control {
            display: flex;
            align-items: center;
            gap: 10px;
        }
        .quantity-control button {
            width: 30px;
            height: 30px;
            padding: 0;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .total-section {
            background: #f8f9fa;
            padding: 20px;
            border-radius: 8px;
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
                <div class="col-lg-8">
                    <div class="card">
                        <div class="card-header">
                            <h4 class="card-title">Add Products</h4>
                        </div>
                        <div class="card-body">
                            <div class="product-search-box">
                                <input type="text" id="productSearch" class="form-control" 
                                       placeholder="Search products by name or generic name...">
                                <div id="searchResults" class="search-results" style="display: none;"></div>
                            </div>
                        </div>
                    </div>

                    <div class="card mt-3">
                        <div class="card-header d-flex justify-content-between">
                            <h4 class="card-title">Cart Items</h4>
                            <button class="btn btn-sm btn-danger" onclick="clearCart()">Clear Cart</button>
                        </div>
                        <div class="card-body">
                            <div id="cartItems">
                                <p class="text-center text-muted py-5">No items in cart</p>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-lg-4">
                    <div class="card">
                        <div class="card-header">
                            <h4 class="card-title">Customer Information</h4>
                        </div>
                        <div class="card-body">
                            <div class="mb-3">
                                <label class="form-label">Customer</label>
                                <select id="customerId" class="form-select">
                                    <option value="">Walk-in Customer</option>
                                    <?php while ($customer = $customers->fetch_assoc()): ?>
                                        <option value="<?php echo $customer['customer_id']; ?>">
                                            <?php echo htmlspecialchars($customer['name']); ?> 
                                            (<?php echo htmlspecialchars($customer['contact_no']); ?>)
                                        </option>
                                    <?php endwhile; ?>
                                </select>
                            </div>
                            <button class="btn btn-sm btn-outline-primary w-100" onclick="window.location.href='customers.php?action=add'">
                                + Add New Customer
                            </button>
                        </div>
                    </div>

                    <div class="card mt-3">
                        <div class="card-header">
                            <h4 class="card-title">Payment Details</h4>
                        </div>
                        <div class="card-body">
                            <div class="total-section">
                                <div class="d-flex justify-content-between mb-2">
                                    <span>Subtotal:</span>
                                    <strong>Rs. <span id="subtotal">0.00</span></strong>
                                </div>
                                <div class="d-flex justify-content-between mb-3">
                                    <span>Discount:</span>
                                    <div class="input-group input-group-sm" style="width: 120px;">
                                        <input type="number" id="discount" class="form-control" value="0" min="0" step="0.01">
                                        <span class="input-group-text">Rs.</span>
                                    </div>
                                </div>
                                <hr>
                                <div class="d-flex justify-content-between mb-3">
                                    <h5>Total:</h5>
                                    <h5 class="text-success">Rs. <span id="total">0.00</span></h5>
                                </div>
                            </div>

                            <div class="mb-3 mt-3">
                                <label class="form-label">Payment Type</label>
                                <select id="paymentType" class="form-select">
                                    <option value="cash">Cash</option>
                                    <option value="credit">Credit</option>
                                </select>
                            </div>

                            <button class="btn btn-primary w-100 mb-2" onclick="processPayment()">
                                Complete Sale
                            </button>
                            <button class="btn btn-outline-secondary w-100" onclick="clearCart()">
                                Cancel
                            </button>
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
        let cart = [];
        let searchTimeout;

        // Product search
        document.getElementById('productSearch').addEventListener('input', function() {
            clearTimeout(searchTimeout);
            const query = this.value.trim();
            
            if (query.length < 2) {
                document.getElementById('searchResults').style.display = 'none';
                return;
            }

            searchTimeout = setTimeout(() => {
                searchProducts(query);
            }, 300);
        });

        function searchProducts(query) {
            fetch('api/search_products.php?q=' + encodeURIComponent(query))
                .then(response => response.json())
                .then(data => {
                    displaySearchResults(data);
                })
                .catch(error => console.error('Error:', error));
        }

        function displaySearchResults(products) {
            const resultsDiv = document.getElementById('searchResults');
            
            if (products.length === 0) {
                resultsDiv.innerHTML = '<div class="p-3 text-muted">No products found</div>';
                resultsDiv.style.display = 'block';
                return;
            }

            let html = '';
            products.forEach(product => {
                html += `
                    <div class="search-result-item" onclick="addToCart(${JSON.stringify(product).replace(/"/g, '&quot;')})">
                        <strong>${product.product_name}</strong>
                        ${product.generic_name ? `<br><small class="text-muted">${product.generic_name}</small>` : ''}
                        <br><small>Batch: ${product.batch_no} | Stock: ${product.quantity_in_stock} | Rs. ${parseFloat(product.selling_price).toFixed(2)}</small>
                    </div>
                `;
            });

            resultsDiv.innerHTML = html;
            resultsDiv.style.display = 'block';
        }

        function addToCart(product) {
            // Check if product already in cart
            const existingItem = cart.find(item => item.batch_id === product.batch_id);
            
            if (existingItem) {
                if (existingItem.quantity < product.quantity_in_stock) {
                    existingItem.quantity++;
                } else {
                    Swal.fire('Error', 'Not enough stock available', 'error');
                    return;
                }
            } else {
                cart.push({
                    batch_id: product.batch_id,
                    product_id: product.product_id,
                    product_name: product.product_name,
                    batch_no: product.batch_no,
                    selling_price: parseFloat(product.selling_price),
                    quantity: 1,
                    max_stock: product.quantity_in_stock
                });
            }

            updateCartDisplay();
            document.getElementById('searchResults').style.display = 'none';
            document.getElementById('productSearch').value = '';
        }

        function updateCartDisplay() {
            const cartDiv = document.getElementById('cartItems');
            
            if (cart.length === 0) {
                cartDiv.innerHTML = '<p class="text-center text-muted py-5">No items in cart</p>';
                updateTotals();
                return;
            }

            let html = '';
            cart.forEach((item, index) => {
                const itemTotal = item.quantity * item.selling_price;
                html += `
                    <div class="cart-item">
                        <div class="d-flex justify-content-between align-items-start mb-2">
                            <div>
                                <strong>${item.product_name}</strong>
                                <br><small class="text-muted">Batch: ${item.batch_no}</small>
                                <br><small class="text-muted">Rs. ${item.selling_price.toFixed(2)} each</small>
                            </div>
                            <button class="btn btn-sm btn-danger" onclick="removeFromCart(${index})">
                                <i class="icon">×</i>
                            </button>
                        </div>
                        <div class="d-flex justify-content-between align-items-center">
                            <div class="quantity-control">
                                <button class="btn btn-sm btn-outline-primary" onclick="updateQuantity(${index}, -1)">-</button>
                                <input type="number" class="form-control form-control-sm text-center" 
                                       style="width: 60px;" value="${item.quantity}" 
                                       onchange="setQuantity(${index}, this.value)" min="1" max="${item.max_stock}">
                                <button class="btn btn-sm btn-outline-primary" onclick="updateQuantity(${index}, 1)">+</button>
                            </div>
                            <strong>Rs. ${itemTotal.toFixed(2)}</strong>
                        </div>
                    </div>
                `;
            });

            cartDiv.innerHTML = html;
            updateTotals();
        }

        function updateQuantity(index, change) {
            const item = cart[index];
            const newQuantity = item.quantity + change;

            if (newQuantity < 1 || newQuantity > item.max_stock) {
                if (newQuantity > item.max_stock) {
                    Swal.fire('Error', 'Not enough stock available', 'error');
                }
                return;
            }

            item.quantity = newQuantity;
            updateCartDisplay();
        }

        function setQuantity(index, value) {
            const quantity = parseInt(value);
            const item = cart[index];

            if (isNaN(quantity) || quantity < 1) {
                Swal.fire('Error', 'Invalid quantity', 'error');
                updateCartDisplay();
                return;
            }

            if (quantity > item.max_stock) {
                Swal.fire('Error', 'Not enough stock available', 'error');
                updateCartDisplay();
                return;
            }

            item.quantity = quantity;
            updateCartDisplay();
        }

        function removeFromCart(index) {
            cart.splice(index, 1);
            updateCartDisplay();
        }

        function clearCart() {
            if (cart.length === 0) return;
            
            Swal.fire({
                title: 'Clear Cart?',
                text: 'Are you sure you want to clear all items?',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#d33',
                cancelButtonColor: '#6c757d',
                confirmButtonText: 'Yes, clear it!'
            }).then((result) => {
                if (result.isConfirmed) {
                    cart = [];
                    updateCartDisplay();
                }
            });
        }

        function updateTotals() {
            const subtotal = cart.reduce((sum, item) => sum + (item.quantity * item.selling_price), 0);
            const discount = parseFloat(document.getElementById('discount').value) || 0;
            const total = subtotal - discount;

            document.getElementById('subtotal').textContent = subtotal.toFixed(2);
            document.getElementById('total').textContent = Math.max(0, total).toFixed(2);
        }

        // Update totals when discount changes
        document.getElementById('discount').addEventListener('input', updateTotals);

        function processPayment() {
            if (cart.length === 0) {
                Swal.fire('Error', 'Cart is empty', 'error');
                return;
            }

            const customerId = document.getElementById('customerId').value;
            const paymentType = document.getElementById('paymentType').value;
            const discount = parseFloat(document.getElementById('discount').value) || 0;
            const subtotal = cart.reduce((sum, item) => sum + (item.quantity * item.selling_price), 0);
            const total = subtotal - discount;

            Swal.fire({
                title: 'Complete Sale?',
                html: `
                    <div class="text-start">
                        <p><strong>Total Amount:</strong> Rs. ${total.toFixed(2)}</p>
                        <p><strong>Payment Type:</strong> ${paymentType.charAt(0).toUpperCase() + paymentType.slice(1)}</p>
                        <p><strong>Items:</strong> ${cart.length}</p>
                    </div>
                `,
                icon: 'question',
                showCancelButton: true,
                confirmButtonColor: '#28a745',
                cancelButtonColor: '#6c757d',
                confirmButtonText: 'Complete Sale'
            }).then((result) => {
                if (result.isConfirmed) {
                    completeSale(customerId, paymentType, discount, subtotal, total);
                }
            });
        }

        function completeSale(customerId, paymentType, discount, subtotal, total) {
            Swal.fire({
                title: 'Processing...',
                text: 'Please wait...',
                allowOutsideClick: false,
                allowEscapeKey: false,
                showConfirmButton: false,
                didOpen: () => {
                    Swal.showLoading();
                }
            });

            const data = {
                customer_id: customerId || null,
                payment_type: paymentType,
                discount: discount,
                total_amount: subtotal,
                net_amount: total,
                items: cart
            };

            fetch('api/process_sale.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify(data)
            })
            .then(response => response.json())
            .then(result => {
                if (result.success) {
                    Swal.fire({
                        title: 'Success!',
                        text: 'Sale completed successfully',
                        icon: 'success',
                        confirmButtonColor: '#28a745'
                    }).then(() => {
                        cart = [];
                        updateCartDisplay();
                        document.getElementById('discount').value = '0';
                        document.getElementById('customerId').value = '';
                        
                        // Ask if want to print receipt
                        Swal.fire({
                            title: 'Print Receipt?',
                            text: 'Would you like to print the receipt?',
                            icon: 'question',
                            showCancelButton: true,
                            confirmButtonText: 'Yes, print',
                            cancelButtonText: 'No'
                        }).then((printResult) => {
                            if (printResult.isConfirmed) {
                                window.open('print_receipt.php?sale_id=' + result.sale_id, '_blank');
                            }
                        });
                    });
                } else {
                    Swal.fire('Error', result.message || 'Failed to complete sale', 'error');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                Swal.fire('Error', 'An error occurred while processing the sale', 'error');
            });
        }

        // Close search results when clicking outside
        document.addEventListener('click', function(e) {
            if (!e.target.closest('.product-search-box')) {
                document.getElementById('searchResults').style.display = 'none';
            }
        });
    </script>
</body>
</html>