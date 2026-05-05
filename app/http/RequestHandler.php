<?php
namespace app\http;
use \lib\StdLib as lib;
class RequestHandler   // extends \fw\http\RequestHandler
{
    protected $trace = false;
    private $doctype = '<!DOCTYPE HTML>';
    protected $pagenum;
    protected $frompagenum;
    protected $nextpagenum;
    protected $loginform;
    protected $form;
    protected $errorhandler;
    private   $loginrequired;
    private   $multiselect; /* includes a multiselect on the page  */
    protected $requestdata;
    protected $config;
    protected $db;
    protected $isajax;
    protected $session;
    protected $manager;
    protected $configmanager;
    protected $sessionmanager;
    private $p = ["#\n +\[#","#ray\n +#","#\n +\)#","# => #"];
    private $r = ["\t[","ray","\t)","="];

    public function __construct(private \app\controller\RequestProcessController $requestprocesscontroller,
                                private \app\controller\ViewController $viewcontroller,
                                private \app\controller\manager\ManagerCollection $managercollection
                             ) {
        if ($this->trace) { echo gtab(1)."Enter ".__METHOD__."<br>"; }
        date_default_timezone_set('Australia/Melbourne');
        if ($this->trace) { echo gtab(-1)."Leave ".__METHOD__."<br>"; }
     }
    public function __destruct() {
        if ($this->trace) { echo gtab()."Enter ".__METHOD__."<br>"; }
        //parent::__destruct();    
     }
    public function init($errorhandler,$session,$db,$trace=false) {
        if ($this->trace ||$trace) { echo gtab(1)."Enter ".__METHOD__."<br>"; }
        $this->requestdata = $_REQUEST;
        $this->isajax = $this->requestdata["ajax"]??0 == "1";
        $this->db = $db;
        $this->errorhandler = $errorhandler; 
        $this->session = $session;
        $this->configmanager = $this->managercollection->ConfigManager();
        $norights = false;
        try {
            // dbconnection.php contains code to connect to the database and then  
            // complete the population of the $config array with the database settings
            // It resolves a circular dependency in the initialisation process.
            // This code is shared with daemon.php 
            $dbc = APP_DIR.'database/dbconnection.php';
            include $dbc;
            connectandconfigure($this->db,$this->config,$this->configmanager); 
            // ... so now we can initialise $errorhandler
            $this->errorhandler->init($this->config); 
            // ... and pass it to $db. 
            $this->db->init($this->errorhandler);
            $this->session->init($this->errorhandler,$this->db,$this->managercollection,$this->requestdata,$norights,$this->config,false);
            $this->errorhandler->initphase2($this->session); 
        } catch (\Exception $e) {
            die('<br>Exception during initialisation: '.$e->getMessage()."\n");
        }
        if ($this->trace) { echo gtab(-1)."Leave ".__METHOD__." user -> {$this->session->getuserid()}<br>"; }
     }
    public function processrequest($trace=false){
        if ($this->trace) { echo gtab(1)."Enter ".__METHOD__."<br>"; }
        $follow=false;
        $output = "";
        $errormessage = "";
        try {
            if (isset($this->requestdata)) {
                // if ($follow){ lib::pr(__METHOD__.">>PROCEEDING > ",$this->requestdata);     }           
                $proceed = true;
                if ($this->isajax) {
                    // lib::pr($this->requestdata);                    
                    $action = $this->requestdata["action_id"]??"";
                    switch ($action) { // these requests mostly bypass the requestprocesscontroller and call the managers directly to generate data 
                        case "bookinghistory" :
                            $sessionid = $this->requestdata["id"];
                            $this->sessionmanager = $this->managercollection->sessionmanager();
                            $this->sessionmanager->init($this->session);
                            $this->sessionmanager->getbookinghistory($history,$sessionid,$numrows,false);
                            $this->viewcontroller->init($this->session,$this->managercollection,$this->errorhandler,$trace);
                            $output = $this->viewcontroller->processajaxrequest($action,"",$history,$errormessage,$trace);
// lib::vd($history);
                            break; 
                        case "attendancereport" : // total beneficiaries per session across a date range
                            $dates = $this->requestdata["thedata"];
                            $this->manager = $this->managercollection->ClientManager();
                            $this->manager->init($this->session);
                            $this->manager->getsessionreportdata($dates,$reportdata,$numrows,false);
                            $this->viewcontroller->init($this->session,$this->managercollection,$this->errorhandler,$trace);
                            $output = $this->viewcontroller->processajaxrequest($action,$dates,$reportdata,$errormessage,$trace);
                            break; 
                         case "generatecsvreport" : // process a sql query
                            $query = $this->requestdata["thedata"];
                            $this->manager = $this->managercollection->ReportManager();
                            $this->manager->init($this->session);
                            if ($this->manager->generatereport($query,$reportdata,$numrows,false)) {
                                $this->viewcontroller->init($this->session,$this->managercollection,$this->errorhandler,$trace);
                                $output = $this->viewcontroller->processajaxrequest($action,"",$reportdata,$errormessage,$trace);
                            } else {
                                $output = "!!";
                            }
                            break; 
                        case "deleteclientsession" :
                            $this->sessionmanager = $this->managercollection->sessionmanager();
                            $this->sessionmanager->init($this->session);
                            $output = $this->sessionmanager->deleteclientsession($this->requestdata["id"]);
                            break;
                       case "addclientsession" :    
                             $this->sessionmanager = $this->managercollection->sessionmanager();
                            $this->sessionmanager->init($this->session);
                            $thedata = explode(',',$this->requestdata["thedata"]);
                            $output = $this->sessionmanager->addclientsession($thedata[0],$thedata[1]);
                            break;
                        case "stockevent_createevent":
                            $this->manager = $this->managercollection->StockEventManager();
                            $this->manager->init($this->session);
                            $d               = json_decode($this->requestdata["thedata"] ?? '{}', true);
                            $event_type      = $d["event_type"]      ?? '';
                            $location1_id    = $d["location1_id"]    ?: null;
                            $location2_id    = $d["location2_id"]    ?: null;
                            $supplier_id     = $d["supplier_id"]     ?: null;
                            $stock_client_id = $d["stock_client_id"] ?: null;
                            $event_id        = 0;
                            $errormsg        = '';
                            $success = $this->manager->createevent($event_type, $location1_id, $location2_id, $supplier_id, $stock_client_id, $event_id, $errormsg);
                            $output = json_encode(['success' => $success, 'event_id' => $event_id, 'error' => $errormsg]);
                            break;
                        case "stockevent_getinprogressevent":
                            $this->manager = $this->managercollection->StockEventManager();
                            $this->manager->init($this->session);
                            $d            = json_decode($this->requestdata["thedata"] ?? '{}', true);
                            $event_type   = $d["event_type"]   ?? '';
                            $location1_id = $d["location1_id"] ?: null;
                            $location2_id = $d["location2_id"] ?: null;
                            $supplier_id  = $d["supplier_id"]  ?: null;
                            $result       = [];
                            $numrows      = 0;
                            $success = $this->manager->getinprogressevent($event_type, $location1_id, $location2_id, $supplier_id, $result, $numrows);
                            $output = json_encode(['success' => $success, 'found' => $numrows > 0, 'event' => $result]);
                            break;
                        case "stockevent_savemovement":
                            $this->manager = $this->managercollection->StockEventManager();
                            $this->manager->init($this->session);
                            $d           = json_decode($this->requestdata["thedata"] ?? '{}', true);
                            $stock_id    = $d["stock_id"]    ?? 0;
                            $event_id    = $d["event_id"]    ?? 0;
                            $location_id = $d["location_id"] ?? 0;
                            $value       = $d["value"]       ?? 0;
                            $event_type  = $d["event_type"]  ?? '';
                            $movement_id = (int)($d["movement_id"] ?? 0);
                            $errormsg    = '';
                            $success = $this->manager->savemovement($stock_id, $event_id, $location_id, $value, $event_type, $movement_id, $errormsg);
                            $output = json_encode(['success' => $success, 'movement_id' => $movement_id, 'error' => $errormsg]);
                            break;
                        case "stockevent_saveweight":
                            $this->manager = $this->managercollection->StockEventManager();
                            $this->manager->init($this->session);
                            $d        = json_decode($this->requestdata["thedata"] ?? '{}', true);
                            $event_id = $d["event_id"] ?? 0;
                            $weight   = $d["weight"]   ?? '';
                            $errormsg = '';
                            $success  = $this->manager->saveweight($event_id, $weight, $errormsg);
                            $output   = json_encode(['success' => $success, 'error' => $errormsg]);
                            break;
                        case "stockevent_closeevent":
                            $this->manager = $this->managercollection->StockEventManager();
                            $this->manager->init($this->session);
                            $d        = json_decode($this->requestdata["thedata"] ?? '{}', true);
                            $event_id = $d["event_id"] ?? 0;
                            $errormsg = '';
                            $success  = $this->manager->closeevent($event_id, $errormsg);
                            $output   = json_encode(['success' => $success, 'error' => $errormsg]);
                            break;
                        case "stockevent_cancelevent":
                            $this->manager = $this->managercollection->StockEventManager();
                            $this->manager->init($this->session);
                            $d        = json_decode($this->requestdata["thedata"] ?? '{}', true);
                            $event_id = $d["event_id"] ?? 0;
                            $errormsg = '';
                            $success  = $this->manager->cancelevent($event_id, $errormsg);
                            $output   = json_encode(['success' => $success, 'error' => $errormsg]);
                            break;
                        case "stockitemlocation_getstock":
                            $this->manager = $this->managercollection->LocationManager();
                            $this->manager->init($this->session);
                            $d           = json_decode($this->requestdata["thedata"] ?? '{}', true);
                            $location_id = $d["location_id"] ?? 0;
                            $results     = [];
                            $numrows     = 0;
                            $this->manager->getstockwithtargets($location_id, $results, $numrows);
                            $this->viewcontroller->init($this->session, $this->managercollection, $this->errorhandler, $trace);
                            $output = $this->viewcontroller->processajaxrequest("stockitemlocation_getstock", $d, $results, $errormessage, $trace);
                            break;
                        case "stockevent_getstock":
                            $this->manager = $this->managercollection->StockEventManager();
                            $this->manager->init($this->session);
                            $d           = json_decode($this->requestdata["thedata"] ?? '{}', true);
                            $event_id    = $d["event_id"]    ?? 0;
                            $category_id = $d["category_id"] ?? '';
                            $supplier_id = $d["supplier_id"] ?? '';
                            $results     = [];
                            $numrows     = 0;
                            $this->manager->getstockforevent($event_id, $category_id, $results, $numrows, $supplier_id);
                            $this->viewcontroller->init($this->session, $this->managercollection, $this->errorhandler, $trace);
                            $output = $this->viewcontroller->processajaxrequest("stockevent_getstock", $d, $results, $errormessage, $trace);
                            break;
                        default: $output = "Unknown request action: ".$action;
                    }
                } else {
                    if ($proceed = ($this->session->isloginsubmit() || $this->session->isloggedin($greeting,$errormessage)) ) {
                            if ($follow){ lib::e(__METHOD__.">>PROCEED > ",$this->session->getpagenum(),$this->session->isloginsubmit());}
                        $this->requestprocesscontroller->init($this->session,$this->managercollection,$this->errorhandler);
                            if ($follow){  lib::e(__METHOD__.">>processformdata enter > ",$this->session->getpagenum()); }               
                        $proceed = $this->requestprocesscontroller->processformdata($errormessage,$trace);
                            if ($follow){lib::e(__METHOD__.">>processformdata complete ",$proceed,$errormessage,$this->session->getpagenum(),$this->session->getuserid());}
                            if ($follow){lib::pr($this->session->getrequestdata()); }    
                    }
                    $this->viewcontroller->init($this->session,$this->managercollection,$this->errorhandler,$trace);
                            if ($follow){  lib::e(__METHOD__.">>viewcontroller->init ",$errormessage);}            
                    $errormessage =  str_replace("<BR>","<BR>\n",str_replace("<br>","<br>\n",$errormessage)); 
                    $output = $this->viewcontroller->processrequest($errormessage,$trace);
                            if ($follow){ lib::e(__METHOD__.">>viewcontroller->processrequest ",$this->session->getpagenum());}           
                }
            } else {
                $output = "Error - badly formed request.";
            }
        } catch(\Exception $e) {
            $output = __METHOD__." : ".$e->__toString();
        }
       // deliver the page  
        $this->errorhandler->closelog();
        if ($this->trace) { echo gtab(-1)."Leave ".__METHOD__."<br>"; }
        return $output;
    }
}
