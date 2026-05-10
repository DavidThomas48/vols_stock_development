<?php
namespace app\controller\manager;
use \lib\StdLib as lib;
class ClientManager extends \fw\controller\manager\StdManager
{   
    private $trace=false;
    protected $name = "Client";
    protected $linkedobject = "session"; // we handle the links to children in the form
    protected $config;
    protected $errorhandler;
    protected $db;
    public function __construct(protected \apptable\ClientTable $table,
                                protected \apptable\ClientSessionTable $clientsessiontable,
                                protected \apptable\ClientMemberTable $clientmembertable,
                                protected \app\controller\manager\SessionManager $sessionmanager,
                            ){
        if ($this->trace ) { echo "Enter/Leave ".__METHOD__."<br>"; }
        $this->child = "clientmember"; 
     }
    public function init($session,$trace=false){
        if ($this->trace || $trace) { echo "Enter ".__METHOD__."<br>"; }
        parent::init($session);
        $this->config = $this->session->getconfig();
        $this->errorhandler = $this->session->geterrorhandler();
        $this->sessionmanager->init($this->session);
        $this->clientsessiontable->init($this->db);
        $this->clientmembertable->init($this->db,$this->session->getuserid());
        if ($this->trace || $trace ) {echo "Leave ".__METHOD__."<br>";}
     }    
    // protected function updatesetclause($data){  
    //     if ($this->trace || $trace) { echo "Enter ".__METHOD__."<br>"; }
    //     $fields = $this->table->getfields();
    //     $fields["residence"] = 3;
    //     $fields["gender"] = 2;
    //     if ($this->trace || $trace ) {echo "Leave ".__METHOD__."<br>";}
    //     return $this->preparesetstatement($fields,$data);
    //  }
    // protected function insertdata($data) {
    //     if ($this->trace || $trace) { echo "Enter ".__METHOD__."<br>"; }
    //     $fields = $this->table->getfields();
    //     $fields["represented_by"] = "self";
    //     $fields["residence"] = "Not supplied";
    //     $fields["gender"] = "NOTGIVEN";
    //     $this->insertintotablefields($fields,$data);
    //     if ($this->trace || $trace ) {echo "Leave ".__METHOD__."<br>";}
    //  }
    protected function updatechildren($clientid,$data,&$errormessage,$trace) {
        // the child data constists of groups of 6 fields - id, membername, relationship, mob, yob, cob
        //first load all current children so we can detect records that were deleted
        $copy = array_slice($data,0);
        $success =  $this->getmembersforclient($clientid,$curmembers,'',$numrows,false);
        if (is_array($curmembers) && count($curmembers)) {
            foreach ($curmembers as $member) {
                $id = $member['id'];
                if (!($copy["child_mem_id".$id]??false)) { 
                    // this current member is not in the form data = must have been deleted
                     $this->clientmembertable->delete( "`id`= ".$id, $numrows,false);
                 }
            }
        }
        // now process the incoming data
        foreach ($data as $key => $value) {
            if (substr($key,0,12) == 'child_mem_id') {
                $id = substr($key,12);
                $required = $copy["child_mem_nam".$id].$copy["child_mem_rel".$id].($copy["child_mem_mob".$id]??"").($copy["child_mem_yob".$id]??"").$copy["child_mem_cob".$id];
                if ($required === "" ) {
                    if ($id < 0) {
                        break; // i.e. ignore this record
                    } else { // delete the now empty record
                        $this->clientmembertable->delete( "`id`= ".$id, $numrows,false);
                    } 
                } else if ($id < 0) { // add new record
                    $this->clientmembertable->clear();
                    $this->clientmembertable->setfield("client_id",$clientid);
                    $this->clientmembertable->setfield("name",$copy["child_mem_nam".$id]);
                    $this->clientmembertable->setfield("relationship",$copy["child_mem_rel".$id]);
                    $this->clientmembertable->setfield("month_of_birth",$copy["child_mem_mob".$id]);
                    $this->clientmembertable->setfield("year_of_birth",$copy["child_mem_yob".$id]);
                    $this->clientmembertable->setfield("country_of_birth",$copy["child_mem_cob".$id]);
                    $success = $this->clientmembertable->insert(true,$id,false,$errormessage);
                } else { // update record
                    $set_parts = ["`name` = ?"];
                    $params    = [$copy["child_mem_nam".$id] ?? ""];
                    if (array_key_exists("child_mem_rel".$id, $copy)) { $set_parts[] = "`relationship` = ?";     $params[] = $copy["child_mem_rel".$id] ?? ""; }
                    if (array_key_exists("child_mem_mob".$id, $copy)) { $set_parts[] = "`month_of_birth` = ?";   $params[] = $copy["child_mem_mob".$id] ?? 0; }
                    if (array_key_exists("child_mem_yob".$id, $copy)) { $set_parts[] = "`year_of_birth` = ?";    $params[] = $copy["child_mem_yob".$id] ?? 0; }
                    if (array_key_exists("child_mem_cob".$id, $copy)) { $set_parts[] = "`country_of_birth` = ?"; $params[] = $copy["child_mem_cob".$id] ?? ""; }
                    $params[] = (int)$id;
                    $result = null; $numrows = 0;
                    $success = $this->clientmembertable->execute_params(
                        "UPDATE client_member SET " . implode(', ', $set_parts) . " WHERE id = ?",
                        $params, $result, $numrows, $errormessage, 1
                    );
                }
            }
        }
        return $success;
     }
    public function loadclient($id,&$clientfields,&$sessions,&$isadmin,&$numrows,$withlock=false, $trace=false) { 
        if ($this->trace || $trace) { echo "Enter ".__METHOD__."<br>\n"; }
        $success = $this->table->selectonID($id,$clientfields,$numrows,false,false);
        if ($success) {
            $isadmin  = $userfields["isadmin"];
            $this->isadmin  = $isadmin;
            $success = $success && $this->loadlinkedobjects($id,$roles,$numrows,false);
        }
        if ($this->trace || $trace) { echo "Leave ".__METHOD__." success=$success isadmin={$this->isadmin}<br>\n"; }
        return $success;
     }
    public function getallrecords(&$datafields,$orderby,&$parents,&$numrows,$withlock=false,$trace=false) {
        if ($this->trace  || $trace ) { echo gtab(1)."Enter ".__METHOD__.": for {$this->name} <br>"; }
        // tandc_signature (MEDIUMTEXT) is intentionally excluded — loaded on demand via AJAX
        $fieldlist = 'id, given_name, family_name, email, phone, address_street, address_street2, '
                   . 'address_townsuburb, address_state, address_postcode, residence, gender, '
                   . 'month_of_birth, year_of_birth, interpreter, language, country_of_birth, '
                   . 'aborigine_TSislander, represented_by, carer_name, concession_card, dietary, '
                   . 'comments, office_comments, has_read_tandc';
        $success = $this->table->select($fieldlist, '', '', '', $orderby ?: 'given_name, family_name', 0, $datafields, $numrows, $trace);
        $success = $success && $this->getparents($parents,$trace);  // IN THE SUBCLASS
        if ($success) {
            $this->alldata = $datafields;
            $this->makenames($trace);
        }
        if ($this->trace  || $trace) { echo gtab(-1)."Leave ".__METHOD__." OK? = ".$success." <br>records = ".count($datafields)."\n"; }
        return $success;
     }
    public function getclientsignature($client_id) {
        $cid     = (int)$client_id;
        $results = []; $numrows = 0;
        $this->table->query("SELECT tandc_signature FROM client WHERE id = {$cid}", $results, $numrows, false);
        return $results[0]['tandc_signature'] ?? '';
     }
    public function makenames($trace=false) { 
        if ($this->trace  || $trace) { echo "Enter ".__METHOD__."<br>"; }
        $this->names = [];
        if (is_array($this->alldata) && count($this->alldata)) {
            foreach ($this->alldata as $record) {
                $this->names[$record["id"]] = $record["given_name"]." ".$record["family_name"];
            }
        }
        if ($this->trace || $trace) { echo "Leave ".__METHOD__." <br>\n"; }
     }
// ======================================= functions related to managing linked objects (Session)
    public function loadlinkedobjects($clientid,&$sessions,$numrows,$trace=false){
        if ($this->trace) { echo "Enter ".__METHOD__." clientid=".$clientid." <br>"; }
        // load all the session records for this client 
        $sessions = array();
        $query = <<<SQL
            SELECT * 
            FROM session  
            WHERE id IN (   
                SELECT session_id 
                FROM client_session cs 
                WHERE client_id = $clientid);
        SQL;
        $success = $this->table->query($query,$sessions,$numrows,false);
        if ($this->trace) { echo "Leave ".__METHOD__." success=$success<br>"; }
        return $success;
     }
    public function deletelink($clientid,$sessionid) { 
        if ($this->trace) { echo "Enter ".__METHOD__." client_id id=".$clientid." <br>"; }
         $whereclause =  "`client_id{$clientid}' AND `session_id{$sessionid}'"; 
         $success =   $this->clientsessiontable->delete($whereclause,$numrows,false);
        if ($this->trace) { echo "Leave ".__METHOD__." success=$success<br>"; }
        return $success;
     }
    public function insertlink($clientid,$sessionid) { 
        if ($this->trace) { echo "Enter ".__METHOD__." client_id=".$clientid." <br>"; }
        $this->clientsessiontable->clear();
        $this->clientsessiontable->setfield("client_id",$clientid);
        $this->clientsessiontable->setfield("session_id",$sessionid);
        $success = $this->clientsessiontable->insert(false);
        if ($this->trace) { echo "Leave ".__METHOD__." success=$success<br>"; }
        return $success;
     }
//================================================================= the following are called from the ViewController
    public function getallclientsessions(&$clientsessions,$numrows,$trace=false) {
        if ($this->trace || $trace) { echo 'Enter '.__METHOD__.'<br>'; }
        $query  = " SELECT cs.client_id as client_id,s.id as session_id, s.start as sessiondate, t.name as task";
        $query .= " FROM client_session cs";
        $query .= " JOIN session s ON s.id = cs.session_id";
        $query .= " JOIN task t ON t.id = s.task_id";
        $query .= " ORDER BY  cs.client_id, sessiondate DESC";
        $success = $this->clientsessiontable->query($query,$clientsessions,$numrows,$trace);
        // lib::v($results);
        if ( $this->trace || $trace) { echo 'Leave '.__METHOD__."  ({$numrows} rows OK? = {$success})<br>";}
        return $success;
     }
    public function getallclientmembers(&$clientmembers,$orderby,&$numrows=0,$trace=false){
        if ($this->trace  || $trace ) { echo "Enter ".__METHOD__."<br>\n"; }
        $session = array();
        $success = $this->clientmembertable->selectall($clientmembers,$numrows,$orderby,$this->trace  || $trace,false);
        if ($this->trace  || $trace ) {echo "Leave ".__METHOD__."  success =  {$success}<br>";}
        return $success;
     }
    public function getmembersforclient($client_id,&$clientmembers,$orderby,&$numrows=0,$trace=false){
        if ($this->trace  || $trace ) { echo "Enter ".__METHOD__."<br>\n"; }
        $session = array();
        $success = $this->clientmembertable->selectononefield("client_id",$client_id,$clientmembers,$numrows,false);
        if ($this->trace  || $trace ) {echo "Leave ".__METHOD__."  success =  {$success}<br>";}
        return $success;
     }
    public function getclientidsandnames(&$clients,$orderby,&$numrows=0,$trace=false){
        if ($this->trace  || $trace ) { echo "Enter ".__METHOD__."<br>\n"; }
        $clients = array();
        $query = <<<SQL
            SELECT id,concat_ws(' ',given_name,family_name) AS name
            FROM client
            ORDER BY "{$orderby}";  
        SQL; 
        $success = $this->clientmembertable->query($query,$clients,$numrows,$this->trace  || $trace);
        if ($this->trace  || $trace ) {echo "Leave ".__METHOD__."  success =  {$success}<br>";}
        return $success;
     }
    public function gettodaysvolunteers(&$volunteers,&$numrows,$trace=false) {
        if ($this->trace  || $trace ) { echo "Enter ".__METHOD__."<br>\n"; }
        $todayDateTime = new \DateTime(); //'2026-01-15'
        $todaydate = $todayDateTime->format('Y-m-d');
        $success = $this->sessionmanager->getfbsessionvolunteers($todaydate,$volunteers,$numrows,$trace);
        if ($this->trace  || $trace ) {echo "Leave ".__METHOD__."  success =  {$success}<br>$todaydate<br>";}
        return $success;
     }
//================================================================  the following function process ajax calls for report data
    public function getsessionreportdata($daterange,&$clientsessions,&$numrows,$trace=false){
        if ($this->trace || $trace) { echo 'Enter '.__METHOD__.'<br>'; }
        $fromdate = substr($daterange,0,10);
        $todate = substr($daterange,11);
        $query  = <<<SQL
                    SELECT t.name as taskname,
                             `start` as start, 
                            given_name,
                            family_name,
                            address_postcode, 
                            gender,     
                            c.month_of_birth,
                            c.year_of_birth,
                            residence,  
                            interpreter, 
                            `language` as language, 
                            c.country_of_birth,
                            aborigine_TSislander,
                            represented_by, 
                            carer_name, 
                            concession_card,
                            dietary,
                            (count(cm.id) + 1) as household
                    FROM client_session cs
                    JOIN client c  ON cs.client_id = c.id
                    LEFT OUTER JOIN client_member cm ON cm.client_id = c.id
                    JOIN session s ON cs.session_id = s.id
                    JOIN task t ON s.task_id = t.id
                    WHERE start > "{$fromdate}" AND start < "{$todate}" 
                    GROUP BY cs.id
                    ORDER BY taskname,start,given_name,family_name;
                SQL;
        // $query .= " ORDER BY cs.client_id, sessiondate";
        // lib::pr($query);
        $success = $this->clientsessiontable->query($query,$clientsessions,$numrows,false);
    // lib::v($clientsessions);
        if ( $this->trace || $trace) { echo 'Leave '.__METHOD__."  ({$numrows} rows OK? = {$success})<br>";}
        return $success;
     }
    public function getbeneficiaryreportdata($daterange,&$clientsessions,&$numrows,$trace=false){
        if ($this->trace || $trace) { echo 'Enter '.__METHOD__.'<br>'; }
        $fromdate = substr($daterange,0,10);
        $todate = substr($daterange,11);
        $query  = <<<SQL
                        SELECT *
                            ,(SUM(member) + 1) as household
                            ,(ischild + SUM(memberischild)) as children
                        FROM (        
                                SELECT  c.id AS clientid,
                                        given_name,
                                        family_name,
                                        address_postcode, 
                                        gender,
                                        CASE WHEN (c.year_of_birth > 0 and c.month_of_birth > 0) THEN
                                            CASE WHEN TIMESTAMPDIFF(MONTH, date(CONCAT(c.year_of_birth,"-",c.month_of_birth,"-01")), CURDATE()) < (18*12)  THEN 1
                                                ELSE 0
                                            END
                                        ELSE 0 
                                        END AS ischild,
                                        residence,  
                                        interpreter, 
                                        `language` as language, 
                                        c.country_of_birth as cob,
                                        aborigine_TSislander,
                                        represented_by, 
                                        carer_name, 
                                        concession_card,
                                        dietary,
                                        sum(CASE WHEN cm.id IS NOT NULL THEN 1 ELSE 0 END) as member,
                                        sum(CASE WHEN (cm.id IS NOT NULL AND cm.year_of_birth > 0 AND cm.month_of_birth > 0) THEN
                                                CASE WHEN TIMESTAMPDIFF(MONTH, date(CONCAT(cm.year_of_birth,"-",cm.month_of_birth,"-01")), CURDATE()) < (18*12)  THEN 1
                                                    ELSE 0
                                                END 
                                            ELSE 0
                                        END) AS memberischild
                                FROM client c 
                                LEFT OUTER JOIN client_member cm ON cm.client_id = c.id
                                WHERE c.id IN (SELECT DISTINCT c.id 
                                               FROM client cc
                                               JOIN client_session cs on cs.client_id = cc.id
                                               JOIN session s ON cs.session_id = s.id
                                               WHERE s.start > "{$fromdate}" AND start < "{$todate}")
                                GROUP BY clientid
                                )  SQL1
                        GROUP BY clientid
                        ORDER BY given_name,family_name;
                SQL;
        $success = $this->clientsessiontable->query($query,$clientsessions,$numrows,false);
    lib::v($clientsessions);
        if ( $this->trace || $trace) { echo 'Leave '.__METHOD__."  ({$numrows} rows OK? = {$success})<br>";}
        return $success;
     }
    public function getdesktopdata(&$summary,&$numrows,$trace=false){
        if ($this->trace || $trace) { echo 'Enter '.__METHOD__.'<br>'; }
        // $todate = CURRENT_DATE();
        // $som = SUBDATE(CURRENT_DATE(),INTERVAL 1 MONTH)
        // $soq = SUBDATE(CURRENT_DATE(),INTERVAL 3 MONTH)
        // $soy = SUBDATE(CURRENT_DATE(),INTERVAL 12 MONTH)
        $query  = <<<SQL
                    SELECT "MONTH",
                            COUNT(clientid) as clients,
                            IFNULL(sum(household + 1),0) as population,
                            SUM(memberischild) + clientischild AS children
                    FROM (SELECT 
                            DISTINCT c.id as clientid,
                            sum(CASE WHEN cm.id IS NOT NULL THEN 1 ELSE 0 END) as household,
                            CASE WHEN (c.year_of_birth > 0 and c.month_of_birth > 0) THEN
                                    CASE WHEN TIMESTAMPDIFF(MONTH, date(CONCAT(c.year_of_birth,"-",c.month_of_birth,"-01")), CURDATE()) < (18*12)  THEN 1
                                    ELSE 0 END
                            ELSE 0 END AS clientischild,
                            sum(CASE WHEN (cm.id IS NOT NULL AND cm.year_of_birth > 0 AND cm.month_of_birth > 0) THEN
                                    (CASE WHEN TIMESTAMPDIFF(MONTH, date(CONCAT(cm.year_of_birth,"-",cm.month_of_birth,"-01")), CURDATE()) < (18*12)  THEN 1
                                     ELSE 0 END) 
                                ELSE 0 END) AS memberischild                                
                        FROM client AS c
                        LEFT OUTER JOIN client_member cm ON cm.client_id = c.id
                        WHERE c.id IN (
                                        SELECT DISTINCT c.id
                                        FROM client AS c
                                        JOIN client_session cs ON cs.client_id = c.id 
                                        JOIN session s ON cs.session_id = s.id 
                                        WHERE s.start >= DATE_FORMAT(DATE_SUB(CURDATE(), INTERVAL 1 MONTH), '%Y-%m-01') AND s.start < DATE_FORMAT(CURDATE(), '%Y-%m-01'))
                        GROUP BY c.id
                        ) as SQL1

                    UNION

                    SELECT "THREE MONTHS",
                            COUNT(clientid) as clients,
                            IFNULL(sum(household + 1),0) as population,
                            SUM(memberischild) + clientischild AS children
                    FROM (SELECT 
                            DISTINCT c.id as clientid,
                            sum(CASE WHEN cm.id IS NOT NULL THEN 1 ELSE 0 END) as household,
                            CASE WHEN (c.year_of_birth > 0 and c.month_of_birth > 0) THEN
                                    CASE WHEN TIMESTAMPDIFF(MONTH, date(CONCAT(c.year_of_birth,"-",c.month_of_birth,"-01")), CURDATE()) < (18*12)  THEN 1
                                    ELSE 0 END
                            ELSE 0 END AS clientischild,
                            sum(CASE WHEN (cm.id IS NOT NULL AND cm.year_of_birth > 0 AND cm.month_of_birth > 0) THEN
                                    (CASE WHEN TIMESTAMPDIFF(MONTH, date(CONCAT(cm.year_of_birth,"-",cm.month_of_birth,"-01")), CURDATE()) < (18*12)  THEN 1
                                     ELSE 0 END) 
                                ELSE 0 END) AS memberischild                                
                        FROM client AS c
                        LEFT OUTER JOIN client_member cm ON cm.client_id = c.id
                        WHERE c.id IN (
                                        SELECT DISTINCT c.id
                                        FROM client AS c
                                        JOIN client_session cs ON cs.client_id = c.id 
                                        JOIN session s ON cs.session_id = s.id 
                                        WHERE s.start >= DATE_FORMAT(DATE_SUB(CURDATE(), INTERVAL 3 MONTH), '%Y-%m-01') AND s.start < DATE_FORMAT(CURDATE(), '%Y-%m-01'))
                        GROUP BY c.id
                        ) as SQL2
 
                    UNION

                    SELECT "TWELVE MONTHS",
                            COUNT(clientid) as clients,
                            IFNULL(sum(household + 1),0) as population,
                            SUM(memberischild) + clientischild AS children
                    FROM (SELECT 
                            DISTINCT c.id as clientid,
                            sum(CASE WHEN cm.id IS NOT NULL THEN 1 ELSE 0 END) as household,
                            CASE WHEN (c.year_of_birth > 0 and c.month_of_birth > 0) THEN
                                    CASE WHEN TIMESTAMPDIFF(MONTH, date(CONCAT(c.year_of_birth,"-",c.month_of_birth,"-01")), CURDATE()) < (18*12)  THEN 1
                                    ELSE 0 END
                            ELSE 0 END AS clientischild,
                            sum(CASE WHEN (cm.id IS NOT NULL AND cm.year_of_birth > 0 AND cm.month_of_birth > 0) THEN
                                    (CASE WHEN TIMESTAMPDIFF(MONTH, date(CONCAT(cm.year_of_birth,"-",cm.month_of_birth,"-01")), CURDATE()) < (18*12)  THEN 1
                                     ELSE 0 END) 
                                ELSE 0 END) AS memberischild                                
                        FROM client AS c
                        LEFT OUTER JOIN client_member cm ON cm.client_id = c.id
                        WHERE c.id IN (
                                        SELECT DISTINCT c.id
                                        FROM client AS c
                                        JOIN client_session cs ON cs.client_id = c.id 
                                        JOIN session s ON cs.session_id = s.id 
                                        WHERE s.start >= DATE_FORMAT(DATE_SUB(CURDATE(), INTERVAL 12 MONTH), '%Y-%m-01') AND s.start < DATE_FORMAT(CURDATE(), '%Y-%m-01'))
                        GROUP BY c.id
                        ) as SQL3

                    UNION

                    SELECT "CURRENT MONTH",
                            COUNT(clientid) as clients,
                            IFNULL(sum(household + 1),0) as population,
                            SUM(memberischild) + clientischild AS children
                    FROM (SELECT
                            DISTINCT c.id as clientid,
                            sum(CASE WHEN cm.id IS NOT NULL THEN 1 ELSE 0 END) as household,
                            CASE WHEN (c.year_of_birth > 0 and c.month_of_birth > 0) THEN
                                    CASE WHEN TIMESTAMPDIFF(MONTH, date(CONCAT(c.year_of_birth,"-",c.month_of_birth,"-01")), CURDATE()) < (18*12)  THEN 1
                                    ELSE 0 END
                            ELSE 0 END AS clientischild,
                            sum(CASE WHEN (cm.id IS NOT NULL AND cm.year_of_birth > 0 AND cm.month_of_birth > 0) THEN
                                    (CASE WHEN TIMESTAMPDIFF(MONTH, date(CONCAT(cm.year_of_birth,"-",cm.month_of_birth,"-01")), CURDATE()) < (18*12)  THEN 1
                                     ELSE 0 END)
                                ELSE 0 END) AS memberischild
                        FROM client AS c
                        LEFT OUTER JOIN client_member cm ON cm.client_id = c.id
                        WHERE c.id IN (
                                        SELECT DISTINCT c.id
                                        FROM client AS c
                                        JOIN client_session cs ON cs.client_id = c.id
                                        JOIN session s ON cs.session_id = s.id
                                        WHERE s.start >= DATE_FORMAT(CURDATE(), '%Y-%m-01') AND s.start < CURRENT_DATE())
                        GROUP BY c.id
                        ) as SQL4
            SQL;
        // $query .= " ORDER BY cs.client_id, sessiondate";
        // lib::pr($query);
        $success = $this->clientsessiontable->query($query,$summary,$numrows,false);
        // lib::v($clientsessions);
        if ( $this->trace || $trace) { echo 'Leave '.__METHOD__."  ({$numrows} rows OK? = {$success})<br>";}
        return $success;
     }
}