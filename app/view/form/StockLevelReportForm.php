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
            "id"               => "",
            "Name"             => "",
            "Code"             => "",
            "category_id"      => "",
            "category_name"    => "",
            "stocktake_date"   => "",
            "stocktake_qty"    => "",
            "deliveries_since" => "",
            "stockouts_since"  => "",
            "damaged_since"    => "",
            "current_qty"      => "",
        );
    }

    protected function addtonames($row) {
        $this->names[$row["id"]] = $row["Name"];
    }

    public function buildinputs($rights=[], $trace=false) {
        $formfields  = '<div class="vols-stockreport-header">';
        $formfields .= '<span class="vols-stockreport-icon">&#128202;</span>';
        $formfields .= '<span class="vols-stockreport-headertext">Current stock levels. Each row shows the last stocktake as the baseline, then deliveries added and stock used or damaged since that date.</span>';
        $formfields .= '</div>';

        // Build JS data array for CSV export
        $jsrows = [];
        foreach ($this->alldata as $item) {
            $jsrows[] = json_encode([
                'category' => $item['category_name'] ?? 'Uncategorised',
                'name'     => $item['Name'],
                'code'     => $item['Code'],
                'stdate'   => $item['stocktake_date'] ? date('d-m-Y', strtotime($item['stocktake_date'])) : '',
                'stqty'    => (float)($item['stocktake_qty']    ?? 0),
                'deliv'    => (float)($item['deliveries_since'] ?? 0),
                'used'     => (float)($item['stockouts_since']  ?? 0),
                'damaged'  => (float)($item['damaged_since']    ?? 0),
                'current'  => (float)($item['current_qty']      ?? 0),
            ]);
        }
        $formfields .= '<script>var stockReportData=[' . implode(',', $jsrows) . '];</script>';

        $formfields .= '<div class="vols-stockreport-toolbar">';
        $formfields .= '<button type="button" class="vols-stockreport-csvbtn" onclick="downloadStockCSV()">&#8681; Export CSV</button>';
        $formfields .= '</div>';

        $formfields .= '<div class="vols-stockreport-table">';
        $formfields .= '<div class="vols-stockreport-colheadings">';
        $formfields .= '<div class="vols-stockreport-col-name">Item</div>';
        $formfields .= '<div class="vols-stockreport-col-code">Code</div>';
        $formfields .= '<div class="vols-stockreport-col-stdate">Last Stocktake</div>';
        $formfields .= '<div class="vols-stockreport-col-num">Stocktake Qty</div>';
        $formfields .= '<div class="vols-stockreport-col-num">+ Deliveries</div>';
        $formfields .= '<div class="vols-stockreport-col-num">&minus; Used</div>';
        $formfields .= '<div class="vols-stockreport-col-num">&minus; Damaged</div>';
        $formfields .= '<div class="vols-stockreport-col-num">= Current</div>';
        $formfields .= '</div>';

        $currentcategory = null;
        foreach ($this->alldata as $item) {
            $cat = htmlspecialchars($item["category_name"] ?? "Uncategorised");
            if ($cat !== $currentcategory) {
                $currentcategory = $cat;
                $formfields .= '<div class="vols-stockreport-category">'.$cat.'</div>';
            }
            $qty      = (float)($item["current_qty"]      ?? 0);
            $stqty    = (float)($item["stocktake_qty"]    ?? 0);
            $deliv    = (float)($item["deliveries_since"] ?? 0);
            $used     = (float)($item["stockouts_since"]  ?? 0);
            $damaged  = (float)($item["damaged_since"]    ?? 0);
            $stdate   = $item["stocktake_date"] ? date('d-m-Y', strtotime($item["stocktake_date"])) : '—';
            $name     = htmlspecialchars($item["Name"]);
            $code     = htmlspecialchars($item["Code"]);
            $qtyclass = $qty <= 0 ? "vols-stockreport-qty vols-stockreport-qty-zero"
                                  : "vols-stockreport-qty vols-stockreport-qty-ok";
            $formfields .= '<div class="vols-stockreport-row">';
            $formfields .= '<div class="vols-stockreport-col-name">'.$name.'</div>';
            $formfields .= '<div class="vols-stockreport-col-code">'.$code.'</div>';
            $formfields .= '<div class="vols-stockreport-col-stdate">'.$stdate.'</div>';
            $formfields .= '<div class="vols-stockreport-col-num">'.$stqty.'</div>';
            $formfields .= '<div class="vols-stockreport-col-num vols-stockreport-deliv">'.($deliv > 0 ? '+'.$deliv : $deliv).'</div>';
            $formfields .= '<div class="vols-stockreport-col-num vols-stockreport-used">'.($used > 0 ? '&minus;'.$used : $used).'</div>';
            $formfields .= '<div class="vols-stockreport-col-num vols-stockreport-dmgd">'.($damaged > 0 ? '&minus;'.$damaged : $damaged).'</div>';
            $formfields .= '<div class="'.$qtyclass.'">'.$qty.'</div>';
            $formfields .= '</div>';
        }

        $formfields .= '</div>';

        // noselection=true, noactionrow=true — pure read-only display
        $this->preparecommontop(true, true, '', '');
        return $formfields;
    }

    public function formscript() {
        return "function formhaserrors() { return 0; }\n"
             . "function displayselectedrecord() {}\n"
             . "function downloadStockCSV() {\n"
             . "    var rows = [['Category','Item','Code','Last Stocktake','Stocktake Qty','Deliveries In','Used','Damaged','Current Qty']];\n"
             . "    for (var i = 0; i < stockReportData.length; i++) {\n"
             . "        var r = stockReportData[i];\n"
             . "        rows.push([r.category, r.name, r.code, r.stdate, r.stqty, r.deliv, r.used, r.damaged, r.current]);\n"
             . "    }\n"
             . "    var csv = rows.map(function(row) {\n"
             . "        return row.map(function(v) {\n"
             . "            var s = String(v);\n"
             . "            return s.indexOf(',') !== -1 || s.indexOf('\"') !== -1 ? '\"' + s.replace(/\"/g, '\"\"') + '\"' : s;\n"
             . "        }).join(',');\n"
             . "    }).join('\\r\\n');\n"
             . "    var blob = new Blob([csv], {type: 'text/csv'});\n"
             . "    var a = document.createElement('a');\n"
             . "    a.href = URL.createObjectURL(blob);\n"
             . "    var d = new Date();\n"
             . "    var pad = function(n){return String(n).padStart(2,'0');};\n"
             . "    var ts = d.getFullYear() + pad(d.getMonth()+1) + pad(d.getDate()) + '-' + pad(d.getHours()) + pad(d.getMinutes());\n"
             . "    a.download = 'stock-levels-' + ts + '.csv';\n"
             . "    a.click();\n"
             . "    URL.revokeObjectURL(a.href);\n"
             . "}\n";
    }
}
