<?php
namespace app\controller\manager;
use \lib\StdLib as lib;
class StocktakeVarianceReportManager extends \fw\controller\manager\StdManager
{
    private $trace = false;
    protected $name = "Stock Reports";
    protected $db;
    protected $linkedobject = "";
    private $location_id = '';
    private $event_id    = '';

    public function __construct(protected \apptable\StockTable      $table,
                                protected \apptable\LocationTable   $locationtable,
                                protected \apptable\StockEventTable $eventtable) {
        if ($this->trace) { echo "Enter ".__METHOD__."<br>"; }
    }

    public function init($session, $trace=false) {
        parent::init($session);
        $this->locationtable->init($this->db, $this->user_id);
        $this->eventtable->init($this->db, $this->user_id);
    }

    public function setlocation($location_id) {
        $this->location_id = $location_id;
    }

    public function setevent($event_id) {
        $this->event_id = $event_id;
    }

    public function getallrecords(&$datafields, $orderby, &$parents, &$numrows, $withlock=false, $trace=false) {
        if ($this->trace || $trace) { echo "Enter ".__METHOD__."<br>"; }

        $locations = []; $locnum = 0;
        $this->locationtable->selectall($locations, $locnum, "name", $trace);

        $stocktakes = []; $stnum = 0;
        if (!empty($this->location_id)) {
            $this->eventtable->getclosedstocktakesforlocation($this->location_id, $stocktakes, $stnum, $trace);
        }

        $datafields = []; $numrows = 0;
        if (!empty($this->event_id)) {
            $this->table->getstocktakevariance($this->event_id, $datafields, $numrows, $trace);
        }
        $this->alldata = $datafields;

        $parents = [
            'locations'   => $locations,
            'location_id' => $this->location_id,
            'stocktakes'  => $stocktakes,
            'event_id'    => $this->event_id,
        ];

        if ($this->trace || $trace) { echo "Leave ".__METHOD__." ({$numrows} rows)<br>"; }
        return true;
    }
}
