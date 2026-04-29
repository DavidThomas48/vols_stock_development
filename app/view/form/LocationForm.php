<?php
namespace app\view\form;
use \lib\StdLib as lib;
class LocationForm extends \fw\view\form\StdCRUDForm {
    protected $trace       = false;
    protected $promptwidth = 30;
    protected $inputwidth  = 50;
    protected $hintwidth   = 20;
    protected $fields      = [];
    protected $names       = [];
    protected $parents     = [];
    protected $formname    = "locationform";
    protected $objname     = "Location";
    protected $locationid;

    public function __construct(protected FormComponent $component) {
        $this->singlerecord = false;
    }

    public function init($session, $data=[], $parents="", $trace=false) {
        parent::init($session, $data, $parents, $trace);
        $this->locationid = $this->requestdata["id"] ?? "";
    }

    public function initfields() {
        $this->fields = array(
            "id"   => "",
            "name" => "",
        );
    }

    protected function addtonames($row) {
        $this->names[$row["id"]] = $row["name"];
    }

    public function buildinputs($rights=[], $trace=false) {
        $formfields  = '<div class="vols-stockmaint-header vols-location-header">';
        $formfields .= '<span class="vols-stockmaint-icon">&#128205;</span>';
        $formfields .= '<span class="vols-stockmaint-text">Manage locations. Add, edit or delete the physical locations used in stock events.</span>';
        $formfields .= '</div>';
        $formfields .= $this->component->buildinputrow("name", 1, "", 'Name', '', 20, 64, true, '', '');
        $this->preparecommontop(false, false, '', $this->locationid);
        return $formfields;
    }

    public function formscript() {
        $script = $this->vols_masterscript(
            $this->formname,
            $this->objname,
            true,   // idselection
            true,   // adjustnamerow
            true,   // updatefields
            false,  // inclmulti
            '',     // postajaxscript
            '',     // postloadfieldsscript
            '',     // postclearfieldsscript
            false,  // trace
            '',     // multisubmit
            ''      // presavescript
        );
        $script .= <<<JS
            function formhaserrors() {
                let errors = 0;
                if (!jQuery("#name").val()) {
                    jQuery("#namerow_error").html("(This is a required field.)");
                    errors++;
                }
                return errors;
            }
            function displayselectedrecord() {}
        JS;
        return $script;
    }
}
