<?php
if (!defined("THINGS2DO")){die("Unauthorized access");}

class KeyStore {
    private $keys;
    private $ids;
    function __construct() {
        $this->keys=Array();
    }
    public function setkey($name, $key) {
        if (isset($this->keys[$name])) {
            throw new Exception('Key $name already exists');
        }
        $this->keys[$name]=$key;
    }
    public function getkey($name) {
        if (isset($this->keys[$name])) {
            return $this->keys[$name];
        }
        return null;
    }

    public function setid($name, $id) {
        if (isset($this->ids[$name])) {
            throw new Exception('ID $name already exists');
        }
        $this->ids[$name]=$id;
    }
    public function getid($name) {
        if (isset($this->ids[$name])) {
            return $this->ids[$name];
        }
        return null;
    }
}

$key=new KeyStore();
function getkey($name) {
    global $key;
    return $key->getkey($name);
}
function getid($name) {
    global $key;
    return $key->getid($name);
}
?>