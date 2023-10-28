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

clearMessages($tgBot, $users);
checkNotice(1);

function clearMessages($tgBot, $users) {
    $uids = $users->all_usersId();
    foreach ($uids as $uid) {
        if ($users->checkMsgs($uid['tid']) > 1) {
            $users->msgs_clear($tgBot, $users, $uid['tid']);
        }
    }
    return;
}

function checkNotice($user_id) {
    echo"TODO провекра и отправка напоминаний сообщения пользователяы";
}
?>