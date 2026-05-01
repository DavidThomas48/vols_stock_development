<?php
namespace app\view\form;
use \lib\StdLib as lib;
class StockClientForm extends \fw\view\form\StdCRUDForm {
    protected $trace       = false;
    protected $promptwidth = 30;
    protected $inputwidth  = 50;
    protected $hintwidth   = 20;
    protected $fields      = [];
    protected $names       = [];
    protected $parents     = [];
    protected $formname    = "stockclientform";
    protected $objname     = "Stock Client";

    public function __construct(protected FormComponent $component) {
        $this->singlerecord = false;
    }

    public function init($session, $data=[], $parents="", $trace=false) {
        parent::init($session, $data, $parents, $trace);
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
        $formfields  = '<div class="vols-stockmaint-header vols-stockclient-header">';
        $formfields .= '<span class="vols-stockmaint-icon">&#128101;</span>';
        $formfields .= '<span class="vols-stockmaint-text">Manage stock clients. Add, edit or delete the clients used in stock issue events.</span>';
        $formfields .= '</div>';
        $formfields .= $this->component->buildinputrow("name", 1, "", 'Name', '', 20, 64, true, '', '');
        $this->preparecommontop(false, false, '', '');
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
