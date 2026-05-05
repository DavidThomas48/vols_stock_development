<?php
namespace apptable;
use \lib\StdLib as lib;
class StockItemLocationTable extends \fw\database\table\MySQLTable
{
    private $trace = false;
    public function init($db, $user_id="null") {
        parent::init($db, $user_id);
        $this->fields = array(
            "id"                => "",
            "stock_id"          => "",
            "stock_location_id" => "",
            "target_qty"        => "",
            "minimum_qty"       => null,
        );
    }

    public function clear() {
        parent::clear();
        $this->fields['minimum_qty'] = null;
    }

    public function upsert($stock_id, $stock_location_id, $target_qty, &$errormessage="") {
        $sid = $this->real_escape_string($stock_id);
        $lid = $this->real_escape_string($stock_location_id);
        $qty = (int)$target_qty;
        $query = "INSERT INTO stock_item_location (stock_id, stock_location_id, target_qty)"
               . " VALUES ('{$sid}', '{$lid}', {$qty})"
               . " ON DUPLICATE KEY UPDATE target_qty = {$qty}";
        $results = []; $numrows = 0;
        return $this->query($query, $results, $numrows);
    }

    public function upsertboth($stock_id, $stock_location_id, $target_qty, $minimum_qty, &$errormessage="") {
        $sid  = $this->real_escape_string($stock_id);
        $lid  = $this->real_escape_string($stock_location_id);
        // target_qty is NOT NULL in the schema; null means "preserve existing, or 0 for new rows"
        $ins_tqty = ($target_qty  !== null) ? (int)$target_qty  : 0;
        $upd_tqty = ($target_qty  !== null) ? 'target_qty = ' . (int)$target_qty : 'target_qty = target_qty';
        $mqty_sql = ($minimum_qty !== null) ? (string)(int)$minimum_qty : 'NULL';
        $query = "INSERT INTO stock_item_location (stock_id, stock_location_id, target_qty, minimum_qty)"
               . " VALUES ('{$sid}', '{$lid}', {$ins_tqty}, {$mqty_sql})"
               . " ON DUPLICATE KEY UPDATE {$upd_tqty}, minimum_qty = {$mqty_sql}";
        $results = []; $numrows = 0;
        return $this->query($query, $results, $numrows);
    }

    public function getminimumqtys($location_id, &$results, &$numrows, $trace=false) {
        $query = "SELECT stock_id, stock_location_id, minimum_qty"
               . " FROM stock_item_location"
               . " WHERE minimum_qty IS NOT NULL AND minimum_qty > 0";
        if (!empty($location_id)) {
            $lid    = $this->real_escape_string($location_id);
            $query .= " AND stock_location_id = '{$lid}'";
        }
        return $this->query($query, $results, $numrows, $trace);
    }

    public function deletebystock($stock_id, $stock_location_id, &$errormessage="") {
        $sid   = $this->real_escape_string($stock_id);
        $lid   = $this->real_escape_string($stock_location_id);
        $where = "stock_id = '{$sid}' AND stock_location_id = '{$lid}'";
        $numrows = 0;
        return $this->delete($where, $numrows, false, $errormessage);
    }

    // Returns all stock items with their target_qty and minimum_qty for the given location.
    // Optionally filtered by category_id.
    public function getstockwithtargets($location_id, $category_id, &$results, &$numrows, $trace=false) {
        $lid    = $this->real_escape_string($location_id);
        $query  = "SELECT s.id AS stock_id, s.Name AS stock_name, s.category_id,";
        $query .= " sc.Name AS category_name,";
        $query .= " sil.target_qty, sil.minimum_qty";
        $query .= " FROM stock s";
        $query .= " LEFT JOIN stock_category sc ON s.category_id = sc.id";
        $query .= " LEFT JOIN stock_item_location sil ON sil.stock_id = s.id AND sil.stock_location_id = '{$lid}'";
        if (!empty($category_id)) {
            $cat    = $this->real_escape_string($category_id);
            $query .= " WHERE s.category_id = '{$cat}'";
        }
        $query .= " ORDER BY sc.Name, s.Name";
        return $this->query($query, $results, $numrows, $trace);
    }
}
