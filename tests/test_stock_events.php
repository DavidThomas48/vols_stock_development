<?php
// =============================================================================
// Test: Stock Event Module (Pages 409-414)
// Tests the new stock_event / location-aware QOH system introduced in Stages 1-8.
//
// Run from CLI: php tests/test_stock_events.php
//
// WARNING: Clears location, stock_supplier, stock_event and the new-style
// stock_movement rows before running. Does NOT touch old-style movements
// (those with stock_event_id IS NULL). Safe to run alongside old test data.
//
// Requires: location, stock_event, stock_movement tables (post-migration).
// =============================================================================
$mysqli = new mysqli('localhost', 'root', '', 'vols');
if ($mysqli->connect_error) { die("DB connection failed: " . $mysqli->connect_error . "\n"); }
$mysqli->set_charset('utf8mb4');

$pass = 0; $fail = 0;

function check(string $label, $result, $expected = true): void {
    global $pass, $fail;
    if ($result === $expected) {
        echo "  PASS: {$label}\n";
        $pass++;
    } else {
        echo "  FAIL: {$label}"
           . " (got " . var_export($result, true)
           . ", expected " . var_export($expected, true) . ")\n";
        $fail++;
    }
}

function q($mysqli, string $sql) {
    $r = $mysqli->query($sql);
    if (!$r) { echo "  SQL ERROR: " . $mysqli->error . "\n  SQL: $sql\n"; return false; }
    return $r;
}

function scalar($mysqli, string $sql) {
    $r = $mysqli->query($sql);
    if (!$r) return false;
    $row = $r->fetch_row();
    return $row ? $row[0] : null;
}

// -------------------------------------------------------------------------
// Runs calculateqoh() inline — mirrors StockMovementTable::calculateqoh().
// -------------------------------------------------------------------------
function calculateqoh($mysqli, int $stock_id, int $location_id): int {
    $sid = $mysqli->real_escape_string($stock_id);
    $lid = $mysqli->real_escape_string($location_id);

    $st_subq  = "SELECT";
    $st_subq .= "  COALESCE(";
    $st_subq .= "    (SELECT sm_st.stock_qoh";
    $st_subq .= "     FROM stock_movement sm_st";
    $st_subq .= "     JOIN stock_event se_st ON sm_st.stock_event_id = se_st.id";
    $st_subq .= "     WHERE sm_st.stock_id = '{$sid}'";
    $st_subq .= "       AND sm_st.location_id = '{$lid}'";
    $st_subq .= "       AND se_st.event = 'stocktake'";
    $st_subq .= "       AND se_st.status = 'closed'";
    $st_subq .= "     ORDER BY se_st.date_created DESC LIMIT 1), 0) AS initial_qty,";
    $st_subq .= "  COALESCE(";
    $st_subq .= "    (SELECT se_st.date_created";
    $st_subq .= "     FROM stock_movement sm_st";
    $st_subq .= "     JOIN stock_event se_st ON sm_st.stock_event_id = se_st.id";
    $st_subq .= "     WHERE sm_st.stock_id = '{$sid}'";
    $st_subq .= "       AND sm_st.location_id = '{$lid}'";
    $st_subq .= "       AND se_st.event = 'stocktake'";
    $st_subq .= "       AND se_st.status = 'closed'";
    $st_subq .= "     ORDER BY se_st.date_created DESC LIMIT 1),";
    $st_subq .= "    '1970-01-01 00:00:00') AS st_date";

    $sum = fn($event_type) =>
        "COALESCE("
        . "(SELECT SUM(sm.qty)"
        . " FROM stock_movement sm"
        . " JOIN stock_event se ON sm.stock_event_id = se.id"
        . " WHERE sm.stock_id = '{$sid}'"
        . "   AND sm.location_id = '{$lid}'"
        . "   AND se.event = '{$event_type}'"
        . "   AND se.status = 'closed'"
        . "   AND se.date_created > st.st_date)"
        . ", 0)";

    $query  = "SELECT";
    $query .= "  st.initial_qty";
    $query .= "  + {$sum('delivery')}";
    $query .= "  + {$sum('transfer')}";
    $query .= "  + {$sum('adjustment')}";
    $query .= "  - {$sum('issue')}";
    $query .= "  AS qoh";
    $query .= " FROM ({$st_subq}) AS st";

    $r = $mysqli->query($query);
    if (!$r) { echo "  SQL ERROR (calculateqoh): " . $mysqli->error . "\n"; return 0; }
    return (int)$r->fetch_row()[0];
}

// -------------------------------------------------------------------------
// Helper: insert a stock_event and return its id.
// -------------------------------------------------------------------------
function insert_event($mysqli, string $event, int $loc1, ?int $loc2, ?int $supplier_id, string $status, string $date_created, ?string $date_closed = null): int {
    $loc2sql    = $loc2        ? $loc2        : 'NULL';
    $suppsql    = $supplier_id ? $supplier_id : 'NULL';
    $closed_sql = $date_closed ? "'{$date_closed}'" : 'NULL';
    q($mysqli,
        "INSERT INTO stock_event (event, location1_id, location2_id, supplier_id, stock_client_id, status, date_created, date_closed, date_cancelled)"
       ." VALUES ('{$event}', {$loc1}, {$loc2sql}, {$suppsql}, NULL, '{$status}', '{$date_created}', {$closed_sql}, NULL)"
    );
    return $mysqli->insert_id;
}

// -------------------------------------------------------------------------
// Helper: insert a new-style stock_movement.
// Event semantics are read from the parent stock_event.event.
// -------------------------------------------------------------------------
function insert_movement($mysqli, int $stock_id, int $event_id, int $location_id, int $qty, ?int $stock_qoh = null): int {
    $qoh_sql = ($stock_qoh !== null) ? $stock_qoh : 'NULL';
    q($mysqli,
        "INSERT INTO stock_movement (stock_id, qty, stock_qoh, unit, unit_qty, stock_event_id, location_id, movement_date)"
       ." VALUES ({$stock_id}, {$qty}, {$qoh_sql}, '', 1, {$event_id}, {$location_id}, NOW())"
    );
    return $mysqli->insert_id;
}

// =========================================================================
echo "\n=== Setup: seed data ===\n";
// =========================================================================

// Remove only new-style data (leave old-style stock_movement rows untouched).
q($mysqli, "DELETE FROM stock_movement WHERE stock_event_id IS NOT NULL");
q($mysqli, "DELETE FROM stock_event");
q($mysqli, "DELETE FROM location");
q($mysqli, "DELETE FROM stock_supplier");

// Locations
q($mysqli, "INSERT INTO location (name) VALUES ('Warehouse')");
$loc1 = $mysqli->insert_id;
q($mysqli, "INSERT INTO location (name) VALUES ('Pantry')");
$loc2 = $mysqli->insert_id;

// Re-use existing stock items (or insert fresh ones if table is empty).
$s1 = (int)scalar($mysqli, "SELECT id FROM stock LIMIT 1");
if (!$s1) {
    q($mysqli, "INSERT INTO stock_category (Name) VALUES ('Test Category')");
    $cat = $mysqli->insert_id;
    q($mysqli, "INSERT INTO stock (Name, Code, category_id) VALUES ('Test Item', 'TI', {$cat})");
    $s1 = $mysqli->insert_id;
    q($mysqli, "INSERT INTO stock (Name, Code, category_id) VALUES ('Test Item 2', 'TI2', {$cat})");
    $s2 = $mysqli->insert_id;
} else {
    $rows = $mysqli->query("SELECT id FROM stock ORDER BY id LIMIT 2")->fetch_all();
    $s1 = (int)$rows[0][0];
    $s2 = isset($rows[1]) ? (int)$rows[1][0] : $s1;
}

echo "  Locations: Warehouse={$loc1}, Pantry={$loc2}\n";
echo "  Stock IDs: s1={$s1}, s2={$s2}\n";

// =========================================================================
echo "\n=== QOH: no movements → 0 ===\n";
// =========================================================================
check("QOH zero with no movements", calculateqoh($mysqli, $s1, $loc1), 0);

// =========================================================================
echo "\n=== QOH: closed stocktake sets baseline ===\n";
// =========================================================================
$ev1 = insert_event($mysqli, 'stocktake', $loc1, null, null, 'closed', '2026-01-10 08:00:00', '2026-01-10 09:00:00');
insert_movement($mysqli, $s1, $ev1, $loc1, 0, 50); // qty=0; stock_qoh=50

check("QOH equals stocktake stock_qoh", calculateqoh($mysqli, $s1, $loc1), 50);
check("Different location still zero",  calculateqoh($mysqli, $s1, $loc2), 0);
check("Different stock still zero",     calculateqoh($mysqli, $s2, $loc1), 0);

// =========================================================================
echo "\n=== QOH: delivery after stocktake adds to QOH ===\n";
// =========================================================================
$ev2 = insert_event($mysqli, 'delivery', $loc1, null, null, 'closed', '2026-01-15 09:00:00', '2026-01-15 10:00:00');
insert_movement($mysqli, $s1, $ev2, $loc1, 24, null);

check("QOH = stocktake + delivery (50+24=74)", calculateqoh($mysqli, $s1, $loc1), 74);

// =========================================================================
echo "\n=== QOH: issue after stocktake reduces QOH ===\n";
// =========================================================================
$ev3 = insert_event($mysqli, 'issue', $loc1, null, null, 'closed', '2026-01-20 11:00:00', '2026-01-20 11:30:00');
insert_movement($mysqli, $s1, $ev3, $loc1, 10, null);

check("QOH = stocktake + delivery - issue (74-10=64)", calculateqoh($mysqli, $s1, $loc1), 64);

// =========================================================================
echo "\n=== QOH: adjustment adds or subtracts ===\n";
// =========================================================================
$ev4 = insert_event($mysqli, 'adjustment', $loc1, null, null, 'closed', '2026-01-22 10:00:00', '2026-01-22 10:05:00');
insert_movement($mysqli, $s1, $ev4, $loc1, -4, null); // negative adjustment

check("QOH after negative adjustment (64-4=60)", calculateqoh($mysqli, $s1, $loc1), 60);

// =========================================================================
echo "\n=== QOH: in-progress event does NOT count ===\n";
// =========================================================================
$ev5 = insert_event($mysqli, 'delivery', $loc1, null, null, 'in progress', '2026-01-25 08:00:00', null);
insert_movement($mysqli, $s1, $ev5, $loc1, 100, null);

check("In-progress delivery ignored (QOH still 60)", calculateqoh($mysqli, $s1, $loc1), 60);

// Clean up in-progress event and its movement.
q($mysqli, "DELETE FROM stock_movement WHERE stock_event_id = {$ev5}");
q($mysqli, "DELETE FROM stock_event WHERE id = {$ev5}");

// =========================================================================
echo "\n=== QOH: delivery BEFORE stocktake date is ignored ===\n";
// =========================================================================
// Insert an event dated before the stocktake (ev1 date = 2026-01-10 08:00:00).
$ev6 = insert_event($mysqli, 'delivery', $loc1, null, null, 'closed', '2026-01-05 08:00:00', '2026-01-05 09:00:00');
insert_movement($mysqli, $s1, $ev6, $loc1, 999, null);

check("Delivery before stocktake ignored (QOH still 60)", calculateqoh($mysqli, $s1, $loc1), 60);

// =========================================================================
echo "\n=== QOH: transfer — positive at To, negative at From ===\n";
// =========================================================================
// Transfer 15 units from loc1 to loc2.
// Movements: +15 at loc2, -15 at loc1.
$ev7 = insert_event($mysqli, 'transfer', $loc1, $loc2, null, 'closed', '2026-01-28 09:00:00', '2026-01-28 09:30:00');
insert_movement($mysqli, $s1, $ev7, $loc2,  15, null); // to loc2
insert_movement($mysqli, $s1, $ev7, $loc1, -15, null); // from loc1

check("QOH at loc1 reduced by transfer (60-15=45)", calculateqoh($mysqli, $s1, $loc1), 45);
check("QOH at loc2 increased by transfer (0+15=15)", calculateqoh($mysqli, $s1, $loc2), 15);

// =========================================================================
echo "\n=== QOH: new stocktake resets baseline ===\n";
// =========================================================================
$ev8 = insert_event($mysqli, 'stocktake', $loc1, null, null, 'closed', '2026-02-01 08:00:00', '2026-02-01 09:00:00');
insert_movement($mysqli, $s1, $ev8, $loc1, 0, 40); // counted 40 units

// All prior movements at loc1 before 2026-02-01 are now behind the new baseline.
// Any closed events AFTER the new stocktake's date_created would add to it — there are none.
check("QOH after new stocktake = new stock_qoh (40)", calculateqoh($mysqli, $s1, $loc1), 40);

// Delivery AFTER the new stocktake counts.
$ev9 = insert_event($mysqli, 'delivery', $loc1, null, null, 'closed', '2026-02-05 10:00:00', '2026-02-05 10:15:00');
insert_movement($mysqli, $s1, $ev9, $loc1, 12, null);

check("QOH = new_stocktake + post-stocktake delivery (40+12=52)", calculateqoh($mysqli, $s1, $loc1), 52);

// =========================================================================
echo "\n=== Stocktake pre-close: qty = stock_qoh - calculateqoh ===\n";
// =========================================================================
// Simulate a stocktake: current QOH at loc2 is 15 (from transfer above).
// Create a new in-progress stocktake at loc2, record a count of 20.
$ev10 = insert_event($mysqli, 'stocktake', $loc2, null, null, 'in progress', '2026-02-10 08:00:00', null);
$mv10 = insert_movement($mysqli, $s1, $ev10, $loc2, 0, 20); // stock_qoh=20, qty=0

// Pre-close: qty = stock_qoh - calculateqoh (which is 15 at loc2)
$current_qoh = calculateqoh($mysqli, $s1, $loc2);
$stock_qoh   = (int)scalar($mysqli, "SELECT stock_qoh FROM stock_movement WHERE id = {$mv10}");
$expected_qty = $stock_qoh - $current_qoh; // 20 - 15 = 5

check("calculateqoh at loc2 before stocktake close = 15", $current_qoh, 15);
check("Pre-close qty = stock_qoh - calculateqoh = 5",     $expected_qty, 5);

// Apply the pre-close qty update (as preclosestocktake() does).
q($mysqli, "UPDATE stock_movement SET qty = {$expected_qty} WHERE id = {$mv10}");

// Close the event.
q($mysqli, "UPDATE stock_event SET status='closed', date_closed='2026-02-10 09:00:00' WHERE id={$ev10}");

// QOH at loc2 should now be: 15 (from transfer) + 5 (stocktake adjustment) = 20 (= the counted value).
check("QOH at loc2 after stocktake close = counted value (20)", calculateqoh($mysqli, $s1, $loc2), 20);

// =========================================================================
echo "\n=== hasinprogressstocktake: only one active stocktake at a time ===\n";
// =========================================================================
$ev11 = insert_event($mysqli, 'stocktake', $loc1, null, null, 'in progress', '2026-02-15 08:00:00', null);

$count = (int)scalar($mysqli, "SELECT COUNT(*) FROM stock_event WHERE event='stocktake' AND status='in progress'");
check("One in-progress stocktake exists", $count, 1);

// Trying to start another should be blocked (enforced by StockEventManager::createevent()).
// We verify here that the count would trigger the block.
check("Block condition: count > 0 triggers refusal", $count > 0, true);

// =========================================================================
echo "\n=== cancelevent: blocked if stocktake closed after event creation ===\n";
// =========================================================================
// Event ev7 (transfer) was created at '2026-01-28 09:00:00'.
// Stocktake ev8 was closed at '2026-02-01 09:00:00' > ev7's date_created → cancel BLOCKED.
$ev7_date = scalar($mysqli, "SELECT date_created FROM stock_event WHERE id = {$ev7}");
$blocking_count = (int)scalar($mysqli,
    "SELECT COUNT(*) FROM stock_event"
   ." WHERE event = 'stocktake' AND status = 'closed'"
   ." AND date_closed > '{$ev7_date}'"
);
check("Cancel blocked: stocktake closed after event was created (count > 0)", $blocking_count > 0, true);

// Event ev9 (delivery, created 2026-02-05) — last stocktake close was 2026-02-10.
// Wait — ev10 stocktake was closed at 2026-02-10 which is AFTER ev9's creation.
// So ev9 cancel is also blocked.
$ev9_date = scalar($mysqli, "SELECT date_created FROM stock_event WHERE id = {$ev9}");
$blocking_count9 = (int)scalar($mysqli,
    "SELECT COUNT(*) FROM stock_event"
   ." WHERE event = 'stocktake' AND status = 'closed'"
   ." AND date_closed > '{$ev9_date}'"
);
check("Cancel of ev9 also blocked (stocktake closed after it)", $blocking_count9 > 0, true);

// An event created AFTER the last stocktake close should be cancellable.
$ev12 = insert_event($mysqli, 'delivery', $loc1, null, null, 'in progress', '2026-03-01 08:00:00', null);
$ev12_date = scalar($mysqli, "SELECT date_created FROM stock_event WHERE id = {$ev12}");
$blocking_count12 = (int)scalar($mysqli,
    "SELECT COUNT(*) FROM stock_event"
   ." WHERE event = 'stocktake' AND status = 'closed'"
   ." AND date_closed > '{$ev12_date}'"
);
check("Cancel NOT blocked for event after last stocktake close", $blocking_count12, 0);

// =========================================================================
echo "\n=== getstockforevent: left-join returns all stock with existing qty ===\n";
// =========================================================================
// Create a fresh in-progress stocktake at loc1 and record one movement.
$ev13 = insert_event($mysqli, 'stocktake', $loc1, null, null, 'in progress', '2026-03-05 08:00:00', null);
insert_movement($mysqli, $s1, $ev13, $loc1, 0, 33);

// getstockforevent should show s1 with stock_qoh=33 and s2 with null (no movement yet).
$r = $mysqli->query(
    "SELECT s.id as stock_id, s.Name as stock_name,"
   ." sm.id as movement_id, sm.qty, sm.stock_qoh, sm.location_id"
   ." FROM stock s"
   ." LEFT JOIN stock_movement sm ON sm.stock_id = s.id AND sm.stock_event_id = {$ev13}"
   ." ORDER BY s.id"
);
$rows = $r->fetch_all(MYSQLI_ASSOC);
$row_s1 = array_values(array_filter($rows, fn($row) => (int)$row['stock_id'] === $s1))[0] ?? null;
$row_s2 = array_values(array_filter($rows, fn($row) => (int)$row['stock_id'] === $s2))[0] ?? null;

check("getstockforevent: s1 has a movement",       $row_s1 !== null && (int)$row_s1['movement_id'] > 0, true);
check("getstockforevent: s1 stock_qoh=33",         $row_s1 !== null ? (int)$row_s1['stock_qoh'] : null, 33);
if ($s1 !== $s2) {
    check("getstockforevent: s2 movement_id is null", $row_s2 !== null ? $row_s2['movement_id'] : 'missing', null);
}

// =========================================================================
echo "\n=== Summary ===\n";
// =========================================================================
echo "  Passed: {$pass}\n";
echo "  Failed: {$fail}\n";
if ($fail === 0) { echo "  ALL TESTS PASSED\n"; }

$mysqli->close();
