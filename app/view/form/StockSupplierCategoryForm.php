<?php
namespace app\view\form;
use \lib\StdLib as lib;
class StockSupplierCategoryForm extends \fw\view\form\StdCRUDForm {
    protected $trace       = false;
    protected $promptwidth = 30;
    protected $inputwidth  = 40;
    protected $hintwidth   = 30;
    protected $fields      = [];
    protected $names       = [];
    protected $parents     = [];
    protected $formname    = "stocksuppliercategoryform";
    protected $objname     = "Supplier Category";

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
        $formfields  = '<div class="vols-stockmaint-header">';
        $formfields .= '<span class="vols-stockmaint-icon">&#127968;</span>';
        $formfields .= '<span class="vols-stockmaint-text">Manage supplier categories. Add, edit or delete categories used to classify suppliers.</span>';
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
