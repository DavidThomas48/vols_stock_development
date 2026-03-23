<?php
namespace app\view\form;
use \lib\StdLib as lib;
class StockoutForm extends \fw\view\form\StdCRUDForm {
    protected $trace = false;
    protected $promptwidth = 30;
    protected $inputwidth  = 40;
    protected $hintwidth   = 30;
    protected $fields      = [];
    protected $names       = [];
    protected $parents     = [];
    protected $formname    = "stockoutform";
    protected $objname     = "Stock Usage";
    protected $movementid;

    public function __construct(protected FormComponent $component) {
        $this->singlerecord = false;
        // Only allow adding new records — no edit or delete of historical records
        $this->actionbuttons = ["new"=>1,"save"=>1,"cancel"=>1,"reset"=>1];
    }

    public function init($session, $data=[], $parents="", $trace=false) {
        parent::init($session, $data, $parents, $trace);
        $this->movementid = $this->requestdata["id"] ?? "";
    }

    public function initfields() {
        // Fields must match the order returned by getmovementsbytype JOIN query
        $this->fields = array(
            "id"            => "",
            "stock_id"      => "",
            "movement_type" => "",
            "qty"           => "",
            "unit"          => "",
            "unit_qty"      => "1",
            "movement_date" => "",
            "stock_name"    => "",
        );
    }

    protected function addtonames($row) {
        $date = substr($row["movement_date"] ?? '', 0, 10);
        $this->names[$row["id"]] = "{$date} – {$row['stock_name']} ({$row['qty']} {$row['unit']})";
    }

    public function buildinputs($rights=[], $trace=false) {
        $stockdata = array_combine(
            array_column($this->parents, "id"),
            array_column($this->parents, "Name")
        );
        $optn = [];

        // Hidden fields set automatically on save
        $formfields  = '<input type="hidden" name="movement_type" id="movement_type" value="stockout" />'."\n";
        $formfields .= '<input type="hidden" name="movement_date" id="movement_date" value="" />'."\n";
        $formfields .= '<input type="hidden" name="stock_name"    id="stock_name"    value="" />'."\n";

        $formfields .= '<div class="vols-movement-header vols-stockout-header">';
        $formfields .= '<span class="vols-movement-icon">&#8681;</span>';
        $formfields .= '<span class="vols-movement-text">Record stock used. Select the item, enter the quantity taken from inventory, and save.</span>';
        $formfields .= '</div>';

        $formfields .= '<div class="vols-movement-fields">';
        $formfields .= $this->component->buildselectrow("stock_id", 1, 1, 'Stock Item', $stockdata, "", $optn, false, false, true, false, '', false);
        $this->component->setwidths(30, 15, 55);
        $formfields .= $this->component->buildinputrow("qty",      3, "", 'Quantity used',  'Number of units taken from stock', 10, 10, true, '', '');
        $formfields .= $this->component->buildinputrow("unit",     4, "", 'Unit',            'e.g. kg, can, box',                10, 24, false, '', '');
        $formfields .= $this->component->buildinputrow("unit_qty", 5, "", 'Unit size',       'Items per unit (default 1)',        10, 6,  false, '', '');
        $this->resetwidths();
        $formfields .= '</div>';

        $this->preparecommontop(false, false, '', $this->movementid);
        return $formfields;
    }

    public function formscript() {
        $presavescript = <<<JS
            jQuery("#movement_date").val(new Date().toISOString().slice(0,19).replace('T',' '));
            jQuery("#formerror").html("");
        JS;

        $script = $this->vols_masterscript(
            $this->formname,
            $this->objname,
            true,   // idselection — show dropdown of existing stockout records
            false,  // adjustnamerow
            true,   // updatefields
            false,  // inclmulti
            '',     // postajaxscript
            '',     // postupdatescript
            '',     // postclearfieldsscript
            false,  // trace
            '',     // multisubmit
            $presavescript
        );
        $script .= <<<JS
            function formhaserrors() {
                let errors = 0;
                if (!jQuery("#stock_id").val()) {
                    jQuery("#stock_idrow_error").html("(Please select a stock item.)");
                    errors++;
                }
                if (!jQuery("#qty").val()) {
                    jQuery("#qtyrow_error").html("(This is a required field.)");
                    errors++;
                }
                return errors;
            }
            function displayselectedrecord() {}
        JS;
        return $script;
    }
}
