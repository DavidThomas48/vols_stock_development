<?php
namespace apptable;
use \lib\StdLib as lib;
class StockClientTable extends \fw\database\table\MySQLTable
{
    private $trace = false;
    public function init($db, $user_id="null") {
        if ($this->trace) { echo 'Enter '.__METHOD__.'<br>'; }
        parent::init($db, $user_id);
        $this->fields = array(
            "id"   => "",
            "name" => "",
        );
        if ($this->trace) { echo 'Leave '.__METHOD__.'<br>'; }
    }
}
