<?php
namespace app\view\form;
use \lib\StdLib as lib;

class TransferEventForm extends StockEventForm {
    protected $trace       = false;
    protected $formname    = "transfereventform";
    protected $event_type  = "transfer";
    protected $event_label       = "Stock Transfer";
    protected $event_icon        = "&#8644;";
    protected $event_description = "Select From and To locations, then enter quantities to transfer.";

    protected function rendereventdefinition(): string {
        $html  = '<div id="se-event-def" class="se-event-def">';
        $html .= '<div class="se-event-def-row">';
        $html .= $this->renderlocationselect('se-location1', 'From location', 'se-location-select');
        $html .= '</div>';
        $html .= '<div class="se-event-def-row">';
        $html .= $this->renderlocationselect('se-location2', 'To location', 'se-location-select');
        $html .= '</div>';
        // Hidden: holds location2_id (the "To" location) for savemovement.
        // #se-location-id (in base class controls) is set to location2_id by JS.
        $html .= '<div id="se-start-area" class="se-start-area" style="display:none">';
        $html .= '<span id="se-status-msg" class="se-status-msg"></span>';
        $html .= '<button type="button" id="se-start-btn" class="vols-button">Start Transfer</button>';
        $html .= '</div>';
        $html .= '</div>';
        return $html;
    }

    protected function renderstocktableheader(): string {
        return '<tr>'
             . '<th class="se-th-name">Stock Item</th>'
             . '<th class="se-th-category">Category</th>'
             . '<th class="se-th-qty">Qty Transferred</th>'
             . '</tr>';
    }

    protected function renderstockrow(array $row): string {
        $stock_id    = (int)$row['stock_id'];
        $stock_name  = htmlspecialchars($row['stock_name']    ?? '');
        $cat_name    = htmlspecialchars($row['category_name'] ?? '');
        $movement_id = (int)($row['movement_id'] ?? 0);
        $value       = ($row['qty'] !== null && $row['qty'] !== '') ? (int)$row['qty'] : '';

        return '<tr class="se-stock-row" data-stock-id="' . $stock_id . '">'
             . '<td class="se-td-name">'     . $stock_name . '</td>'
             . '<td class="se-td-category">' . $cat_name   . '</td>'
             . '<td class="se-td-qty">'
             . '<input type="number" min="0" step="1" class="se-qty"'
             . ' data-stock-id="'    . $stock_id    . '"'
             . ' data-movement-id="' . $movement_id . '"'
             . ' value="'            . $value       . '"'
             . ' inputmode="numeric">'
             . '</td>'
             . '</tr>';
    }

    public function formscript(): string {
        $base = parent::formscript();
        $extra = <<<'JS'

// ---- TransferEventForm-specific JS ----
(function() {
    var sameLocTimer = null;

    function checktransferselections() {
        clearTimeout(sameLocTimer);
        var loc1 = jQuery('#se-location1').val();
        var loc2 = jQuery('#se-location2').val();
        jQuery('#se-start-area').hide();
        jQuery('#se-event-controls').hide();
        jQuery('#se-event-id').val('');
        jQuery('#se-location-id').val('');
        if (!loc1 || !loc2) return;
        if (loc1 === loc2) {
            sameLocTimer = setTimeout(function() {
                if (jQuery('#se-location1').val() === jQuery('#se-location2').val()) {
                    alert('From and To locations must be different.');
                    jQuery('#se-location2').val('');
                }
            }, 500);
            return;
        }

        getinprogressevent('transfer', loc1, loc2, null, function(r) {
            if (r.found && r.event && r.event.id) {
                jQuery('#se-event-id').val(r.event.id);
                jQuery('#se-location-id').val(loc2); // movements linked to "To" location
                jQuery('#se-status-msg').text('Resuming transfer in progress.');
                jQuery('#se-start-btn').text('Resume Transfer');
                jQuery('#se-start-area').show();
                jQuery('#se-event-controls').show();
                loadstock(r.event.id, '');
            } else {
                jQuery('#se-status-msg').text('No transfer in progress for these locations.');
                jQuery('#se-start-btn').text('Start Transfer');
                jQuery('#se-start-area').show();
            }
        });
    }

    jQuery(document).on('change', '#se-location1, #se-location2', checktransferselections);

    jQuery('#se-start-btn').on('click', function() {
        clearTimeout(sameLocTimer);
        var loc1 = jQuery('#se-location1').val();
        var loc2 = jQuery('#se-location2').val();
        if (!loc1) { alert('Please select a From location.'); return; }
        if (!loc2) { alert('Please select a To location.'); return; }
        if (loc1 === loc2) {
            alert('From and To locations must be different.');
            jQuery('#se-location2').val('');
            return;
        }

        var existing_id = parseInt(jQuery('#se-event-id').val() || '0');
        if (existing_id > 0) {
            jQuery('#se-event-controls').show();
            loadstock(existing_id, '');
            return;
        }

        // location1_id = From, location2_id = To; movements go to location2 (To).
        createstockevent('transfer', loc1, loc2, null, null, function(event_id) {
            jQuery('#se-location-id').val(loc2);
            loadstock(event_id, '');
        });
    });
})();
JS;
        return $base . $extra;
    }
}
