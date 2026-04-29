<?php
namespace app\controller\manager;
use \lib\StdLib as lib;
class StockoutManager extends \fw\controller\manager\StdManager
{
    private $trace = false;
    protected $name = "Stock Usage";
    protected $db;
    protected $linkedobject = "";
    public function __construct(protected \apptable\StockMovementTable $table,
                                protected \apptable\StockTable         $stocktable) {
        if ($this->trace) { echo "Enter ".__METHOD__."<br>"; }
    }

    public function init($session, $trace=false) {
        parent::init($session);
        $this->stocktable->init($this->db);
    }

    public function getallrecords(&$datafields, $orderby, &$parents, &$numrows, $withlock=false, $trace=false) {
        if ($this->trace || $trace) { echo "Enter ".__METHOD__."<br>"; }
        $success = $this->table->getmovementsbyeventtype('issue', $datafields, $numrows, $trace);
        $this->alldata = $datafields;
        $this->makenames($trace);
        $success = $success && $this->getparents($parents, $trace);
        if ($this->trace || $trace) { echo "Leave ".__METHOD__." ({$numrows} rows)<br>"; }
        return $success;
    }

    protected function makenames($trace=false) {
        foreach ($this->alldata as $record) {
            $date = substr($record["movement_date"] ?? '', 0, 10);
            $this->names[$record["id"]] = "{$date} – {$record['stock_name']} ({$record['qty']} {$record['unit']})";
        }
    }

    protected function getparents(&$parents, $trace=false) {
        $stockitems = [];
        $numrows = 0;
        $success = $this->stocktable->selectall($stockitems, $numrows, "Name", $trace);
        $parents = $stockitems;
        return $success;
    }

    protected function setdefaults(&$fields, $trace=false) {
        $fields["movement_date"] = date('Y-m-d H:i:s');
        $fields["unit_qty"]      = $fields["unit_qty"] ?: 1;
    }
}
