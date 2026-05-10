<?php
namespace fw\database\table;
use \lib\StdLib as lib;
abstract class MySQLTable extends Table
{
    private $trace = false;
    protected $dbquote = '`';
    protected $user_id;

    public function __construct() {
        $this->databaselocktimeoutsecs = 1800;
    }

    public function init($db, $user_id="null") {
        $this->user_id = $user_id;
        $this->db = $db;
        $classname = get_class($this);
        $this->tableclassname = lib::getLastToken($classname, "\\");
        $this->tablename = substr($this->tableclassname, 0, strlen($this->tableclassname) - 5);
        unset($this->fields);
        if ($this->trace) { echo 'Leave '.__METHOD__.": {$classname} >> {$this->tableclassname} >> {$this->tablename} <br>"; }
    }

    public function setuser($user_id) {
        $this->user_id = $user_id;
    }

    // Delegates real_escape_string to the DB layer for legacy callers.
    // Prefer execute_params() / query_params() for new code.
    public function real_escape_string($str) {
        return $this->db->real_escape_string($str);
    }

    // Runs a parameterised query. Use for SELECT statements returning rows in $results.
    public function query_params(string $sql, array $params, &$results, &$numrows=0, $trace=false): bool {
        $errormessage = '';
        $success = $this->db->execute_params($sql, $params, $result, $numrows, $errormessage, 0, $trace);
        if ($success) { $results = $result; }
        return $success;
    }

    // General parameterised execution (SELECT, INSERT, UPDATE, DELETE).
    // $querytype: 0 = SELECT, 1 = mutation, 2 = SET/other
    public function execute_params(string $sql, array $params, &$result, &$numrows=0, &$errormessage='', int $querytype=0, $trace=false): bool {
        return $this->db->execute_params($sql, $params, $result, $numrows, $errormessage, $querytype, $trace);
    }

    public function insert($recoverid=true, &$id=0, $trace=false, &$errormessage='') {
        if ($this->trace || $trace) { echo gtab(1)."Enter ".__METHOD__."(".$this->tablename.")<br>\n"; }
        try {
            $fnames       = '';
            $placeholders = '';
            $params       = [];
            $comma        = '';
            foreach ($this->fields as $var => $val) {
                $upper = strtoupper($var);
                if (in_array($upper, ['ID', 'LOCKTIME', 'LOCKEDBY'])) continue;
                if ($upper === 'CREATED') {
                    $fnames       .= $comma . $var;
                    $placeholders .= $comma . '?';
                    $params[]      = lib::nowf();
                    $comma         = ', ';
                } elseif (!is_null($val)) {
                    $fnames .= $comma . $var;
                    if (strtolower((string)$val) === 'null') {
                        $placeholders .= $comma . 'null';
                    } else {
                        $placeholders .= $comma . '?';
                        $params[] = $val;
                    }
                    $comma = ', ';
                }
            }
            $this->beforeinsert($fnames, $placeholders);
            $sql     = "INSERT INTO " . lib::capsToUnderscores($this->tablename) . " ({$fnames}) VALUES ({$placeholders})";
            $success = $this->db->execute_params($sql, $params, $this->result, $numrows, $errormessage, 1, $trace);
            if ($success && isset($this->fields['id']) && $recoverid) {
                $id = $this->result;
                $this->fields['id'] = $this->result;
            }
        } catch (\Exception $e) {
            die(__METHOD__ . " : " . $e->getMessage());
        }
        if ($this->trace || $trace) { echo gtab(-1)."Leave ".$this->tablename.":".__METHOD__." success={$success} err={$errormessage}<br>\n"; }
        return $success;
    }

    protected function getvalidlockstart($now) {
        $interval = 'PT' . $this->databaselocktimeoutsecs . 'S';
        $firstvalidlockstart = $now->sub(new \DateInterval($interval));
        return $firstvalidlockstart;
    }

    public function update($set_clause, $whereclause, &$numrows, &$errormessage, $trace=false, &$matchedrows=0, $keeplock=false) {
        if ($this->trace || false) { echo gtab(1)."Enter ".$this->tablename.":".__METHOD__." SET ".$set_clause." WHERE ".$whereclause."<br>\n"; }
        if ($this->ismyfield('lockedby') && (strpos($set_clause, 'lockedby') === false)) {
            if (!$keeplock) {
                $set_clause .= (empty($set_clause) ? '' : ', ') . $this->dbquote . "lockedby" . $this->dbquote . " = '', " . $this->dbquote . "locktime" . $this->dbquote . " = ''";
            } else {
                $set_clause .= (empty($set_clause) ? '' : ', ') . $this->dbquote . "lockedby" . $this->dbquote . "='" . session_id() . "', " . $this->dbquote . "locktime" . $this->dbquote . " = '" . lib::nowf() . "'";
            }
        }
        $this->beforeupdate($set_clause);
        $query   = "UPDATE " . lib::capsToUnderscores($this->tablename) . " SET " . $set_clause;
        $query  .= empty($whereclause) ? "" : (" WHERE " . $whereclause);
        $success = $this->db->dbquery($query, $this->result, $numrows, $errormessage, 1, false, $matchedrows, $trace);
        if ($this->trace || $trace) { echo gtab(-1).$this->tablename.":".__METHOD__." >> {$numrows}/{$matchedrows} rows<br><br>\n"; }
        return $success;
    }

    public function updateallfields(&$numrows, &$errormessage, $keeplock=false, $trace=false) {
        if ($this->trace || $trace) { echo gtab(1)."Enter ".$this->tablename.":".__METHOD__."<br>\n"; }
        global $now;
        $success = false;
        if (!empty($this->fields['id'])) {
            $set_parts   = [];
            $set_params  = [];
            $whereclause = '';
            $where_params = [];

            foreach ($this->fields as $field => $val) {
                $upper = strtoupper($field);
                if ($upper === 'CREATED') continue;
                if ($upper === 'ID') {
                    if (isset($this->fields['lockedby'])) {
                        $curlockstart = $this->getvalidlockstart($now);
                        $whereclause  = "ID = ? AND (lockedby = '' OR lockedby = ? OR locktime < ?)";
                        $where_params = [$this->fields['id'], session_id(), datetimestring($curlockstart)];
                    } else {
                        $whereclause  = "ID = ?";
                        $where_params = [$this->fields['id']];
                    }
                } elseif ($upper === 'LOCKTIME') {
                    if ($keeplock) {
                        $set_parts[]  = $this->dbquote . $field . $this->dbquote . " = ?";
                        $set_params[] = lib::nowf();
                    } else {
                        $set_parts[] = $this->dbquote . $field . $this->dbquote . " = ''";
                    }
                } elseif ($upper === 'LOCKEDBY') {
                    if ($keeplock) {
                        $set_parts[]  = $this->dbquote . $field . $this->dbquote . " = ?";
                        $set_params[] = session_id();
                    } else {
                        $set_parts[] = $this->dbquote . $field . $this->dbquote . " = ''";
                    }
                } elseif (!is_null($val)) {
                    if (strtoupper((string)$val) === 'NULL') {
                        $set_parts[] = $this->dbquote . $field . $this->dbquote . " = null";
                    } else {
                        $set_parts[]  = $this->dbquote . $field . $this->dbquote . " = ?";
                        $set_params[] = $val;
                    }
                }
            }

            $dummy_set = '';
            $this->beforeupdate($dummy_set);
            $sql    = "UPDATE " . lib::capsToUnderscores($this->tablename)
                    . " SET " . implode(', ', $set_parts)
                    . " WHERE " . $whereclause;
            $params = array_merge($set_params, $where_params);
            $success = $this->db->execute_params($sql, $params, $this->result, $numrows, $errormessage, 1, $trace);

            if ($this->trace || $trace) { echo gtab(-1)."Leave ".$this->tablename.":".__METHOD__." success={$success} numrows={$numrows}<br>\n"; }
        } else {
            if ($this->trace || $trace) { echo gtab(-1)."Leave ".$this->tablename.":".__METHOD__."<br>\n"; }
        }
        // $numrows is matched rows (MYSQL_ATTR_FOUND_ROWS) — must equal 1 for success
        return ($success && ($numrows == 1));
    }

    public function select($fieldselection, $whereclause, $groupby, $having, $orderby, $locktype, &$results, &$numrows=0, $trace=false, $noerrorhandler=false) {
        if ($this->trace || $trace) { echo gtab(1)."Enter ".$this->tablename." : ".__METHOD__." select ".$fieldselection." where ".$whereclause."<br>\n"; }
        global $domainid;
        $success = $this->db->select(lib::capsToUnderscores($this->tablename), $fieldselection, $whereclause, $groupby, $having, $orderby, $locktype == '' ? 0 : $locktype, $results, $numrows, $trace, $noerrorhandler);
        if ($this->trace || $trace) { echo gtab(-1)."Leave ".$this->tablename.":".__METHOD__."({$numrows} rows found))<br>\n"; }
        return $success;
    }

    public function multiselect($as, $joins, $fieldselection, $whereclause, $groupby, $having, $orderby, $locktype, &$results, &$numrows=0, $trace=false) {
        global $domainid;
        if ($this->trace || $trace) { echo gtab(1)."Enter ".$this->tablename.":".__METHOD__."<br>\n"; }
        $success = $this->db->multiselect(lib::capsToUnderscores($this->tablename), $as, $joins, $fieldselection, $whereclause, $groupby, $having, $orderby, $locktype == '' ? 0 : $locktype, $results, $numrows, $trace);
        if ($this->trace || $trace) { echo gtab(-1)."Leave ".$this->tablename.":".__METHOD__."({$numrows} rows found))<br>\n"; }
        return $success;
    }

    public function countrecords($whereclause, &$numrows=0, $trace=false) {
        if ($this->trace || $trace) { echo gtab(1)."Enter ".$this->tablename.":".__METHOD__."<br>\n"; }
        return $this->select("id", $whereclause, "", "", "", 0, $results, $numrows, $trace);
    }

    public function query($query, &$results, &$numrows=0, $trace=false) {
        if ($this->trace || $trace) { echo gtab(1)."Enter ".$this->tablename.":".__METHOD__."<br>\n"; }
        $success = $this->db->dbquery($query, $query_result, $numrows, $errormessage, 1, 0, $matched, $trace, false);
        if ($success) {
            $this->db->processresults($query_result, $results, false, $trace);
        }
        if ($this->trace || $trace) { echo gtab(-1)."Leave ".$this->tablename.":".__METHOD__."({$numrows} rows affected OK? = {$success})<br>\n"; }
        return $success;
    }

    public function delete($whereclause, &$numrows=0, $trace=false, &$errormessage="") {
        if ($this->trace || $trace) { echo gtab(1)."Enter ".$this->tablename.":".__METHOD__."<br>\n"; }
        $query   = "DELETE FROM " . lib::capsToUnderscores($this->tablename);
        $query  .= (strlen($whereclause) === 0) ? "" : (" WHERE " . $whereclause);
        if ($this->trace || $trace) { echo gtab()."Query ".$query."<br>\n"; }
        $success = $this->db->dbquery($query, $this->result, $numrows, $errormessage, 1, $trace);
        if ($this->trace || $trace) { echo gtab(-1)."Leave ".$this->tablename.":".__METHOD__."({$numrows} rows affected. ".$errormessage.")<br>\n"; }
        return $success;
    }

    public function createfromfields($formfields, &$id, $recoverid=false, $trace=false) {
        if ($this->trace || $trace) { echo gtab(1)."Enter ".$this->tablename.":".__METHOD__."<br>\n"; }
        $this->clear();
        $this->loadfromfields($formfields);
        $success = $this->insert($recoverid, $id);
        if ($this->trace || $trace) { echo gtab(-1)."Leave ".$this->tablename.":".__METHOD__."<br>"; }
        return $success;
    }

    protected function processdbresultset($dbresults, &$records) {
        $records = [];
        foreach ($dbresults as $result) {
            $this->initfields($this->fields);
            foreach ($this->fields as $var => $val) {
                if (isset($result[$var])) {
                    $this->fields[$var] = $result[$var];
                }
            }
            $records[] = $this->fields;
        }
    }

    public function selectonID($id, &$record, &$numrows=0, $withlock=0, $trace=false) {
        if ($this->trace || $trace) { echo gtab(1)."Enter ".$this->tablename.":".__METHOD__."  id={$id}<br>\n"; }
        $this->initfields($this->fields);
        $lock_clause = $withlock ? ($withlock == 1 ? " LOCK IN SHARE MODE" : " FOR UPDATE") : "";
        $tablename   = lib::capsToUnderscores($this->tablename);
        $success     = $this->db->execute_params(
            "SELECT * FROM `{$tablename}` WHERE id = ?{$lock_clause}",
            [$id], $dbresults, $numrows, $errormessage, 0, $trace
        );
        if ($success) {
            $success = ($numrows == 1);
            if ($success) {
                $records = [];
                $this->processdbresultset($dbresults, $records);
                $record = $records[0];
            }
        }
        if ($this->trace || $trace) { echo gtab(-1)."Leave ".$this->tablename.":".__METHOD__." ({$numrows} rows found)<br>\n"; }
        return $success;
    }

    public function selectall(&$records, &$numrows=0, $orderby="", $trace=false, $noerrorhandler=false) {
        if ($this->trace || $trace) { echo gtab(1)."Enter ".$this->tablename.":".__METHOD__."<br>\n"; }
        $success = $this->select("*", '', '', '', $orderby, 0, $dbresults, $numrows, $trace, $noerrorhandler);
        if ($success) {
            $this->processdbresultset($dbresults, $records);
        }
        if ($this->trace || $trace) { echo gtab(-1)."Leave ".$this->tablename.":".__METHOD__." ({$numrows} rows found)<br>\n"; }
        return $success;
    }

    public function selectallbydomain($orderby, &$results, &$numrows=0, $alldomains=false, $trace=false) {
        if ($this->trace || $trace) { echo gtab(1)."Enter ".$this->tablename." :".__METHOD__."<br>\n"; }
        $success = $this->select("*", ($alldomains ? "" : ($this->ismyfield("domain_id") ? ("domain_id = " . $domainid) : "")), '', '', $orderby, 0, $results, $numrows, $trace);
        if ($this->trace || $trace) { echo gtab(-1)."Leave ".$this->tablename.":".__METHOD__." ({$numrows} rows found)<br>\n"; }
        return $success;
    }

    public function selectononefield($field, $value, &$records, &$numrows=0, $withlock=false, $trace=false, $order="") {
        if ($this->trace || $trace) { echo gtab(1)."Enter ".$this->tablename.":".__METHOD__."  {$field} = {$value}<br>\n"; }
        try {
            $this->initfields($this->fields);
            $lock_clause  = $withlock ? " FOR UPDATE" : "";
            $order_clause = $order ? " ORDER BY {$order}" : "";
            $tablename    = lib::capsToUnderscores($this->tablename);
            $success      = $this->db->execute_params(
                "SELECT * FROM `{$tablename}` WHERE `{$field}` = ?{$order_clause}{$lock_clause}",
                [$value], $dbresults, $numrows, $errormessage, 0, $trace
            );
            if ($success) {
                $this->processdbresultset($dbresults, $records);
            }
            if ($this->trace || $trace) { echo gtab(-1)."Leave ".$this->tablename.":".__METHOD__." ({$numrows} rows found)<br>\n"; }
            return $success;
        } catch (\Exception $e) {
            echo __METHOD__ . " exception " . $e->getMessage();
        }
    }

    public function selectonmultiplefields($fielddata, &$records, &$numrows=0, $withlock=false, $trace=false) {
        if ($this->trace || $trace) { echo gtab(1)."Enter ".$this->tablename.":".__METHOD__."<br>\n"; }
        $this->initfields($this->fields);
        $conditions = [];
        $params     = [];
        if (isset($fielddata)) {
            foreach ($fielddata as $field => $value) {
                $conditions[] = "`{$field}` = ?";
                $params[]     = $value;
            }
        }
        $tablename = lib::capsToUnderscores($this->tablename);
        $where     = $conditions ? implode(' AND ', $conditions) : '1';
        $success   = $this->db->execute_params(
            "SELECT * FROM `{$tablename}` WHERE {$where}",
            $params, $dbresults, $numrows, $errormessage, 0, $trace
        );
        if ($success) {
            if ($numrows == 1 && $withlock) {
                $success = $this->lock($id, session_id());
            }
            if ($success) {
                $this->processdbresultset($dbresults, $records);
            }
        }
        if ($this->trace || $trace) { echo gtab(-1)."Leave ".$this->tablename.":".__METHOD__." ({$numrows} rows found)<br>\n"; }
        return $success;
    }

    public function lock($id, $session_id) {
        if ($this->trace) { echo gtab(1)."Enter ".$this->tablename.":".__METHOD__."<br>\n"; }
        global $now;
        $setclause   = $this->dbquote . "LOCKEDBY" . $this->dbquote . " = '" . $session_id . "', " . $this->dbquote . "LOCKTIME" . $this->dbquote . " = '" . lib::nowf() . "'";
        $validlockstart = $this->getvalidlockstart($now);
        $whereclause = "ID = '{$id}' AND (LOCKEDBY = '' OR LOCKEDBY = '{$session_id}' OR LOCKTIME < '" . datetimestring($validlockstart) . "')";
        if ($this->trace) { echo gtab()."LOCK SET ".$setclause." WHERE ".$whereclause."<br>\n"; }
        $success = $this->update($setclause, $whereclause, $numrows, $errormessage);
        if ($this->trace) { echo gtab(-1)."Leave ".__METHOD__."<br>"; }
        return $success;
    }

    public function unlock($id, $session_id) {
        if ($this->trace) { echo gtab(1)."Enter ".$this->tablename.":".__METHOD__."<br>\n"; }
        $setclause   = $this->dbquote . "LOCKEDBY" . $this->dbquote . " = '', " . $this->dbquote . "LOCKTIME" . $this->dbquote . " = ''";
        $whereclause = "ID = '{$id}' AND LOCKEDBY = '{$session_id}'";
        if ($this->trace) { echo gtab()."LOCK SET ".$setclause." WHERE ".$whereclause."<br>\n"; }
        $success = $this->update($setclause, $whereclause, $numrows, false, $matchedrows);
        if ($this->trace) { echo gtab(-1)."Leave ".__METHOD__."<br>"; }
        return $success;
    }

    public function put($fieldname, $data, $save=false, $trace=false) {
        if ($this->trace || $trace) { echo gtab(1)."Enter ".__METHOD__.":".$this->tablename." fieldname={$fieldname} data={$data} id=".$this->fields["id"]."<br>\n"; }
        $this->fields[$fieldname] = $data;
        if ($save) {
            $setclause   = $this->dbquote . $fieldname . $this->dbquote . " = " . (($data === "null" || is_null($data)) ? "null" : ("'" . $this->db->real_escape_string($data) . "'"));
            $whereclause = " id = '" . $this->db->real_escape_string($this->fields["id"]) . "'";
            $success     = $this->update($setclause, $whereclause, $numrows, $errormessage);
        } else {
            $success = true;
        }
        if ($this->trace || $trace) { echo gtab(-1)."Leave ".$this->tablename.":".__METHOD__." success={$success}<br>\n"; }
        return $success;
    }

    public function putfields(&$id, $fieldnames, $data, $save, &$numrows, &$errormessage, $trace=false) {
        if ($this->trace || $trace) { echo gtab(1)."Enter ".$this->tablename.":".__METHOD__."<br>\n"; }
        $this->clear();
        foreach ($fieldnames as $key => $fieldname) {
            $this->fields[$fieldname] = $data[$key];
        }
        if ($save) {
            if ($id == 0) {
                $success = $this->insert(true, $id, $trace, $em);
            } else {
                $set_parts = [];
                $params    = [];
                foreach ($fieldnames as $key => $fieldname) {
                    $val = $data[$key];
                    if ($val === "null" || is_null($val)) {
                        $set_parts[] = $this->dbquote . $fieldname . $this->dbquote . " = null";
                    } else {
                        $set_parts[] = $this->dbquote . $fieldname . $this->dbquote . " = ?";
                        $params[]    = $val;
                    }
                }
                $params[]  = $id;
                $tablename = lib::capsToUnderscores($this->tablename);
                $success   = $this->db->execute_params(
                    "UPDATE `{$tablename}` SET " . implode(', ', $set_parts) . " WHERE id = ?",
                    $params, $result, $numrows, $em, 1, $trace
                );
            }
            $errormessage .= $em ?? '';
        } else {
            $success = true;
        }
        if ($this->trace || $trace) { echo gtab(-1)."Leave ".$this->tablename.":".__METHOD__." id={$id} success={$success}<br>\n"; }
        return $success;
    }

    protected function beforeinsert(&$fnames, &$fvalues) {}
    protected function beforeupdate(&$set) {}
}
