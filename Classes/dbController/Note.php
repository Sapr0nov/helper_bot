<?php
Class Note {
    private $MYSQLI;
    private $TABLE = 'notes';
    /**
     * id       bigint
     * user_id  bigint
     * title    string 255 
     * content  text
     * data_create_at datatime
     * tags     string 255 (example important, today, todo)
     */
    function __construct($mysqli) {
        $this->MYSQLI = $mysqli;
    }

    function init() {
        $response = "";
        $query = "CREATE TABLE IF NOT EXISTS " . $this->TABLE . " (
            id bigint NOT NULL AUTO_INCREMENT PRIMARY KEY,
            user_id bigint NOT NULL,
            title varchar(255) NULL DEFAULT '',
            content text NULL,
            data_create_at datetime NULL DEFAULT CURRENT_TIMESTAMP,
            tags varchar(255) NULL DEFAULT ''
        );";
        try {
            $this->MYSQLI->query($query);
            $response .= "Таблиц " . $this->TABLE . " создана.\r\n";
        } catch (Exception $e) {
            $response .= "Ошибка создания таблицы " . $this->TABLE . "\r\n";
        }

        return $response;
    }

    function add($user_id, $title='', $content='', $tags=[] ) {

    }
}

?>