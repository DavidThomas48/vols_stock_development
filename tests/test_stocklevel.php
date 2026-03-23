<?php
// =============================================================================
// Test: Stock Level Report Query (Page 406)
// Tests getstockwithlevels() directly via mysqli.
// Run from CLI: /path/to/php.exe tests/test_stocklevel.php
// =============================================================================
$mysqli = new mysqli('localhost', 'root', '', 'vols');
if ($mysqli->connect_error) {
    die("Connection failed: " . $mysqli->connect_error . "\n");
}

$query  = "SELECT s.id, s.Name, s.Code, s.category_id, sc.Name as category_name,";
$query .= " COALESCE((";
$query .= "   SELECT sm1.qty FROM stock_movement sm1";
$query .= "   WHERE sm1.stock_id = s.id AND sm1.movement_type = 'stocktake_adjustment'";
$query .= "   ORDER BY sm1.id DESC LIMIT 1";
$query .= " ), 0)";
$query .= " + COALESCE((";
$query .= "   SELECT SUM(sm2.qty) FROM stock_movement sm2";
$query .= "   WHERE sm2.stock_id = s.id AND sm2.movement_type = 'delivery'";
$query .= "   AND sm2.id > COALESCE((";
$query .= "     SELECT MAX(sm3.id) FROM stock_movement sm3";
$query .= "     WHERE sm3.stock_id = s.id AND sm3.movement_type = 'stocktake_adjustment'";
$query .= "   ), 0)";
$query .= " ), 0)";
$query .= " - COALESCE((";
$query .= "   SELECT SUM(sm4.qty) FROM stock_movement sm4";
$query .= "   WHERE sm4.stock_id = s.id AND sm4.movement_type = 'stockout'";
$query .= "   AND sm4.id > COALESCE((";
$query .= "     SELECT MAX(sm5.id) FROM stock_movement sm5";
$query .= "     WHERE sm5.stock_id = s.id AND sm5.movement_type = 'stocktake_adjustment'";
$query .= "   ), 0)";
$query .= " ), 0) as current_qty";
$query .= " FROM stock s";
$query .= " LEFT JOIN stock_category sc ON s.category_id = sc.id";
$query .= " ORDER BY sc.Name, s.Name";

$result = $mysqli->query($query);
if (!$result) {
    die("Query failed: " . $mysqli->error . "\n");
}

$rows = $result->fetch_all(MYSQLI_ASSOC);
echo "Rows returned: " . count($rows) . "\n\n";

$currentcat = null;
foreach ($rows as $row) {
    $cat = $row['category_name'] ?? 'Uncategorised';
    if ($cat !== $currentcat) {
        $currentcat = $cat;
        echo "--- {$cat} ---\n";
    }
    $qty = (float)$row['current_qty'];
    $flag = $qty <= 0 ? " *** ZERO/NEGATIVE ***" : "";
    printf("  %-30s %-10s  Qty: %6.1f%s\n", $row['Name'], "({$row['Code']})", $qty, $flag);
}

$mysqli->close();
