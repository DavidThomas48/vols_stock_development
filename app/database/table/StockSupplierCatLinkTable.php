<?php
namespace apptable;
use \lib\StdLib as lib;
class StockSupplierCatLinkTable extends \fw\database\table\MySQLTable
{
    private $trace = false;
    public function init($db, $user_id="null") {
        if ($this->trace) { echo 'Enter '.__METHOD__.'<br>'; }
        parent::init($db, $user_id);
        $this->fields = array(
            "id"                => "",
            "stock_supplier_id" => "",
            "stock_category_id" => "",
        );
        if ($this->trace) { echo 'Leave '.__METHOD__.'<br>'; }
    }

    // Returns all stock_category rows linked to a given supplier, ordered by name.
    public function getcategoriesforsupplier($supplier_id, &$results, &$numrows, $trace=false) {
        if ($this->trace || $trace) { echo 'Enter '.__METHOD__.'<br>'; }
        $query  = "SELECT sc.id, sc.Name";
        $query .= " FROM stock_supplier_cat_link ssc";
        $query .= " JOIN stock_category sc ON ssc.stock_category_id = sc.id";
        $query .= " WHERE ssc.stock_supplier_id = ?";
        $query .= " ORDER BY sc.Name";
        $success = $this->query_params($query, [$supplier_id], $results, $numrows, $trace);
        if ($this->trace || $trace) { echo 'Leave '.__METHOD__."  ({$numrows} rows)<br>"; }
        return $success;
    }

    // Returns true if a supplier supplies the given category.
    public function suppliersuppliescategory($supplier_id, $category_id, &$numrows, $trace=false) {
        if ($this->trace || $trace) { echo 'Enter '.__METHOD__.'<br>'; }
        $records = [];
        $success = $this->selectonmultiplefields(
            ["stock_supplier_id" => $supplier_id, "stock_category_id" => $category_id],
            $records, $numrows, false, $trace
        );
        if ($this->trace || $trace) { echo 'Leave '.__METHOD__."  ({$numrows} rows)<br>"; }
        return $success;
    }
}
