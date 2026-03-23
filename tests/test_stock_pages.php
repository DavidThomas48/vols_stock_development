<?php
// =============================================================================
// Test: Stock Pages Integration Test (Pages 401-406)
// Tests all DB operations for the stock tracking system.
// Run from CLI: /path/to/php.exe tests/test_stock_pages.php
//
// WARNING: This test clears the stock_movement, stock, and stock_category
// tables before running. Do not run against a database with live data.
// =============================================================================
$mysqli = new mysqli('localhost', 'root', '', 'vols');
if ($mysqli->connect_error) { die("DB connection failed: " . $mysqli->connect_error . "\n"); }

$pass = 0; $fail = 0;

function check($label, $result, $expected=true) {
    global $pass, $fail;
    if ($result === $expected) {
        echo "  PASS: {$label}\n";
        $pass++;
    } else {
        echo "  FAIL: {$label} (got " . var_export($result,true) . ", expected " . var_export($expected,true) . ")\n";
        $fail++;
    }
}

function q($mysqli, $sql) {
    $r = $mysqli->query($sql);
    if (!$r) { echo "  SQL ERROR: " . $mysqli->error . "\n  SQL: $sql\n"; return false; }
    return $r;
}

function scalar($mysqli, $sql) {
    $r = $mysqli->query($sql);
    if (!$r) return false;
    $row = $r->fetch_row();
    return $row ? $row[0] : null;
}

// ============================================================
echo "\n=== Clean slate ===\n";
q($mysqli, "DELETE FROM stock_movement");
q($mysqli, "DELETE FROM stock");
q($mysqli, "DELETE FROM stock_category");
echo "  Tables cleared.\n";

// ============================================================
echo "\n=== Page 401: Stock Categories ===\n";

q($mysqli, "INSERT INTO stock_category (Name) VALUES ('Tinned Goods')");
$cat1 = $mysqli->insert_id;
q($mysqli, "INSERT INTO stock_category (Name) VALUES ('Dry Goods')");
$cat2 = $mysqli->insert_id;
q($mysqli, "INSERT INTO stock_category (Name) VALUES ('Delete Me')");
$cat3 = $mysqli->insert_id;

check("Insert category 1", $cat1 > 0);
check("Insert category 2", $cat2 > 0);
check("Category count", (int)scalar($mysqli, "SELECT COUNT(*) FROM stock_category"), 3);

q($mysqli, "UPDATE stock_category SET Name='Tinned & Packaged' WHERE id={$cat1}");
check("Update category name", scalar($mysqli, "SELECT Name FROM stock_category WHERE id={$cat1}"), 'Tinned & Packaged');

q($mysqli, "DELETE FROM stock_category WHERE id={$cat3}");
check("Delete category", (int)scalar($mysqli, "SELECT COUNT(*) FROM stock_category"), 2);

// ============================================================
echo "\n=== Page 402: Stock Items ===\n";

q($mysqli, "INSERT INTO stock (Name, Code, category_id) VALUES ('Baked Beans', 'BB', {$cat1})");
$s1 = $mysqli->insert_id;
q($mysqli, "INSERT INTO stock (Name, Code, category_id) VALUES ('Tomato Soup', 'TS', {$cat1})");
$s2 = $mysqli->insert_id;
q($mysqli, "INSERT INTO stock (Name, Code, category_id) VALUES ('Pasta', 'PA', {$cat2})");
$s3 = $mysqli->insert_id;

check("Insert stock item 1", $s1 > 0);
check("Insert stock item 2", $s2 > 0);
check("Insert stock item 3", $s3 > 0);
check("Stock count", (int)scalar($mysqli, "SELECT COUNT(*) FROM stock"), 3);

q($mysqli, "UPDATE stock SET Code='BEAN' WHERE id={$s1}");
check("Update stock item code", scalar($mysqli, "SELECT Code FROM stock WHERE id={$s1}"), 'BEAN');

$row = $mysqli->query("SELECT s.Name, sc.Name as cat FROM stock s JOIN stock_category sc ON sc.id=s.category_id WHERE s.id={$s1}")->fetch_assoc();
check("Category join for stock item", $row['cat'], 'Tinned & Packaged');

// ============================================================
echo "\n=== Page 403: Stocktake ===\n";

$now = date('Y-m-d H:i:s');
q($mysqli, "INSERT INTO stock_movement (stock_id, movement_type, qty, unit, unit_qty, movement_date) VALUES ({$s1}, 'stocktake_adjustment', 24, 'can', 1, '{$now}')");
q($mysqli, "INSERT INTO stock_movement (stock_id, movement_type, qty, unit, unit_qty, movement_date) VALUES ({$s2}, 'stocktake_adjustment', 12, 'can', 1, '{$now}')");
q($mysqli, "INSERT INTO stock_movement (stock_id, movement_type, qty, unit, unit_qty, movement_date) VALUES ({$s3}, 'stocktake_adjustment', 10, 'kg',  1, '{$now}')");

check("Stocktake movements inserted", (int)scalar($mysqli, "SELECT COUNT(*) FROM stock_movement WHERE movement_type='stocktake_adjustment'"), 3);

$r = $mysqli->query("SELECT COALESCE((SELECT sm1.qty FROM stock_movement sm1 WHERE sm1.stock_id={$s1} AND sm1.movement_type='stocktake_adjustment' ORDER BY sm1.id DESC LIMIT 1),0) + COALESCE((SELECT SUM(sm2.qty) FROM stock_movement sm2 WHERE sm2.stock_id={$s1} AND sm2.movement_type='delivery' AND sm2.id > COALESCE((SELECT MAX(sm3.id) FROM stock_movement sm3 WHERE sm3.stock_id={$s1} AND sm3.movement_type='stocktake_adjustment'),0)),0) - COALESCE((SELECT SUM(sm4.qty) FROM stock_movement sm4 WHERE sm4.stock_id={$s1} AND sm4.movement_type='stockout' AND sm4.id > COALESCE((SELECT MAX(sm5.id) FROM stock_movement sm5 WHERE sm5.stock_id={$s1} AND sm5.movement_type='stocktake_adjustment'),0)),0) as lvl");
check("Level after stocktake only (BB=24)", (float)$r->fetch_row()[0], 24.0);

// ============================================================
echo "\n=== Page 404: Deliveries ===\n";

q($mysqli, "INSERT INTO stock_movement (stock_id, movement_type, qty, unit, unit_qty, movement_date) VALUES ({$s1}, 'delivery', 48, 'can', 1, '{$now}')");
q($mysqli, "INSERT INTO stock_movement (stock_id, movement_type, qty, unit, unit_qty, movement_date) VALUES ({$s3}, 'delivery',  5, 'kg',  1, '{$now}')");

check("Delivery movements inserted", (int)scalar($mysqli, "SELECT COUNT(*) FROM stock_movement WHERE movement_type='delivery'"), 2);

$r = $mysqli->query("SELECT COALESCE((SELECT sm1.qty FROM stock_movement sm1 WHERE sm1.stock_id={$s1} AND sm1.movement_type='stocktake_adjustment' ORDER BY sm1.id DESC LIMIT 1),0) + COALESCE((SELECT SUM(sm2.qty) FROM stock_movement sm2 WHERE sm2.stock_id={$s1} AND sm2.movement_type='delivery' AND sm2.id > COALESCE((SELECT MAX(sm3.id) FROM stock_movement sm3 WHERE sm3.stock_id={$s1} AND sm3.movement_type='stocktake_adjustment'),0)),0) - COALESCE((SELECT SUM(sm4.qty) FROM stock_movement sm4 WHERE sm4.stock_id={$s1} AND sm4.movement_type='stockout' AND sm4.id > COALESCE((SELECT MAX(sm5.id) FROM stock_movement sm5 WHERE sm5.stock_id={$s1} AND sm5.movement_type='stocktake_adjustment'),0)),0) as lvl");
check("Level after delivery (BB: 24+48=72)", (float)$r->fetch_row()[0], 72.0);

// ============================================================
echo "\n=== Page 405: Stock Usage (Stockout) ===\n";

q($mysqli, "INSERT INTO stock_movement (stock_id, movement_type, qty, unit, unit_qty, movement_date) VALUES ({$s2}, 'stockout', 5, 'can', 1, '{$now}')");
q($mysqli, "INSERT INTO stock_movement (stock_id, movement_type, qty, unit, unit_qty, movement_date) VALUES ({$s3}, 'stockout', 3, 'kg',  1, '{$now}')");

check("Stockout movements inserted", (int)scalar($mysqli, "SELECT COUNT(*) FROM stock_movement WHERE movement_type='stockout'"), 2);

$r = $mysqli->query("SELECT COALESCE((SELECT sm1.qty FROM stock_movement sm1 WHERE sm1.stock_id={$s2} AND sm1.movement_type='stocktake_adjustment' ORDER BY sm1.id DESC LIMIT 1),0) + COALESCE((SELECT SUM(sm2.qty) FROM stock_movement sm2 WHERE sm2.stock_id={$s2} AND sm2.movement_type='delivery' AND sm2.id > COALESCE((SELECT MAX(sm3.id) FROM stock_movement sm3 WHERE sm3.stock_id={$s2} AND sm3.movement_type='stocktake_adjustment'),0)),0) - COALESCE((SELECT SUM(sm4.qty) FROM stock_movement sm4 WHERE sm4.stock_id={$s2} AND sm4.movement_type='stockout' AND sm4.id > COALESCE((SELECT MAX(sm5.id) FROM stock_movement sm5 WHERE sm5.stock_id={$s2} AND sm5.movement_type='stocktake_adjustment'),0)),0) as lvl");
check("Level after stockout (TS: 12-5=7)", (float)$r->fetch_row()[0], 7.0);

$r = $mysqli->query("SELECT COALESCE((SELECT sm1.qty FROM stock_movement sm1 WHERE sm1.stock_id={$s3} AND sm1.movement_type='stocktake_adjustment' ORDER BY sm1.id DESC LIMIT 1),0) + COALESCE((SELECT SUM(sm2.qty) FROM stock_movement sm2 WHERE sm2.stock_id={$s3} AND sm2.movement_type='delivery' AND sm2.id > COALESCE((SELECT MAX(sm3.id) FROM stock_movement sm3 WHERE sm3.stock_id={$s3} AND sm3.movement_type='stocktake_adjustment'),0)),0) - COALESCE((SELECT SUM(sm4.qty) FROM stock_movement sm4 WHERE sm4.stock_id={$s3} AND sm4.movement_type='stockout' AND sm4.id > COALESCE((SELECT MAX(sm5.id) FROM stock_movement sm5 WHERE sm5.stock_id={$s3} AND sm5.movement_type='stocktake_adjustment'),0)),0) as lvl");
check("Level after delivery+stockout (Pasta: 10+5-3=12)", (float)$r->fetch_row()[0], 12.0);

// ============================================================
echo "\n=== Page 406: Stock Level Report (full query) ===\n";

$query  = "SELECT s.id, s.Name, s.Code, sc.Name as category_name,";
$query .= " COALESCE((SELECT sm1.qty FROM stock_movement sm1 WHERE sm1.stock_id=s.id AND sm1.movement_type='stocktake_adjustment' ORDER BY sm1.id DESC LIMIT 1),0)";
$query .= " + COALESCE((SELECT SUM(sm2.qty) FROM stock_movement sm2 WHERE sm2.stock_id=s.id AND sm2.movement_type='delivery' AND sm2.id > COALESCE((SELECT MAX(sm3.id) FROM stock_movement sm3 WHERE sm3.stock_id=s.id AND sm3.movement_type='stocktake_adjustment'),0)),0)";
$query .= " - COALESCE((SELECT SUM(sm4.qty) FROM stock_movement sm4 WHERE sm4.stock_id=s.id AND sm4.movement_type='stockout' AND sm4.id > COALESCE((SELECT MAX(sm5.id) FROM stock_movement sm5 WHERE sm5.stock_id=s.id AND sm5.movement_type='stocktake_adjustment'),0)),0) as current_qty";
$query .= " FROM stock s LEFT JOIN stock_category sc ON s.category_id=sc.id ORDER BY sc.Name, s.Name";

$r = $mysqli->query($query);
check("Report query executes", $r !== false);
$rows = $r->fetch_all(MYSQLI_ASSOC);
check("Report returns 3 rows", count($rows), 3);

echo "\n  Full report output:\n";
$currentcat = null;
foreach ($rows as $row) {
    if ($row['category_name'] !== $currentcat) {
        $currentcat = $row['category_name'];
        echo "    [{$currentcat}]\n";
    }
    $qty = (float)$row['current_qty'];
    $flag = $qty <= 0 ? " *** ZERO ***" : "";
    printf("      %-20s %-6s  Qty: %6.1f%s\n", $row['Name'], $row['Code'], $qty, $flag);
}

// ============================================================
echo "\n=== Summary ===\n";
echo "  Passed: {$pass}\n";
echo "  Failed: {$fail}\n";

$mysqli->close();
