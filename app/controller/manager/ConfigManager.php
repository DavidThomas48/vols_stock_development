<?php
namespace app\controller\manager;
use \lib\StdLib as lib;
class ConfigManager extends \fw\controller\manager\StdManager
{   
    private $trace=false;
    protected $db;
    protected $name = "Configuration";
    protected $linkedobject = ""; // we handle the links to the page table in the page form
    public function __construct(protected \apptable\ConfigTable $table){
        if ($this->trace ) { echo "Enter ".__METHOD__."<br>"; }
        if ($this->trace ) {echo "Leave ".__METHOD__."<br>";}
     }
    public function init($session){
        parent::init($session);
     }
    public function setdb($db) {
        $this->db = $db;
        $this->table->init($this->db);
     }
    protected function updatesetclause(){
        return "";
     }
    public function getallconfig(&$data,&$numrows=0,$orderby="",$trace=false,$noerrorhandler=false){
        if ($this->trace  || $trace ) { echo "Enter ".__METHOD__."<br>\n"; }
        $data = array();
        $success = $this->table->selectall($data,$numrows,$orderby,$trace,$noerrorhandler);
        if ($this->trace  || $trace ) {echo "Leave ".__METHOD__."  success =  {$success}<br>";}
        return $success;
     }
    public function getconfigdata(&$data,$orderby="",$trace=false,$noerrorhandler=false) {
        // With the config table, we re-asssemble all the db records into a multi-dimensional array 
        // with the id field as the index, all other fields in the rows as an array.
        $success = $this->getallconfig($configrows,$numrows,($orderby==""?"`group`":$orderby),$trace,$noerrorhandler);
        $keys = array_column($configrows,"id");
        lib::deleteArrayCol($configrows, "id");
        $data = array_combine($keys,$configrows);
        return $success;
     }
    public function getconfigvalue($name, $default='', $trace=false) {
        $escaped = $this->table->real_escape_string($name);
        $results = []; $numrows = 0;
        $this->table->query("SELECT `value` FROM config WHERE `name` = '{$escaped}' LIMIT 1", $results, $numrows, false);
        return ($numrows > 0) ? $results[0]['value'] : $default;
    }
    public function update(&$errormessage="",$trace=false){
        if ($this->trace || $trace ) { echo "<br>"."Enter ".__METHOD__."  <br>\n"; }
        // Config is a special case. The Config table stores one value per record, but all records are 
        // conolidated into a single editor page for maintenance purposes. This means the $request_data 
        // received following a SUBMIT have to be processed one field at a time, using the fieldname
        // to find the corresponding record from the table, rather than the id 
    // lib::pr($field,$results);
        $errormessage = $query = "";
        $success = true;
        $success = $this->getallconfig($dbdata,$numrows,'`name`',false);
        foreach ($this->requestdata as $field => $formvalue) {
            // search for the $field in the "name" column of the $dbdata array
            if (lib::array_2Dsearch($dbdata,"name",$field,$foundkey) !== false) {
                $dbvalue = $dbdata[$foundkey]["value"];
                if ($dbvalue !== $formvalue) { // data has been changed
                    $emsg = "";
                    $id = $dbdata[$foundkey]["id"];
                    $success = $this->table->putfields($id,["value"],[$this->table->real_escape_string($formvalue)],true,$nr,$emsg,false);
                    if ($emsg!== "") {
                        $errormessage .= "Updating {$field}: ".$emsg."<br>\n";
                    }
                }            
            }
        }
        return $success;
    }
}
