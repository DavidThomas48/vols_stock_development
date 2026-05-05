<?php
namespace app\view\form;
use \lib\StdLib as lib;
class BelowMinimumReportForm extends \fw\view\form\StdCRUDForm {
    protected $trace       = false;
    protected $promptwidth = 30;
    protected $inputwidth  = 40;
    protected $hintwidth   = 30;
    protected $fields      = [];
    protected $names       = [];
    protected $parents     = [];
    protected $formname    = "belowminimumreportform";
    protected $objname     = "Low Stock Report";

    public function __construct(protected FormComponent $component) {
        $this->singlerecord  = false;
        $this->actionbuttons = [];
    }

    public function init($session, $data=[], $parents="", $trace=false) {
        parent::init($session, $data, $parents, $trace);
    }

    public function initfields() {
        $this->fields = [
            "id"            => "",
            "Name"          => "",
            "category_name" => "",
            "location_name" => "",
            "current_qty"   => "",
            "minimum_qty"   => "",
            "variance"      => "",
        ];
    }

    protected function addtonames($row) {}

    public function buildinputs($rights=[], $trace=false) {
        $locations   = $this->parents['locations']   ?? [];
        $location_id = $this->parents['location_id'] ?? '';

        $this->component->setheadingoverride("Stock Reports");

        $rtype  = '<form id="reporttypeform" method="POST">';
        $rtype .= '<input type="hidden" name="p" value="' . (int)$this->pagenum . '">';
        $rtype .= '<label class="vols-stockreport-filter-label">Report:</label>';
        $rtype .= '<select name="report_type" class="vols-stockreport-reportselect" onchange="this.form.submit()">';
        $rtype .= '<option value="stocklevels">Stock Levels</option>';
        $rtype .= '<option value="stocktakevariance">Stocktake Variance</option>';
        $rtype .= '<option value="usagereport">Usage Report</option>';
        $rtype .= '<option value="deliveriesreport">Deliveries Report</option>';
        $rtype .= '<option value="belowminimumreport" selected>Low Stock</option>';
        $rtype .= '</select>';
        $rtype .= '</form>';

        $formfields  = '<div class="vols-stockreport-header">';
        $formfields .= '<span class="vols-stockreport-icon">&#9660;</span>';
        $formfields .= '<span class="vols-stockreport-headertext">Low Stock report. Lists stock items whose current level is below their set minimum quantity.</span>';
        $formfields .= '</div>';

        $formfields .= '<div class="vols-stockreport-filter">';
        $formfields .= '<label class="vols-stockreport-filter-label" for="location_id">Location:</label>';
        $formfields .= '<select name="location_id" id="location_id" class="vols-stockreport-locselect" onchange="this.form.submit()">';
        $formfields .= '<option value="">All locations</option>';
        foreach ($locations as $loc) {
            $sel = ((string)$loc['id'] === (string)$location_id) ? ' selected' : '';
            $formfields .= '<option value="' . (int)$loc['id'] . '"' . $sel . '>'
                         . htmlspecialchars($loc['name']) . '</option>';
        }
        $formfields .= '</select>';
        $formfields .= '</div>';

        $show_loc = empty($location_id);

        if (empty($this->alldata)) {
            $formfields .= '<div class="vols-usagereport-summary">No items are currently below their minimum quantity.</div>';
        } else {
            $formfields .= '<div class="vols-stockreport-toolbar">';
            $formfields .= '<button type="button" class="vols-stockreport-csvbtn" onclick="downloadBelowMinCSV()">&#8681; Export CSV</button>';
            $formfields .= '</div>';

            $cls = $show_loc ? 'vols-belowmin-withloc' : 'vols-belowmin-noloc';

            $formfields .= '<div class="vols-stockreport-table-wrap">';
            $formfields .= '<div class="' . $cls . '">';
            $formfields .= '<div class="vols-stockreport-colheadings">';
            $formfields .= '<div>Category</div>';
            $formfields .= '<div>Stock Item</div>';
            if ($show_loc) { $formfields .= '<div>Location</div>'; }
            $formfields .= '<div class="vols-stockreport-col-num">Current</div>';
            $formfields .= '<div class="vols-stockreport-col-num">Minimum</div>';
            $formfields .= '<div class="vols-stockreport-col-num">Variance</div>';
            $formfields .= '</div>';

            $jsrows = [];
            foreach ($this->alldata as $row) {
                $formfields .= '<div class="vols-stockreport-row">';
                $formfields .= '<div>' . htmlspecialchars($row['category_name']) . '</div>';
                $formfields .= '<div>' . htmlspecialchars($row['Name'])          . '</div>';
                if ($show_loc) {
                    $formfields .= '<div>' . htmlspecialchars($row['location_name']) . '</div>';
                }
                $formfields .= '<div class="vols-stockreport-col-num">'   . (int)$row['current_qty'] . '</div>';
                $formfields .= '<div class="vols-stockreport-col-num">'   . (int)$row['minimum_qty'] . '</div>';
                $formfields .= '<div class="vols-stockreport-col-num vols-belowmin-variance">' . (int)$row['variance'] . '</div>';
                $formfields .= '</div>';

                $jsrows[] = json_encode([
                    'cat'      => $row['category_name'],
                    'name'     => $row['Name'],
                    'location' => $row['location_name'],
                    'current'  => (int)$row['current_qty'],
                    'minimum'  => (int)$row['minimum_qty'],
                    'variance' => (int)$row['variance'],
                ]);
            }

            $formfields .= '</div></div>';
            $formfields .= '<script>var belowMinData=['    . implode(',', $jsrows) . '];</script>';
            $formfields .= '<script>var belowMinShowLoc='  . ($show_loc ? 'true' : 'false') . ';</script>';
        }

        $this->preparecommontop(true, true, '<input type="hidden" name="report_type" value="belowminimumreport">', '', false, $rtype);
        return $formfields;
    }

    public function formscript() {
        return <<<'JS'
function formhaserrors() { return 0; }
function displayselectedrecord() {}
function downloadBelowMinCSV() {
    var headers = belowMinShowLoc
        ? ['Category','Stock Item','Location','Current Qty','Minimum Qty','Variance']
        : ['Category','Stock Item','Current Qty','Minimum Qty','Variance'];
    var rows = [headers];
    for (var i = 0; i < belowMinData.length; i++) {
        var d = belowMinData[i];
        if (belowMinShowLoc) {
            rows.push([d.cat, d.name, d.location, d.current, d.minimum, d.variance]);
        } else {
            rows.push([d.cat, d.name, d.current, d.minimum, d.variance]);
        }
    }
    var csv = rows.map(function(row) {
        return row.map(function(v) {
            var s = String(v);
            return s.indexOf(',') !== -1 || s.indexOf('"') !== -1 ? '"' + s.replace(/"/g, '""') + '"' : s;
        }).join(',');
    }).join('\r\n');
    var blob = new Blob([csv], {type: 'text/csv'});
    var a = document.createElement('a');
    a.href = URL.createObjectURL(blob);
    var d2 = new Date();
    var pad = function(n){return String(n).padStart(2,'0');};
    var ts = d2.getFullYear() + pad(d2.getMonth()+1) + pad(d2.getDate()) + '-' + pad(d2.getHours()) + pad(d2.getMinutes());
    a.download = 'below-minimum-' + ts + '.csv';
    a.click();
    URL.revokeObjectURL(a.href);
}
JS;
    }
}
