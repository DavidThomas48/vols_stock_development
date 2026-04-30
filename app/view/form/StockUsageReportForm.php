<?php
namespace app\view\form;
use \lib\StdLib as lib;
class StockUsageReportForm extends \fw\view\form\StdCRUDForm {
    protected $trace      = false;
    protected $promptwidth = 30;
    protected $inputwidth  = 40;
    protected $hintwidth   = 30;
    protected $fields      = [];
    protected $names       = [];
    protected $parents     = [];
    protected $formname    = "stockusagereportform";
    protected $objname     = "Stock Usage Report";

    public function __construct(protected FormComponent $component) {
        $this->singlerecord = false;
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
            "total_used"    => "",
        );
    }

    protected function addtonames($row) {
        $this->names[$row["id"]] = $row["Name"];
    }

    public function buildinputs($rights=[], $trace=false) {
        $locations   = $this->parents['locations']   ?? [];
        $location_id = $this->parents['location_id'] ?? '';
        $from_val    = (!empty($this->parents['from'])) ? $this->parents['from'] : date('Y-m-d', strtotime('-30 days'));
        $to_val      = (!empty($this->parents['to']))   ? $this->parents['to']   : date('Y-m-d');
        $queried     = !empty($this->parents['from']) && !empty($this->parents['to']);

        $this->component->setheadingoverride("Stock Reports");
        $rtype  = '<form id="reporttypeform" method="POST">';
        $rtype .= '<input type="hidden" name="p" value="' . (int)$this->pagenum . '">';
        $rtype .= '<label class="vols-stockreport-filter-label">Report:</label>';
        $rtype .= '<select name="report_type" class="vols-stockreport-reportselect" onchange="this.form.submit()">';
        $rtype .= '<option value="stocklevels">Stock Levels</option>';
        $rtype .= '<option value="stocktakevariance">Stocktake Variance</option>';
        $rtype .= '<option value="usagereport" selected>Usage Report</option>';
        $rtype .= '</select>';
        $rtype .= '</form>';

        $formfields  = '<div class="vols-usagereport-header">';
        $formfields .= '<span class="vols-usagereport-icon">&#128200;</span>';
        $formfields .= '<span class="vols-usagereport-headertext">Stock usage report. Select a location and date range, then click Generate.</span>';
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

        $formfields .= '<div class="vols-usagereport-filter">';
        $formfields .= '<label class="vols-usagereport-label">From</label>';
        $formfields .= '<input type="date" name="from" class="vols-usagereport-dateinput" value="'.htmlspecialchars($from_val).'">';
        $formfields .= '<label class="vols-usagereport-label">To</label>';
        $formfields .= '<input type="date" name="to" class="vols-usagereport-dateinput" value="'.htmlspecialchars($to_val).'">';
        $formfields .= '<button type="submit" class="vols-usagereport-genbtn">Generate Report</button>';
        $formfields .= '</div>';

        if ($queried) {
            $from_disp = date('d-m-Y', strtotime($this->parents['from']));
            $to_disp   = date('d-m-Y', strtotime($this->parents['to']));

            $jsrows = [];
            foreach ($this->alldata as $item) {
                $jsrows[] = json_encode([
                    'category' => $item['category_name'] ?? 'Uncategorised',
                    'name'     => $item['Name'],
                    'code'     => $item['Code'],
                    'used'     => (float)($item['total_used'] ?? 0),
                ]);
            }
            $formfields .= '<script>var usageReportData=[' . implode(',', $jsrows) . '];</script>';
            $formfields .= '<script>var usageReportFrom="'.htmlspecialchars($from_disp).'";var usageReportTo="'.htmlspecialchars($to_disp).'";</script>';

            $formfields .= '<div class="vols-usagereport-summary">';
            $formfields .= 'Showing stock usage from <strong>'.$from_disp.'</strong> to <strong>'.$to_disp.'</strong>';
            if (empty($this->alldata)) {
                $formfields .= ' &mdash; no usage recorded in this period.';
            } else {
                $formfields .= ' &mdash; '.count($this->alldata).' item(s).';
                $formfields .= '<button type="button" class="vols-usagereport-csvbtn" onclick="downloadUsageCSV()">&#8681; Export CSV</button>';
            }
            $formfields .= '</div>';

            if (!empty($this->alldata)) {
                $formfields .= '<div class="vols-usagereport-table">';
                $formfields .= '<div class="vols-usagereport-colheadings">';
                $formfields .= '<div class="vols-usagereport-col-name">Item</div>';
                $formfields .= '<div class="vols-usagereport-col-code">Code</div>';
                $formfields .= '<div class="vols-usagereport-col-num">Total Used</div>';
                $formfields .= '</div>';

                $currentcategory = null;
                foreach ($this->alldata as $item) {
                    $cat = htmlspecialchars($item['category_name'] ?? 'Uncategorised');
                    if ($cat !== $currentcategory) {
                        $currentcategory = $cat;
                        $formfields .= '<div class="vols-usagereport-category">'.$cat.'</div>';
                    }
                    $used = (float)($item['total_used'] ?? 0);
                    $name = htmlspecialchars($item['Name']);
                    $code = htmlspecialchars($item['Code']);
                    $formfields .= '<div class="vols-usagereport-row">';
                    $formfields .= '<div class="vols-usagereport-col-name">'.$name.'</div>';
                    $formfields .= '<div class="vols-usagereport-col-code">'.$code.'</div>';
                    $formfields .= '<div class="vols-usagereport-col-num vols-usagereport-qty">'.$used.'</div>';
                    $formfields .= '</div>';
                }
                $formfields .= '</div>';
            }
        }

        $this->preparecommontop(true, true, '<input type="hidden" name="report_type" value="usagereport">', '', false, $rtype);
        return $formfields;
    }

    public function formscript() {
        return "function formhaserrors() { return 0; }\n"
             . "function displayselectedrecord() {}\n"
             . "function downloadUsageCSV() {\n"
             . "    var rows = [['Category','Item','Code','Total Used','From','To']];\n"
             . "    for (var i = 0; i < usageReportData.length; i++) {\n"
             . "        var r = usageReportData[i];\n"
             . "        rows.push([r.category, r.name, r.code, r.used, usageReportFrom, usageReportTo]);\n"
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
             . "    a.download = 'stock-usage-' + ts + '.csv';\n"
             . "    a.click();\n"
             . "    URL.revokeObjectURL(a.href);\n"
             . "}\n";
    }
}
