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
        );
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
}
