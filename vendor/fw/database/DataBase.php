<?php
namespace fw\database;
use  \lib\StdLib as lib;
abstract class DataBase
{
    private $res = null;
    private $insert_id = null;
    private $affected_rows = null;   
    private $errorhandler = null;
    private $debugtrace = false;   
    private $indent = "&nbsp;&nbsp;&nbsp;&nbsp";
    abstract function init($errorhandler);
    abstract function connect($host, $user, $password, $dbname);
    abstract function dbquery($query, &$result, &$numrows,&$errormessage, $querytype, $log=false, &$matchedrows=0);
    abstract function select($tablename, $fields, $where_clause, $groupby,$having,$orderby,$locktype, &$results, &$numrows);
    abstract function get_insert_id();
    abstract function real_escape_string(string $string): string;
    abstract function execute_params(string $sql, array $params, &$result, &$numrows, &$errormessage, int $querytype=0, $trace=false): bool;
   // abstract function getcolumnnames($table_name,&$columns);
    public function __destruct()
    {
        if (VERBOSE || $this->debugtrace) { echo "Enter ".__METHOD__."<br>"; }
        $this->mysqli->close() ; //$link
    }
    private function getmatches()
    {
        preg_match_all('/(\S[^:]+) :(\d+) /', $this->mysqli->info, $matches) ;
        //var_Dump($matches) ;echo "<BR>";
        if (count($matches[1]) && count($matches[2]) ) {
            $info = array_combine($matches[1], $matches[2]) ;
            return $info['Rows matched'];
        } else {
                return 0;
        }
    }
      
    public function freeresults($results)
    {
        if (VERBOSE || $this->debugtrace) { echo "Enter ".__METHOD__."<br>"; }
        $this->res->close() ;
    }
       
    public function printHTMLresults($result, $tableclass, $colnames, $rownumbers)
    {
        $nl = "\n";
        if (VERBOSE || $this->debugtrace) { echo "Enter ".__METHOD__."<br>"; }
        $rn = isset($rownumbers) && $rownumbers = true;
        echo '<table class="', $tableclass, '">'.$nl;
        //column headings
        if (isset($colnames) && $colnames = true) {
            $line = mysql_fetch_array($result, MYSQL_ASSOC) ;
            echo "\t<tr>$nl";
            if ($rn = true) {
                echo "\t\t<th>#</th>".$nl;
            }
            foreach(array_keys($line) as $col_value) {
                echo "\t\t<th>$col_value</th>".$nl;
            }
           
            echo "\t</tr>".$nl;
        }
        //rows
        $i=0;
        do {
            echo "\t<tr>".$nl;
            $i++;
            if ($rn = true) {
                echo "\t\t<th>$i</th>".$nl;
            }
            foreach($line as $col_value) {
                echo "\t\t<td>$col_value</td>".$nl;
            }
            echo "\t</tr>".$nl;
        }while ($line = mysql_fetch_array($result, MYSQL_ASSOC) ) ;
        //close
        echo "</table>$nl";
    }
}
