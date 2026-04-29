<?php
namespace app\controller\manager;
use \lib\StdLib as lib;
class StockLevelReportManager extends \fw\controller\manager\StdManager
{
    private $trace = false;
    protected $name = "Stock Level Report";
    protected $db;
    protected $linkedobject = "";
    private $location_id = '';

    public function __construct(protected \apptable\StockTable    $table,
                                protected \apptable\LocationTable $locationtable) {
        if ($this->trace) { echo "Enter ".__METHOD__."<br>"; }
    }

    public function init($session, $trace=false) {
        parent::init($session);
        $this->locationtable->init($this->db, $this->user_id);
    }

    public function setlocation($location_id) {
        $this->location_id = $location_id;
    }

    public function getallrecords(&$datafields, $orderby, &$parents, &$numrows, $withlock=false, $trace=false) {
        if ($this->trace || $trace) { echo "Enter ".__METHOD__."<br>"; }
        $success = $this->table->getstockwithlevels($datafields, $numrows, $this->location_id, $trace);
        $this->alldata = $datafields;

        $locations = [];
        $locnum    = 0;
        $this->locationtable->selectall($locations, $locnum, "name", $trace);
        $parents = [
            'locations'   => $locations,
            'location_id' => $this->location_id,
        ];

        if ($this->trace || $trace) { echo "Leave ".__METHOD__." ({$numrows} rows)<br>"; }
        return $success;
    }
}
