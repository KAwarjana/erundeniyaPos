<?php
header('Content-Type: application/json');

$servername = "localhost";
$username = "root";
$password = "Kawi@#$123";
$dbname = "elegant";

$conn = new mysqli($servername, $username, $password, $dbname);
if ($conn->connect_error) {
  http_response_code(500);
  echo json_encode(['error' => 'DB connection failed']);
  exit;
}
$conn->set_charset("utf8");

$action = isset($_GET['action']) ? $_GET['action'] : 'products';

if ($action === 'categories') {
  $sql = "SELECT id, name FROM categories ORDER BY name";
  $res = $conn->query($sql);
  $categories = [];
  while ($row = $res->fetch_assoc()) {
    $categories[] = $row;
  }
  echo json_encode($categories);
  $conn->close();
  exit;
}

if ($action === 'colors') {
  $sql = "SELECT id, hex_code FROM colors ORDER BY id";
  $res = $conn->query($sql);
  $colors = [];
  while ($row = $res->fetch_assoc()) {
    $colors[] = $row;
  }
  echo json_encode($colors);
  $conn->close();
  exit;
}

// Products filter params
$category_id = isset($_GET['category_id']) ? intval($_GET['category_id']) : 0;
$price_min = isset($_GET['price_min']) ? floatval($_GET['price_min']) : 0;
$price_max = isset($_GET['price_max']) ? floatval($_GET['price_max']) : 0;
$color_id = isset($_GET['color_id']) ? intval($_GET['color_id']) : 0;
$page = isset($_GET['page']) ? intval($_GET['page']) : 1;
$per_page = isset($_GET['per_page']) ? intval($_GET['per_page']) : 9;

$offset = ($page - 1) * $per_page;

$where = "1=1";
if ($category_id > 0) $where .= " AND category_id = $category_id";
if ($price_min > 0) $where .= " AND price >= $price_min";
if ($price_max > 0) $where .= " AND price <= $price_max";
if ($color_id > 0) $where .= " AND color_id = $color_id";

$totalSql = "SELECT COUNT(*) AS total FROM products WHERE $where";
$totalRes = $conn->query($totalSql);
$totalRow = $totalRes->fetch_assoc();
$total = intval($totalRow['total']);

$sql = "SELECT p.*, c.hex_code FROM products p LEFT JOIN colors c ON p.color_id = c.id WHERE $where ORDER BY p.id DESC LIMIT $offset, $per_page";
$res = $conn->query($sql);

$products = [];
while ($row = $res->fetch_assoc()) {
  $products[] = $row;
}

echo json_encode(['total' => $total, 'products' => $products]);
$conn->close();
