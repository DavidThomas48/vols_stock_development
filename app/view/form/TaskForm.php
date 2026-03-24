<?php
namespace app\view\form;
use \lib\StdLib as lib;
class TaskForm extends \fw\view\form\StdCRUDForm {
    protected $trace= false;                
    protected $promptwidth = 30;
    protected $inputwidth = 40;
    protected $hintwidth = 30;
    protected $fields = [];
    protected $formname = "taskform";
    protected $objname = "Task";
    protected $parentname = "Page";
    protected $parentobj = "page_id";
    protected $pagenum;
    protected $names;
    protected $parents;
    protected $taskid;
    protected $roles;
    protected $rolerows;
    protected $taskroles;
    protected $loaddowfieldscript;
    protected $loaddowvariablescript;
    public function __construct(protected FormComponent $component) {
        if ($this->trace) { echo "Enter ".__METHOD__."<br>"; }
        $this->singlerecord = false;
     }
    public function init( $session,$tasks=[],$parents="",$trace=false,$roles='',$taskroles='') {
        if ($this->trace||$trace) { echo "Enter ".__METHOD__."<br>"; }
        if (count($taskroles)) {  // add the role_ids for the user's roles in a new element in each $user array
            $this->addlinkstodata($tasks,$roles,$taskroles,$trace);
         }
        parent::init($session,$tasks,$parents,$trace);
        $this->roles =$roles;
        $this->taskroles = $taskroles;
        $this->taskid = $this->requestdata["id"]??"";
        if ($this->trace||$trace) { echo "Leave ".__METHOD__."<br>"; }
     }
    public function addlinkstodata(&$tasks=[],$roles=[],$taskroles=[],$trace=false) {
        foreach ($tasks as &$task) {
            foreach ($roles as $role) {
                $ur=0;
                foreach ($taskroles as $taskrole) {
                    if ($task["id"] === $taskrole["task_id"] && $role["id"] === $taskrole["role_id"] ) {
                        $ur = 1 ;
                        break;
                    }
                }
                $task["role".$role["id"]."id"] = $ur;
                $task["role".$role["id"]."min_quantity"] = $ur?$taskrole["min_quantity"]:0;
                $task["role".$role["id"]."max_quantity"] = $ur?$taskrole["max_quantity"]:0;
            }
        }
     }
    public function initfields() {
        if ($this->trace) { echo "Enter ".__METHOD__."<br>"; }
        $this->fields = array("id"=>"");
                                // "dowaccess"=>"");
     }
    protected function addtonames($task){
            $this->names[$task["id"]] = $task["name"];
     }                
    public function buildinputs($rights=[],$trace=false) {
        // Note the fieldnum parameter should equal the position of the field in the field array in the relevant table Class
        if ($this->trace) { echo "Enter ".__METHOD__."<br>"; }
        $parentdata = array_combine(array_column($this->parents,"id"),array_column($this->parents,"menutext"));
        $parentdata = [NULL=>""] + $parentdata;
        $optn = [];
        // $button = '<div class="vols-tablecell vols-width-100 aligncenter"><div id="buildsessions" class="clickable action doitbg" style"height: auto">Build</div></div>';
        // $heading = '<div style="display:inline-grid;grid-template-columns:1fr 1fr;align-items:center;"><div style="margin-right:20px">Build sessions:  </div>'.$button.'</div>';
        // $formfields  = $this->component->rendersectionheading($heading,"","","","","","","","",true);
        $formfields = $this->component->buildinputrow("name",2,"",'Name','Name',20,64,true,'','');
        // ======================================General section
        $formfields .= $this->component->rendersectionheading("General",inputgroup:"generalgroup");
        $formfields .= $this->component->buildselectrow("page_id",1,1,$this->parentname,$parentdata,"",$optionsout,false,false,true,false,'',false);
        $input = $this->component->renderdateinput("starttime","time","","","",1,false,"","",3,false,false,false);
        $formfields .= $this->component->renderformrow(0,0,"Start Time",false,"","","starttime",$input);
        $input = $this->component->renderdateinput("endtime","time","","","",1,false,"","",4,false,false,false);
        $formfields .= $this->component->renderformrow(0,0,"End Time",false,"","","endtime",$input);
        $this->component->setwidths (30,20,50);
        // ======================================Display section
        $formfields .= $this->component->rendersectionheading("Display",inputgroup:"displaygroup");
        $formfields .= $this->component->buildinputrow("taskgroup",28,"",'Task group','',3,3,false,'','The numbered Group that contains this task in a multi-group, multi-task roster page (1,2,...)? Tasks within a Group will display across the page if the screen width allows it.');
        $formfields .= $this->component->buildinputrow("groupindex",29,"",'Group position','',3,3,false,'','The position of this Task in its Task Group (1,2,...).');
        $formfields .= $this->component->buildinputrow("cellsperrow",30,"",'Cells per row','',3,3,false,'','The number of Volunteer cells to display per row in the Roster page (max 6). This impacts the width of the task. If more Volunteers are required per session, the sessions will contain multiple rows of Volunteer cells, as required.');
        $formfields .= $this->component->buildinputrow("sessiondepth",31,"",'Session per page','',3,3,false,'','This determines how many sessions will be displayed for the parent task per roster page?');

        // ======================================recurrence section
        $formfields .= $this->component->rendersectionheading("Recurrence",inputgroup:"recurrencegroup");
        $fromdate = $this->component->renderdateinput("startdate",'','','','','',false,'',"",26,false,false,false); 
        $cellclass = " vols-overflow-show ";
        $formfields .= $this->component->renderformrow("startdate","","First Date",false,'','','',$fromdate,'',$cellclass,'','The date of the first session for this task.','','','','','','','','vols-tablerow '); 
        $todate = $this->component->renderdateinput("enddate",'','','','','',false,'',"",27,false,false,false); 
        $formfields .= $this->component->renderformrow("enddate","","Final Date",false,'','','',$todate,'',$cellclass,'','The date of the last session for this task. Use 31/12/2099 to represent an indefinite period.','','','','','','','','vols-tablerow ');
        $this->component->setwidths (30,70,0);
        $formfields .="  <input type='hidden' name='recurrence' data-fnum='9' id='recurrence'  value='' />\n";
        // because the recurrence field is an enum, the value for the radion button must be the text element, not its index
        $buttons = [["Once-only"=>"Once-only"],["Daily"=>"Daily"],["Weekly"=>"Weekly"],["Monthly"=>"Monthly"]]; //,["Yearly"=>"Yearly"]
        $rb  = $this->component->renderradiobuttons("rb",$buttons,0,"",999,true,"rb",required:true);
        $formfields .= $this->component->renderformrow('recurrencerow',"","Recurring period",true,'','','',$rb);
        for($d=1;$d<=31;$d++) {
            $dom[$d]=$d;
        }
        $fn = 10;

        // DAILY OPTIONS==================================================================   10/11
        $formfields .= '<div id="dailyrecurrence" class="periodic-ocurrence">';
            $formfields .="  <input type='hidden' data-fnum='10' name='dailyoption' id='dailyoption'  value='' />\n";
            $dailyinterval =  $this->component->rendertextinput("dailyinterval",3,3,"1",false,"",'','vols-form-input',11,false,false,false,1,);
            $dailyintervalinput =  " Every &nbsp; {$dailyinterval} &nbsp; day(s)";
            $buttons = [[$dailyintervalinput => 0],["Every weekday"=>1]];
            $dailyoptions  = $this->component->renderradiobuttons("dayopt",$buttons,0,"",999,false,'do',false);        
            $formfields .= $this->component->renderformrow('dailyrow',"","Details",false,'','','',$dailyoptions,'',$cellclass,'','','','','','','','','','vols-tablerow ');
        $formfields .= '</div>';
        // WEEKLY OPTIONS===============================================================  12/13
        $formfields .= '<div id="weeklyrecurrence" class="periodic-ocurrence">';
            $weeklygroup  =  '<div class="vols-form-radiobuttons vols-width-95 ">';
            $weeklygroup  .=  "Recur every &nbsp; ".$this->component->rendertextinput("weeklyinterval",3,3,"1",false,"",'','vols-form-input',12,false,false,false,1,' week(s) on the: ')."</div>";
            $windex = "<div id='weeklyindexwrapper' class='vols-float-left'><select name='weeklyindex' id='weeklyindex' class='vols-form-select hide' size='1' required='' data-fnum='32' disabled=''></select></div>"; // only used if weekly interval > 1, populated by JS $onloadscript (see below)
            $weeklygroup .= $this->component->dayofweekcheckboxes("weeklydow",13,$windex,"",0,false,$this->loaddowfieldscript,$this->loaddowvariablescript,"wdow",true);
            $formfields  .= $this->component->renderformrow('weeklyrow',"","Details",false,'','','',$weeklygroup,'',$cellclass,'','','','','','','','','','vols-tablerow ');
        $formfields .= '</div>';
        // MONTHLY OPTIONS=============================================================== 14..19
        $ordinalvals = ["first","second","third","fourth","last"];
        $daynames = [0=>"day",1=>"weekday",2=>"weekend day",3=>"Sunday",4=>"Monday",5=>"Tuesday",6=>"Wenesday",7=>"Thursday",8=>"Friday",9=>"Saturday"];

        $formfields .= '<div id="monthlyrecurrence" class="periodic-ocurrence">';
        $formfields .="  <input type='hidden' data-fnum='14' name='monthlyoption' id='monthlyoption' value='' />\n";
        $monthdaynums = $this->component->renderdropdown("monthlydayofmonth",1,$optn,false,false,false,false,$dom,'',false,'','',false,15);
        $monthlyinterval0 = $this->component->rendertextinput("monthlyinterval0",3,3,"1",false,"",'','vols-form-input',16,false,false,false,1,'month(s)');
        $monthlyoption0 = "Day ".$monthdaynums." of every ".$monthlyinterval0;

        $monthordinaldropdown = $this->component->renderdropdown("monthlywhichdow",1,$optn,false,false,false,false,$ordinalvals,'',false,'','',false,17);
        $monthdaynamesdropdown = $this->component->renderdropdown("monthlydow",1,$optn,false,false,false,false,$daynames,'',false,'','',false,18);
        $monthlyinterval1 = $this->component->rendertextinput("monthlyinterval1",3,3,"1",false,"",'','vols-form-input',19,false,false,false,1,'month(s)');
        $monthlyoption1 = $monthordinaldropdown." &nbsp; ".$monthdaynamesdropdown." &nbsp; of every &nbsp; ".$monthlyinterval1;
        $buttons = [[$monthlyoption0=>0],[$monthlyoption1 =>1]];
        $monthlygroup  = $this->component->renderradiobuttons("monopt",$buttons,0,"",999,false,'mo',false);
        $formfields .= $this->component->renderformrow('monthlyrow',"","Details",false,'','','',$monthlygroup,'',$cellclass,'','','','','','','','','','vols-tablerow ');
        $formfields .= '</div>';
        // =========================================== Publication section
        $this->component->setwidths (30,20,50);
        $formfields .= $this->component->rendersectionheading("Publication",inputgroup:"publishgroup");
        $formfields .= $this->component->buildinputrow("leadtime",5,"",'Leadtime (weeks)','',3,3,false,'','How many weeks ahead of today should the system <strong>generate</strong> sessions?');
        $formfields .= $this->component->buildinputrow("publishedleadtime",6,"",'Published Leadtime','',3,3,false,'','How many weeks ahead of today should the system <strong>publish</strong> sessions?');
        $hint1 = <<<HINT
        These "<STRONG>Booking Alert ...</STRONG>" fields are used by the process that generates emails appealing for more volunteers, as required.<BR>"<STRONG>Booking Alert Periods</STRONG>" specifies the number of days before the task on which the system will check for insufficient bookings. A field containing e.g. "2,7,21" specifies 3 checking periods : 1-2 days, 3-7 days, and 8-21 days BEFORE THE TASK. The first period is always "URGENT". Earlier periods get normal listing but typically differ in the bookings alert levels (next field).<BR>This and the following field must hold the same number of comma-separated values.
        HINT;
        $hint2 = <<<HINT
        "<STRONG>Booking Alert Levels</STRONG>" specifies the minimum number of volunteers needed in the checking periods - any session with fewer than this will be included in the appeal email.<BR>A field containing "3,3,2" specifies that fewer than 3 volunteers in the URGENT period, or 3 volunteers on days 3-7 before the task, or 2 volunteers on days 8-21 before the task, will trigger an appeal email to all volunteers.<BR>This and the previous field must hold the same number of comma-separated values.<BR>
        HINT;
        $formfields .= $this->component->buildinputrow("bookingalertperiods",8,"",'Booking Alert Periods','',10,20,false,'',$hint1);
        $formfields .= $this->component->buildinputrow("bookingalertlevels",7,"",'Booking Alert Levels','',10,20,false,'',$hint2);

        // // YEARLY OPTIONS=============================================================== 20..25
        // $formfields .= '<div id="yearlyrecurrence" class="periodic-ocurrence">';
        //     $formfields .="  <input type='hidden' data-fnum='20' name='yearlyoption' id='yearlyoption' value='' />\n";
        //     // $yearlygroup  =  "Recur every ".$this->component->rendertextinput("yearlymultiple",3,3,"1",false,"",'','vols-form-input',$fn++,false,false,false,1,' year(s)');
        //     $monthnames = ["January","February","March","April","May","June","July","August","September","October","November","December"];
        //     $yeardaynumdropdown = $this->component->renderdropdown("yearlydom",1,$optn,false,false,false,false,$dom,'',false,'','',false,21);
        //     $yearmonthnamesdropdown0 = $this->component->renderdropdown("yearlymonth0",1,$optn,false,false,false,false,$monthnames,'',false,'','',false,22);
        //     $yearlyoption0 = " On: &nbsp; ".$yeardaynumdropdown." &nbsp; ".$yearmonthnamesdropdown0;
        //     $yearordinaldropdown = $this->component->renderdropdown("yearlywhichdom",1,$optn,false,false,false,false,$ordinalvals,'',false,'','',false,23);
        //     $yeardaynamesdropdown = $this->component->renderdropdown("yearlywhichday",1,$optn,false,false,false,false,$daynames,'',false,'','',false,24);
        //     $yearmonthnamesdropdown1 = $this->component->renderdropdown("yearlymonth1",1,$optn,false,false,false,false,$monthnames,'',false,'','',false,25);
        //     $yearlyoption1 = " On the &nbsp;".$yearordinaldropdown." &nbsp; ".$yeardaynamesdropdown."&nbsp; of  &nbsp;".$yearmonthnamesdropdown1;
        //     $buttons = [[$yearlyoption0 => 0],[$yearlyoption1 =>1]];
        //     $yearlyoptions  = $this->component->renderradiobuttons("yearopt",$buttons,0,"",999,false,'yo',false);        
        //     $formfields .= $this->component->renderformrow('yearlyrow',"","Details",false,'','','',$yearlyoptions,'',$cellclass,'','','','','','','','','','');
        // $formfields .= '</div>';
        $this->resetwidths();        
        if ($this->isadmin || in_array($this->pagenum."||ROLES",$rights)) {
            $fn = 35;  // there are 34 task table fields
            $buttons = ["rightid"=>"showrowsbtn","righttext"=>"Show A<span class='underlined'>L</span>L","rightscript"=>"","leftid"=>"","lefttext"=>"","leftscript"=>""];
            $heading = "<span id='statustextspan'>ALL</span> Roles";
            $formfields .= $this->component->rendersectionheading($heading,buttons:$buttons);
            $this->component->setwidths (30,40,30);
            $hiddencheckboxes = '';
            foreach ($this->roles as $role) {
                $rolename = "link_role".$role["id"];
                $rcb = $this->component->rendercheckbox($rolename,1,0,'',false,$fn++,false,'','',false,false,false);
                $min = $this->component->rendertextinput($rolename."_min_quantity",3,5,"",false,"",'','',$fn++,false,false,true,1);
                $max = $this->component->rendertextinput($rolename."_max_quantity",3,5,"",false,"",'','',$fn++,false,false,true,1);
                $rolefields = $rcb." &nbsp; &nbsp;min &nbsp;".$min." &nbsp; &nbsp;max &nbsp;".$max;
                $formfields .= $this->component->renderformrow("link_role".$role["id"]."row","",$role["name"],false,"","","",$rolefields,'','','',"Volunteers needed from this Role",'','','','','','','','');
                $hiddencheckboxes .= '<input type="hidden" name="'.$rolename.'"  value=false />';
            }
        }
        $this->preparecommontop(selecttext:$this->taskid,hiddeninputs:$hiddencheckboxes);
        return $formfields;
     }
    public function formscript() {
        // passive validation of email and phone number when being entered
          //    Thus the SUBCLASS should do the following:
          //        1.   CALL parentscript() . The parameters passed with this call will determine some other subclass requirements:
          //        2.   IMPLEMENT ITS OWN $(document).ready() IF REQUIRED e.g. assign form-specific event handlers, initialise form-specific (third party) components
          //        3.   IMPLEMENT validateform() AS AT LEAST A STUB (return true), or as needed by the form - this function is required in all subclasses
          //        4.   IMPLEMENT thiseditexisting() as needed by the form - this function is required if $singlerecord = false
          //        5.   IMPLEMENT thisaddnewrecord() as needed by the form - this function is required if $singlerecord = false
          //        6.   IMPLEMENT updatefields() - this function is required if $updatefields=true
          //        7.   IMPLEMENT refreshmulti() - this function is required if $inclmulti=true
          //        8.   IMPLEMENT displayselectedrecord() - this function is required if $idselection=false
        $postloadfieldsscript = <<<JS
                        // this script is already built by the component class as it creates the DOW check boxes
                        {$this->loaddowfieldscript} 
                        // find the Recurrence period radio button to check based on the data 
                        const recurrenceval = jQuery("#recurrence").val();  // loaded from the hidden fields or set by user
                        // let radioarray =  ["Once-only","Daily","Weekly","Monthly","Yearly"];
                        // let btnnum = radioarray.indexOf(recurrenceval); 
                        const radioid = "#rb"+ recurrenceval; // this is the button to be checked
                        jQuery(radioid).prop("checked", true).trigger("click"); 
                        showoptions(recurrenceval);

                        const dailyoptionid = "#do"+jQuery("#dailyoption").val();
                        jQuery(dailyoptionid).prop("checked", true)
                        const monthoptionid = "#mo"+jQuery("#monthlyoption").val();
                        jQuery(monthoptionid).prop("checked", true)
                        const yearlyoptionid = "#yo"+jQuery("#yearlyoption").val();
                        jQuery(yearlyoptionid).prop("checked", true)

                        if (jQuery("#showrowsbtn").text() === "Show linked") {
                            jQuery("#dataspace [id^=link_role]").removeClass("hidden");
                        } else {
                            jQuery("#dataspace [id^=link_role]").addClass("hidden");
                            jQuery("#dataspace [id^=link_role] input[type='checkbox']:checked").parent().parent().removeClass("hidden");        
                        }
                        displayweeklyindex(jfield[12],jfield[32]);
                        showhidepages();

         JS;
        $postclearfieldsscript = <<<JS

                        jQuery("input[type='checkbox']").prop("checked",false);
                        $('input:radio').each(function () { $(this).prop('checked', false); });
        JS;
        $presavescript = <<<JS

                        jQuery("#formerror").html("") ;
                        {$this->loaddowvariablescript}
                        // now recover the index from the 'checked' radio to 
                        let thisval = jQuery("input[type='radio'][name='rb']:checked").val();
                        jQuery("#recurrence").val(thisval);
                        
                        thisval = jQuery("input[type='radio'][name='dayopt']:checked").val();
                        jQuery("#dailyoption")   .val(thisval);
                        
                        thisval = jQuery("input[type='radio'][name='monopt']:checked").val();
                        jQuery("#monthlyoption") .val(thisval);
                        
                        thisval = jQuery("input[type='radio'][name='yearopt']:checked").val();
                        jQuery("#yearlyoption") .val(thisval);

        JS;
        $disablescript = "";
        $onloadscript = <<<JS
                        // need to display the appropriate recurrence options based on the recurrence period
                        jQuery("input[type='radio'][name='rb']").click(function() {
                            const recurrenceval = jQuery("input[type='radio'][name='rb']:checked").val();
                            showoptions(recurrenceval);
                        }) 
                        jQuery("#buildsessions").on( "click", function(event) {
                            setallinactivestatus(1,1,0,0,0,0);
                            $("#action").val("buildsessions");
                            jQuery("#{$this->formname}").trigger("submit");
                        });
                        jQuery("#weeklyinterval").on("change", function() {
                            const interval = $(this).val();
                            const index = jQuery("#weeklyindex").val(); 
                            displayweeklyindex(interval,index);
                        });
        JS;
        $script  = $this->vols_masterscript($this->formname, 
                                    $this->objname, //$objectname
                                    true, //$idselection=
                                    true,  //$adjustnamerow=
                                    true, //$updatefields=
                                    false, //$inclmulti=
                                    '',  //$postajaxscript=
                                    $postloadfieldsscript,  //
                                    $postclearfieldsscript, //$postclearfieldsscript=
                                    false, //$trace=
                                    '',  //$multisubmit
                                    $presavescript,
                                    $disablescript,
                                    $onloadscript
                                    ); 
        $script .= <<<JS
            function showhidepages() { 
                setchildselectorheadingtext();
                const element = document.getElementById("dataspace");
                element.scrollTop = element.scrollHeight;
            }
        
            function ordinalwords( cardinal ) {
                const ordinals = [ 'zeroth', 'first', 'second', 'third', 'fourth', 'fifth', 'sixth', 'seventh', 'eighth', 'nineth', 'tenth', 'eleventh', 'twelfth', 'thirteenth', 'fourteenth', 'fifteenth', 'sixteenth', 'seventeenth', 'eighteenth', 'nineteenth', 'twentieth'];
                const tens = {
                    20: 'twenty',
                    30: 'thirty',
                    40: 'forty', 
                    50: 'fifty',
                    60: 'sixty', 
                    70: 'seventy',
                    80: 'eighty', 
                    90: 'ninety',
                };
                const ordinalTens = {
                    20: 'twentieth',
                    30: 'thirtieth',
                    40: 'fortieth',
                    50: 'fiftieth', 
                    60: 'sixtieth',
                    70: 'seventieth',
                    80: 'eightieth', 
                    90: 'ninetieth',
                };

                if( cardinal <= 20 ) {                    
                    return ordinals[ cardinal ];
                }

                if( cardinal % 10 === 0 ) {
                    return ordinalTens[ cardinal ];
                }

                return tens[ cardinal - ( cardinal % 10 ) ] + ordinals[ cardinal % 10 ];
            }
            function displayweeklyindex(weeklyinterval,weeklyindex) {
                // this updates the weeklyindex dropdown with the appropriate <options> for the value of weeklyinterval
                // it's called when a record is loaded and when weeklyinterval changes 
                if (weeklyinterval > 1) {
                    let options  = '<option id="weeklyindex-0" value="0" '+(weeklyindex==0?'selected':'') +'>First</option>';
                    options += '<option id="weeklyindex-1" value="1" '+(weeklyindex==1?'selected':'') +'>Second</option>';
                    for (i=3;i<=weeklyinterval;i++) {
                        const ordinal = ordinalwords(i);
                        options += '<option id="weeklyindex-'+(i-1)+'" value="'+(i-1)+'" '+(weeklyindex==(i-1)?'selected':'') +'>'+ordinal+'</option>';
                    }
                    jQuery("#weeklyindex").html(options).removeClass("hidden");
                } else {
                    jQuery("#weeklyindex").html("").addClass("hidden");
                }
             }
            function formhaserrors() {
                let errors = 0;
                if (!jQuery("#name").val()){ 
                    jQuery("#namerow_error").html("(This is a required field.)");
                    errors++;
                }
                return errors;
            }
            function displayselectedrecord() {
            }
            function showoptions(recurrenceval) {
                let optionblock;
                switch (recurrenceval) {
                    case "Once-only": break;
                    case "Daily": optionblock = "#dailyrecurrence"; break;
                    case "Weekly": optionblock = "#weeklyrecurrence"; break;
                    case "Monthly": optionblock = "#monthlyrecurrence"; break;
                    /* case "Yearly": optionblock = "#yearlyrecurrence"; break; */
                    default: optionblock = ""
                }
                jQuery(".periodic-ocurrence").removeClass("show").addClass("hide");
                if (optionblock !== "") {
                    jQuery(optionblock).removeClass("hide").addClass("show");
                }                 
            }
            function getchildnames() { return ["role","Roles"]}
         JS;
        return $script;
     }
}
