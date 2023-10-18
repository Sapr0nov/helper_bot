<?php
Class User {
    private $MYSQLI;
    private $TABLE = 'users';
    /**
     * id       bigint
     * tid      bigint  // id in Telegram
     * username     varchar 255
     * first_name   varchar 255
     * last_name    varchar 255
     * date_create_at datatime
     */
    private $TABLE_MSGS = 'messages';
    /**
     * id       bigint
     * msg_id      bigint  // id in Telegram
     * user_id     bigint
     * chat_id     bigint
     * text   text
     * date_create_at datatime
     * status     int
     */
    private $TABLE_STATUS = 'user_statuses';
    /**
     * id       int
     * status   varchar 255
     */
    function __construct($mysqli) {
        $this->MYSQLI = $mysqli;
    }

    function init() {
        $response = "";
        $query = "CREATE TABLE IF NOT EXISTS " . $this->TABLE . " (
            id bigint NOT NULL AUTO_INCREMENT PRIMARY KEY,
            tid bigint NOT NULL UNIQUE,
            username varchar(255) NULL DEFAULT '',
            first_name varchar(255) NULL DEFAULT '',
            last_name varchar(255) NULL DEFAULT '',
            date_create_at datetime NULL DEFAULT CURRENT_TIMESTAMP,
            status int NULL DEFAULT 0
        );";

        try {
            $this->MYSQLI->query($query);
            $response .= "Таблиц " . $this->TABLE . " создана.\r\n";
        } catch (Exception $e) {
            $response .= "ошибка создания таблицы " . $this->TABLE . "\r\n";
        }

        $query = "CREATE TABLE IF NOT EXISTS " . $this->TABLE_MSGS . " (
            id bigint NOT NULL AUTO_INCREMENT PRIMARY KEY,
            msg_id bigint NOT NULL UNIQUE,
            user_id bigint NULL,
            chat_id bigint NULL,
            text text NULL DEFAULT '',
            date_create_at datetime NULL DEFAULT CURRENT_TIMESTAMP
        );";

        try {
            $this->MYSQLI->query($query);
            $response .= "Таблиц " . $this->TABLE_MSGS . " создана.\r\n";
        } catch (Exception $e) {
            $response .= "Ошибка создания таблицы " . $this->TABLE_MSGS . "\r\n";
        }

        $query = "CREATE TABLE IF NOT EXISTS " . $this->TABLE_STATUS . " (
            id int NOT NULL AUTO_INCREMENT PRIMARY KEY,
            status varchar(255) NULL DEFAULT ''
        );";
        
        try {
            $this->MYSQLI->query($query);
            $response .= "Таблиц " . $this->TABLE_STATUS . " создана.\r\n";
        } catch (Exception $e) {
            $response .= "Ошибка создания таблицы " . $this->TABLE_STATUS . "\r\n";
        }
        
        $query = "INSERT INTO " . $this->TABLE_STATUS . " (`id`,`status`) VALUES(0,'гость')";
        $this->MYSQLI->query($query);

        return $response;
    }

    function add($tid, $username='', $first_name='', $last_name='', $status=0 ) {

    }
}

?>