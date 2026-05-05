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
        $report_type = $this->parents['report_type'] ?? 'stocklevels';
        $as_at_mysql = $this->parents['as_at']       ?? '';   // 'YYYY-MM-DD HH:MM:SS' or ''
        $as_at_html5 = $as_at_mysql
            ? substr(str_replace(' ', 'T', $as_at_mysql), 0, 16)  // 'YYYY-MM-DDTHH:MM'
            : '';
        $as_at_disp  = '';
        if ($as_at_html5) {
            $dt = \DateTime::createFromFormat('Y-m-d\TH:i', $as_at_html5);
            $as_at_disp = $dt ? $dt->format('d-m-Y H:i') : $as_at_html5;
        }

        $loc_name = '';
        foreach ($locations as $loc) {
            if ((string)$loc['id'] === (string)$location_id) { $loc_name = $loc['name']; break; }
        }

        $loc_phrase = $location_id
            ? 'at <strong>' . htmlspecialchars($loc_name) . '</strong>'
            : 'across all locations';
        $time_phrase = $as_at_disp
            ? 'as at <strong>' . htmlspecialchars($as_at_disp) . '</strong>'
            : 'as at the current time';
        $header_text = 'Stock levels ' . $loc_phrase . ', ' . $time_phrase . '.';
        if (!$as_at_disp) {
            $header_text .= $location_id
                ? ' Each row shows the last stocktake at that location as the baseline.'
                : ' Each row shows the last stocktake per location as the baseline.';
        }

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

        $formfields .= '<div class="vols-stockreport-filter">';
        $formfields .= '<label class="vols-stockreport-filter-label" for="as_at">As at:</label>';
        $formfields .= '<input type="datetime-local" id="as_at" name="as_at"'
                     . ' class="vols-stockreport-asat"'
                     . ($as_at_html5 ? ' value="' . htmlspecialchars($as_at_html5) . '"' : '')
                     . ' onchange="this.form.submit()">';
        $formfields .= '<button type="button" class="vols-stockreport-filter-clear"'
                     . ' onclick="resetAsAtToNow()">Now</button>';
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
        $loc_js   = json_encode($loc_name ?: 'all-locations');
        $as_at_js = json_encode($as_at_disp ?: '');
        $formfields .= '<script>var stockReportData=[' . implode(',', $jsrows) . '];'
                     . 'var stockReportLocation=' . $loc_js . ';'
                     . 'var stockReportAsAt=' . $as_at_js . ';</script>';

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

        $this->component->setheadingoverride("Stock Reports");
        $rtype  = '<form id="reporttypeform" method="POST">';
        $rtype .= '<input type="hidden" name="p" value="' . (int)$this->pagenum . '">';
        $rtype .= '<label class="vols-stockreport-filter-label">Report:</label>';
        $rtype .= '<select name="report_type" class="vols-stockreport-reportselect" onchange="this.form.submit()">';
        $rtype .= '<option value="stocklevels" selected>Stock Levels</option>';
        $rtype .= '<option value="stocktakevariance">Stocktake Variance</option>';
        $rtype .= '<option value="usagereport">Usage Report</option>';
        $rtype .= '<option value="deliveriesreport">Deliveries Report</option>';
        $rtype .= '<option value="belowminimumreport">Low Stock</option>';
        $rtype .= '</select>';
        $rtype .= '</form>';
        // noselection=true, noactionrow=true — pure read-only display
        $this->preparecommontop(true, true, '<input type="hidden" name="report_type" value="stocklevels">', '', false, $rtype);
        return $formfields;
    }

    public function formscript() {
        return "function formhaserrors() { return 0; }\n"
             . "function displayselectedrecord() {}\n"
             . "(function() {\n"
             . "    var el = document.getElementById('as_at');\n"
             . "    if (el && !el.value) {\n"
             . "        var d = new Date();\n"
             . "        var pad = function(n) { return String(n).padStart(2,'0'); };\n"
             . "        el.value = d.getFullYear() + '-' + pad(d.getMonth()+1) + '-' + pad(d.getDate())\n"
             . "                 + 'T' + pad(d.getHours()) + ':' + pad(d.getMinutes());\n"
             . "    }\n"
             . "})();\n"
             . "function resetAsAtToNow() {\n"
             . "    document.getElementById('as_at').value = '';\n"
             . "    document.getElementById('as_at').form.submit();\n"
             . "}\n"
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
             . "    var asAtSlug = stockReportAsAt ? '-asat-' + stockReportAsAt.replace(/[^0-9]+/g,'-').replace(/-$/,'') : '';\n"
             . "    a.download = 'stock-levels-' + locSlug + asAtSlug + '-' + ts + '.csv';\n"
             . "    a.click();\n"
             . "    URL.revokeObjectURL(a.href);\n"
             . "}\n";
    }
}
