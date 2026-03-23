<?php
// =============================================================================
// Test: Stock Usage Report Query (Page 408)
// Tests getusagereport() date-range filtering and aggregation.
// Run from CLI: /path/to/php.exe tests/test_usagereport.php
//
// WARNING: Clears stock_movement, stock, and stock_category before running.
// Do not run against a database with live data.
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

function runusagereport($mysqli, $from, $to) {
    $from = $mysqli->real_escape_string($from);
    $to   = $mysqli->real_escape_string($to);
    $query  = "SELECT s.id, s.Name, s.Code, sc.Name as category_name,";
    $query .= " SUM(sm.qty) as total_used";
    $query .= " FROM stock_movement sm";
    $query .= " JOIN stock s ON sm.stock_id = s.id";
    $query .= " LEFT JOIN stock_category sc ON s.category_id = sc.id";
    $query .= " WHERE sm.movement_type = 'stockout'";
    $query .= " AND sm.movement_date >= '{$from} 00:00:00'";
    $query .= " AND sm.movement_date <= '{$to} 23:59:59'";
    $query .= " GROUP BY s.id, s.Name, s.Code, sc.Name";
    $query .= " ORDER BY sc.Name, s.Name";
    $r = $mysqli->query($query);
    if (!$r) { echo "  SQL ERROR: " . $mysqli->error . "\n"; return []; }
    return $r->fetch_all(MYSQLI_ASSOC);
}

// ============================================================
echo "\n=== Setup: seed categories and stock items ===\n";
q($mysqli, "DELETE FROM stock_movement");
q($mysqli, "DELETE FROM stock");
q($mysqli, "DELETE FROM stock_category");

q($mysqli, "INSERT INTO stock_category (Name) VALUES ('Tinned Goods')");
$cat1 = $mysqli->insert_id;
q($mysqli, "INSERT INTO stock_category (Name) VALUES ('Dry Goods')");
$cat2 = $mysqli->insert_id;
q($mysqli, "INSERT INTO stock (Name, Code, category_id) VALUES ('Baked Beans', 'BB', {$cat1})");
$s1 = $mysqli->insert_id;
q($mysqli, "INSERT INTO stock (Name, Code, category_id) VALUES ('Tomato Soup', 'TS', {$cat1})");
$s2 = $mysqli->insert_id;
q($mysqli, "INSERT INTO stock (Name, Code, category_id) VALUES ('Rice', 'RC', {$cat2})");
$s3 = $mysqli->insert_id;
echo "  Seeded 2 categories, 3 items.\n";

// Insert movements across different dates
$movements = [
    // Jan stockouts
    [$s1, 'stockout', 5,  '2026-01-05 10:00:00'],
    [$s1, 'stockout', 3,  '2026-01-15 11:00:00'],
    [$s2, 'stockout', 8,  '2026-01-20 09:00:00'],
    // Feb stockouts
    [$s1, 'stockout', 7,  '2026-02-03 10:00:00'],
    [$s3, 'stockout', 12, '2026-02-14 14:00:00'],
    // Mar stockouts
    [$s1, 'stockout', 4,  '2026-03-01 10:00:00'],
    [$s2, 'stockout', 6,  '2026-03-10 09:00:00'],
    // Non-stockout (should never appear)
    [$s1, 'damaged',  2,  '2026-02-10 10:00:00'],
    [$s1, 'delivery', 50, '2026-01-01 08:00:00'],
];
foreach ($movements as [$sid, $type, $qty, $date]) {
    q($mysqli, "INSERT INTO stock_movement (stock_id, movement_type, qty, unit, unit_qty, movement_date) VALUES ({$sid}, '{$type}', {$qty}, 'can', 1, '{$date}')");
}
echo "  Movements inserted.\n";

// ============================================================
echo "\n=== Full date range: all stockouts ===\n";

$rows = runusagereport($mysqli, '2026-01-01', '2026-03-31');
check("Returns only stock items with stockouts", count($rows), 3);
$bb = array_values(array_filter($rows, fn($r) => $r['Name']==='Baked Beans'))[0];
$ts = array_values(array_filter($rows, fn($r) => $r['Name']==='Tomato Soup'))[0];
$rc = array_values(array_filter($rows, fn($r) => $r['Name']==='Rice'))[0];
// BB: 5+3+7+4 = 19
check("BB total_used=19 (all months)", (float)$bb['total_used'], 19.0);
// TS: 8+6 = 14
check("TS total_used=14 (all months)", (float)$ts['total_used'], 14.0);
// Rice: 12
check("RC total_used=12 (all months)", (float)$rc['total_used'], 12.0);
check("non-stockout movements excluded", !array_filter($rows, fn($r) => $r['total_used'] == 2));

// ============================================================
echo "\n=== January only ===\n";

$rows = runusagereport($mysqli, '2026-01-01', '2026-01-31');
check("Jan: 2 items with usage", count($rows), 2);
$bb = array_values(array_filter($rows, fn($r) => $r['Name']==='Baked Beans'))[0];
$ts = array_values(array_filter($rows, fn($r) => $r['Name']==='Tomato Soup'))[0];
// BB Jan: 5+3 = 8
check("BB Jan total_used=8", (float)$bb['total_used'], 8.0);
// TS Jan: 8
check("TS Jan total_used=8", (float)$ts['total_used'], 8.0);
// Rice: no Jan stockouts
check("Rice not in Jan results", count(array_filter($rows, fn($r) => $r['Name']==='Rice')), 0);

// ============================================================
echo "\n=== February only ===\n";

$rows = runusagereport($mysqli, '2026-02-01', '2026-02-28');
check("Feb: 2 items with usage", count($rows), 2);
$bb = array_values(array_filter($rows, fn($r) => $r['Name']==='Baked Beans'))[0];
$rc = array_values(array_filter($rows, fn($r) => $r['Name']==='Rice'))[0];
check("BB Feb total_used=7", (float)$bb['total_used'], 7.0);
check("RC Feb total_used=12", (float)$rc['total_used'], 12.0);

// ============================================================
echo "\n=== Boundary: exact day range ===\n";

// Just 2026-01-05 — should get BB=5 only
$rows = runusagereport($mysqli, '2026-01-05', '2026-01-05');
check("Single day returns 1 item", count($rows), 1);
check("Single day BB=5", (float)$rows[0]['total_used'], 5.0);

// ============================================================
echo "\n=== Empty range: no stockouts ===\n";

$rows = runusagereport($mysqli, '2025-01-01', '2025-12-31');
check("Range with no stockouts returns 0 rows", count($rows), 0);

// ============================================================
echo "\n=== Ordering: grouped by category then name ===\n";

$rows = runusagereport($mysqli, '2026-01-01', '2026-03-31');
$names = array_column($rows, 'Name');
check("Dry Goods (Rice) before Tinned Goods (Baked Beans)", array_search('Rice',$names) < array_search('Baked Beans',$names));
check("Baked Beans before Tomato Soup within category", array_search('Baked Beans',$names) < array_search('Tomato Soup',$names));
check("category_name present", $rows[0]['category_name'] !== null);

// ============================================================
echo "\n=== Display sample output ===\n";
$rows = runusagereport($mysqli, '2026-01-01', '2026-03-31');
echo "\n  Usage 2026-01-01 to 2026-03-31:\n";
$currentcat = null;
foreach ($rows as $row) {
    $cat = $row['category_name'] ?? 'Uncategorised';
    if ($cat !== $currentcat) {
        $currentcat = $cat;
        printf("  --- %s ---\n", $cat);
    }
    printf("  %-15s %-5s  total_used=%s\n", $row['Name'], $row['Code'], $row['total_used']);
}

// ============================================================
echo "\n=== Summary ===\n";
echo "  Passed: {$pass}\n";
echo "  Failed: {$fail}\n";

$mysqli->close();
