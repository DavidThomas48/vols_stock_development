<?php
namespace app\view\form;
use \lib\StdLib as lib;

class StocktakeEventForm extends StockEventForm {
    protected $trace             = false;
    protected $formname          = "stocktakeeventform";
    protected $event_type        = "stocktake";
    protected $event_label       = "Stocktake";
    protected $event_icon        = "&#9998;";
    protected $event_description = "Select a location and count the stock items on hand. Leave any item blank to skip it.";

    protected function rendereventdefinition(): string {
        $html  = '<div id="se-event-def" class="se-event-def">';
        $html .= '<div class="se-event-def-row">';
        $html .= $this->renderlocationselect('se-location1', 'Location', 'se-location-select');
        $html .= '<button type="button" id="se-start-btn" class="vols-button" style="display:none">Start Stocktake</button>';
        $html .= '</div>';
        $html .= '</div>';
        return $html;
    }

    protected function renderstocktableheader(): string {
        return '<tr>'
             . '<th class="se-th-category">Category</th>'
             . '<th class="se-th-name">Stock Item</th>'
             . '<th class="se-th-qty">Count</th>'
             . '</tr>';
    }

    // $row keys: stock_id, stock_name, category_name, movement_id, qty, stock_qoh, location_id
    protected function renderstockrow(array $row): string {
        $stock_id    = (int)$row['stock_id'];
        $stock_name  = htmlspecialchars($row['stock_name']    ?? '');
        $cat_name    = htmlspecialchars($row['category_name'] ?? '');
        $movement_id = (int)($row['movement_id'] ?? 0);
        // stock_qoh is the actual count recorded; null means not yet counted → show empty input
        $value       = ($row['stock_qoh'] !== null && $row['stock_qoh'] !== '') ? (int)$row['stock_qoh'] : '';

        return '<tr class="se-stock-row" data-stock-id="' . $stock_id . '">'
             . '<td class="se-td-category">' . $cat_name   . '</td>'
             . '<td class="se-td-name">'     . $stock_name . '</td>'
             . '<td class="se-td-qty">'
             . '<input type="number" min="0" step="1" class="se-qty"'
             . ' data-stock-id="'    . $stock_id    . '"'
             . ' data-movement-id="' . $movement_id . '"'
             . ' value="'            . $value       . '"'
             . ' inputmode="numeric">'
             . '</td>'
             . '</tr>';
    }

    // Appends stocktake-specific JS to the shared base script.
    public function formscript(): string {
        $base = parent::formscript();
        $extra = <<<'JS'

// ---- StocktakeEventForm-specific JS ----
jQuery(function() {
    // Location dropdown change: check for in-progress stocktake at that location.
    jQuery('#se-location1').on('change', function() {
        var loc = jQuery(this).val();
        jQuery('#se-start-btn').hide();
        jQuery('#se-event-controls').hide();
        jQuery('#se-event-id').val('');
        jQuery('#se-location-id').val('');
        if (!loc) return;

        getinprogressevent('stocktake', loc, null, null, function(r) {
            if (r.found && r.event && r.event.id) {
                jQuery('#se-event-id').val(r.event.id);
                jQuery('#se-location-id').val(loc);
                jQuery('#se-event-controls').show();
                loadstock(r.event.id, '');
            } else {
                jQuery('#se-start-btn').show();
            }
        });
    });

    jQuery('#se-start-btn').on('click', function() {
        var loc = jQuery('#se-location1').val();
        if (!loc) { alert('Please select a location first.'); return; }
        jQuery('#se-start-btn').hide();
        createstockevent('stocktake', loc, null, null, null, function(event_id) {
            jQuery('#se-location-id').val(loc);
            loadstock(event_id, '');
        });
    });
});
JS;
        return $base . $extra;
    }
}
