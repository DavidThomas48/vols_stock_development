<?php
namespace app\view\form;
use \lib\StdLib as lib;
class StockSupplierForm extends \fw\view\form\StdCRUDForm {
    protected $trace       = false;
    protected $promptwidth = 30;
    protected $inputwidth  = 50;
    protected $hintwidth   = 20;
    protected $fields      = [];
    protected $names       = [];
    protected $parents     = [];   // populated with all stock_categories by manager
    protected $formname    = "stocksupplierform";
    protected $objname     = "Stock Supplier";

    public function __construct(protected FormComponent $component) {
        $this->singlerecord = false;
    }

    public function init($session, $data=[], $parents=[], $trace=false) {
        parent::init($session, $data, $parents, $trace);
    }

    public function initfields() {
        // Only the base fields — category link fields are added dynamically by
        // the manager and serialised into the hidden data by buildhiddendatafields().
        $this->fields = array(
            "id"   => "",
            "name" => "",
        );
    }

    protected function addtonames($row) {
        $this->names[$row["id"]] = $row["name"];
    }

    public function buildinputs($rights=[], $trace=false) {
        $formfields  = '<div class="vols-stockmaint-header vols-stocksupplier-header">';
        $formfields .= '<span class="vols-stockmaint-icon">&#128666;</span>';
        $formfields .= '<span class="vols-stockmaint-text">Manage stock suppliers. Add or edit suppliers and specify which categories each supplier provides.</span>';
        $formfields .= '</div>';

        $formfields .= $this->component->buildinputrow("name", 1, "", 'Name', '', 20, 64, true, '', '');

        // Category checkboxes (fnum starts at 2, after id=0 and name=1).
        // The order MUST match the order in which StockSupplierManager::getallrecords()
        // adds the "stockcategory{id}" fields to each supplier record.
        $fn = 2;
        $hiddencheckboxes = '';
        if (!empty($this->parents)) {
            $formfields .= $this->component->rendersectionheading("Categories supplied");
            $this->component->setwidths(35, 60, 5);
            foreach ($this->parents as $cat) {
                $fieldname = "link_stockcategory".$cat["id"];
                $formfields .= $this->component->buildcheckboxrow(
                    $fieldname,         // field name
                    $cat["id"],         // value
                    "",                 // default
                    true,               // enabled
                    $fn++,              // fnum
                    $cat["Name"],       // label
                    '',                 // hint
                    false, false, false, false,
                    'vols-tablerow',    // row class
                    []                  // row data
                );
                // Hidden field ensures unchecked boxes appear in POST as false
                $hiddencheckboxes .= '<input type="hidden" name="'.$fieldname.'" value="false" />';
            }
        }

        $this->preparecommontop(false, false, $hiddencheckboxes, '');
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
