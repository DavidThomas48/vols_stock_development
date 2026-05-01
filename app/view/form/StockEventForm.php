<?php
namespace app\view\form;
use \lib\StdLib as lib;

// Abstract base for all stock-event entry pages (stocktake, delivery, transfer, adjustment).
// Subclasses must implement rendereventdefinition(), renderstocktableheader(), renderstockrow().
// Extends Form directly (not StdCRUDForm) because event pages are not standard CRUD pages.
abstract class StockEventForm extends \fw\view\form\Form {
    protected $trace       = false;
    protected $formname    = "stockeventform";
    protected $event_type        = "";   // set by subclass: 'stocktake','delivery','transfer','adjustment'
    protected $event_label       = "";   // set by subclass: human-readable title
    protected $event_icon        = "";   // set by subclass: HTML entity for banner icon
    protected $event_description = "";   // set by subclass: one-line description for banner

    protected $locations  = [];
    protected $suppliers  = [];
    protected $categories = [];
    protected $event      = [];    // in-progress event record from stock_event table, or []

    // Called by ViewController's prepare_stockevent_body().
    // $event: the current in-progress event record (empty array if none exists yet).
    public function init($session, array $locations = [], array $suppliers = [], array $categories = [], array $event = []) {
        parent::init($session);
        $this->locations  = $locations;
        $this->suppliers  = $suppliers;
        $this->categories = $categories;
        $this->event      = $event;
    }

    // =========================================================================
    // Abstract hooks for subclasses
    // =========================================================================

    // Returns the HTML for the event-definition section at the top of the page.
    // Subclass renders location / supplier / client dropdowns and a "Start" button.
    abstract protected function rendereventdefinition(): string;

    // Returns the <thead> inner HTML (one <tr>) for the stock table.
    abstract protected function renderstocktableheader(): string;

    // Returns one <tr> of the stock table body for the given stock row.
    // $row contains keys: stock_id, stock_name, category_name, movement_id, qty, stock_qoh, location_id.
    abstract protected function renderstockrow(array $row): string;

    // =========================================================================
    // AJAX entry point: renders all <tr> elements for the stock table body.
    // Called by RequestHandler for stockevent_getstock.
    // =========================================================================
    public function renderstocktable(array $rows): string {
        if (empty($rows)) {
            return '<tr><td colspan="99" class="se-empty">No stock items found.</td></tr>';
        }
        $html = '';
        $lastcat = null;
        foreach ($rows as $row) {
            // Insert a category heading row when the category changes.
            $cat = $row['category_name'] ?? '';
            if ($cat !== $lastcat) {
                $html .= '<tr class="se-category-heading"><td colspan="99">'
                       . htmlspecialchars($cat ?: '(No category)')
                       . '</td></tr>';
                $lastcat = $cat;
            }
            $html .= $this->renderstockrow($row);
        }
        return $html;
    }

    // =========================================================================
    // Shared HTML helpers
    // =========================================================================

    private function renderbanner(): string {
        if (!$this->event_description) return '';
        return '<div class="se-event-header se-event-header-' . htmlspecialchars($this->event_type) . '">'
             . '<span class="se-event-header-icon">' . $this->event_icon . '</span>'
             . '<span class="se-event-header-text">' . htmlspecialchars($this->event_description) . '</span>'
             . '</div>';
    }

    //

    // Builds a <select> of locations with a blank first option.
    protected function renderlocationselect(string $id, string $label, string $class = ''): string {
        $html  = '<label for="' . $id . '">' . htmlspecialchars($label) . '</label>';
        $html .= '<select id="' . $id . '" name="' . $id . '"' . ($class ? ' class="' . $class . '"' : '') . '>';
        $html .= '<option value="">-- Select --</option>';
        foreach ($this->locations as $loc) {
            $html .= '<option value="' . (int)$loc['id'] . '">'
                   . htmlspecialchars($loc['name']) . '</option>';
        }
        $html .= '</select>';
        return $html;
    }

    // Builds a <select> of suppliers with a blank first option.
    protected function rendersupplierselect(string $id, string $label): string {
        $html  = '<label for="' . $id . '">' . htmlspecialchars($label) . '</label>';
        $html .= '<select id="' . $id . '" name="' . $id . '">';
        $html .= '<option value="">-- Select --</option>';
        foreach ($this->suppliers as $sup) {
            $html .= '<option value="' . (int)$sup['id'] . '">'
                   . htmlspecialchars($sup['name']) . '</option>';
        }
        $html .= '</select>';
        return $html;
    }

    protected function rendercategoryfilter(): string {
        $html  = '<div id="se-category-filter" class="se-category-filter">';
        $html .= '<label for="se-category">Filter by category:</label>';
        $html .= '<select id="se-category">';
        $html .= '<option value="">All categories</option>';
        foreach ($this->categories as $cat) {
            $html .= '<option value="' . (int)$cat['id'] . '">'
                   . htmlspecialchars($cat['Name']) . '</option>';
        }
        $html .= '</select>';
        $html .= '</div>';
        return $html;
    }

    private function renderdigitpad(): string {
        $html  = '<div id="se-digitpad" class="se-digitpad">';
        $html .= '<div class="se-pad-display">';
        $html .= '<input type="text" id="se-pad-display" readonly tabindex="-1" placeholder="0">';
        $html .= '</div>';
        $html .= '<div class="se-pad-keys">';
        $keys = ['7','8','9','4','5','6','1','2','3','clear','0','back'];
        foreach ($keys as $k) {
            if ($k === 'clear') {
                $html .= '<button type="button" class="se-digit-btn se-digit-clear" data-key="clear" tabindex="-1">CLR</button>';
            } elseif ($k === 'back') {
                $html .= '<button type="button" class="se-digit-btn se-digit-back" data-key="back" tabindex="-1">&#9003;</button>';
            } else {
                $html .= '<button type="button" class="se-digit-btn" data-key="' . $k . '" tabindex="-1">' . $k . '</button>';
            }
        }
        $html .= '</div>';
        $html .= '</div>';
        return $html;
    }

    // The "event controls" section: category filter, stock table, digit pad, Close/Cancel buttons.
    // Hidden on initial page load when no event is in progress; shown by JS after event is created.
    private function rendereventcontrols(): string {
        $event_id = isset($this->event['id']) ? (int)$this->event['id'] : 0;
        $style    = $event_id ? '' : ' style="display:none"';
        $html     = '<div id="se-event-controls" class="se-event-controls"' . $style . '>';

        // Hidden state fields used by all AJAX calls.
        $html .= '<input type="hidden" id="se-event-id"    value="' . $event_id . '">';
        $html .= '<input type="hidden" id="se-event-type"  value="' . htmlspecialchars($this->event_type) . '">';
        $html .= '<input type="hidden" id="se-location-id" value="">';  // set by JS after event definition

        $html .= $this->rendercategoryfilter();

        $html .= '<div id="se-stock-table-container" class="se-stock-table-container">';
        $html .= '<table id="se-stock-table" class="se-stock-table">';
        $html .= '<thead>' . $this->renderstocktableheader() . '</thead>';
        $html .= '<tbody id="se-stock-table-body"></tbody>';
        $html .= '</table>';
        $html .= '</div>';

        $html .= $this->renderdigitpad();

        $html .= '<div id="se-action-buttons" class="se-action-buttons">';
        $html .= '<button type="button" id="se-close-btn"  class="vols-button" onclick="closestockevent()">Close Event</button>';
        $html .= '<button type="button" id="se-cancel-btn" class="vols-button vols-button-danger" onclick="cancelstockevent()">Cancel Event</button>';
        $html .= '</div>';

        $html .= '</div>'; // se-event-controls
        return $html;
    }

    // =========================================================================
    // render() — called by StandardBody
    // =========================================================================
    public function render($pagenum = '', $nextpage = '', $subheading = "", $rights = [], $isadmin = false, $menu = "", $trace = false): string {
        if ($this->trace || $trace) { echo "Enter " . __METHOD__ . "<br>"; }

        $greeting = htmlspecialchars($this->session->getgreeting());
        $html  = '<div class="greetingcontainer">';
        $html .= '  <div class="greeting">Welcome ' . $greeting . '</div>';
        $html .= $menu;
        $html .= '</div>';
        $html .= '<div class="vols-form-content se-event-page">';
        $html .= '<h2 class="vols-form-pageheading">' . htmlspecialchars($this->event_label) . '</h2>';
        $html .= $this->renderbanner();
        $html .= $this->rendereventdefinition();
        $html .= $this->rendereventcontrols();
        $html .= '<script>' . $this->formscript() . '</script>';
        $html .= '</div>';

        if ($this->trace || $trace) { echo "Leave " . __METHOD__ . "<br>"; }
        return $html;
    }

    // =========================================================================
    // Shared JavaScript
    // =========================================================================
    public function formscript(): string {
        return <<<'JS'
(function() {
    var $activeInput = null;

    // Track which qty input is active (for digit pad).
    jQuery(document).on('focus', '.se-qty', function() {
        $activeInput = jQuery(this);
        jQuery('#se-pad-display').val(jQuery(this).val());
    });

    // Digit pad key press.
    jQuery(document).on('click touchstart', '.se-digit-btn', function(e) {
        e.preventDefault();
        if (!$activeInput) return;
        var key = jQuery(this).data('key');
        var cur = $activeInput.val();
        if (key === 'clear') {
            $activeInput.val('');
        } else if (key === 'back') {
            $activeInput.val(cur.slice(0, -1));
        } else if (/^[0-9]$/.test(key)) {
            $activeInput.val(cur + key);
        }
        jQuery('#se-pad-display').val($activeInput.val());
    });

    // Auto-save when a qty field loses focus.
    jQuery(document).on('blur', '.se-qty', function() {
        savemovement(jQuery(this));
    });

    // Tab moves to the next qty input instead of into the digit pad.
    jQuery(document).on('keydown', '.se-qty', function(e) {
        if (e.key !== 'Tab' || e.shiftKey) return;
        var $inputs = jQuery('.se-qty:visible');
        var $next   = $inputs.eq($inputs.index(this) + 1);
        if ($next.length) {
            e.preventDefault();
            $next.focus();
        }
    });

    // Reload stock table when category filter changes.
    jQuery(document).on('change', '#se-category', function() {
        var event_id = jQuery('#se-event-id').val();
        if (event_id && parseInt(event_id) > 0) {
            loadstock(event_id, jQuery(this).val(), '');
        }
    });

    // If an event is already in progress on page load, show controls and load stock.
    jQuery(function() {
        var event_id = parseInt(jQuery('#se-event-id').val() || '0');
        if (event_id > 0) {
            jQuery('#se-event-controls').show();
            loadstock(event_id, '');
        }

        // Recalculate table height whenever se-event-controls becomes visible,
        // and whenever the window is resized.
        var ctrl = document.getElementById('se-event-controls');
        if (ctrl) {
            new MutationObserver(function() {
                if (jQuery('#se-event-controls').is(':visible')) {
                    setTimeout(resizestocktable, 0);
                }
            }).observe(ctrl, { attributes: true, attributeFilter: ['style', 'class'] });
        }
        jQuery(window).on('resize', resizestocktable);
        if (jQuery('#se-event-controls').is(':visible')) {
            resizestocktable();
        }
    });
})();

function savemovement($input) {
    var stock_id    = $input.data('stock-id');
    var movement_id = $input.data('movement-id') || 0;
    var value       = $input.val();
    var event_id    = jQuery('#se-event-id').val();
    var location_id = jQuery('#se-location-id').val();
    var event_type  = jQuery('#se-event-type').val();
    if (!stock_id || !event_id) return;
    doServerRequest(0, JSON.stringify({
        stock_id:    stock_id,
        movement_id: movement_id,
        value:       value,
        event_id:    event_id,
        location_id: location_id,
        event_type:  event_type
    }), 'stockevent_savemovement').then(function(resp) {
        try {
            var r = JSON.parse(resp);
            if (r.success) {
                $input.data('movement-id', r.movement_id);
                var $row = $input.closest('tr');
                $row.addClass('se-saved');
                setTimeout(function() { $row.removeClass('se-saved'); }, 1000);
            } else {
                alert('Error saving: ' + r.error);
            }
        } catch(ex) { console.error('savemovement parse error', ex, resp); }
    });
}

function resizestocktable() {
    var $container = jQuery('#se-stock-table-container');
    if (!$container.length || !$container.is(':visible')) return;
    var containerTop = $container[0].getBoundingClientRect().top;
    var belowHeight = 0;
    jQuery('#se-digitpad, #se-action-buttons').each(function() {
        belowHeight += jQuery(this).outerHeight(true) || 0;
    });
    var available = Math.max(200, window.innerHeight - containerTop - belowHeight - 16);
    $container.css('max-height', available + 'px');
}

function loadstock(event_id, category_id, supplier_id) {
    var event_type = jQuery('#se-event-type').val();
    jQuery('#se-stock-table-body').html('<tr><td colspan="99" class="se-loading">Loading\u2026</td></tr>');
    doServerRequest(0, JSON.stringify({
        event_id:    event_id,
        category_id: category_id || '',
        supplier_id: supplier_id || '',
        event_type:  event_type
    }), 'stockevent_getstock').then(function(resp) {
        jQuery('#se-stock-table-body').html(resp);
        resizestocktable();
    });
}

function closestockevent() {
    if (!confirm('Close this event? This will finalise all entries.')) return;
    var event_id = jQuery('#se-event-id').val();
    doServerRequest(0, JSON.stringify({ event_id: event_id }), 'stockevent_closeevent').then(function(resp) {
        try {
            var r = JSON.parse(resp);
            if (r.success) { location.reload(); } else { alert('Cannot close: ' + r.error); }
        } catch(ex) { console.error(ex, resp); }
    });
}

function cancelstockevent() {
    if (!confirm('Cancel this event? All entries will be deleted.')) return;
    var event_id = jQuery('#se-event-id').val();
    doServerRequest(0, JSON.stringify({ event_id: event_id }), 'stockevent_cancelevent').then(function(resp) {
        try {
            var r = JSON.parse(resp);
            if (r.success) { location.reload(); } else { alert('Cannot cancel: ' + r.error); }
        } catch(ex) { console.error(ex, resp); }
    });
}

function createstockevent(event_type, location1_id, location2_id, supplier_id, stock_client_id, onSuccess) {
    doServerRequest(0, JSON.stringify({
        event_type:      event_type,
        location1_id:    location1_id    || '',
        location2_id:    location2_id    || '',
        supplier_id:     supplier_id     || '',
        stock_client_id: stock_client_id || ''
    }), 'stockevent_createevent').then(function(resp) {
        try {
            var r = JSON.parse(resp);
            if (r.success) {
                jQuery('#se-event-id').val(r.event_id);
                jQuery('#se-event-controls').show();
                if (typeof onSuccess === 'function') onSuccess(r.event_id);
            } else {
                alert('Cannot start event: ' + r.error);
            }
        } catch(ex) { console.error(ex, resp); }
    });
}

function getinprogressevent(event_type, location1_id, location2_id, supplier_id, onResult) {
    doServerRequest(0, JSON.stringify({
        event_type:   event_type,
        location1_id: location1_id || '',
        location2_id: location2_id || '',
        supplier_id:  supplier_id  || ''
    }), 'stockevent_getinprogressevent').then(function(resp) {
        try {
            var r = JSON.parse(resp);
            if (typeof onResult === 'function') onResult(r);
        } catch(ex) { console.error(ex, resp); }
    });
}
JS;
    }
}
