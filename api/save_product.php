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

// Validation
if (empty($productName)) {
    echo json_encode(['success' => false, 'message' => 'Product name is required']);
    exit;
}

if ($reorderLevel < 0) {
    echo json_encode(['success' => false, 'message' => 'Reorder level cannot be negative']);
    exit;
}

$conn = getDBConnection();

try {
    // Check if product name already exists for different product
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
        $stmt = $conn->prepare("UPDATE products SET product_name = ?, generic_name = ?, unit = ?, reorder_level = ? WHERE product_id = ?");
        $stmt->bind_param("sssii", $productName, $genericName, $unit, $reorderLevel, $productId);
        
        if ($stmt->execute()) {
            echo json_encode(['success' => true, 'message' => 'Product updated successfully']);
        } else {
            throw new Exception('Failed to update product');
        }
    } else {
        // Insert new product
        $stmt = $conn->prepare("INSERT INTO products (product_name, generic_name, unit, reorder_level) VALUES (?, ?, ?, ?)");
        $stmt->bind_param("sssi", $productName, $genericName, $unit, $reorderLevel);
        
        if ($stmt->execute()) {
            $newProductId = $conn->insert_id;
            echo json_encode(['success' => true, 'message' => 'Product added successfully', 'product_id' => $newProductId]);
        } else {
            throw new Exception('Failed to add product');
        }
    }
    
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?>