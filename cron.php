<?php
/**
 * Ubuntu add sudo nano /etc/crontab 
 * OR
 * crontab -e
 * THEN 
 */
 # add string 
 # */1 * * * * cd /home/hestia/web/stacksite.ru/public_html/assets/projects3/tg_helper/ && /usr/bin/php -q cron.php
 
$SITE_DIR = dirname(__FILE__) . '/';
require_once($SITE_DIR . 'env.php');
require_once($SITE_DIR . 'Classes/i18n.php');
require_once($SITE_DIR . 'Classes/tg_Bot/tg.class.php');
require_once($SITE_DIR . 'Classes/dbController/db.class.php');
require_once($SITE_DIR . 'Classes/dbController/Notice.php');
require_once($SITE_DIR . 'Classes/dbController/User.php');

header('Content-Type: text/html; charset=utf-8'); // Выставляем кодировку UTF-8
date_default_timezone_set('Europe/Moscow');

$tgBot = new TgBotClass($BOT_TOKEN);
$db = new DB($DB_SERVER, $DB_USER, $DB_PASSWORD, $DB_NAME);

// Start onece when installing bot (create tables and register webhook)
$notices = new Notice($db->MYSQLI); 
$users = new User($db->MYSQLI);

// work with date
// получаем минуты прошедшие с 1700
$timeNow = time();
$past_min = round(time()/60);
// Если интервал попадает в указанный в ини файле запускаем очистку
if ($past_min % $CLEAR_INTERVAL == 0) {
    clearMessages($tgBot, $users);
}


checkNotice($db, $tgBot, $users);

function clearMessages($tgBot, $users) {
    $uids = $users->all_usersId();
    foreach ($uids as $uid) {
        if ($users->checkMsgs($uid['tid']) > 1) {
            $users->msgs_clear($tgBot, $uid['tid']);
        }
    }
    return;
}

function checkNotice($db, $tgBot, $users) {
    $endCheck = date('Y-m-d H:i:59', time());
    $startCheck = date('Y-m-d H:i:59', time() - 3600);

    $query = "SELECT `notices`.`id` as `id`, `users`.`tid` as `tid`, `notices`.`content` as `content` FROM `notices` LEFT JOIN `users` ON `users`.`id` = `notices`.`user_id` WHERE `notices`.`status` = 'active' AND `notices`.`date_remind` > '".$startCheck."' AND `notices`.`date_remind` < '".$endCheck."';";
    try {
        $result = $db->MYSQLI->query($query);
    } catch (Exception $e) {
        return false;
    }
    $rows = $result->fetch_all(MYSQLI_ASSOC);
    $result->close();
    foreach ($rows as $row) {
        $reply = $tgBot->msg_to_tg($row['tid'], $row['content']);
        save_reply($users, $reply);

        $query = "UPDATE `notices` SET `status` = 'finished' WHERE `id` = '" . $row['id'] . "';";
        $db->MYSQLI->query($query);
    }
    return true;
}

function save_reply($users, $reply) {
    $replyTgBot = new TgBotClass('');
    $replyTgBot->get_data($reply);
    $users->msg_save($replyTgBot->MSG_INFO);
}
?>