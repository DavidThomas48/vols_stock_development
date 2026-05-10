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
        $qty = (int)$target_qty;
        $sql = "INSERT INTO stock_item_location (stock_id, stock_location_id, target_qty)"
             . " VALUES (?, ?, {$qty})"
             . " ON DUPLICATE KEY UPDATE target_qty = {$qty}";
        $result = null; $numrows = 0;
        return $this->execute_params($sql, [(int)$stock_id, (int)$stock_location_id], $result, $numrows, $errormessage, 1);
    }

    public function upsertboth($stock_id, $stock_location_id, $target_qty, $minimum_qty, &$errormessage="") {
        // target_qty is NOT NULL in the schema; null means "preserve existing, or 0 for new rows"
        $ins_tqty = ($target_qty  !== null) ? (int)$target_qty  : 0;
        $upd_tqty = ($target_qty  !== null) ? 'target_qty = ' . (int)$target_qty : 'target_qty = target_qty';
        $mqty_sql = ($minimum_qty !== null) ? (string)(int)$minimum_qty : 'NULL';
        $sql = "INSERT INTO stock_item_location (stock_id, stock_location_id, target_qty, minimum_qty)"
             . " VALUES (?, ?, {$ins_tqty}, {$mqty_sql})"
             . " ON DUPLICATE KEY UPDATE {$upd_tqty}, minimum_qty = {$mqty_sql}";
        $result = null; $numrows = 0;
        return $this->execute_params($sql, [(int)$stock_id, (int)$stock_location_id], $result, $numrows, $errormessage, 1);
    }

    public function getminimumqtys($location_id, &$results, &$numrows, $trace=false) {
        $params = [];
        $query = "SELECT stock_id, stock_location_id, minimum_qty"
               . " FROM stock_item_location"
               . " WHERE minimum_qty IS NOT NULL AND minimum_qty > 0";
        if (!empty($location_id)) {
            $query .= " AND stock_location_id = ?";
            $params[] = $location_id;
        }
        return $this->query_params($query, $params, $results, $numrows, $trace);
    }

    public function deletebystock($stock_id, $stock_location_id, &$errormessage="") {
        $result = null; $numrows = 0;
        return $this->execute_params(
            "DELETE FROM stock_item_location WHERE stock_id = ? AND stock_location_id = ?",
            [(int)$stock_id, (int)$stock_location_id], $result, $numrows, $errormessage, 1
        );
    }

    // Returns all stock items with their target_qty and minimum_qty for the given location.
    // Optionally filtered by category_id.
    public function getstockwithtargets($location_id, $category_id, &$results, &$numrows, $trace=false) {
        $params = [(int)$location_id];
        $query  = "SELECT s.id AS stock_id, s.Name AS stock_name, s.category_id,";
        $query .= " sc.Name AS category_name,";
        $query .= " sil.target_qty, sil.minimum_qty";
        $query .= " FROM stock s";
        $query .= " LEFT JOIN stock_category sc ON s.category_id = sc.id";
        $query .= " LEFT JOIN stock_item_location sil ON sil.stock_id = s.id AND sil.stock_location_id = ?";
        if (!empty($category_id)) {
            $query .= " WHERE s.category_id = ?";
            $params[] = $category_id;
        }
        $query .= " ORDER BY sc.Name, s.Name";
        return $this->query_params($query, $params, $results, $numrows, $trace);
    }
}
