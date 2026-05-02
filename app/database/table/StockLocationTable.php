<?php
namespace apptable;
use \lib\StdLib as lib;
class StockLocationTable extends \fw\database\table\MySQLTable
{
    private $trace = false;
    public function init($db, $user_id="null") {
        if ($this->trace) { echo 'Enter '.__METHOD__.'<br>'; }
        parent::init($db, $user_id);
        $this->fields = array(
            "id"                        => "",
            "name"                      => "",
            "uncontrolled_issues"       => "",
            "is_delivery_default"       => "0",
            "is_transfer_from_default"  => "0",
            "is_transfer_to_default"    => "0",
        );
        if ($this->trace) { echo 'Leave '.__METHOD__.'<br>'; }
    }
}
