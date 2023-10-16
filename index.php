<?PHP
/**
*   tg bot
**/
$SITE_DIR = dirname(__FILE__) . "/";
require_once($SITE_DIR . 'env.php');
require_once($SITE_DIR . 'Classes/tg_Bot/tg.class.php');
require_once($SITE_DIR . 'Classes/dbController/db.class.php');
require_once($SITE_DIR . 'Classes/dbController/Note.php');
require_once($SITE_DIR . 'Classes/dbController/User.php');

header('Content-Type: text/html; charset=utf-8'); // Выставляем кодировку UTF-8
date_default_timezone_set('Europe/Moscow');

$tgBot = new TgBotClass($BOT_TOKEN);

$db = new DB($DB_SERVER, $DB_USER, $DB_PASSWORD, $DB_NAME);

if ($INIT) {
    $url = $_SERVER["REQUEST_SCHEME"] . "://" . $_SERVER["SERVER_NAME"] . $_SERVER["REQUEST_URI"];
    $result = $tgBot->register_web_hook($url);
    $response = json_decode($result);
    echo "<p>" . $response->description . "</p>";

    $notes = new Note($db->MYSQLI);
    echo "<p>" . $notes->init() . "</p>";
    $users = new User($db->MYSQLI);
    echo "<p>" . $users->init() . "</p>";
    return;
}

// $dataInput = file_get_contents('php://input'); // весь ввод перенаправляем в $data
// $data = json_decode($dataInput, true); // декодируем json-закодированные-текстовые данные в PHP-массив
// $tgBot->get_data($dataInput);

// $tgBot->msg_to_tg($tgBot->MSG_INFO["chat_id"], "Текст сообщения", $tgBot->keyboard([['кнопка'],['кнопка2']]));
// $tgBot->delete_msg_tg($tgBot->MSG_INFO["chat_id"], $tgBot->MSG_INFO["message_id"]);

?>