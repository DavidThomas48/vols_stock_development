<?php
namespace apptable;
use \lib\StdLib as lib;
class StockMovementTable extends \fw\database\table\MySQLTable
{
    private $trace = false;
    public function init($db, $user_id="null") {
        if ($this->trace) { echo 'Enter '.__METHOD__.'<br>'; }
        parent::init($db, $user_id);
        $this->fields = array(
            "id"            => "",
            "stock_id"      => "",
            "movement_type" => "",
            "qty"           => "",
            "unit"          => "",
            "unit_qty"      => "1",
            "movement_date" => "",
        );
        if ($this->trace) { echo 'Leave '.__METHOD__.'<br>'; }
    }

    public function getmovementsbytype($type, &$results, &$numrows, $trace=false) {
        if ($this->trace || $trace) { echo 'Enter '.__METHOD__.'<br>'; }
        $type = $this->real_escape_string($type);
        $query  = "SELECT sm.id, sm.stock_id, sm.movement_type, sm.qty, sm.unit, sm.unit_qty,";
        $query .= " sm.movement_date, s.Name as stock_name";
        $query .= " FROM stock_movement sm";
        $query .= " JOIN stock s ON sm.stock_id = s.id";
        $query .= " WHERE sm.movement_type = '{$type}'";
        $query .= " ORDER BY sm.id DESC";
        $success = $this->query($query, $results, $numrows, $trace);
        if ($this->trace || $trace) { echo 'Leave '.__METHOD__."  ({$numrows} rows)<br>"; }
        return $success;
    }

    public function insertstocktake($stock_id, $qty, $unit, $unit_qty, &$id, &$errormessage, $trace=false) {
        if ($this->trace || $trace) { echo 'Enter '.__METHOD__.'<br>'; }
        $this->clear();
        $this->setfield("stock_id",      $stock_id);
        $this->setfield("movement_type", "stocktake_adjustment");
        $this->setfield("qty",           $qty);
        $this->setfield("unit",          $unit);
        $this->setfield("unit_qty",      $unit_qty ?: 1);
        $this->setfield("movement_date", date('Y-m-d H:i:s'));
        $success = $this->insert(true, $id, $trace, $errormessage);
        if ($this->trace || $trace) { echo 'Leave '.__METHOD__."  id={$id}<br>"; }
        return $success;
    }

    public function getusagereport($from, $to, &$results, &$numrows, $trace=false) {
        if ($this->trace || $trace) { echo 'Enter '.__METHOD__.'<br>'; }
        $from = $this->real_escape_string($from);
        $to   = $this->real_escape_string($to);
        $query  = "SELECT s.id, s.Name, s.Code, sc.Name as category_name,";
        $query .= " SUM(sm.qty) as total_used";
        $query .= " FROM stock_movement sm";
        $query .= " JOIN stock s ON sm.stock_id = s.id";
        $query .= " LEFT JOIN stock_category sc ON s.category_id = sc.id";
        $query .= " WHERE sm.movement_type = 'stockout'";
        $query .= " AND sm.movement_date >= '{$from} 00:00:00'";
        $query .= " AND sm.movement_date <= '{$to} 23:59:59'";
        $query .= " GROUP BY s.id, s.Name, s.Code, sc.Name";
        $query .= " ORDER BY sc.Name, s.Name";
        $success = $this->query($query, $results, $numrows, $trace);
        if ($this->trace || $trace) { echo 'Leave '.__METHOD__."  ({$numrows} rows)<br>"; }
        return $success;
    }
}
