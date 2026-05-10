<?php
namespace apptable;
use \lib\StdLib as lib;
class StockEventTable extends \fw\database\table\MySQLTable
{
    private $trace = false;
    public function init($db, $user_id="null") {
        if ($this->trace) { echo 'Enter '.__METHOD__.'<br>'; }
        parent::init($db, $user_id);
        $this->fields = array(
            "id"              => "",
            "location1_id"    => "",
            "location2_id"    => null,   // nullable — used only for transfers
            "supplier_id"     => null,   // nullable — used only for deliveries
            "stock_client_id" => null,   // nullable — used only for issues
            "event"           => "",
            "status"          => "in progress",
            "date_created"    => null,   // set by DB DEFAULT CURRENT_TIMESTAMP
            "date_closed"     => null,
            "date_cancelled"  => null,
            "total_weight"    => null,
        );
        if ($this->trace) { echo 'Leave '.__METHOD__.'<br>'; }
    }

    // Restore nullable fields to null after clear() so they are omitted from
    // INSERT and MySQL's column defaults (NULL / CURRENT_TIMESTAMP) are applied.
    public function clear() {
        parent::clear();
        $this->fields['date_created']    = null;
        $this->fields['date_closed']     = null;
        $this->fields['date_cancelled']  = null;
        $this->fields['location2_id']    = null;
        $this->fields['supplier_id']     = null;
        $this->fields['stock_client_id'] = null;
        $this->fields['total_weight']    = null;
    }

    // Returns in-progress event(s) of a given type for a single location.
    // Used for: stocktake, adjustment, issue.
    public function getinprogressevent($event_type, $location1_id, &$results, &$numrows, $trace=false) {
        if ($this->trace || $trace) { echo 'Enter '.__METHOD__.'<br>'; }
        $query  = "SELECT se.*, l.name as location1_name";
        $query .= " FROM stock_event se";
        $query .= " JOIN stock_location l ON se.location1_id = l.id";
        $query .= " WHERE se.event = ?";
        $query .= " AND se.status = 'in progress'";
        $query .= " AND se.location1_id = ?";
        $query .= " ORDER BY se.date_created DESC";
        $success = $this->query_params($query, [$event_type, $location1_id], $results, $numrows, $trace);
        if ($this->trace || $trace) { echo 'Leave '.__METHOD__."  ({$numrows} rows)<br>"; }
        return $success;
    }

    // Returns in-progress transfer event(s) between two specific locations.
    public function getinprogresstransfer($location1_id, $location2_id, &$results, &$numrows, $trace=false) {
        if ($this->trace || $trace) { echo 'Enter '.__METHOD__.'<br>'; }
        $query  = "SELECT se.*";
        $query .= ", l1.name as location1_name, l2.name as location2_name";
        $query .= " FROM stock_event se";
        $query .= " JOIN stock_location l1 ON se.location1_id = l1.id";
        $query .= " JOIN stock_location l2 ON se.location2_id = l2.id";
        $query .= " WHERE se.event = 'transfer'";
        $query .= " AND se.status = 'in progress'";
        $query .= " AND se.location1_id = ?";
        $query .= " AND se.location2_id = ?";
        $query .= " ORDER BY se.date_created DESC";
        $success = $this->query_params($query, [$location1_id, $location2_id], $results, $numrows, $trace);
        if ($this->trace || $trace) { echo 'Leave '.__METHOD__."  ({$numrows} rows)<br>"; }
        return $success;
    }

    // Returns in-progress delivery event(s) for a given supplier.
    public function getinprogressdelivery($supplier_id, &$results, &$numrows, $trace=false) {
        if ($this->trace || $trace) { echo 'Enter '.__METHOD__.'<br>'; }
        $query  = "SELECT se.*, ss.name as supplier_name";
        $query .= " FROM stock_event se";
        $query .= " JOIN stock_supplier ss ON se.supplier_id = ss.id";
        $query .= " WHERE se.event = 'delivery'";
        $query .= " AND se.status = 'in progress'";
        $query .= " AND se.supplier_id = ?";
        $query .= " ORDER BY se.date_created DESC";
        $success = $this->query_params($query, [$supplier_id], $results, $numrows, $trace);
        if ($this->trace || $trace) { echo 'Leave '.__METHOD__."  ({$numrows} rows)<br>"; }
        return $success;
    }

    // Returns all in-progress deliveries, grouped by supplier — used to build the
    // delivery dropdown showing "Continue" vs "New Delivery" options.
    public function getallinprogressdeliveries(&$results, &$numrows, $trace=false) {
        if ($this->trace || $trace) { echo 'Enter '.__METHOD__.'<br>'; }
        $query  = "SELECT se.*, ss.name as supplier_name";
        $query .= " FROM stock_event se";
        $query .= " JOIN stock_supplier ss ON se.supplier_id = ss.id";
        $query .= " WHERE se.event = 'delivery' AND se.status = 'in progress'";
        $query .= " ORDER BY ss.name, se.date_created DESC";
        $success = $this->query($query, $results, $numrows, $trace);
        if ($this->trace || $trace) { echo 'Leave '.__METHOD__."  ({$numrows} rows)<br>"; }
        return $success;
    }

    // Returns all closed stocktake events for a given location, newest first.
    public function getclosedstocktakesforlocation($location_id, &$results, &$numrows, $trace=false) {
        if ($this->trace || $trace) { echo 'Enter '.__METHOD__.'<br>'; }
        $query  = "SELECT se.id, se.date_created";
        $query .= " FROM stock_event se";
        $query .= " WHERE se.event = 'stocktake'";
        $query .= " AND se.status = 'closed'";
        $query .= " AND se.location1_id = ?";
        $query .= " ORDER BY se.date_created DESC";
        $success = $this->query_params($query, [$location_id], $results, $numrows, $trace);
        if ($this->trace || $trace) { echo 'Leave '.__METHOD__."  ({$numrows} rows)<br>"; }
        return $success;
    }

    // Returns one row per delivery item for all closed deliveries in [from, to].
    // Optionally filtered by supplier_id (exact) or category_id (supplier's category).
    // Rows with no stock movements still appear (LEFT JOIN) with null item fields.
    // Results are ordered newest delivery first, then by supplier, category and item name.
    public function getdeliveriesreport($from, $to, $supplier_id, $category_id, &$results, &$numrows, $trace=false) {
        if ($this->trace || $trace) { echo 'Enter '.__METHOD__.'<br>'; }
        $query  = "SELECT se.id AS id";
        $query .= ", DATE(se.date_closed) AS delivery_date";
        $query .= ", ss.name AS supplier_name";
        $query .= ", se.total_weight";
        $query .= ", sl.name AS location_name";
        $query .= ", sc.Name AS category_name";
        $query .= ", s.Name AS stock_name";
        $query .= ", sm.qty";
        $query .= " FROM stock_event se";
        $query .= " JOIN stock_supplier ss ON se.supplier_id = ss.id";
        $query .= " LEFT JOIN stock_location sl ON se.location1_id = sl.id";
        $query .= " LEFT JOIN stock_movement sm ON sm.stock_event_id = se.id";
        $query .= " LEFT JOIN stock s ON sm.stock_id = s.id";
        $query .= " LEFT JOIN stock_category sc ON s.category_id = sc.id";
        $query .= " WHERE se.event = 'delivery'";
        $query .= " AND se.status = 'closed'";
        $query .= " AND DATE(se.date_closed) >= ?";
        $query .= " AND DATE(se.date_closed) <= ?";
        $params = [$from, $to];
        if (!empty($supplier_id)) {
            $query .= " AND se.supplier_id = ?";
            $params[] = $supplier_id;
        } elseif (!empty($category_id)) {
            $query .= " AND ss.supplier_category_id = ?";
            $params[] = $category_id;
        }
        $query .= " ORDER BY se.date_closed DESC, ss.name, sc.Name, s.Name";
        $success = $this->query_params($query, $params, $results, $numrows, $trace);
        if ($this->trace || $trace) { echo 'Leave '.__METHOD__."  ({$numrows} rows)<br>"; }
        return $success;
    }

    // Checks whether any stocktake event currently has status 'in progress'.
    // Used to enforce the single-active-stocktake business rule.
    public function hasinprogressstocktake(&$numrows, $trace=false) {
        if ($this->trace || $trace) { echo 'Enter '.__METHOD__.'<br>'; }
        $results = [];
        $success = $this->select("id", "event = 'stocktake' AND status = 'in progress'",
                                 "", "", "", 0, $results, $numrows, $trace);
        if ($this->trace || $trace) { echo 'Leave '.__METHOD__."  ({$numrows} rows)<br>"; }
        return $success;
    }
}
