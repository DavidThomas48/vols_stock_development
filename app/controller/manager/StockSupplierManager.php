<?php
namespace app\controller\manager;
use \lib\StdLib as lib;
class StockSupplierManager extends \fw\controller\manager\StdManager
{
    private $trace = false;
    protected $name = "Stock Supplier";
    protected $db;
    protected $linkedobject = "stockcategory";

    public function __construct(
        protected \apptable\StockSupplierTable         $table,
        protected \apptable\StockCategoryTable         $categorytable,
        protected \apptable\StockSupplierCategoryTable $linktable
    ) {
        if ($this->trace) { echo "Enter ".__METHOD__."<br>"; }
    }

    public function init($session, $trace=false) {
        parent::init($session);
        $this->categorytable->init($this->db, $this->user_id);
        $this->linktable->init($this->db, $this->user_id);
    }

    // Override to inject category link fields into each supplier record and
    // return all categories as $parents for the form's checkbox section.
    public function getallrecords(&$datafields, $orderby, &$parents, &$numrows, $withlock=false, $trace=false) {
        if ($this->trace || $trace) { echo "Enter ".__METHOD__."<br>"; }

        // 1. Load all suppliers
        $success = $this->table->selectall($datafields, $numrows, "name");

        // 2. Load all categories (ordered by name — must match order used in the form)
        $allcategories = [];
        $catnumrows    = 0;
        $success = $success && $this->categorytable->selectall($allcategories, $catnumrows, "Name");

        // 3. Load all supplier-category links
        $alllinks    = [];
        $linknumrows = 0;
        $success = $success && $this->linktable->selectall($alllinks, $linknumrows);

        if ($success) {
            // Build lookup: supplier_id → [category_id, ...]
            $supplierlinks = [];
            foreach ($alllinks as $link) {
                $supplierlinks[$link["stock_supplier_id"]][] = $link["stock_category_id"];
            }
            // Add a boolean link field per category to each supplier record.
            // The field order here must match the checkbox order in StockSupplierForm::buildinputs().
            foreach ($datafields as &$supplier) {
                foreach ($allcategories as $cat) {
                    $linked = isset($supplierlinks[$supplier["id"]]) &&
                              in_array($cat["id"], $supplierlinks[$supplier["id"]]);
                    $supplier["stockcategory".$cat["id"]] = $linked ? 1 : 0;
                }
            }
            unset($supplier);

            $this->alldata = $datafields;
            $this->makenames($trace);
            $parents = $allcategories;
        }

        if ($this->trace || $trace) { echo "Leave ".__METHOD__." OK={$success} ({$numrows} rows)<br>"; }
        return $success;
    }

    // Returns the categories currently linked to a given supplier.
    // Each element must have "id" = category_id so updaten2nlinks() can compare.
    protected function loadlinkedobjects($id, &$dblinkedobjs, &$numrows, $trace=false) {
        if ($this->trace || $trace) { echo "Enter ".__METHOD__."<br>"; }
        $records     = [];
        $success     = $this->linktable->selectononefield("stock_supplier_id", $id, $records, $numrows, false, $trace);
        $dblinkedobjs = [];
        foreach ($records as $r) {
            $dblinkedobjs[] = ["id" => $r["stock_category_id"]];
        }
        if ($this->trace || $trace) { echo "Leave ".__METHOD__." ({$numrows} rows)<br>"; }
        return $success;
    }

    // Returns all rows from the supplier-category link table (id, stock_supplier_id, stock_category_id).
    public function getallcategorylinks(&$links, &$numrows) {
        return $this->linktable->selectall($links, $numrows);
    }

    protected function deletelink($parent_id, $linked_id, $trace=false) {
        if ($this->trace || $trace) { echo "Enter ".__METHOD__." supplier={$parent_id} category={$linked_id}<br>"; }
        $parent_id = $this->linktable->real_escape_string($parent_id);
        $linked_id = $this->linktable->real_escape_string($linked_id);
        $where     = "stock_supplier_id = '{$parent_id}' AND stock_category_id = '{$linked_id}'";
        $success   = $this->linktable->delete($where, $numrows, $trace);
        if ($this->trace || $trace) { echo "Leave ".__METHOD__." OK={$success}<br>"; }
        return $success;
    }

    protected function insertlink($parent_id, $linked_id, $trace=false) {
        if ($this->trace || $trace) { echo "Enter ".__METHOD__." supplier={$parent_id} category={$linked_id}<br>"; }
        $this->linktable->clear();
        $this->linktable->setfield("stock_supplier_id", $parent_id);
        $this->linktable->setfield("stock_category_id", $linked_id);
        $success = $this->linktable->insert(false, $id, $trace, $errormessage);
        if ($this->trace || $trace) { echo "Leave ".__METHOD__." OK={$success}<br>"; }
        return $success;
    }
}
