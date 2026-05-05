<?php
namespace app\view\form;
use \lib\StdLib as lib;
class LocationForm extends \fw\view\form\StdCRUDForm {
    protected $trace       = false;
    protected $promptwidth = 25;
    protected $inputwidth  = 40;
    protected $hintwidth   = 35;
    protected $fields      = [];
    protected $names       = [];
    protected $parents     = [];
    protected $formname    = "locationform";
    protected $objname     = "Location";
    protected $locationid;
    protected $categories  = [];

    public function __construct(protected FormComponent $component) {
        $this->singlerecord = false;
    }

    public function init($session, $data=[], $parents="", $trace=false, $categories=[]) {
        parent::init($session, $data, $parents, $trace);
        $this->locationid = $this->requestdata["id"] ?? "";
        $this->categories = $categories;
    }

    public function initfields() {
        $this->fields = array(
            "id"                  => "",
            "name"                => "",
            "uncontrolled_issues" => "",
        );
    }

    protected function addtonames($row) {
        $this->names[$row["id"]] = $row["name"];
    }

    // Called by ViewController::processajaxrequest for stockitemlocation_getstock.
    // Returns the <tbody> rows for the target quantities table.
    public function rendertargetstable(array $rows): string {
        if (empty($rows)) {
            return '<tr><td colspan="4" class="loc-tgt-empty">No stock items found.</td></tr>';
        }
        $html = '';
        foreach ($rows as $row) {
            $stock_id    = (int)$row['stock_id'];
            $stock_name  = htmlspecialchars($row['stock_name']    ?? '');
            $cat_name    = htmlspecialchars($row['category_name'] ?? '');
            $cat_id      = (int)($row['category_id'] ?? 0);
            $target_qty  = ($row['target_qty'] !== null && $row['target_qty'] !== '' && (int)$row['target_qty'] > 0) ? (int)$row['target_qty'] : '';
            $minimum_qty = ($row['minimum_qty'] !== null && $row['minimum_qty'] !== '') ? (int)$row['minimum_qty'] : '';
            $html .= '<tr data-category-id="' . $cat_id . '">'
                   . '<td class="loc-tgt-cat">'  . $cat_name   . '</td>'
                   . '<td class="loc-tgt-name">' . $stock_name . '</td>'
                   . '<td class="loc-tgt-qty">'
                   . '<input type="number" name="sil_' . $stock_id . '" min="0" step="1"'
                   . ' class="loc-target-input" data-stock-id="' . $stock_id . '"'
                   . ' value="' . $target_qty . '">'
                   . '</td>'
                   . '<td class="loc-tgt-minqty">'
                   . '<input type="number" name="min_qty_' . $stock_id . '" min="0" step="1"'
                   . ' class="loc-minqty-input"'
                   . ' value="' . $minimum_qty . '">'
                   . '</td>'
                   . '</tr>';
        }
        return $html;
    }

    public function buildinputs($rights=[], $trace=false) {
        $formfields  = '<div class="vols-stockmaint-header vols-location-header">';
        $formfields .= '<span class="vols-stockmaint-icon">&#128205;</span>';
        $formfields .= '<span class="vols-stockmaint-text">Manage locations. Add, edit or delete the physical locations used in stock events.</span>';
        $formfields .= '</div>';
        $formfields .= $this->component->buildinputrow("name", 1, "", 'Name', '', 20, 64, true, '', '');
        $this->component->setwidths(25, 15, 60);
        $formfields .= '<input type="hidden" name="uncontrolled_issues" value="0">';
        $formfields .= $this->component->buildcheckboxrow("uncontrolled_issues", "1", "", false, 2, "Untracked issues", 'Issues quantities are derived from stocktake variances.', false, false, false);

        // ---- Target quantities section ----------------------------------------
        $catoptionshtml = '<option value="">All categories</option>';
        foreach ($this->categories as $cat) {
            $catoptionshtml .= '<option value="' . (int)$cat['id'] . '">'
                             . htmlspecialchars($cat['Name']) . '</option>';
        }
        $catselector = '<select id="loc-category-filter" class="vols-form-select nondatainput">'
                     . $catoptionshtml . '</select>';
        $formfields .= $this->component->rendersectionheading(
            'Target quantities &nbsp;&mdash;&nbsp; ' . $catselector
        );

        $formfields .= '<input type="hidden" name="sil_stock_ids" id="sil_stock_ids" value="">';
        $formfields .= '<div id="loc-targets-wrapper" style="display:none">';
        $formfields .= '<table class="vols-table loc-targets-table">';
        $formfields .= '<thead><tr>'
                     . '<th class="loc-tgt-cat">Category</th>'
                     . '<th class="loc-tgt-name">Stock Item</th>'
                     . '<th class="loc-tgt-qty">Target Qty</th>'
                     . '<th class="loc-tgt-minqty">Min Qty</th>'
                     . '</tr></thead>';
        $formfields .= '<tbody id="loc-targets-tbody"></tbody>';
        $formfields .= '</table>';
        $formfields .= '</div>';
        // -----------------------------------------------------------------------

        $this->preparecommontop(false, false, '', $this->locationid);
        return $formfields;
    }

    public function formscript() {
        $postloadfieldsscript  = "loadtargets();";
        $postclearfieldsscript = "clearlocations();";

        $script = $this->vols_masterscript(
            $this->formname,
            $this->objname,
            true,                    // idselection
            true,                    // adjustnamerow
            true,                    // updatefields
            false,                   // inclmulti
            '',                      // postajaxscript
            $postloadfieldsscript,
            $postclearfieldsscript,
            false,                   // trace
            '',                      // multisubmit
            ''                       // presavescript
        );
        $script .= <<<JS

            function loadtargets() {
                var location_id = jQuery('#hiddenid').val();
                jQuery('#loc-targets-wrapper').hide();
                jQuery('#loc-targets-tbody').empty();
                jQuery('#sil_stock_ids').val('');
                if (!location_id) return;
                doServerRequest(0, JSON.stringify({location_id: location_id}), 'stockitemlocation_getstock')
                    .then(function(html) {
                        jQuery('#loc-targets-tbody').html(html);
                        var ids = jQuery('#loc-targets-tbody .loc-target-input').map(function() {
                            return jQuery(this).data('stock-id');
                        }).get().join(',');
                        jQuery('#sil_stock_ids').val(ids);
                        if (!jQuery('#editrecord').hasClass('inactive')) {
                            jQuery('#loc-targets-tbody input').prop('disabled', true);
                        }
                        filtertargetsbycat();
                        jQuery('#loc-targets-wrapper').show();
                    });
            }

            function clearlocations() {
                jQuery('#loc-targets-tbody').empty();
                jQuery('#sil_stock_ids').val('');
                jQuery('#loc-targets-wrapper').hide();
                jQuery('#loc-category-filter').val('');
            }

            function filtertargetsbycat() {
                var cat_id = String(jQuery('#loc-category-filter').val() || '');
                var n = 0;
                jQuery('#loc-targets-tbody tr').each(function() {
                    var row_cat = String(jQuery(this).data('category-id') || '');
                    var visible = !cat_id || row_cat === cat_id;
                    jQuery(this).toggle(visible);
                    if (visible) {
                        jQuery(this).toggleClass('vols-row-odd',  n % 2 === 0)
                                    .toggleClass('vols-row-even', n % 2 === 1);
                        n++;
                    }
                });
            }

            jQuery(document).on('change', '#loc-category-filter', filtertargetsbycat);

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
