<?php
namespace app\daemon;
use \lib\StdLib as lib;
class RosterReview {
    protected $trace= false;   
    protected $config;
    protected $configmanager;
    protected $usermanager;
    protected $sessionmanager;
    protected $emailmanager;
    protected $heading;
    protected $footer;
    protected $requestdata;
    protected $report;
    protected $volunteer;
    protected $today;
    protected $todaydow;
    protected $nl = "<BR>\n";
    protected $indent4 = "&nbsp; &nbsp; ";
    protected $indent8 = "&nbsp; &nbsp; &nbsp; &nbsp; ";
    private $display = true;
    private $noemails = false; // don't send any vol emails
    private $testing = false; // send report and vol emails to me
    protected $belowminimumreportmanager;
    public function __construct(protected \fw\exception\ErrorHandler $errorhandler,
                                protected \database\MySqlDB $db,
                                protected \fw\session\WebSession $session ,
                                protected \app\controller\manager\ManagerCollection $managercollection,
                                protected \apptable\SessionTable $sessiontable,
                                protected \apptable\BookingTable $bookingtable,
                                protected \app\controller\manager\TaskExtenderManager $taskextendermanger
                               ) {
        date_default_timezone_set('Australia/Melbourne');
     }
    public function init($fromroster=false) {  
        if ($this->trace) { echo "Enter ".__METHOD__."<br>"; }
        try {
            $this->configmanager = $this->managercollection->ConfigManager();
            $norights = false;
            // dbconnection.php contains code to connect to the database and then  
            // complete the population of the $config array with the database settings
            // It resolves a circular dependency in the initialisation process.
            // This code is shared with daemon.php 
            $dbcpath = __DIR__.'/../database/dbconnection.php';
            // die($dbcpath);
            include  $dbcpath;
            connectandconfigure($this->db,$this->config,$this->configmanager); 
            // ... so now we can initialise $errorhandler
            $this->errorhandler->init($this->config); 
            // ... and pass it to $db. 
            $this->db->init($this->errorhandler);
            $this->session->init($this->errorhandler,$this->db,$this->managercollection,$this->requestdata,$norights,$this->config,false);
            $this->errorhandler->initphase2($this->session); 
            $this->usermanager = $this->managercollection->usermanager();
            $this->sessionmanager = $this->managercollection->sessionmanager();
            $this->emailmanager = $this->managercollection->emailmanager();
            $this->belowminimumreportmanager = $this->managercollection->BelowMinimumReportManager();
            $this->usermanager->init($this->session);
            $this->emailmanager->init($this->session);
            $this->sessionmanager->init($this->session);
            $this->belowminimumreportmanager->init($this->session);
            $this->taskextendermanger->init($this->session);
            $this->sessiontable->init($this->db);
            $this->bookingtable->init($this->db);
            $this->heading = $this->emailmanager->getheading();
            $this->footer = $this->emailmanager->getfooter();
        } catch (\Exception $e) {
            die('Caught exception in  RosterReview : '.$e->getMessage());
        }
     }
    public  function do_review($doshortfall=true,$dodigest=true,$domaintainrosters=true,$docheckuserroles=true,$dochecksessionroles=true,$sendreport=true,$data=[],$trace=false,$dostockalerts=true){
        if ($this->trace || $trace ) { echo "Enter ".__METHOD__."<br>"; }
        try {
            $this->init();
            // send request out for up-coming sessions that are short of vols
            $result = $doshortfall && $this->shortfall($trace);
            if(date('D') == $this->config["app"]["DIGESTDAY"]) { // Date format 'D' = short Dayname e.g. "Sun"
                $result = $dodigest && $this->digest($trace);// send out reminders to vols booked this week
            }
            // if(date('D') == $this->config["app"]["PUBLISHDAY"]) { // Date format 'D' = short Dayname e.g. "Sun"
                $task_id =  $data["task_id"]??0;
                $result = $domaintainrosters && $this->taskextendermanger->extendsessions($task_id,$this->errorhandler,$this->trace||$trace); // extend the roster and publish sessions as required
            // }
            $result = $docheckuserroles && $this->checkuserroles($trace);
            $result = $dochecksessionroles && $this->checksessionroles($trace);
            $result = $dostockalerts && $this->checkstockalerts($trace);
            $result = $sendreport && $this->sendreport();
        } catch (\Exception $e) {
            $result =  'Caught exception: '.$e->getMessage();
        }
        return $this->report;
     }
//==================================================================REPORT
    private function sendreport() {
        // echo $this->report;
        // $email["To"]=[['Email' => "david.thomas@elliott-thomas.com.au","Name" =>"David Thomas"],['Email' => "amandak@woodendnh.org.au","Name" =>"Amanda Knight"]];
        // lib::prf(P,R,$this->config["app"]);
        if ($this->testing) {
            if ($this->display) {
                echo $this->report;
            }
            $email["To"] =  [['Email' => "david.thomas@elliott-thomas.com.au","Name" => "dt"]];
        } else {
            if ("" == $recips = $this->config["app"]["REPORTRECIPIENTS"]) {
                $recips = "david.thomas@elliott-thomas.com.au";
            }
            while ($recips != "") {
                lib::getnexttokeninstring($recips,$recip,"|");
                $email["To"][] = ['Email' => $recip,"Name" =>"Recipent"];
            }        
        }
        $email["TextPart"]= $this->report; 
        $email["HTMLPart"]= $this->report; 
        $email["Subject"] = "Daemon report";
        $responsestr = "";
        $this->emailmanager->sendmail($email,2,0,$responsestr,$this->errorhandler,false);
     }
//==================================================================SHORTFALL
    private function shortfall($trace =false) {
        if ($this->trace || $trace ) { echo "Enter ".__METHOD__."<br>";} 
        $this->report .= date('Y-m-d H:i:s').": running shortfall(){$this->nl}";
        $today = date("Y-m-d H:i:s");
        $query = <<<  BOOKINGS
                SELECT  
                     s.id as session_id
                     ,s.start as sessdate
                     ,s.is_holiday
                     ,s.holiday_name
                     ,e.name as taskname
                     ,e.bookingalertperiods as alertperiods
                     ,e.bookingalertlevels as alertlevels
                     ,p.pagenumber as pagenumber
                     ,count(b.id) AS bookings
                FROM session s 
                JOIN task e ON e.id = s.task_id
                JOIN page p ON p.id = e.page_id
                JOIN session_role sr ON s.id = sr.session_id
                LEFT OUTER JOIN booking b on b.status = 'booked' AND  sr.id = b.session_role_id
                WHERE (s.start >= "{$today}") 
                GROUP BY s.id
                ORDER BY p.pagenumber, s.start
                BOOKINGS;
        $success = $this->bookingtable->query($query,$sessions,$numrows,$trace);
        if ($success) {
            $understaffedsessions = [];
            if (current($sessions) !== false) { // we have some sessions to check
                 // now iterate thru $sessions looking for sessions that have fewer bookings than required for the relevant interval
                $sessdate = (strtotime(current($sessions)["sessdate"]));  // first result
                $now = strtotime(date("Y-m-d H:i:s"));
                $daystosession = round(($sessdate - $now)/86400) ;// convert seconds to days
                do {
                    $intervals = explode(",",current($sessions)["alertperiods"]); // turn e.g. "7|21" into an array
                    $required = explode(",",current($sessions)["alertlevels"]); // turn e.g. "3|2" into an array
                    $intervalcount = count($intervals);
                    $lastinterval = end($intervals);  // advances array pointer to last element
                   // first establish which interval this session is in
                    if (current($sessions)["is_holiday"] == "0") {
                        if ($daystosession <= $lastinterval) { //then we are interested in this session
                            $idx = 0;
                            // by finding the first interval >= $daystosession
                            while (isset($intervals[$idx]) && $intervals[$idx] <= $daystosession ) { 
                                $idx++;
                            }
                            // compare actual bookings to required bookings 
                            if ($idx < $intervalcount) { // remember idx is zero-based so the last idx is (count - 1) 
                                if (isset($required[$idx])) { // cannot assume config data is good
                                    if (current($sessions)["bookings"] < $required[$idx]) {
                                        $understaffedsessions[$daystosession] =  ["date"=>date('l, jS F',$sessdate),"session"=>current($sessions)];  
                                    }
                                }
                            }
                        }
                    }
                    $nextresult = next($sessions);
                    if ($nextresult !== false) {
                        $sessdate = strtotime($nextresult["sessdate"]);
                        $daystosession = round(($sessdate - $now)/86400) ;// convert seconds to days
                    }
                } while ($daystosession < $lastinterval && ($nextresult !== false));
            }
            if (count($understaffedsessions)) {
               $this->sendshortfallemail($understaffedsessions,$trace);
            }
        }
        $this->report .= date('Y-m-d H:i:s').": leaving shortfall(){$this->nl}";
        if ($this->trace || $trace ) { echo "Leave ".__METHOD__." ".count($sessions)." sessions, ".count($understaffedsessions)." understaffedsessions <br>";} 
     }
    private function sendshortfallemail($understaffedsessions, $trace =false){
        if ($this->trace || $trace ) { echo "Enter ".__METHOD__."<br>";} 
        $success = $this->usermanager->getemailaddresses($volunteers,$trace);
        $e = 0;
        $emaillist = "";
        if ($success) {
            foreach ($volunteers as $volunteer) {
                $text = <<<TEXT
                    {$this->heading["text"]}
                    Dear {$volunteer["given_name"]},

                    We have some Food Bank sessions coming up for which we need additional volunteers. We need at least 3 volunteers per session.

                    TEXT;
                $html = <<<TEXT
                    {$this->heading["html"]}
                    <p>Dear {$volunteer["given_name"]},</p>
                    <p>We have some Food Bank sessions coming up for which we need additional volunteers. We need at least 3 volunteers per session.</p>
                    TEXT;
                $asterisks = 0; 
                $holstext = $holshtml = $hols = "";   
                foreach ($understaffedsessions as $gap => $session) {
                    if ($session["session"]["is_holiday"] == "1")  {
                        $hols = "(Please note - we are closed for {$session["session"]['holiday_name']} on {$session["date"]})";
                        $holstext .=  $hols."\n";
                        $holshtml .=  $hols."<br />";
                    } else  {
                        // DETERMINE IF THIS USER IS ALREADY BOOKED INTO THIS SESSION
                        $sessiondescr = $session["session"]["taskname"]." &nbsp;&nbsp;on ".substr($session["date"],strpos($session["date"],",")+1); // the date has been prepared for publication
                        $query =<<<QUERY
                        SELECT b.id
                        FROM booking b
                        JOIN session_role sr ON sr.id = b.session_role_id
                        WHERE b.user_id = {$volunteer["id"]} 
                                AND sr.session_id = {$session["session"]["session_id"]} 
                                AND b.status = 'booked'
                        QUERY;
                        $success = $this->bookingtable->query($query,$bookings,$numrows,false);
                        $yourbooking = "";
                        if ($numrows) {
                            $yourbooking = "* ";
                            $asterisks++;
                        }
                        $text .= $sessiondescr.$yourbooking.($gap <= 3?"  (URGENT)":"")."\n";
                        $html .= $sessiondescr.$yourbooking.($gap <= 3?"  (<strong>URGENT</strong>)":"")."<br />";
                    }
                }
                if ($hols !== "") {
                    $text .= "\n".$holstext."\n";
                    $html .= "<br />".$holshtml."<br />";
                }
                if ($asterisks) {                         
                    $text .= "\n* You are already on the roster for this session.
                    \n";
                    $html .= "<p><strong>* You are already on the roster for this session.</strong></p>";
                }
                $text .= <<<TEXT
                    If you can assist, please login to the Volunteers Roster at {$this->config["app"]["SITEURL"]} to make your bookings. If this will be your first login to the new on-line roster, please use the login credentials sent to you recently.

                    Alternatively, please contact {$this->config["app"]["RECEPTION"]} on {$this->config["app"]["RECEPTIONPHONE"]} ({$this->config["app"]["OFFICEHOURS"]}).

                    {$this->footer["text"]}
                    TEXT;
                $html .= <<<HTML
                    <p> If you can assist, please login to the <a href='{$this->config["app"]["SITEURL"]}'>Volunteers Roster Site</a> to make your bookings.  If this will be your first login to the new on-line roster, please use the login credentials sent to you recently.

                    Alternatively, please contact {$this->config["app"]["RECEPTION"]} on {$this->config["app"]["RECEPTIONPHONE"]} ({$this->config["app"]["OFFICEHOURS"]}).
                    {$this->footer["html"]}
                    HTML;
                $email["TextPart"]= $text; //$text;
                $email["HTMLPart"]= $html; //$html;
                if ($this->testing) {
                    $email["To"] =  [['Email' => "david.thomas@elliott-thomas.com.au","Name" => "dt"]];
                } else {
                    $email["To"] = [['Email' => $volunteer['email'],"Name" => $volunteer['given_name']." ".$volunteer['family_name']]];
                }
                $email["Subject"] = "Food Bank Roster";
                $responsestr = "";
                if (!$this->noemails) {
                    $this->emailmanager->sendmail($email,$volunteer["id"],0,$responsestr,$this->errorhandler,($this->trace || $trace));
                    $emaillist .= "{$volunteer["given_name"]} {$volunteer["family_name"]} at {$volunteer['email']},".PHP_EOL;
                }
            }  
            if ($this->noemails) {
                $this->report .= date('Y-m-d H:i:s').": NO apppeal emails to be sent {$this->nl}";
            } else {
                $this->report .= date('Y-m-d H:i:s').": sending appeal emails to {$this->nl}{$emaillist}";
            }
        }
        if ($this->trace || $trace ) { echo "Leave ".__METHOD__."<br>";} 
        return $success;
     }
//==================================================================DIGEST
    private function digest($trace =false) {
        if ($this->trace || $trace ) { echo "Enter ".__METHOD__."<br>";} 
        $this->report .= date('Y-m-d H:i:s').": running digest(){$this->nl}";
        $first = strtotime("today");
        $last  = date("Y-m-d",strtotime("+7days",$first));
        $first = date("Y-m-d",$first);
        $query = <<<  BOOKINGS
            SELECT  
                 u.id as userid,
                 u.given_name,
                 u.family_name,
                 u.email as email,   
                 group_concat(concat(DATE_FORMAT(s.start, '%Y-%m-%d'),"|",e.name) ORDER BY s.start   SEPARATOR "!!") as bookings 
            FROM booking b 
            JOIN user u ON u.id = b.user_id
            JOIN session_role sr ON sr.id = b.session_role_id
            JOIN session s ON s.id = sr.session_id
            JOIN task e ON e.id = s.task_id
            WHERE b.status = 'booked' AND s.start > "{$first}" AND s.start < "{$last}" 
            GROUP BY u.id
            ORDER BY u.id, s.start
        BOOKINGS;
        $success = $this->bookingtable->query($query,$results,$numrows,$trace);
        if ($success) {
            $emaillist = "";
            foreach ($results as $user) {
                $bookings = $this->listbookings($user["bookings"]);
                $html = <<<HTML
                    {$this->heading["html"]}
                    <p>Dear {$user["given_name"]},</p>
                    <p>We just wanted to remind you that you have volunteered for the following sessions this week: </p>
                    {$bookings["html"]}<br />
                    {$this->footer["html"]}
                    HTML;
                $text = <<<TEXT
                    {$this->heading["text"]}
                    Dear {$user["given_name"]},\n
                    We just wanted to remind you that you have volunteered for the following sessions this week: \n
                    {$bookings["text"]}\n
                    {$this->footer["text"]}
                    TEXT;
                $email = [];
                if ($this->testing) {
                    $email["To"] =  [['Email' => "david.thomas@elliott-thomas.com.au","Name" => "dt"]];
                } else {
                    $email["To"] = [['Email' => $user["email"], "Name" => ($user['given_name']." ".$user['family_name'])]];
                }
                $email["Subject"] = $this->config["app"]["ORGANISATIONNAME"];
                $email["TextPart"]= $text; //$text;
                $email["HTMLPart"]= $html; //$html;    
                if (!$this->noemails) {
                    $success = $this->emailmanager->sendmail($email,$user["userid"],'',$response,$this->trace || $trace,$this->testing);
                    $emaillist .= "{$this->indent4}{$user["given_name"]} {$user["family_name"]} at {$user['email']} for{$this->nl}{$bookings["html"]}";
                }
            }
            $this->report .= date('Y-m-d H:i:s').": sending reminder emails to <br>{$this->nl}$emaillist";
        }
        $this->report .= date('Y-m-d H:i:s').": leaving digest(){$this->nl}";
        if ($this->trace || $trace ) { echo "Leave ".__METHOD__."<br>";} 
        return $success;
     }
    private function listbookings($userbookings) {
        $bookinglist["text"] = $bookinglist["html"] = "";
        while ($userbookings != "") {
             lib::getnexttokeninstring($userbookings,$task,"!!");
             lib::getnexttokeninstring($task,$session,"|");
             $taskdate = date('l, j F Y',strtotime($session));
             $bookinglist["text"] .= "        {$task} (on {$taskdate})\n";
             $bookinglist["html"] .= "{$this->indent8}<strong>{$task}</strong> (on {$taskdate}){$this->nl}";
        }
        $bookinglist["text"] = $bookinglist["text"];
        $bookinglist["html"] = $bookinglist["html"];
        return $bookinglist;
     }
//================================================================== CHECK ROLES
    private function checkuserroles($trace =false) {
        if ($this->trace || $trace ) { echo "Enter ".__METHOD__."<br>";} 
        $query = <<<  SQL
            SELECT 
                 u.isadmin, 
                 u.given_name,
                 u.family_name,
                 count(ur.id) AS rolecount
            FROM user u 
            LEFT OUTER JOIN user_role ur ON u.id = ur.user_id
            GROUP BY u.id
            HAVING rolecount=0;  
        SQL;
        $success = $this->bookingtable->query($query,$results,$numrows,$trace);
        $this->report .= date('Y-m-d H:i:s').": running checkuserroles(){$this->nl}";
        $this->report .= date('Y-m-d H:i:s').": The following Users have not been assigned any Roles:{$this->nl}";
        if ($success) {
            if ($numrows) {
                foreach ($results as $user) {
                    $this->report .= ($user["isadmin"])?"":($this->indent8.$user["given_name"]." ".$user["family_name"].$this->nl);
                }
            }
        } else {
            $this->report .= date('Y-m-d H:i:s').": ERROR returned from SQL{$this->nl}";
        }
        $this->report .= date('Y-m-d H:i:s').": leaving checkuserroles(){$this->nl}";
        if ($this->trace || $trace ) { echo "Leave ".__METHOD__."<br>";} 
        return $success;
     }
//================================================================== CHECK SESSIONROLES
    private function checksessionroles($trace =false) {
        if ($this->trace || $trace ) { echo "Enter ".__METHOD__."<br>";} 
        $query = <<<  SQL
            INSERT INTO session_role (session_id, role_id, min_quantity, max_quantity)
                SELECT s.id, role_id, min_quantity, max_quantity 
                FROM  task_role er
                JOIN session s ON s.task_id = er.task_id
                WHERE s.id IN 
                    (SELECT s.id FROM session s
                    LEFT OUTER JOIN session_role sr ON s.id = sr.session_id
                    GROUP BY s.id HAVING COUNT(sr.id) = 0);
        SQL;
        $success = $this->bookingtable->query($query,$results,$numrows,$trace);
        $this->report .= date('Y-m-d H:i:s').": running checksessionroles(){$this->nl}";
        $this->report .= $this->indent8.$numrows." sessionroles added.{$this->nl}";
        $this->report .= date('Y-m-d H:i:s').": leaving checksessionroles(){$this->nl}";
        if ($this->trace || $trace ) { echo "Leave ".__METHOD__."<br>";} 
        return $success;
     }

//================================================================== STOCK ALERTS
    private function checkstockalerts($trace = false) {
        if ($this->trace || $trace) { echo "Enter ".__METHOD__."<br>"; }
        $this->report .= date('Y-m-d H:i:s').": running checkstockalerts(){$this->nl}";

        $items = []; $parents = []; $numrows = 0;
        $this->belowminimumreportmanager->getallrecords($items, '', $parents, $numrows, false, $trace);

        if ($numrows === 0) {
            $this->report .= $this->indent8."No stock items below minimum.{$this->nl}";
            $this->report .= date('Y-m-d H:i:s').": leaving checkstockalerts(){$this->nl}";
            return true;
        }

        $this->report .= $this->indent8."{$numrows} stock item(s) below minimum:{$this->nl}";
        foreach ($items as $item) {
            $this->report .= $this->indent8
                . htmlspecialchars($item['category_name']) . ' &mdash; '
                . htmlspecialchars($item['Name'])
                . ' (' . htmlspecialchars($item['location_name']) . ')'
                . ': current&nbsp;' . (int)$item['current_qty']
                . ', minimum&nbsp;' . (int)$item['minimum_qty']
                . ', shortfall&nbsp;' . abs((int)$item['variance'])
                . $this->nl;
        }

        $success = $this->usermanager->getstockalertrecipients($recipients, $trace);
        if (!$success || empty($recipients)) {
            $this->report .= $this->indent8."No stock alert recipients configured.{$this->nl}";
            $this->report .= date('Y-m-d H:i:s').": leaving checkstockalerts(){$this->nl}";
            return true;
        }

        $asofdate = date('j F Y');

        $text_rows = $html_rows = '';
        foreach ($items as $item) {
            $shortfall = abs((int)$item['variance']);
            $text_rows .= sprintf(
                "  %-30s %-25s %-20s  %7d  %7d  %8d\n",
                $item['category_name'],
                $item['Name'],
                $item['location_name'],
                (int)$item['current_qty'],
                (int)$item['minimum_qty'],
                $shortfall
            );
            $html_rows .= '<tr>'
                . '<td style="padding:3px 8px">' . htmlspecialchars($item['category_name'])  . '</td>'
                . '<td style="padding:3px 8px">' . htmlspecialchars($item['Name'])           . '</td>'
                . '<td style="padding:3px 8px">' . htmlspecialchars($item['location_name'])  . '</td>'
                . '<td style="padding:3px 8px;text-align:right">' . (int)$item['current_qty'] . '</td>'
                . '<td style="padding:3px 8px;text-align:right">' . (int)$item['minimum_qty'] . '</td>'
                . '<td style="padding:3px 8px;text-align:right;color:#cc0000">' . $shortfall   . '</td>'
                . '</tr>';
        }

        $html_table = '<table style="border-collapse:collapse;font-size:0.95em">'
            . '<thead><tr style="background:#f0f0f0">'
            . '<th style="padding:4px 8px;text-align:left">Category</th>'
            . '<th style="padding:4px 8px;text-align:left">Stock Item</th>'
            . '<th style="padding:4px 8px;text-align:left">Location</th>'
            . '<th style="padding:4px 8px;text-align:right">Current</th>'
            . '<th style="padding:4px 8px;text-align:right">Minimum</th>'
            . '<th style="padding:4px 8px;text-align:right">Shortfall</th>'
            . '</tr></thead>'
            . '<tbody>' . $html_rows . '</tbody></table>';

        $emaillist = '';
        foreach ($recipients as $user) {
            $html = $this->heading["html"]
                . '<p>Dear ' . htmlspecialchars($user['given_name']) . ',</p>'
                . '<p>The following stock items are below their minimum quantities as at ' . $asofdate . ':</p>'
                . $html_table
                . $this->footer["html"];

            $text = $this->heading["text"]
                . "\nDear {$user['given_name']},\n\n"
                . "The following stock items are below their minimum quantities as at {$asofdate}:\n\n"
                . sprintf("  %-30s %-25s %-20s  %7s  %7s  %8s\n", 'Category', 'Stock Item', 'Location', 'Current', 'Minimum', 'Shortfall')
                . str_repeat('-', 105) . "\n"
                . $text_rows
                . "\n" . $this->footer["text"];

            $email = [];
            $email['Subject']  = 'Stock Alert: Items Below Minimum';
            $email['TextPart'] = $text;
            $email['HTMLPart'] = $html;
            if ($this->testing) {
                $email['To'] = [['Email' => 'david.thomas@elliott-thomas.com.au', 'Name' => 'dt']];
            } else {
                $email['To'] = [['Email' => $user['email'], 'Name' => $user['given_name'] . ' ' . $user['family_name']]];
            }

            if (!$this->noemails) {
                $responsestr = '';
                $this->emailmanager->sendmail($email, $user['id'], 0, $responsestr, $this->errorhandler, ($this->trace || $trace));
                $emaillist .= "{$user['given_name']} {$user['family_name']} at {$user['email']},{$this->nl}";
            }
        }

        if ($this->noemails) {
            $this->report .= $this->indent8."Stock alert emails not sent (noemails=true).{$this->nl}";
        } else {
            $this->report .= $this->indent8."Stock alert emails sent to: {$this->nl}{$emaillist}";
        }

        $this->report .= date('Y-m-d H:i:s').": leaving checkstockalerts(){$this->nl}";
        if ($this->trace || $trace) { echo "Leave ".__METHOD__."<br>"; }
        return true;
    }

//================================================================== END

}
