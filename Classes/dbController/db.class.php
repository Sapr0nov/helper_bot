<?php

Class DB {
    public $MYSQLI;
    function __construct($server='localhost', $user='', $pswd='', $db=''){
        $this->MYSQLI = new mysqli($server, $user, $pswd, $db);
        if ($this->MYSQLI->connect_errno) {
            throw new Exception($this->MYSQLI->connect_error, 1);
        }
    }

}
?>