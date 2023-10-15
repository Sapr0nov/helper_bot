<?PHP
/**
*   tg bot
**/

header('Content-Type: text/html; charset=utf-8'); // Выставляем кодировку UTF-8
date_default_timezone_set('Europe/Moscow');

$SITE_DIR = dirname(__FILE__) . "/";
require_once($SITE_DIR . 'env.php'); 
require_once($SITE_DIR . 'tg.class.php');

$tgBot = new TgBotClass($BOT_TOKEN);

$dataInput = file_get_contents('php://input'); // весь ввод перенаправляем в $data
$data = json_decode($dataInput, true); // декодируем json-закодированные-текстовые данные в PHP-массив
$tgBot->get_data($dataInput);

$tgBot->msg_to_tg($tgBot->MSG_INFO["chat_id"], "Текст сообщения", $tgBot->keyboard([['кнопка'],['кнопка2']]));
$tgBot->delete_msg_tg($tgBot->MSG_INFO["chat_id"], $tgBot->MSG_INFO["message_id"]);

?>