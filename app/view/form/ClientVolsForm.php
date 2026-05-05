<?php
namespace app\view\form;
use \lib\StdLib as lib;
class ClientVolsForm extends ClientForm {
    public $formname = "clientvolsform";
    protected $objname = "Client";
    // this subclass is intended for use by the volunteers - it is accessed from the Attendance Logging page, and offers a return to that page.
    // it allows Volunteers to add new Clients to the database and/or update records for existing Clients
    // The  page is accessed through a generic login "logger".
    // The vol using the page is required to select their name from a SELECT containing today's vols for audit trail purposes
    protected $trace= false;                
    protected $attendancescript = "";
    public function __construct(protected FormComponent $component) {
        if ($this->trace) { echo "Enter ".__METHOD__."<br>"; }
        $this->singlerecord = false;
     }
    public function init($session,$clients=[],$parents="",$trace=false,$clientmembers=[],$clientsessions=[],$volunteers=[],$pagenum='',$tandc_text='') {
        if ($this->trace||$trace) { echo "Enter ".__METHOD__."<br>isadmin = $this->isadmin<br>$this->formname<br>"; }
        parent::init($session,$clients,$parents,$trace,$clientmembers,$clientsessions,$volunteers,$pagenum,$tandc_text);
     }
    protected function buildpagesubheading($trace=false) {
        if ($this->trace||$trace) { echo "Enter ".__METHOD__."<br>"; }
        $this->volunteers = ["0"=>""] + $this->volunteers;
        $vols = $this->component->renderdropdown("volunteerselection",1,$optionsout,'','',false,'',$this->volunteers,"",false,'');
        return $this->component->rendersectionheading("Volunteer - please select your name... ".$vols."<BR>","vols-filtercontainer",containerclass:'vols-tablecell vols-width-100 vols-filtercontainer" style="font-size:2rem;font-weight:700"' );      
     }    
    protected function sessionsattended() { 
        return "";
     }
    protected function newclickscript(){
        $script = parent::newclickscript();
        $script .= <<<JS
            jQuery("#represented_by").val(jQuery("#volunteerselection").val());
            jQuery("#modified_by").val("");
            jQuery("#volunteerselection").prop("disabled",true);
        JS;
        return "";
     } 
    protected function editclickscript(){
        $script = <<<JS
            jQuery("#represented_by").val("");
            jQuery("#modified_by").val(jQuery("#volunteerselection").val());
            jQuery("#volunteerselection").prop("disabled",true);
        JS;
        return "";
     } 
    protected function cancelclickscript(){
        $script = <<<JS
            jQuery("#volunteerselection").prop("disabled",false);
        JS;
        return "";
     } 
    protected function resetclickscript(){
        $script = <<<JS
            jQuery("#volunteerselection").prop("disabled",false);
        JS;
        return "";
     } 
}