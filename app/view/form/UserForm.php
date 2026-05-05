<?php
namespace app\view\form;
use \lib\StdLib as lib;
class UserForm extends \fw\view\form\StdCRUDForm {
    protected $trace= false;                
    protected $promptwidth = 40;
    protected $inputwidth = 35;
    protected $hintwidth = 25;
    protected $fields = [];
    protected $formname = "userform";
    protected $objname = "User";
    protected $parentname = "";
    protected $parentobj = "";
    protected $secondselectorname = "childselector";
    protected $pagenum;
    protected $names;
    protected $parents;
    protected $usermgr;
    protected $rights;
    protected $roles;
    protected $userroles;
    protected $userid;
    public function __construct(protected FormComponent $component) {
        if ($this->trace) { echo "Enter ".__METHOD__."<br>"; }
        $this->singlerecord = false;
     }
    public function init($session,$users=[],$parents="",$trace=false,$roles=[],$userroles=[],$pagenum='') {
        if ($this->trace||$trace) { echo "Enter ".__METHOD__."<br>"; }
        if (count($userroles)) {  // add the role_ids for the user's roles in a new element in each $user array
            $this->addlinkstodata($users,$roles,$userroles,$trace);
        }
        parent::init($session,$users,$parents,$trace);
        $this->usermgr = $this->session->usermanager();
        $this->rights = $this->usermgr->getuserrights();
        $this->roles = $roles;
        $this->userroles = $userroles;
        $this->pagenum = $pagenum;
        $this->userid = $this->requestdata["id"]??"";
     }
    public function addlinkstodata(&$users=[],$roles=[],$userroles=[],$trace=false) {
        foreach ($users as &$user) {
            foreach ($roles as $role) {
                $ur=0;
                foreach ($userroles as $userrole) {
                    if ($user["id"] === $userrole["user_id"] && $role["id"] === $userrole["role_id"] ) {
                        $ur = 1 ;
                        break;
                    }
                }
                $user["role".$role["id"]] = $ur;
            }
        }
     }    
    public function initfields($trace=false) {
        if ($this->trace) { echo "Enter ".__METHOD__."<br>"; }
        $this->fields = array(  "id"=>""
                                ,"given_name"=>""
                                ,"family_name"=>""
                                ,"display_name"=>""
                                ,"email"=>""
                                ,"mobile"=>""
                                ,"username"=>""
                                ,"menu_number"=>""
                            );
     }
    protected function addtonames($user){
        // this is in the subclass because the name field will vary by table
        $this->names[$user["id"]] = $user["given_name"]." ".$user["family_name"];
     }                
    public function buildinputs($rights=[],$trace=false) {
        // lib::pr($this->rights);     
        if ($this->trace) { echo "Enter ".__METHOD__."<br>"; }
        $formfields = $this->component->buildinputrow("given_name",1,$this->fields["given_name"],'Given Name','Given Name',20,64,true); 
        $formfields .= $this->component->buildinputrow("family_name",2,$this->fields["family_name"],'Family Name','Family Name',20,64,true); 
        $formfields .= $this->component->rendersectionheading("General",inputgroup:"generalgroup");
        $formfields .= $this->component->buildinputrow("display_name",3,$this->fields["display_name"],'Display Name','Display Name',20,64,false,0,"If not supplied, the Given Name will be used."); 
        $formfields .= $this->component->buildinputrow("email",4,$this->fields["email"],'Email','email',35,255,true,0,"",'','',' vols-overflow-show', type:'email'); 
        $formfields .= $this->component->buildinputrow("mobile",5,$this->fields["mobile"],'Mobile/Phone','mobile',20,16); 
        if (!$this->singlerecord) {
            $formfields .= $this->component->buildinputrow("username",6,$this->fields["username"],'Username','username',20,64,false,0,"It is strongly advised not to change this once it is in use.");
            $formfields .= $this->component->buildinputrow("menu_number",7,$this->fields["menu_number"],'Menu Number','Menu number',3,3,false,0,"Use '0' for standard users. '1' is used for session attendance logging volunteers.");
            $this->component->setwidths(40, 10, 50);
            $formfields .= $this->component->rendersectionheading("Notifications");
            $formfields .= '<input type="hidden" name="receives_stock_alerts" value="0">';
            $formfields .= $this->component->buildcheckboxrow("receives_stock_alerts","1","",false,7,"Receives stock alerts","Receives email alerts when stock falls below minimum levels.",false,false,false,false);
        }
        $hiddencheckboxes = '';
        $this->component->setwidths(40, 55, 5);
        if (!$this->singlerecord) {
            if ($this->isadmin || in_array($this->pagenum."||ROLES",$rights)) {
                $fn = 8;
// =============================================================================================
                $buttons = ["rightid"=>"showrowsbtn","righttext"=>'Show <span style="text-decoration: underline;">L</span>INKED',"rightscript"=>"","rightdata"=>" data-state='all'","leftid"=>"","lefttext"=>"","leftscript"=>""];
                $heading = "<span id='statustextspan'>ALL</span> Roles";
                $formfields .= $this->component->rendersectionheading($heading,buttons:$buttons);
                $this->component->setwidths (40,55,5);
                foreach ($this->roles as $id=>$role) {
                    $rolename = "link_role".$role["id"];
                    $formfields .= $this->component->buildcheckboxrow($rolename,$role["id"],"",true,$fn++,$role["name"],'',false,false,false,false);
                    $hiddencheckboxes .= '<input type="hidden" name="'.$rolename.'"  value=false />';
                }
            }
        } else {
            $formfields .= $this->component->rendersectionheading("Make changes and click Save, or just choose another menu option.","vols-form-headingmessage", containerclass:"vol-form-sectionheadingcontainer");
        }
        $this->preparecommontop(selecttext:$this->userid,hiddeninputs:$hiddencheckboxes);
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
                    showhidepages();
        JS;
          $postclearfieldsscript = <<<SCRIPT
                    jQuery("input[type='checkbox']").prop("checked",false);
                    $('input:radio[name="mb"]').each(function () { $(this).prop('checked', false); });
        SCRIPT;
        $presavescript = <<<SCRIPT
                    // see $ postupdatescript above
                    // we have to read the values from the checkboxes and radiobuttons 
                    // to reconstruct the user's 'available' and 'message_by' fields 
                    // first combine the 7 checkboxes into an integer
                    jQuery("#formerror").html("") ;
                    const cbval=$("#sun").is(":checked")+
                                (2*$("#mon").is(":checked"))+
                                (4*$("#tue").is(":checked"))+
                                (8*$("#wed").is(":checked"))+
                                (16*$("#thu").is(":checked"))+
                                (32*$("#fri").is(":checked"))+
                                (64*$("#sat").is(":checked"));
                    jQuery("#available").val(cbval);

                    // now recover the index from the 'checked' radio to 
                    // determine the appropriate value for 'message_by'
                    const options=  ["","SMS","EMAIL","PHONE"] ;// values are 1..3 so we need a blank array[0] to fill the hole
                    let radioindex = jQuery("input[type=radio][name='mb']:checked").val();
                    radioval = options[radioindex];
                    jQuery("#message_by").val(radioval);

        SCRIPT;
        $script  = $this->vols_masterscript($this->formname, 
                                    $this->objname, //$objectname
                                    true, //$idselection=
                                    true,  //$adjustnamerow=
                                    true, //$updatefields=
                                    false, //$inclmulti=
                                    '',  //$postajaxscript=
                                    $postloadfieldsscript,  //$postloadfieldsscript=
                                    $postclearfieldsscript, //$postclearfieldsscript=
                                    false, //$trace=
                                    '',  //$multisubmit
                                    $presavescript
                                    ); 
        $script .= <<<JS
            function showhidepages() { 
                setchildselectorheadingtext();
                const element = document.getElementById("dataspace");
                element.scrollTop = element.scrollHeight;
            }
            function loaddataintoform (recordnum) {
                let recdata = getdata();
                jQuery("#hiddenid").val(recdata[0]);
                jQuery("#name").val(recdata[1]);
            }
            function formhaserrors() {
                let errors = 0;
                if (!jQuery("#given_name").val() || !jQuery("#family_name").val() || !jQuery("#email").val() ){ 
                    jQuery("#namerow_error").html("(This is a required field.)");
                    errors++;
                }
                return errors;
            }
            function displayselectedrecord() {
            }
            function getchildnames() { return ["role","Roles"]}
         JS;
        return $script;
     }
}
