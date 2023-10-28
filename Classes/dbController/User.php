<?php
Class User {
    private $MYSQLI;
    /**
     * id       bigint
     * tid      bigint  // id in Telegram
     * username     varchar 255
     * first_name   varchar 255
     * last_name    varchar 255
     * date_create_at datatime
     */
    private $TABLE = 'users';
    /**
     * id           bigint
     * msg_id       bigint  // id in Telegram
     * user_id      bigint
     * chat_id      bigint
     * text         text
     * date_create_at datatime
     */
    private $TABLE_MSGS = 'messages';
    /**
     * id       int
     * status   varchar 255
     */
    private $TABLE_STATUS = 'user_statuses';
    
    function __construct($mysqli) {
        $this->MYSQLI = $mysqli;
    }

    /**
     * create all tables
     */
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

        $query = "DROP TABLE IF EXISTS " . $this->TABLE_STATUS . ";";
        $this->MYSQLI->query($query);

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
        
        $query = "INSERT INTO " . $this->TABLE_STATUS . " (`id`,`status`) VALUES(0,'main_menu')";
        $this->MYSQLI->query($query);

        return $response;
    }

    /**
     *  return int user_id || null
     */
    function add($tid, $username='', $first_name='', $last_name='', $status = 0 ) {
        if ($this->checkUser($tid)) {
            return $this->checkUser($tid);
        }

        $query = "INSERT INTO `" . $this->TABLE 
        . "` (`tid`,`first_name`,`last_name`,`username`)" 
        . "VALUES(" . $tid . ", '" . $first_name . "','" . $last_name . "','" . $username . "' );";
        try {
            $this->MYSQLI->query($query);
            $result = $this->MYSQLI->insert_id;
        }catch(Exception $e) {
            return null;
        }
        if (!$result) {
            return null;
        }
        return $result;
    }


    /**
     *  return int user_id || null
     */
    function checkUser($tid) {
        $response = null;
        $query = "SELECT `id` FROM `" . $this->TABLE 
        . "` WHERE `tid` = " . $tid;
        try {
            $result = $this->MYSQLI->query($query);
        }catch(Exception $e) {
            $result = false;
        }
        if (!$result) {
            return null;
        }
        $obj = $result->fetch_object();
        $response = $obj->id;
        $result->close();
        unset($obj);

        return $response;
     }
     
    /**
     *  return object {"code":int,"value":string} || null
     */
    function getStatus($uid) {
        $query = "SELECT `" . $this->TABLE . "`.`status` as 'code', `" . $this->TABLE_STATUS . "`.`status` as 'value' FROM `" . $this->TABLE 
        . "` INNER JOIN `" . $this->TABLE_STATUS 
        . "` ON . `" . $this->TABLE . "`.`status` = `" . $this->TABLE_STATUS . "`.`id` "
        . " WHERE `" . $this->TABLE . "`.`id` = " . $uid;
        try {
            $result = $this->MYSQLI->query($query);
        }catch(Exception $e) {
            return null;
        }
        $response = $result->fetch_object();

        return $response;
    }


    /**
    *  status - int or text
    *  return object {"code":int,"value":string} || null
    */
    function setStatus($uid, $status) {

        if (is_int($status)) {
            $statusObject = $this->checkStatus($status);
        }elseif(is_string($status)) {
            $statusObject = $this->checkStatus(null, $status);
        }else {
            return null;
        }
        if (is_null($statusObject)) {
            return null;
        }

        $query = "UPDATE `" . $this->TABLE . "` SET `status` = " . $statusObject->code . " WHERE `id` = '" . $uid . "'";
        try {
            $this->MYSQLI->query($query);
        }catch(Exception $e) {
            return null;
        }

        return $statusObject;
    }


    private function checkStatus($sid = null, $status = null) {
        $query = "SELECT `id` as 'code', `status` as 'value' FROM `" . $this->TABLE_STATUS . "`"
        . " WHERE ";

        if (!is_null($sid)) {
            $query .= "`id` = " . $sid;
        }

        if (!is_null($status)) {
            if (!is_null($sid)) {
                $query .= " AND `status` = '" .$status . "'";
            }
            $query .= "`status` = '" .$status . "'";
        }

        try {
            $result = $this->MYSQLI->query($query);
        }catch(Exception $e) {
            return null;
        }
        $response = $result->fetch_object();

        return $response;

    }


    /**
     *  return int id message || null
     */
    function msg_save($MSG_INFO) {

        $query = "INSERT INTO `" . $this->TABLE_MSGS 
        . "` (`msg_id`,`user_id`,`chat_id`,`text`)" 
        . "VALUES(" . $MSG_INFO['message_id'] . ", '" . $MSG_INFO['user_id'] . "','" . $MSG_INFO['chat_id'] . "','" . $MSG_INFO['text'] . "' );";
        try {
            $this->MYSQLI->query($query);
            $result = $this->MYSQLI->insert_id;
        }catch(Exception $e) {
            return null;
        }
        if (!$result) {
            return null;
        }
        return $result;
    }
/**
     *  return boolean
     */
    function msgs_clear($tgBot, $users, $uid) {
        $query = "SELECT `msg_id` FROM `" . $this->TABLE_MSGS . "` WHERE `chat_id` = '" . $uid . "'";        
        try {
            $result = $this->MYSQLI->query($query);
        }catch(Exception $e) {
            return false;
        }
        if (!$result) {
            return false;
        }
        $rows = $result->fetch_all(MYSQLI_ASSOC);
        $rows = array_reverse($rows);
        foreach ($rows as $row) {
            //удаляем сообщения из чата
            $tgBot->delete_msg_tg($uid, $row['msg_id']);
        }
        $reply = $tgBot->msg_to_tg($uid, "Жду приказаний \xF0\x9F\x98\x8A", silent: true);

        // удаляем сообщаения из таблицы
        $query = "DELETE FROM `" . $this->TABLE_MSGS . "` WHERE `chat_id` = '" . $uid . "'";
        $this->MYSQLI->query($query); 

        // сохраняем свое приветственное сообщение
        $this->save_reply($users, $reply);

        return true;
    }

    /**
     * return array ids of users
     */
    function all_usersId() {
        $query = "SELECT `tid` FROM `" . $this->TABLE . "` WHERE '1' = '1'";        
        try {
            $result = $this->MYSQLI->query($query);
        }catch(Exception $e) {
            return false;
        }
        if (!$result) {
            return false;
        }
        $rows = $result->fetch_all(MYSQLI_ASSOC);
        return $rows;
    }

    /**
     * return int - amount messages of user
     */
    function checkMsgs($tid) {
        $query = "SELECT COUNT(`id`) as messages FROM `" . $this->TABLE_MSGS . "` WHERE `user_id` = " . $tid . ";";        
        try {
            $result = $this->MYSQLI->query($query);
        }catch(Exception $e) {
            return false;
        }
        if (!$result) {
            return false;
        }
        $row = $result->fetch_object();
        return $row->messages;

    }

    function save_reply($users, $reply) {
        $replyTgBot = new TgBotClass('');
        $replyTgBot->get_data($reply);
        $users->msg_save($replyTgBot->MSG_INFO);
    }
}
?>