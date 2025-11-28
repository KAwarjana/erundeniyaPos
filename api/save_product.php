<?php
require_once '../config.php';
require_once '../auth.php';

Auth::requireAuth();
header('Content-Type: application/json');

$productId = $_POST['product_id'] ?? 0;
$productName = trim($_POST['product_name'] ?? '');
$genericName = trim($_POST['generic_name'] ?? '');
$unit = trim($_POST['unit'] ?? '');
$reorderLevel = intval($_POST['reorder_level'] ?? 10);
$status = $_POST['status'] ?? 'active';

// Check if initial stock should be added
$addInitialStock = ($_POST['add_initial_stock'] ?? '0') === '1';
$batchNo = trim($_POST['batch_no'] ?? '');
$expiryDate = $_POST['expiry_date'] ?? '';
$costPrice = isset($_POST['cost_price']) && $_POST['cost_price'] !== '' ? floatval($_POST['cost_price']) : null;
$sellingPrice = floatval($_POST['selling_price'] ?? 0);
$quantity = intval($_POST['quantity_in_stock'] ?? 0);

// Validation for product
if (empty($productName)) {
    echo json_encode(['success' => false, 'message' => 'Product name is required']);
    exit;
}

if ($reorderLevel < 0) {
    echo json_encode(['success' => false, 'message' => 'Reorder level cannot be negative']);
    exit;
}

// Validation for initial stock if checkbox is checked
if ($addInitialStock && $productId == 0) {
    if (empty($batchNo) || empty($expiryDate) || $sellingPrice <= 0 || $quantity < 0) {
        echo json_encode(['success' => false, 'message' => 'Batch number, expiry date, selling price and quantity are required when adding initial stock']);
        exit;
    }

    $today = date('Y-m-d');
    if ($expiryDate < $today) {
        echo json_encode(['success' => false, 'message' => 'Expiry date cannot be in the past']);
        exit;
    }

    // Only validate selling price vs cost price if cost price is provided
    if ($costPrice !== null && $sellingPrice <= $costPrice) {
        echo json_encode(['success' => false, 'message' => 'Selling price should be greater than cost price']);
        exit;
    }
}

$conn = getDBConnection();

try {
    $conn->begin_transaction();
    
    // Check if product name already exists
    if ($productId > 0) {
        $stmt = $conn->prepare("SELECT product_id FROM products WHERE product_name = ? AND product_id != ?");
        $stmt->bind_param("si", $productName, $productId);
    } else {
        $stmt = $conn->prepare("SELECT product_id FROM products WHERE product_name = ?");
        $stmt->bind_param("s", $productName);
    }
    
    $stmt->execute();
    if ($stmt->get_result()->num_rows > 0) {
        echo json_encode(['success' => false, 'message' => 'Product name already exists']);
        exit;
    }
    
    if ($productId > 0) {
        // Update existing product
        $stmt = $conn->prepare("UPDATE products SET product_name = ?, generic_name = ?, unit = ?, reorder_level = ?, status = ? WHERE product_id = ?");
        $stmt->bind_param("sssisi", $productName, $genericName, $unit, $reorderLevel, $status, $productId);
        
        if ($stmt->execute()) {
            $conn->commit();
            echo json_encode(['success' => true, 'message' => 'Product updated successfully']);
        } else {
            throw new Exception('Failed to update product');
        }
    } else {
        // Insert new product
        $stmt = $conn->prepare("INSERT INTO products (product_name, generic_name, unit, reorder_level, status) VALUES (?, ?, ?, ?, ?)");
        $stmt->bind_param("sssis", $productName, $genericName, $unit, $reorderLevel, $status);
        
        if (!$stmt->execute()) {
            throw new Exception('Failed to add product');
        }
        
        $newProductId = $conn->insert_id;
        $message = 'Product added successfully';
        
        // Add initial stock if requested
        if ($addInitialStock) {
            // Check if batch number already exists
            $stmt = $conn->prepare("SELECT batch_id FROM product_batches WHERE batch_no = ? AND product_id = ?");
            $stmt->bind_param("si", $batchNo, $newProductId);
            $stmt->execute();
            if ($stmt->get_result()->num_rows > 0) {
                throw new Exception('Batch number already exists for this product');
            }
            
            // Insert batch - cost_price can be NULL
            $stmt = $conn->prepare("INSERT INTO product_batches (product_id, batch_no, expiry_date, cost_price, selling_price, quantity_in_stock) VALUES (?, ?, ?, ?, ?, ?)");
            
            if ($costPrice !== null) {
                $stmt->bind_param("issddi", $newProductId, $batchNo, $expiryDate, $costPrice, $sellingPrice, $quantity);
            } else {
                // Use a temporary variable for NULL binding
                $nullValue = null;
                $stmt->bind_param("issddi", $newProductId, $batchNo, $expiryDate, $nullValue, $sellingPrice, $quantity);
            }
            
            if (!$stmt->execute()) {
                throw new Exception('Failed to add batch');
            }
            
            $newBatchId = $conn->insert_id;
            
            // Create purchase record - use cost_price as is (can be NULL)
            $userInfo = Auth::getUserInfo();
            $totalAmount = $costPrice !== null ? ($costPrice * $quantity) : 0.00;
            $invoiceNo = 'AUTO-' . date('YmdHis');
            
            $stmt = $conn->prepare("INSERT INTO purchases (supplier_id, user_id, invoice_no, total_amount) VALUES (NULL, ?, ?, ?)");
            $stmt->bind_param("isd", $userInfo['user_id'], $invoiceNo, $totalAmount);
            
            if (!$stmt->execute()) {
                throw new Exception('Failed to create purchase record');
            }
            
            $purchaseId = $conn->insert_id;
            
            // Create purchase item - cost_price can be NULL
            $stmt = $conn->prepare("INSERT INTO purchase_items (purchase_id, batch_id, quantity, cost_price, total_cost) VALUES (?, ?, ?, ?, ?)");
            $totalCost = $costPrice !== null ? ($costPrice * $quantity) : 0.00;
            
            if ($costPrice !== null) {
                $stmt->bind_param("iiidd", $purchaseId, $newBatchId, $quantity, $costPrice, $totalCost);
            } else {
                $nullValue = null;
                $stmt->bind_param("iiidd", $purchaseId, $newBatchId, $quantity, $nullValue, $totalCost);
            }
            
            if (!$stmt->execute()) {
                throw new Exception('Failed to create purchase item');
            }
            
            $message = 'Product and initial stock added successfully';
        }
        
        $conn->commit();
        echo json_encode([
            'success' => true, 
            'message' => $message, 
            'product_id' => $newProductId
        ]);
    }
    
} catch (Exception $e) {
    $conn->rollback();
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?>