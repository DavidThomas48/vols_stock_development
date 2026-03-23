<?php
namespace app\view\form;
use \lib\StdLib as lib;
class StockForm extends \fw\view\form\StdCRUDForm {
    protected $trace = false;
    protected $promptwidth = 30;
    protected $inputwidth  = 40;
    protected $hintwidth   = 30;
    protected $fields      = [];
    protected $names       = [];
    protected $parents     = [];
    protected $formname    = "stockform";
    protected $objname     = "Stock Item";
    protected $stockid;

    public function __construct(protected FormComponent $component) {
        $this->singlerecord = false;
    }

    public function init($session, $data=[], $parents="", $trace=false) {
        parent::init($session, $data, $parents, $trace);
        $this->stockid = $this->requestdata["id"] ?? "";
    }

    public function initfields() {
        $this->fields = array(
            "id"          => "",
            "Name"        => "",
            "Code"        => "",
            "category_id" => "",
        );
    }

    protected function addtonames($row) {
        $this->names[$row["id"]] = $row["Name"];
    }

    public function buildinputs($rights=[], $trace=false) {
        $categorydata = array_combine(
            array_column($this->parents, "id"),
            array_column($this->parents, "Name")
        );
        $optn = [];
        $formfields  = '<div class="vols-stockmaint-header vols-stockitem-header">';
        $formfields .= '<span class="vols-stockmaint-icon">&#128230;</span>';
        $formfields .= '<span class="vols-stockmaint-text">Manage stock items. Add, edit or delete items that can be tracked in inventory.</span>';
        $formfields .= '</div>';
        $formfields .= $this->component->buildinputrow("Name", 1, "", 'Name', '', 20, 64, true, '', '');
        $formfields .= $this->component->buildinputrow("Code", 2, "", 'Code', 'Short code for this item', 20, 20, true, '', '');
        $formfields .= $this->component->buildselectrow("category_id", 3, 1, 'Category', $categorydata, "", $optn, false, false, true, false, '', false);
        $this->preparecommontop(false, false, '', $this->stockid);
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
                if (!jQuery("#Code").val()) {
                    jQuery("#Coderow_error").html("(This is a required field.)");
                    errors++;
                }
                if (!jQuery("#category_id").val()) {
                    jQuery("#category_idrow_error").html("(Please select a category.)");
                    errors++;
                }
                return errors;
            }
            function displayselectedrecord() {}
        JS;
        return $script;
    }
}
