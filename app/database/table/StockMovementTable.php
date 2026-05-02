<?php
namespace apptable;
use \lib\StdLib as lib;
class StockMovementTable extends \fw\database\table\MySQLTable
{
    private $trace = false;
    public function init($db, $user_id="null") {
        if ($this->trace) { echo 'Enter '.__METHOD__.'<br>'; }
        parent::init($db, $user_id);
        $this->fields = array(
            "id"             => "",
            "stock_id"       => "",
            "qty"            => "",
            "unit"           => "",
            "unit_qty"       => "1",
            "stock_qoh"      => null,   // nullable: actual count recorded during stocktake
            "stock_event_id" => null,   // nullable: FK to stock_event (new-style movements only)
            "location_id"    => null,   // nullable: FK to location (new-style movements only)
            "movement_date"  => "",
        );
        if ($this->trace) { echo 'Leave '.__METHOD__.'<br>'; }
    }

    // Returns all closed movements of the given event type, joined to stock.
    // Replaces the old getmovementsbytype() which filtered on movement_type.
    public function getmovementsbyeventtype($event_type, &$results, &$numrows, $trace=false) {
        if ($this->trace || $trace) { echo 'Enter '.__METHOD__.'<br>'; }
        $event_type = $this->real_escape_string($event_type);
        $query  = "SELECT sm.id, sm.stock_id, sm.qty, sm.unit, sm.unit_qty,";
        $query .= " sm.movement_date, s.Name as stock_name,";
        $query .= " se.event, se.status, se.date_created as event_date";
        $query .= " FROM stock_movement sm";
        $query .= " JOIN stock s ON sm.stock_id = s.id";
        $query .= " JOIN stock_event se ON sm.stock_event_id = se.id";
        $query .= " WHERE se.event = '{$event_type}' AND se.status = 'closed'";
        $query .= " ORDER BY sm.id DESC";
        $success = $this->query($query, $results, $numrows, $trace);
        if ($this->trace || $trace) { echo 'Leave '.__METHOD__."  ({$numrows} rows)<br>"; }
        return $success;
    }

    public function insertstocktake($stock_id, $qty, $unit, $unit_qty, &$id, &$errormessage, $trace=false) {
        if ($this->trace || $trace) { echo 'Enter '.__METHOD__.'<br>'; }
        $this->clear();
        $this->setfield("stock_id",      $stock_id);
        $this->setfield("qty",           $qty);
        $this->setfield("unit",          $unit);
        $this->setfield("unit_qty",      $unit_qty ?: 1);
        $this->setfield("movement_date", date('Y-m-d H:i:s'));
        $success = $this->insert(true, $id, $trace, $errormessage);
        if ($this->trace || $trace) { echo 'Leave '.__METHOD__."  id={$id}<br>"; }
        return $success;
    }

    // Returns all movements for a stock_event, joined to stock and category.
    // Optionally filtered by category_id (pass 0 or null for all categories).
    public function getmovementsforevent($event_id, $category_id, &$results, &$numrows, $trace=false) {
        if ($this->trace || $trace) { echo 'Enter '.__METHOD__.'<br>'; }
        $event_id = $this->real_escape_string($event_id);
        $query  = "SELECT sm.id, sm.stock_id, sm.qty, sm.stock_qoh,";
        $query .= " sm.unit, sm.unit_qty, sm.location_id, sm.stock_event_id,";
        $query .= " s.Name as stock_name, s.category_id,";
        $query .= " sc.Name as category_name";
        $query .= " FROM stock_movement sm";
        $query .= " JOIN stock s ON sm.stock_id = s.id";
        $query .= " LEFT JOIN stock_category sc ON s.category_id = sc.id";
        $query .= " WHERE sm.stock_event_id = '{$event_id}'";
        if (!empty($category_id)) {
            $category_id = $this->real_escape_string($category_id);
            $query .= " AND s.category_id = '{$category_id}'";
        }
        $query .= " ORDER BY sc.Name, s.Name";
        $success = $this->query($query, $results, $numrows, $trace);
        if ($this->trace || $trace) { echo 'Leave '.__METHOD__."  ({$numrows} rows)<br>"; }
        return $success;
    }

    // Returns all stock items for a category (or all categories if $category_id is empty),
    // left-joined to any existing movement for the given event so the form can show
    // existing values and know whether to INSERT or UPDATE.
    // $supplier_id: if non-empty and category_id is empty, restricts to stock categories supplied by that supplier.
    public function getstockforevent($event_id, $category_id, &$results, &$numrows, $trace=false, $supplier_id='') {
        if ($this->trace || $trace) { echo 'Enter '.__METHOD__.'<br>'; }
        $event_id = $this->real_escape_string($event_id);
        $query  = "SELECT s.id as stock_id, s.Name as stock_name, s.category_id,";
        $query .= " sc.Name as category_name,";
        $query .= " sm.id as movement_id, sm.qty, sm.stock_qoh, sm.location_id";
        $query .= " FROM stock s";
        $query .= " LEFT JOIN stock_category sc ON s.category_id = sc.id";
        $query .= " LEFT JOIN stock_movement sm";
        $query .= "   ON sm.stock_id = s.id AND sm.stock_event_id = '{$event_id}'";
        if (!empty($category_id)) {
            $cat_safe = $this->real_escape_string($category_id);
            $query .= " WHERE s.category_id = '{$cat_safe}'";
        } elseif (!empty($supplier_id)) {
            $sup_safe = $this->real_escape_string($supplier_id);
            $query .= " WHERE s.category_id IN (SELECT stock_category_id FROM stock_supplier_category WHERE stock_supplier_id = '{$sup_safe}')";
        }
        $query .= " ORDER BY sc.Name, s.Name";
        $success = $this->query($query, $results, $numrows, $trace);
        if ($this->trace || $trace) { echo 'Leave '.__METHOD__."  ({$numrows} rows)<br>"; }
        return $success;
    }

    // Returns all stock items for a transfer event.
    // LEFT JOINs stock_item_location for the TO location to get target_qty.
    // Includes inline correlated QOH for the TO location as current_qoh.
    // Required = target_qty - current_qoh is computed by the caller (PHP).
    public function getstockfortransfer($event_id, $category_id, $from_loc_id, $to_loc_id, &$results, &$numrows, $trace=false) {
        if ($this->trace || $trace) { echo 'Enter '.__METHOD__.'<br>'; }
        $event_id = $this->real_escape_string($event_id);
        $to_loc   = $this->real_escape_string($to_loc_id);

        $st_date = "(SELECT se_st2.date_created"
                 . " FROM stock_movement sm_st2"
                 . " JOIN stock_event se_st2 ON sm_st2.stock_event_id = se_st2.id"
                 . " WHERE sm_st2.stock_id = s.id AND sm_st2.location_id = '{$to_loc}'"
                 . "   AND se_st2.event = 'stocktake' AND se_st2.status = 'closed'"
                 . " ORDER BY se_st2.date_created DESC LIMIT 1)";

        $query  = "SELECT s.id as stock_id, s.Name as stock_name, s.category_id,";
        $query .= " sc.Name as category_name,";
        $query .= " sm.id as movement_id, sm.qty, sm.stock_qoh, sm.location_id,";
        $query .= " sil_to.target_qty AS target_qty,";
        $query .= " (COALESCE((SELECT sm_st.stock_qoh";
        $query .= "   FROM stock_movement sm_st";
        $query .= "   JOIN stock_event se_st ON sm_st.stock_event_id = se_st.id";
        $query .= "   WHERE sm_st.stock_id = s.id AND sm_st.location_id = '{$to_loc}'";
        $query .= "     AND se_st.event = 'stocktake' AND se_st.status = 'closed'";
        $query .= "   ORDER BY se_st.date_created DESC LIMIT 1), 0)";
        $query .= " + COALESCE((SELECT SUM(CASE se2.event WHEN 'issue' THEN -sm2.qty ELSE sm2.qty END)";
        $query .= "   FROM stock_movement sm2";
        $query .= "   JOIN stock_event se2 ON sm2.stock_event_id = se2.id";
        $query .= "   WHERE sm2.stock_id = s.id AND sm2.location_id = '{$to_loc}'";
        $query .= "     AND se2.event IN ('delivery','transfer','adjustment','issue')";
        $query .= "     AND se2.status = 'closed'";
        $query .= "     AND se2.date_created > COALESCE({$st_date}, '1970-01-01 00:00:00')";
        $query .= "   ), 0)";
        $query .= " ) AS current_qoh";
        $query .= " FROM stock s";
        $query .= " LEFT JOIN stock_category sc ON s.category_id = sc.id";
        $query .= " LEFT JOIN stock_movement sm ON sm.stock_id = s.id AND sm.stock_event_id = '{$event_id}'";
        $query .= " LEFT JOIN stock_item_location sil_to ON sil_to.stock_id = s.id AND sil_to.stock_location_id = '{$to_loc}'";
        if (!empty($category_id)) {
            $cat_safe = $this->real_escape_string($category_id);
            $query .= " WHERE s.category_id = '{$cat_safe}'";
        }
        $query .= " ORDER BY sc.Name, s.Name";
        $success = $this->query($query, $results, $numrows, $trace);
        if ($this->trace || $trace) { echo 'Leave '.__METHOD__."  ({$numrows} rows)<br>"; }
        return $success;
    }

    // Calculates the current QOH for a single stock item at a single location.
    // QOH = stock_qoh from most recent closed stocktake
    //     + deliveries + transfers + adjustments - issues
    // (all from closed events dated after that stocktake).
    public function calculateqoh($stock_id, $location_id, &$qoh, $trace=false) {
        if ($this->trace || $trace) { echo 'Enter '.__METHOD__.'<br>'; }
        $sid = $this->real_escape_string($stock_id);
        $lid = $this->real_escape_string($location_id);

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

        $results = [];
        $numrows = 0;
        $success = $this->query($query, $results, $numrows, $trace);
        $qoh = $success && !empty($results) ? (int)$results[0]['qoh'] : 0;
        if ($this->trace || $trace) { echo 'Leave '.__METHOD__."  qoh={$qoh}<br>"; }
        return $success;
    }

    public function getmovementforstockandevent($stock_id, $event_id, &$record, &$numrows, $trace=false) {
        if ($this->trace || $trace) { echo 'Enter '.__METHOD__.'<br>'; }
        $records = [];
        $success = $this->selectonmultiplefields(
            ["stock_id" => $stock_id, "stock_event_id" => $event_id],
            $records, $numrows, false, $trace
        );
        $record = $success && !empty($records) ? $records[0] : [];
        if ($this->trace || $trace) { echo 'Leave '.__METHOD__."  ({$numrows} rows)<br>"; }
        return $success;
    }

    public function getusagereport($from, $to, &$results, &$numrows, $location_id='', $trace=false) {
        if ($this->trace || $trace) { echo 'Enter '.__METHOD__.'<br>'; }
        $from = $this->real_escape_string($from);
        $to   = $this->real_escape_string($to);
        $query  = "SELECT s.id, s.Name, s.Code, sc.Name as category_name,";
        $query .= " SUM(sm.qty) as total_used";
        $query .= " FROM stock_movement sm";
        $query .= " JOIN stock s ON sm.stock_id = s.id";
        $query .= " LEFT JOIN stock_category sc ON s.category_id = sc.id";
        $query .= " JOIN stock_event se ON sm.stock_event_id = se.id";
        $query .= " WHERE se.event = 'issue' AND se.status = 'closed'";
        $query .= " AND DATE(sm.movement_date) >= '{$from}'";
        $query .= " AND DATE(sm.movement_date) <= '{$to}'";
        if (!empty($location_id)) {
            $lid    = $this->real_escape_string($location_id);
            $query .= " AND sm.location_id = '{$lid}'";
        }
        $query .= " GROUP BY s.id, s.Name, s.Code, sc.Name";
        $query .= " ORDER BY sc.Name, s.Name";
        $success = $this->query($query, $results, $numrows, $trace);
        if ($this->trace || $trace) { echo 'Leave '.__METHOD__."  ({$numrows} rows)<br>"; }
        return $success;
    }
}
