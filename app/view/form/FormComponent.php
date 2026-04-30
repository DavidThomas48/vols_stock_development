<?php
namespace app\view\form;
use \lib\StdLib as lib;
class FormComponent  extends \fw\view\form\FormComponent{
    protected $trace = false;
    protected $radioid = 0;
    protected $mustbenewrecord;
    protected $singlerecord;
    protected $session;
    protected $isadmin;
    protected $objectname;
    protected $recordname;
    protected $odd = false;
    protected $processed; // record the fact that this form was processed in case errors occur and error messages have to be re-rendered
    protected $pagenum;
    private   $headingoverride = '';

    public function setheadingoverride(string $text): void {
        $this->headingoverride = $text;
    }
    protected $formname;
    protected $objname;
    protected $rights;
    protected $holdpromptwidth;
    protected $holdinputwidth;
    protected $holdhintwidth;
    protected $red_asterisk = '<div class="vols-form-field-required">*</div>';
    protected $inputgroup = "";
    public $collapseicon = '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24"><path d="M19,3H5A2,2 0 0,0 3,5V19C3,20.11 3.9,21 5,21H19C20.11,21 21,20.11 21,19V5A2,2 0 0,0 19,3M19,19H5V5H19V19M16.59,15.71L12,11.12L7.41,15.71L6,14.29L12,8.29L18,14.29L16.59,15.71Z" /></svg>';
    public $expandicon   = '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24"><path d="M19,3H5A2,2 0 0,0 3,5V19C3,20.11 3.9,21 5,21H19C20.11,21 21,20.11 21,19V5A2,2 0 0,0 19,3M19,19H5V5H19V19M7.41,8.29L12,12.88L16.59,8.29L18,9.71L12,15.71L6,9.71L7.41,8.29Z" /></svg>';
    public $addicon      = '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24"><path d="M19,19V5H5V19H19M19,3A2,2 0 0,1 21,5V19A2,2 0 0,1 19,21H5A2,2 0 0,1 3,19V5C3,3.89 3.9,3 5,3H19M11,7H13V11H17V13H13V17H11V13H7V11H11V7Z" /></svg>';
    public $trashicon   = '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 22 22"><path d="M9,3V4H4V6H5V19A2,2 0 0,0 7,21H17A2,2 0 0,0 19,19V6H20V4H15V3H9M7,6H17V19H7V6M9,8V17H11V8H9M13,8V17H15V8H13Z" /></sv8CG5180P95 g>';
    public $bookinghistoryicon   = '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24"><path d="M4,2A2,2 0 0,0 2,4V14H4V4H14V2H4M8,6A2,2 0 0,0 6,8V18H8V8H18V6H8M20,12V20H12V12H20M20,10H12A2,2 0 0,0 10,12V20A2,2 0 0,0 12,22H20A2,2 0 0,0 22,20V12A2,2 0 0,0 20,10M19,17H17V19H15V17H13V15H15V13H17V15H19V17Z" /></svg>';
    public $righticon = '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24"><path d="M19,3H5A2,2 0 0,0 3,5V19C3,20.11 3.9,21 5,21H19C20.11,21 21,20.11 21,19V5A2,2 0 0,0 19,3M19,19H5V5H19V19M8.29,16.59L12.88,12L8.29,7.41L9.71,6L15.71,12L9.71,18L8.29,16.59Z" /></svg>';
    public $doublerighticon   = '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24"><path d="M19,3 H5A2, 2 0 0, 0 3,5 V19A2, 2 0 0, 0 5,21H19A2, 2 0 0, 0 21,19V5A2,2 0 0,0 19,3M19,19H5V5H19V19M6.29,7.41L10.88,12L6.29,16.59L7.71,18L13.71,12 L7.71,6L6.29,7.4ZM10.29,7.41L14.88,12L10.29,16.59L11.71,18 L17.71,12 L11.71,6 L10.29,7.41 Z"/></svg>';
    public $lefticon      = '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24"><path d="M19,3H5A2,2 0 0,0 3,5V19A2, 2 0 0, 0 5,21H19A2, 2 0 0, 0 21,19V5A2,2 0 0,0 19,3M19,19H5V5H19V19M15.71,7.41L11.12,12L15.71,16.59L14.29,18L8.29,12L14.29,6L15.71,7.41Z" /></svg>';
    public $doublelefticon   = '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24"><path d="M19,3 H5A2, 2 0 0, 0 3,5 V19A2, 2 0 0, 0 5,21H19A2, 2 0 0, 0 21,19V5A2,2 0 0,0 19,3M19,19H5V5H19V19M17.71,7.41L13.12,12L17.71,16.59L16.29,18L10.29,12L16.29,6L17.71,7.41ZM13.71,7.41L9.12,12L13.71,16.59L12.29,18L6.29,12L12.29,6L13.71,7.41Z" /></svg>';
    public $printericon   = '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24"><path d="M19 8C20.66 8 22 9.34 22 11V17H18V21H6V17H2V11C2 9.34 3.34 8 5 8H6V3H18V8H19M8 5V8H16V5H8M16 19V15H8V19H16M18 15H20V11C20 10.45 19.55 10 19 10H5C4.45 10 4 10.45 4 11V15H6V13H18V15M19 11.5C19 12.05 18.55 12.5 18 12.5C17.45 12.5 17 12.05 17 11.5C17 10.95 17.45 10.5 18 10.5C18.55 10.5 19 10.95 19 11.5Z" /></svg>';
    public $todayicon   = '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 49 24"><path d="M44,3 H5 A2, 2 0 0, 0 3,5 V19A2, 2 0 0, 0 5,21H44A2, 2 0 0, 0 46,19V5A2,2 0 0,0 44,3 M44,19H5V5H44V19"/><text x="25" y="16" text-anchor="middle" font-family="Arial, sans-serif" font-size="12" font-weight="700"  fill="black">today</text></svg>';
    public $manicon   = '<svg version="1.1" xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" x="0px" y="0px" width="24px" height="24px" viewBox="0 0 60 60" ><g><circle fill="#0E0F0F" cx="32.912" cy="9.734" r="4.507"/><path fill="#0E0F0F" d="M34.482,16.39c-1.123-0.228-2.344-0.218-3.447,0.042c-7.493,0.878-9.926,9.551-9.239,16.164 c0.298,2.859,4.805,2.889,4.504,0c-0.25-2.41-0.143-6.047,1.138-8.632c0, 3.142,0,6.284,0, 9.425c0,0.111,0.011,0.215,0.016,0.322 c-0.003, 0.051-0.015,0.094-0.015,0.146c0,7.479-0.013,14.955-0.322,22.428c-0.137,3.322,5.014,3.309,5.15,0 c0.242-5.857,0.303-11.717,0.317-17.578c0.244, 0.016,0.488,0.016,0.732, 0.002c0.015,5.861,0.074,11.721,0.314,17.576 c0.137,3.309,5.288,3.322,5.15,0c-0.309-7.473-0.32-14.949-0.32-22.428c0-0.232-0.031-0.443-0.078-0.646 c-0.007-3.247-0.131-6.497-0.093-9.742c1.534,2.597,1.674,6.558,1.408,9.125c-0.302,2.887,4.206,2.858,4.504,0 C44.904,25.844,42.354,16.946,34.482,16.39z"/></g></svg>';
    public $womanicon = '<svg version="1.1"  xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" x="0px" y="0px" width="24px" height="24px" viewBox="0 0 15 15" xml:space="preserve"><path style="fill:#010002;" d="M9.167,1.253 c0,0.692 -0.561,1.251 -1.252,1.251 S6.663,1.945,6.663,1.253S7.224,0,7.915,0 S9.167,0.561,9.167,1.253z M10.82,7.691c0,0-0.657-3.193-0.97-3.834V3.855c0,0-0.001,0-0.001-0.002 c-0.312-0.64-0.625-1.025-1.812-1.025H7.915H7.792c-1.187,0-1.499,0.386-1.812,1.025c0,0.002-0.001,0.002-0.001,0.002v0.002 C5.667,4.498,5.01,7.691,5.01,7.691c-0.049,0.215,0.085,0.43,0.301,0.479c0.03,0.008,0.061,0.012,0.09,0.012c0.183,0,0.348-0.127,0.391-0.311L6.68,4.712l-0.863,5.28h0.832L6.907,14.7c0,0.165,0.135,0.3,0.297,0.3 c0.17,0,0.304-0.135,0.304-0.3l0.256-4.708h0.151h0.151L8.322,14.7c0,0.165,0.134,0.3,0.304,0.3c0.162,0,0.297-0.135,0.297-0.3 l0.259-4.708h0.831L9.15,4.712l0.889,3.159C10.082,8.055,10.247,8.182,10.43,8.182c0.029,0,0.06-0.004,0.09-0.012   C10.735,8.121,10.869,7.906,10.82,7.691z "/> </svg>';
    public $houseicon = '<svg version="1.1" xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" x="0px" y="0px" width="24px" height="24px" viewBox="0 0 15 13.125" style="enable-background:new 0 0 15 13.125;" xml:space="preserve"><polygon style="fill:#010002;" points="1.875,13.125 6.562,13.125 6.562,9.375 8.438,9.375 8.438,13.125 13.125,13.125 13.125,7.5   15,7.5 7.5,0 0,7.5 1.875,7.5 "/> </svg>';
    public $shoppericon = '<svg version="1.1"  xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" x="0px" y="0px" width="24px" height="24px" viewBox="0 0 372 491.797" xml:space="preserve"> <path d="M196,81.18c12,1,22.333-2.167,31-9.5c8.667-7.335,13.5-16.668,14.5-28c1-11.335-2.333-21.168-10-29.5 c-7.668-8.333-17.334-13-29-14c-11.667-1-22,2.167-31,9.5s-14,16.667-15,28s2.333,21.167,10,29.5S184,80.18,196,81.18z"/> <path d="M372,409.68l-14-123h-23c0-7.332-0.333-13.332-1-18c-0.667-6.666-2.667-10.666-6-12c3.333-3.332,5-7,5-11 c0.667-6.667-1.667-12-7-16l-59-38l-43-82c-8.667-12-20-18.667-34-20c-10.667-0.667-20.667,2-30,8l-86,67c-2.667,2-4.667,4.667-6,8 l-20,81l-0.5,1l-0.5,1h-1c-3.333,0-5.667,2.668-7,8c-2,6-3,10.668-3,14v9H14l-14,123h57l-25,40c-2,4.667-3.333,8.667-4,12 c-0.667,7.333,1.667,14,7,20s12,9.333,20,10c11.333,0.667,20.333-4,27-14l42-68h6l-1-8l12-19l6-11l14-63l62,64l19,97 c3.333,13.333,12,20.667,26,22c8,0.667,15-1.5,21-6.5s9.333-11.5,10-19.5v-2.5v-2.5l-10-51h18H372z M86,286.68H65H46v-1.5v-4.5v-4 c0.667-5.332,2.667-8,6-8h1c3.333,4,8,6.334,14,7c6,0.668,11-1.332,15-6c1.333,0.668, 2,1.668,2,3c1.333,3.334, 2,6.334, 2,9V286.68z M105,286.68H93c0-7.332-0.333-13.332-1-18c-0.667-5.332-2-9-4-11l15-66l30-23L105,286.68z M283,263.68c-0.667,1.334-1,6-1,14v9 h-26l-4,36l-53-59l19-82l17,31c1.338,2,2.671,3.667,4,5l58,38h-8C286.333,255.68,284.333,258.348,283,263.68z M288,286.68v-2v-4.5 v-3.5c0.667-5.332,2.667-8,6-8h12c6-0.666,11-1,15-1c2.667,0,4.5,1.5,5.5,4.5s1.5,6.168,1.5,9.5v5h-21H288z"/> </svg>';
    public $cardicon = '<svg version="1.1" xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" x="0px" y="0px" width="24px" height="24px" viewBox="0 0 48 48"> <path d="M 45.00,42.00l-9.00,0.00 l0.00,-3.00 l1.50,0.00 c 0.828,0.00, 1.50-0.672, 1.50-1.50S 38.328,36.00, 37.50,36.00l-6.00,0.00 c-0.825,0.00-1.50,0.672-1.50,1.50s 0.675,1.50, 1.50,1.50L33.00,39.00 l0.00,3.00 L15.00,42.00 l0.00,-3.00 l1.50,0.00 C 17.328,39.00, 18.00,38.328, 18.00,37.50S 17.328,36.00, 16.50,36.00l-6.00,0.00 C 9.672,36.00, 9.00,36.672, 9.00,37.50S 9.672,39.00, 10.50,39.00L12.00,39.00 l0.00,3.00 L3.00,42.00 c-1.656,0.00-3.00-1.341-3.00-3.00L0.00,9.00 c0.00-1.656, 1.344-3.00, 3.00-3.00l42.00,0.00 c 1.659,0.00, 3.00,1.344, 3.00,3.00l0.00,30.00 C 48.00,40.659, 46.659,42.00, 45.00,42.00z M 30.00,33.00l3.00,0.00 l0.00,-3.00 l-3.00,0.00 L30.00,33.00 z M 30.00,21.00l6.00,0.00 L36.00,18.00 l-6.00,0.00 L30.00,21.00 z M 27.00,12.00L6.00,12.00 l0.00,21.00 l21.00,0.00 L27.00,12.00 z M 39.00,12.00l-9.00,0.00 l0.00,3.00 l9.00,0.00 L39.00,12.00 z M 45.00,12.00l-3.00,0.00 l0.00,3.00 l3.00,0.00 L45.00,12.00 z M 45.00,18.00l-6.00,0.00 l0.00,3.00 l6.00,0.00 L45.00,18.00 z M 45.00,24.00l-15.00,0.00 l0.00,3.00 l15.00,0.00 L45.00,24.00 z M 15.00,27.183L15.00,26.697 C 13.26,26.073, 12.00,24.453, 12.00,22.50l0.00,-3.00 C 12.00,17.013, 14.013,15.00, 16.50,15.00S 21.00,17.013, 21.00,19.50l0.00,3.00 c0.00,1.953-1.26,3.573-3.00,4.197 l0.00,0.483 C 19.812,27.558, 21.387,28.572, 22.467,30.00L18.00,30.00 L15.00,30.00 L10.533,30.00 C 11.613,28.572, 13.188,27.558, 15.00,27.183z" ></path></svg>';

    public function __construct() {
        if ($this->trace) { echo gtab()."Enter ".__METHOD__."<br>"; }
     }
    public function init ($session,$processed,$promptwidth,$inputwidth,$hintwidth,$recorddelimiter,$isadmin,$singlerecord=false) {
        $this->session = $session;
        $this->processed = $processed;
        $this->promptwidth = $promptwidth;
        $this->inputwidth = $inputwidth;
        $this->hintwidth = $hintwidth;
        $this->recorddelimiter = $recorddelimiter;
        $this->isadmin = $isadmin;
        $this->singlerecord = $singlerecord;
     }
    public function setinputgroup ($inputgroup) {
        $this->inputgroup = $inputgroup;
     }
    public function setwidths ($promptwidth,$inputwidth,$hintwidth,$preservecurrent=false) {
        if ($preservecurrent) {
            $this->holdpromptwidth = $this->promptwidth;
            $this->holdinputwidth = $this->inputwidth;
            $this->holdhintwidth = $this->hintwidth;
        }
        $this->promptwidth = $promptwidth;
        $this->inputwidth = $inputwidth;
        $this->hintwidth = $hintwidth;
     }
    public function restorewidths () {
        $this->promptwidth = $this->holdpromptwidth;
        $this->inputwidth = $this->holdinputwidth;
        $this->hintwidth = $this->holdhintwidth;
     }
    public function setvariables($pagenum,$formname,$objectname='record',$rights=[]){
        $this->pagenum= $pagenum;
        $this->formname= $formname;
        $this->objectname= $objectname;
        $this->rights= $rights;
     } 
    public function geticon($icon="") { 
        return $this->{$icon."icon"}??"";    
     }
    public function getnhlogo() { // should be one of "collapse", "expand", "add", "trash","bookinghistory" 
        return '<img id="wnhlogo" fetchpriority="high"  width="70" height="68"  srcset="https://static.wixstatic.com/media/b6e990_3166b13408334ba2bf244dca84c4fa9e~mv2.png/v1/crop/x_77,y_56,w_790,h_810/fill/w_180,h_170,al_c,q_85,usm_0.66_1.00_0.01,enc_avif,quality_auto/wnh_round_logo_col.png 1x, https://static.wixstatic.com/media/b6e990_3166b13408334ba2bf244dca84c4fa9e~mv2.png/v1/crop/x_77,y_56,w_790,h_810/fill/w_360,h_340,al_c,q_85,usm_0.66_1.00_0.01,enc_avif,quality_auto/wnh_round_logo_col.png 2x" id="img_comp-l00c5rkd" src="https://static.wixstatic.com/media/b6e990_3166b13408334ba2bf244dca84c4fa9e~mv2.png/v1/crop/x_77,y_56,w_790,h_810/fill/w_180,h_170,al_c,q_85,usm_0.66_1.00_0.01,enc_avif,quality_auto/wnh_round_logo_col.png" alt="wnh_round_logo_col.png" style="object-fit:cover" >';    
     }
    public function getfblogo() {
        return '<img id="fblogo" fetchpriority="high" width="70" height="68" srcset="https://static.wixstatic.com/media/45fb2c_9c55ed381a0148d8a29961ffbabe1e0f~mv2.png/v1/crop/x_0,y_6,w_300,h_288/fill/w_254,h_244,al_c,q_85,usm_0.66_1.00_0.01,enc_avif,quality_auto/Untitled%20design%20(8).png 1x, https://static.wixstatic.com/media/45fb2c_9c55ed381a0148d8a29961ffbabe1e0f~mv2.png/v1/crop/x_0,y_6,w_300,h_288/fill/w_420,h_403,al_c,lg_1,q_85,enc_avif,quality_auto/Untitled%20design%20(8).png 2x" id="img_comp-lmrayi9l2" src="https://static.wixstatic.com/media/45fb2c_9c55ed381a0148d8a29961ffbabe1e0f~mv2.png/v1/crop/x_0,y_6,w_300,h_288/fill/w_254,h_244,al_c,q_85,usm_0.66_1.00_0.01,enc_avif,quality_auto/Untitled%20design%20(8).png" alt="Untitled design (8).png" class="BI8PVQ Tj01hh" style="object-fit: cover;">';    
     }
    protected function renderproblemsheader($trace=false) {
        if ( $this->trace || $trace) { echo gtab(1)."Enter ".__METHOD__." Missing fields = ";var_dump($this->missingfields);echo  "<br>\n"; }
        $html = "";
        if ($this->processed || count($this->dberrors)) { //this form ia being rendered having been processed - there must have been problems
            if (count($this->missingfields) || count($this->dataerrors) || count($this->dberrors)) {
                $html .= '<div class="errorbox"><p>'; //<div class="errorheading">THERE ARE PROBLEMS WITH YOUR REQUEST</div>
                if (count($this->dberrors) > 0){
                    //$html .= '<div class="errorheading">The following errors occured when updating the database (the webmaster has been notified) :</div>';
                    $html .= '<div class="errorbody"><p>';
                    foreach($this->dberrors as $var=>$msg) {
                        $html .= '<p>'.$msg.'</p>';
                    }
                    $html .= "</div>";
                }
                if (count($this->missingfields) > 0){
                    $comma = "";        
                    $html .= '<div class="errorheading">The following fields are mandatory :</div><div class="errorbody"><p>';
                    foreach($this->missingfields as $var=>$msg) {
                        $html .= $comma.$msg;
                        $comma = ', ';
                    }
                    $html .= "</p></div>\n";
                }
                if (count($this->dataerrors)) {
                    $html .= '<div class="errorheading">There are fields that contain invalid data - see (red) messages below</div>';
                }
                $html .= "</div><!-- errorbox -->\n";
            }
        }
        if ( $this->trace || $trace) { echo gtab(-1)."Leave ".__METHOD__."<br>"; }
        return $html ;
     }
    public function renderhint($hint,$class='')     {
        if ( $this->trace) { echo gtab()."Enter ".__METHOD__."<br>"; }
        if (strlen($hint)) { 
            $html = '<div class="'.(strlen($class)?$class:'vols-form-row-hint').'">'.$hint."</div>\n";
        } else {
            $html = "";
        }
        return $html;
     }
    public function renderrowerror($error,$class='')     {
        if ( $this->trace) { echo gtab()."Enter ".__METHOD__."<br>"; }
        if (strlen($error)) { 
            $html = '<div class="'.(strlen($class)?$class:'vols-form-row-error').'">'.$error."</div>\n";
        }
        return $html;
     }
    public function renderbuttongroup($buttons,$groupid) {

        $buttongroup = "<div id='#{$groupid}' class='buttongroup'> ";
        foreach ($buttons as $key => $button) {
            $button["cellclass"]    = empty($button["cellclass"])?" vols-width-100 ":$button["cellclass"];
            $buttongroup .= '<div class="vols-tablecell aligncenter '.$button["cellclass"].' ">';
            $button["buttonclass"]  = empty($button["buttonclass"])?" doitbg":$button["buttonclass"];
            $buttongroup .= '  <div id="'.$button["id"].'" class="clickable action '.($button["buttonclass"]??"").'" '.($button["data"]??"").' >'.$button["text"].'</div>';
            $buttongroup .= '</div>';   
        }
        $buttongroup .= "</div>";
        return $buttongroup;
     }
    public function rendersectionheading($heading,$headingclass="",$subheading="",$subheadingclass="",$rowid='',$actionrow="",$width='',$id='',$cellclass='',$inputgroup='',$containerclass="",$buttons=[],$addid="",$trace=false) {
        if ( $this->trace || $trace) { echo gtab(1)."Enter ".__METHOD__."<br>"; }
        $addable = $addid != "";
        $collapsable = false;
        if ($inputgroup != '' && $inputgroup !== true && $inputgroup !== false) { // this param used to be a boolean
            $this->setinputgroup($inputgroup);
            // for collapsable to work we need an inputgroup. we can assume we need collapsable if frc is !empty
            $collapsable = true; 
        } else {
            $this->setinputgroup( "");
        }

         if (($buttons["leftid"]??"") !== "") {
            $leftcell = '<div class="vols-tablecell aligncenter vols-width-17 vols-leftcell"><div id="'.$buttons["leftid"].'" class="clickable action  doitbg "'.$buttons["leftdata"]??"".' >'.$buttons["lefttext"].'</div></div>';
        } else {
            $leftcell = '<div class="vols-tablecell  vols-width-17 vols-leftcell">&nbsp;</div>';
        }

        $rightcell = '<div class="vols-tablecell vols-width-17 aligncenter  vols-rightcell vols-righttcell">';
        if (($buttons["rightid"]??"") !== "") {
            $rightcell .= '<div id="'.$buttons["rightid"].'" class="clickable action  doitbg" '.($buttons["rightdata"]??"").'>'.($buttons["righttext"]??"").'</div>';
        } else {
            $rightcell .= '&nbsp;';
        }

        $rightcell .= ($collapsable?"<div id='{$inputgroup}' class='floatright groupsvgcontainer'></div>":(""));
        $rightcell .= ($addable?"<div id='{$addid}' class='floatright addsvgcontainer activeicon'>{$this->addicon}</div>":(""));
        $rightcell .= '</div>';

        $html  = '<div'.(strlen($rowid)?' id = "'.$rowid.'"':'').' class="vols-tablerow" '.(strlen($width)?' style="width:'.$width.'px"':'').'>'."\n";
        $html .= '  <div class="'.(strlen($cellclass)?$cellclass:"vols-tablecell vols-width-100 vols-sectionhead").'">'."\n";
        $html .= '    <div class="'.(strlen($containerclass)?$containerclass:"vol-form-headingcontainer").'">'."\n"; 
        $html .= '      <div class="headingrowwrap">';    
        $html .= $leftcell.'<div '.(strlen($id)?('id="'.$id.'" '):'').'class="'.(strlen($headingclass)?$headingclass:'vols-form-heading').'">'.$heading.'</div>'.$rightcell."\n"; 
        $html .= "      </div>\n";
        $html .= '      <div class="'.(strlen($subheadingclass)?$subheadingclass:'vols-form-subheading').'">'.$subheading."\n</div>\n"; 
        $html .= "    </div>\n";
        $html .= "    <div style='clear: both;'></div>\n";
        $html .=      $actionrow;
        $html .= "  </div>\n";
        $html .= "</div>\n";
        if (($buttons["leftscript"]??"") !== "") { $html .= "<script>{$buttons['leftscript']}</script>"; }
        if (($buttons["rightscript"]??"") !== "") {$html .= "<script>{$buttons['rightscript']}</script>"; }
        if ( $this->trace|| $trace) { echo gtab(-1)."Leave ".__METHOD__."<br>"; }
        return $html;
     }
    public function renderfiltersheading($id,$filterheading="",$filters=[])     {
        if ( $this->trace) { echo gtab(1)."Enter ".__METHOD__."<br>"; }
        $html = '<div id="'.$id.'" class="vols-tablecell vols-width-100 vols-filtercontainer">'."\n";
        $html .= "<div style='color: var(--darkgrey);font-weight: 500;font-size: 1.6rem;height:2.5rem;width: 100%;text-align:center'>{$filterheading}</div>";       
        $html .= "<div style='display:grid;grid-template-columns: 1fr 1fr 3fr'>";
        foreach ($filters as $filter) {
            $html .= "<div>&nbsp;</div><div style='height:2rem'>$filter[0]</div><div>$filter[1]</div>";
        }   
        if ( $this->trace) { echo gtab(-1)."Leave ".__METHOD__."<br>"; }
        $html .= "</div></div>\n";
        return $html;
     }
    public function renderprompt($id='',$prompt='',$required=false,$cellclass='')     {
        $promptcell = '<div '.(strlen($id)?('id="'.$id.'" '):' ');
        $promptcell .= 'class="vols-tablecell vols-vertical-center'.(strlen($cellclass)?$cellclass:''); 
        $promptcell .= (empty($this->promptwidth)?"":(' vols-width-'.$this->promptwidth));
        $promptcell .= '">'.$prompt.($required?$this->red_asterisk:'')."</div>\n"; 
        return $promptcell ;         
     }
    public function buildsimpleoptionlist($array,&$optionarray='',$trace=false)     {
        // array is non-associative collection of names - 
        // build both array and string of SELECT OPTIONS for the names
        if ($this->trace || $trace) { echo gtab(1)."Enter ".__METHOD__."<br>"; }// var_dump($this->dataerrors);  echo "<br>";
        $select = "";
        $i = 0;
        if (isset($array)) {
            foreach ($array as $item) {
                $option = "<option value='".$i++."'>".$item.'</option>';
                $select .=  $option;
                $optionarray[$item["id"]] = $option;                
            }
        }    
        if ( $this->trace || $trace) { echo gtab(-1)."Leave ".__METHOD__."<br>"; }
        return $select   ;
     } 
    public function buildoptionlist($data,&$ids='',&$names=[],$valfield="id",$orderby='name',$sequenceconstants=SORT_ASC,$trace=false)     { 
        // data is 2D, with each element being an associative array containing, usually, at least "id" and "name" keys
        // build both array and string of SELECT OPTIONS for the elements
        if ($this->trace || $trace) { echo gtab(1)."Enter ".__METHOD__."<br>"; }// var_dump($this->dataerrors);  echo "<br>";
        $select = "";
        if (!empty($data)) {
            $names = []; 
            $data = \array_orderby($data,$orderby,SORT_ASC);
            foreach ($data as $item) {
                $option = "<option value='".$item[$valfield]."'>".$item[$orderby].'</option>';
                $select .=  $option;
                $ids .= $item[$valfield].$this->recorddelimiter;            
                $names += [$item[$valfield]=>$item[$orderby]];
            }
        }  
        if ( $this->trace || $trace) { echo gtab(-1)."Leave ".__METHOD__."<br>"; }
        return $select;
     }
/*=========================================================================== BUILD FORM INPUT for various field types */
    public function rendermulti($sectionheading,$rowid,$prompt1,$prompt2,$notmine,$mine,$size,$identifier='',$mid='',$hidden=false,$trace=false,$selectandhidesingleoptions=true,$sectionheadclass='',$formclass='')     {
        if ($this->trace || $trace) { echo gtab(1)."Enter ".__METHOD__."<br>"; }// var_dump($this->dataerrors);  echo "<br>";
        $mid= (strlen($mid)?$mid:"multiselect");
        if ($selectandhidesingleoptions  && (count($mine) + count($notmine) == 1)) {
            $hidden = true;
            $mine = $mine + $notmine;
            $notmine = [];  
        }   
        $multi  =   ($hidden?"":$this->rendersectionheading($sectionheading,$formclass,"","",$rowid."heading",'','','',$sectionheadclass))."\n"; 
        $multi .= ' <div id="'.$rowid.'" class="vols-tablerow '.($hidden?"hidden":("")).'">'."\n";
        $multi .= '   <div class="vols-tablecell vols-width-100">'."\n";
        $multi .= '     <div class="col-xs-5">'."\n";
        $multi .= '       <div class="multilist-heading">'.$prompt1.'</div>'."\n";
        // add in multi starts here
        $multi .= '       <select name="'.(strlen($identifier)?("notmy".$identifier."[]"):("cantaccess[]")).'" id="'.$mid.'" class="form-control" size="'.$size.'" multiple="multiple">'."\n";
        $multi .= $this->buildoptionlist($notmine);
        $multi .= '       </select>'."\n";
        $multi .= '     </div>'."\n";
        $multi .= '     <div class="col-xs-2">'."\n";
        $multi .= '       <div class="multilist-heading">&nbsp;</div>'."\n";
        $multi .= '       <button type="button" id="'.$mid.'_rightAll" class="btn btn-block"><i class="glyphicon glyphicon-forward"></i></button>'."\n";
        $multi .= '       <button type="button" id="'.$mid.'_rightSelected" class="btn btn-block"><i class="glyphicon glyphicon-chevron-right"></i></button>'."\n";
        $multi .= '       <button type="button" id="'.$mid.'_leftSelected" class="btn btn-block"><i class="glyphicon glyphicon-chevron-left"></i></button>'."\n";
        $multi .= '       <button type="button" id="'.$mid.'_leftAll" class="btn btn-block"><i class="glyphicon glyphicon-backward"></i></button>'."\n";
        $multi .= '     </div>'."\n";
        $multi .= '     <div class="col-xs-5">'."\n";
        $multi .= '       <div class="multilist-heading">'.$prompt2.'</div>'."\n";
        $multi .= '	  <select name="'.(strlen($identifier)?("my".$identifier."[]"):("canaccess[]")).'" id="'.$mid.'_to" class="form-control" size="'.$size.'" multiple="multiple">'."\n";
        $multi .= $this->buildoptionlist($mine);
        $multi .= '	  </select>'."\n";
        // multi ends here
        $multi .= '     </div>'."\n";
        $multi .= '     <div style="clear: both;"></div>'."\n";
        $multi .= '   </div>'."\n"; //cell
        $multi .= ' <div style="clear: both;"></div>';
        $multi .= ' </div>'."\n"; //row
        if ( $this->trace || $trace) { echo gtab(-1)."Leave ".__METHOD__."<br>"; }
        return $multi ;
     }
    public function rendercheckbox($fieldname,$value,$checked=0,$text='',$lefttext=false,$fieldnum=0,$autofocus=false,$inputclass='',$divclass='',$required=false,$readonly=false,$disabled=false)     {
        if ( $this->trace ) { echo gtab(1)."Enter ".__METHOD__."  $value  <br>"; }
        $cb = '<input type="checkbox"  id="'.$fieldname.'"  value="'.$value.'"  name="'.$fieldname.'" ' ;
        $cb .= ' class="'.(strlen($inputclass)?$inputclass:'vols-form-input').'" ';         
        $cb .= $fieldnum>0?(' data-fnum="'.$fieldnum.'"'):"";  
        $cb .= $autofocus?" autofocus":"";  
        $cb .= $readonly?" readonly":"";  
        $cb .= $disabled?" disabled":"";  
        $cb .= $required?" required":"";  
        $cb .= ($checked?" checked":"").">";
        if (strlen($text)) {
            $html = '<div class="vols-form-checkboxes" style="display: inline-block;">';
            $html .= '<label for="'.$fieldname.'" style="display: inline-block;">';
            if ($lefttext) 
                $html .= $text."&nbsp;".$cb.'</label></div>';
            else
                $html .= $cb."&nbsp;".$text.'</label></div>';
        } else {
            $html = $cb;
        } 
        if ( $this->trace) { echo gtab(-1)."Leave ".__METHOD__."<br>"; }
        return $html;
     }
    public function renderradiobuttons($fieldname,$buttons,$curval,$radioclass='',$fnum='',$horizontal=false,$id='',$multicolumn=false,$trailinglabel=false,$prompt="",$required=false)     {    
        // $buttons should be an array of button arrays e.g. [["SMS"=>1],["Email"=>2],["Phone"=>3]];
        if ( $this->trace) { echo gtab(1)."Enter ".__METHOD__." :<br>"; }
        $buttoncount = 0;
        $html = $prompt==""?"":'<div class="vols-float-left">'.$prompt."</div> ";
        $html .= ($multicolumn?'<div class="vols-float-left">':'');
        foreach ($buttons as $button) {
          if ($button == "newcol") {
            $html .= '</div><div class="vols-float-left ">';
            $buttoncount = 0;
          } else { 
            $buttonlabel = "";
            $rqd = ($required&&($buttoncount==0))?"required":"";
            $row = 0;
            if ($buttoncount++ > 0 && !$horizontal) $html.='<br /> '."\n";
            $html .= '<div class="'.(!strlen($radioclass)?"vols-form-radiobuttons":$radioclass).'" style="display: inline-block;padding-right:10px">';
            foreach ($button as $label=>$val) {
                $idstr = (strlen($id)?$id:$fieldname).$val;
                $html .= '<label for="'.$idstr.'">';
                if ($row++ == 0) { // first row is the label and value
                    $btn  = "<input id='{$idstr}' type='radio' name='{$fieldname}' value='{$val}' " ;
                    $btn .= ($val == $curval ? " checked" : "");
                    $btn .= strlen($fnum)?(' data-fnum="'.$fnum.$val.'"'):"";//we can use this to hold a field number 
                    $html .= $btn;
                    $buttonlabel = $label; // store this 
                } else { // subsequent rows may contain  properties in the value e.g. "disabled"
                    $html .= " ".$val;
                }
            }
            $html .= " $rqd >&nbsp;".$buttonlabel."</label></div>";
          }
        }
        $html .= ($multicolumn?'</div>':'');
        if ( $this->trace) { echo gtab(-1)."Leave ".__METHOD__."<br>"; }
        return "\n".$html;
     }
    public function renderdropdown($fieldname,$size,&$options,$autofocus=false,$disabled=false,$required=false,$multiselect=false,$values=[],$selection='', $selectontext=false,$selectclass='',$divclass='',$trace=false,$fnum='',$script='') {
        if ( $this->trace || $trace) { echo gtab(1)."Enter ".__METHOD__." :".$fieldname." selected = '".$selection."':<br>";lib::pr($values);  } //var_dump($values);
        $html = '<select name="'.$fieldname.'" id="'.$fieldname.'"  class="'.(strlen($selectclass)?$selectclass:"vols-form-select").'"';
        $html .= strlen($size)?(' size="'.$size.'"'):""; 
        $html .= $autofocus?" autofocus":"";  
        $html .= $disabled?" disabled":"";  
        $html .= $required?" required":"";  
        $html .= $multiselect?" multiple":"";  
        //        $html .= $readonly?" readonly":"";  
        $html .= strlen($fnum)?(' data-fnum="'.$fnum.'"'):"";  //we can use this to hold a field number 
        $html .= ">"; 
        $options = ""; 
        settype($selection,"string");
        if (is_array($values) && count($values)>0) {
          foreach ($values as $id=>$text) {
            settype($text,"string");
            settype($id,"string");
            $textparts = explode("||",$text);
            $optionattributes = $textparts[1] ?? ""; 
            $option= '<option id="'.$fieldname."-".$id.'" value="'.$id.'" '.$optionattributes;
            if ((($selectontext == true) && ($selection=== $textparts[0])) || (($selectontext == false) && ($selection== $id))) {
                $option.= ' selected';
            }
            $option.= '>'.$textparts[0].'</option>';
            $options .= $option;  
            $html .= $option;
          }
        }
        $html .= "</select>$script";
        if ( $this->trace || $trace) { echo gtab(-1)."Leave ".__METHOD__."<br>"; } // :'".$html."
        return $html;
     }
    public function rendertextinput($fieldname,$size,$maxlength,$placeholder,$autofocus=false,$value='',$inputclass='',$divclass='',$fnum=0,$required=false,$readonly=false,$disabled=false,$rows=1,$trailingtext="",$type="",$min="",$max="",$step="",$listname="")     {
        if ( $this->trace) { echo gtab(1)."Enter ".__METHOD__."<br>"; }
        if ($rows > 1) {
            $html = '<textarea cols="'.$size.'" rows="'.$rows.'" ';
        } else { 
            $type  = strlen($type)? $type: "text";
            $html  = "<input type='{$type}'";
            // $html  = '<input ';
            $html .= strlen($size)?(' size="'.$size.'"'):""; 
            $html .= strlen($value)?(' value="'.$value.'"'):""; 
        }
        $html .= ' name="'.$fieldname.'" id="'.$fieldname.'" ';
        $html .= 'class="'.(strlen($inputclass)?$inputclass:'vols-form-input').'" ';         
        $html .= strlen($maxlength)?(' maxlength="'.$maxlength.'"'):"";  
        $html .= strlen($placeholder)?(' placeholder ="'.$placeholder.'"'):"";  
        $html .= $fnum>0?(' data-fnum="'.$fnum.'"'):"";  
        $html .= strlen($min)?(' min="'.$min.'"'):"";  
        $html .= strlen($max)?(' max="'.$max.'"'):"";  
        $html .= strlen($step)?(' step="'.$step.'"'):"";  
        $html .= $autofocus?" autofocus":"";  
        $html .= $readonly?" readonly":"";  
        $html .= $disabled?" disabled":"";  
        $html .= $required?" required":"";
        $html .= $listname==""?"": " list='{$listname}'";
        $html .= '>';
        if ($rows > 1) {
            $html .= $value.'</textarea>';
        }
        $html .= $trailingtext == ""?"":(" ".$trailingtext);
        if ( $this->trace) { echo gtab(-1)."Leave ".__METHOD__."<br>"; }
        return $html;
     }
    public function renderdateinput($fieldname,$type,$placeholder,$min,$max,$step,$autofocus=false,$value='',$inputclass="",$fnum=0,$required=false,$readonly=false,$disabled=false)     {
        if ( $this->trace) { echo gtab(1)."Enter ".__METHOD__."<br>"; }
        $type  = strlen($type)? $type: "date";
        $html  = "<input type='{$type}' name='{$fieldname}' id='{$fieldname}'";
        $html .= ' class="'.(strlen($inputclass)?$inputclass:'vols-form-input').'"';         
        $html .= strlen($min)?(' min="'.$min.'"'):"";  
        $html .= strlen($max)?(' max="'.$max.'"'):"";  
        $html .= strlen($step)?(' step="'.$step.'"'):"";  
        $html .= strlen($placeholder)?(' placeholder ="'.$placeholder.'"'):"";  
        $html .= $fnum>0?(' data-fnum="'.$fnum.'"'):"";  
        $html .= $autofocus?" autofocus":"";  
        $html .= $readonly?" readonly":"";  
        $html .= $disabled?" disabled":"";  
        $html .= $required?" required":"";  
        $html .= strlen($value)?" value = {$value}":"";
        $html .= '>';
        if ( $this->trace) { echo gtab(-1)."Leave ".__METHOD__."<br>"; }
        return $html;
     }
    public function rendergrid($data,$entityname,$addrows,$deleterows,$maxbodywidth,$maxheight,$rowwidth,$trace=false) {
            /*=====================================================================================================================================
         THIS IS EXPERIMENTAL AT THIS STAGE. 
         THE FOLLOWING IS AN EXAMPLE OF THE DATA ARRAY REQUIRED FOR A GRID 
                $data = ["headingrow"=>[ // CONTENTS OF THE FOLLOWING ARRAYS ARE TYPE-SPECIFIC - E.G. ONLY TYPE=RADIO HAS A "prompts" ELEMENT
                                        ["heading"=>"Number",	"type"=>"input",   "field"=>"mynum",   "width"=>100, "size"=>5, "maxlength"=>10,"placeholder"=>"num", "required"=>1,"readonly"=>0,"disabled"=>0],
                                        ["heading"=>"Text",	"type"=>"input",   "field"=>"mytext",  "width"=>100, "size"=>10,"maxlength"=>50,"placeholder"=>"text","required"=>0,"readonly"=>0,"disabled"=>0],
                                        ["heading"=>"Radio",	"type"=>"radio",   "field"=>"myradio", "width"=>100, "prompts"=>[0=>"No",1=>"Yes"],"horizontal"=>0,"readonly"=>0,"disabled"=>0],
                                        ["heading"=>"checkbox",	"type"=>"checkbox","field"=>"mybool",  "width"=>100, "required"=>1,"readonly"=>0,"disabled"=>0],
                                        ["heading"=>"Select",	"type"=>"select",  "field"=>"myselect","width"=>100, "lines"=>1,"depth"=>5,"required"=>0,"readonly"=>0,"disabled"=>0,"multiple"=>0]],
                         12=>          [1234,"abcde",true,1,5],   // THE KEY OF 12 IS THE ID OF THE RECORD, OR WHATEVER
                         26=>          [34,"david",false,0,2],    // DATA FIELDS IN THESE ARRAYS MUST MATCH THE COLUMNS IN THE HEADINGROW
                         32=>          [64,"wonderful",true,1,3],
                         125=>         [23345,"beauty",false,0,8]
                        ]; 
         TO BE ADDED... 
         > CAPABILITY TO ATTACH E.G. DATE AND TIME WIDGETS TO FIELDS
         > CUSTOM BUTTONS

        =====================================================================================================================================*/
        if ($this->trace || $trace) { echo gtab(1)."Enter ".__METHOD__."<br>"; }// var_dump($this->dataerrors);  echo "<br>";
        $col = []; 
        $options = '';
        $grid  = '<div class="vols-gridtable" >'."\n"; 
        $grid .= '  <div class="vols-gridtablebody" style="max-width:'.$maxbodywidth.'px; max-height:'.$maxheight.'px;  ">'."\n"; 
        foreach ($data as $key=>$row) { 
            $grid .= '    <div id=gridrow_"'.$key.'" class="vols-gridtablerow vols-row-divider" style="width:'.$rowwidth.'px; '.($key=="headingrow"?"background-color:#ddd; font-weight:bold":" ").' " >'."\n";  
            if ($key=="headingrow") {
                $coldefns = $row;
                if ($addrows || $deleterows) { //include, or leave the space of, an add button
                    $grid .= '       <div id="addbutton" class="vols-tablehead vols-pxwidth-35 vols-minwidth-35">';
                    if ($addrows) {
                        $grid .= '<span title="Add a new '.$entityname.'."><button type="button" id="rate-insert"><i class="glyphicon glyphicon-plus"></i></button></span>';
                    } else {
                        $grid .= '&nbsp;';
                    }
                    $grid .= '</div>'."\n";
                }
                foreach ($row as $id=>$column) {
                    $grid .= "        ".$this->rendercell($id,$column["heading"],'','','',$column["width"])."\n";
                }
            } else { 
                if ($addrows || $deleterows) { //include, or leave the space of, a delete button
                    $grid .= '        <div id="delbutton" class="vols-tablecell vols-pxwidth-35 vols-minwidth-35">';
                    if ($deleterows) {
                        $grid .= '<span title="Remove this new '.$entityname.'."><button type="button" id="rowdelete"><i class="glyphicon glyphicon-minus"></i></button></span>';
                    } else {
                        $grid .= '&nbsp;';
                    }
                    $grid .= '</div>'."\n";
                }
                foreach ($row as $id=>$value) {
                    $column = $coldefns[$id];
                    switch ($column["type"]) {
                        case "input":
                            $cell = $this->rendertextinput($column["field"],$column["size"],$column["maxlength"],$column["placeholder"],false,$value,'','',0,$column["required"],$column["readonly"],$column["disabled"]);
                            break;
                        case "radio":
                            $cell = $this->renderradiobuttons($column["field"],$column["prompts"],$value,'','',$column["horizontal"]); 
                            break;
                        case "select":
                            $cell = $this->renderdropdown($column["field"],$column["size"],$options,false,$column["disabled"],$column["required"],$column["multiple"],$column["values"],$value); 
                            break;
                        default:
                            $cell = "&nbsp;"; 
                            break;
                    }
                    $grid .= "        ".$this->rendercell($id,$cell,'','','',$column["width"])."\n";
                }
            } 
            $grid .= "    <div style='clear: both;'></div></div>\n        \n";          
        }
        $grid .= '  </div>'."\n";          
        $grid .= '</div>'."\n";          
        $grid .= '<div style="clear: both;"></div>';
        if ( $this->trace) { echo gtab(-1)."Leave ".__METHOD__."<br>"; }
        return $grid;
     }
/*================= SPECIALS */
    public function dayofweekcheckboxes($field,$fieldnum,$prompt,$hint,$value,$required,&$loaddowfieldscript,&$loaddowvariablescript,$checkboxsuffix="",$cellonly=false)     { 
        // quick build  for a relatively standard row (
        $x = (int) $value / 1;
        $cbs  = $this->rendercheckbox("sun{$checkboxsuffix}",1,($x & 1),'Sun',false,99,false,'','',false,false,false);
        $cbs .= $this->rendercheckbox("mon{$checkboxsuffix}",2,(($x >> 1) & 1),'Mon',false,99,false,'','',false,false,false);
        $cbs .= $this->rendercheckbox("tue{$checkboxsuffix}",4,(($x >> 2) & 1),'Tue',false,99,false,'','',false,false,false);
        $cbs .= $this->rendercheckbox("wed{$checkboxsuffix}",8,(($x >> 3) & 1),'Wed',false,99,false,'','',false,false,false);
        $cbs .= $this->rendercheckbox("thu{$checkboxsuffix}",16,(($x >> 4) & 1),'Thu',false,99,false,'','',false,false,false);
        $cbs .= $this->rendercheckbox("fri{$checkboxsuffix}",32,(($x >> 5) & 1),'Fri',false,99,false,'','',false,false,false);
        $cbs .= $this->rendercheckbox("sat{$checkboxsuffix}",64,(($x >> 6) & 1),'Sat',false,99,false,'','',false,false,false);
        $formfields ="  <input type='hidden' name='{$field}' id='{$field}'' data-fnum='{$fieldnum}' value='' />\n";
        if ($cellonly) {
            $formfields .= $this->rendercell("",$prompt." ".$cbs,"","100");
        } else {
            $formfields .= $this->renderformrow("","",$prompt,$required,'','','',$cbs,'','','',$hint);
        }
        $loaddowfieldscript = <<<JS
                    const x = jQuery("#{$field}").val(); // loaded from the hidden fields
                    // x is an integer in which the lower order 7 bits carry a boolean for each day of the week
                    // we use right shifting and ANDing with 1 to extract the 7 values
                    jQuery("#sun{$checkboxsuffix}").prop("checked",(x & 1));
                    jQuery("#mon{$checkboxsuffix}").prop("checked",((x >> 1) & 1));
                    jQuery("#tue{$checkboxsuffix}").prop("checked",((x >> 2) & 1));
                    jQuery("#wed{$checkboxsuffix}").prop("checked",((x >> 3) & 1));
                    jQuery("#thu{$checkboxsuffix}").prop("checked",((x >> 4) & 1));
                    jQuery("#fri{$checkboxsuffix}").prop("checked",((x >> 5) & 1));
                    jQuery("#sat{$checkboxsuffix}").prop("checked",((x >> 6) & 1));
                JS;
        $loaddowvariablescript = <<<JS
                    // we have to read the values from the checkboxes to reconstruct the dow field 
                    // by combining the 7 checkboxes into an integer
                    const cbval=jQuery("#sun{$checkboxsuffix}").is(":checked")+
                                (2*jQuery("#mon{$checkboxsuffix}").is(":checked"))+
                                (4*jQuery("#tue{$checkboxsuffix}").is(":checked"))+
                                (8*jQuery("#wed{$checkboxsuffix}").is(":checked"))+
                                (16*jQuery("#thu{$checkboxsuffix}").is(":checked"))+
                                (32*jQuery("#fri{$checkboxsuffix}").is(":checked"))+
                                (64*jQuery("#sat{$checkboxsuffix}").is(":checked"));
                    jQuery("#{$field}").val(cbval);
                JS;
        return $formfields;
     }
/*================= RENDER ONE ROW */
    public function rendercell($id,$cellcontents,$cellclass='',$pcwidth='',$styleclass='',$pxwidth='',$trace=false   )     {
        if ($this->trace || $trace) { echo gtab(1)."Enter ".__METHOD__.",".($cellclass??"a").",".($pcwidth??"b").",".($styleclass??"c").",".($pxwidth??"d").",<br>"; }// var_dump($this->dataerrors);  echo "<br>";
        $cell = '<div '.(empty($id)?"":("id='$id'"))." class='vols-tablecell ";
        $cell .= $cellclass??"";       
        $cell .= empty($pcwidth)?"":(' vols-width-'.$pcwidth);
        $cell .= empty($pxwidth)?"":(' vols-pxwidth-'.$pxwidth);
        $cell .= ' '.($styleclass??'')."'>";
        $cell .= $cellcontents."</div>\n";
        if ($this->trace || $trace) { echo gtab(-1)."Leave ".__METHOD__."<br>"; }// var_dump($this->dataerrors);  echo "<br>";
        return $cell;         
     }
    public function renderformrow($id="",$promptid="",$prompt="",$required="",$promptcellclass="",$promptstyle="",$inputid="",$forminput="",$inputcellclass='',$inputstyle='', $hintid='', $hint='',$hintcellclass='',$hintstyle='',$errorid='', $rowerror='', $errorcellclass='', $errorstyle='', $rowborder='',$rowclass='',$rowdata=[])     {
        $html  = '<div '.(empty($id)?'':("id='{$id}'")).' class="';
        $html .= ((!empty($rowborder) && !empty($rowerror))?$rowborder:'').(empty($rowclass)?' vols-tablerow':$rowclass);
        $html .= ($this->odd?' vols-row-odd':' vols-row-even').(empty($this->inputgroup)?"":" {$this->inputgroup} grouped").'"';
        if ($rowdata!=[]) {
            $keys= array_keys($rowdata);
            $html .= " data-{$keys[0]} = '{$rowdata[$keys[0]]}'"; 
        }
        $html .= ">\n";  
        $html .= $this->renderprompt($promptid,$prompt,$required,$promptcellclass);
        $html .= $this->rendercell($inputid,$forminput,$inputcellclass,$this->inputwidth,$inputstyle,'',false);
        $html .= $this->rendercell($hintid,$hint,$hintcellclass,$this->hintwidth,(strlen($hintstyle)?$hintstyle:"vols-hintcell"),'',false);
        $html .= ' <div style="clear: both;"></div>';
        $html .= "</div>\n";
        $html .= '<div class="vols-shallow-table-row '.(strlen($rowborder)?$rowborder." ":'').($rowclass==''?($this->odd?'vols-row-odd':'vols-row-even'):$rowclass).' '.(empty($this->inputgroup)?"":" {$this->inputgroup} grouped").'">'."\n";
        // $this->inputgroup.'">'."\n";
        $html .= $this->renderprompt($id."_errorprompt",((strlen($rowerror))?'&nbsp;':''),false,(strlen($errorstyle)?$errorstyle:" vols-errorprompt"));
        $html .= $this->rendercell((strlen($errorid)?$errorid:($id."_error")),$rowerror," vols-vertical-center ".$errorcellclass,$this->inputwidth+$this->hintwidth-5,(strlen($errorstyle)?$errorstyle:"vols-errorcell"),'',false);
        $html .= ' <div style="clear: both;"></div>';
        $html .= "</div>\n";
        $this->odd = empty($rowclass)?!($this->odd):($this->odd); 
        return $html;
     }
/*================= BUILD AND RENDER ENTIRE ROW - various field types */
    public function buildselectrow($field,$fieldnum,$listheight,$prompt,$values,$hint,&$optionsout,$autofocus=false,$disabled=false,$required=false,$multiple=false,$rowstyle='',$trace=false)     {    // quick build  for a relatively standard row (styling) containing a dropdown select
        $select = $this->renderdropdown($field,$listheight,$optionsout,$autofocus,$disabled,$required,$multiple,$values,'',false,'vols-form-select','',$trace,$fieldnum);
        $select = $this->renderformrow($field.'row',$field.'prompt',$prompt,$required,'','','',$select,'','','',$hint,'','','','','','',$rowstyle); 
        return $select;
     }
    public function buildinputrow($field,$fieldnum,$value,$prompt,$placeholder,$cols,$maxlength,$required=false,$hintid='', $hint='',$readonly=false,$disabled=false,$inputcellstyle='',$rows=1,$trailingtext="",$type="",$listname="")     {    // quick build for a relatively standard row (styling) containing a text input 
        $input = $this->rendertextinput($field,$cols,$maxlength,$placeholder,true,$value,'','vols-form-input',$fieldnum,$required,$readonly,$disabled,$rows,$trailingtext,$type, listname:$listname);
        $input = $this->renderformrow($field.'row',$field.'prompt',$prompt,$required,'','','',$input ,'',$inputcellstyle,$hintid,$hint,'','','','','','','') ;
        return $input ;
     }
    public function buildtextarearow($field,$fieldnum,$value,$prompt,$placeholder,$cols,$rows,$maxlength,$required=false,$hintid='', $hint='',$readonly=false,$disabled=false,$inputcellstyle="",$trailingtext="")     {    // quick build for a relatively standard row (styling) containing a text input 
        $input = $this->rendertextinput($field,$cols,$maxlength,$placeholder,true,$value,'','vols-form-input',$fieldnum,$required,$readonly,$disabled,($rows<2?2:$rows),$trailingtext);
        $input = $this->renderformrow($field.'row',$field.'prompt',$prompt,$required,'','','',$input ,'',$inputcellstyle,$hintid,$hint,'','','','','','','') ;
        return $input ;
     }
    public function buildbuttonsrow($buttons,$groupid)     {    // quick build for a relatively standard row (styling) containing a text input 
        $this->setwidths(5,90,5,true);
        $buttons = $this->renderbuttongroup($buttons,$groupid);
        $input = $this->renderformrow(forminput:$buttons) ;
        $this->restorewidths();
        return $input ;
     }
    public function buildcheckboxrow($field,$value,$text,$lefttext=false,$fieldnum=0,$prompt='', $hint='',$required=false,$readonly=false,$disabled=false,$checked=false,$rowclass='',$rowdata=[])     {    // quick build for a relatively standard row (styling) containing a text input 
        $input = $this->rendercheckbox($field,$value,$checked,$text,$lefttext,$fieldnum,false,'','',$required,$readonly,$disabled);
        $input = $this->renderformrow($field.'row',$field.'prompt',$prompt,$required,'','','',$input ,'','','',$hint,'','','','','','','',$rowclass,$rowdata) ;
        return $input ;
     }
    public function builddaterow($fieldname,$type,$placeholder,$min,$max,$step,$fnum=0,$autofocus=false,$value='',$prompt='',$hint='',$required=false,$disabled=false,$readonly=false,$trace=false)     {    // quick build for a relatively standard row (styling) containing a date with day, month and year in separate fields
        // $dayselect = $this->renderdropdown($dayfield,1,$autofocus,$disabled,$required,false,$days,'',false,'vols-form-select','',$trace,$optionsout,$dayfnum);
        // $monthselect = $this->renderdropdown($monthfield,1,$autofocus,$disabled,$required,false,$months,'',false,'vols-form-select','',$trace,$optionsout,$monthfnum);
        // $yearselect = $this->renderdropdown($yearfield,1,$autofocus,$disabled,$required,false,$years,'',false,'vols-form-select','',$trace,$optionsout,$yearfnum);
        // $selects = '<div class="vols-width-auto vols-vert-prompt-field-container"><div class="vols-form-vert-prompt-field">Day</div><div style="clear: both;"></div><div class="vols-form-vert-prompt-field">'.$dayselect.'</div></div>';"\n"; 
        // $selects .= '<div class="vols-width-auto vols-vert-prompt-field-container"><div class="vols-form-vert-prompt-field">Month</div><div style="clear: both;"></div><div class="vols-form-vert-prompt-field">'.$monthselect.'</div></div>';"\n"; 
        // $selects .= '<div class="vols-width-auto vols-vert-prompt-field-container"><div class="vols-form-vert-prompt-field">Year</div><div style="clear: both;"></div><div class="vols-form-vert-prompt-field">'.$yearselect.'</div></div>';"\n"; 
        $input = $this->renderdateinput($fieldname,$type,$placeholder,$min,$max,$step,$autofocus,$value,'',$fnum,$required,$readonly,$disabled);
        $row = $this->renderformrow($fieldname.'row',$fieldname.'prompt',$prompt,$required,'','','',$input,'','','',$hint,'','','','','','',''); 
        return $row;
     }
    public function renderinputrangerow($rowid,$prompt,$placeholder,$fromid,$toid,$fromval,$toval,$fromnum=0,$tonum=0,$required=false,$readonly=false,$disabled=false,$hint='',$rowclass='',$size=5,$max=5,$rows=1,$trailingtext="",$type="")     {
        $inputs = $this->rendertextinput((strlen($fromid)?$fromid:($rowid."fromval")),$size,$max,$placeholder,false,$fromval,'','',$fromnum,$required,$readonly,$disabled,$rows,$trailingtext,$type);
        $inputs .= "&nbsp;to&nbsp;".$this->rendertextinput((strlen($toid)?$toid:($rowid."toval")),$size,$max,$placeholder,false,$toval,'','',$tonum,$required,$readonly,$disabled,$rows,$trailingtext,$type);
        return $this->renderformrow($rowid,'',$prompt,$required,'','','',$inputs,'','','',$hint,'','','','','','','',$rowclass);
     }
    public function renderdaterangerow($rowid,$prompt,$fromdateid,$todateid,$fromfnum,$tofnum,$fromval,$toval,$frommin="",$frommax="",$tomin="",$tomax="",$fromstep="",$tostep="",$hint='',$inputclass='',$rowclass='',$required=false,$readonly=false,$disabled=false,$autofocus=false,$promptcellclass="",$inputcellclass="")     {
        $fdate = $this->renderdateinput((strlen($fromdateid)?$fromdateid:($rowid."fromdate")),"","",$frommin,$frommax,$fromstep,$autofocus=false,$fromval='',$inputclass="",$fromfnum=0,$required=false,$readonly=false,$disabled=false);
        $tdate = $this->renderdateinput((strlen($todateid)?$todateid:($rowid."todate")),"","",$tomin,$tomax,$tostep,$autofocus=false,$toval='',$inputclass="",$tofnum=0,$required=false,$readonly=false,$disabled=false);
        $dates = "From ".$fdate." to ".$tdate;
        return $this->renderformrow($rowid,'',$prompt,$required,$promptcellclass,'','',$dates,$inputcellclass,'','',$hint,'','','','','','','',$rowclass);
     }
    public function rendertimerangerow($rowid,$prompt,$placeholder,$fromtimeid,$totimeid,$fromval,$toval,$fromnum=0,$tonum=0,$required=false,$readonly=false,$disabled=false,$hint='',$rowclass='',$size=5,$max=5)    {
        $times = $this->rendertextinput(strlen($fromtimeid)?$fromtimeid:($rowid."fromtime"),$size,$max,$placeholder,false,$fromval,'','',$fromnum,$required,$readonly,$disabled,1,"","time");
        $times .= "&nbsp;to&nbsp;".$this->rendertextinput(strlen($totimeid)?$totimeid:($rowid."totime"),$size,$max,$placeholder,false,$toval,'','',$tonum,$required,$readonly,$disabled,1,"","time");
        return $this->renderformrow($rowid,'',$prompt,$required,'','','',$times ,'','','',$hint,'','','','','','','','');
     }
    public function renderlocationrangerow($rowid,$prompt,$fromloc,$toloc,$fromselect,$toselect,$fromval,$toval,$fromnum=0,$tonum=0,$required=false,$readonly=false,$disabled=false,$multiple=false,$hint='')     {
        $locs = $this->renderdropdown($fromloc,'1',$optionsout,false,$disabled,$required,$multiple,$fromselect,$fromval,false,'vols-form-select','',$trace,$fromnum);
        $locs .= "&nbsp;to&nbsp;". $this->renderdropdown($toloc,'1',$optionsout,false,$disabled,$required,$multiple,$toselect,$toval,false,'vols-form-select','',$trace,$tonum);
        return $this->renderformrow($rowid,'',$prompt,$required,'','','',$locs,'','','',$hint,'','','','','','','','');
     }
/*========================================================================================= RENDER COMMON TOP AND BOTTOM */
    private function prepareactionbuttons($buttonsdata,&$selectoptions,$trace=false) {
        if ( $this->trace || $trace ) { echo gtab(1)."Enter ".__METHOD__." : selectoption = '".$selectoptions."':<br>";  } //var_dump($values);
        if (is_array($buttonsdata["buttons"]) && ($buttoncount = count($buttonsdata["buttons"]))) { 
            $colcountclass = 'col'.$buttoncount+((int) $buttonsdata["noselection"]==false);
            $html  = ' <div class="vols-table vols-table-border">'."\n"; 
            $html .= '  <div class="vols-tablebody actioncontainer">'."\n";
            $html .= '   <div id="editcontainer" class="'.$colcountclass.'">'."\n";
            $inactive = $this->singlerecord?"":"inactive";
            if (!$this->singlerecord && array_key_exists("new",$buttonsdata["buttons"])) { 
                $html .= $this->makebutton("newrecord",$buttoncount,"N","ew"," doitbg");
                } 
            if (array_key_exists("edit",$buttonsdata["buttons"])) {
                $html .= $this->makebutton("editrecord",$buttoncount,"E","dit","doitbg"); 
                if (!$buttonsdata["noselection"]) {

                    $dropdown = $this->renderdropdown("recordselector",1,$selectoptions,true,false,false,false,$buttonsdata["names"],$buttonsdata["selecttext"],false,'vols-form-select','',$trace);
                    $html .= '    <div class="vols-tablecell vols-width-100 aligncenter ">'.$dropdown."</div>\n";//editbtncell
                }
            } 
            if (array_key_exists("reset",$buttonsdata["buttons"])) { 
                if (!(array_key_exists("new",$buttonsdata["buttons"]) && array_key_exists("edit",$buttonsdata["buttons"]))) { 
                    $ul="U";$name="ndo Changes";
                } else { 
                    $ul="R";$name="eset";
                }
                $html .= $this->makebutton("resetbutton",$buttoncount,$ul,$name,"neutralbg $inactive");
            } 
            if (array_key_exists("cancel",$buttonsdata["buttons"])) { 
                $html .= $this->makebutton("cancelbutton",$buttoncount,"C","ancel","dangerbg $inactive");
            } 
            if (!$this->singlerecord && array_key_exists("delete",$buttonsdata["buttons"])) { 
                $html .= $this->makebutton("deletebutton",$buttoncount,"D","elete","dangerbg");
            }
            if (array_key_exists("save",$buttonsdata["buttons"])) { 
                $save="ave";
                if (!(array_key_exists("new",$buttonsdata["buttons"]) && array_key_exists("edit",$buttonsdata["buttons"]))) {
                    $save .= "  Changes";
                }
                $html .= $this->makebutton("submitbutton",$buttoncount,"S",$save,"doitbg $inactive");
            } 
            $html .= '   </div> '."\n";
            $html .= '  </div>  '."\n";
            $html .= ' </div>  '."\n";
            $html .= $this->keyboardeventscript($buttonsdata["secondselectorname"]);
        }
        if ( $this->trace) { echo gtab(-1)."Leave ".__METHOD__."<br>"; }
        return $html;
     }
    private function makebutton($id,$buttoncount,$ul,$name,$classes){
        $cellwidthclass = 'vols-width-100';    //.match($buttoncount) {0=>"100",1=>"100",2=>"50",3=>"33",4=>"25",5=>"20",6=>"16"};
        $html  = "    <div class='vols-tablecell {$cellwidthclass} aligncenter'>\n";
        $html .= "     <div id='{$id}' class='clickable action  {$classes}' ><span class='underlined'>{$ul}</span>{$name}</div>\n";
        $html .= "    </div>\n";
        return $html;
     }
    public  function keyboardeventscript($secondselectorname){
        return  <<<HTML
                    <script>
                        jQuery(function () {
                            jQuery(document).off('keydown').on('keydown', function(event) {
                                processactionshortcutkeystroke(event); 
                            });
                        })
                        function processactionshortcutkeystroke(event) {
                            if (event.altKey) {  // SHORTCUT KEYS
                                let button = "";
                                const chr = String.fromCharCode(event.which).toUpperCase();
                                switch (chr) {
                                    case '1' :  jQuery("#recordselector").focus();event.stopPropagation();break;
                                    case '2' :  jQuery("#childselector").focus();event.stopPropagation();break;
                                    case 'N' :  button = "#newrecord";break;
                                    case 'E' :  button = "#editrecord";break;
                                    case 'R' :  button = "#resetbutton";break;
                                    case 'C' :  button = "#cancelbutton";break;
                                    case 'D' :  button = "#deletebutton";break;
                                    case 'S' :  button = "#submitbutton";break;
                                    case 'L' :  button = "#showrowsbtn";break;
                                    case 'M' :  button = "#menubutton";break;
                                    default:
                                }
                                if (button !="") {
                                    if (!$(button).hasClass("inactive")) {
                                        switch(chr) {
                                            case "M": menukeyboardhover(button);break;
                                            default : flashit(button);$(button).trigger("click");
                                        }
                                        event.stopPropagation();
                                    }
                                }
                            }
                        }
                </script>
            HTML;
     }      
    private function greetingrow ($menu='',$trace=false){
        if ($this->trace || $trace) { echo gtab(1)."Enter ".__METHOD__."<br>"; }
        $username = $this->session->getgreeting();
        $content  = '<div class="greetingcontainer">';
        $content .= "  <div class='greeting'>Welcome {$this->session->getgreeting()}</div>"; 
        $content .= $menu;
        $content .= '</div>';
        if ($this->trace|| $trace) { echo gtab(-1)."Leave ".__METHOD__."<br>"; }
        return $content;
     }
    public  function rendercommontop($id,$names,$subheading="",$subheadingclass="",$actionbuttons=[],
                                        $nextpage_num=0,$noactionrow=false,$noselection=false,$selecttext='',$menu='',
                                        $trace=false,$headingclass="",$secondselectorname="")     {
        if ($this->trace || $trace ) { echo gtab(1)."Enter ".__METHOD__.$selecttext."<br>"; }// var_dump($this->dataerrors);  echo "<br>";
        $heading["heading"] = !empty($this->headingoverride) ? $this->headingoverride : ($this->objectname.' Details');
        $this->headingoverride = '';
        $heading["headingclass"] = $headingclass;
        $heading["subheading"] = $subheading;
        $heading["subheadingclass"] = $subheadingclass;
        $actionbuttons = ["noactionrow"=>$noactionrow,"buttons"=>$actionbuttons,"noselection"=>$noselection,"names"=>$names,"selecttext"=>$selecttext,"secondselectorname" =>$secondselectorname];
        $top = $this->rendercommontopbrief($heading,$actionbuttons,$menu,$trace);
        if ( $this->trace || $trace) { echo gtab(-1)."Leave ".__METHOD__."<br>"; }
        return $top;
     } 
    public  function rendercommontopbrief($heading,$actionbuttons,$menu='',$trace=false)     {
        if ($this->trace || $trace ) { echo gtab(1)."Enter ".__METHOD__."<br>"; }// var_dump($this->dataerrors);  echo "<br>";
        $top = $this->renderproblemsheader();
        $top .='<div id="headercontainer" class="vols-table vols-table-border">'."\n"; //vols-table-minheight
        $top .='  <div id="formheader" class="vols-tablebody">'."\n"; 
        $actionrow = $actionbuttons["noactionrow"]?"":$this->prepareactionbuttons($actionbuttons,$selectoptions,$trace);
        $top .= $this->greetingrow($menu,false)."\n";
        // $top .='<!--   start of rendersectionheading  -->'."\n";   
        $top .= $this->rendersectionheading($heading["heading"],$heading["headingclass"]??"",$heading["subheading"]??"",$heading["subheadingclass"]??"","",$actionrow??"")."\n";
        // $top .='<!--   end of rendersectionheading  -->'."\n";   
        $top .='  </div>'."\n";   
        $top .='</div>'."\n";   
        if ( $this->trace || $trace) { echo gtab(-1)."Leave ".__METHOD__."<br>"; }
        return $top;
     }
    public  function rendercommonbottom($trace=false)     {   
        if ($this->trace || $trace) { echo gtab(1)."Enter ".__METHOD__."<br>"; }// var_dump($this->dataerrors);  echo "<br>";
        $formbottom = '';
        if ( $this->trace || $trace) { echo gtab(-1)."Leave ".__METHOD__."<br>"; }
        return $formbottom;
     }
} 