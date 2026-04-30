<?php
namespace app\controller;
use \lib\StdLib as lib;
class RequestProcessController {
    private $trace=false;
	protected $form;
    protected $session;
    protected $requestdata;
    protected $managercollection;
    protected $rostermanager;
    protected $emailmanager;
    protected $usermanager;
    protected $errorhandler;
    private $p = ["#\n +\[#","#ray\n +#","#\n +\)#","# => #"];
    private $r = ["\t[","ray","\t)","="];
    public function __construct(){
        if ($this->trace ) { echo gtab(0)."Enter ".__METHOD__."<br>\n"; }
     }
 	public function init($session,$managercollection,$errorhandler) {
        if ($this->trace ) { echo gtab(1)."Enter ".__METHOD__."<br>\n"; }
 		$this->session = $session;
        $this->errorhandler = $errorhandler;
        $this->managercollection = $managercollection;
        $this->requestdata = $this->session->getrequestdata();
        $this->usermanager = $this->session->usermanager();
        $this->emailmanager = $managercollection->EMailManager();
        $this->rostermanager = $managercollection->RosterManager();
        if ($this->trace ) { echo gtab(-1)."Leave ".__METHOD__."  User > ".$this->session->getuserid()."<br>\n"; }
     }
    public function processformdata(&$errormessage,$trace=false) {
        // check incoming parameters for a FORM POST. If present, identify the FORM and process accordingly.
        if ($this->trace || $trace) { echo gtab(1)."Enter ".__METHOD__."  User > ".$this->session->getuserid()."<br>\n"; }
        if (!array_key_exists("formname", $this->requestdata)) { 
            // this request must be from a menu click, not a form submission
            $success = true;
        } else { 
            $formname = $this->requestdata["formname"];
            $c = 0;
            switch ($formname) {
                case "menuactionform"  :
                case "newpw"           :  $c++;
                case "refreshform"     :  $success = true; break;

                case "loginform"       :  $c++;
                case "pwresetgetemail" :  $c++;
                case "pwresetgetcode"  :  $c++;
                case "pwresetgetpw"    :  $c++;    //david.thomas@elliott-thomas.com.au
                                        $this->usermanager->init($this->session,$trace);
                                        $adminchange = false;
                                        switch ($c) {
                                            case 4: $success = $this->usermanager->processlogin($user,$errormessage,$trace);
                                                    if (!$success){
                                                        if ($errormessage==="newpassword") { // forgoetten password has been clicked
                                                            $this->session->putpagenum(11);
                                                            $errormessage = "This is your first login. Please create a new password for yourself.";
                                                         } else { // normal login failure
                                                            $this->session->putpagenum(1); // back to login page
                                                        }
                                                        $success = true; 
                                                    }
                                                    break;
                                            case 3: $this->usermanager->initemails($this->emailmanager,$trace);
                                                    $success = $this->usermanager->pwresetsendcode($errormessage,$trace);break;
                                            case 2: $success = $this->usermanager->pwresetcheckcode($errormessage,$trace);break;
                                            case 1: $success = $this->usermanager->pwresetsave($errormessage,$adminchange,$trace);break;
                                        }
                                        if (!$success ) {
                                            if (!$adminchange) {
                                                $this->session->clearuser();
                                                $this->session->putpagenum(1); // back to login page
                                            } else {
                                                $this->session->putpagenum(101); // back to login page
                                            }
                                            $errormessage = "We encountered an error.";
                                            $success = true;
                                        }
                                        break; 
                case "logoutform"    :  $success  = $this->session->logout() ; 
                                        break;
                case "extendrosterform"     : $c++;
                case "cancelbookingform"    : $c++;
                case "makebookingform"      : $c++;
                case "updatepublicationform": $c++;
                                        $this->rostermanager->init($this->session,$trace) ;
                                        $this->rostermanager->initemails($this->emailmanager,$trace);
                                        switch ($c) {
                                            case 4: $success  = $this->rostermanager->extendroster($this->requestdata,$errormessage,$trace); break;
                                            case 3: $success  = $this->rostermanager->processcancellation($errormessage,$emailresponse,$trace); break;
                                            case 2: $success  = $this->rostermanager->processbooking($errormessage,$emailresponse,$trace); break;
                                            case 1: $success  = $this->rostermanager->updatepublicationdata($errormessage,$trace);break;
                                        }
                                        break;
                case "userform":  
                case "userprofileform": $success = $this->processCRUDform($this->usermanager,$errormessage,$trace);break;
                case "menuitemform":    $success = $this->processCRUDform($this->managercollection->menumanager(),$errormessage,$trace);break;
                case "clientform":      $success = $this->processCRUDform($this->managercollection->ClientManager(),$errormessage,$trace); break;
                case "clientadminform": $success = $this->processCRUDform($this->managercollection->ClientManager(),$errormessage,$trace); break;
                case "clientvolsform":  $success = $this->processCRUDform($this->managercollection->ClientManager(),$errormessage,$trace); break;
                case "taskform":        $success = $this->processCRUDform($this->managercollection->TaskManager(),$errormessage,$trace); break;
                case "roleform":        $success = $this->processCRUDform($this->managercollection->RoleManager(),$errormessage,$trace); break;
                case "reportform":      $success = $this->processCRUDform($this->managercollection->ReportManager(),$errormessage,$trace); break;
                case "pageform":        $success = $this->processCRUDform($this->managercollection->PageManager(),$errormessage,$trace); break;
                case "actionform":      $success = $this->processCRUDform($this->managercollection->ActionManager(),$errormessage,$trace); break;
                case "configform":           $success = $this->processCRUDform($this->managercollection->ConfigManager(),$errormessage,$trace); break;
                case "sessionform":          $success = $this->processCRUDform($this->managercollection->SessionManager(),$errormessage,$trace); break;
                case "stockcategoryform":    $success = $this->processCRUDform($this->managercollection->StockCategoryManager(),$errormessage,$trace); break;
                case "stockform":            $success = $this->processCRUDform($this->managercollection->StockManager(),$errormessage,$trace); break;
                case "stocktakeform":        $success = $this->processCRUDform($this->managercollection->StocktakeManager(),$errormessage,$trace); break;
                case "deliveryform":         $success = $this->processCRUDform($this->managercollection->DeliveryManager(),$errormessage,$trace); break;
                case "stockoutform":         $success = $this->processCRUDform($this->managercollection->StockoutManager(),$errormessage,$trace); break;
                case "damagedstockform":     $success = $this->processCRUDform($this->managercollection->DamagedStockManager(),$errormessage,$trace); break;
                case "stockusagereportform":
                case "stocklevelreportform":
                case "stockvariancereportform": $success = true; break;
                case "locationform":         $success = $this->processCRUDform($this->managercollection->LocationManager(),$errormessage,$trace); break;
                case "stocksupplierform":    $success = $this->processCRUDform($this->managercollection->StockSupplierManager(),$errormessage,$trace); break;
                default :               $errormessage = 'No FORM recognised. $_POST = '.implode(",",$this->requestdata);
                                        $success = false;
            }
        }  
        if ($this->trace || $trace) { echo gtab(-1)."Leave ".__METHOD__."  success=$success, form -> $formname, error => $errormessage<br>\n"; }
        return $success;
     }
    private function processCRUDform($manager,&$errormessage,$trace=false){
        if ($this->trace  || $trace ) { echo gtab(1)."Enter ".__METHOD__."  User > ".$this->session->getuserid()."<br>\n"; }
        try {
            $manager->init($this->session);
            if ($this->requestdata["action"] == 'save') {
                if ($this->requestdata["id"] > 0) {
                    $success = $manager->update($errormessage,$trace);
                } else {
                    $success = $manager->insert($id,$errormessage,$trace);
                    
                }
            } else if ($this->requestdata["action"] == 'delete') {
                $success = $manager->delete($errormessage,$trace);
            } else {  
                // the following call allows forms to contain non-standard CRUD 'actions'.  
                // the performaction() method should be implemented in the relevant manager subclass
                // if not the call bubbles up to the parent class's (StdManager) method which returns an "unkown action" message 
                $success = $manager->performaction($this->requestdata["action"],$errormessage,$trace);
            }  
        } catch (\Exception $e) {
            $errormessage = $e->__toString();
        }
        if ($this->trace  || $trace) { echo gtab(-1)."Leave ".__METHOD__."  User > ".$this->session->getuserid().", $errormessage<br>\n"; }
        return $success;
     }
}
