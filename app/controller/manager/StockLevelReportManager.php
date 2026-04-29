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
    private $as_at       = '';

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

    // $as_at: MySQL datetime string 'YYYY-MM-DD HH:MM:SS', or '' for current time.
    public function setasat($as_at) {
        $this->as_at = $as_at;
    }

    public function getallrecords(&$datafields, $orderby, &$parents, &$numrows, $withlock=false, $trace=false) {
        if ($this->trace || $trace) { echo "Enter ".__METHOD__."<br>"; }
        $success = $this->table->getstockwithlevels($datafields, $numrows, $this->location_id, $this->as_at, $trace);
        $this->alldata = $datafields;

        $locations = [];
        $locnum    = 0;
        $this->locationtable->selectall($locations, $locnum, "name", $trace);
        $parents = [
            'locations'   => $locations,
            'location_id' => $this->location_id,
            'as_at'       => $this->as_at,
        ];

        if ($this->trace || $trace) { echo "Leave ".__METHOD__." ({$numrows} rows)<br>"; }
        return $success;
    }
}
