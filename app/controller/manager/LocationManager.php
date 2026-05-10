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
        protected \apptable\StockLocationTable     $table,
        protected \apptable\StockEventTable        $stockeventtable,
        protected \apptable\StockItemLocationTable $itemlocationtable
    ) {
        if ($this->trace) { echo "Enter ".__METHOD__."<br>"; }
    }

    public function init($session, $trace=false) {
        parent::init($session);
        $this->stockeventtable->init($this->db, $this->user_id);
        $this->itemlocationtable->init($this->db, $this->user_id);
    }

    public function getstockwithtargets($location_id, &$results, &$numrows) {
        return $this->itemlocationtable->getstockwithtargets($location_id, '', $results, $numrows, $this->trace);
    }

    public function settargetqty($stock_id, $location_id, $qty_str, &$errormessage) {
        if ($qty_str === '' || $qty_str === null) {
            return $this->itemlocationtable->deletebystock($stock_id, $location_id, $errormessage);
        }
        return $this->itemlocationtable->upsert($stock_id, $location_id, (int)$qty_str, $errormessage);
    }

    public function setlocationstock($stock_id, $location_id, $target_str, $min_str, &$errormessage) {
        $tqty = ($target_str !== '' && $target_str !== null) ? (int)$target_str : null;
        $mqty = ($min_str    !== '' && $min_str    !== null) ? (int)$min_str    : null;
        if ($tqty === null && $mqty === null) {
            return $this->itemlocationtable->deletebystock($stock_id, $location_id, $errormessage);
        }
        return $this->itemlocationtable->upsertboth($stock_id, $location_id, $tqty, $mqty, $errormessage);
    }

    public function update(&$errormessage="", $trace=false) {
        $success = parent::update($errormessage, $trace);
        if (!$success) return false;

        $location_id   = $this->requestdata['id'] ?? 0;
        $stock_ids_str = $this->requestdata['sil_stock_ids'] ?? '';
        if (empty($stock_ids_str) || !$location_id) return true;

        foreach (array_filter(explode(',', $stock_ids_str)) as $raw) {
            $stock_id = (int)$raw;
            if (!$stock_id) continue;
            $target  = $this->requestdata["sil_{$stock_id}"]     ?? '';
            $min     = $this->requestdata["min_qty_{$stock_id}"] ?? '';
            $errm    = '';
            $this->setlocationstock($stock_id, $location_id, $target, $min, $errm);
            if ($errm) $errormessage .= ($errormessage ? '; ' : '') . $errm;
        }
        return true;
    }

    public function delete(&$errormessage="", $trace=false) {
        $id      = (int)($this->requestdata['id']);
        $results = [];
        $numrows = 0;
        $this->stockeventtable->query_params(
            "SELECT id FROM stock_event WHERE location1_id = ? OR location2_id = ?",
            [$id, $id], $results, $numrows
        );
        if ($numrows > 0) {
            $errormessage = "Cannot delete this location — it is used by $numrows stock event(s). Remove those events first.";
            return false;
        }
        return parent::delete($errormessage, $trace);
    }
}
