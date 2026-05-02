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
        $html .= '<button type="button" id="se-start-btn" class="vols-button" style="display:none">Start Transfer</button>';
        $html .= '</div>';
        $html .= '</div>';
        return $html;
    }

    protected function renderstocktableheader(): string {
        return '<tr>'
             . '<th class="se-th-category">Category</th>'
             . '<th class="se-th-name">Stock Item</th>'
             . '<th class="se-th-qoh">Current<br>QOH</th>'
             . '<th class="se-th-target">Target</th>'
             . '<th class="se-th-required">Required</th>'
             . '<th class="se-th-qty">Qty<br>Transferred</th>'
             . '</tr>';
    }

    protected function renderstockrow(array $row): string {
        $stock_id    = (int)$row['stock_id'];
        $stock_name  = htmlspecialchars($row['stock_name']    ?? '');
        $cat_name    = htmlspecialchars($row['category_name'] ?? '');
        $movement_id = (int)($row['movement_id'] ?? 0);
        $value       = ($row['qty'] !== null && $row['qty'] !== '' && $row['qty'] != 0) ? (int)$row['qty'] : '';
        $current_qoh = isset($row['current_qoh']) ? (int)$row['current_qoh'] : 0;
        $has_target  = ($row['target_qty'] !== null && $row['target_qty'] !== '');
        $target_qty  = $has_target ? (int)$row['target_qty'] : null;

        if ($has_target) {
            $required  = $target_qty - $current_qoh;
            $req_class = $required > 0 ? 'se-required-pos' : ($required < 0 ? 'se-required-neg' : 'se-required-zero');
            $req_html  = '<span class="se-required ' . $req_class . '">' . $required . '</span>';
            $tgt_html  = $target_qty;
        } else {
            $req_html = '<span class="se-required">–</span>';
            $tgt_html = '–';
        }

        return '<tr class="se-stock-row" data-stock-id="' . $stock_id . '" data-qoh="' . $current_qoh . '">'
             . '<td class="se-td-category">' . $cat_name    . '</td>'
             . '<td class="se-td-name">'     . $stock_name  . '</td>'
             . '<td class="se-td-qoh">'      . $current_qoh . '</td>'
             . '<td class="se-td-target">'   . $tgt_html    . '</td>'
             . '<td class="se-td-required">' . $req_html    . '</td>'
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
        jQuery('#se-start-btn').hide();
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
                jQuery('#se-location-id').val(loc2);
                jQuery('#se-event-controls').show();
                loadstock(r.event.id, '');
            } else {
                jQuery('#se-start-btn').show();
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

        jQuery('#se-start-btn').hide();
        createstockevent('transfer', loc1, loc2, null, null, function(event_id) {
            jQuery('#se-location-id').val(loc2);
            loadstock(event_id, '');
        });
    });

    jQuery(document).ready(function() {
        var defaults = jQuery('.se-event-page').data('defaults') || {};
        var preselected = false;
        if (defaults.transfer_from && !jQuery('#se-location1').val()) {
            jQuery('#se-location1').val(defaults.transfer_from);
            preselected = true;
        }
        if (defaults.transfer_to && !jQuery('#se-location2').val()) {
            jQuery('#se-location2').val(defaults.transfer_to);
            preselected = true;
        }
        if (preselected) checktransferselections();
    });

})();
JS;
        return $base . $extra;
    }
}
