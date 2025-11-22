<?php
require_once '../config.php';
require_once '../auth.php';

Auth::requireAuth();
header('Content-Type: application/json');

$data = json_decode(file_get_contents('php://input'), true);
$batchId = $data['batch_id'] ?? 0;

if ($batchId <= 0) {
    echo json_encode(['success' => false, 'message' => 'Invalid batch ID']);
    exit;
}

$conn = getDBConnection();

try {
    // Check if batch is used in any sales
    $stmt = $conn->prepare("SELECT COUNT(*) as count FROM sale_items WHERE batch_id = ?");
    $stmt->bind_param("i", $batchId);
    $stmt->execute();
    $result = $stmt->get_result()->fetch_assoc();
    
    if ($result['count'] > 0) {
        echo json_encode(['success' => false, 'message' => 'Cannot delete batch with existing sales records']);
        exit;
    }
    
    // Check if batch is used in any purchases
    $stmt = $conn->prepare("SELECT COUNT(*) as count FROM purchase_items WHERE batch_id = ?");
    $stmt->bind_param("i", $batchId);
    $stmt->execute();
    $result = $stmt->get_result()->fetch_assoc();
    
    if ($result['count'] > 0) {
        echo json_encode(['success' => false, 'message' => 'Cannot delete batch with existing purchase records']);
        exit;
    }
    
    // Check if batch has stock adjustments
    $stmt = $conn->prepare("SELECT COUNT(*) as count FROM stock_adjustments WHERE batch_id = ?");
    $stmt->bind_param("i", $batchId);
    $stmt->execute();
    $result = $stmt->get_result()->fetch_assoc();
    
    if ($result['count'] > 0) {
        // Delete stock adjustments first
        $stmt = $conn->prepare("DELETE FROM stock_adjustments WHERE batch_id = ?");
        $stmt->bind_param("i", $batchId);
        $stmt->execute();
    }
    
    // Delete the batch
    $stmt = $conn->prepare("DELETE FROM product_batches WHERE batch_id = ?");
    $stmt->bind_param("i", $batchId);
    
    if ($stmt->execute()) {
        echo json_encode(['success' => true, 'message' => 'Batch deleted successfully']);
    } else {
        throw new Exception('Failed to delete batch');
    }
    
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?>