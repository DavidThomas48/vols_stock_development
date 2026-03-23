<?php
namespace app\view\form;
use \lib\StdLib as lib;
class StockLevelReportForm extends \fw\view\form\StdCRUDForm {
    protected $trace = false;
    protected $promptwidth = 30;
    protected $inputwidth  = 40;
    protected $hintwidth   = 30;
    protected $fields      = [];
    protected $names       = [];
    protected $parents     = [];
    protected $formname    = "stocklevelreportform";
    protected $objname     = "Stock Level Report";

    public function __construct(protected FormComponent $component) {
        $this->singlerecord = false;
        // Read-only report — no action buttons
        $this->actionbuttons = [];
    }

    public function init($session, $data=[], $parents="", $trace=false) {
        parent::init($session, $data, $parents, $trace);
    }

    public function initfields() {
        $this->fields = array(
            "id"            => "",
            "Name"          => "",
            "Code"          => "",
            "category_id"   => "",
            "category_name" => "",
            "current_qty"   => "",
        );
    }

    protected function addtonames($row) {
        $this->names[$row["id"]] = $row["Name"];
    }

    public function buildinputs($rights=[], $trace=false) {
        $formfields  = '<div class="vols-stockreport-header">';
        $formfields .= '<span class="vols-stockreport-icon">&#128202;</span>';
        $formfields .= '<span class="vols-stockreport-headertext">Current stock levels. Quantities are calculated from the last stocktake plus deliveries minus stock used.</span>';
        $formfields .= '</div>';

        $formfields .= '<div class="vols-stockreport-table">';
        $formfields .= '<div class="vols-stockreport-colheadings">';
        $formfields .= '<div class="vols-stockreport-col-name">Item</div>';
        $formfields .= '<div class="vols-stockreport-col-code">Code</div>';
        $formfields .= '<div class="vols-stockreport-col-qty">Current Qty</div>';
        $formfields .= '</div>';

        $currentcategory = null;
        foreach ($this->alldata as $item) {
            $cat = htmlspecialchars($item["category_name"] ?? "Uncategorised");
            if ($cat !== $currentcategory) {
                $currentcategory = $cat;
                $formfields .= '<div class="vols-stockreport-category">'.$cat.'</div>';
            }
            $qty  = (float)($item["current_qty"] ?? 0);
            $name = htmlspecialchars($item["Name"]);
            $code = htmlspecialchars($item["Code"]);
            $qtyclass = $qty <= 0 ? "vols-stockreport-qty vols-stockreport-qty-zero"
                                  : "vols-stockreport-qty vols-stockreport-qty-ok";
            $formfields .= '<div class="vols-stockreport-row">';
            $formfields .= '<div class="vols-stockreport-col-name">'.$name.'</div>';
            $formfields .= '<div class="vols-stockreport-col-code">'.$code.'</div>';
            $formfields .= '<div class="'.$qtyclass.'">'.$qty.'</div>';
            $formfields .= '</div>';
        }

        $formfields .= '</div>';

        // noselection=true, noactionrow=true — pure read-only display
        $this->preparecommontop(true, true, '', '');
        return $formfields;
    }

    public function formscript() {
        return "function formhaserrors() { return 0; }\nfunction displayselectedrecord() {}";
    }
}
