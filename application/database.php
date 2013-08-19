<?php
if (!defined("THINGS2DO")){die("Unauthorized access");}

// TODO: Allow for stackable query's

$db_conf=Array();
include_once "$root/config/database.php";

class MYSQL_DB {
    private $db;
    private $query;
    private $executable;
    private $where;
    public $unclaimed;
    function __construct($host, $username, $password, $dbname) {
        $this->db=mysqli_connect($host,$username,$password,$dbname);
        $this->flush_query();
    }
    public function select($rows, $table) {
        $this->flush_query();
        if (gettype($rows)=="array") {
            $rows=implode(",", $rows);
        }
        $this->add_query(Array("SELECT", $rows, "FROM", $table));
        $this->executable=true;
        return $this;
    }
    private function gen_key_val($data) {
        foreach ($data as $key=>$value) {
            $value=mysqli_real_escape_string($this->db, $value);
            $fdata[]="$key = $value,";
        }
        $fdata[count($fdata)-1]=rtrim($fdata[count($fdata)-1], ",");
        return $fdata;
    }
    public function update($table, $data) {
        $this->flush_query();
        $this->add_query(array_merge(Array("UPDATE", $table, "SET"), $this->gen_key_val($data)));
        return $this;
    }
    public function insert($table, $data) {
        $this->flush_query();
        $keys="(";
        foreach (array_keys($data) as $key) {
            $keys.="`$key`, ";
        }
        $keys=rtrim($keys, ", ").")";
        $values="(";
        foreach (array_values($data) as $val) {
            $values.="'".mysqli_real_escape_string($this->db, $val)."', ";
        }
        $values=rtrim($values, ", ").")";
        $this->add_query(Array("INSERT", "INTO",  "`$table`", $keys, "VALUES", $values));
        $this->executable=true;
        return $this;
    }
    public function where($key, $op, $val) {
        $val="'".mysqli_real_escape_string($this->db, $val)."'";
        if ($this->where) {
            $this->add_query(Array("AND", $key, $op, $val));
        }
        else {
            $this->add_query(Array("WHERE", $key, $op, $val));
            $this->where=true;
        }
        return $this;
    }
    private function flush_query() {
        if ($this->executable) {
            $this->unclaimed[]=$this->_();
        }
        $this->executable=false;
        $this->where=false;
        $this->query="";
    }
    private function add_query($arrdata) {
        $this->query.=implode(" ", $arrdata)." ";
    }
    public function _() {
        $o=Array();
        if (!$this->executable) {
            return false;
        }
        $result=mysqli_query($this->db, $this->query);
        if ($result != false) {
            if ($result === true) {
                return true;
            }
            else {
                //TODO: Use mysqli_fetch_field_direct for more details
                while($row = mysqli_fetch_object($result)) {
                    $o[]=get_object_vars($row);
                }
            }
        }
        else {
            throw new Exception(mysqli_error($this->db));
        }
        $this->executable=false;
        $this->flush_query();
        return $o;
    }
    function __destruct() {
        mysqli_close($this->db);
    }
}
?>