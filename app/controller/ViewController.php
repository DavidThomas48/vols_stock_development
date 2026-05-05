<?php
namespace app\controller;
use \lib\StdLib as lib;
class ViewController {
    private $trace=false;
    private $doctype = '<!DOCTYPE html>';
    private $multiselect;
    private $session;
    private $errorhandler;
    private $menumanager;
    private $user_id;
    private $user_menu_number;
    private $pagenum;
    private $targetpage;
    private $bodysection;
    private $data;
    private $rights;
    private $orderby;
    private $form;
    private $config;
    private $mgrs;
    private $requestdata;
    private $rostermanager;
    private $usermanager;
    private $clientmgr;
    private $taskmgr;
    private $isadmin;
    private $manager;
    private $rolemanager;
    private $processerrormessage;
    private $p = ["#\n +\[#","#ray\n +#","#\n +\)#","# => #"];
    private $r = ["\t[","ray","\t)","="];
	public  function __construct(protected \app\view\head\HTMLHead $headsection,
                                 protected \app\view\body\BodyCollection $bodies,
                                 protected \app\view\form\FormCollection $forms,
                            ){
        if ($this->trace ) { echo gtab(0)."Enter ".__METHOD__."<br>\n"; }
	 }
    public  function init($session,$managercollection,$errorhandler) {
        if ($this->trace ) { echo gtab(1)."Enter ".__METHOD__."<br>\n"; }
        $this->session = $session;
        // echo __METHOD__." user = ".$this->session->getuserid()."<br>";
        $this->config = $this->session->getconfig();
        $this->errorhandler = $errorhandler;
        $this->mgrs = $managercollection;
        $this->requestdata = $this->session->getrequestdata();
        $this->user_id = $this->session->getuserid();
        $this->usermanager = $this->session->usermanager();
        $this->user_menu_number = $this->usermanager->getuser_menu_number();
        $this->user_menu_number = $this->user_menu_number??0;
        $this->isadmin = $this->session->isadmin();
        $this->menumanager = $this->session->getmenumanager();
        $this->pagenum = $this->session->getpagenum();
        $this->rights = $this->usermanager->getuserrights();
        // lib::pr(__METHOD__,$this->user_id,$this->isadmin,$this->rights);
        // $rights is an array of "pagenumber||actioncode" owned by this user
        if ($this->pagenum == $this->session->homepage()) { 
            if ($this->isadmin) {
                // lib::pr($this->config["app"]);
                $this->pagenum = $this->config["app"]["HOMEPAGE"];
            } else {
                // this means we have to find the default page for this user, from their rights 
                // rights have the format "[pagenum]||[actioncode]"
                // we go for the lowest numbered page
                sort($this->rights);
                // lib::pr($this->session->getuserid(),$this->rights);     
                $first = array_key_exists(0,$this->rights) ? $this->rights[0]: 0;
                $this->pagenum = substr($first,0,strpos($first,"||"));
            }
            $this->session->putpagenum($this->pagenum,'' ); 
        }
        $this->orderby = "";
        $this->headsection->init($this->session,$this->pagenum,$this->targetpage);
        if ($this->trace ) { echo gtab(-1)."Leave ".__METHOD__."<br>\n";lib::pr($this->user_id,$this->pagenum,$this->rights,$this->isadmin); }
     }
    // ========================================================================================
    public  function processajaxrequest($action,$formdata,$data,&$errormessage,$trace=false) {
        if ($this->trace ) { echo gtab(1)."Enter ".__METHOD__."<br>\n"; }
        $output = "";
        switch ($action) {
            case "bookinghistory" :
                    $this->form = $this->forms->SessionForm();
                    $this->form->init($this->session,[],"",false,[],[]);
                    $historyrows = $this->form->formathistory($data);
                    reset($historyrows);
                    $output = '<div id="history">'.current($historyrows); 
                    while (next($historyrows) !== false) {
                        $output .= current($historyrowsk);
                    }
                    $output .= '</div>'; 
                    break;
            case "attendancereport" :
                    $this->form = $this->forms->AttendanceReportForm();
                    $this->form->init($this->session,[],"",false,[],[]);
                    $output = "<div id='attendancedata'>".$this->form->rendersessionreport($data,$formdata)."</div>"; 
                    break;
            case "stockitemlocation_getstock":
                    $this->form = $this->forms->LocationForm();
                    $output = $this->form->rendertargetstable($data);
                    break;
            case "stockevent_getstock":
                    $event_type = $formdata["event_type"] ?? '';
                    $this->form = $this->getStockEventForm($event_type);
                    $output = $this->form->renderstocktable($data);
                    break;
            case "generatecsvreport":
                    foreach ($data as $row) {
                        foreach ($row as $field) {
                            $pattern = '/[",\n\r]/';
                            if (preg_match($pattern,$field)) {
                                preg_replace('/"/','""',$field);
                                $output .= '"'.$field.'",';
                            } else {
                                $output .= $field.",";
                            }
                        }
                        $output .= "\n";
                    }
                    break;
            default :
                $output = "Unknown action: ".$action;
        }
        if ($this->trace || $trace) { echo gtab(-1)."Leave ".__METHOD__."<br>"; }
        return $output;
     }
    public  function processrequest($processerrormessage="",$trace=false) {
        if ($this->trace || $trace ) { echo gtab(1)."Enter ".__METHOD__." target = ".$this->pagenum."<br>\n"; }
        // This routine builds the entire response to the request
        unset($this->manager);
        unset($this->form);
        $this->setpages($errormessage,$trace) ; 
        $errormessage .= ($errormessage==""?"":"<br><br>").$processerrormessage;
        $success = $this->prepareHTMLbody($errormessage,$trace); // do this first to catch when login is required
         if ($success) {// any errormessage will be displayed in form
            $menu  = $this->menumanager->buildmenu ($this->pagenum,$this->rights,$this->isadmin,$this->user_menu_number);
            $stockalertpopup = $this->user_id ? $this->buildstockalertpopup() : '';
            $body  = $this->bodysection->render($this->pagenum,$this->rights,$this->isadmin,$menu,$errormessage,$trace,'',$stockalertpopup);
            $head  = $this->headsection->render($this->pagenum,$this->multiselect,$trace);
            if ($this->trace ) { echo gtab(-1)."Leave ".__METHOD__."<br>\n"; }
            return $this->doctype."\r\n<html lang='en-AU'>\r\n".$head.$body."</html>";
        }
        if ($this->trace || $trace) { echo gtab(-1)."Leave ".__METHOD__."... Sorry. Something went wrong<br>"; }
        return $this->doctype."<html><body><h1>Sorry. Something went wrong...</h1><p>".$errormessage."</p></body></html>";
     }  
    // ========================================================================================
    private function setpages(&$errormessage, $trace=false) {
        if ($this->trace ) { echo gtab(1)."Enter ".__METHOD__." target = ".$this->pagenum."<br>\n"; }
        $c2v =  fn($val) => $val;
        $mm = $this->menumanager;
        unset($this->manager);
        unset($this->form);
        // echo "2:".$this->pagenum.$errormessage."<br>\n";
        $permission = 1;
        $tracker = 0;
        switch ($this->pagenum) {
            case $c2v($mm::STARTNEWPWPAGE)  : $this->form = $this->forms->startnewpasswordform(); 
            case $c2v($mm::ENTERCODEPAGE)   : $this->form = isset($this->form)?$this->form:$this->forms->confirmcodeform(); 
            case $c2v($mm::ENTERNEWPWPAGE)  : $this->form = isset($this->form)?$this->form:$this->forms->enternewpasswordform(); 
            case  "": 
            case   0:
            case $c2v($mm::LOGOUTPAGE)      : 
            case $c2v($mm::LOGINPAGE)       : $this->form = isset($this->form)?$this->form:$this->forms->loginform(); 
                                                $this->setthispage(1,0,$this->usermanager,$this->form,$errormessage,'', $trace);
                                                break;

            case $c2v($mm::ROSTER10)        : //$permission = in_array($mm::ROSTER10."||VIEW",$this->rights);
            case $c2v($mm::ROSTER9)         : //$permission = $permission || in_array($mm::ROSTER9."||VIEW",$this->rights);
            case $c2v($mm::ROSTER8)         : //$permission = $permission || in_array($mm::ROSTER8."||VIEW",$this->rights);
            case $c2v($mm::ROSTER7)         : //$permission = $permission || in_array($mm::ROSTER7."||VIEW",$this->rights);
            case $c2v($mm::ROSTER6)         : //$permission = $permission || in_array($mm::ROSTER6."||VIEW",$this->rights);
            case $c2v($mm::ROSTER5)         : //$permission = $permission || in_array($mm::ROSTER5."||VIEW",$this->rights);
            case $c2v($mm::ROSTER4)         : //$permission = $permission || in_array($mm::ROSTER4."||VIEW",$this->rights);
            case $c2v($mm::ROSTER3)         : //$permission = $permission || in_array($mm::ROSTER3."||VIEW",$this->rights);
            case $c2v($mm::ROSTER2)         : //$permission = $permission || in_array($mm::ROSTER2."||VIEW",$this->rights);
            case $c2v($mm::ROSTER1)         : //$permission = $permission || in_array($mm::ROSTER1."||VIEW",$this->rights);
                                                if ($this->setthispage(0,$this->pagenum,$this->usermanager,$this->forms->Rosterform(),$errormessage, $trace)) {
                                                    $this->rolemanager = $this->mgrs->Rolemanager();
                                                    $this->rolemanager->init($this->session);
                                                    $this->rostermanager = $this->mgrs->RosterManager();
                                                    $this->rostermanager->init($this->session);
                                                    $this->form->init($this->session);
                                                }
                                                break;
            case $c2v($mm::CLIENTADMINPAGE) : $this->setthispage(0,$this->pagenum,$this->mgrs->ClientManager(),$this->forms->ClientAdminForm(),$errormessage,"given_name",$trace);
                                                break;
            case $c2v($mm::CLIENTVOLSPAGE)  : $this->setthispage(0,$this->pagenum,$this->mgrs->ClientManager(),$this->forms->ClientVolsForm(),$errormessage,"given_name",$trace);
                                                break;
            case $c2v($mm::ATTENDANCEADMINPAGE): 
                                                $this->setthispage(0,$this->pagenum,$this->mgrs->SessionManager(),$this->forms->AttendanceAdminForm(),$errormessage,"task_id, start", $trace);
                                                $tracker = 1;
            case $c2v($mm::ATTENDANCEVOLSPAGE):
                                                if (!$tracker) {
                                                    $this->setthispage(0,$this->pagenum,$this->mgrs->SessionManager(),$this->forms->AttendanceVolsForm(),$errormessage,"task_id, start", $trace);
                                                }
                                                $this->clientmgr = $this->mgrs->ClientManager();
                                                $this->clientmgr->init($this->session);
                                                $this->form->init($this->session);
                                                break;
            case $c2v($mm::CLIENTREPORTPAGE): $this->setthispage(0,$this->pagenum,$this->mgrs->ClientManager(),$this->forms->AttendanceReportForm(),$errormessage,"",$trace);break;
            case $c2v($mm::REPORTPAGE)      : $this->setthispage(0,$this->pagenum,$this->mgrs->ReportManager(),$this->forms->ReportForm(),$errormessage,'',$trace);break;
            case $c2v($mm::ROLEPAGE)        : $this->setthispage(0,$this->pagenum,$this->mgrs->RoleManager(),$this->forms->RoleForm(),$errormessage,'',$trace);break;
            case $c2v($mm::PROFILEPAGE)     : $this->setthispage(0,$this->pagenum,$this->usermanager,$this->forms->UserProfileForm(),$errormessage,'',$trace);break;
            case $c2v($mm::USERPAGE)        : $this->setthispage(0,$this->pagenum,$this->usermanager,$this->forms->UserForm(),$errormessage,"given_name");break;
            case $c2v($mm::ACTIONPAGE)      : $this->setthispage(0,$this->pagenum,$this->mgrs->ActionManager(),$this->forms->ActionForm(),$errormessage,"name",$trace);break;
            case $c2v($mm::PAGEPAGE)        : $this->setthispage(0,$this->pagenum,$this->mgrs->PageManager(),$this->forms->PageForm(),$errormessage,"pagenumber",$trace);break;
            case $c2v($mm::TASKPAGE)        : $this->setthispage(0,$this->pagenum,$this->mgrs->TaskManager(),$this->forms->TaskForm(),$errormessage,"name",$trace);break;
            case $c2v($mm::SESSIONPAGE)     : $this->setthispage(0,$this->pagenum,$this->mgrs->SessionManager(),$this->forms->SessionForm(),$errormessage,"task_id, start",$trace);break;
            case $c2v($mm::MENUITEMPAGE)    : $this->setthispage(0,$this->pagenum,$this->mgrs->MenuManager(),$this->forms->MenuItemForm(),$errormessage,"menucode",$trace);break;
            case $c2v($mm::CONFIGPAGE)      : $this->setthispage(0,$this->pagenum,$this->mgrs->ConfigManager(),$this->forms->ConfigForm(),$errormessage,"",$trace);break;
                case $c2v($mm::STOCKCATEGORYPAGE): $this->setthispage(0,$this->pagenum,$this->mgrs->StockCategoryManager(),$this->forms->StockCategoryForm(),$errormessage,"Name",$trace);break;
                case $c2v($mm::STOCKPAGE)       : $this->setthispage(0,$this->pagenum,$this->mgrs->StockManager(),$this->forms->StockForm(),$errormessage,"Name",$trace);break;
                case $c2v($mm::STOCKLEVELREPORTPAGE): $this->setthispage(0,$this->pagenum,$this->mgrs->StockLevelReportManager(),$this->forms->StockLevelReportForm(),$errormessage,"",$trace);break;
                case $c2v($mm::LOCATIONPAGE)        : $this->setthispage(0,$this->pagenum,$this->mgrs->LocationManager(),$this->forms->LocationForm(),$errormessage,"name",$trace);break;
                case $c2v($mm::STOCKSUPPLIERCATEGORYPAGE) : $this->setthispage(0,$this->pagenum,$this->mgrs->StockSupplierCategoryManager(),$this->forms->StockSupplierCategoryForm(),$errormessage,"name",$trace);break;
                case $c2v($mm::STOCKSUPPLIERPAGE)   : $this->setthispage(0,$this->pagenum,$this->mgrs->StockSupplierManager(),$this->forms->StockSupplierForm(),$errormessage,"name",$trace);break;
                case $c2v($mm::STOCKCLIENTPAGE)     : $this->setthispage(0,$this->pagenum,$this->mgrs->StockClientManager(),$this->forms->StockClientForm(),$errormessage,"name",$trace);break;
                case $c2v($mm::STOCKTAKEEVENTPAGE)  : $this->setthispage(0,$this->pagenum,$this->mgrs->StockEventManager(),$this->forms->StocktakeEventForm(),$errormessage,"",$trace);break;
                case $c2v($mm::DELIVERYEVENTPAGE)   : $this->setthispage(0,$this->pagenum,$this->mgrs->StockEventManager(),$this->forms->DeliveryEventForm(),$errormessage,"",$trace);break;
                case $c2v($mm::TRANSFEREVENTPAGE)   : $this->setthispage(0,$this->pagenum,$this->mgrs->StockEventManager(),$this->forms->TransferEventForm(),$errormessage,"",$trace);break;
                case $c2v($mm::ADJUSTMENTEVENTPAGE) : $this->setthispage(0,$this->pagenum,$this->mgrs->StockEventManager(),$this->forms->AdjustmentEventForm(),$errormessage,"",$trace);break;
            default: die(__METHOD__." Unknown pagenum : {$this->pagenum}");
        }
       if ($this->trace ) { echo gtab(-1)."Leave ".__METHOD__."<br>\n"; }
     }    
    private function setthispage($permission,$pagenum,$manager,$form,&$errormessage,$orderby='',$trace=false) {
        if ($this->trace  || $trace) { echo gtab(1)."Enter ".__METHOD__." target = ".$this->pagenum."<br>\n"; }
        if ($permission || $this->isadmin || (($pagenum == 0) || in_array($pagenum."||VIEW",$this->rights))) {
            $this->manager = $manager; 
            $this->manager->init($this->session,$trace); 
            $this->form = $form;
            $this->orderby = $orderby;
        } else {
            $this->session->putpagenum(0,"");
            $this->pagenum = 0;
            $errormessage = "You do not have permission to view that page. Please login again.";        
            $this->setpages($errormessage) ;
        }
        if ($this->trace || $trace) { echo gtab(-1)."Leave ".__METHOD__."<br>"; }
        return true;
     }
    private function prepareHTMLbody (&$errormessage,$trace=false) {
        if ($this->trace || $trace) { echo gtab(1)."Enter ".__METHOD__.$this->pagenum."<br>"; }
        $c2v =  fn($val) => $val;  // convert constant to a variable
        $user_id = $this->session->getuserid();
        // choose the body section according to the page number requested
        $mm = $this->menumanager;
        switch ($this->pagenum) {
            case  "": 
            case   0:
            case $c2v($mm::LOGOUTPAGE)            : 
            case $c2v($mm::STARTNEWPWPAGE)        : 
            case $c2v($mm::ENTERCODEPAGE)         : 
            case $c2v($mm::ENTERNEWPWPAGE)        : 
            case $c2v($mm::LOGINPAGE)             :$success = $this->prepare_login_body($errormessage); break;
            case $c2v($mm::ROSTER10)              :
            case $c2v($mm::ROSTER9)               :
            case $c2v($mm::ROSTER8)               :
            case $c2v($mm::ROSTER7)               :
            case $c2v($mm::ROSTER6)               :
            case $c2v($mm::ROSTER5)               :
            case $c2v($mm::ROSTER4)               :
            case $c2v($mm::ROSTER3)               :
            case $c2v($mm::ROSTER2)               :
            case $c2v($mm::ROSTER1)               :$success = $this->prepare_roster_body($user_id,$errormessage,$trace); break;
            case $c2v($mm::ATTENDANCEADMINPAGE)   :$success = $this->prepare_attendance_body($user_id,$errormessage,$trace); break;
            case $c2v($mm::ATTENDANCEVOLSPAGE)    :$success = $this->prepare_attendance_body($user_id,$errormessage,$trace); break;
            case $c2v($mm::CLIENTREPORTPAGE)      :$success = $this->prepare_attendancereport_body($user_id,$errormessage,$trace); break;
            // note to self - explore possibility of a general "prepare_CRUD_body()"" to replace the following calls
            case $c2v($mm::PROFILEPAGE)           :$success = $this->prepare_profile_body($user_id,$errormessage,$trace); break;
            case $c2v($mm::USERPAGE)              :$success = $this->prepare_user_body($user_id,$errormessage,$trace); break;
            case $c2v($mm::CLIENTADMINPAGE)       :
            case $c2v($mm::CLIENTVOLSPAGE)        :$success = $this->prepare_client_body($user_id,$errormessage,$trace); break;
            case $c2v($mm::PAGEPAGE)              :$success = $this->prepare_page_body($user_id,$errormessage,$trace); break;
            case $c2v($mm::ROLEPAGE)              :$success = $this->prepare_role_body($user_id,$errormessage,$trace); break;
            case $c2v($mm::TASKPAGE)              :$success = $this->prepare_task_body($user_id,$errormessage,$trace); break;
            case $c2v($mm::SESSIONPAGE)           :$success = $this->prepare_session_body($user_id,$errormessage,$trace); break;
            case $c2v($mm::MENUITEMPAGE)          :$success = $this->prepare_menuitem_body($user_id,$errormessage,$trace); break;
            case $c2v($mm::CONFIGPAGE)            :$success = $this->prepare_config_body($user_id,$errormessage,$trace); break;
            case $c2v($mm::STOCKLEVELREPORTPAGE)  :$success = $this->prepare_stocklevelreport_body($user_id,$errormessage,$trace); break;
            case $c2v($mm::LOCATIONPAGE)          :$success = $this->prepare_location_body($user_id,$errormessage,$trace); break;
            case $c2v($mm::STOCKCLIENTPAGE)       :$success = $this->prepare_std_body($user_id,"name",$errormessage,$trace); break;
            case $c2v($mm::STOCKTAKEEVENTPAGE)    :
            case $c2v($mm::DELIVERYEVENTPAGE)     :
            case $c2v($mm::TRANSFEREVENTPAGE)     :
            case $c2v($mm::ADJUSTMENTEVENTPAGE)   :$success = $this->prepare_stockevent_body($user_id,$errormessage,$trace); break;
            default                               :$success = $this->prepare_std_body($user_id,$this->orderby,$errormessage,$trace);
        }
        if ($this->trace ) { echo gtab(-1)."Leave ".__METHOD__."<br>\n"; }
        return $success;
     }
    private function selectHTMLhead () {
        if ($this->trace) { echo gtab(0)."Enter ".__METHOD__."<br>"; }
        switch ($this->pagenum) {
            case 431: 
                $this->multiselect = true;
                break;
            default: 
        } 
     }
    private function prepare_login_body(&$errormessage,$trace=false) {
        if ($this->trace || $trace) { echo gtab(1)."Enter ".__METHOD__."<br>"; }
        if (isset($this->requestdata["menuid"]) && ($this->requestdata["menuid"] == "changepwd")) {
            $data = "";
            $numrows = 0;
            $parents = 0;
            $success = $this->manager->getallrecords($data,"given_name",$parents,$numrows,false,false);
            $this->form->setadmindata($this->manager->names(),$this->requestdata["pp"],$trace=false);
        }
        $this->form->init($this->session);
        $this->bodysection = $this->bodies->loginbody();
        $this->bodysection->init($this->session,$this->form,"","",$errormessage); 
        if ($this->trace || $trace) { echo gtab(-1)."Leave ".__METHOD__."<br>"; }
        return true;
     }
    private function prepare_roster_body($user_id,&$errormessage,$trace=false) {
        if ($this->trace || $trace) { echo gtab(1)."Enter ".__METHOD__." for user ".$user_id."<br>"; }
        $success = true;
        // determine the first data for the roster display
        // $firstdatestr =  $this->requestdata["firstdate"] ?? date('Y-m-d');
        // $direction =     $this->requestdata["direction"] ?? "";
        // $pagedepth =     $this->requestdata["pagedepth"] ?? trim($this->config["app"]["ROSTERPAGEROWS"]);
        $unpublished = ($this->isadmin || in_array($this->pagenum."||VIEWUNPUB",$this->rights));
        try {
            $c = $maxcols = 0;
            $numrows = 0;
            $userdata = "";
            $parents = [];
            $firstdatestr = "";
            $page_id = 0;
            $pagedepth = 0;
            $bookingdata = [];
            $success = $this->manager->getuser($user_id,$userdata,$parents,$numrows,false,$trace);$c=$c+$success;
            $roledata = [];
            $success = $success && ($this->isadmin || $this->manager->loadlinkedobjects($user_id,$roledata,$numrows,false));$c=$c+$success;
            $sessiondata = [];
            $this->rostermanager->loadroster($this->pagenum,$pagedepth,$firstdatestr,$unpublished,$page_id,$sessiondata,$trace);
            $this->rostermanager->loadboookings($bookingdata,false);
            // $success = $success && $this->rostermanager->loadroster($this->pagenum,$pagedepth,$firstdatestr,$unpublished,$page_id,$sessiondata,$trace);$c=$c+$success;
            // $success = $success && $this->rostermanager->loadboookings($bookingdata,false);$c=$c+$success;
            if ($success) {
                $pagename = $this->menumanager->getmenutext($this->pagenum);
                $this->form->setdata($userdata,$roledata,$page_id,$sessiondata,$bookingdata,$firstdatestr,$this->pagenum,$pagedepth,$pagename,$trace);
                if ($this->isadmin || in_array("{$this->pagenum}||BOOKOTHERS",$this->rights)) {
                    // $success = $this->manager->getallrecords($data,"given_name",$parents,$numrows,false,$trace);
                    $this->form->setadmindata($this->manager->getvolunteernames($this->pagenum),$trace);
                }
                $this->bodysection = $this->bodies->standardbody(); 
                $this->bodysection->init($this->session,$this->form,"title goes here","",$errormessage); 
            } else {
                if (($userdata??[])==[]) {
                    $errormessage = __METHOD__." Failed to retrieve data for user id={$user_id} for page {$this->pagenum}";
                } else if (!$this->isadmin && ($roledata??[])==[]) {
                    $errormessage = __METHOD__." Failed to retrieve role data for user id={$user_id} for page {$this->pagenum}";
                } else if ($sessiondata == []) {
                    $errormessage = __METHOD__." No sessions created yet for page {$this->pagenum}";
                } else {
                    $errormessage = __METHOD__." Error encountered getting data for page {$this->pagenum}";
                }
                return false;
            }
        } catch(\Exception $e) {
            $errormessage = __METHOD__." Exception when getting data for page {$this->pagenum}' (line {$c}): {$e->getCode()} {$e->getMessage()}";
            return false;
        }
        if ($this->trace || $trace) { echo gtab(-1)."Leave ".__METHOD__."<br>"; }
        return true;
     }
    private function prepare_attendance_body($user_id,&$errormessage,$trace=false) {
        if ($this->trace || $trace) { echo gtab(1)."Enter ".__METHOD__." for user ".$user_id."<br>"; }
        $success = true;
        try {
            $userdata = "";
            $numrows = 0;
            $sessiondata = $clients = $clientsessions = [];
            $c = 0;
            // load data
            $success = $this->clientmgr->getclientidsandnames($clients,"name",$numrows,false);
            $success = $success && ++$c && $this->manager->getattendancesessionlist($sessions,$numrows,false);
            $success = $success && ++$c && $this->manager->getclientsessions($clientsessions,$numrows,false);
            if ($success && $c==2) {
                $this->form->setdata($clients,$sessions,$clientsessions,$trace);
                $this->bodysection = $this->bodies->standardbody(); 
                $this->bodysection->init($this->session,$this->form,"title goes here","",$errormessage); 
             } else {
                $table = match($c){
                    0 => "clientdata",
                    1 => "sessiondata",
                    2 => "clientsessions",
                };
                $errormessage = __METHOD__." failed to get {$table} for page {$this->pagenum}";
                return false;
            }
        } catch(\Exception $e) {
            $errormessage = __METHOD__." Exception when getting data for page {$this->pagenum}' (line {$c}): {$e->getCode()} {$e->getMessage()}";
            return false;
        }
        if ($this->trace ) { echo gtab(-1)."Leave ".__METHOD__."<br>\n"; }
        return true;
     }
    private function prepare_attendancereport_body($user_id,&$errormessage,$trace=false) {
        if ($this->trace || $trace) { echo gtab(1)."Enter ".__METHOD__." for user ".$user_id."<br>"; }
        try {
            $this->manager->getdesktopdata($summary,$numrows,false);
            $this->form->init($this->session);
            $this->form->setdata($summary);
            $this->bodysection = $this->bodies->standardbody(); 
            $this->bodysection->init($this->session,$this->form,"title goes here","",$errormessage); 
        } catch(\Exception $e) {
            $errormessage = __METHOD__." Exception preparing page {$this->pagenum}: {$e->getCode()} {$e->getMessage()}";
            return false;
        }
        if ($this->trace ) { echo gtab(-1)."Leave ".__METHOD__."<br>\n"; }
        return true;
     }
    private function prepare_profile_body($user_id,&$errormessage,$trace=false) {
        if ($this->trace || $trace) { echo gtab(1)."Enter ".__METHOD__." for user ".$user_id."<br>"; }
        try {
            $data = "";
            $numrows = 0;
            $parents = [];
            $success = $this->manager->getuser($user_id,$data,$parents,$numrows,false,$trace);
            if ($success) {
                $this->form->init($this->session,$data,$parents,false,[],[]);
                $this->bodysection = $this->bodies->standardbody();
                $this->bodysection->init($this->session,$this->form,"My Profile","",$errormessage);
            } else {
                $errormessage = __METHOD__." failed to get user '{$user_id}' for page {$this->pagenum}";
                return false;
            }
        } catch(\Exception $e) {
            $errormessage = __METHOD__." failed to get user '{$user_id}' for page {$this->pagenum}: {$e->getCode() } {$e->getMessage()}";
            return false;
        }
        if ($this->trace || $trace) { echo gtab(-1)."Leave ".__METHOD__."<br>"; }
        return true;
     }
    private function prepare_config_body($user_id,&$errormessage,$trace=false) { //$trace=false
        if ($this->trace || $trace) { echo gtab(1)."Enter ".__METHOD__." for user ".$user_id."<br>"; }
        try {
            $configdata = "";
            $success = $this->manager->getconfigdata($configdata,"",$trace,FALSE);
            if ($success) {
                $this->form->init($this->session,$configdata,[],false);
                $this->bodysection = $this->bodies->standardbody();
                $this->bodysection->init($this->session,$this->form,"Configuration","",$errormessage);
            } else {
                $errormessage = __METHOD__." failed to get the data for the Config page.";
                return false;
            }
        } catch(\Exception $e) {
            $errormessage = __METHOD__." failed to get the data for the Config page.: {$e->getCode() } {$e->getMessage()}";
            if ($this->trace ) { echo gtab(-1)."Leave ".__METHOD__."...<br>$errormessage<br>\n"; }
            return false;
        }
        if ($this->trace || $trace) { echo gtab(-1)."Leave ".__METHOD__."<br>"; }
        return true;
     }
    private function prepare_menuitem_body($user_id,&$errormessage,$trace=false) { //$trace=false
        if ($this->trace || $trace) { echo gtab(1)."Enter ".__METHOD__." for user ".$user_id."<br>"; }
        try {
            $parents = []; // there are no parents for this table
            $menuitems = "";
            $success = $this->manager->getallrecords($menuitems,"menucode",$parents,$numrows,false,false);
            if ($success) {
                $pagenumbers = $this->manager->getpagenumberarray();
                $this->form->init($this->session,$menuitems,[],false,$pagenumbers);
                $this->bodysection = $this->bodies->standardbody();
                $this->bodysection->init($this->session,$this->form,"Menu Items","",$errormessage);
            } else {
                $errormessage = __METHOD__." failed to get the data for the Menu Items page.";
                return false;
            }
        } catch(\Exception $e) {
            $errormessage = __METHOD__." failed to get the data for the Menu Items page: {$e->getCode() } {$e->getMessage()}";
            if ($this->trace ) { echo gtab(-1)."Leave ".__METHOD__."...<br>$errormessage<br>\n"; }
            return false;
        }
        if ($this->trace || $trace) { echo gtab(-1)."Leave ".__METHOD__."<br>"; }
        return true;
     }
    private function prepare_role_body($page_id,&$errormessage,$trace=false) {
        if ($this->trace || $trace) { echo gtab(1)."Enter ".__METHOD__." for page ".$page_id."<br>"; }
        try {
            $data = "";
            $parents = [];
            $numrows = 0;
            $success = $this->manager->getallrecords($data,"name",$parents,$numrows,false,false);
            $pageactionroles = [];
            $pageactions = [];
            $pages = [];
            $success = $success && $this->manager->getallpageactionroles($pageactionroles,$numrows,"role_id",false);
            $success = $success && $this->manager->getallpageactions($pageactions,$numrows,false);
            $success = $success && $this->manager->getpageswithactions($pages,$numrows,"menutext",false,false);
            if ($success) {
                $this->form->init($this->session,$data,$parents,false,$pageactions,$pageactionroles,$pages);
                $this->bodysection = $this->bodies->standardbody();
                $this->bodysection->init($this->session,$this->form,$this->manager->getname(),"",$errormessage);
            } else {
                $errormessage = __METHOD__." failed to get {$this->manager->getname()} for page {$this->pagenum}";
                return false;
            }
        } catch(\Exception $e) {
            $errormessage = __METHOD__." failed to get user '{$page_id}' for page {$this->pagenum}: {$e->getCode() } {$e->getMessage()}";
            if ($this->trace ) { echo gtab(-1)."Leave ".__METHOD__."...<br>$errormessage<br>\n"; }
            return false;
        }    
        if ($this->trace || $trace) { echo gtab(-1)."Leave ".__METHOD__."<br>"; }
        return true;
     }
    private function prepare_location_body($user_id, &$errormessage, $trace=false) {
        if ($this->trace || $trace) { echo gtab(1)."Enter ".__METHOD__."<br>"; }
        try {
            $locations = $categories = $parents = [];
            $numrows = 0;
            $success = $this->manager->getallrecords($locations, "name", $parents, $numrows, false, false);
            if ($success) {
                $catmgr = $this->mgrs->StockCategoryManager();
                $catmgr->init($this->session);
                $catmgr->getallrecords($categories, "Name", $parents, $numrows, false, false);
                $this->form->init($this->session, $locations, $parents, false, $categories);
                $this->bodysection = $this->bodies->standardbody();
                $this->bodysection->init($this->session, $this->form, $this->manager->getname(), "", $errormessage);
            } else {
                $errormessage = __METHOD__ . " failed to get locations.";
                return false;
            }
        } catch (\Exception $e) {
            $errormessage = __METHOD__ . ": " . $e->getMessage();
            if ($this->trace) { echo gtab(-1)."Leave ".__METHOD__."...<br>$errormessage<br>"; }
            return false;
        }
        if ($this->trace || $trace) { echo gtab(-1)."Leave ".__METHOD__."<br>"; }
        return true;
    }
    private function prepare_task_body($user_id,&$errormessage,$trace=false) {
        if ($this->trace || $trace) { echo gtab(1)."Enter ".__METHOD__."<br>"; }
        try {
            $c = 0;
            $data = "";
            $parents = [];
            $numrows = 0;
            $taskroles = [];
            $roles = [];
            $success = $this->manager->getallrecords($data,"name",$parents,$numrows,false,false);$c=$c+$success;
            $success = $success && is_array($parents) && count($parents);$c=$c+$success; // without parents a foreign key error will result
            $success = $success && $this->manager->getalltaskroles($taskroles,$numrows,"task_id",false);$c=$c+$success;
            $success = $success && $this->manager->getallroles($roles,$numrows,"name",false);$c=$c+$success;
            if ($success) {
            // lib::pr($parents);
                $this->form->init($this->session,$data,$parents,false,$roles,$taskroles);
                $this->bodysection = $this->bodies->standardbody();
                $this->bodysection->init($this->session,$this->form,$this->manager->getname(),"",$errormessage);
            } else {
                switch ($c) {
                    case 1; 
                        $errormessage = __METHOD__." failed to load tasks for page {$this->pagenum}";
                    case 2; 
                        $errormessage = __METHOD__." no pages available for task on {$this->pagenum}";
                    case 3; 
                        $errormessage = __METHOD__." failed to load task-roles for page {$this->pagenum}";
                    case 4; 
                        $errormessage = __METHOD__." failed to load roles for page {$this->pagenum}";
                }
                return false;
            }
        } catch(\Exception $e) {
            $errormessage = __METHOD__." failed for page {$this->pagenum}: {$e->getCode() } {$e->getMessage()}";
            if ($this->trace ) { echo gtab(-1)."Leave ".__METHOD__."...<br>$errormessage<br>\n"; }
            return false;
        }    
        if ($this->trace || $trace) { echo gtab(-1)."Leave ".__METHOD__."<br>"; }
        return true;
     }
    private function prepare_user_body($user_id,&$errormessage,$trace=false) {
        if ($this->trace || $trace) { echo gtab(1)."Enter ".__METHOD__." for user ".$user_id."<br>"; }
        try {
            $data = "";
            $parents = [];
            $numrows = 0;
            $userroles = [];
            $roles = [];
            $success = $this->manager->getallrecords($data,"given_name",$parents,$numrows,false,false);
            $success = $success && $this->manager->getalluserroles($userroles,$numrows,"user_id",false);
            $success = $success && $this->manager->getallroles($roles,$numrows,"name",false);
        // lib::pr($roles,$userroles);
            if ($success) {
                $this->form->init($this->session,$data,$parents,false,$roles,$userroles,$this->pagenum);
                $this->bodysection = $this->bodies->standardbody();
                $this->bodysection->init($this->session,$this->form,$this->manager->getname(),"",$errormessage);
            } else {
                $errormessage = __METHOD__." failed to get {$this->manager->getname()} for page {$this->pagenum}";
                return false;
            }
        } catch(\Exception $e) {
            $errormessage = __METHOD__." failed to get user '{$user_id}' for page {$this->pagenum}: {$e->getCode() } {$e->getMessage()}";
            if ($this->trace ) { echo gtab(-1)."Leave ".__METHOD__."...<br>$errormessage<br>\n"; }
            return false;
        }    
        if ($this->trace ) { echo gtab(-1)."Leave ".__METHOD__."<br>\n"; }
        return true;
     }
    private function prepare_client_body($client_id,&$errormessage,$trace=false) {
        if ($this->trace || $trace ) { echo gtab(1)."Enter ".__METHOD__." for client ".$client_id."<br>"; }
        try {
            $data = "";
            $numrows = 0;
            $parents = $volunteers = $clientmembers = $clientsessions = [];
            $c = 1;
            $success = $this->manager->getallrecords($data,"given_name",$parents,$numrows,false,false);
            $success = $success && $c++ && $this->manager->getallclientmembers($clientmembers,'client_id,id',$numrows,false);
            $success = $success && $c++ && $this->manager->getallclientsessions($clientsessions,$numrows,false);
            $success = $success && $c++ && $this->manager->gettodaysvolunteers($volunteers,$numrows,false);
            if ($success) {
                $this->form->init($this->session,$data,$parents,false,$clientmembers,$clientsessions,$volunteers,$this->pagenum);
                $this->bodysection = $this->bodies->standardbody();
                $this->bodysection->init($this->session,$this->form,$this->manager->getname(),"",$errormessage);
            } else {
                $errormessage = __METHOD__." failed @{$c} getting {$this->manager->getname()} {$client_id} for page {$this->pagenum}";
                return false;
            }
        } catch(\Exception $e) {
            $errormessage = __METHOD__." failed to get client '{$client_id}' for page {$this->pagenum}: {$e->getCode() } {$e->getMessage()}";
            if ($this->trace ) { echo gtab(-1)."Leave ".__METHOD__."...<br>$errormessage<br>\n"; }
            return false;
        }    
        if ($this->trace ) { echo gtab(-1)."Leave ".__METHOD__."<br>\n"; }
        return true;
     }
    private function prepare_page_body($page_id,&$errormessage,$trace=false) {
        if ($this->trace || $trace) { echo gtab(1)."Enter ".__METHOD__." for page ".$page_id."<br>"; }
        try {
            $c = 1;
            $data = "";
            $parents = [];
            $numrows = 0;
            $pageactions = [];
            $actions = [];
            $success = $this->manager->getallrecords($data,"pagenumber",$parents,$numrows,false,$trace=false);
            $success = $success && $this->manager->getallpageactions($pageactions,$numrows,"page_id",$trace=false);
            $success = $success && $this->manager->getallactions($actions,$numrows,"name",false);
            if ($success) {
                $this->form->init($this->session,$data,$parents,$trace,$actions,$pageactions);
                $this->bodysection = $this->bodies->standardbody();
                $this->bodysection->init($this->session,$this->form,$this->manager->getname(),"",$errormessage);
            } else {
                $errormessage = __METHOD__." failed to get {$this->manager->getname()} for page {$this->pagenum}";
                return false;
            }
        } catch(\Exception $e) {
            $errormessage = __METHOD__." failed to get page '{$page_id}' for page {$this->pagenum}: {$e->getCode() } {$e->getMessage()}";
            if ($this->trace ) { echo gtab(-1)."Leave ".__METHOD__."...<br>$errormessage<br>\n"; }
            return false;
        }    
        if ($this->trace ) { echo gtab(-1)."Leave ".__METHOD__."<br>\n"; }
        return true;
     }
    private function prepare_action_body($action_id,&$errormessage,$trace=false) {
        if ($this->trace || $trace) { echo gtab(1)."Enter ".__METHOD__." for action ".$action_id."<br>"; }
        try {
            $c = 1;
            $data = "";
            $parents = [];
            $numrows = 0;
            $actionpages = [];
            $pages = [];
            $success = $this->manager->getallrecords($data,"name",$parents,$numrows,false,false);
            $success = $success && $this->manager->getallpageactions($actionpages,$numrows,"action_id",false);
            $success = $success && $this->manager->getallpages($pages,$numrows,"name",false);
            if ($success) {
                $this->form->init($this->session,$data,$parents,false,$pages,$actionpages);
                $this->bodysection = $this->bodies->standardbody();
                $this->bodysection->init($this->session,$this->form,$this->manager->getname(),"",$errormessage);
            } else {
                $errormessage = __METHOD__." failed to get {$this->manager->getname()} for page {$this->pagenum}";
                return false;
            }
        } catch(\Exception $e) {
            $errormessage = __METHOD__." failed to get action '{$action_id}' for page {$this->pagenum}: {$e->getCode() } {$e->getMessage()}";
            if ($this->trace ) { echo gtab(-1)."Leave ".__METHOD__."...<br>$errormessage<br>\n"; }
            return false;
        }    
        if ($this->trace ) { echo gtab(-1)."Leave ".__METHOD__."<br>\n"; }
         return true;
     }
    private function prepare_report_body($action_id,&$errormessage,$trace=false) {
        if ($this->trace || $trace) { echo gtab(1)."Enter ".__METHOD__." for action ".$action_id."<br>"; }
        try {
            $data = "";
            $parents = $actionpages = $pages = [];
            $numrows = 0;
            $success = $this->manager->getallrecords($data,"name",$parents,$numrows,false,false);
            if ($success) {
                $this->form->init($this->session,$data,$parents,false,$pages,$actionpages);
                $this->bodysection = $this->bodies->standardbody();
                $this->bodysection->init($this->session,$this->form,$this->manager->getname(),"",$errormessage);
            } else {
                $errormessage = __METHOD__." failed to get Report list";
                return false;
            }
        } catch(\Exception $e) {
            $errormessage = __METHOD__." failed to get Report list: {$e->getCode() } {$e->getMessage()}";
            if ($this->trace ) { echo gtab(-1)."Leave ".__METHOD__."...<br>$errormessage<br>\n"; }
            return false;
        }    
        if ($this->trace ) { echo gtab(-1)."Leave ".__METHOD__."<br>\n"; }
         return true;
     }
    private function prepare_session_body($user_id,&$errormessage,$trace=false) {
        if ($this->trace || $trace) { echo gtab(1)."Enter ".__METHOD__." for user ".$user_id."<br>"; }
        try {
            $c = 1;
            $data = "";
            $parents = [];
            $numrows = 0;
            $volunteers = [];
            $history = [];
            $c = 1;
            $success = $this->manager->getallrecords($data,"task_id, start",$parents,$numrows,false,false);
            $success = $success && $c++ && $this->manager->getvolunteers($volunteers,$numrows,false);
            $success = $success && $c++ && $this->manager->getbookinghistory($history,0,$numrows,false);
            $success = $success && $c++ && $this->manager->getattendances($clientsessions,$numrows,false);
            // lib::pr($history);
            if ($success) {
                $this->form->init($this->session,$data,$parents,false,$volunteers,$history,$clientsessions);
                $this->bodysection = $this->bodies->standardbody();
                $this->bodysection->init($this->session,$this->form,$this->manager->getname(),"",$errormessage);
            } else {
                $errormessage = __METHOD__." failed to get line {$c} for page {$this->pagenum}, {$this->manager->getname()}";
                return false;
            }
        } catch(\Exception $e) {
            $errormessage = __METHOD__." failed to get user '{$user_id}' for page {$this->pagenum}: {$e->getCode() } {$e->getMessage()}";
            if ($this->trace ) { echo gtab(-1)."Leave ".__METHOD__."...<br>$errormessage<br>\n"; }
            return false;
        }    
        if ($this->trace ) { echo gtab(-1)."Leave ".__METHOD__."<br>\n"; }
        return true;
     }
    private function prepare_std_body($user_id,$orderby,&$errormessage,$trace=false) {
        if ($this->trace || $trace) { echo gtab(1)."Enter ".__METHOD__." for user ".$user_id."<br>"; }
        try {
            // $parents is a array of datasets from any parent tables relevant for this object
            $c = 1;
            $data = "";
            $parents = [];
            $numrows = 0;
            $success = $this->manager->getallrecords($data,$orderby,$parents,$numrows,false,false);
            if ($success) {
                $this->form->init($this->session,$data,$parents,false);
                $this->bodysection = $this->bodies->standardbody();
                $this->bodysection->init($this->session,$this->form,$this->manager->getname(),"",$errormessage);
            } else {
              die(__METHOD__." failed to get {$this->manager->getname()} for page {$this->pagenum}");
            }
        } catch(\Exception $e) {
            // lib::v($user_id,$data,$numrows,);
            $errormessage = __METHOD__." : {$e->getCode() } {$e->getMessage()}";
            if ($this->trace ) { echo gtab(-1)."Leave ".__METHOD__."...<br>$errormessage<br>\n"; }
            return false;
        }    
        if ($this->trace ) { echo gtab(-1)."Leave ".__METHOD__."<br>\n"; }
        return true;
     }
    private function prepare_stockevent_body($user_id, &$errormessage, $trace=false) {
        if ($this->trace || $trace) { echo gtab(1)."Enter ".__METHOD__."<br>"; }
        try {
            $locations = $suppliers = $categories = $parents = [];
            $numrows   = 0;
            $locmgr    = $this->mgrs->LocationManager();       $locmgr->init($this->session);
            $supmgr    = $this->mgrs->StockSupplierManager();  $supmgr->init($this->session);
            $catmgr    = $this->mgrs->StockCategoryManager();  $catmgr->init($this->session);
            $locmgr->getallrecords($locations,  "name", $parents, $numrows, false, false);
            $supmgr->getallrecords($suppliers,  "name", $parents, $numrows, false, false);
            $catmgr->getallrecords($categories, "Name", $parents, $numrows, false, false);

            // Enrich suppliers with in-progress delivery data so the delivery form
            // can build "continue (date)" vs "New Delivery" options without an extra AJAX call.
            $eventmgr = $this->mgrs->StockEventManager();
            $eventmgr->init($this->session);
            $deliveries  = [];
            $del_numrows = 0;
            $eventmgr->getallinprogressdeliveries($deliveries, $del_numrows);
            $delivery_lookup = [];
            foreach ($deliveries as $del) {
                // Keep only the most recent in-progress delivery per supplier.
                if (!isset($delivery_lookup[$del['supplier_id']])) {
                    $delivery_lookup[$del['supplier_id']] = $del;
                }
            }
            foreach ($suppliers as &$sup) {
                if (isset($delivery_lookup[$sup['id']])) {
                    $d = $delivery_lookup[$sup['id']];
                    $sup['in_progress_event_id'] = $d['id'];
                    $sup['in_progress_date']     = $d['date_created'];
                } else {
                    $sup['in_progress_event_id'] = 0;
                    $sup['in_progress_date']     = '';
                }
            }
            unset($sup);

            // Enrich categories with the list of supplier IDs that supply each category,
            // so the delivery form can filter the category dropdown by supplier.
            $links     = [];
            $lnk_rows  = 0;
            $supmgr->getallcategorylinks($links, $lnk_rows);
            $cat_sup_map = [];
            foreach ($links as $link) {
                $cat_sup_map[$link['stock_category_id']][] = $link['stock_supplier_id'];
            }
            foreach ($categories as &$cat) {
                $cat['supplier_ids'] = $cat_sup_map[$cat['id']] ?? [];
            }
            unset($cat);

            $this->form->init($this->session, $locations, $suppliers, $categories);
            $this->bodysection = $this->bodies->standardbody();
            $this->bodysection->init($this->session, $this->form, $this->form->getformname(), "", $errormessage);
        } catch(\Exception $e) {
            $errormessage = __METHOD__." Exception: ".$e->getMessage();
            if ($this->trace) { echo gtab(-1)."Leave ".__METHOD__."...<br>$errormessage<br>\n"; }
            return false;
        }
        if ($this->trace || $trace) { echo gtab(-1)."Leave ".__METHOD__."<br>"; }
        return true;
     }
    private function getStockEventForm(string $event_type): \app\view\form\StockEventForm {
        return match($event_type) {
            'delivery'   => $this->forms->DeliveryEventForm(),
            'transfer'   => $this->forms->TransferEventForm(),
            'adjustment' => $this->forms->AdjustmentEventForm(),
            default      => $this->forms->StocktakeEventForm(),
        };
     }
    private function prepare_stocktake_body($user_id,&$errormessage,$trace=false) {
        if ($this->trace || $trace) { echo gtab(1)."Enter ".__METHOD__."<br>"; }
        try {
            $data    = [];
            $parents = [];
            $numrows = 0;
            $success = $this->manager->getallrecords($data,"",$parents,$numrows,false,$trace);
            if ($success) {
                $this->form->init($this->session,$data,$parents,false);
                $this->bodysection = $this->bodies->standardbody();
                $this->bodysection->init($this->session,$this->form,"Stocktake","",$errormessage);
            } else {
                $errormessage = __METHOD__." failed to load stock items for page {$this->pagenum}";
                return false;
            }
        } catch(\Exception $e) {
            $errormessage = __METHOD__." : {$e->getCode()} {$e->getMessage()}";
            if ($this->trace) { echo gtab(-1)."Leave ".__METHOD__."...<br>$errormessage<br>\n"; }
            return false;
        }
        if ($this->trace || $trace) { echo gtab(-1)."Leave ".__METHOD__."<br>\n"; }
        return true;
     }
    private function prepare_stockmovement_body($user_id,&$errormessage,$trace=false) {
        if ($this->trace || $trace) { echo gtab(1)."Enter ".__METHOD__."<br>"; }
        try {
            $data    = [];
            $parents = [];
            $numrows = 0;
            $success = $this->manager->getallrecords($data,"",$parents,$numrows,false,$trace);
            if ($success) {
                $this->form->init($this->session,$data,$parents,false);
                $this->bodysection = $this->bodies->standardbody();
                $this->bodysection->init($this->session,$this->form,$this->manager->getname(),"",$errormessage);
            } else {
                $errormessage = __METHOD__." failed to load records for page {$this->pagenum}";
                return false;
            }
        } catch(\Exception $e) {
            $errormessage = __METHOD__." : {$e->getCode()} {$e->getMessage()}";
            if ($this->trace) { echo gtab(-1)."Leave ".__METHOD__."...<br>$errormessage<br>\n"; }
            return false;
        }
        if ($this->trace || $trace) { echo gtab(-1)."Leave ".__METHOD__."<br>\n"; }
        return true;
     }
    private function prepare_stocklevelreport_body($user_id,&$errormessage,$trace=false) {
        if ($this->trace || $trace) { echo gtab(1)."Enter ".__METHOD__."<br>"; }
        try {
            $report_type = $this->requestdata['report_type'] ?? 'stocklevels';
            if (!in_array($report_type, ['stocklevels', 'stocktakevariance', 'usagereport', 'deliveriesreport', 'belowminimumreport'])) $report_type = 'stocklevels';

            if ($report_type === 'deliveriesreport') {
                $this->manager = $this->mgrs->DeliveriesReportManager();
                $this->manager->init($this->session, $trace);
                $this->form = $this->forms->DeliveriesReportForm();

                $from        = $this->requestdata['from']        ?? '';
                $to          = $this->requestdata['to']          ?? '';
                $supplier_id = $this->requestdata['supplier_id'] ?? '';
                $category_id = $this->requestdata['category_id'] ?? '';
                if ($from        && !preg_match('/^\d{4}-\d{2}-\d{2}$/', $from))  $from        = '';
                if ($to          && !preg_match('/^\d{4}-\d{2}-\d{2}$/', $to))    $to          = '';
                if ($supplier_id && !preg_match('/^\d+$/', $supplier_id))          $supplier_id = '';
                if ($category_id && !preg_match('/^\d+$/', $category_id))          $category_id = '';
                $this->manager->setfilters($from, $to, $supplier_id, $category_id);

                $include_items = !empty($this->requestdata['include_items']) && $this->requestdata['include_items'] === '1';
            } elseif ($report_type === 'belowminimumreport') {
                $this->manager = $this->mgrs->BelowMinimumReportManager();
                $this->manager->init($this->session, $trace);
                $this->form = $this->forms->BelowMinimumReportForm();

                $location_id = $this->requestdata['location_id'] ?? '';
                if (!preg_match('/^\d*$/', $location_id)) $location_id = '';
                $this->manager->setlocation($location_id);
            } elseif ($report_type === 'usagereport') {
                $this->manager = $this->mgrs->StockUsageReportManager();
                $this->manager->init($this->session, $trace);
                $this->form = $this->forms->StockUsageReportForm();

                $location_id = $this->requestdata['location_id'] ?? '';
                if (!preg_match('/^\d*$/', $location_id)) $location_id = '';
                $this->manager->setlocation($location_id);

                $from = $this->requestdata['from'] ?? '';
                $to   = $this->requestdata['to']   ?? '';
                if ($from && !preg_match('/^\d{4}-\d{2}-\d{2}$/', $from)) $from = '';
                if ($to   && !preg_match('/^\d{4}-\d{2}-\d{2}$/', $to))   $to   = '';
                $this->manager->setdaterange($from, $to);
            } elseif ($report_type === 'stocktakevariance') {
                $this->manager = $this->mgrs->StocktakeVarianceReportManager();
                $this->manager->init($this->session, $trace);
                $this->form = $this->forms->StocktakeVarianceReportForm();

                $location_id = $this->requestdata['location_id'] ?? '';
                if (!preg_match('/^\d*$/', $location_id)) $location_id = '';
                $this->manager->setlocation($location_id);

                $event_id = $this->requestdata['event_id'] ?? '';
                if (!preg_match('/^\d*$/', $event_id)) $event_id = '';
                $this->manager->setevent($event_id);
            } else {
                $location_id = $this->requestdata['location_id'] ?? '';
                if (!preg_match('/^\d*$/', $location_id)) $location_id = '';
                $this->manager->setlocation($location_id);
                $as_at_raw = $this->requestdata['as_at'] ?? '';
                if ($as_at_raw && !preg_match('/^\d{4}-\d{2}-\d{2}T\d{2}:\d{2}$/', $as_at_raw)) $as_at_raw = '';
                $this->manager->setasat($as_at_raw ? str_replace('T', ' ', $as_at_raw) . ':00' : '');
            }

            $data    = [];
            $parents = [];
            $numrows = 0;
            $success = $this->manager->getallrecords($data, "", $parents, $numrows, false, $trace);
            if ($success) {
                $parents['report_type'] = $report_type;
                if ($report_type === 'deliveriesreport') $parents['include_items'] = $include_items ?? false;
                $this->form->init($this->session, $data, $parents, false);
                $this->bodysection = $this->bodies->standardbody();
                $this->bodysection->init($this->session, $this->form, "Stock Reports", "", $errormessage);
            } else {
                $errormessage = __METHOD__." failed to load stock report for page {$this->pagenum}";
                return false;
            }
        } catch(\Exception $e) {
            $errormessage = __METHOD__." : {$e->getCode()} {$e->getMessage()}";
            if ($this->trace) { echo gtab(-1)."Leave ".__METHOD__."...<br>$errormessage<br>\n"; }
            return false;
        }
        if ($this->trace || $trace) { echo gtab(-1)."Leave ".__METHOD__."<br>\n"; }
        return true;
     }
    private function buildstockalertpopup(): string {
        try {
            if (!$this->usermanager->getreceivesstockalerts($this->user_id)) return '';
            $today = date('Y-m-d');
            if (($_SESSION['stock_alert_date'] ?? '') === $today) return '';
            $belmgr = $this->mgrs->BelowMinimumReportManager();
            $belmgr->init($this->session);
            $items = []; $parents = []; $numrows = 0;
            $belmgr->getallrecords($items, '', $parents, $numrows, false, false);
            if (empty($items)) return '';
            $_SESSION['stock_alert_date'] = $today;
            $count = count($items);
            $n     = $count === 1;
            $mm    = $this->menumanager;
            $url   = '?p=' . $mm::STOCKLEVELREPORTPAGE . '&report_type=belowminimumreport';
            $html  = '<div id="stockalertdialog" style="display:none;" title="Low Stock Alert">';
            $html .= '<p>' . $count . ' stock ' . ($n ? 'item is' : 'items are') . ' currently below minimum quantity.</p>';
            $html .= '<p style="text-align:center"><button type="button" onclick="window.location=\'' . $url . '\'">View the Low Stock report &rarr;</button></p>';
            $html .= '</div>';
            $html .= '<script>jQuery(function($){'
                  .  '$("#stockalertdialog").dialog({modal:true,autoOpen:true,width:420,'
                  .  'buttons:[{text:"View Report",click:function(){window.location="' . $url . '";}}'
                  .  ',{text:"Close",click:function(){$(this).dialog("close");}}]});'
                  .  '});</script>';
            return $html;
        } catch (\Exception $e) {
            return '';
        }
     }
    private function prepare_stockusagereport_body($user_id,&$errormessage,$trace=false) {
        if ($this->trace || $trace) { echo gtab(1)."Enter ".__METHOD__."<br>"; }
        try {
            $from = $this->requestdata['from'] ?? '';
            $to   = $this->requestdata['to']   ?? '';
            if ($from && !preg_match('/^\d{4}-\d{2}-\d{2}$/', $from)) $from = '';
            if ($to   && !preg_match('/^\d{4}-\d{2}-\d{2}$/', $to))   $to   = '';
            $this->manager->setdaterange($from, $to);
            $data    = [];
            $parents = [];
            $numrows = 0;
            $success = $this->manager->getallrecords($data, '', $parents, $numrows, false, $trace);
            if ($success) {
                $parents['from'] = $from;
                $parents['to']   = $to;
                $this->form->init($this->session, $data, $parents, false);
                $this->bodysection = $this->bodies->standardbody();
                $this->bodysection->init($this->session, $this->form, "Stock Usage Report", "", $errormessage);
            } else {
                $errormessage = __METHOD__." failed to load usage data for page {$this->pagenum}";
                return false;
            }
        } catch(\Exception $e) {
            $errormessage = __METHOD__." : {$e->getCode()} {$e->getMessage()}";
            if ($this->trace) { echo gtab(-1)."Leave ".__METHOD__."...<br>$errormessage<br>\n"; }
            return false;
        }
        if ($this->trace || $trace) { echo gtab(-1)."Leave ".__METHOD__."<br>\n"; }
        return true;
    }
}
