<?php
namespace app\view\form;
use \lib\StdLib as lib;
class ClientAdminForm extends ClientForm {
    // this subclass is intended for use by the administration - it includes a full menu and Sessions Attended sections
    public $formname = "clientadminform";
    protected $objname = "Client";

    protected $trace= false;                
    protected $attendancescript;
    public function __construct(protected FormComponent $component) {
        if ($this->trace) { echo "Enter ".__METHOD__."<br>"; }
        $this->singlerecord = false;
     }
    public function init($session,$clients=[],$parents="",$trace=false,$clientmembers=[],$clientsessions=[],$volunteers=[],$pagenum='',$tandc_text='') {
        if ($this->trace||$trace) { echo "Enter ".__METHOD__."<br>"; }
        parent::init($session,$clients,$parents,$trace,$clientmembers,$clientsessions,$volunteers,$pagenum,$tandc_text);
        $this->attendancescript = <<<JS

                    const attendeeids     = makearray("#js-sessionclientids",fd) 
                    clientindex = attendeeids.indexOf(selectedid) // find selectedid in jmemberparentclientids[] and we have the index to all 
                    if (clientindex != -1) {// the client has attendances
                        const allsessions  = makearray("#js-sessiondata",rd) 
                        if (Array.isArray(allsessions) && allsessions.length) {
                            const thisclientfieldsstr = allsessions[clientindex]; 
                            if (thisclientfieldsstr != "") { 
                                // thismemberfieldsstr contains all fields for the selected object - we convert this to an array
                                const sessionarray =  thisclientfieldsstr.split(fd);
                                let isodd = true;
                                for (i=0;i < sessionarray.length-1;) { 
                                    let attendancedivs = jQuery("#attendancetemplate").html();
                                    attendancedivs = attendancedivs.replaceAll("##date",sessionarray[i++]).replaceAll("##taskname",sessionarray[i++]);
                                    attendancedivs = attendancedivs.replaceAll("##oddeven",(isodd?" vols-row-odd":" vols-row-even"));
                                    isodd = !isodd;
                                    jQuery("#attendanceheadings").after('<div class="attendancecontainer">'+attendancedivs+"</div>");
                                }
                            }
                        }
                    }
            JS;
     }
    protected function sessionsattended() { 
        $headings   = '   <div id="attendanceheadings" class="vols-tablerow sessiongroup vols-row-headings grouped">'
                                    .$this->component->rendercell("",'<strong>DATE</strong>',"vols-tablecell historycell","30",'',0)
                                    .$this->component->rendercell("",'<strong>TASK</strong>',"vols-tablecell historycell","60",'',0)
                                    .'</div>'; 
        $sessions = $this->component->rendersectionheading("Sessions attended",inputgroup:'sessiongroup');
        $sessions .= $headings.'<div id="sessions"></div>';
        return $sessions;
    }
    protected function buildpagesubheading($trace=true) {
        if ($this->trace||$trace) { echo "Enter ".__METHOD__."<br>"; }
        return "";
     }    
    // protected function newclickscript(){
    //     $script = parent::newclickscript();
    //     $script .= <<<JS
    //         jQuery("#represented_by").val(jQuery("#volunteerselection").val());
    //         jQuery("#modified_by").val("");
    //         jQuery("#volunteerselection").prop("disabled",true);
    //     JS;
    //     return $script;
    // } 
    // protected function editclickscript(){
    //     $script = <<<JS
    //         jQuery("#represented_by").val("");
    //         jQuery("#modified_by").val(jQuery("#volunteerselection").val());
    //         jQuery("#volunteerselection").prop("disabled",true);
    //     JS;
    //     return $script;
    // } 
    // protected function cancelclickscript(){
    //     $script = <<<JS
    //         jQuery("#volunteerselection").prop("disabled",false);
    //     JS;
    //     return $script;
    // } 
    // protected function resetclickscript(){
    //     $script = <<<JS
    //         jQuery("#volunteerselection").prop("disabled",false);
    //     JS;
    //     return $script;
    // } 
}