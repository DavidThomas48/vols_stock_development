<?php
namespace app\controller\manager;
use \lib\StdLib as lib;
class BelowMinimumReportManager extends \fw\controller\manager\StdManager
{
    private $trace        = false;
    protected $name       = "Below Minimum Report";
    protected $db;
    protected $linkedobject = "";
    private $location_id  = '';

    public function __construct(
        protected \apptable\StockTable             $table,
        protected \apptable\StockLocationTable     $locationtable,
        protected \apptable\StockItemLocationTable $itemlocationtable
    ) {}

    public function init($session, $trace=false) {
        parent::init($session);
        $this->locationtable->init($this->db, $this->user_id);
        $this->itemlocationtable->init($this->db, $this->user_id);
    }

    public function setlocation($location_id) {
        $this->location_id = $location_id;
    }

    public function getallrecords(&$datafields, $orderby, &$parents, &$numrows, $withlock=false, $trace=false) {
        if ($this->trace || $trace) { echo "Enter ".__METHOD__."<br>"; }

        $locations = []; $locnum = 0;
        $this->locationtable->selectall($locations, $locnum, "name");

        $locnames = [];
        foreach ($locations as $loc) {
            $locnames[$loc['id']] = $loc['name'];
        }

        // Fetch stock_item_location rows that have a minimum_qty set.
        $minqtys = []; $minnum = 0;
        $this->itemlocationtable->getminimumqtys($this->location_id, $minqtys, $minnum);

        // Index: [location_id][stock_id] => min_qty
        $minindex = [];
        foreach ($minqtys as $row) {
            $minindex[$row['stock_location_id']][$row['stock_id']] = (int)$row['minimum_qty'];
        }

        $loc_ids_to_check = !empty($this->location_id)
            ? [$this->location_id]
            : array_unique(array_column($minqtys, 'stock_location_id'));

        $datafields = [];
        foreach ($loc_ids_to_check as $lid) {
            if (!isset($minindex[$lid])) continue;
            $levels = []; $levnum = 0;
            $this->table->getstockwithlevels($levels, $levnum, $lid, '', $trace);
            foreach ($levels as $row) {
                $sid = $row['id'];
                if (!isset($minindex[$lid][$sid])) continue;
                $min_qty = $minindex[$lid][$sid];
                $current = (int)round((float)($row['current_qty'] ?? 0));
                if ($current < $min_qty) {
                    $datafields[] = [
                        'id'            => $sid,
                        'Name'          => $row['Name'],
                        'category_name' => $row['category_name'] ?? '',
                        'location_name' => $locnames[$lid]        ?? 'Unknown',
                        'location_id'   => $lid,
                        'current_qty'   => $current,
                        'minimum_qty'   => $min_qty,
                        'variance'      => $current - $min_qty,
                    ];
                }
            }
        }

        usort($datafields, function($a, $b) {
            $c = strcmp($a['location_name'], $b['location_name']);
            if ($c !== 0) return $c;
            $c2 = strcmp($a['category_name'], $b['category_name']);
            return $c2 !== 0 ? $c2 : strcmp($a['Name'], $b['Name']);
        });

        $numrows = count($datafields);
        $parents = [
            'locations'   => $locations,
            'location_id' => $this->location_id,
        ];

        if ($this->trace || $trace) { echo "Leave ".__METHOD__." ({$numrows} rows)<br>"; }
        return true;
    }
}
