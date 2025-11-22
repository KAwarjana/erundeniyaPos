<?php
require_once '../config.php';
require_once '../auth.php';

Auth::requireAuth();

header('Content-Type: application/json');

$data = json_decode(file_get_contents('php://input'), true);

if (!$data || !isset($data['items']) || empty($data['items'])) {
    echo json_encode(['success' => false, 'message' => 'No items in cart']);
    exit;
}

$conn = getDBConnection();
$userInfo = Auth::getUserInfo();

try {
    $conn->begin_transaction();
    
    $customerId = !empty($data['customer_id']) ? $data['customer_id'] : null;
    $paymentType = $data['payment_type'];
    $totalAmount = $data['total_amount'];
    $discount = $data['discount'];
    $netAmount = $data['net_amount'];
    $userId = $userInfo['user_id'];
    
    // Insert sale record
    $stmt = $conn->prepare("INSERT INTO sales (customer_id, user_id, payment_type, total_amount, discount, net_amount) VALUES (?, ?, ?, ?, ?, ?)");
    $stmt->bind_param("iisddd", $customerId, $userId, $paymentType, $totalAmount, $discount, $netAmount);
    
    if (!$stmt->execute()) {
        throw new Exception("Failed to create sale record");
    }
    
    $saleId = $conn->insert_id;
    
    // Insert sale items and update stock
    $stmtItem = $conn->prepare("INSERT INTO sale_items (sale_id, batch_id, quantity, unit_price, total_price) VALUES (?, ?, ?, ?, ?)");
    $stmtStock = $conn->prepare("UPDATE product_batches SET quantity_in_stock = quantity_in_stock - ? WHERE batch_id = ?");
    
    foreach ($data['items'] as $item) {
        $batchId = $item['batch_id'];
        $quantity = $item['quantity'];
        $unitPrice = $item['selling_price'];
        $totalPrice = $quantity * $unitPrice;
        
        // Check if enough stock
        $checkStock = $conn->prepare("SELECT quantity_in_stock FROM product_batches WHERE batch_id = ?");
        $checkStock->bind_param("i", $batchId);
        $checkStock->execute();
        $stockResult = $checkStock->get_result();
        $stockRow = $stockResult->fetch_assoc();
        
        if (!$stockRow || $stockRow['quantity_in_stock'] < $quantity) {
            throw new Exception("Insufficient stock for one or more items");
        }
        
        // Insert sale item
        $stmtItem->bind_param("iiidd", $saleId, $batchId, $quantity, $unitPrice, $totalPrice);
        if (!$stmtItem->execute()) {
            throw new Exception("Failed to add sale item");
        }
        
        // Update stock
        $stmtStock->bind_param("ii", $quantity, $batchId);
        if (!$stmtStock->execute()) {
            throw new Exception("Failed to update stock");
        }
    }
    
    $conn->commit();
    
    echo json_encode([
        'success' => true,
        'message' => 'Sale completed successfully',
        'sale_id' => $saleId
    ]);
    
} catch (Exception $e) {
    $conn->rollback();
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
?>