<?php
Class Note {
    private $MYSQLI;
    private $TABLE;
    /**
     * id       int
     * user_id  int
     * title    string 
     * content  text
     * data_create_at datatime
     * tags     string (example important, today, todo)
     */
    function __construct($mysqli, $table) {
        $this->MYSQLI = $mysqli;
        $this->TABLE = $table;
        $query = "CREATE TABLE IF NOT EXISTS " . $this->TABLE . " (
            id bigint NOT NULL AUTO_INCREMENT PRIMARY KEY,
            user_id bigint NOT NULL,
            title varchar(255) NULL DEFAULT '',
            content text NULL,
            data_create_at datetime NULL DEFAULT CURRENT_TIMESTAMP,
            tags varchar(255) NULL DEFAULT ''
        );";
        $this->MYSQLI->query($query);
    }

    function add($user_id, $title='', $content='', $tags=[] ) {

    }
}

?>