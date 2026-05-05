<?php
namespace app\controller\manager;
use \lib\StdLib as lib;
class StockSupplierCategoryManager extends \fw\controller\manager\StdManager
{
    private $trace = false;
    protected $name = "Supplier Category";
    protected $db;
    protected $linkedobject = "";
    public function __construct(protected \apptable\StockSupplierCategoryTable $table) {
        if ($this->trace) { echo "Enter ".__METHOD__."<br>"; }
    }
}
