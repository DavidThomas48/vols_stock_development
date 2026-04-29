<?php
namespace apptable;
use \lib\StdLib as lib;
class StockTable extends \fw\database\table\MySQLTable
{
    private $trace = false;
    public function init($db, $user_id="null") {
        if ($this->trace) { echo 'Enter '.__METHOD__.'<br>'; }
        parent::init($db, $user_id);
        $this->fields = array(
            "id"          => "",
            "Name"        => "",
            "Code"        => "",
            "category_id" => "",
        );
        if ($this->trace) { echo 'Leave '.__METHOD__.'<br>'; }
    }

    // $location_id: when non-empty, restricts all calculations to that location.
    // When empty, runs the per-location calculation independently for every location
    // that has stock movements and sums the results — each location uses its own
    // last-stocktake baseline without conflating baselines across locations.
    // $as_at: MySQL datetime 'YYYY-MM-DD HH:MM:SS'. When set, any event with
    // date_created after this time is ignored and the stocktake search works
    // backwards from this time rather than from now.
    public function getstockwithlevels(&$results, &$numrows, $location_id='', $as_at='', $trace=false) {
        if ($this->trace || $trace) { echo 'Enter '.__METHOD__.'<br>'; }

        if (empty($location_id)) {
            $loc_rows = []; $loc_n = 0;
            $this->query(
                "SELECT DISTINCT location_id FROM stock_movement"
                . " WHERE location_id IS NOT NULL AND location_id > 0",
                $loc_rows, $loc_n, $trace
            );

            if ($loc_n === 0) {
                // No movements recorded yet — return all items with zero quantities.
                $q  = "SELECT s.id, s.Name, s.Code, s.category_id,";
                $q .= " sc.Name as category_name,";
                $q .= " NULL as stocktake_date, 0 as stocktake_qty,";
                $q .= " 0 as deliveries_since, 0 as transfers_since,";
                $q .= " 0 as adjustments_since, 0 as issues_since, 0 as current_qty";
                $q .= " FROM stock s LEFT JOIN stock_category sc ON s.category_id = sc.id";
                $q .= " ORDER BY sc.Name, s.Name";
                $success = $this->query($q, $results, $numrows, $trace);
                if ($this->trace || $trace) { echo 'Leave '.__METHOD__."  ({$numrows} rows)<br>"; }
                return $success;
            }

            $aggregated = [];
            foreach ($loc_rows as $loc) {
                $loc_data = []; $loc_num = 0;
                $this->getstockwithlevels($loc_data, $loc_num, $loc['location_id'], $as_at, $trace);
                foreach ($loc_data as $row) {
                    $sid = $row['id'];
                    if (!isset($aggregated[$sid])) {
                        $aggregated[$sid] = [
                            'id'                => $row['id'],
                            'Name'              => $row['Name'],
                            'Code'              => $row['Code'],
                            'category_id'       => $row['category_id'],
                            'category_name'     => $row['category_name'],
                            'stocktake_date'    => null,
                            'stocktake_qty'     => (float)($row['stocktake_qty']     ?? 0),
                            'deliveries_since'  => (float)($row['deliveries_since']  ?? 0),
                            'transfers_since'   => (float)($row['transfers_since']   ?? 0),
                            'adjustments_since' => (float)($row['adjustments_since'] ?? 0),
                            'issues_since'      => (float)($row['issues_since']      ?? 0),
                            'current_qty'       => (float)($row['current_qty']       ?? 0),
                        ];
                    } else {
                        $aggregated[$sid]['stocktake_qty']     += (float)($row['stocktake_qty']     ?? 0);
                        $aggregated[$sid]['deliveries_since']  += (float)($row['deliveries_since']  ?? 0);
                        $aggregated[$sid]['transfers_since']   += (float)($row['transfers_since']   ?? 0);
                        $aggregated[$sid]['adjustments_since'] += (float)($row['adjustments_since'] ?? 0);
                        $aggregated[$sid]['issues_since']      += (float)($row['issues_since']      ?? 0);
                        $aggregated[$sid]['current_qty']       += (float)($row['current_qty']       ?? 0);
                    }
                }
            }

            usort($aggregated, fn($a, $b) =>
                ($c = strcmp($a['category_name'] ?? '', $b['category_name'] ?? '')) !== 0
                    ? $c : strcmp($a['Name'], $b['Name'])
            );

            $results = array_values($aggregated);
            $numrows = count($results);
            if ($this->trace || $trace) { echo 'Leave '.__METHOD__."  ({$numrows} rows)<br>"; }
            return true;
        }

        // Per-location: use the most recent closed stocktake at this location as the
        // baseline, then add movements of each type that occurred after it.
        $lid    = $this->real_escape_string($location_id);
        $loc_x  = " AND sm_x.location_id = '{$lid}'";
        $loc_st = " AND sm_st.location_id = '{$lid}'";
        $loc_mv = " AND {alias}.location_id = '{$lid}'";

        // When an as_at cutoff is supplied, ignore any event created after that time.
        $as_at_safe    = !empty($as_at) ? $this->real_escape_string($as_at) : '';
        $as_at_st_cond = $as_at_safe  ? " AND se_x.date_created <= '{$as_at_safe}'" : '';

        // Correlated subquery: id of the most recent closed stocktake for this stock item at this location
        // (at or before as_at when supplied).
        $last_st_id =
            "(SELECT se_x.id"
            . " FROM stock_movement sm_x"
            . " JOIN stock_event se_x ON sm_x.stock_event_id = se_x.id"
            . " WHERE sm_x.stock_id = s.id{$loc_x}"
            . "   AND se_x.event = 'stocktake' AND se_x.status = 'closed'{$as_at_st_cond}"
            . " ORDER BY se_x.date_created DESC LIMIT 1)";

        // Correlated subquery: date_created of that event.
        $last_st_date =
            "(SELECT se_x.date_created"
            . " FROM stock_movement sm_x"
            . " JOIN stock_event se_x ON sm_x.stock_event_id = se_x.id"
            . " WHERE sm_x.stock_id = s.id{$loc_x}"
            . "   AND se_x.event = 'stocktake' AND se_x.status = 'closed'{$as_at_st_cond}"
            . " ORDER BY se_x.date_created DESC LIMIT 1)";

        // Sum of actual counts (stock_qoh) from the most recent stocktake.
        $st_qty =
            "COALESCE("
            . "(SELECT SUM(sm_st.stock_qoh)"
            . " FROM stock_movement sm_st"
            . " WHERE sm_st.stock_id = s.id{$loc_st}"
            . "   AND sm_st.stock_event_id = {$last_st_id})"
            . ", 0)";

        // Helper: sum qty for a given closed event type since the last stocktake
        // (and no later than as_at when supplied).
        // When no stocktake baseline exists (last_st_id IS NULL) all qualifying
        // closed events of this type are included regardless of date.
        $sum_since = fn($alias, $event_type) =>
            "COALESCE("
            . "(SELECT SUM({$alias}.qty)"
            . " FROM stock_movement {$alias}"
            . " JOIN stock_event se_{$alias} ON {$alias}.stock_event_id = se_{$alias}.id"
            . " WHERE {$alias}.stock_id = s.id"
            . str_replace('{alias}', $alias, $loc_mv)
            . "   AND se_{$alias}.event = '{$event_type}'"
            . "   AND se_{$alias}.status = 'closed'"
            . ($as_at_safe ? "   AND se_{$alias}.date_created <= '{$as_at_safe}'" : "")
            . "   AND ({$last_st_id} IS NULL"
            . "        OR se_{$alias}.date_created > {$last_st_date}))"
            . ", 0)";

        $deliv = $sum_since('sm_d', 'delivery');
        $trans = $sum_since('sm_t', 'transfer');
        $adj   = $sum_since('sm_a', 'adjustment');
        $iss   = $sum_since('sm_i', 'issue');

        $query  = "SELECT s.id, s.Name, s.Code, s.category_id, sc.Name as category_name,";
        $query .= " {$last_st_date} as stocktake_date,";
        $query .= " {$st_qty} as stocktake_qty,";
        $query .= " {$deliv} as deliveries_since,";
        $query .= " {$trans} as transfers_since,";
        $query .= " {$adj} as adjustments_since,";
        $query .= " {$iss} as issues_since,";
        $query .= " {$st_qty} + {$deliv} + {$trans} + {$adj} - {$iss} as current_qty";
        $query .= " FROM stock s";
        $query .= " LEFT JOIN stock_category sc ON s.category_id = sc.id";
        $query .= " ORDER BY sc.Name, s.Name";

        $success = $this->query($query, $results, $numrows, $trace);
        if ($this->trace || $trace) { echo 'Leave '.__METHOD__."  ({$numrows} rows)<br>"; }
        return $success;
    }
}
