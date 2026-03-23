<?php
namespace app\view\form;
use \lib\StdLib as lib;
class StockCategoryForm extends \fw\view\form\StdCRUDForm {
    protected $trace = false;
    protected $promptwidth = 30;
    protected $inputwidth  = 40;
    protected $hintwidth   = 30;
    protected $fields      = [];
    protected $names       = [];
    protected $parents     = [];
    protected $formname    = "stockcategoryform";
    protected $objname     = "Stock Category";
    protected $categoryid;

    public function __construct(protected FormComponent $component) {
        $this->singlerecord = false;
    }

    public function init($session, $data=[], $parents="", $trace=false) {
        parent::init($session, $data, $parents, $trace);
        $this->categoryid = $this->requestdata["id"] ?? "";
    }

    public function initfields() {
        $this->fields = array(
            "id"   => "",
            "Name" => "",
        );
    }

    protected function addtonames($row) {
        $this->names[$row["id"]] = $row["Name"];
    }

    public function buildinputs($rights=[], $trace=false) {
        $formfields  = '<div class="vols-stockmaint-header vols-stockcategory-header">';
        $formfields .= '<span class="vols-stockmaint-icon">&#9776;</span>';
        $formfields .= '<span class="vols-stockmaint-text">Manage stock categories. Add, edit or delete categories used to group stock items.</span>';
        $formfields .= '</div>';
        $formfields .= $this->component->buildinputrow("Name", 1, "", 'Name', '', 20, 64, true, '', '');
        $this->preparecommontop(false, false, '', $this->categoryid);
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
            '',     // postupdatescript
            '',     // postclearfieldsscript
            false,  // trace
            '',     // multisubmit
            ''      // presavescript
        );
        $script .= <<<JS
            function formhaserrors() {
                let errors = 0;
                if (!jQuery("#Name").val()) {
                    jQuery("#Namerow_error").html("(This is a required field.)");
                    errors++;
                }
                return errors;
            }
            function displayselectedrecord() {}
        JS;
        return $script;
    }
}
