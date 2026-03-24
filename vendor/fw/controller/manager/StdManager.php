<?php
namespace fw\controller\manager;
use \lib\StdLib as lib;
abstract class StdManager {
    private   $trace= false;                
    protected $session;
    protected $delimiter = "!!";
    protected $trimdelim;
    protected $fields ;
    protected $alldata;
    protected $names;
    protected $requestdata;
    protected $id;
    protected $child=""; 
    protected $user_id=""; 

    public function __construct() {
        if ($this->trace) { echo gtab(0)."Enter ".__METHOD__."<br>"; }
        // $this->setrequired();
     }
    public function __destruct() {
     }
    public function init($session){
        if ($this->trace) { echo gtab(1)."Enter ".__METHOD__.":   $this->name<br>"; }
        $this->session = $session;
        $this->requestdata = $this->session->getrequestdata();
        $this->db = $session->getdb();
        $this->user_id = $this->session->getuserid();
        $this->table->init($this->db,$this->user_id); // $table supplied in subclass's __construct
        $this->id = $this->requestdata["id"]??0;
        if ($this->trace ) {echo gtab(-1)."Leave ".__METHOD__."<br>";}
     }
    public function getallrecords(&$datafields,$orderby,&$parents,&$numrows,$withlock=false,$trace=false) { 
        if ($this->trace  || $trace ) { echo gtab(1)."Enter ".__METHOD__.": for {$this->name} <br>"; }
        $success = $this->table->selectall($datafields,$numrows,$orderby,$trace);
        $success = $success && $this->getparents($parents,$trace);  // IN THE SUBCLASS IF REQUIRED
        if ($success) {
            $this->alldata = $datafields;
            $this->makenames($trace);
        }
        if ($this->trace  || $trace) { echo gtab(-1)."Leave ".__METHOD__." OK? = ".$success." <br>records = ".count($datafields)."\n"; }
        return $success;
     }
    public function getrecord($id,&$fields,&$numrows,$withlock=false, $trace=false) { 
        if ($this->trace || $trace) { echo "Enter ".__METHOD__."<br>\n"; }
        $success = $this->table->selectonID($id,$fields,$numrows,false,false);
        if ($this->trace || $trace) { echo "Leave ".__METHOD__." success=$success<br>\n"; }
        return $success;
     }
    protected function makenames($trace=false) { // overrride if  necessary - e.g. users
        if ($this->trace  || $trace  ) { echo gtab(1)."Enter ".__METHOD__."<br>"; }
        foreach ($this->alldata as $record) {
            if (array_key_exists("name", $record)) {
                $this->names[$record["id"]] = $record["name"];
            } else {
                $this->names[$record["id"]] = $record["id"];
            }
        }
        if ($this->trace  || $trace ) { echo gtab(-1)."Leave ".__METHOD__." names = ".count($this->names)."<br>\n"; }
     }
    public function getname(){
        return $this->name;
     }
    public function names(){
        return $this->names;
     }
    public function alldata() {
         return $this->alldata;
     }
    public function update(&$errormessage="",$trace=false) {
        if ($this->trace || $trace) { echo gtab(1)."Enter ".__METHOD__."<br>"; }
        $data = $this->session->getrequestdata();
        $set = $this->preparesetstatement($data);
        $where = " `id`= '{$this->id}'";
        $success = $this->table->update($set,$where,$numrows,$errormessage,false,$matchedrows,$trace);
        if ($success && $this->child !== "") { // $this->child is declared in the subclass __contruct() when a child with no other parents exists 
             $success && $this->updatechildren($this->id, $data,$errormessage,$trace);
        }
        if ($success && $this->linkedobject !== "") { // this is declared in the subclass if an n2n relationship exists with another table
             $success && $this->updaten2nlinks($data,$errormessage,$trace);
        }
        if ($this->trace || $trace ) {echo gtab(-1)."Leave ".__METHOD__." ".$errormessage." success=".$success."<br>";}
        return $success;
     }
    public function insert(&$id="0",&$errormessage="",$trace=false) {
        if ($this->trace || $trace ) { echo gtab(1)."Enter ".__METHOD__."<br>\n"; }
        try {
            $data = $this->session->getrequestdata();
            $this->insertdataintotablefields($data);
            $this->table->setfield("id","");
            $success = $this->table->insert(true,$id,$trace,$errormessage);
            if ($success) {
                $this->session->putrequestid($id); // used later by the view controller to identify the current record when building forms
                $data["id"] = $id;
                if ($this->child !== "") { // this var is declared in the subclass __construct() where a child with no other parents exists 
                     $success && $this->updatechildren($id,$data,$errormessage,$trace);
                }
                if ($this->linkedobject !== "") { // this var is declared in the subclass where an n2n relationship exists
                    $success = $success && $this->updaten2nlinks($data,$errormessage,$trace);
                }
            }
        } catch (\Exception $e) {
            $errormessage = __METHOD__." : ".$e->__toString();
        }
        if ($this->trace || $trace ) { echo gtab(-1)."Leave ".__METHOD__." {$errormessage}<br>\n"; }
        return $success;
     }
    private function preparetablefields(){
        $this->table->clear();
        $fields = $this->table->getfields();
        $this->setdefaults($fields);  // table-specific - in subclause
        return $fields;
     }
    protected function setdefaults(&$fields,$trace=false){//if there are defaults, override this function in the subcause
     }    
    protected function preparesetstatement($data){
        $set = $comma = "";
        $fields = $this->preparetablefields();
        foreach ($fields as $fieldname => $default) {
            if (substr($fieldname,-3) == "_id" && $data["$fieldname"] == "" ) {
                $set .= $comma." `$fieldname` = null";
            } else {
                $set .= $comma." `$fieldname` = '".$this->table->real_escape_string($data["$fieldname"]??$default)."'";
            }
            $comma=",";
        }
        return $set;
     }
    protected function insertdataintotablefields($data){
        $fields = $this->preparetablefields();
        foreach ($fields as $fieldname => $default) {
            $this->table->setfield($fieldname,$data[$fieldname]??$default);
        }
     }
    public function delete(&$errormessage="",$trace=false) {
        if ($this->trace  || $trace) { echo gtab(1)."Enter ".__METHOD__."<br>"; }
        $data = $this->session->getrequestdata();
        $whereclause = " `id`= '{$this->requestdata['id']}'";
        $success = $this->table->delete($whereclause, $numrows,false);
        if ($this->trace || $trace ) {echo gtab(-1)."Leave ".__METHOD__." ".$errormessage." success=".$success."<br>";}
        return $success;
     }
     // if the following method is required, implement them in the subclass 
    protected function getparents(&$parents,$trace) {
        if ($this->trace ) { echo gtab()."Enter ".__METHOD__."<br>"; }
        $parents = [];
        return true;
     }
    protected function updaten2nlinks($data,&$errormessage,$trace=false) {
        if ($this->trace  || $trace) { echo gtab(1)."Enter ".__METHOD__."<br>"; }
        // this routine handles the case where the current record is linked to other objs in a
        // many-to-many relationship. It contains all the code that is generic to all such situations, 
        // and there are callbacks to the subclass to deliver case-specific code where needed:
        //      $this->deletelink()
        //      $this->insertlink()
        // The function loadrecordandlinkedobjects() in this class also has a callbaack to the subclass:
        //      $this->loadlinkedobjects().  
        // $this->linkedobject is declared in the subclass.
        // ============================================================================================
        //                          INCOMING DATA FIELD NAMING CONVENTION
        // Let's assume that Employee has an n:n relationship with Role, requiring a linking table
        // called EmployeeRole. So the ER looks like this:    Employee --> EmployeeRole <-- Role  and
        // $this->linkedobject = "Role0".
        // Say the Role table contains 3 records with IDs 7, 9 and 12
        // In the page where Employee records are maintained, there is a list of all 3 Roles, each with
        // a checkbox (value=1) to declare a link between the Employee and that Role. The names for these 3 checkboxes 
        // will be "link_role7", "link_role9" and "link_role12", and these names will arrive in the HTTP Request for
        // the checkboxes. We have to parse the incoming request looking for field names that start with 
        // "link_role", extract the trailing ids, then process the data into the database. We also have to parse 
        // the current links in the database, looking for Role IDs with value FALSE in the HTTP Request.
        // This tells us that the link has been deleted on the form, and therefor we have to delete the existing
        // EmployeeRole record. 
        //     
        // Say the EmployeeRole table, apart from the fields "employee_id" and "role_id" also has a field 
        // called "hours". We need text inputs on the form for this field, one for each Role (in addition to the checkbox).
        // These fields will named  "link_role7_hours", "link_role9_hours" and "link_role12_hours". These fields will
        // always arrive in the HTTP Request, but we will only process them if the accompanying checkbox (with 
        // the same role ID) exists in the request and does not have the value FALSE.  
        //
        // Note that form's field naming happens in the individual Form::buildinputs() functions. 
        // ============================================================================================
        // create array of incoming linkedobj's ids and and linked fields
        $success = true;
        $linkedobjectlen= strlen($this->linkedobject) + 5; // add 5 for "link_"
        $weblinkedobjectids = $weblinkedfields = [];
        foreach ($data as $key => $val) { 
            if (substr($key,0,$linkedobjectlen) == "link_".$this->linkedobject) {
                $key = substr($key,$linkedobjectlen); // e.g. "link_role7" -> "7", or  "link_role7_hours" -> "7_hours"
                if (strpos($key,"_") !== false) {
                    $weblinkedfields[$key] = $val;
                } elseif (($val !== false) && ($val !== "false")) { // is a checked checkbox
                    $weblinkedobjectids[] = $key;
                }
            } 
        }   
        $linkedobjectsfromdb = [];
        if ($this->loadrecordandlinkedobjects($data["id"],$thisfields,$linkedobjectsfromdb,$numrows,false,$trace)) {
            // process all database links
            foreach ($linkedobjectsfromdb as $dblinkedobj) { 
                // delete existing links that are not in the incoming data
                if ((!in_array($dblinkedobj["id"],$weblinkedobjectids))) {
                    $success = $this->deletelink($data["id"],$dblinkedobj["id"],$trace);
                }
            }
            // process all incoming links
            foreach ($weblinkedobjectids as $webobjid) { 
                //insert links present in the incoming data but not currently present in db
                if ($webobjid !== "") {
                    $alreadypresent = false;
                    foreach ($linkedobjectsfromdb as $dblnkobj) { 
                        $alreadypresent = ($webobjid == $dblnkobj["id"]);
                        if ($alreadypresent) {
                            break;
                        }
                    }
                    if ($alreadypresent === false) { // this is a new link for this parent
                         $success = $this->insertlink($data["id"],$webobjid,$trace);
                    }
                }
            }
            if (count($weblinkedfields)) { // data fields in linking table may need updating
                $success =  $this->updatelinkfields($weblinkedfields,$data["id"],$trace,$errormessage);
            }
        }
        if ($this->trace) {echo gtab(-1)."Leave ".__METHOD__."  success =  {$success}<br>\n{$errormessage}";}
        return $success;
     }
    protected function updatelinkfields($weblinkedfields,$main_id,$trace,&$errormessage="") { 
        // to be overloaded in subclass where required
        if ($this->trace  || $trace) { echo gtab()."Enter ".__METHOD__."<br>"; }
        return true;
     } 
    protected function loadrecordandlinkedobjects($id,&$fields,&$dblinkedobjs,&$numrows,$withlock=false, $trace=false) { 
        if ($this->trace  || $trace) { echo gtab(1)."Enter ".__METHOD__."<br>"; }
        $success = $this->table->selectonID($id,$fields,$numrows,false,false);
        if ($success) {
            $this->loadlinkedobjects($id,$dblinkedobjs,$numrows,$trace=false);
        }
        if ($this->trace) { echo gtab(-1)."Leave ".__METHOD__." ".$success." <br>\n"; }
        return $success;
     }
    protected function performaction($action,&$outcomemessage,$trace=false) {
        // if it reaches here the subclass has not implemented this method;
        return "Invalid Action in request: $this->requestdata['action']";
     }
}

