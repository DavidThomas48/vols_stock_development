<?php
namespace app\view\form;
use \lib\StdLib as lib;
class ClientForm extends \fw\view\form\StdCRUDForm {
    protected $trace= false;                
    protected $promptwidth = 30;
    protected $inputwidth = 65;
    protected $hintwidth = 5;
    protected $fields = [];
    protected $parentname = "";
    protected $parentobj = "";
    protected $pagenum;
    protected $clientid;
    protected $names;
    protected $parents;
    protected $usermgr;
    protected $rights;
    protected $sessions;
    protected $clientmembers;
    protected $clientsessions;
    protected $volunteers;
    protected $config;
    protected $placenames;
    protected $tandc_text = 'I confirm that the personal information I have provided is accurate and complete. '
        . 'I consent to Woodend Neighbourhood House Food Bank collecting and holding this information '
        . 'for the purpose of providing food relief services, in accordance with the Privacy Act 1988 (Cth).';
    protected $months = ["0"=>"","1"=>"Jan","2"=>"Feb","3"=>"Mar","4"=>"Apr","5"=>"May","6"=>"Jun","7"=>"Jul","8"=>"Aug","9"=>"Sep","10"=>"Oct","11"=>"Nov","12"=>"Dec"];
    protected $years = [0=>""];
    protected $states = [""=>"","NSW"=>"NSW","VIC"=>"VIC","QLD"=>"QLD","SA"=>"SA","WA"=>"WA","TAS"=>"TAS","NT"=>"NT","ACT"=>"ACT"];
    protected $genders = [["Male"=>"MALE"],["Female"=>"FEMALE"],["Other"=>"OTHER"]];
    protected $representation = [["self"=>"self"],["carer"=>"carer"]];
    protected $residence = ["Not Supplied"=>"Not Supplied","Rent"=>"Rental","OwnHome"=>"Own Home","Temporary"=>"Temporary","Other"=>"Other"];
    protected $agegroups = ["12"=>"12-21 yo","22"=>"22-30 yo","31"=>"31-39 yo","40"=>"40-55 yo","56"=>"56-67 yo","68"=>"68 yo or older"];
    public function __construct(protected FormComponent $component) {
        if ($this->trace) { echo "Enter ".__METHOD__."<br>"; }
        $this->singlerecord = false;
     }
    public function init($session,$clients=[],$parents="",$trace=false,$clientmembers=[],$clientsessions=[],$volsdata=[],$pagenum='',$tandc_text='') {
        if ($this->trace||$trace) { echo "Enter ".__METHOD__."<br>isadmin = $this->isadmin<br>$this->formname<br>"; }
        parent::init($session,$clients,$parents,$trace);
        $this->config = $this->session->getconfig();
        $this->placenames = $this->config["app"]["PLACENAMES"];
        $this->clientsessions = $clientsessions;
        $this->clientmembers = $clientmembers;
        $this->volunteers = [];
        foreach ($volsdata as $vol) {
            $this->volunteers[$vol["id"]] = $vol["name"];
        }
        $this->pagenum = $pagenum;
        for ($i=1915; $i <= date('Y'); $i++) { 
            $this->years[$i] = $i;
        }
        $this->clientid = $this->requestdata["id"]??"";
        if ($tandc_text !== '') { $this->tandc_text = $tandc_text; }
     }
    protected function initfields($trace = false) { 
        if ($this->trace) { echo "Enter ".__METHOD__."<br>"; }
        $this->fields = array(  "id"=>""
                                ,"given_name"=>""
                                ,"family_name"=>""
                                ,"email"=>""
                                ,"has_read_tandc"=>""
                            );
     }
    public function addlinkstodata(&$clients=[],$clientsessions=[],$trace=false) {
     }    
    protected function addtonames($client){
        // this is in the subclass because the name field will vary by table
        $this->names[$client["id"]] = $client["given_name"]." ".$client["family_name"];
     }                
    protected function addtohidden(){ 
        // add display-only sessions-attended records for each client to hidden fields - called from StdCRUDform
        if ($this->trace) { echo "Enter ".__METHOD__."<br>"; }
        // first create an HTML template for a client member section for the form. 
        // The template will be copied and filled for each of the members in the hidden data as the client is loaded 
        $myhidden = '<div id="membertemplate">'.$this->makeclientmembertemplate().'</div>';
        // Create a templlate for a session attendance
        $myhidden .= '<div id="attendancetemplate">'.$this->makeattendancetemplate().'</div>';
        // add the clientmember data to the hidden divs
        $myhidden .= $this->makeclientmemberhiddendata ();
        // add the clientsession data to the hidden divs
        $myhidden .= $this->makeclientsessionshiddendata ();
        return $myhidden;
     }
    private function makeclientmembertemplate() {
        // the following need replacing in the template with the data for each record from the hidden divs
        // For new records, the ##id is replaced with a neg random number and the others with empty strings 
        // ##id, ##clientmemberid, ##membername, ##relationship, ##mob,  ##yob, ##cob
        // 
        $hiddenidinput = '<input id="child_mem_id##clientmemberid" type="hidden" name="child_mem_id##clientmemberid" value="##clientmemberid">';

        $namelabelcell          = $this->component->rendercell("","Name","","30");
        $relationshiplabelcell  = $this->component->rendercell("","Relationship","","30");
        $doblabelcell           = $this->component->rendercell("","Month and Year of birth","","30");
        // $yoblabelcell           = $this->component->rendercell("","Year of birth","","30");
        $coblabelcell   = $this->component->rendercell("","Country of birth","","30");

        $nameinput          = '<input id="child_mem_nam##clientmemberid" size="30" name="child_mem_nam##clientmemberid" class="vols-form-input" maxlength="64"  value="##membername"  disabled/>';
        $relationshipinput  = '<input id="child_mem_rel##clientmemberid" size="30" name="child_mem_rel##clientmemberid" class="vols-form-input" maxlength="64"  value="##relationship"  disabled/>';
        $mobselect = $this->component->renderdropdown('child_mem_mob##clientmemberid',1,$optionsout,0,0,0,0,$this->months,'',false,'vols-form-select');
        $yobselect = $this->component->renderdropdown('child_mem_yob##clientmemberid',1,$optionsout,0,0,0,0,$this->years,'',false,'vols-form-select');
        $dobselects = $mobselect." ".$yobselect;
        $cobinput   = '<input id="child_mem_cob##clientmemberid" size="30" name="child_mem_cob##clientmemberid" class="vols-form-input" maxlength="64"  value="##cob"  disabled/>';
        $nameinputcell          = $this->component->rendercell("",$nameinput,"","45");
        $relationshipinputcell  = $this->component->rendercell("",$relationshipinput,"","45");
        $dobinputcell           = $this->component->rendercell("",$dobselects,"","45");
        $cobinputcell           = $this->component->rendercell("",$cobinput,"","45");

        $deleteicon = "<div id='delete_mem_id##clientmemberid' class='floatright activeicon trashsvgcontainer childdeleteicon'>{$this->component->geticon("trash")}</div>"; 
        $namehintcell  = $this->component->rendercell("",$deleteicon,"childdeletecell");
        $hintcell  = $this->component->rendercell("","","vols-tablecell hintcell","10");

        $clientmembertemplate = <<<HTML
                <div id="" class="membergroup grouped childcontainer ">
                    {$hiddenidinput}
                    <div id="" class="vols-tablerow ##oddeven membergroup grouped ">{$namelabelcell}{$nameinputcell}{$namehintcell}<div style="clear: both;"></div></div>
                    <div id="" class="vols-tablerow ##oddeven membergroup grouped">{$relationshiplabelcell}{$relationshipinputcell}{$hintcell}<div style="clear: both;"></div></div>
                    <div id="" class="vols-tablerow ##oddeven membergroup grouped">{$doblabelcell}{$dobinputcell}{$hintcell}<div style="clear: both;"></div></div>
                    <div id="" class="vols-tablerow ##oddeven membergroup grouped">{$coblabelcell}{$cobinputcell}{$hintcell}<div style="clear: both;"></div></div>
                </div>
        HTML;
        return $clientmembertemplate;
     }    
     private function makeattendancetemplate() {
        // the following need replacing in the template with the data for each record from the hidden divs
        // For new records, the ##id is replaced with a neg random number and the others with empty strings 
        // ##date, ##taskname
        // 
        $attendance = <<<HTML
            <div class='vols-tablerow sessiongroup attendance grouped ##oddeven'>
                <div class="vols-tablecell attendancecell vols-vertical-center vols-width-30 ">##date</div>
                <div class="vols-tablecell attendancecell vols-vertical-center vols-width-60 ">##taskname</div>
            </div>
        HTML;    
        return $attendance;
     }
    private function makeclientmemberhiddendata () {
        // the hidden data consistes of a div containing the ids of clients who have members
        // and another div containing sets of 5 fields containing the data for each member (id,name,relationship,mob,yob,cob). 
        // in the latter, the fields for a given client are separated by "|" and the groups of fields belonging to different clients are separated with "!!"
        $fd=$this->fielddelimiter;
        $rd=$this->recorddelimiter;
        $memberclientids = '';
        $memberdivs = '';
        $prevclientid = "0";
        // lib::pr($this->alldata,$this->clientmembers); 
        foreach ($this->alldata as $client) {
            foreach ($this->clientmembers as $clientmember) {
                if ($client["id"] == $clientmember["client_id"]) {
                    if ($client["id"] != $prevclientid) { // we have reached new client's member data
                        if ($prevclientid != "0") {
                            $memberdivs .= $rd;
                        }
                        $memberclientids .= $client["id"].$rd;
                        $prevclientid = $client["id"];  
                    }
                    $memberdivs .= $clientmember["id"].$fd.$clientmember["name"].$fd.$clientmember["relationship"].$fd.$clientmember["month_of_birth"].$fd.$clientmember["year_of_birth"].$fd.$clientmember["country_of_birth"].$fd;
                }
            }
        }
        $memberdivs .= $rd;
        // lib::pr($memberclientids,$memberdivs);
        $myhidden  = '<div id="js-memberclientids">'.$memberclientids.'</div>'."\n";
        $myhidden .= '<div id="js-memberdivs">'.$memberdivs.'</div>'."\n";
        return $myhidden;
     }
    private function makeclientsessionshiddendata () {
        $fd=$this->fielddelimiter;
        $rd=$this->recorddelimiter;
        $sessionclientids = '';
        $sessiondata = '';
        $prevclientid = "0";
        foreach ($this->alldata as &$client) {
            $odd = false;
            foreach ($this->clientsessions as $clientsession) {
                if ($client["id"] == $clientsession["client_id"]??0) {
                    if ($client["id"] != $prevclientid) {
                        if ($prevclientid != "0") {
                            $sessiondata .=  $rd;
                        }
                        $sessionclientids .= $client["id"].$fd;
                        $prevclientid = $client["id"];  
                    }
                    $sd = \DateTime::createFromFormat('Y-m-d H:i:s',$clientsession["sessiondate"]);
                    $sessdate = $sd->format('D, j F Y');
                    $sessiondata .= $sessdate.$fd.$clientsession["task"].$fd;
                }
            }
        }
        $sessiondata .=  $rd;
        $myhidden  = '<div id="js-sessionclientids">'.$sessionclientids.'</div>'."\n";
        $myhidden .= '<div id="js-sessiondata">'.$sessiondata.'</div>'."\n";
        return $myhidden;
     }
    protected function makeplacenames() {
        $placenames = "";
        $pnarray = explode("|", $this->placenames);
        sort($pnarray);
        foreach ($pnarray as $place) {
            $placenames .= "<option value='{$place}'>";
        }
        $placenames = "<datalist id='placenames'>".$placenames."</datalist>";
        return $placenames;
     }
    public function buildinputs($rights=[],$trace=false) {
        // lib::pr($this->rights);     
        if ($this->trace) { echo "Enter ".__METHOD__."<br>"; }
        $pagesubheading  = $this->buildpagesubheading(false);
        $given = $this->component->rendertextinput("given_name",15,64,'Given Name',false,'','','vols-form-input',1,true,false,false,1," &nbsp; ","text");
        $family = $this->component->rendertextinput("family_name",20,64,'Family Name',false,'','','vols-form-input',2,true,false,false,1," &nbsp; ","text");
        $formfields = $this->component->renderformrow('','','Given and Family Names',false,'','','',$given.$family); 
        $formfields .= $this->component->buildinputrow("phone",4,'','Phone','phone',15,15,false,0,"",'','',' vols-overflow-show'); 
        $formfields .= $this->component->buildinputrow("email",3,'','Email','email',35,255,false,0,"",'','',' vols-overflow-show'); 

        $formfields .= $this->component->rendersectionheading("Address",inputgroup:"addressgroup");
        $formfields .= $this->component->buildinputrow("address_street",5,'','Street','Street',35,255,true,0,"",'','',' vols-overflow-show'); 
        $formfields .= $this->component->buildinputrow("address_street2",6,'','Street 2','Street 2',15,15,false,0,"",'','',' vols-overflow-show'); 
        $placenames = $this->makeplacenames();
        $formfields .= $this->component->buildinputrow("address_townsuburb",7,'','Town/Suburb','Town/Suburb',20,64,true,0,"",listname:"placenames"); 
        $formfields .= $placenames;
        $formfields .= $this->component->buildselectrow("address_state",8,1,'State',$this->states,'',$optionsout);
        $formfields .= $this->component->buildinputrow("address_postcode",9,'','Postcode','Postcode',15,15,true,0,"",'','',' vols-overflow-show'); 

        $formfields .= $this->component->rendersectionheading("General",inputgroup:"generalgroup");
        $genderbuttons = $this->component->renderradiobuttons('gdr',$this->genders,0,'',999,true);
        $formfields .= $this->component->renderformrow('','','Gender',false,'','','',$genderbuttons); 
        $formfields .= '<input type="hidden" id="gender" name="gender" data-fnum="11" />';

        $mobselect =  $this->component->renderdropdown('month_of_birth',1,$optionsout,false,false,true,false,$this->months,'',false,'vols-form-select','',$trace,12);
        $yobselect =  $this->component->renderdropdown('year_of_birth',1,$optionsout,false,false,true,false,$this->years,'',false,'vols-form-select','',$trace,13);
        $selects = $mobselect." ".$yobselect;
        $formfields .= $this->component->renderformrow('','','Month and Year of Birth',false,'','','',$selects); 
        $formfields .= $this->component->buildselectrow('residence',10,1,'Residence',$this->residence,'',$optionsout);
        $formfields .= $this->component->buildcheckboxrow("concession_card",1,"",false,20,"Concession card");
        $formfields .= $this->component->buildcheckboxrow("interpreter",1,"",false,14,"Interpreter required");
        $formfields .= $this->component->buildinputrow("language",15,"",'Language','Language',20,64,false,0,""); 
        $formfields .= $this->component->buildinputrow("country_of_birth",16,"",'Country of Birth','Country of Birth',20,64,false,0,""); 
        $formfields .= $this->component->buildcheckboxrow("aborigine_TSislander",1,"",false,17,"Aboriginal or Torres Strait Islander");
        // //=====================================================================================================
        $representbuttons = $this->component->renderradiobuttons('rep',$this->representation,0,'',999,false);
        $formfields .= $this->component->renderformrow('','','Represented by',false,'','','',$representbuttons); 
        $formfields .= '<input type="hidden" id="represented_by" name="represented_by" data-fnum="18" />';
        // $formfields .= $this->component->buildcheckboxrow("represented_by",0,"",false,12,"May/will be collected by Carer");
        $formfields .= $this->component->buildinputrow("carer_name",19,"",'Carer Name','Carer Name',14,64,false,0,""); 
        //=====================================================================================================
        $formfields .= $this->component->rendersectionheading("Household members",inputgroup:'membergroup',addid:"clientmember");
        $formfields .= '<div id="members"></div>';
        //=====================================================================================================
        $formfields .= $this->component->rendersectionheading("Comments",inputgroup:"commentsgroup");
        $formfields .= $this->component->buildtextarearow("comments",22,"","Other comments","",40,3,4096); 
        $formfields .= $this->component->buildtextarearow("office_comments",23,"","Office comments","",40,3,4096);
        //=====================================================================================================
        $formfields .= $this->component->rendersectionheading("Terms &amp; Conditions");
        $formfields .= '<div class="vols-tandc-statement">' . $this->tandc_text . '</div>';
        $formfields .= '<input type="hidden" name="has_read_tandc" value="0">';
        $formfields .= $this->component->buildcheckboxrow("has_read_tandc","1","",false,24,"I have read and accept the above statement",'',false,false,false,false);
        $formfields .= '<div class="vols-tandc-sig-section">';
        $formfields .= '<div class="vols-tandc-sig-label">Signature:</div>';
        $formfields .= '<canvas id="sig-canvas" class="vols-tandc-canvas" width="500" height="150"></canvas>';
        $formfields .= '<button type="button" id="sig-clear-btn" class="vols-tandc-clear-btn" disabled>Clear</button>';
        $formfields .= '</div>';
        $formfields .= '<input type="hidden" id="tandc_signature" name="tandc_signature" value="">';
        //=====================================================================================================
        $formfields .= $this->sessionsattended(); // exists in the subclass
        $this->preparecommontop(selecttext:$this->clientid,pagesubheading:$pagesubheading);
        // in this form, vols may perform data entry under a common login. In order to populate the client's registered_by and modified_by fields
        // we have to collect these data from the vol doing the work (via a select in the common top section), and upload them as data within the form
        $user_id = $this->session->getuserid();
        $this->hiddeninputs  = '<input id="registered_by" type="hidden" name="registered_by" value="'.$user_id.'">';
        $this->hiddeninputs .= '<input id="modified_by" type="hidden" name="modified_by"  value="'.$user_id.'">';
        return $formfields;
     }
    protected function newclickscript() {
        // in the case of a ClientVolsForm implementation there is subclass version of this method that
        // calls this method as a parent:: call
        $script = <<<JS
                // reduce the number of children to 1 if any are present
                while (jQuery("#dataspace .childcontainer").length > 1) {
                    jQuery("#dataspace .childcontainer").first().remove();
                }
                // default the state to VIC
                jQuery("#address_state").val("VIC").change();
                jQuery("div.attendancecontainer").remove();

        JS;
        return $script;
     }
    public function formscript() {
        $as = $this->attendancescript; // defined in the subclass
        $script  = <<<JS
                function postajaxscript(){}
                function postloadfieldsscript(selectedid){
                    const fd = "{$this->fielddelimiter}";
                    const rd = "{$this->recorddelimiter}";
                    // we have fields in the CLIENT table that require check boxes and radio buttons
                    // we need to transfer values between these fields and the form elements
                    // gender is an ENUM represented by radio buttons with the ENUM vals as buttons' VALUE atributes
                    const gender = jQuery("#gender").val();  // loaded from the hidden field
                    jQuery("#gdr"+gender).prop('checked',true).trigger("click");
                    const rb = jQuery("#represented_by").val();
                    jQuery("#rep"+rb).prop('checked',true).trigger("click");

                    const jsessionids       = makearray("#js-sessionids",rd) ;
                    const jbookingactions   = makearray("#js-bookingactions",rd); 
                    const jhistindex = jsessionids.indexOf(selectedid);
                    jQuery("#history").html(jhistindex == -1? "" : jbookingactions[jhistindex]);

                    // now populate clientmember inputs from the template using data from the hidden fields
                    // first, clear old member structures
                    jQuery("div.attendancecontainer").remove();
                    jQuery("#dataspace .childcontainer").remove();
                    const memberparentids     = makearray("#js-memberclientids",rd) 
                    let clientindex = memberparentids.indexOf(selectedid) // find selectedid in jmemberparentclientids[] and we have the index to all arrays
                    if (clientindex != -1) {// the client has members
                        const allmemberfields  = makearray("#js-memberdivs",rd) 
                        if (Array.isArray(allmemberfields) && allmemberfields.length) {
                            const thismemberfieldsstr = allmemberfields[clientindex]; 
                            if (thismemberfieldsstr != "") { 
                                // thismemberfieldsstr contains all fields for the selected object - we convert this to an array
                                const memberarray =  thismemberfieldsstr.split(fd);
                                let isodd = true;
                                for (i=0;i < memberarray.length-1;) { 
                                    // memberarray will contain a multiple of 6 elements. Each block of six represents one clientmember
                                    // for each clientmember, manifest a template and populate all fields by replaceing ##placeholders
                                    // each loop of this advances i by +6 
                                    let memberdivs = jQuery("#membertemplate").html();
                                    memberdivs = memberdivs .replaceAll("##clientmemberid",memberarray[i++])
                                                            .replaceAll("##membername",memberarray[i++])
                                                            .replaceAll("##relationship",memberarray[i++])
                                                            .replaceAll('value="'+memberarray[i]+'">','value="'+memberarray[i++]+'" selected>') //mob
                                                            .replaceAll('value="'+memberarray[i]+'">','value="'+memberarray[i++]+'" selected>') //yob
                                                            .replaceAll("##cob",memberarray[i++])   
                                                            .replaceAll("##clientid",selectedid)
                                                            .replaceAll("##oddeven",(isodd?" vols-row-odd":" vols-row-even"));
                                   isodd = !isodd;
                                   jQuery(".vols-tablerow:has('#clientmember')").after(memberdivs);
                                }
                            }
                        }
                    }
                    jQuery("#editarea div.childcontainer div.childdeleteicon").off().on("click",function(event){
                        deletechild(jQuery(this),event);  
                    });
                    jQuery("#address_state").val("VIC").change();
                    {$as}
                    sigclear();
                    doServerRequest(0, JSON.stringify({client_id: selectedid}), 'client_getsignature')
                        .then(function(base64) { sigload(base64); });
                }
                function postclearfieldsscript(){
                    jQuery(".vols-tablecell input[type='checkbox']").prop("checked",false);
                    jQuery(".vols-tablecell input[type='radio'][name='gdr']").each(function () { $(this).prop('checked', false); });
                    jQuery("#address_state").val("VIC").change();
                    sigclear();
                }
                function presavescript(){
                    const g = jQuery("input[type='radio'][name='gdr']:checked").val() ;
                    jQuery("#gender").val(g);
                    const c = jQuery("input[type='radio'][name='rep']:checked").val();
                    jQuery('#tandc_signature').val(sigisempty() ? '' : sigCanvas.toDataURL('image/png'));
                }
                function disablescript(){ sigenable(false); }
                function onloadscript(){
                     jQuery("#address_townsuburb").on("change",function(event){
                        let pc;
                        switch ($(this).val()) {
                            case "Clarkefield": pc = "3430";break;
                            case "Riddells Creek": pc = "3431";break;
                            case "Bolinda": pc = "3432";break;
                            case "Monegeetta": pc = "3433";break;
                            case "Cherokee": pc = "3434";break;
                            case "Kerrie": pc = "3434";break;
                            case "Romsey": pc = "3434";break;
                            case "Springfield": pc = "3434";break;
                            case "Benloch": pc = "3435";break;
                            case "Goldie": pc = "3435";break;
                            case "Lancefield": pc = "3435";break;
                            case "Nulla Vale": pc = "3435";break;
                            case "Bullengarook": pc = "3437";break;
                            case "Gisborne": pc = "3437";break;
                            case "Gisborne South": pc = "3437";break;
                            case "New Gisborne": pc = "3438";break;
                            case "Macedon": pc = "3440";break;
                            case "Mount Macedon": pc = "3441";break;
                            case "Ashbourne": pc = "3442";break;
                            case "Cadello": pc = "3442";break;
                            case "Carlsruhe": pc = "3442";break;
                            case "Cobaw": pc = "3442";break;
                            case "Hesket": pc = "3442";break;
                            case "Newham": pc = "3442";break;
                            case "Rochford": pc = "3442";break;
                            case "Woodend": pc = "3442";break;
                            case "Woodend North": pc = "3442";break;
                            case "Barfold": pc = "3444";break;
                            case "Baynton": pc = "3444";break;
                            case "Baynton East": pc = "3444";break;
                            case "Edgecombe": pc = "3444";break;
                            case "Fern Hill": pc = "3458";break;
                            case "Glenhope": pc = "3444";break;
                            case "Greenhill": pc = "3444";break;
                            case "Kyneton": pc = "3444";break;
                            case "Kyneton South": pc = "3444";break;
                            case "Langley": pc = "3444";break;
                            case "Lauriston": pc = "3444";break;
                            case "Lyal": pc = "3444";break;
                            case "Metcalfe East": pc = "3444";break;
                            case "Pastoria": pc = "3444";break;
                            case "Pastoria East": pc = "3444";break;
                            case "Pipers Creek": pc = "3444";break;
                            case "Sidonia": pc = "3444";break;
                            case "Spring Hill": pc = "3444";break;
                            case "Trentham": pc = "3458";break;
                            case "Trentham East": pc = "3458";break;
                            case "Tylden": pc = "3444";break;
                            case "Tylden South": pc = "3444";break;
                            case "Drummond North": pc = "3446";break;
                            case "Malmsbury": pc = "3446";break;
                            case "Darraweit Guim": pc = "3756";break;
                            default: 
                        }
                        jQuery("#address_postcode").val(pc);
                     });                   
                    if (jQuery("#volunteerselection").length){
                        jQuery("#volunteerselection").on("change",function(event){
                            if (jQuery(this).val() != 0 ) {
                                disactivateactionbuttons(0,0,1,1,0,1);
                            } else {
                                disactivateactionbuttons(1,1,1,1,1,1);
                                disableallinputstatus(true);
                            } 
                        });
                        jQuery("#volunteerselection").trigger("change");
                    } else {
                        disactivateactionbuttons(0,0,1,1,0,1);
                    }                }
         JS;
        $script .= $this->vols_masterscript($this->formname, 
                                    $this->objname, //$objectname
                                    true, //$idselection=
                                    true,  //$adjustnamerow=
                                    true, //$updatefields=
                                    false, //$inclmulti=
                                    // '',  //$postajaxscript=
                                    // '', //$postloadfieldsscript,  //$postloadfieldsscript=
                                    // '',//$postclearfieldsscript, //$postclearfieldsscript=
                                    // false, //$trace=
                                    // '',  //$multisubmit
                                    // '', //$presavescript,
                                    // '', // disablescript
                                    // '', //$onloadscript
                                    ); 
        $script .= <<<'JS'

            // ---- Signature pad ----
            var sigCanvas, sigCtx, sigIsDrawing = false, sigEnabled = false;
            function siginit() {
                sigCanvas = document.getElementById('sig-canvas');
                if (!sigCanvas) return;
                sigCtx = sigCanvas.getContext('2d');
                sigCtx.strokeStyle = '#000';
                sigCtx.lineWidth   = 2;
                sigCtx.lineCap     = 'round';
                sigCtx.lineJoin    = 'round';
                sigCanvas.addEventListener('pointerdown', function(e) {
                    if (!sigEnabled) return;
                    sigIsDrawing = true;
                    var p = sigpos(e);
                    sigCtx.beginPath();
                    sigCtx.moveTo(p.x, p.y);
                    e.preventDefault();
                });
                sigCanvas.addEventListener('pointermove', function(e) {
                    if (!sigIsDrawing || !sigEnabled) return;
                    var p = sigpos(e);
                    sigCtx.lineTo(p.x, p.y);
                    sigCtx.stroke();
                    e.preventDefault();
                });
                sigCanvas.addEventListener('pointerup',    function() { sigIsDrawing = false; });
                sigCanvas.addEventListener('pointerleave', function() { sigIsDrawing = false; });
            }
            function sigpos(e) {
                var r = sigCanvas.getBoundingClientRect();
                return {
                    x: (e.clientX - r.left) * (sigCanvas.width  / r.width),
                    y: (e.clientY - r.top)  * (sigCanvas.height / r.height)
                };
            }
            function sigisempty() {
                if (!sigCtx) return true;
                var d = sigCtx.getImageData(0, 0, sigCanvas.width, sigCanvas.height).data;
                for (var i = 3; i < d.length; i += 4) { if (d[i] !== 0) return false; }
                return true;
            }
            function sigclear() {
                if (sigCtx) sigCtx.clearRect(0, 0, sigCanvas.width, sigCanvas.height);
                jQuery('#tandc_signature').val('');
            }
            function sigenable(on) {
                sigEnabled = on;
                if (sigCanvas) sigCanvas.style.cursor = on ? 'crosshair' : 'default';
                jQuery('#sig-clear-btn').prop('disabled', !on);
            }
            function sigload(base64) {
                if (!sigCtx || !sigCanvas) return;
                sigCtx.clearRect(0, 0, sigCanvas.width, sigCanvas.height);
                if (base64) {
                    var img = new Image();
                    img.onload = function() { sigCtx.drawImage(img, 0, 0); };
                    img.src = base64;
                }
                jQuery('#tandc_signature').val(base64 || '');
            }
            jQuery(document).ready(function() {
                siginit();
                sigenable(false);
                jQuery(document).on('click', '#editrecord', function() { sigenable(true); });
                jQuery(document).on('click', '#newrecord',  function() { sigclear(); sigenable(true); });
                jQuery(document).on('click', '#sig-clear-btn', function() { sigclear(); });
            });
            // ---- End signature pad ----

JS;
        $script .= <<<JS
            function showhidepages() {
                setchildselectorheadingtext();
                const element = document.getElementById("dataspace");
                element.scrollTop = element.scrollHeight;
            }
            function deletechild(target,event){
                memid = target.prop("id").substring(13).toString();
                container = jQuery(event.target).closest(".childcontainer");
                container.find("#child_mem_nam"+memid).val("");
                container.find("#child_mem_rel"+memid).val("");
                container.find("#child_mem_mob"+memid).val("");
                container.find("#child_mem_yob"+memid).val("");
                container.find("#child_mem_cob"+memid).val("");
                container.addClass("hide content_hidden");
            }
           function addtogroup(task) {
                const target = jQuery(task.currentTarget);
                const groupname = target.prop("id");
                if (groupname == "clientmember") {
                    let newmember = jQuery("#membertemplate").html();
                    const randid = -1*getRandomInt();
                    const randidstr = randid.toString();
                    newmember = newmember.replaceAll("##clientmemberid",randidstr).replaceAll("##clientmemberid","").replaceAll("##membername","").replaceAll("##relationship","").replaceAll("##mob","").replaceAll("##yob","").replaceAll("##cob","");    
                    target.closest(".vols-tablerow").after(newmember);
                    if (target.prev(".groupsvgcontainer").hasClass("collapsed")) {
                        target.prev(".groupsvgcontainer").trigger("click");
                    }
                }
            }
            function onblur(elem_id) {
                elem = jQuery('"#'+elem_id+'"');
                elem.on('blur', () => {
                    if (!elem.value && elem.arg("required")) {
                        jQuery('"#'+elem_id+'row_error"').html("This field is required.");
                    } else if (elem.value && elem.prop("data-validitytest") && ((errortext = isvalid(elem_id)) !== "")) {
                        jQuery('"#'+elem_id+'row_error"').html(errortext);
                    } else {
                        jQuery('"#'+elem_id+'row_error"').html("");
                    }
                });

            }
            function isvalid(elem_id) {
                const elem = jQuery('"#'+elem_id+'"');
                const contents = elem.val()
                switch (elem_id) {
                    case ("other_diet") :
                        if (jQuery) {

                        }
                    default:
                        return "";
                }
            }
            function loaddataintoform (recordnum) {
                let recdata = getdata();
                jQuery("#hiddenid").val(recdata[0]);
            }
            function formhaserrors() {
                let errors = 0;
                jQuery("input:required,textarea:required,select:required").each(function () {
                    // for empty required inputs we highlight the imput and post an error in the error div below them 
                    const inputrow = jQuery(this).closest(".vols-tablerow");
                    const id = "#"+inputrow.prop("id");
                    const errorrow = inputrow.next();
                    if (jQuery(this).val() == "") {
                        jQuery(this).parent().addClass("vols-lookatme");
                        jQuery(id+"_errorprompt").html("Error");
                        jQuery(id+"_error").html("This is a required field");
                        errorrow.addClass("errorshowing");
                        errors++;
                    } else {
                        jQuery(this).parent().removeClass("vols-lookatme");
                        jQuery(id+"_errorprompt",id+"_error").html("");
                        errorrow.removeClass("errorshowing");
                    }
                })
                return errors;
            }
            function displayselectedrecord() {
            }
         JS;
        return $script;
     }
}
