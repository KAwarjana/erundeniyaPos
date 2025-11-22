<?php
require_once '../config.php';
require_once '../auth.php';

Auth::requireAuth();

header('Content-Type: application/json');

$query = $_GET['q'] ?? '';

if (strlen($query) < 2) {
    echo json_encode([]);
    exit;
}

$conn = getDBConnection();

$searchQuery = "%" . $conn->real_escape_string($query) . "%";

$sql = "SELECT 
            p.product_id,
            p.product_name,
            p.generic_name,
            pb.batch_id,
            pb.batch_no,
            pb.expiry_date,
            pb.selling_price,
            pb.quantity_in_stock
        FROM products p
        INNER JOIN product_batches pb ON p.product_id = pb.product_id
        WHERE (p.product_name LIKE ? OR p.generic_name LIKE ?)
        AND pb.quantity_in_stock > 0
        AND pb.expiry_date > CURDATE()
        ORDER BY p.product_name, pb.expiry_date
        LIMIT 20";

$stmt = $conn->prepare($sql);
$stmt->bind_param("ss", $searchQuery, $searchQuery);
$stmt->execute();
$result = $stmt->get_result();

$products = [];
while ($row = $result->fetch_assoc()) {
    $products[] = $row;
}

echo json_encode($products);
?>