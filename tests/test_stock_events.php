<?php
// =============================================================================
// Test: Stock Event Module — exercises the real app class stack via PDO.
//
// Run from project root: php tests/test_stock_events.php
//
// Inserts test-only locations, events, and movements then removes them.
// Does NOT touch existing stock items or non-test data.
// =============================================================================

// ---- Bootstrap ---------------------------------------------------------------
$tc = 0;
define('DS', DIRECTORY_SEPARATOR);
define('ROOT_DIR', dirname(__DIR__) . DS);
define('FW_DIR',  ROOT_DIR . 'vendor' . DS . 'fw' . DS);
define('APP_DIR', ROOT_DIR . 'app' . DS);

require FW_DIR . 'bootstrap/bootstrap.php';

// gtab() is defined in index.php; provide a no-op for CLI tests.
function gtab($direction = 0) {}
// datetimestring() is used by MySQLTable lock logic — not hit in these tests,
// but define it so the autoloaded class file parses without error.
function datetimestring($dt) { return $dt->format('Y-m-d H:i:s'); }

// ---- Minimal error-handler stub ---------------------------------------------
class TestErrorHandler {
    public $lastError = '';
    public function sqlerror($code, $message, $query) {
        $this->lastError = $message;
        echo "  DB ERROR: $message\n  SQL: $query\n";
    }
    public function dblog($msg) {}
    public function loginerror($msg, $ctx) {}
}

// ---- Connect via the real MySqlDB (PDO) driver ------------------------------
$eh = new TestErrorHandler();
$db = new \database\MySqlDB();
$db->init($eh);
$db->connect('localhost', 'root', '', 'vols');

// ---- Instantiate the real table classes ------------------------------------
$eventTable    = new \apptable\StockEventTable();
$movementTable = new \apptable\StockMovementTable();
$stockTable    = new \apptable\StockTable();

$eventTable->init($db);
$movementTable->init($db);
$stockTable->init($db);

// ---- Test harness -----------------------------------------------------------
$pass = 0; $fail = 0;

function check(string $label, $result, $expected = true): void {
    global $pass, $fail;
    if ($result === $expected) {
        echo "  PASS: $label\n";
        $pass++;
    } else {
        echo "  FAIL: $label"
           . " (got "      . var_export($result,   true)
           . ", expected " . var_export($expected, true) . ")\n";
        $fail++;
    }
}

// ---- Helper: insert a stock_event via the real table class ------------------
function insert_event(
    \apptable\StockEventTable $t,
    string $event, int $loc1, ?int $loc2, ?int $supplier_id,
    string $status, string $date_created, ?string $date_closed = null
): int {
    $t->clear();
    $t->setfield('event',        $event);
    $t->setfield('location1_id', $loc1);
    $t->setfield('location2_id', $loc2     ?: 'null');
    $t->setfield('supplier_id',  $supplier_id ?: 'null');
    $t->setfield('status',       $status);
    $t->setfield('date_created', $date_created);
    if ($date_closed !== null) { $t->setfield('date_closed', $date_closed); }
    $id = 0;
    $t->insert(true, $id, false, $em);
    return $id;
}

// ---- Helper: insert a stock_movement via the real table class ---------------
function insert_movement(
    \apptable\StockMovementTable $t,
    int $stock_id, int $event_id, int $location_id, int $qty, ?int $stock_qoh = null
): int {
    $t->clear();
    $t->setfield('stock_id',       $stock_id);
    $t->setfield('stock_event_id', $event_id);
    $t->setfield('location_id',    $location_id);
    $t->setfield('qty',            $qty);
    $t->setfield('unit',           '');
    $t->setfield('unit_qty',       1);
    $t->setfield('movement_date',  date('Y-m-d H:i:s'));
    if ($stock_qoh !== null) { $t->setfield('stock_qoh', $stock_qoh); }
    $id = 0;
    $t->insert(true, $id, false, $em);
    return $id;
}

// =============================================================================
echo "\n=== Setup: seed data ===\n";
// =============================================================================

// Insert two test locations.
$db->dbquery("INSERT INTO stock_location (name, uncontrolled_issues) VALUES ('TEST_Warehouse', 0)", $r, $n, $em, 1);
$loc1 = $db->get_insert_id();
$db->dbquery("INSERT INTO stock_location (name, uncontrolled_issues) VALUES ('TEST_Pantry', 0)", $r, $n, $em, 1);
$loc2 = $db->get_insert_id();

// Use the first two stock items that already exist.
$rows = []; $n = 0;
$db->select('stock', 'id', '', '', '', '', 0, $rows, $n);
if ($n < 1) { die("No stock items found — seed the stock table first.\n"); }
$s1 = (int)$rows[0]['id'];
$s2 = ($n >= 2) ? (int)$rows[1]['id'] : $s1;

echo "  Locations: Warehouse={$loc1}, Pantry={$loc2}\n";
echo "  Stock IDs: s1={$s1}, s2={$s2}\n";

// Track every event we insert so we can clean up at the end.
$created_event_ids = [];

// =============================================================================
echo "\n=== QOH: no movements → 0 ===\n";
// =============================================================================
$qoh = 0;
$movementTable->calculateqoh($s1, $loc1, $qoh);
check("QOH zero with no movements", $qoh, 0);

// =============================================================================
echo "\n=== QOH: closed stocktake sets baseline ===\n";
// =============================================================================
$ev1 = insert_event($eventTable, 'stocktake', $loc1, null, null, 'closed', '2026-01-10 08:00:00', '2026-01-10 09:00:00');
$created_event_ids[] = $ev1;
insert_movement($movementTable, $s1, $ev1, $loc1, 0, 50); // stock_qoh=50

$movementTable->calculateqoh($s1, $loc1, $qoh);
check("QOH equals stocktake stock_qoh",      $qoh, 50);
$movementTable->calculateqoh($s1, $loc2, $qoh);
check("Different location still zero",       $qoh,  0);
$movementTable->calculateqoh($s2, $loc1, $qoh);
check("Different stock still zero",          $qoh,  0);

// =============================================================================
echo "\n=== QOH: delivery after stocktake adds to QOH ===\n";
// =============================================================================
$ev2 = insert_event($eventTable, 'delivery', $loc1, null, null, 'closed', '2026-01-15 09:00:00', '2026-01-15 10:00:00');
$created_event_ids[] = $ev2;
insert_movement($movementTable, $s1, $ev2, $loc1, 24);

$movementTable->calculateqoh($s1, $loc1, $qoh);
check("QOH = stocktake + delivery (50+24=74)", $qoh, 74);

// =============================================================================
echo "\n=== QOH: issue after stocktake reduces QOH ===\n";
// =============================================================================
$ev3 = insert_event($eventTable, 'issue', $loc1, null, null, 'closed', '2026-01-20 11:00:00', '2026-01-20 11:30:00');
$created_event_ids[] = $ev3;
insert_movement($movementTable, $s1, $ev3, $loc1, 10);

$movementTable->calculateqoh($s1, $loc1, $qoh);
check("QOH = stocktake + delivery - issue (74-10=64)", $qoh, 64);

// =============================================================================
echo "\n=== QOH: adjustment adds or subtracts ===\n";
// =============================================================================
$ev4 = insert_event($eventTable, 'adjustment', $loc1, null, null, 'closed', '2026-01-22 10:00:00', '2026-01-22 10:05:00');
$created_event_ids[] = $ev4;
insert_movement($movementTable, $s1, $ev4, $loc1, -4);

$movementTable->calculateqoh($s1, $loc1, $qoh);
check("QOH after negative adjustment (64-4=60)", $qoh, 60);

// =============================================================================
echo "\n=== QOH: in-progress event does NOT count ===\n";
// =============================================================================
$ev5 = insert_event($eventTable, 'delivery', $loc1, null, null, 'in progress', '2026-01-25 08:00:00');
$created_event_ids[] = $ev5;
insert_movement($movementTable, $s1, $ev5, $loc1, 100);

$movementTable->calculateqoh($s1, $loc1, $qoh);
check("In-progress delivery ignored (QOH still 60)", $qoh, 60);

// =============================================================================
echo "\n=== QOH: delivery BEFORE stocktake date is ignored ===\n";
// =============================================================================
$ev6 = insert_event($eventTable, 'delivery', $loc1, null, null, 'closed', '2026-01-05 08:00:00', '2026-01-05 09:00:00');
$created_event_ids[] = $ev6;
insert_movement($movementTable, $s1, $ev6, $loc1, 999);

$movementTable->calculateqoh($s1, $loc1, $qoh);
check("Delivery before stocktake ignored (QOH still 60)", $qoh, 60);

// =============================================================================
echo "\n=== QOH: transfer — positive at To, negative at From ===\n";
// =============================================================================
$ev7 = insert_event($eventTable, 'transfer', $loc1, $loc2, null, 'closed', '2026-01-28 09:00:00', '2026-01-28 09:30:00');
$created_event_ids[] = $ev7;
insert_movement($movementTable, $s1, $ev7, $loc2,  15);
insert_movement($movementTable, $s1, $ev7, $loc1, -15);

$movementTable->calculateqoh($s1, $loc1, $qoh);
check("QOH at loc1 reduced by transfer (60-15=45)", $qoh, 45);
$movementTable->calculateqoh($s1, $loc2, $qoh);
check("QOH at loc2 increased by transfer (0+15=15)", $qoh, 15);

// =============================================================================
echo "\n=== QOH: new stocktake resets baseline ===\n";
// =============================================================================
$ev8 = insert_event($eventTable, 'stocktake', $loc1, null, null, 'closed', '2026-02-01 08:00:00', '2026-02-01 09:00:00');
$created_event_ids[] = $ev8;
insert_movement($movementTable, $s1, $ev8, $loc1, 0, 40);

$movementTable->calculateqoh($s1, $loc1, $qoh);
check("QOH after new stocktake = new stock_qoh (40)", $qoh, 40);

$ev9 = insert_event($eventTable, 'delivery', $loc1, null, null, 'closed', '2026-02-05 10:00:00', '2026-02-05 10:15:00');
$created_event_ids[] = $ev9;
insert_movement($movementTable, $s1, $ev9, $loc1, 12);

$movementTable->calculateqoh($s1, $loc1, $qoh);
check("QOH = new_stocktake + post-stocktake delivery (40+12=52)", $qoh, 52);

// =============================================================================
echo "\n=== Stocktake pre-close: qty = stock_qoh - calculateqoh ===\n";
// =============================================================================
$ev10 = insert_event($eventTable, 'stocktake', $loc2, null, null, 'in progress', '2026-02-10 08:00:00');
$created_event_ids[] = $ev10;
$mv10_id = insert_movement($movementTable, $s1, $ev10, $loc2, 0, 20);

$movementTable->calculateqoh($s1, $loc2, $current_qoh);
check("calculateqoh at loc2 before stocktake close = 15", $current_qoh, 15);

$mv10_rec = []; $mv_n = 0;
$movementTable->selectonID($mv10_id, $mv10_rec, $mv_n);
$expected_qty = (int)$mv10_rec['stock_qoh'] - $current_qoh;
check("Pre-close qty = stock_qoh - calculateqoh = 5", $expected_qty, 5);

// Apply the qty update via execute_params (same as preclosestocktake() does).
$em2 = ''; $r2 = null; $n2 = 0;
$movementTable->execute_params(
    "UPDATE stock_movement SET qty = ? WHERE id = ?",
    [$expected_qty, $mv10_id], $r2, $n2, $em2, 1
);
$db->dbquery(
    "UPDATE stock_event SET status='closed', date_closed='2026-02-10 09:00:00' WHERE id={$ev10}",
    $r2, $n2, $em2, 1
);

$movementTable->calculateqoh($s1, $loc2, $qoh);
check("QOH at loc2 after stocktake close = counted value (20)", $qoh, 20);

// =============================================================================
echo "\n=== hasinprogressstocktake ===\n";
// =============================================================================
$ev11 = insert_event($eventTable, 'stocktake', $loc1, null, null, 'in progress', '2026-02-15 08:00:00');
$created_event_ids[] = $ev11;

$in_prog_n = 0;
$eventTable->hasinprogressstocktake($in_prog_n);
check("hasinprogressstocktake detects one active stocktake", $in_prog_n >= 1, true);

// =============================================================================
echo "\n=== cancelevent block: stocktake closed after event creation ===\n";
// =============================================================================
// ev7 (transfer) was created at 2026-01-28; ev8 stocktake closed 2026-02-01 → BLOCKED.
$ev7_rec = []; $ev7_n = 0;
$eventTable->selectonID($ev7, $ev7_rec, $ev7_n);

$st_blocking = []; $st_n = 0;
$eventTable->query_params(
    "SELECT id FROM stock_event WHERE event = 'stocktake' AND status = 'closed' AND date_closed > ?",
    [$ev7_rec['date_created']], $st_blocking, $st_n
);
check("Cancel blocked: stocktake closed after event was created", $st_n > 0, true);

// ev created after last stocktake close should NOT be blocked.
$ev12 = insert_event($eventTable, 'delivery', $loc1, null, null, 'in progress', '2099-03-01 08:00:00');
$created_event_ids[] = $ev12;
$ev12_rec = []; $ev12_n = 0;
$eventTable->selectonID($ev12, $ev12_rec, $ev12_n);

$st_blocking2 = []; $st_n2 = 0;
$eventTable->query_params(
    "SELECT id FROM stock_event WHERE event = 'stocktake' AND status = 'closed' AND date_closed > ?",
    [$ev12_rec['date_created']], $st_blocking2, $st_n2
);
check("Cancel NOT blocked for event after last stocktake close", $st_n2, 0);

// =============================================================================
echo "\n=== getstockforevent: LEFT JOIN returns all stock with existing qty ===\n";
// =============================================================================
$ev13 = insert_event($eventTable, 'stocktake', $loc1, null, null, 'in progress', '2026-03-05 08:00:00');
$created_event_ids[] = $ev13;
insert_movement($movementTable, $s1, $ev13, $loc1, 0, 33);

$stock_rows = []; $stock_n = 0;
$movementTable->getstockforevent($ev13, null, $stock_rows, $stock_n);

$row_s1 = null;
foreach ($stock_rows as $row) {
    if ((int)$row['stock_id'] === $s1) { $row_s1 = $row; break; }
}
check("getstockforevent: returns rows",               $stock_n > 0, true);
check("getstockforevent: s1 has a movement",          $row_s1 !== null && (int)($row_s1['movement_id'] ?? 0) > 0, true);
check("getstockforevent: s1 stock_qoh = 33",          $row_s1 !== null ? (int)$row_s1['stock_qoh'] : null, 33);

// =============================================================================
echo "\n=== getinprogressevent ===\n";
// =============================================================================
$found = []; $found_n = 0;
$eventTable->getinprogressevent('stocktake', $loc1, $found, $found_n);
check("getinprogressevent finds in-progress stocktake at loc1", $found_n >= 1, true);

$found_wrong = []; $found_wrong_n = 0;
$eventTable->getinprogressevent('issue', $loc1, $found_wrong, $found_wrong_n);
check("getinprogressevent: wrong type returns nothing",         $found_wrong_n, 0);

// =============================================================================
echo "\n=== Cleanup ===\n";
// =============================================================================
foreach ($created_event_ids as $eid) {
    $db->dbquery("DELETE FROM stock_movement WHERE stock_event_id = {$eid}", $r, $n, $em, 1);
    $db->dbquery("DELETE FROM stock_event WHERE id = {$eid}", $r, $n, $em, 1);
}
$db->dbquery("DELETE FROM stock_location WHERE id IN ({$loc1}, {$loc2})", $r, $n, $em, 1);
echo "  Cleaned up " . count($created_event_ids) . " events and 2 test locations.\n";

// =============================================================================
echo "\n=== Summary ===\n";
// =============================================================================
echo "  Passed: {$pass}\n";
echo "  Failed: {$fail}\n";
if ($fail === 0) { echo "  ALL TESTS PASSED\n"; }
