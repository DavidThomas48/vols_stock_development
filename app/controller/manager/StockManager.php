<?php
namespace app\controller\manager;
use \lib\StdLib as lib;
class StockManager extends \fw\controller\manager\StdManager
{
    private $trace = false;
    protected $name = "Stock Item";
    protected $db;
    protected $linkedobject = "";
    public function __construct(
        protected \apptable\StockTable              $table,
        protected \apptable\StockCategoryTable      $categorytable,
        protected \apptable\StockLocationTable      $locationtable,
        protected \apptable\StockItemLocationTable  $stockitemlocationtable
    ) {
        if ($this->trace) { echo "Enter ".__METHOD__."<br>"; }
    }

    public function init($session, $trace=false) {
        parent::init($session);
        $this->categorytable->init($this->db);
        $this->locationtable->init($this->db, $this->user_id);
        $this->stockitemlocationtable->init($this->db, $this->user_id);
    }

    public function getallrecords(&$datafields, $orderby, &$parents, &$numrows, $withlock=false, $trace=false) {
        $success = $this->table->selectall($datafields, $numrows, $orderby, $trace);

        $allcategories = []; $catnumrows = 0;
        $success = $success && $this->categorytable->selectall($allcategories, $catnumrows, "Name", $trace);

        $alllocations = []; $locnumrows = 0;
        $success = $success && $this->locationtable->selectall($alllocations, $locnumrows, "name", $trace);

        $allqtys = []; $qtynumrows = 0;
        $success = $success && $this->stockitemlocationtable->selectall($allqtys, $qtynumrows, "", $trace);

        if ($success) {
            $qtyindex = [];
            foreach ($allqtys as $q) {
                $qtyindex[$q['stock_id']][$q['stock_location_id']] = [
                    'target_qty'       => $q['target_qty'],
                    'minimum_qty' => $q['minimum_qty'],
                ];
            }
            foreach ($datafields as &$row) {
                foreach ($alllocations as $loc) {
                    $row['target_qty_' . $loc['id']] = $qtyindex[$row['id']][$loc['id']]['target_qty'] ?? '';
                }
                foreach ($alllocations as $loc) {
                    $row['min_qty_' . $loc['id']] = $qtyindex[$row['id']][$loc['id']]['minimum_qty'] ?? '';
                }
            }
            unset($row);
            $this->alldata = $datafields;
            $this->makenames($trace);
            $parents = ['categories' => $allcategories, 'locations' => $alllocations];
        }
        return $success;
    }

    public function update(&$errormessage="", $trace=false) {
        $success = parent::update($errormessage, $trace);
        return $success && $this->savetargetqtys($this->id, $errormessage);
    }

    public function insert(&$id="0", &$errormessage="", $trace=false) {
        $success = parent::insert($id, $errormessage, $trace);
        return $success && $this->savetargetqtys($id, $errormessage);
    }

    private function savetargetqtys($stock_id, &$errormessage) {
        $loc_vals = [];
        foreach ($this->requestdata as $key => $value) {
            if (substr($key, 0, 11) === 'target_qty_') {
                $loc_id = substr($key, 11);
                if (!ctype_digit($loc_id)) continue;
                $loc_vals[$loc_id]['tqty'] = trim($value);
            } elseif (substr($key, 0, 8) === 'min_qty_') {
                $loc_id = substr($key, 8);
                if (!ctype_digit($loc_id)) continue;
                $loc_vals[$loc_id]['mqty'] = trim($value);
            }
        }
        foreach ($loc_vals as $loc_id => $vals) {
            $tqty = $vals['tqty'] ?? '';
            $mqty = $vals['mqty'] ?? '';
            $sid  = $this->stockitemlocationtable->real_escape_string($stock_id);
            $lid  = $this->stockitemlocationtable->real_escape_string($loc_id);
            if ($tqty === '' && $mqty === '') {
                $numrows = 0;
                $this->stockitemlocationtable->delete("stock_id='{$sid}' AND stock_location_id='{$lid}'", $numrows);
            } else {
                $tval = ($tqty !== '') ? (int)$tqty : null;
                $mval = ($mqty !== '') ? (int)$mqty : null;
                if (!$this->stockitemlocationtable->upsertboth($sid, $lid, $tval, $mval, $errormessage)) {
                    return false;
                }
            }
        }
        return true;
    }
}
