<?php
namespace app\controller\manager;
use \lib\StdLib as lib;
class DeliveriesReportManager extends \fw\controller\manager\StdManager
{
    private $trace          = false;
    protected $name         = "Deliveries Report";
    protected $db;
    protected $linkedobject = "";
    private $from           = '';
    private $to             = '';
    private $supplier_id    = '';
    private $category_id    = '';

    public function __construct(
        protected \apptable\StockEventTable            $table,
        protected \apptable\StockSupplierTable         $suppliertable,
        protected \apptable\StockSupplierCategoryTable $suppliercategorytable
    ) {}

    public function init($session, $trace=false) {
        parent::init($session);
        $this->suppliertable->init($this->db, $this->user_id);
        $this->suppliercategorytable->init($this->db, $this->user_id);
    }

    public function setfilters($from, $to, $supplier_id, $category_id) {
        $this->from        = $from;
        $this->to          = $to;
        $this->supplier_id = $supplier_id;
        $this->category_id = $category_id;
    }

    public function getallrecords(&$datafields, $orderby, &$parents, &$numrows, $withlock=false, $trace=false) {
        if ($this->trace || $trace) { echo "Enter ".__METHOD__."<br>"; }

        $from = ($this->from !== '') ? $this->from : date('Y-m-01');
        $to   = ($this->to   !== '') ? $this->to   : date('Y-m-t');

        $suppliers          = [];
        $suppliercategories = [];
        $n                  = 0;

        $success  = $this->suppliertable->selectall($suppliers, $n, "name");
        $success  = $success && $this->suppliercategorytable->selectall($suppliercategories, $n, "name");
        $success  = $success && $this->table->getdeliveriesreport(
            $from, $to, $this->supplier_id, $this->category_id, $datafields, $numrows, $trace
        );

        $parents = [
            'from'                => $from,
            'to'                  => $to,
            'supplier_id'         => $this->supplier_id,
            'category_id'         => $this->category_id,
            'suppliers'           => $suppliers,
            'supplier_categories' => $suppliercategories,
        ];

        if ($this->trace || $trace) { echo "Leave ".__METHOD__." ({$numrows} rows)<br>"; }
        return $success;
    }
}
