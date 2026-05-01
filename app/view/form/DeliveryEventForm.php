<?php
namespace app\view\form;
use \lib\StdLib as lib;

class DeliveryEventForm extends StockEventForm {
    protected $trace             = false;
    protected $formname          = "deliveryeventform";
    protected $event_type        = "delivery";
    protected $event_label       = "Delivery";
    protected $event_icon        = "&#8679;";
    protected $event_description = "Select the receiving location and supplier, then enter the quantity received for each stock item.";

    protected function rendereventdefinition(): string {
        $html  = '<div id="se-event-def" class="se-event-def">';
        $html .= '<div class="se-event-def-row">';
        $html .= $this->renderlocationselect('se-location1', 'Receiving location', 'se-location-select');
        $html .= '</div>';
        $html .= '<div class="se-event-def-row">';
        $html .= $this->rendersupplierselect('se-supplier', 'Supplier');
        $html .= '</div>';
        $html .= '<div id="se-start-area" class="se-start-area" style="display:none">';
        $html .= '<span id="se-status-msg" class="se-status-msg"></span>';
        $html .= '<button type="button" id="se-start-btn" class="vols-button">Start Delivery</button>';
        $html .= '</div>';
        $html .= '</div>';
        return $html;
    }

    // Each supplier shows as one option:
    //   "[Name] – continue (DD Mon YYYY)" with data-event-id if an in-progress delivery exists
    //   "[Name] – New Delivery" with data-event-id="0" if not
    protected function rendersupplierselect(string $id, string $label): string {
        $html  = '<label for="' . $id . '">' . htmlspecialchars($label) . '</label>';
        $html .= '<select id="' . $id . '" name="' . $id . '">';
        $html .= '<option value="">-- Select --</option>';
        foreach ($this->suppliers as $sup) {
            $event_id = (int)($sup['in_progress_event_id'] ?? 0);
            if ($event_id > 0) {
                $date  = date('d M Y', strtotime($sup['in_progress_date']));
                $label_text = $sup['name'] . ' – continue (' . $date . ')';
            } else {
                $label_text = $sup['name'] . ' – New Delivery';
            }
            $html .= '<option value="' . (int)$sup['id'] . '" data-event-id="' . $event_id . '">'
                   . htmlspecialchars($label_text) . '</option>';
        }
        $html .= '</select>';
        return $html;
    }

    // Overrides base: adds data-supplier-ids to each category option so JS can
    // filter the list when the supplier changes.
    protected function rendercategoryfilter(): string {
        $html  = '<div id="se-category-filter" class="se-category-filter">';
        $html .= '<label for="se-category">Filter by category:</label>';
        $html .= '<select id="se-category">';
        $html .= '<option value="">All categories</option>';
        foreach ($this->categories as $cat) {
            $sup_ids = implode(',', $cat['supplier_ids'] ?? []);
            $html .= '<option value="' . (int)$cat['id'] . '"'
                   . ' data-supplier-ids="' . htmlspecialchars($sup_ids) . '">'
                   . htmlspecialchars($cat['Name']) . '</option>';
        }
        $html .= '</select>';
        $html .= '</div>';
        return $html;
    }

    protected function renderstocktableheader(): string {
        return '<tr>'
             . '<th class="se-th-name">Stock Item</th>'
             . '<th class="se-th-category">Category</th>'
             . '<th class="se-th-qty">Qty Received</th>'
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

// ---- DeliveryEventForm-specific JS ----
(function() {

    // Filter category dropdown to only show categories supplied by the selected supplier.
    function filtercategoriesbysupplier(sup_id) {
        if (!sup_id) {
            jQuery('#se-category option').show();
            jQuery('#se-category').val('');
            return;
        }
        // Check whether any categories are linked to this supplier.
        var hasLinked = false;
        jQuery('#se-category option[value!=""]').each(function() {
            var ids = (jQuery(this).data('supplier-ids') || '').toString();
            if (ids && ids.split(',').indexOf(String(sup_id)) !== -1) {
                hasLinked = true;
            }
        });
        jQuery('#se-category option').each(function() {
            if (!jQuery(this).val()) { jQuery(this).show(); return; }
            if (!hasLinked) {
                // No supplier-category links configured — show all categories.
                jQuery(this).show();
            } else {
                var ids = (jQuery(this).data('supplier-ids') || '').toString();
                var list = ids ? ids.split(',') : [];
                jQuery(this).toggle(list.indexOf(String(sup_id)) !== -1);
            }
        });
        jQuery('#se-category').val('');
    }

    function checkdeliveryselections() {
        var loc   = jQuery('#se-location1').val();
        var sup   = jQuery('#se-supplier').val();
        jQuery('#se-start-area').hide();
        jQuery('#se-event-controls').hide();
        jQuery('#se-event-id').val('');
        jQuery('#se-location-id').val('');
        if (!loc || !sup) return;

        // The selected option already carries the in-progress event id — no AJAX needed.
        var event_id = parseInt(jQuery('#se-supplier option:selected').data('event-id') || '0');
        if (event_id > 0) {
            jQuery('#se-event-id').val(event_id);
            jQuery('#se-location-id').val(loc);
            jQuery('#se-status-msg').text('Resuming delivery in progress.');
            jQuery('#se-start-btn').text('Resume Delivery');
            jQuery('#se-start-area').show();
            jQuery('#se-event-controls').show();
            loadstock(event_id, '', '');
        } else {
            jQuery('#se-status-msg').text('No delivery in progress for this supplier.');
            jQuery('#se-start-btn').text('Start Delivery');
            jQuery('#se-start-area').show();
        }
    }

    jQuery(document).on('change', '#se-location1', checkdeliveryselections);

    jQuery(document).on('change', '#se-supplier', function() {
        filtercategoriesbysupplier(jQuery(this).val());
        checkdeliveryselections();
    });

    jQuery('#se-start-btn').on('click', function() {
        var loc = jQuery('#se-location1').val();
        var sup = jQuery('#se-supplier').val();
        if (!loc) { alert('Please select a receiving location.'); return; }
        if (!sup) { alert('Please select a supplier.'); return; }

        var existing_id = parseInt(jQuery('#se-event-id').val() || '0');
        if (existing_id > 0) {
            jQuery('#se-event-controls').show();
            loadstock(existing_id, '', '');
            return;
        }

        createstockevent('delivery', loc, null, sup, null, function(event_id) {
            jQuery('#se-location-id').val(loc);
            loadstock(event_id, '', '');
        });
    });
})();
JS;
        return $base . $extra;
    }
}
