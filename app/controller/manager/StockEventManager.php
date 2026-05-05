<?php
namespace app\controller\manager;
use \lib\StdLib as lib;
class StockEventManager extends \fw\controller\manager\StdManager
{
    private   $trace = false;
    protected $name  = "Stock Event";
    protected $db;
    protected $linkedobject = "";

    public function __construct(
        protected \apptable\StockEventTable    $table,
        protected \apptable\StockMovementTable $movementtable,
        protected \apptable\StockLocationTable  $locationtable,
        protected \apptable\StockTable         $stocktable
    ) {
        if ($this->trace) { echo "Enter ".__METHOD__."<br>"; }
    }

    public function init($session, $trace=false) {
        if ($this->trace || $trace) { echo "Enter ".__METHOD__."<br>"; }
        parent::init($session);
        $this->movementtable->init($this->db, $this->user_id);
        $this->locationtable->init($this->db, $this->user_id);
        $this->stocktable->init($this->db, $this->user_id);
        if ($this->trace || $trace) { echo "Leave ".__METHOD__."<br>"; }
    }

    // =========================================================================
    // EVENT CREATION
    // =========================================================================

    // Creates a new stock_event record of the given type.
    // Enforces the single-active-stocktake rule (section 2.1).
    // On success, $id is set to the new event's id.
    public function createevent($event_type, $location1_id, $location2_id, $supplier_id, $stock_client_id, &$id, &$errormessage) {
        if ($this->trace) { echo "Enter ".__METHOD__." type={$event_type}<br>"; }

        if ($event_type === 'stocktake') {
            $numrows = 0;
            $this->table->hasinprogressstocktake($numrows);
            if ($numrows > 0) {
                $errormessage = "A stocktake is already in progress. Close or cancel it before starting a new one.";
                return false;
            }
        }

        $this->table->clear();
        $this->table->setfield("event",           $event_type);
        $this->table->setfield("location1_id",    $location1_id);
        $this->table->setfield("location2_id",    $location2_id    ?: "null");
        $this->table->setfield("supplier_id",     $supplier_id     ?: "null");
        $this->table->setfield("stock_client_id", $stock_client_id ?: "null");
        $this->table->setfield("status",          "in progress");
        $success = $this->table->insert(true, $id, false, $errormessage);

        if ($this->trace) { echo "Leave ".__METHOD__." id={$id} OK={$success}<br>"; }
        return $success;
    }

    // Retrieves the in-progress event matching the given criteria.
    // For stocktake / adjustment / issue: pass $location2_id = null, $supplier_id = null.
    // For transfer: pass both location IDs.
    // For delivery: pass $supplier_id; location1_id is the receiving location.
    // Returns the event record in $result (empty array if none found).
    public function getinprogressevent($event_type, $location1_id, $location2_id, $supplier_id, &$result, &$numrows) {
        if ($this->trace) { echo "Enter ".__METHOD__." type={$event_type}<br>"; }
        $results = [];

        if ($event_type === 'transfer') {
            $success = $this->table->getinprogresstransfer($location1_id, $location2_id, $results, $numrows);
        } elseif ($event_type === 'delivery') {
            $success = $this->table->getinprogressdelivery($supplier_id, $results, $numrows);
        } else {
            $success = $this->table->getinprogressevent($event_type, $location1_id, $results, $numrows);
        }

        $result = (!empty($results)) ? $results[0] : [];
        if ($this->trace) { echo "Leave ".__METHOD__." found={$numrows}<br>"; }
        return $success;
    }

    // =========================================================================
    // MOVEMENT SAVE  (called from AJAX handler for every quantity field blur)
    // =========================================================================

    // Inserts or updates the movement for one stock item in one event.
    // $movement_id: 0 means insert; > 0 means update that record.
    // For stocktake events, $value is stored in stock_qoh (the actual count);
    // qty is set to 0 and recalculated on close.
    // For all other event types, $value is stored in qty.
    // On insert, $movement_id is set to the new record's id.
    public function savemovement($stock_id, $event_id, $location_id, $value, $event_type, &$movement_id, &$errormessage) {
        if ($this->trace) { echo "Enter ".__METHOD__." stock={$stock_id} event={$event_id} val={$value}<br>"; }

        $movement_id = (int)$movement_id;

        // If movement_id is 0, look up whether one already exists — this handles
        // concurrent saves (explicit + blur) and the page-reload resume case.
        if ($movement_id === 0) {
            $existing = [];
            $existing_n = 0;
            $this->movementtable->getmovementforstockandevent($stock_id, $event_id, $existing, $existing_n);
            if (!empty($existing) && isset($existing['id'])) {
                $movement_id = (int)$existing['id'];
            }
        }

        // If location_id is missing/zero, derive it from the parent event.
        // Transfers store movements at the TO location (location2_id); all
        // other event types use location1_id.
        if (empty($location_id)) {
            $ev = []; $evn = 0;
            $this->table->selectonID($event_id, $ev, $evn);
            $location_id = ($event_type === 'transfer')
                ? ($ev['location2_id'] ?? 0)
                : ($ev['location1_id'] ?? 0);
        }

        if ($movement_id > 0) {
            $val_safe = $this->movementtable->real_escape_string($value);
            $id_safe  = $this->movementtable->real_escape_string($movement_id);
            $set   = ($event_type === 'stocktake') ? "`stock_qoh` = '{$val_safe}'" : "`qty` = '{$val_safe}'";
            $where = "id = '{$id_safe}'";
            $numrows = 0;
            $success = $this->movementtable->update($set, $where, $numrows, $errormessage);
        } else {
            $this->movementtable->clear();
            $this->movementtable->setfield("stock_id",       $stock_id);
            $this->movementtable->setfield("stock_event_id", $event_id);
            $this->movementtable->setfield("location_id",    $location_id);
            $this->movementtable->setfield("unit",           "");
            $this->movementtable->setfield("unit_qty",       "1");
            $this->movementtable->setfield("movement_date",  date('Y-m-d H:i:s'));
            if ($event_type === 'stocktake') {
                $this->movementtable->setfield("stock_qoh", $value);
                $this->movementtable->setfield("qty",       "0");
            } else {
                $this->movementtable->setfield("qty",       $value);
            }
            $movement_id = 0;
            $success = $this->movementtable->insert(true, $movement_id, false, $errormessage);
        }

        if ($this->trace) { echo "Leave ".__METHOD__." movement_id={$movement_id} OK={$success}<br>"; }
        return $success;
    }

    // =========================================================================
    // QOH CALCULATION  (section 2.3)
    // =========================================================================

    // Calculates the current QOH for a stock item at a location.
    // Delegates to StockMovementTable::calculateqoh().
    public function calculateqoh($stock_id, $location_id, &$qoh) {
        return $this->movementtable->calculateqoh($stock_id, $location_id, $qoh, $this->trace);
    }

    // =========================================================================
    // WEIGHT SAVE  (delivery only)
    // =========================================================================

    public function saveweight($event_id, $weight, &$errormessage) {
        $id_safe     = $this->table->real_escape_string($event_id);
        $weight_safe = $this->table->real_escape_string($weight);
        $weight_val  = ($weight !== '' && $weight !== null) ? "'{$weight_safe}'" : "NULL";
        $set         = "`total_weight` = {$weight_val}";
        $where       = "id = '{$id_safe}'";
        $numrows     = 0;
        return $this->table->update($set, $where, $numrows, $errormessage);
    }

    // =========================================================================
    // CLOSE EVENT  (section 2.2)
    // =========================================================================

    // Closes the event identified by $event_id.
    // Applies type-specific pre-close logic before setting status = 'closed'.
    public function closeevent($event_id, &$errormessage) {
        if ($this->trace) { echo "Enter ".__METHOD__." event={$event_id}<br>"; }

        $event   = [];
        $numrows = 0;
        if (!$this->table->selectonID($event_id, $event, $numrows)) {
            $errormessage = "Stock event {$event_id} not found.";
            return false;
        }

        $success = true;
        switch ($event['event']) {
            case 'stocktake':
                $success = $this->preclosestocktake($event, $errormessage);
                break;
            case 'transfer':
                $success = $this->preclosetransfer($event, $errormessage);
                break;
            // delivery, adjustment, issue: no pre-close actions required
        }

        if ($success) {
            $now      = date('Y-m-d H:i:s');
            $id_safe  = $this->table->real_escape_string($event_id);
            $set      = "`status` = 'closed', `date_closed` = '{$now}'";
            $where    = "id = '{$id_safe}'";
            $numrows  = 0;
            $success  = $this->table->update($set, $where, $numrows, $errormessage);
        }

        // For stocktakes at uncontrolled-issues locations: generate variance issue and cancel.
        if ($success && $event['event'] === 'stocktake') {
            $success = $this->postclosestocktakeifuncontrolled($event, $errormessage);
        }

        if ($this->trace) { echo "Leave ".__METHOD__." OK={$success}<br>"; }
        return $success;
    }

    // Stocktake pre-close (section 2.2.1):
    // For each movement, qty = stock_qoh - current_qoh (where current_qoh
    // excludes the in-progress stocktake, which it does naturally since only
    // closed events count toward QOH).
    private function preclosestocktake($event, &$errormessage) {
        if ($this->trace) { echo "Enter ".__METHOD__."<br>"; }
        $movements = [];
        $numrows   = 0;
        $this->movementtable->getmovementsforevent($event['id'], null, $movements, $numrows);

        $success = true;
        foreach ($movements as $movement) {
            $current_qoh = 0;
            $this->calculateqoh($movement['stock_id'], $movement['location_id'], $current_qoh);
            $qty      = (int)$movement['stock_qoh'] - $current_qoh;
            $qty_safe = $this->movementtable->real_escape_string($qty);
            $id_safe  = $this->movementtable->real_escape_string($movement['id']);
            $set      = "`qty` = '{$qty_safe}'";
            $where    = "id = '{$id_safe}'";
            $numrows  = 0;
            $success  = $success && $this->movementtable->update($set, $where, $numrows, $errormessage);
        }
        if ($this->trace) { echo "Leave ".__METHOD__." OK={$success}<br>"; }
        return $success;
    }

    // Transfer pre-close (section 2.2.2):
    // The existing movements are linked to the "To" location with positive qty.
    // Create a mirrored movement for each: negative qty, linked to the "From" location.
    private function preclosetransfer($event, &$errormessage) {
        if ($this->trace) { echo "Enter ".__METHOD__."<br>"; }
        $movements = [];
        $numrows   = 0;
        $this->movementtable->getmovementsforevent($event['id'], null, $movements, $numrows);

        $from_location_id = $event['location1_id'];
        $success = true;
        foreach ($movements as $movement) {
            $this->movementtable->clear();
            $this->movementtable->setfield("stock_id",       $movement['stock_id']);
            $this->movementtable->setfield("stock_event_id", $event['id']);
            $this->movementtable->setfield("location_id",    $from_location_id);
            $this->movementtable->setfield("qty",            -(int)$movement['qty']);
            $this->movementtable->setfield("unit",           $movement['unit'] ?? "");
            $this->movementtable->setfield("unit_qty",       $movement['unit_qty'] ?: "1");
            $this->movementtable->setfield("movement_date",  date('Y-m-d H:i:s'));
            $new_id = 0;
            $success = $success && $this->movementtable->insert(true, $new_id, false, $errormessage);
        }
        if ($this->trace) { echo "Leave ".__METHOD__." OK={$success}<br>"; }
        return $success;
    }

    // For stocktakes at locations with uncontrolled_issues = true:
    // create a closed issue event (dated 1 minute before the stocktake) with
    // movements qty = variance for every item that has a non-zero variance.
    // The stocktake remains closed as the new baseline; the issue sits just before
    // it in the timeline, capturing the untracked consumption.
    // If all variances are zero, no issue event is created.
    private function postclosestocktakeifuncontrolled($event, &$errormessage) {
        if ($this->trace) { echo "Enter ".__METHOD__." event={$event['id']}<br>"; }

        $loc = []; $loc_n = 0;
        $this->locationtable->selectonID($event['location1_id'], $loc, $loc_n);
        if (empty($loc['uncontrolled_issues'])) {
            if ($this->trace) { echo "Leave ".__METHOD__." (controlled location)<br>"; }
            return true;
        }

        $variance_rows = []; $var_n = 0;
        if (!$this->stocktable->getstocktakevariance($event['id'], $variance_rows, $var_n)) {
            $errormessage = "Could not compute variance for stocktake {$event['id']}.";
            return false;
        }

        $nonzero = array_values(array_filter($variance_rows, fn($r) => $r['variance'] != 0));
        if (empty($nonzero)) {
            if ($this->trace) { echo "Leave ".__METHOD__." (zero variance — stocktake stays closed)<br>"; }
            return true;
        }

        // Create a new closed issue event dated 1 minute before the stocktake.
        $issue_date   = date('Y-m-d H:i:s', strtotime($event['date_created']) - 60);
        $new_event_id = 0;
        $this->table->clear();
        $this->table->setfield("event",        "issue");
        $this->table->setfield("location1_id", $event['location1_id']);
        $this->table->setfield("date_created", $issue_date);
        $this->table->setfield("status",       "closed");
        $this->table->setfield("date_closed",  date('Y-m-d H:i:s'));
        if (!$this->table->insert(true, $new_event_id, false, $errormessage)) {
            return false;
        }

        // Create one movement per non-zero-variance item; qty = variance.
        foreach ($nonzero as $row) {
            $this->movementtable->clear();
            $this->movementtable->setfield("stock_id",       $row['id']);
            $this->movementtable->setfield("stock_event_id", $new_event_id);
            $this->movementtable->setfield("location_id",    $event['location1_id']);
            $this->movementtable->setfield("unit",           "");
            $this->movementtable->setfield("unit_qty",       "1");
            $this->movementtable->setfield("movement_date",  $issue_date);
            $this->movementtable->setfield("qty",            $row['variance']);
            $mid = 0;
            if (!$this->movementtable->insert(true, $mid, false, $errormessage)) {
                return false;
            }
        }

        if ($this->trace) { echo "Leave ".__METHOD__." OK=1 issue_event={$new_event_id}<br>"; }
        return true;
    }

    // =========================================================================
    // STOCK QUERY  (used by AJAX getstock handler)
    // =========================================================================

    // Returns all in-progress delivery events — used to enrich the delivery supplier dropdown.
    public function getallinprogressdeliveries(&$results, &$numrows) {
        return $this->table->getallinprogressdeliveries($results, $numrows, $this->trace);
    }

    // Returns stock items for an event, optionally filtered by category or supplier.
    // Transfer events use a dedicated query that includes target_qty and current_qoh.
    public function getstockforevent($event_id, $category_id, &$results, &$numrows, $supplier_id='') {
        $ev = []; $evn = 0;
        if ($this->table->selectonID($event_id, $ev, $evn) && ($ev['event'] ?? '') === 'transfer') {
            return $this->movementtable->getstockfortransfer(
                $event_id, $category_id,
                $ev['location1_id'] ?? 0, $ev['location2_id'] ?? 0,
                $results, $numrows, $this->trace
            );
        }
        return $this->movementtable->getstockforevent($event_id, $category_id, $results, $numrows, $this->trace, $supplier_id);
    }

    // =========================================================================
    // CANCEL EVENT  (section 3.3 step 5)
    // =========================================================================

    // Cancels an event: deletes all its movements and marks it cancelled.
    // Blocked if any stocktake has been closed since this event was created.
    public function cancelevent($event_id, &$errormessage) {
        if ($this->trace) { echo "Enter ".__METHOD__." event={$event_id}<br>"; }

        $event   = [];
        $numrows = 0;
        if (!$this->table->selectonID($event_id, $event, $numrows)) {
            $errormessage = "Stock event {$event_id} not found.";
            return false;
        }

        // Block if a stocktake was closed after this event was created
        $date_safe   = $this->table->real_escape_string($event['date_created']);
        $id_safe     = $this->table->real_escape_string($event_id);
        $st_results  = [];
        $st_numrows  = 0;
        $this->table->select(
            "id",
            "event = 'stocktake' AND status = 'closed' AND date_closed > '{$date_safe}'",
            "", "", "", 0, $st_results, $st_numrows
        );
        if ($st_numrows > 0) {
            $errormessage = "This event cannot be cancelled: a stocktake was completed after it was created.";
            return false;
        }

        // Delete all movements linked to this event
        $where   = "stock_event_id = '{$id_safe}'";
        $success = $this->movementtable->delete($where, $numrows, false, $errormessage);

        if ($success) {
            $now     = date('Y-m-d H:i:s');
            $set     = "`status` = 'cancelled', `date_cancelled` = '{$now}'";
            $where   = "id = '{$id_safe}'";
            $numrows = 0;
            $success = $this->table->update($set, $where, $numrows, $errormessage);
        }

        if ($this->trace) { echo "Leave ".__METHOD__." OK={$success}<br>"; }
        return $success;
    }
}
