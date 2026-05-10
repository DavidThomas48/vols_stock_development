<?php
namespace database;

class MySqlDB extends \fw\database\DataBase
{
    private $trace = false;
    private $pdo   = null;
    private $insert_id = null;
    private $errorhandler = null;
    private $indent;
    private $outparams;

    public function __construct() {
        if ($this->trace) { echo "Enter ".__METHOD__."<br>"; }
    }

    public function __destruct() {
        if ($this->trace) { echo "Enter ".__METHOD__."<br>"; }
        $this->pdo = null;
    }

    public function init($errorhandler) {
        if ($this->trace) { echo "Enter ".__METHOD__."<br>"; }
        $this->errorhandler = $errorhandler;
    }

    public function connect($host, $user, $password, $dbname) {
        if ($this->trace) { echo "Enter ".__METHOD__."<br>"; }
        try {
            $dsn = "mysql:host={$host};dbname={$dbname};charset=utf8mb4";
            $this->pdo = new \PDO($dsn, $user, $password, [
                \PDO::ATTR_ERRMODE            => \PDO::ERRMODE_EXCEPTION,
                \PDO::ATTR_DEFAULT_FETCH_MODE => \PDO::FETCH_ASSOC,
                \PDO::ATTR_EMULATE_PREPARES   => false,
                \PDO::MYSQL_ATTR_FOUND_ROWS   => true,
            ]);
        } catch (\PDOException $e) {
            die("error initialising database driver: " . $e->getMessage());
        }
    }

    public function resetconnection($host, $user, $password, $dbname) {
        try {
            $this->pdo = null;
            $this->connect($host, $user, $password, $dbname);
        } catch (\PDOException $e) {
            die("error in ".__METHOD__.": " . $e->getMessage());
        }
    }

    public function starttransaction($flags=0, $name=null) {
        try {
            return $this->pdo->beginTransaction();
        } catch (\PDOException $e) {
            die("error in ".__METHOD__.": " . $e->getMessage());
        }
    }

    public function commit($flags=0, $name="") {
        try {
            return $this->pdo->commit();
        } catch (\PDOException $e) {
            die("error in ".__METHOD__.": " . $e->getMessage());
        }
    }

    public function rollback() {
        try {
            return $this->pdo->rollBack();
        } catch (\PDOException $e) {
            die("error in ".__METHOD__.": " . $e->getMessage());
        }
    }

    protected function clearstoredresults($query_result, $trace=false) {
        // PDO cleans up automatically when the statement variable goes out of scope
    }

    public function buildresultsarray($query_result, &$results, $trace=false) {
        if ($this->trace || $trace) { echo "Enter ".__METHOD__."<br>"; }
        $results = is_array($query_result) ? $query_result : [];
        if ($this->trace || $trace) { echo "Leave ".__METHOD__."<br>"; }
    }

    public function settimezone($timezone='') {
        global $siteglobals;
        $tz = empty($timezone) ? $siteglobals["MYSQLDEFAULTTIMEZONE"] : $timezone;
        return $this->execute_params("SET time_zone = ?", [$tz], $r, $n, $em, 2);
    }

    public function processresults($query_result, &$results, $logresult, $trace=false) {
        $this->buildresultsarray($query_result, $results);
        if ($logresult) {
            $this->errorhandler->dblog("RESULT>>\n " . print_r($results, 1));
        }
    }

    // Runs a raw SQL string. Detects SELECT vs mutation by examining the first keyword so
    // that callers using querytype=1 with SELECT statements (MySQLTable::query()) still work.
    public function dbquery($query, &$result, &$numrows, &$errormessage, $querytype, $log=0, &$matchedrows=0, $trace=false, $noerrorhandler=false) {
        if ($this->trace || $trace) { echo "Enter ".__METHOD__."({$querytype}: {$query})<br>\n"; }
        try {
            $stmt    = $this->pdo->query($query);
            if ($log && !$noerrorhandler) $this->errorhandler->dblog($query);

            $trimmed   = ltrim($query);
            $is_select = (strncasecmp($trimmed, 'SELECT', 6) === 0
                       || strncasecmp($trimmed, 'SHOW',   4) === 0
                       || strncasecmp($trimmed, 'DESCRI', 6) === 0);

            if ($is_select || $querytype === 0) {
                $result  = $stmt->fetchAll();
                $numrows = $matchedrows = count($result);
            } elseif (strncasecmp($trimmed, 'CALL', 4) === 0) {
                $result = $stmt->fetchAll();
                while ($stmt->nextRowset()) { /* consume extra result sets from stored proc */ }
                $numrows = $matchedrows = count($result);
            } else {
                $numrows = $matchedrows = $stmt->rowCount();
                if (strncasecmp($trimmed, 'INSERT', 6) === 0) {
                    $result = $this->insert_id = (int)$this->pdo->lastInsertId();
                } else {
                    $result = true;
                }
            }
            $success = true;
        } catch (\PDOException $e) {
            $errormessage = "Query Failed: (" . $e->getCode() . ") " . $e->getMessage() . "\n\n(" . $query . ")";
            if (!$noerrorhandler && $this->errorhandler) {
                $this->errorhandler->sqlerror(null, $errormessage, $query);
            }
            $success = false;
        }
        if ($this->trace || $trace) { echo "Leave ".__METHOD__." success={$success}<br>\n"; }
        return $success;
    }

    // Executes a parameterised query using a prepared statement.
    // $querytype: 0 = SELECT (returns rows in $result), 1 = mutation (returns insert id or true), 2 = SET/other
    public function execute_params(string $sql, array $params, &$result, &$numrows, &$errormessage, int $querytype=0, $trace=false): bool {
        if ($this->trace || $trace) { echo "Enter ".__METHOD__."<br>\n"; }
        try {
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute($params);

            $trimmed   = ltrim($sql);
            $is_select = (strncasecmp($trimmed, 'SELECT', 6) === 0
                       || strncasecmp($trimmed, 'SHOW',   4) === 0
                       || strncasecmp($trimmed, 'DESCRI', 6) === 0);

            if ($is_select || $querytype === 0) {
                $result  = $stmt->fetchAll();
                $numrows = count($result);
            } else {
                $numrows = $stmt->rowCount();
                if (strncasecmp($trimmed, 'INSERT', 6) === 0) {
                    $result = $this->insert_id = (int)$this->pdo->lastInsertId();
                } else {
                    $result = true;
                }
            }
            $success = true;
        } catch (\PDOException $e) {
            $errormessage = "Query Failed: (" . $e->getCode() . ") " . $e->getMessage() . "\n\n(" . $sql . ")";
            if ($this->errorhandler) {
                $this->errorhandler->sqlerror(null, $errormessage, $sql);
            }
            $success = false;
        }
        if ($this->trace || $trace) { echo "Leave ".__METHOD__." success={$success}<br>\n"; }
        return $success;
    }

    public function select($tablename, $fields, $where_clause, $groupby, $having, $orderby, $locktype, &$results, &$numrows, $trace=false, $noerrorhandler=false) {
        if ($this->trace || $trace) { echo "Enter : ".__METHOD__."<br>"; }
        $query  = "SELECT ";
        $query .= (strlen($fields) === 0) ? "*" : $fields;
        $query .= " from `{$tablename}` ";
        $query .= (strlen($where_clause) === 0) ? "" : " WHERE {$where_clause}";
        $query .= strlen($groupby) ? " GROUP BY {$groupby}" : "";
        $query .= strlen($having)  ? " HAVING {$having}"   : "";
        $query .= strlen($orderby) ? " ORDER BY {$orderby}" : "";
        $query .= $locktype ? ($locktype == 1 ? " LOCK IN SHARE MODE" : " FOR UPDATE") : "";
        $success = $this->dbquery($query, $select_result, $numrows, $errormessage, 0, false, $matchedrows, $trace, $noerrorhandler);
        if ($success) {
            $this->buildresultsarray($select_result, $results, $trace);
        }
        if ($this->trace || $trace) { echo "Leave ".__METHOD__."<br>"; }
        return $success;
    }

    public function multiselect($tablename, $as, $joins, $fields, $where_clause, $groupby, $having, $orderby, $locktype, &$results, &$numrows) {
        if ($this->trace) { echo "Enter ".__METHOD__."<br>\n"; }
        $query  = "SELECT ";
        $query .= ((strlen($fields) === 0) ? "*" : $fields) . "\n";
        $query .= " FROM `{$tablename}` " . (!$as == "" ? "AS {$as}" : "") . "\n";
        foreach ($joins as $table) {
            $jointype = $table[3] ?? "INNER";
            $query .= " {$jointype} JOIN {$table[0]}" . (!$table[1] == '' ? " AS {$table[1]}" : '') . (!($table[2] == "") ? " ON {$table[2]}" : '') . "\n";
        }
        $query .= (!(strlen($where_clause) === 0) ? " WHERE {$where_clause}" : '') . "\n";
        $query .= strlen($groupby) ? " GROUP BY {$groupby}" : "";
        $query .= strlen($having)  ? " HAVING {$having}"   : "";
        $query .= strlen($orderby) ? " ORDER BY {$orderby}" : "";
        $query .= $locktype ? ($locktype == 1 ? " LOCK IN SHARE MODE" : " FOR UPDATE") : "";
        $success = $this->dbquery($query, $select_result, $numrows, $errormessage, 0);
        if ($success) {
            $this->buildresultsarray($select_result, $results);
        }
        if ($this->trace) { echo "Leave ".__METHOD__."<br>\n"; }
        return $success;
    }

    public function get_insert_id() {
        if ($this->trace) { echo "Enter ".__METHOD__."<br>"; }
        return (int)$this->pdo->lastInsertId();
    }

    public function freeresults($results) {
        // PDO manages cleanup automatically
    }

    // Compatibility wrapper: strips PDO::quote's surrounding quotes to match mysqli behaviour.
    // Prefer execute_params() for new code.
    public function real_escape_string(string $string): string {
        if ($this->trace) { echo "Enter ".__METHOD__."<br>"; }
        $quoted = $this->pdo->quote($string);
        return substr($quoted, 1, -1);
    }

    public function executefunction($function, $parameters, &$results, &$numrows=0, $trace=false, $log=0, $logquery=0, $logresult=0) {
        if ($this->trace || $trace) { echo "Enter : ".__METHOD__.":".$function."<br>\n\n"; }
        $placeholders = implode(', ', array_fill(0, count($parameters), '?'));
        $sql = "SELECT {$function}({$placeholders})";
        if ($logquery) { $this->errorhandler->dblog($sql); }
        $success = $this->execute_params($sql, array_values($parameters), $query_result, $numrows, $errormessage, 0, $trace);
        if ($success) {
            $this->processresults($query_result, $results, $logresult);
        } else {
            if ($logresult) { $this->errorhandler->dblog("{$function} FAILED"); }
            $results[] = [];
        }
        $results = array_values($results[0] ?? []);
        if ($this->trace || $trace) { echo "Leave : ".__METHOD__.":".$function."({$numrows} rows returned)<br>\n\n"; }
        return $success;
    }

    public function executeprocedure($procedure, $parameters, &$resultset, &$outparams, &$numrows=0, $trace=false, $logquery=0, $logresult=0) {
        if ($this->trace || $trace) { echo "Enter ".__METHOD__.":".$procedure."<br>\n"; }
        $success     = true;
        $call_params = [];
        foreach ($parameters as $key => $param) {
            if (mb_substr($param, 0, 3) === "OUT") {
                $varname = mb_substr($param, 3);
                $this->execute_params("SET @{$varname} = ''", [], $r, $n, $em, 2);
                $call_params[] = "@{$varname}";
            } elseif (mb_substr($param, 0, 5) === "INOUT") {
                $val = mb_substr($param, 5);
                $this->execute_params("SET @io{$key} = ?", [$val], $r, $n, $em, 2);
                $call_params[] = "@io{$key}";
            } else {
                $call_params[] = $this->pdo->quote($param);
            }
        }
        if ($success) {
            $callsql = "CALL {$procedure}(" . implode(', ', $call_params) . ")";
            if ($this->trace || $trace) { echo $callsql."<br>\n"; }
            if ($logquery) { $this->errorhandler->dblog($callsql); }
            try {
                $stmt = $this->pdo->query($callsql);
                $resultset = $stmt->fetchAll();
                while ($stmt->nextRowset()) { /* consume extra result sets */ }
                if ($logresult && isset($resultset[0])) {
                    $this->errorhandler->dblog("{$procedure} RESULT>>\n " . print_r($resultset[0], 1));
                }
            } catch (\PDOException $e) {
                $errormessage = "Query Failed: " . $e->getMessage();
                if ($this->errorhandler) { $this->errorhandler->sqlerror(null, $errormessage, $callsql); }
                $success = false;
            }
            if ($success) {
                $out_selects = [];
                foreach ($parameters as $key => $param) {
                    if (mb_substr($param, 0, 3) === "OUT") {
                        $varname = mb_substr($param, 3);
                        $out_selects[] = "@{$varname} as {$varname}";
                    } elseif (mb_substr($param, 0, 5) === "INOUT") {
                        $out_selects[] = "@io{$key} as io{$key}";
                    }
                }
                if ($out_selects) {
                    $this->execute_params("SELECT " . implode(', ', $out_selects), [], $outparams, $n, $em, 0);
                } else {
                    $outparams = [];
                }
            }
        }
        if ($this->trace || $trace) { echo "Leave ".__METHOD__.":".$procedure."<br>\n"; }
        return $success;
    }

    public function printHTMLresults($result, $tableclass, $colnames, $rownumbers) {
        $nl = "\n";
        echo '<table class="', $tableclass, '">', $nl;
        $rn    = $rownumbers ?? false;
        $first = true;
        foreach ($result as $line) {
            if ($first && $colnames) {
                echo "\t<tr>{$nl}";
                if ($rn) echo "\t\t<th>#</th>{$nl}";
                foreach (array_keys($line) as $col) echo "\t\t<th>{$col}</th>{$nl}";
                echo "\t</tr>{$nl}";
                $first = false;
            }
            echo "\t<tr>{$nl}";
            if ($rn) echo "\t\t<th></th>{$nl}";
            foreach ($line as $v) echo "\t\t<td>{$v}</td>{$nl}";
            echo "\t</tr>{$nl}";
        }
        echo "</table>{$nl}";
    }
}
