<?php
Class Notice { 
    private $MYSQLI;
    private $TABLE = 'notices';
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

    function add($user_id, $str) {
        $title=''; 
        $content=$str; 
        $tags=[];
        $query = "INSERT INTO " . $this->TABLE . " (user_id, title, content, tags) VALUES (" . $user_id . ", '" . $title . "', '" . $content . "', '" . json_encode($tags) . "');";
        try {
            $this->MYSQLI->query($query);
        } catch (Exception $e) {
            return false;
        }
        return true;
    }


    function search($user_id, $str) {
         $search_str = $this->MYSQLI->real_escape_string($str);
        // $query = sprintf("SELECT * FROM `%1$s` WHERE `user_id` = '%2$d' AND ( CONVERT(`title` USING utf8) LIKE '%%%3$s%%' OR CONVERT(`content` USING utf8) LIKE '%%%3$s%%' OR CONVERT(`tags` USING utf8) LIKE '%%%3$s%%'');",
        //  $this->TABLE, $user_id, $search_str);
        $query = "SELECT `id` as note_id, title, content, data_create_at as date, tags FROM `" . $this->TABLE . "`"
        . " WHERE `user_id` = '" . $user_id . "' AND ( CONVERT(`title` USING utf8) LIKE '%" . $search_str . "%' OR CONVERT(`content` USING utf8) LIKE '%" . $search_str . "%' OR CONVERT(`tags` USING utf8) LIKE '%" . $search_str . "%') ";
        try {
            $result = $this->MYSQLI->query($query);
        }catch(Exception $e) {
            $result = false;
        }
        if (!$result) {
            return null;
        }
        $array = $result->fetch_all(MYSQLI_ASSOC);
        $result->close();
        return $array;     
    }

}

?>