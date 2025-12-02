<?php
require_once 'auth.php';
Auth::requireRole([1, 2, 3]); // Admin, Manager, and Cashier can access POS

$conn = getDBConnection();
$userInfo = Auth::getUserInfo();

// Get all customers for dropdown
$customers = $conn->query("SELECT customer_id, name, contact_no FROM customers ORDER BY name");
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
        /* Mobile-first responsive styles - similar to sales_history.php */
        .pos-container {
            margin-top: 0;
        }
        
        @media (max-width: 992px) {
            .pos-container {
                margin-top: 0;
            }
        }
        
        .billing-card {
            background: #f8f9fa;
            border-radius: 10px;
            padding: 20px;
            margin-bottom: 20px;
        }
        
        @media (max-width: 576px) {
            .billing-card {
                padding: 15px;
                margin-bottom: 15px;
            }
        }
        
        .billing-input {
            width: 100%;
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 5px;
            margin-bottom: 10px;
        }
        
        .items-section {
            background: white;
            border-radius: 10px;
            padding: 20px;
            min-height: 400px;
        }
        
        @media (max-width: 576px) {
            .items-section {
                padding: 15px;
                min-height: 300px;
            }
        }
        
        .search-box {
            position: relative;
            margin-bottom: 20px;
        }
        
        .search-results {
            position: absolute;
            top: 100%;
            left: 0;
            right: 0;
            background: white;
            border: 1px solid #ddd;
            border-radius: 5px;
            max-height: 300px;
            overflow-y: auto;
            z-index: 1000;
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
            display: none;
        }
        
        .search-result-item {
            padding: 12px;
            cursor: pointer;
            border-bottom: 1px solid #f0f0f0;
            transition: background 0.2s;
        }
        
        .search-result-item:hover {
            background-color: #f8f9fa;
        }
        
        .invoice-preview {
            background: white;
            border-radius: 10px;
            padding: 25px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        
        /* @media (max-width: 992px) {
            .invoice-preview {
                margin-top: 20px;
            }
        } */
        
        @media (max-width: 576px) {
            .invoice-preview {
                padding: 15px;
            }
        }
        
        .invoice-header {
            text-align: center;
            margin-bottom: 20px;
            padding-bottom: 15px;
            border-bottom: 2px solid #000;
        }
        
        .invoice-header h4 {
            margin: 0;
            font-size: 20px;
            font-weight: bold;
        }
        
        .invoice-details {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 15px;
            margin-bottom: 20px;
            font-size: 13px;
        }
        
        @media (max-width: 576px) {
            .invoice-details {
                grid-template-columns: 1fr;
                gap: 10px;
            }
        }
        
        .invoice-detail-group label {
            display: block;
            font-weight: 600;
            margin-bottom: 5px;
            color: #666;
        }
        
        .invoice-totals {
            margin-top: 15px;
            padding-top: 15px;
            border-top: 2px solid #000;
        }
        
        .total-row {
            display: flex;
            justify-content: space-between;
            padding: 5px 0;
            font-size: 14px;
        }
        
        .total-row.grand-total {
            font-size: 18px;
            font-weight: bold;
            color: #28a745;
            padding-top: 10px;
            border-top: 2px dashed #000;
            margin-top: 10px;
        }
        
        .payment-section {
            margin-top: 20px;
            padding-top: 20px;
            border-top: 2px solid #f0f0f0;
        }
        
        .btn-action {
            width: 100%;
            padding: 12px;
            margin-bottom: 10px;
            border: none;
            border-radius: 5px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s;
        }
        
        .btn-save {
            background: #28a745;
            color: white;
        }
        
        .btn-cancel {
            background: #6c757d;
            color: white;
        }
        
        /* Responsive table improvements */
        .table-responsive {
            overflow-x: auto;
            -webkit-overflow-scrolling: touch;
        }
        
        @media (max-width: 768px) {
            .table-responsive table {
                font-size: 12px;
            }
        }
        
        @media (max-width: 576px) {
            .table-responsive table {
                font-size: 11px;
            }
        }
        
        /* Mobile-specific adjustments */
        .mb-mobile-2 {
            margin-bottom: 0.5rem !important;
        }
        
        @media (min-width: 768px) {
            .mb-mobile-2 {
                margin-bottom: 0 !important;
            }
        }
        
        /* Responsive grid adjustments */
        .col-stack-mobile {
            width: 100%;
        }
        
        @media (min-width: 768px) {
            .col-stack-mobile {
                width: auto;
            }
        }
        
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
            <div class="pos-container">
                <div class="row">
                    <!-- Left Side - Billing & Items -->
                    <div class="col-12 col-lg-8">
                        <!-- Billing Details -->
                        <div class="billing-card">
                            <h5 class="mb-3">Billing Details</h5>
                            <div class="row">
                                <div class="col-12 col-md-6 mb-mobile-2">
                                    <label><strong>Billing From</strong></label>
                                    <input type="text" class="billing-input form-control" id="billingFrom" value="<?php echo htmlspecialchars($userInfo['full_name']); ?>" readonly>
                                </div>
                                <div class="col-12 col-md-6 mb-mobile-2">
                                    <label><strong>Customer Type</strong></label>
                                    <select class="billing-input form-select" id="customerType" onchange="toggleCustomerInput()">
                                        <option value="walkin">Walk-in Customer</option>
                                        <option value="existing">Existing Customer</option>
                                    </select>
                                </div>
                            </div>
                            <div class="row mt-2">
                                <div class="col-12 col-md-6 mb-mobile-2">
                                    <label><strong>Customer Name</strong></label>
                                    <input type="text" class="billing-input form-control" id="customerName" 
                                           placeholder="Enter customer name (optional)" 
                                           style="display: block;">
                                    
                                    <select class="billing-input form-select" id="customerId" style="display: none;" onchange="loadCustomerDetails()">
                                        <option value="">Select Customer</option>
                                        <?php 
                                        $customers->data_seek(0);
                                        while ($customer = $customers->fetch_assoc()): 
                                        ?>
                                            <option value="<?php echo $customer['customer_id']; ?>" 
                                                    data-contact="<?php echo htmlspecialchars($customer['contact_no']); ?>">
                                                <?php echo htmlspecialchars($customer['name']); ?>
                                            </option>
                                        <?php endwhile; ?>
                                    </select>
                                </div>
                                <div class="col-12 col-md-6 mb-mobile-2">
                                    <label><strong>Mobile Number</strong></label>
                                    <input type="text" class="billing-input form-control" id="customerMobile" 
                                           placeholder="Enter mobile number (optional)">
                                </div>
                            </div>
                        </div>

                        <!-- Items Section -->
                        <div class="items-section">
                            <h5 class="mb-3">Items</h5>
                            
                            <!-- Search Box -->
                            <div class="search-box">
                                <input type="text" class="billing-input form-control" id="productSearch" 
                                       placeholder="Search by name, ID, or barcode...">
                                <div id="searchResults" class="search-results"></div>
                            </div>

                            <!-- Items Table -->
                            <div class="table-responsive">
                                <table class="table table-sm">
                                    <thead>
                                        <tr>
                                            <th style="width: 5%;">#</th>
                                            <th style="width: 35%;">ITEM NAME</th>
                                            <th style="width: 15%;">UNIT PRICE</th>
                                            <th style="width: 15%;">QUANTITY</th>
                                            <th style="width: 10%;">UNIT</th>
                                            <th style="width: 15%;">NET AMOUNT</th>
                                            <th style="width: 5%;"></th>
                                        </tr>
                                    </thead>
                                    <tbody id="cartItems">
                                        <tr>
                                            <td colspan="7" class="text-center text-muted py-4">
                                                No items added yet. Search and add products above.
                                            </td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>

                    <!-- Right Side - Invoice Preview -->
                    <div class="col-12 col-lg-4">
                        <div class="invoice-preview">
                            <div class="invoice-header">
                                <h4>E. W. D. එරුන්දෙනිය හෙළ ඔසුසල</h4>
                                <p style="margin: 5px 0; font-size: 12px;">A/55 වෙදගෙදර, එරුන්දෙනිය, ආමිතිරිගල, උතුර.</p>
                                <p style="margin: 0; font-size: 12px;">Tel: +94 77 936 6908</p>
                            </div>

                            <div class="invoice-details">
                                <div>
                                    <div class="invoice-detail-group">
                                        <label>Invoice date</label>
                                        <div id="invoiceDate"><?php echo date('M jS, Y'); ?></div>
                                    </div>
                                    <div class="invoice-detail-group mt-2">
                                        <label>Invoice number</label>
                                        <div id="invoiceNumber">00000000</div>
                                    </div>
                                </div>
                                <div>
                                    <div class="invoice-detail-group">
                                        <label>User</label>
                                        <div id="invoiceUser"><?php echo htmlspecialchars($userInfo['full_name']); ?></div>
                                    </div>
                                    <div class="invoice-detail-group mt-2">
                                        <label>Time</label>
                                        <div id="invoiceTime"><?php echo date('h:i A'); ?></div>
                                    </div>
                                </div>
                            </div>

                            <!-- Invoice Items -->
                            <div class="invoice-items">
                                <table class="table table-sm">
                                    <thead>
                                        <tr>
                                            <th>Item</th>
                                            <th>Qty</th>
                                            <th>Unit</th>
                                            <th>Price</th>
                                            <th>Amount</th>
                                        </tr>
                                    </thead>
                                    <tbody id="invoiceItemsList">
                                        <tr>
                                            <td colspan="5" class="text-center text-muted">No items</td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>

                            <!-- Totals -->
                            <div class="invoice-totals">
                                <div class="total-row">
                                    <span>Net total</span>
                                    <span id="netTotal">0.00</span>
                                </div>
                                <div class="total-row">
                                    <span>Discount</span>
                                    <span>
                                        <input type="number" id="discount" class="form-control form-control-sm d-inline-block" value="0" min="0" step="0.01" 
                                               style="width: 80px; padding: 4px; border: 1px solid #ddd; border-radius: 4px; text-align: right;"
                                               onchange="updateTotals()">
                                    </span>
                                </div>
                                <div class="total-row grand-total">
                                    <span>TOTAL</span>
                                    <span id="grandTotal">0.00</span>
                                </div>
                            </div>

                            <!-- Payment Section -->
                            <div class="payment-section">
                                <label><strong>Payment Method</strong></label>
                                <select class="billing-input form-select mb-3" id="paymentMethod">
                                    <option value="cash">Cash</option>
                                    <option value="credit">Credit</option>
                                </select>

                                <div class="mb-3" id="cashPaymentSection">
                                    <label><strong>Paid</strong></label>
                                    <input type="number" class="billing-input form-control mb-2" id="paidAmount" value="0" min="0" step="0.01" onchange="calculateChange()">
                                    
                                    <label><strong>Change</strong></label>
                                    <input type="text" class="billing-input form-control" id="changeAmount" value="0.00" readonly>
                                </div>

                                <button class="btn-action btn-save btn btn-success mb-2" onclick="saveInvoice()">Save Invoice</button>
                                <button class="btn-action btn-cancel btn btn-secondary" onclick="clearAll()">Cancel</button>
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
        let cart = [];
        let searchTimeout;
        let invoiceCounter = 1;

        // Update invoice number
        document.getElementById('invoiceNumber').textContent = String(invoiceCounter).padStart(8, '0');

        // Update time every second
        setInterval(() => {
            document.getElementById('invoiceTime').textContent = new Date().toLocaleTimeString('en-US', { 
                hour: '2-digit', 
                minute: '2-digit',
                hour12: true 
            });
        }, 1000);

        // Toggle between walk-in and existing customer
        function toggleCustomerInput() {
            const customerType = document.getElementById('customerType').value;
            const customerNameInput = document.getElementById('customerName');
            const customerSelect = document.getElementById('customerId');
            const customerMobile = document.getElementById('customerMobile');
            
            if (customerType === 'walkin') {
                customerNameInput.style.display = 'block';
                customerSelect.style.display = 'none';
                customerSelect.value = '';
                customerMobile.value = '';
                customerMobile.readOnly = false;
            } else {
                customerNameInput.style.display = 'none';
                customerSelect.style.display = 'block';
                customerNameInput.value = '';
                customerMobile.value = '';
                customerMobile.readOnly = false;
            }
        }
        
        // Load customer details when selected from dropdown
        function loadCustomerDetails() {
            const select = document.getElementById('customerId');
            const selectedOption = select.options[select.selectedIndex];
            const contactNo = selectedOption.getAttribute('data-contact');
            
            if (contactNo) {
                document.getElementById('customerMobile').value = contactNo;
            } else {
                document.getElementById('customerMobile').value = '';
            }
        }

        // Product search
        document.getElementById('productSearch').addEventListener('input', function() {
            clearTimeout(searchTimeout);
            const query = this.value.trim();
            
            if (query.length < 1) {
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
                resultsDiv.innerHTML = '<div class="search-result-item text-muted">No products found</div>';
                resultsDiv.style.display = 'block';
                return;
            }

            let html = '';
            products.forEach(product => {
                html += `
                    <div class="search-result-item" onclick='addToCart(${JSON.stringify(product).replace(/'/g, "&#39;")})'>
                        <strong>${product.product_name}</strong> <span class="badge bg-primary">ID: ${product.product_id}</span>
                        ${product.generic_name ? `<br><small class="text-muted">${product.generic_name}</small>` : ''}
                        <br><small>Stock: ${product.quantity_in_stock} | Rs. ${parseFloat(product.selling_price).toFixed(2)}/kg</small>
                    </div>
                `;
            });

            resultsDiv.innerHTML = html;
            resultsDiv.style.display = 'block';
        }

        function addToCart(product) {
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
                    price_per_kg: parseFloat(product.selling_price),
                    quantity: 1,
                    unit: 'kg',
                    max_stock: product.quantity_in_stock
                });
            }

            updateCartDisplay();
            document.getElementById('searchResults').style.display = 'none';
            document.getElementById('productSearch').value = '';
        }
        
        // Calculate actual price based on unit
        function calculatePrice(item) {
            let pricePerUnit = item.price_per_kg;
            
            switch(item.unit) {
                case 'g':
                    pricePerUnit = item.price_per_kg / 1000;
                    break;
                case 'kg':
                    pricePerUnit = item.price_per_kg;
                    break;
                case 'ml':
                    pricePerUnit = item.price_per_kg / 1000;
                    break;
                case 'bottle':
                    pricePerUnit = item.price_per_kg;
                    break;
            }
            
            return pricePerUnit * item.quantity;
        }
        
        // Get display price per unit
        function getUnitPrice(item) {
            switch(item.unit) {
                case 'g':
                    return item.price_per_kg / 1000;
                case 'kg':
                    return item.price_per_kg;
                case 'ml':
                    return item.price_per_kg / 1000;
                case 'bottle':
                    return item.price_per_kg;
                default:
                    return item.price_per_kg;
            }
        }

        function updateCartDisplay() {
            const cartDiv = document.getElementById('cartItems');
            const invoiceItemsDiv = document.getElementById('invoiceItemsList');
            
            if (cart.length === 0) {
                cartDiv.innerHTML = '<tr><td colspan="7" class="text-center text-muted py-4">No items added yet.</td></tr>';
                invoiceItemsDiv.innerHTML = '<tr><td colspan="5" class="text-center text-muted">No items</td></tr>';
                updateTotals();
                return;
            }

            let cartHtml = '';
            let invoiceHtml = '';
            
            cart.forEach((item, index) => {
                const unitPrice = getUnitPrice(item);
                const itemTotal = calculatePrice(item);
                
                let qtyPlaceholder = '';
                switch(item.unit) {
                    case 'g': qtyPlaceholder = 'e.g., 250 (250g)'; break;
                    case 'kg': qtyPlaceholder = 'e.g., 1.5 (1.5kg)'; break;
                    case 'ml': qtyPlaceholder = 'e.g., 500 (500ml)'; break;
                    case 'bottle': qtyPlaceholder = 'e.g., 2 (2 bottles)'; break;
                }
                
                cartHtml += `
                    <tr>
                        <td>${index + 1}</td>
                        <td>
                            <strong>${item.product_name}</strong>
                            <br><small class="text-muted">Rs. ${item.price_per_kg.toFixed(2)}/kg</small>
                        </td>
                        <td>Rs. ${unitPrice.toFixed(4)}</td>
                        <td>
                            <input type="number" class="item-input form-control form-control-sm" value="${item.quantity}" 
                                   min="0.001" step="any"
                                   placeholder="${qtyPlaceholder}"
                                   onchange="updateQuantity(${index}, this.value)">
                        </td>
                        <td>
                            <select class="unit-select form-select form-select-sm" onchange="updateUnit(${index}, this.value)">
                                <option value="g" ${item.unit === 'g' ? 'selected' : ''}>g</option>
                                <option value="kg" ${item.unit === 'kg' ? 'selected' : ''}>kg</option>
                                <option value="ml" ${item.unit === 'ml' ? 'selected' : ''}>ml</option>
                                <option value="bottle" ${item.unit === 'bottle' ? 'selected' : ''}>bottle</option>
                            </select>
                        </td>
                        <td>Rs. ${itemTotal.toFixed(2)}</td>
                        <td>
                            <button class="remove-btn btn btn-sm btn-danger" onclick="removeFromCart(${index})">✕</button>
                        </td>
                    </tr>
                `;
                
                invoiceHtml += `
                    <tr>
                        <td>${item.product_name}</td>
                        <td>${item.quantity}</td>
                        <td>${item.unit}</td>
                        <td>${unitPrice.toFixed(2)}</td>
                        <td>${itemTotal.toFixed(2)}</td>
                    </tr>
                `;
            });

            cartDiv.innerHTML = cartHtml;
            invoiceItemsDiv.innerHTML = invoiceHtml;
            updateTotals();
        }

        function updateQuantity(index, value) {
            const quantity = parseFloat(value);
            const item = cart[index];

            if (isNaN(quantity) || quantity <= 0) {
                Swal.fire('Error', 'Invalid quantity', 'error');
                updateCartDisplay();
                return;
            }

            let stockInSelectedUnit = item.max_stock;
            if (item.unit === 'g' || item.unit === 'ml') {
                stockInSelectedUnit = item.max_stock * 1000;
            }

            if (quantity > stockInSelectedUnit) {
                Swal.fire('Error', `Not enough stock. Available: ${stockInSelectedUnit} ${item.unit}`, 'error');
                updateCartDisplay();
                return;
            }

            item.quantity = quantity;
            updateCartDisplay();
        }

        function updateUnit(index, unit) {
            const item = cart[index];
            const oldUnit = item.unit;
            
            if (oldUnit !== unit) {
                if (oldUnit === 'kg' && unit === 'g') {
                    item.quantity = item.quantity * 1000;
                } else if (oldUnit === 'g' && unit === 'kg') {
                    item.quantity = item.quantity / 1000;
                } else if (oldUnit === 'kg' && unit === 'ml') {
                    item.quantity = item.quantity * 1000;
                } else if (oldUnit === 'ml' && unit === 'kg') {
                    item.quantity = item.quantity / 1000;
                } else if (oldUnit === 'g' && unit === 'ml') {
                    // Keep same value
                } else if (oldUnit === 'ml' && unit === 'g') {
                    // Keep same value
                }
            }
            
            item.unit = unit;
            updateCartDisplay();
        }

        function removeFromCart(index) {
            cart.splice(index, 1);
            updateCartDisplay();
        }

        function updateTotals() {
            const subtotal = cart.reduce((sum, item) => sum + calculatePrice(item), 0);
            const discount = parseFloat(document.getElementById('discount').value) || 0;
            const total = subtotal - discount;

            document.getElementById('netTotal').textContent = subtotal.toFixed(2);
            document.getElementById('grandTotal').textContent = Math.max(0, total).toFixed(2);
            
            calculateChange();
        }

        function calculateChange() {
            const total = parseFloat(document.getElementById('grandTotal').textContent);
            const paid = parseFloat(document.getElementById('paidAmount').value) || 0;
            const change = paid - total;
            
            document.getElementById('changeAmount').value = Math.max(0, change).toFixed(2);
        }

        // Payment method change
        document.getElementById('paymentMethod').addEventListener('change', function() {
            const cashSection = document.getElementById('cashPaymentSection');
            if (this.value === 'cash') {
                cashSection.style.display = 'block';
            } else {
                cashSection.style.display = 'none';
            }
        });

        function saveInvoice() {
            if (cart.length === 0) {
                Swal.fire('Error', 'Cart is empty', 'error');
                return;
            }

            const customerType = document.getElementById('customerType').value;
            let customerId = null;
            let customerName = 'Walk-in Customer';
            let customerMobile = document.getElementById('customerMobile').value.trim();
            
            if (customerType === 'walkin') {
                customerName = document.getElementById('customerName').value.trim() || 'Walk-in Customer';
            } else {
                customerId = document.getElementById('customerId').value;
                if (customerId) {
                    const select = document.getElementById('customerId');
                    customerName = select.options[select.selectedIndex].text;
                }
            }

            const paymentType = document.getElementById('paymentMethod').value;
            const discount = parseFloat(document.getElementById('discount').value) || 0;
            const subtotal = cart.reduce((sum, item) => sum + calculatePrice(item), 0);
            const total = subtotal - discount;

            Swal.fire({
                title: 'Save Invoice?',
                html: `
                    <div class="text-start">
                        <p><strong>Customer:</strong> ${customerName}</p>
                        ${customerMobile ? `<p><strong>Mobile:</strong> ${customerMobile}</p>` : ''}
                        <p><strong>Total Amount:</strong> Rs. ${total.toFixed(2)}</p>
                        <p><strong>Payment:</strong> ${paymentType.toUpperCase()}</p>
                        <p><strong>Items:</strong> ${cart.length}</p>
                    </div>
                `,
                icon: 'question',
                showCancelButton: true,
                confirmButtonColor: '#28a745',
                cancelButtonColor: '#6c757d',
                confirmButtonText: 'Save & Print'
            }).then((result) => {
                if (result.isConfirmed) {
                    processSale(customerId, customerName, customerMobile, paymentType, discount, subtotal, total);
                }
            });
        }

        function processSale(customerId, customerName, customerMobile, paymentType, discount, subtotal, total) {
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

            const paidAmount = parseFloat(document.getElementById('paidAmount').value) || 0;
            const changeAmount = parseFloat(document.getElementById('changeAmount').value) || 0;

            const data = {
                customer_id: customerId || null,
                customer_name: customerName,
                customer_mobile: customerMobile,
                payment_type: paymentType,
                discount: discount,
                total_amount: subtotal,
                net_amount: total,
                paid_amount: paidAmount,
                change_amount: changeAmount,
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
                        text: 'Invoice saved successfully',
                        icon: 'success',
                        confirmButtonColor: '#28a745'
                    }).then(() => {
                        const printUrl = 'print_receipt.php?sale_id=' + result.sale_id + 
                                       '&paid=' + paidAmount + 
                                       '&change=' + changeAmount;
                        window.open(printUrl, '_blank');
                        
                        clearAll();
                        invoiceCounter++;
                        document.getElementById('invoiceNumber').textContent = String(invoiceCounter).padStart(8, '0');
                    });
                } else {
                    Swal.fire('Error', result.message || 'Failed to save invoice', 'error');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                Swal.fire('Error', 'An error occurred while processing the sale', 'error');
            });
        }

        function clearAll() {
            if (cart.length > 0) {
                Swal.fire({
                    title: 'Clear All?',
                    text: 'Are you sure you want to clear all items?',
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonColor: '#d33',
                    confirmButtonText: 'Yes, clear!'
                }).then((result) => {
                    if (result.isConfirmed) {
                        cart = [];
                        document.getElementById('discount').value = '0';
                        document.getElementById('paidAmount').value = '0';
                        document.getElementById('customerType').value = 'walkin';
                        document.getElementById('customerName').value = '';
                        document.getElementById('customerMobile').value = '';
                        document.getElementById('customerId').value = '';
                        toggleCustomerInput();
                        updateCartDisplay();
                    }
                });
            } else {
                cart = [];
                document.getElementById('discount').value = '0';
                document.getElementById('paidAmount').value = '0';
                document.getElementById('customerType').value = 'walkin';
                document.getElementById('customerName').value = '';
                document.getElementById('customerMobile').value = '';
                document.getElementById('customerId').value = '';
                toggleCustomerInput();
                updateCartDisplay();
            }
        }

        // Close search results when clicking outside
        document.addEventListener('click', function(e) {
            if (!e.target.closest('.search-box')) {
                document.getElementById('searchResults').style.display = 'none';
            }
        });
    </script>
</body>
</html>