<?php
namespace app\controller\manager;
use \lib\StdLib as lib;
class LocationManager extends \fw\controller\manager\StdManager
{
    private $trace = false;
    protected $name = "Location";
    protected $db;
    protected $linkedobject = "";
    public function __construct(
        protected \apptable\LocationTable   $table,
        protected \apptable\StockEventTable $stockeventtable
    ) {
        if ($this->trace) { echo "Enter ".__METHOD__."<br>"; }
    }

    public function init($session, $trace=false) {
        parent::init($session);
        $this->stockeventtable->init($this->db, $this->user_id);
    }

    public function delete(&$errormessage="", $trace=false) {
        $id      = $this->table->real_escape_string($this->requestdata['id']);
        $results = [];
        $numrows = 0;
        $this->stockeventtable->select("id", "location1_id='$id' OR location2_id='$id'", "", "", "", 0, $results, $numrows);
        if ($numrows > 0) {
            $errormessage = "Cannot delete this location — it is used by $numrows stock event(s). Remove those events first.";
            return false;
        }
        return parent::delete($errormessage, $trace);
    }
}
