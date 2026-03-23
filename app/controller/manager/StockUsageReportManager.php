<?php
namespace app\controller\manager;
use \lib\StdLib as lib;
class StockUsageReportManager extends \fw\controller\manager\StdManager
{
    private $trace = false;
    protected $name = "Stock Usage Report";
    protected $db;
    protected $linkedobject = "";
    private $from = '';
    private $to   = '';

    public function __construct(protected \apptable\StockMovementTable $table) {}

    public function init($session, $trace=false) {
        parent::init($session);
    }

    public function setdaterange($from, $to) {
        $this->from = $from;
        $this->to   = $to;
    }

    public function getallrecords(&$datafields, $orderby, &$parents, &$numrows, $withlock=false, $trace=false) {
        if ($this->trace || $trace) { echo "Enter ".__METHOD__."<br>"; }
        if ($this->from !== '' && $this->to !== '') {
            $success = $this->table->getusagereport($this->from, $this->to, $datafields, $numrows, $trace);
        } else {
            $datafields = [];
            $numrows = 0;
            $success = true;
        }
        $this->alldata = $datafields;
        $parents = [];
        if ($this->trace || $trace) { echo "Leave ".__METHOD__." ({$numrows} rows)<br>"; }
        return $success;
    }
}
