<?php
namespace app\view\form;
use \lib\StdLib as lib;
use \app\library\AppLib as applib;
class AttendanceReportForm extends \fw\view\form\Form { 
    protected $trace= false;                
    protected $promptwidth = 15;
    protected $inputwidth = 55;
    protected $hintwidth =30;
    protected $fields = [];
    protected $pagename = "Attendance Report";
    protected $formname = "attendancereportview";
    protected $objname = "Client Attendance Reports";
    protected $rights;
    protected $sessions;
    protected $requestdata;
    protected $config;
    protected $man;
    protected $woman;
    protected $house; 
    protected $shopper; 
    protected $card; 
    protected $summary; 
    public  function __construct(protected FormComponent $component) {
        if ($this->trace) { echo "Enter ".__METHOD__."<br>"; }
     }
    public  function __destruct() {
        if ($this->trace) { echo "Enter ".__METHOD__."<br>"; }
     }
    public  function init($sessn){
        parent::init($sessn);
        $this->requestdata = $this->session->getrequestdata();
        $this->config = $this->session->getconfig();
        $this->component->init($this->session,false,$this->promptwidth,$this->inputwidth,$this->hintwidth,$this->recorddelimiter,$this->isadmin,false);
        $this->man = $this->component->manicon; 
        $this->woman = $this->component->womanicon; 
        $this->house = $this->component->houseicon; 
        $this->shopper = $this->component->shoppericon; 
        $this->card = $this->component->cardicon; 
     }
    public  function setdata($summary){
        $this->summary = $summary; 
     }
    private function rendersessionline($context,$sessdata) {
        switch ($context) {
            case -1:
                $keytoiconmap = ["Male"=>$this->man,"Female"=>$this->woman,
                                "Clients"=>$this->shopper,"Total beneficiaries"=>$this->house,
                                "Cnsn"=>$this->card,"DietOther"=>"??"];
                
                $sessionline  = "<div class='report-sessionline report-sessionheader2'>";
                $sessionline .= "<div>Clients</div><div>Gender</div><div>Age Group</div><div>Residence</div><div>Conc</div><div></div><div>Dietary</div><div>Rep.</div>";
                $sessionline .= "</div>"; 
                $sessionline .= "<div  class='report-sessionline report-sessionheaders'>";
                foreach ($sessdata as $key => $value) {
                    $key = $keytoiconmap[$key]??$key;
                    $sessionline .= "<div class='vols-tablecell vols-width-100 aligncenter report-colheading'>{$key}</div>";           
                }
                break;            
            case 0:
                break;            
            case 1:
                $sessionline = "<div  class='report-sessionline report-sessiontask'>";
                $sessionline .= "<div class='vols-tablecell vols-width-100 aligncenter taskname'>{$sessdata["Taskname"]}</div>";
                break;            
            case 2:
                $sessionline = "<div  class='report-sessionline report-sessiondate'>";
                $sessionline .= "<div class='vols-tablecell vols-width-100 aligncenter sessiondate'>{$sessdata["Date"]}</div>";
                break;            
            case 3:
            case 4:
            case 5:
                $contextclass = ($context==5?"report-grandtotals":($context==4?"report-tasktotals":"report-sesssiontotals"));
                $sessionline = "<div  class='report-sessionline report-{$contextclass}'>";
                // $f = 1;
                foreach ($sessdata as $key => $value) {
                    if ($key != "Taskname" && $key != "Date" ) { // we jump over the taskname and date fields
                        $sessionline .= "<div class='vols-tablecell vols-width-100 aligncenter'>{$value}</div>";
                    }
                }
        // lib::vd($sessdata);
                break;            
            default:

        } 
        $sessionline .= "</div>"; 
        return $sessionline;
     }
    private function advancesessiontotals($data,&$sessiontotals,&$tasktotals,&$grandtotals) {
        // this ia a single clientsession
        // the following array converts the possible values of the enum fields in the database to
        // individual columns in the display/report
        $valuetokeymap = ["MALE"=>"Male","FEMALE"=>"Female","NOTGIVEN"=>"??","OTHER"=>"??","12"=>"12+","22"=>"22+","31"=>"31+","40"=>"40+","55"=>"55+","68"=>"68+","self"=>"Self","carer"=>"Carer",'Not supplied'=>"?",'Rent'=>"Rent",'OwnHome'=>"Own",'Temporary'=>"Temp",'Other'=>"?"];
        $k="";$v="";

        $agegroup = applib::agegroup($data["month_of_birth"],$data["year_of_birth"]);
        if ($agegroup<> "") {
            $sessiontotals[$agegroup] += 1;
            $tasktotals[$agegroup] += 1;
            $grandtotals[$agegroup] += 1;
        // } else {
        //     lib::v($data["month_of_birth"],$data["year_of_birth"]);
        }

        set_error_handler(function ($severity, $message, $file, $line) {
            throw new \ErrorException($message, 0, $severity, $file, $line);
        });
        foreach ($data as $key=>$value) {
            switch ($key) {
                case "taskname" : 
                    $sessiontotals["Taskname"] = $value??"";
                    $tasktotals["Taskname"] = $value??"";
                    $grandtotals["Taskname"] = $value??"";
                    break;
                case "start" : 
                    $sessiontotals["Date"] = $value??"";
                    $tasktotals["Date"] = $value??"";
                    $grandtotals["Date"] = $value??"";
                    break;
                case "dietary" :
                    // 1=Gluten free\\\\n2=Vegetarian\\\\n4=Vegan\\\\n8=DairyFree\\\\n16=Nut Free\\\\n32=Other\\\\n 
                    $dietkeys = [0=>"GF",1=>"V",2=>"VGN",3=>"DF",4=>"NF",5=>"DietOther"]; 
                    $val = (int)$value;
                    for ($power = 5;$power >= 0; $power--) {
                        $totkey =$dietkeys[$power];

            // lib::pr($val,$power,pow(2,$power),($val / pow(2,$power)),$totkey);                        
                        if ($val / pow(2,$power) >= 1 ) {
                            $sessiontotals[$totkey] += 1;
                            $tasktotals[$totkey]  += 1;
                            $grandtotals[$totkey]  += 1;
                            $val -= pow(2,$power);
                        }
                    }
                    break;
                default: 
                    try {
                        switch ($key) {
                            case "gender" : 
                            // case "age_group" : 
                            case "residence" : 
                            case "represented_by" : 
                                if ($value != "" && $value != "0") {
                                    $k=$valuetokeymap[$value];  
                                    $v=1;  
                                }
                                break;
                            case "aborigine_TSislander" : 
                                $k="ATSI";  
                                $v=$value;  
                                break;
                            case "concession_card" : 
                                $k="Cnsn";   
                                $v=$value;   
                                break;
                            case "household": 
                                $k="Total beneficiaries";  
                                $v=$value;  
                                break;
                            default:
                                $k="";
                        }
                    } catch (\ErrorException $e) {
                        echo "Caught an error: " . $e->getMessage()."<br>mykey ='$key', myvalue = '$value',  myk = '$k' <br>";;
                    } 
                    if ($k<>"") {
                        $sessiontotals[$k] += $v;
                        $tasktotals[$k] += $v;
                        $grandtotals[$k] += $v;
                    }
            }
        }
        $sessiontotals["Clients"] += 1;
        $tasktotals["Clients"] += 1;
        $grandtotals["Clients"] += 1;
        restore_error_handler();
        // lib::prd("AA",$sessiontotals);   
     }
    public  function rendersessionreport($data,$dates,$trace=false){
        // called in response to ajax call to format the report data just returned from db
        if ($this->trace || $trace) { echo "Enter ".__METHOD__."<br>"; }
        $task = "";
        $date = "";
        $task_array = ["Taskname"=>""];
        $date_array = ["?"=>"","Date"=>""];
        $sessionarray = ["Clients"=>0,"Total beneficiaries"=>0,"Male"=>0,"Female"=>0,"???"=>0,"<12"=>0,"12+"=>0,"22+"=>0,"31+"=>0,"40+"=>0,"55+"=>0,"68+"=>0,
                        "Rent"=>0,"Own"=>0,"Temp"=>0,"?"=>0,"Cnsn"=>0,"ATSI"=>0,"GF"=>0,"V"=>0,"VGN"=>0,"DF"=>0,"NF"=>0,"DietOther"=>0,"Self"=>0,"Carer"=>0];
        $grandtotals = $tasktotals = $sessiontotals = $sessionarray;
        $sessions  =  '<div id="detailedreportcontainer" >';
        $sessions .=  '<div class="taskcontainer" >';
        foreach ($data as $session) { // each record is a single client session
            if (($session["taskname"] <> $task && $task <> "") || ($session["start"] <> $date && $date <> "")) {
                // report session totals
                $sessions.= $this->rendersessionline(3,$sessiontotals);
                $sessiontotals = $sessionarray;
                if ($session["taskname"] <> $task) {
                    $sessions.= $this->rendersessionline(1,["Taskname"=>$task." Totals"]);
                    $sessions.= $this->rendersessionline(4,$tasktotals);
                    $tasktotals = $sessionarray;
                    $sessions .=  '</div><div class="taskcontainer" >';
                }
            }
            if ($session["taskname"] <> $task) { // render new taskname
                $sessions .= $this->rendersessionline(-1,$sessionarray); // headings
                $sessions .= $this->rendersessionline(1,["Taskname"=>$session["taskname"]]);
            }
            if ($session["start"] <> $date) { // render new date
                $sessions .= $this->rendersessionline(2,["Date"=>$session["start"]]);
            } 
            $date = $session["start"];
            $task = $session["taskname"];
            $this->advancesessiontotals($session,$sessiontotals,$tasktotals,$grandtotals);
        }
        $sessions .= $this->rendersessionline(3,$sessiontotals) ;
        $sessions .= $this->rendersessionline(1,["Taskname"=>$task." Totals"]);
        $sessions .= $this->rendersessionline(4,$tasktotals) ;
        $sessions .= $this->rendersessionline(-1,$sessionarray); // headings
        $sessions .= '</div><div class="taskcontainer" >';
        $fromdate = substr($dates,0,10);
        $todate = substr($dates,11);
        $sessions .= $this->rendersessionline(1,["Taskname"=>"Report Totals for the period ".$fromdate." to ".$todate]);
        $sessions .= $this->rendersessionline(5,$grandtotals) ;
        $sessions .= "</div></div>";
        return $sessions;
     }
    public  function renderbeneficiariesreport($data,$dates,$trace=false){
        // called in response to ajax call to format the report data just returned from db
        if ($this->trace || $trace) { echo "Enter ".__METHOD__."<br>"; }
        $task = "";
        $date = "";
        $task_array = ["Taskname"=>""];
        $date_array = ["?"=>"","Date"=>""];
        $sessionarray = ["Clients"=>0,"Total beneficiaries"=>0,"Male"=>0,"Female"=>0,"???"=>0,"12+"=>0,"22+"=>0,"31+"=>0,"40+"=>0,"55+"=>0,"68+"=>0,
                        "Rent"=>0,"Own"=>0,"Temp"=>0,"?"=>0,"Cnsn"=>0,"ATSI"=>0,"GF"=>0,"V"=>0,"VGN"=>0,"DF"=>0,"NF"=>0,"DietOther"=>0,"Self"=>0,"Carer"=>0];
        $grandtotals = $tasktotals = $sessiontotals = $sessionarray;
        // $clientdata = ["Suburb"=>"","Attendances"=>"","Gender"=>"","ATSI"=>"","AgeGroup"=>"","Nationalty"=>"","Home"=>"","Carer"=>"","concession"=>"","Dietary"=>"","Household"=>""];
        // $sessions = $clients = "";
        // $genderhead = "Gender";
        $sessions =  '<div id="detailedreportcontainer" >';
        foreach ($data as $session) {
        // lib::e($session["taskname"]."||".$task."||".$session["start"]."||".$date."||".."||".)
            if (($session["taskname"] <> $task && $task <> "") || ($session["start"] <> $date && $date <> "")) {
                // report session totals
                $sessions.= $this->rendersessionline(3,$sessiontotals);
                $sessiontotals = $sessionarray;
                if ($session["taskname"] <> $task) {
                    $sessions.= $this->rendersessionline(1,["Taskname"=>$task." Totals"]);
                    $sessions.= $this->rendersessionline(4,$tasktotals);
                    $tasktotals = $sessionarray;
                }
            }
            if ($session["taskname"] <> $task) { // render new taskname
                $sessions .= $this->rendersessionline(-1,$sessionarray); // headings
                $sessions .= $this->rendersessionline(1,["Taskname"=>$session["taskname"]]);
            }
            if ($session["start"] <> $date) { // render new date
                $sessions .= $this->rendersessionline(2,["Date"=>$session["start"]]);
            } 
            $date = $session["start"];
            $task = $session["taskname"];
            $this->advancesessiontotals($session,$sessiontotals,$tasktotals,$grandtotals);
        }
        $sessions .= $this->rendersessionline(3,$sessiontotals) ;
        $sessions .= $this->rendersessionline(1,["Taskname"=>$task." Totals"]);
        $sessions .= $this->rendersessionline(4,$tasktotals) ;
        $sessions .= $this->rendersessionline(-1,$sessionarray); // headings
        $fromdate = substr($dates,0,10);
        $todate = substr($dates,11);
        $sessions .= $this->rendersessionline(1,["Taskname"=>"Report Totals for the period ".$fromdate." to ".$todate]);
        $sessions .= $this->rendersessionline(5,$grandtotals) ;
        $sessions .= "</div>";
        return $sessions;
     }
    private function rendersummary() {
        // lib::vd($this->summary);
        $dat = $this->summary;
        $summary  = <<<HTML

            <div id="summarycontainer">
                <div>&nbsp;</div>
                <div id="summarywrapper">
                    <div class="summaryheading">Period</div><div class="summaryheading">Clients</div><div class="summaryheading">Total in<br>Households</div><div class="summaryheading">Children</div>
                    <div class="summaryheading">Current Month to date</div><div>{$dat[3]["clients"]}</div><div>{$dat[3]["population"]}</div><div>{$dat[3]["children"]}</div>
                    <div class="summaryheading">Previous Month</div><div>{$dat[0]["clients"]}</div><div>{$dat[0]["population"]}</div><div>{$dat[0]["children"]}</div>
                    <div class="summaryheading">Previous Quarter</div><div>{$dat[1]["clients"]}</div><div>{$dat[1]["population"]}</div><div>{$dat[1]["children"]}</div>
                    <div class="summaryheading">Previous Year</div><div>{$dat[2]["clients"]}</div><div>{$dat[2]["population"]}</div><div>{$dat[2]["children"]}</div>
                </div>
                <div>&nbsp;</div>
            </div>
        HTML;
        return $summary;
     }
    public  function render($pagenum=0,$nextpage='',$subheading='',$rights=[],$isadmin=false,$menu='',$trace=false) {
        if ($this->trace || $trace) { echo "Enter ".__METHOD__."<br>"; }
        $this->isadmin = $isadmin;
        $heading=["heading"=>"Client Attendance Reports"];
        $actnbtns = ["noactionrow"=>true]; 
        $hint = '<div id="finddata" class="clickable action  doitbg vols-vertical-center " style="padding:0"><span class="underlined">F</span>ind data</div>';
        $sectionhead = ["heading"=>$this->objname,"headingclass"=>"vols-form-pageheading","subheading"=>"","subheadingclass"=>""];        
        $content = $this->component->rendercommontopbrief($sectionhead,$actnbtns,$menu,$trace);
        $content .= $this->rendersummary($trace);
        $inputs = $this->component->rendersectionheading("Custom Report");
        $inputs .= $this->component->renderdaterangerow("daterange","Date range","fromdate","todate",1,2,"","",hint:$hint);
        $content .=  <<<HTML
                <div id ="sessiongridcontainer">
                    {$inputs}
                    <div class="actioncontainer">
                        <div id="editcontainer" class="col3"  class="col3" style="margin: 0 auto;">
                            <div class="vols-tablecell vols-width-100 aligncenter">
                                <div id="printdetailed" class="clickable action doitbg disabled ">Print <span class="underlined">D</span>etailed</div>
                            </div>
                            <div class="vols-tablecell vols-width-100 aligncenter">
                                <div id="printsummary" class="clickable action doitbg disabled">Print <span class="underlined">S</span>ummary</div>
                            </div>
                            <div class="vols-tablecell vols-width-100 aligncenter">
                                <div id="printsummary" class="clickable action doitbg disabled">Print <span class="underlined">E</span>xport</div>
                            </div>
                        </div>
                    </div>
                    <div id="reportscontainer"> 
                        <!-- filled by ajax call -->
                    </div>    
                </div>
        HTML;
        $content .= $this->component->rendercommonbottom(false);
        $content  .= "<script>".$this->formscript()."</script>";  
        return $content;
     }
    public  function buildinputs($rights=[],$trace=false) {
        // lib::pr($this->rights);     

        if ($this->trace) { echo "Enter ".__METHOD__."<br>"; }
        return $content;
     }
    public  function formscript() {
        $script = <<<JS
                jQuery(function() {
                    let today = new Date().toISOString().slice(0, 10)
                    jQuery("#fromdate").val("2025-12-01")
                    jQuery("#todate").val(today)



                    function isDateValid(dateStr) {
                      return !isNaN(new Date(dateStr));
                    }
                    jQuery("#finddata").on("click",async  function() {
                        // check date range
                        const fromdatestr  = jQuery("#fromdate").val();
                        const todatestr    = jQuery("#todate").val();

                        const fd        = new Date(fromdatestr);
                        const td        = new Date(todatestr);
                        if (!(isDateValid(fromdatestr) && isDateValid(todatestr))) {
                            alert("Not a valid date.") 
                        } else if (fd > td) {
                            alert("Dates in incorrect order.") 
                        } else {
                            // if OK send request
                            vols.cursor.wait();
                            result = await  doServerRequest('',fromdatestr+'|'+todatestr,'attendancereport');
                            vols.cursor.default();
                            jQuery("#reportscontainer").html(result);
                        } 
                    });

                    jQuery("#printdetailed, #printsummary").on("click",function() {
                        let eid = 0;
                        let task = [];
                        const detailed = $(this).prop("id") == "printdetailed";
                        jQuery("#rostercontainer div.taskcontainer").each(function() {
                            task.push(jQuery(this).find("div.taskheading").html());
                            let sessions = [];
                            jQuery(this).find("div.sessioncontainer").each(function () {
                                sessiondate = jQuery(this).find("div.sessiondatetext").html();
                                let sessiondata=[]
                                sessiondata.push(sessiondate)
                                jQuery(this).find("div.volcell").each(function () {
                                    celltext = jQuery(this).html();
                                    sessiondata.push(celltext);
                                }); 
                                sessions.push(sessiondata);
                            }); 
                            task.push(sessions);
                        });
                        nowstr = fw_nowstring(1);
                        footer = "Printed at "+fw_nowstring(0);
                        Print2TaskRoster('ROSTER_'+nowstr,"{$this->config["app"]["DEPARTMENT"]} Roster",task,"{$this->config["app"]["ORGANISATIONNAME"]}",footer);
                    });



                });

        JS;
        return $script;
     }
}