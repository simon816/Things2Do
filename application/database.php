<?php
if (!defined("THINGS2DO")){die("Unauthorized access");}

// TODO: Allow for stackable query's


class MYSQL_DB {
    private $db;
    private $query;
    private $executable;
    private $chainarr;
    public $unclaimed;
    function __construct() {
        $db_conf=Array();
        global $root;
        include_once "$root/config/database.php";
        $this->db=mysqli_connect($db_conf['host'],$db_conf['user'],$db_conf['pass'],$db_conf['database']);
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
            if (gettype($val)=="NULL") {
                $values.="NULL";
            }
            elseif (gettype($val)=="integer") {
                $values.=(string)$val;
            }
            elseif (gettype($val)=="boolean") {
                $values.=$val?"TRUE":"FALSE";
            }
            else {
                $values.="'".mysqli_real_escape_string($this->db, $val)."'";
            }
            $values.=", ";
        }
        $values=rtrim($values, ", ").")";
        $this->add_query(Array("INSERT", "INTO",  "`$table`", $keys, "VALUES", $values));
        $this->executable=true;
        return $this;
    }
    public function where($key, $op, $val) {
        if ($val) {
        $val="'".mysqli_real_escape_string($this->db, $val)."'";
        }
        $this->add_to_chain("where", Array($key, $op, $val));
        return $this;
    }
    public function order_by($colname, $order) {
        $this->add_to_chain("order by", Array($colname, $order));
        return $this;
    }
    public function limit($from, $to=null) {
        $this->add_to_chain("limit", array($from.($to!=null?", ".(int)$to:"")));
        return $this;
    }
    private function flush_query() {
        if ($this->executable) {
            $this->unclaimed[]=$this->_();
        }
        $this->executable=false;
        $this->where=false;
        $this->query="";
        $this->chainarr= Array(
            "WHERE"=>Array("AND", Array()),
            "ORDER BY"=>Array(", ", Array()),
            "LIMIT"=>Array(", ",Array())
        );
    }
    private function add_query($arrdata) {
        $this->query.=implode(" ", $arrdata)." ";
    }
    private function add_to_chain($chname, $data) {
        $cap=strtoupper($chname);
        if(!isset($this->chainarr[$cap])) {
            throw new Exception("Unknown chain name '$chname'");
        }
        $this->chainarr[$cap][1][]=$data;
    }
    public function _() {
        $o=Array();
        if (!$this->executable) {
            return false;
        }
        foreach($this->chainarr as $chname=>$chinfo) {
            $len=count($chinfo[1]);
            if ($len>0) {
                for($i=0;$i<$len;$i++) {
                    $this->add_query(array_merge(Array($i==0?$chname:$chinfo[0]), $chinfo[1][$i]));
                }
            }
        }
        $result=mysqli_query($this->db, $this->query);
        $this->executable=false;
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
        $this->flush_query();
        return $o;
    }
    function __destruct() {
        mysqli_close($this->db);
    }
}
?>