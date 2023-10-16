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
     * data_create_at datatime
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
        $query = "CREATE TABLE IF NOT EXISTS " . $this->TABLE . " (
            id bigint NOT NULL AUTO_INCREMENT PRIMARY KEY,
            tid bigint NOT NULL UNIQUE,
            username varchar(255) NULL DEFAULT '',
            first_name varchar(255) NULL DEFAULT '',
            last_name varchar(255) NULL DEFAULT '',
            data_create_at datetime NULL DEFAULT CURRENT_TIMESTAMP,
            status int NULL DEFAULT 0
        );";
        $result = $this->MYSQLI->query($query);
        if (!$result) {
            return "ошибка создания таблицы " . $this->TABLE . "\r\n";
        }

        $query = "CREATE TABLE IF NOT EXISTS " . $this->TABLE_STATUS . " (
            id int NOT NULL AUTO_INCREMENT PRIMARY KEY,
            status varchar(255) NULL DEFAULT ''
        );";
        
        $result = $this->MYSQLI->query($query);
        if (!$result) {
            return "ошибка создания таблицы " . $this->TABLE_STATUS . "\r\n";
        }
        $query = "INSERT INTO " . $this->TABLE_STATUS . " (`id`,`status`) VALUES(0,'гость')";
        $this->MYSQLI->query($query);

        return "Таблицы " . $this->TABLE .  " и " . $this->TABLE_STATUS . " созданы.\r\n";
    }

    function add($tid, $username='', $first_name='', $last_name='', $status=0 ) {

    }
}

?>