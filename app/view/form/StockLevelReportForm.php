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
            "id"                => "",
            "Name"              => "",
            "Code"              => "",
            "category_id"       => "",
            "category_name"     => "",
            "stocktake_date"    => "",
            "stocktake_qty"     => "",
            "deliveries_since"  => "",
            "transfers_since"   => "",
            "adjustments_since" => "",
            "issues_since"      => "",
            "current_qty"       => "",
        );
    }

    protected function addtonames($row) {
        $this->names[$row["id"]] = $row["Name"];
    }

    public function buildinputs($rights=[], $trace=false) {
        $locations   = $this->parents['locations']   ?? [];
        $location_id = $this->parents['location_id'] ?? '';
        $loc_name    = '';
        foreach ($locations as $loc) {
            if ((string)$loc['id'] === (string)$location_id) { $loc_name = $loc['name']; break; }
        }
        $header_text = $location_id
            ? 'Current stock levels at <strong>' . htmlspecialchars($loc_name) . '</strong>. Each row shows the last stocktake at that location as the baseline.'
            : 'Current stock levels across all locations. Each row shows the last stocktake as the baseline, then deliveries added and stock used or damaged since that date.';

        $formfields  = '<div class="vols-stockreport-header">';
        $formfields .= '<span class="vols-stockreport-icon">&#128202;</span>';
        $formfields .= '<span class="vols-stockreport-headertext">' . $header_text . '</span>';
        $formfields .= '</div>';

        $formfields .= '<div class="vols-stockreport-filter">';
        $formfields .= '<label class="vols-stockreport-filter-label" for="location_id">Location:</label>';
        $formfields .= '<select id="location_id" name="location_id" class="vols-stockreport-locselect" onchange="this.form.submit()">';
        $formfields .= '<option value="">All locations</option>';
        foreach ($locations as $loc) {
            $sel = ((string)$loc['id'] === (string)$location_id) ? ' selected' : '';
            $formfields .= '<option value="' . (int)$loc['id'] . '"' . $sel . '>'
                         . htmlspecialchars($loc['name']) . '</option>';
        }
        $formfields .= '</select>';
        $formfields .= '</div>';

        // Build JS data array for CSV export
        $jsrows = [];
        foreach ($this->alldata as $item) {
            $jsrows[] = json_encode([
                'category' => $item['category_name'] ?? 'Uncategorised',
                'name'     => $item['Name'],
                'code'     => $item['Code'],
                'stdate'   => ($dtc = $item['stocktake_date'] ? \DateTime::createFromFormat('Y-m-d H:i:s', $item['stocktake_date']) : false) ? $dtc->format('d-m-Y H:i') : '',
                'stqty'    => (float)($item['stocktake_qty']     ?? 0),
                'deliv'    => (float)($item['deliveries_since']  ?? 0),
                'trans'    => (float)($item['transfers_since']   ?? 0),
                'adj'      => (float)($item['adjustments_since'] ?? 0),
                'issues'   => (float)($item['issues_since']      ?? 0),
                'current'  => (float)($item['current_qty']       ?? 0),
            ]);
        }
        $loc_js = json_encode($loc_name ?: 'all-locations');
        $formfields .= '<script>var stockReportData=[' . implode(',', $jsrows) . '];var stockReportLocation=' . $loc_js . ';</script>';

        $formfields .= '<div class="vols-stockreport-toolbar">';
        $formfields .= '<button type="button" class="vols-stockreport-csvbtn" onclick="downloadStockCSV()">&#8681; Export CSV</button>';
        $formfields .= '</div>';

        $show_stdate  = !empty($location_id);
        $table_class  = 'vols-stockreport-table' . ($show_stdate ? '' : ' vols-stockreport-table--noloc');

        $formfields .= '<div class="vols-stockreport-table-wrap">';
        $formfields .= '<div class="' . $table_class . '">';
        $formfields .= '<div class="vols-stockreport-colheadings">';
        $formfields .= '<div class="vols-stockreport-col-name">Item</div>';
        $formfields .= '<div class="vols-stockreport-col-code">Code</div>';
        if ($show_stdate) $formfields .= '<div class="vols-stockreport-col-stdate">Last<br>Stocktake</div>';
        $formfields .= '<div class="vols-stockreport-col-num">Stocktake<br>Qty</div>';
        $formfields .= '<div class="vols-stockreport-col-num">+<br>Deliveries</div>';
        $formfields .= '<div class="vols-stockreport-col-num">&plusmn;<br>Transfers</div>';
        $formfields .= '<div class="vols-stockreport-col-num">&plusmn;<br>Adjustments</div>';
        $formfields .= '<div class="vols-stockreport-col-num">&minus;<br>Issues</div>';
        $formfields .= '<div class="vols-stockreport-col-num">=<br>Current</div>';
        $formfields .= '</div>';

        $currentcategory = null;
        foreach ($this->alldata as $item) {
            $cat = htmlspecialchars($item["category_name"] ?? "Uncategorised");
            if ($cat !== $currentcategory) {
                $currentcategory = $cat;
                $formfields .= '<div class="vols-stockreport-category">'.$cat.'</div>';
            }
            $qty     = (float)($item["current_qty"]       ?? 0);
            $stqty   = (float)($item["stocktake_qty"]     ?? 0);
            $deliv   = (float)($item["deliveries_since"]  ?? 0);
            $trans   = (float)($item["transfers_since"]   ?? 0);
            $adj     = (float)($item["adjustments_since"] ?? 0);
            $issues  = (float)($item["issues_since"]      ?? 0);
            $dt      = $item["stocktake_date"] ? \DateTime::createFromFormat('Y-m-d H:i:s', $item["stocktake_date"]) : false;
            $stdate  = $dt ? $dt->format('d-m-Y H:i') : '—';
            $name    = htmlspecialchars($item["Name"]);
            $code    = htmlspecialchars($item["Code"]);
            $qtyclass = $qty <= 0 ? "vols-stockreport-qty vols-stockreport-qty-zero"
                                  : "vols-stockreport-qty vols-stockreport-qty-ok";
            $formfields .= '<div class="vols-stockreport-row">';
            $formfields .= '<div class="vols-stockreport-col-name">'.$name.'</div>';
            $formfields .= '<div class="vols-stockreport-col-code">'.$code.'</div>';
            if ($show_stdate) $formfields .= '<div class="vols-stockreport-col-stdate">'.$stdate.'</div>';
            $formfields .= '<div class="vols-stockreport-col-num">'.$stqty.'</div>';
            $formfields .= '<div class="vols-stockreport-col-num vols-stockreport-deliv">'.($deliv > 0 ? '+'.$deliv : $deliv).'</div>';
            $formfields .= '<div class="vols-stockreport-col-num vols-stockreport-trans">'.($trans > 0 ? '+'.$trans : $trans).'</div>';
            $formfields .= '<div class="vols-stockreport-col-num vols-stockreport-adj">'.($adj > 0 ? '+'.$adj : $adj).'</div>';
            $formfields .= '<div class="vols-stockreport-col-num vols-stockreport-issues">'.($issues > 0 ? '&minus;'.$issues : $issues).'</div>';
            $formfields .= '<div class="'.$qtyclass.'">'.$qty.'</div>';
            $formfields .= '</div>';
        }

        $formfields .= '</div>'; // vols-stockreport-table
        $formfields .= '</div>'; // vols-stockreport-table-wrap

        // noselection=true, noactionrow=true — pure read-only display
        $this->preparecommontop(true, true, '', '');
        return $formfields;
    }

    public function formscript() {
        return "function formhaserrors() { return 0; }\n"
             . "function displayselectedrecord() {}\n"
             . "function downloadStockCSV() {\n"
             . "    var rows = [['Category','Item','Code','Last Stocktake','Stocktake Qty','Deliveries','Transfers','Adjustments','Issues','Current Qty']];\n"
             . "    for (var i = 0; i < stockReportData.length; i++) {\n"
             . "        var r = stockReportData[i];\n"
             . "        rows.push([r.category, r.name, r.code, r.stdate, r.stqty, r.deliv, r.trans, r.adj, r.issues, r.current]);\n"
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
             . "    var locSlug = stockReportLocation.toLowerCase().replace(/[^a-z0-9]+/g,'-').replace(/^-|-$/g,'');\n"
             . "    a.download = 'stock-levels-' + locSlug + '-' + ts + '.csv';\n"
             . "    a.click();\n"
             . "    URL.revokeObjectURL(a.href);\n"
             . "}\n";
    }
}
