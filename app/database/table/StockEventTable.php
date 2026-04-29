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
    }

    // Returns in-progress event(s) of a given type for a single location.
    // Used for: stocktake, adjustment, issue.
    public function getinprogressevent($event_type, $location1_id, &$results, &$numrows, $trace=false) {
        if ($this->trace || $trace) { echo 'Enter '.__METHOD__.'<br>'; }
        $event_type  = $this->real_escape_string($event_type);
        $location1_id = $this->real_escape_string($location1_id);
        $query  = "SELECT se.*, l.name as location1_name";
        $query .= " FROM stock_event se";
        $query .= " JOIN location l ON se.location1_id = l.id";
        $query .= " WHERE se.event = '{$event_type}'";
        $query .= " AND se.status = 'in progress'";
        $query .= " AND se.location1_id = '{$location1_id}'";
        $query .= " ORDER BY se.date_created DESC";
        $success = $this->query($query, $results, $numrows, $trace);
        if ($this->trace || $trace) { echo 'Leave '.__METHOD__."  ({$numrows} rows)<br>"; }
        return $success;
    }

    // Returns in-progress transfer event(s) between two specific locations.
    public function getinprogresstransfer($location1_id, $location2_id, &$results, &$numrows, $trace=false) {
        if ($this->trace || $trace) { echo 'Enter '.__METHOD__.'<br>'; }
        $location1_id = $this->real_escape_string($location1_id);
        $location2_id = $this->real_escape_string($location2_id);
        $query  = "SELECT se.*";
        $query .= ", l1.name as location1_name, l2.name as location2_name";
        $query .= " FROM stock_event se";
        $query .= " JOIN location l1 ON se.location1_id = l1.id";
        $query .= " JOIN location l2 ON se.location2_id = l2.id";
        $query .= " WHERE se.event = 'transfer'";
        $query .= " AND se.status = 'in progress'";
        $query .= " AND se.location1_id = '{$location1_id}'";
        $query .= " AND se.location2_id = '{$location2_id}'";
        $query .= " ORDER BY se.date_created DESC";
        $success = $this->query($query, $results, $numrows, $trace);
        if ($this->trace || $trace) { echo 'Leave '.__METHOD__."  ({$numrows} rows)<br>"; }
        return $success;
    }

    // Returns in-progress delivery event(s) for a given supplier.
    public function getinprogressdelivery($supplier_id, &$results, &$numrows, $trace=false) {
        if ($this->trace || $trace) { echo 'Enter '.__METHOD__.'<br>'; }
        $supplier_id = $this->real_escape_string($supplier_id);
        $query  = "SELECT se.*, ss.name as supplier_name";
        $query .= " FROM stock_event se";
        $query .= " JOIN stock_supplier ss ON se.supplier_id = ss.id";
        $query .= " WHERE se.event = 'delivery'";
        $query .= " AND se.status = 'in progress'";
        $query .= " AND se.supplier_id = '{$supplier_id}'";
        $query .= " ORDER BY se.date_created DESC";
        $success = $this->query($query, $results, $numrows, $trace);
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
