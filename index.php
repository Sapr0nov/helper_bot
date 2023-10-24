<?PHP
/**
*   tg bot
**/
$SITE_DIR = dirname(__FILE__) . "/";
require_once($SITE_DIR . 'env.php');
require_once($SITE_DIR . 'Classes/i18n.php');
require_once($SITE_DIR . 'Classes/tg_Bot/tg.class.php');
require_once($SITE_DIR . 'Classes/dbController/db.class.php');
require_once($SITE_DIR . 'Classes/dbController/Note.php');
require_once($SITE_DIR . 'Classes/dbController/User.php');

header('Content-Type: text/html; charset=utf-8'); // Выставляем кодировку UTF-8
date_default_timezone_set('Europe/Moscow');

$tgBot = new TgBotClass($BOT_TOKEN);

$db = new DB($DB_SERVER, $DB_USER, $DB_PASSWORD, $DB_NAME);

// Start onece when installing bot (create tables and register webhook)
$notes = new Note($db->MYSQLI);
$users = new User($db->MYSQLI);

if ($INIT) {
    $url = $_SERVER["REQUEST_SCHEME"] . "://" . $_SERVER["SERVER_NAME"] . $_SERVER["REQUEST_URI"];
    $result = $tgBot->register_web_hook($url);
    $response = json_decode($result);
    echo "<p>" . $response->description . "</p>";
    echo "<p>" . $notes->init() . "</p>";
    echo "<p>" . $users->init() . "</p>";
    return;
}

// Bot Logic

$dataInput = file_get_contents('php://input'); // весь ввод перенаправляем в $data
$data = json_decode($dataInput, true); // декодируем json-закодированные-текстовые данные в PHP-массив
$tgBot->get_data($dataInput);

// проверяем есть ли в БД такой пользователь, добавляем и возвращаем ID
$uid = $users->checkUser($tgBot->MSG_INFO["user_id"]) OR $users->add($tgBot->MSG_INFO["user_id"], $tgBot->MSG_INFO["from_first_name"], $tgBot->MSG_INFO["from_last_name"], $tgBot->MSG_INFO["from_username"] );
$status = $users->getStatus($uid);

$tgBot->msg_to_tg($tgBot->MSG_INFO["chat_id"], " Debug статус : " . json_encode($status));


if ($status->value == "search") {
    $users->setStatus($uid, 'main_menu');
    $finded = $notes->search($uid, $tgBot->MSG_INFO['text']);
    foreach ($finded as $key => $value) {
        $title = "<b>".$value["title"]."</b>";
        $content = $value["content"];
        $tags = $value["tags"];
        $date = $value["date"];
        $note_id = $value["note_id"];
        $tgBot->msg_to_tg($tgBot->MSG_INFO["chat_id"], ($key+1) . ": " . $title . $content);
    }

    return;
}

   
if ($status->value == "add_note") {
 //   $notes->add($uid, $tgBot->MSG_INFO['text']);
    $users->setStatus($uid,'main_menu');
    $tgBot->msg_to_tg($tgBot->MSG_INFO["chat_id"], "Принято возвращаемся в главное меню: ", $tgBot->keyboard([[ $MENU1['search'], $MENU1['add_note'] ]]));
    return;
}

// Назначаем действия не по статусу а по тексту сообщения
if ($tgBot->MSG_INFO["msg_type"] == 'message')  {
    if($tgBot->MSG_INFO['text'] == $MENU1['search']) {
        $users->setStatus($uid, 'search');
        $tgBot->msg_to_tg($tgBot->MSG_INFO["chat_id"], "Укажите что ищем: ");
        return;
    };

    if($tgBot->MSG_INFO['text'] == $MENU1['add_note']) {
        $users->setStatus($uid,'add_note');
        return;
    };

    return;
}

$tgBot->msg_to_tg($tgBot->MSG_INFO["chat_id"], " Menu default действие: ", $tgBot->keyboard([[ $MENU1['search'], $MENU1['add_note'] ]]));
//$tgBot->delete_msg_tg($tgBot->MSG_INFO["chat_id"], $tgBot->MSG_INFO["message_id"]);

?>