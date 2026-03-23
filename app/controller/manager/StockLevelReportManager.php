<?php
namespace app\controller\manager;
use \lib\StdLib as lib;
class StockLevelReportManager extends \fw\controller\manager\StdManager
{
    private $trace = false;
    protected $name = "Stock Level Report";
    protected $db;
    protected $linkedobject = "";
    public function __construct(protected \apptable\StockTable $table) {
        if ($this->trace) { echo "Enter ".__METHOD__."<br>"; }
    }

    public function init($session, $trace=false) {
        parent::init($session);
    }

    public function getallrecords(&$datafields, $orderby, &$parents, &$numrows, $withlock=false, $trace=false) {
        if ($this->trace || $trace) { echo "Enter ".__METHOD__."<br>"; }
        $success = $this->table->getstockwithlevels($datafields, $numrows, $trace);
        $this->alldata = $datafields;
        $parents = [];
        if ($this->trace || $trace) { echo "Leave ".__METHOD__." ({$numrows} rows)<br>"; }
        return $success;
    }
}
