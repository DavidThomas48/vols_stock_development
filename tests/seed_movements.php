<?php
$m = new mysqli('localhost', 'root', '', 'vols');
if ($m->connect_error) die("DB error: " . $m->connect_error . "\n");

$m->query('DELETE FROM stock_movement');
echo "Cleared existing movements\n";

$r  = $m->query("SELECT id, Name FROM stock");
$ids = [];
while ($row = $r->fetch_assoc()) $ids[$row['Name']] = $row['id'];
$bb = $ids['Baked Beans'];
$ts = $ids['Tomato Soup'];
$rc = $ids['Rice'];
echo "Stock IDs: BB={$bb}, TS={$ts}, RC={$rc}\n";

$rows = [
    // Stocktake 6 Jan
    [$bb, 'stocktake_adjustment', 48, 'can', '2026-01-06 09:00:00'],
    [$ts, 'stocktake_adjustment', 36, 'can', '2026-01-06 09:00:00'],
    [$rc, 'stocktake_adjustment', 25, 'bag', '2026-01-06 09:00:00'],
    // Delivery 20 Jan
    [$bb, 'delivery',  24, 'can', '2026-01-20 10:00:00'],
    [$ts, 'delivery',  12, 'can', '2026-01-20 10:00:00'],
    // Stockout 22 Jan
    [$bb, 'stockout',   8, 'can', '2026-01-22 14:00:00'],
    [$ts, 'stockout',   5, 'can', '2026-01-22 14:00:00'],
    [$rc, 'stockout',   3, 'bag', '2026-01-22 14:00:00'],
    // Stockout 5 Feb
    [$bb, 'stockout',  12, 'can', '2026-02-05 14:00:00'],
    [$ts, 'stockout',   8, 'can', '2026-02-05 14:00:00'],
    [$rc, 'stockout',   6, 'bag', '2026-02-05 14:00:00'],
    // Damaged 10 Feb
    [$ts, 'damaged',    3, 'can', '2026-02-10 11:00:00'],
    // Delivery 18 Feb
    [$bb, 'delivery',  36, 'can', '2026-02-18 10:00:00'],
    [$rc, 'delivery',  20, 'bag', '2026-02-18 10:00:00'],
    // Stockout 26 Feb
    [$bb, 'stockout',  10, 'can', '2026-02-26 14:00:00'],
    [$ts, 'stockout',   6, 'can', '2026-02-26 14:00:00'],
    [$rc, 'stockout',   8, 'bag', '2026-02-26 14:00:00'],
    // Damaged 5 Mar
    [$bb, 'damaged',    2, 'can', '2026-03-05 09:30:00'],
    // Delivery 10 Mar
    [$ts, 'delivery',  24, 'can', '2026-03-10 10:00:00'],
    // Stockout 12 Mar
    [$bb, 'stockout',  15, 'can', '2026-03-12 14:00:00'],
    [$ts, 'stockout',  10, 'can', '2026-03-12 14:00:00'],
    [$rc, 'stockout',   5, 'bag', '2026-03-12 14:00:00'],
    // Stockout 19 Mar
    [$bb, 'stockout',   6, 'can', '2026-03-19 14:00:00'],
    [$rc, 'stockout',   4, 'bag', '2026-03-19 14:00:00'],
];

$vals = array_map(fn($r) =>
    "({$r[0]}, '{$r[1]}', {$r[2]}, '{$r[3]}', 1, '{$r[4]}')", $rows);
$sql = 'INSERT INTO stock_movement (stock_id, movement_type, qty, unit, unit_qty, movement_date) VALUES '
     . implode(',', $vals);

if ($m->query($sql)) {
    echo "Inserted " . count($rows) . " movements\n";
} else {
    echo "Error: " . $m->error . "\n";
}

// Show resulting stock levels
echo "\nCurrent stock levels:\n";
$last_st = "SELECT MAX(x.movement_date) FROM stock_movement x WHERE x.stock_id=s.id AND x.movement_type='stocktake_adjustment'";
$q  = "SELECT s.Name,";
$q .= " COALESCE((SELECT sm1.qty FROM stock_movement sm1 WHERE sm1.stock_id=s.id AND sm1.movement_type='stocktake_adjustment' ORDER BY sm1.movement_date DESC LIMIT 1),0) as st_qty,";
$q .= " COALESCE((SELECT SUM(sm2.qty) FROM stock_movement sm2 WHERE sm2.stock_id=s.id AND sm2.movement_type='delivery' AND sm2.movement_date > COALESCE(({$last_st}),'1970-01-01')),0) as deliv,";
$q .= " COALESCE((SELECT SUM(sm3.qty) FROM stock_movement sm3 WHERE sm3.stock_id=s.id AND sm3.movement_type='stockout' AND sm3.movement_date > COALESCE(({$last_st}),'1970-01-01')),0) as used,";
$q .= " COALESCE((SELECT SUM(sm4.qty) FROM stock_movement sm4 WHERE sm4.stock_id=s.id AND sm4.movement_type='damaged' AND sm4.movement_date > COALESCE(({$last_st}),'1970-01-01')),0) as damaged,";
$q .= " COALESCE((SELECT sm1.qty FROM stock_movement sm1 WHERE sm1.stock_id=s.id AND sm1.movement_type='stocktake_adjustment' ORDER BY sm1.movement_date DESC LIMIT 1),0)";
$q .= " + COALESCE((SELECT SUM(sm2.qty) FROM stock_movement sm2 WHERE sm2.stock_id=s.id AND sm2.movement_type='delivery' AND sm2.movement_date > COALESCE(({$last_st}),'1970-01-01')),0)";
$q .= " - COALESCE((SELECT SUM(sm4.qty) FROM stock_movement sm4 WHERE sm4.stock_id=s.id AND sm4.movement_type IN ('stockout','damaged') AND sm4.movement_date > COALESCE(({$last_st}),'1970-01-01')),0) as current_qty";
$q .= " FROM stock s ORDER BY s.Name";

$r = $m->query($q);
while ($row = $r->fetch_assoc()) {
    printf("  %-15s  stocktake=%3d  +deliveries=%3d  -used=%3d  -damaged=%2d  =current=%3d\n",
        $row['Name'], $row['st_qty'], $row['deliv'], $row['used'], $row['damaged'], $row['current_qty']);
}
$m->close();
