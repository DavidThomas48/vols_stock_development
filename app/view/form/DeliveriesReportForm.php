<?php
namespace app\view\form;
use \lib\StdLib as lib;
class DeliveriesReportForm extends \fw\view\form\StdCRUDForm {
    protected $trace       = false;
    protected $promptwidth = 30;
    protected $inputwidth  = 40;
    protected $hintwidth   = 30;
    protected $fields      = [];
    protected $names       = [];
    protected $parents     = [];
    protected $formname    = "deliveriesreportform";
    protected $objname     = "Deliveries Report";

    public function __construct(protected FormComponent $component) {
        $this->singlerecord = false;
        $this->actionbuttons = [];
    }

    public function init($session, $data=[], $parents="", $trace=false) {
        parent::init($session, $data, $parents, $trace);
    }

    public function initfields() {
        $this->fields = [
            "id"            => "",
            "delivery_date" => "",
            "supplier_name" => "",
            "total_weight"  => "",
            "location_name" => "",
            "category_name" => "",
            "stock_name"    => "",
            "qty"           => "",
        ];
    }

    protected function addtonames($row) {}

    public function buildinputs($rights=[], $trace=false) {
        $from_val      = $this->parents['from']        ?? date('Y-m-01');
        $to_val        = $this->parents['to']          ?? date('Y-m-t');
        $include_items = !empty($this->parents['include_items']);
        $sel_cat       = $this->parents['category_id'] ?? '';
        $sel_sup       = $this->parents['supplier_id'] ?? '';
        $suppcats      = $this->parents['supplier_categories'] ?? [];
        $suppliers     = $this->parents['suppliers']           ?? [];

        $this->component->setheadingoverride("Stock Reports");

        $rtype  = '<form id="reporttypeform" method="POST">';
        $rtype .= '<input type="hidden" name="p" value="' . (int)$this->pagenum . '">';
        $rtype .= '<label class="vols-stockreport-filter-label">Report:</label>';
        $rtype .= '<select name="report_type" class="vols-stockreport-reportselect" onchange="this.form.submit()">';
        $rtype .= '<option value="stocklevels">Stock Levels</option>';
        $rtype .= '<option value="stocktakevariance">Stocktake Variance</option>';
        $rtype .= '<option value="usagereport">Usage Report</option>';
        $rtype .= '<option value="deliveriesreport" selected>Deliveries Report</option>';
        $rtype .= '<option value="belowminimumreport">Low Stock</option>';
        $rtype .= '</select>';
        $rtype .= '</form>';

        $formfields  = '<div class="vols-stockreport-header">';
        $formfields .= '<span class="vols-stockreport-icon">&#128666;</span>';
        $formfields .= '<span class="vols-stockreport-headertext">Deliveries report. Select a date range to list all closed deliveries with their total weights.</span>';
        $formfields .= '</div>';

        // Category filter row
        $formfields .= '<div class="vols-stockreport-filter">';
        $formfields .= '<label class="vols-stockreport-filter-label" for="category_id">Category:</label>';
        $formfields .= '<select name="category_id" id="category_id" class="vols-stockreport-locselect" onchange="filterDeliverySuppliers(this.value)">';
        $formfields .= '<option value="">All categories</option>';
        foreach ($suppcats as $cat) {
            $sel = ((string)$cat['id'] === (string)$sel_cat) ? ' selected' : '';
            $formfields .= '<option value="' . (int)$cat['id'] . '"' . $sel . '>' . htmlspecialchars($cat['name']) . '</option>';
        }
        $formfields .= '</select>';
        $formfields .= '</div>';

        // Supplier filter row
        $formfields .= '<div class="vols-stockreport-filter">';
        $formfields .= '<label class="vols-stockreport-filter-label" for="supplier_id">Supplier:</label>';
        $formfields .= '<select name="supplier_id" id="supplier_id" class="vols-stockreport-locselect">';
        $formfields .= '<option value="">All suppliers</option>';
        foreach ($suppliers as $sup) {
            $sel   = ((string)$sup['id'] === (string)$sel_sup) ? ' selected' : '';
            $catid = $sup['supplier_category_id'] !== null ? (int)$sup['supplier_category_id'] : '';
            $formfields .= '<option value="' . (int)$sup['id'] . '" data-cat="' . $catid . '"' . $sel . '>'
                         . htmlspecialchars($sup['name']) . '</option>';
        }
        $formfields .= '</select>';
        $formfields .= '</div>';

        // Date range + generate + include items row
        $formfields .= '<div class="vols-usagereport-filter">';
        $formfields .= '<label class="vols-usagereport-label">From</label>';
        $formfields .= '<input type="date" name="from" class="vols-usagereport-dateinput" value="' . htmlspecialchars($from_val) . '">';
        $formfields .= '<label class="vols-usagereport-label">To</label>';
        $formfields .= '<input type="date" name="to" class="vols-usagereport-dateinput" value="' . htmlspecialchars($to_val) . '">';
        $formfields .= '<button type="submit" class="vols-usagereport-genbtn">Generate</button>';
        $formfields .= '<span class="vols-delivreport-items-toggle">';
        $formfields .= '<input type="hidden" name="include_items" value="0">';
        $checked     = $include_items ? ' checked' : '';
        $formfields .= '<input type="checkbox" id="include_items" name="include_items" value="1"' . $checked . ' onchange="this.form.submit()">';
        $formfields .= '<label for="include_items">Include stock items</label>';
        $formfields .= '</span>';
        $formfields .= '</div>';

        // JS data for client-side supplier filtering
        $js_suppliers = array_map(function($s) {
            return [
                'id'  => (int)$s['id'],
                'name'=> $s['name'],
                'cat' => $s['supplier_category_id'] !== null ? (int)$s['supplier_category_id'] : null,
            ];
        }, $suppliers);
        $formfields .= '<script>'
                     . 'var deliverySuppliers='    . json_encode($js_suppliers) . ';'
                     . 'var deliverySelCat='       . json_encode($sel_cat) . ';'
                     . 'var deliverySelSup='       . json_encode($sel_sup) . ';'
                     . 'document.addEventListener("DOMContentLoaded",function(){filterDeliverySuppliers(deliverySelCat);});'
                     . '</script>';

        // Group flat query rows into deliveries
        $deliveries = [];
        foreach ($this->alldata as $row) {
            $eid = $row['id'];
            if (!isset($deliveries[$eid])) {
                $deliveries[$eid] = [
                    'delivery_date' => $row['delivery_date'],
                    'supplier_name' => $row['supplier_name'],
                    'total_weight'  => $row['total_weight'],
                    'items'         => [],
                ];
            }
            if ($row['stock_name'] !== null) {
                $deliveries[$eid]['items'][] = [
                    'category_name' => $row['category_name'] ?? '',
                    'stock_name'    => $row['stock_name'],
                    'qty'           => (int)$row['qty'],
                ];
            }
        }

        $from_disp = date('d/m/Y', strtotime($from_val));
        $to_disp   = date('d/m/Y', strtotime($to_val));

        if (empty($deliveries)) {
            $formfields .= '<div class="vols-usagereport-summary">No deliveries found for the selected filters.</div>';
        } else {
            $formfields .= '<div class="vols-stockreport-toolbar">';
            $formfields .= '<button type="button" class="vols-stockreport-csvbtn" onclick="downloadDeliveriesCSV()">&#8681; Export CSV</button>';
            $formfields .= '</div>';
        }

        if (!empty($deliveries)) {
            $total_weight = 0;
            $jsrows = [];

            $formfields .= '<div class="vols-stockreport-table-wrap">';
            $formfields .= '<div class="vols-delivreport-table">';
            $formfields .= '<div class="vols-stockreport-colheadings">';
            $formfields .= '<div>Date</div>';
            $formfields .= '<div>Supplier</div>';
            $formfields .= '<div class="vols-stockreport-col-num">Weight (kg)</div>';
            $formfields .= '</div>';

            foreach ($deliveries as $eid => $d) {
                $wt        = ($d['total_weight'] !== null) ? (int)$d['total_weight'] : null;
                $wt_disp   = ($wt !== null) ? $wt : '&ndash;';
                $date_disp = $d['delivery_date'] ? date('d/m/Y', strtotime($d['delivery_date'])) : '';
                $total_weight += ($wt ?? 0);

                $formfields .= '<div class="vols-stockreport-row">';
                $formfields .= '<div>' . htmlspecialchars($date_disp) . '</div>';
                $formfields .= '<div>' . htmlspecialchars($d['supplier_name']) . '</div>';
                $formfields .= '<div class="vols-stockreport-col-num">' . $wt_disp . '</div>';
                $formfields .= '</div>';

                if ($include_items && !empty($d['items'])) {
                    foreach ($d['items'] as $item) {
                        $formfields .= '<div class="vols-delivreport-item">';
                        $formfields .= '<div></div>';
                        $formfields .= '<div class="vols-delivreport-col-itemname">'
                                     . htmlspecialchars($item['category_name'] ? $item['category_name'] . ' – ' : '')
                                     . htmlspecialchars($item['stock_name']) . '</div>';
                        $formfields .= '<div class="vols-stockreport-col-num">' . $item['qty'] . '</div>';
                        $formfields .= '</div>';
                    }
                }

                // Build JS data for CSV
                $jsrow = ['date' => $date_disp, 'supplier' => $d['supplier_name'], 'weight' => $wt ?? ''];
                $jsrow['items'] = [];
                foreach ($d['items'] as $item) {
                    $jsrow['items'][] = ['cat' => $item['category_name'], 'name' => $item['stock_name'], 'qty' => $item['qty']];
                }
                $jsrows[] = json_encode($jsrow);
            }

            $formfields .= '<div class="vols-delivreport-total">';
            $formfields .= '<div>Total</div>';
            $formfields .= '<div></div>';
            $formfields .= '<div class="vols-stockreport-col-num">' . $total_weight . '</div>';
            $formfields .= '</div>';

            $formfields .= '</div></div>';

            $formfields .= '<script>var deliveriesReportData=[' . implode(',', $jsrows) . '];</script>';
            $formfields .= '<script>var deliveriesIncludeItems=' . ($include_items ? 'true' : 'false') . ';</script>';
            $formfields .= '<script>var deliveriesReportFrom="' . htmlspecialchars($from_disp) . '";</script>';
            $formfields .= '<script>var deliveriesReportTo="'   . htmlspecialchars($to_disp)   . '";</script>';
        }

        $this->preparecommontop(true, true, '<input type="hidden" name="report_type" value="deliveriesreport">', '', false, $rtype);
        return $formfields;
    }

    public function formscript() {
        return <<<'JS'
function formhaserrors() { return 0; }
function displayselectedrecord() {}
function filterDeliverySuppliers(catId) {
    var sel = document.getElementById('supplier_id');
    if (!sel) return;
    var curVal = sel.value;
    while (sel.options.length > 1) sel.remove(1);
    for (var i = 0; i < deliverySuppliers.length; i++) {
        var s = deliverySuppliers[i];
        if (!catId || s.cat == catId) {
            var opt = document.createElement('option');
            opt.value = s.id;
            opt.text  = s.name;
            if (String(s.id) === String(curVal) || String(s.id) === String(deliverySelSup)) {
                opt.selected = true;
                deliverySelSup = '';
            }
            sel.appendChild(opt);
        }
    }
}
function downloadDeliveriesCSV() {
    var rows = deliveriesIncludeItems
        ? [['Date','Supplier','Weight (kg)','Category','Stock Item','Qty']]
        : [['Date','Supplier','Weight (kg)']];
    var totalWeight = 0;
    for (var i = 0; i < deliveriesReportData.length; i++) {
        var d = deliveriesReportData[i];
        totalWeight += d.weight !== '' ? parseInt(d.weight) : 0;
        if (deliveriesIncludeItems && d.items && d.items.length > 0) {
            rows.push([d.date, d.supplier, d.weight !== '' ? d.weight : '']);
            for (var j = 0; j < d.items.length; j++) {
                var it = d.items[j];
                rows.push(['', '', '', it.cat, it.name, it.qty]);
            }
        } else {
            rows.push([d.date, d.supplier, d.weight !== '' ? d.weight : '']);
        }
    }
    if (deliveriesIncludeItems) {
        rows.push(['Total', '', totalWeight, '', '', '']);
    } else {
        rows.push(['Total', '', totalWeight]);
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
    a.download = 'deliveries-' + ts + '.csv';
    a.click();
    URL.revokeObjectURL(a.href);
}
JS;
    }
}
