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
    protected $parents     = [];
    protected $formname    = "stocksupplierform";
    protected $objname     = "Stock Supplier";

    public function __construct(protected FormComponent $component) {
        $this->singlerecord = false;
    }

    public function init($session, $data=[], $parents=[], $trace=false) {
        parent::init($session, $data, $parents, $trace);
    }

    public function initfields() {
        $this->fields = array(
            "id"                   => "",
            "name"                 => "",
            "supplier_category_id" => "",
        );
    }

    protected function addtonames($row) {
        $this->names[$row["id"]] = $row["name"];
    }

    public function buildinputs($rights=[], $trace=false) {
        $suppliercats = $this->parents['supplier_categories'] ?? [];
        $stockcats    = $this->parents['stock_categories']    ?? [];

        $formfields  = '<div class="vols-stockmaint-header vols-stocksupplier-header">';
        $formfields .= '<span class="vols-stockmaint-icon">&#128666;</span>';
        $formfields .= '<span class="vols-stockmaint-text">Manage stock suppliers. Add or edit suppliers and specify which categories each supplier provides.</span>';
        $formfields .= '</div>';

        $formfields .= $this->component->buildinputrow("name", 1, "", 'Name', '', 20, 64, true, '', '');

        // Supplier category dropdown (nullable)
        $optn    = [];
        $catdata = [];
        if (!empty($suppliercats)) {
            $catdata = array_combine(
                array_column($suppliercats, "id"),
                array_column($suppliercats, "name")
            );
        }
        $formfields .= $this->component->buildselectrow(
            "supplier_category_id", 2, 1, 'Supplier Category', $catdata, '', $optn,
            false, false, false, false, '', false
        );

        // Stock category checkboxes — fnum starts at 3 now
        $fn = 3;
        $hiddencheckboxes = '';
        if (!empty($stockcats)) {
            $formfields .= $this->component->rendersectionheading("Categories supplied");
            $this->component->setwidths(35, 60, 5);
            $oddeven = 'vols-row-odd';
            foreach ($stockcats as $cat) {
                $fieldname = "link_stockcategory".$cat["id"];
                $formfields .= $this->component->buildcheckboxrow(
                    $fieldname,
                    $cat["id"],
                    "",
                    true,
                    $fn++,
                    $cat["Name"],
                    '',
                    false, false, false, false,
                    'vols-tablerow ' . $oddeven,
                    []
                );
                $hiddencheckboxes .= '<input type="hidden" name="'.$fieldname.'" value="false" />';
                $oddeven = ($oddeven === 'vols-row-odd') ? 'vols-row-even' : 'vols-row-odd';
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
