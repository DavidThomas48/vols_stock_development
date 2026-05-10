<?php
namespace app\controller\manager;
use \lib\StdLib as lib;
class StockClientManager extends \fw\controller\manager\StdManager
{
    private $trace = false;
    protected $name = "Stock Client";
    protected $db;
    protected $linkedobject = "";

    public function __construct(
        protected \apptable\StockClientTable $table,
        protected \apptable\StockEventTable  $stockeventtable
    ) {
        if ($this->trace) { echo "Enter ".__METHOD__."<br>"; }
    }

    public function init($session, $trace=false) {
        parent::init($session);
        $this->stockeventtable->init($this->db, $this->user_id);
    }

    public function delete(&$errormessage="", $trace=false) {
        $id      = (int)($this->requestdata['id']);
        $results = [];
        $numrows = 0;
        $this->stockeventtable->query_params(
            "SELECT id FROM stock_event WHERE stock_client_id = ?",
            [$id], $results, $numrows
        );
        if ($numrows > 0) {
            $errormessage = "Cannot delete this client — it is used by $numrows stock event(s). Remove those events first.";
            return false;
        }
        return parent::delete($errormessage, $trace);
    }
}
