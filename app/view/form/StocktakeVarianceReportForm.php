<?php
namespace app\view\form;
use \lib\StdLib as lib;
class StocktakeVarianceReportForm extends \fw\view\form\StdCRUDForm {
    protected $trace       = false;
    protected $promptwidth = 30;
    protected $inputwidth  = 40;
    protected $hintwidth   = 30;
    protected $fields      = [];
    protected $names       = [];
    protected $parents     = [];
    protected $formname    = "stockvariancereportform";
    protected $objname     = "Stock Reports";

    public function __construct(protected FormComponent $component) {
        $this->singlerecord  = false;
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
            "category_name" => "",
            "stocktake_qty" => "",
            "stock_level"   => "",
            "variance"      => "",
        );
    }

    protected function addtonames($row) {
        $this->names[$row["id"]] = $row["Name"] ?? '';
    }

    public function buildinputs($rights=[], $trace=false) {
        $locations   = $this->parents['locations']   ?? [];
        $location_id = $this->parents['location_id'] ?? '';
        $stocktakes  = $this->parents['stocktakes']  ?? [];
        $event_id    = $this->parents['event_id']    ?? '';

        // Report type selector rendered in #headercontainer subheading
        $this->component->setheadingoverride("Stock Reports");
        $rtype  = '<form id="reporttypeform" method="POST">';
        $rtype .= '<input type="hidden" name="p" value="' . (int)$this->pagenum . '">';
        $rtype .= '<label class="vols-stockreport-filter-label">Report:</label>';
        $rtype .= '<select name="report_type" class="vols-stockreport-reportselect" onchange="this.form.submit()">';
        $rtype .= '<option value="stocklevels">Stock Levels</option>';
        $rtype .= '<option value="stocktakevariance" selected>Stocktake Variance</option>';
        $rtype .= '<option value="usagereport">Usage Report</option>';
        $rtype .= '<option value="deliveriesreport">Deliveries Report</option>';
        $rtype .= '<option value="belowminimumreport">Low Stock</option>';
        $rtype .= '</select>';
        $rtype .= '</form>';

        // Page header
        $formfields  = '<div class="vols-stockreport-header">';
        $formfields .= '<span class="vols-stockreport-icon">&#128202;</span>';
        $formfields .= '<span class="vols-stockreport-headertext">Stocktake variance: the difference between counted quantities and calculated stock levels at the time of each stocktake.</span>';
        $formfields .= '</div>';

        // Location selector (no "All Locations" option)
        $formfields .= '<div class="vols-stockreport-filter">';
        $formfields .= '<label class="vols-stockreport-filter-label" for="location_id">Location:</label>';
        $formfields .= '<select id="location_id" name="location_id" class="vols-stockreport-locselect" onchange="this.form.submit()">';
        $formfields .= '<option value="">-- Select a location --</option>';
        foreach ($locations as $loc) {
            $sel = ((string)$loc['id'] === (string)$location_id) ? ' selected' : '';
            $formfields .= '<option value="' . (int)$loc['id'] . '"' . $sel . '>'
                         . htmlspecialchars($loc['name']) . '</option>';
        }
        $formfields .= '</select>';
        $formfields .= '</div>';

        // Stocktake event selector
        $formfields .= '<div class="vols-stockreport-filter">';
        $formfields .= '<label class="vols-stockreport-filter-label" for="event_id">Stocktake:</label>';
        $formfields .= '<select id="event_id" name="event_id" class="vols-stockreport-locselect" onchange="this.form.submit()">';
        if (empty($location_id)) {
            $formfields .= '<option value="">-- Select a location first --</option>';
        } elseif (empty($stocktakes)) {
            $formfields .= '<option value="">-- No closed stocktakes for this location --</option>';
        } else {
            $formfields .= '<option value="">-- Select a stocktake --</option>';
            foreach ($stocktakes as $st) {
                $sel  = ((string)$st['id'] === (string)$event_id) ? ' selected' : '';
                $dt   = \DateTime::createFromFormat('Y-m-d H:i:s', $st['date_created']);
                $disp = $dt ? $dt->format('D, j M Y \a\t H:i') : htmlspecialchars($st['date_created']);
                $formfields .= '<option value="' . (int)$st['id'] . '"' . $sel . '>'
                             . htmlspecialchars($disp) . '</option>';
            }
        }
        $formfields .= '</select>';
        $formfields .= '</div>';

        // JS data for CSV (always define even when empty)
        $jsrows = [];
        foreach ($this->alldata as $item) {
            $jsrows[] = json_encode([
                'category' => $item['category_name'] ?? 'Uncategorised',
                'name'     => $item['Name'],
                'code'     => $item['Code'],
                'stqty'    => (float)($item['stocktake_qty'] ?? 0),
                'level'    => (float)($item['stock_level']   ?? 0),
                'variance' => (float)($item['variance']      ?? 0),
            ]);
        }
        $formfields .= '<script>var varianceReportData=[' . implode(',', $jsrows) . '];</script>';

        // Variance table — only shown when an event is selected and there is data
        if (!empty($event_id) && !empty($this->alldata)) {
            $formfields .= '<div class="vols-stockreport-toolbar">';
            $formfields .= '<button type="button" class="vols-stockreport-csvbtn" onclick="downloadVarianceCSV()">&#8681; Export CSV</button>';
            $formfields .= '</div>';

            $formfields .= '<div class="vols-stockreport-table-wrap">';
            $formfields .= '<div class="vols-variancereport-table">';
            $formfields .= '<div class="vols-stockreport-colheadings">';
            $formfields .= '<div class="vols-stockreport-col-name">Item</div>';
            $formfields .= '<div class="vols-stockreport-col-code">Code</div>';
            $formfields .= '<div class="vols-stockreport-col-num">Stocktake<br>Qty</div>';
            $formfields .= '<div class="vols-stockreport-col-num">Calculated<br>Level</div>';
            $formfields .= '<div class="vols-stockreport-col-num">Variance</div>';
            $formfields .= '</div>';

            $currentcategory = null;
            foreach ($this->alldata as $item) {
                $cat = htmlspecialchars($item["category_name"] ?? "Uncategorised");
                if ($cat !== $currentcategory) {
                    $currentcategory = $cat;
                    $formfields .= '<div class="vols-stockreport-category">' . $cat . '</div>';
                }
                $stqty    = (float)($item["stocktake_qty"] ?? 0);
                $level    = (float)($item["stock_level"]   ?? 0);
                $variance = (float)($item["variance"]      ?? 0);
                $vclass   = $variance < 0 ? ' vols-variance-neg'
                          : ($variance > 0 ? ' vols-variance-pos' : ' vols-variance-zero');
                $name     = htmlspecialchars($item["Name"]);
                $code     = htmlspecialchars($item["Code"]);
                $vsign    = $variance > 0 ? '+' . $variance : (string)$variance;
                $formfields .= '<div class="vols-stockreport-row">';
                $formfields .= '<div class="vols-stockreport-col-name">'  . $name  . '</div>';
                $formfields .= '<div class="vols-stockreport-col-code">'  . $code  . '</div>';
                $formfields .= '<div class="vols-stockreport-col-num">'   . $stqty . '</div>';
                $formfields .= '<div class="vols-stockreport-col-num">'   . $level . '</div>';
                $formfields .= '<div class="vols-stockreport-col-num' . $vclass . '">' . $vsign . '</div>';
                $formfields .= '</div>';
            }

            $formfields .= '</div>'; // vols-variancereport-table
            $formfields .= '</div>'; // vols-stockreport-table-wrap
        }

        $this->preparecommontop(true, true, '<input type="hidden" name="report_type" value="stocktakevariance">', '', false, $rtype);
        return $formfields;
    }

    public function formscript() {
        return "function formhaserrors() { return 0; }\n"
             . "function displayselectedrecord() {}\n"
             . "function downloadVarianceCSV() {\n"
             . "    var rows = [['Category','Item','Code','Stocktake Qty','Calculated Level','Variance']];\n"
             . "    for (var i = 0; i < varianceReportData.length; i++) {\n"
             . "        var r = varianceReportData[i];\n"
             . "        rows.push([r.category, r.name, r.code, r.stqty, r.level, r.variance]);\n"
             . "    }\n"
             . "    var csv = rows.map(function(row) {\n"
             . "        return row.map(function(v) {\n"
             . "            var s = String(v);\n"
             . "            return s.indexOf(',') !== -1 || s.indexOf('\"') !== -1\n"
             . "                ? '\"' + s.replace(/\"/g, '\"\"') + '\"' : s;\n"
             . "        }).join(',');\n"
             . "    }).join('\\r\\n');\n"
             . "    var blob = new Blob([csv], {type: 'text/csv'});\n"
             . "    var a = document.createElement('a');\n"
             . "    a.href = URL.createObjectURL(blob);\n"
             . "    a.download = 'stocktake-variance.csv';\n"
             . "    a.click();\n"
             . "    URL.revokeObjectURL(a.href);\n"
             . "}\n";
    }
}
