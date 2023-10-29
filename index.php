<?PHP
/**
*   tg bot
**/
$SITE_DIR = dirname(__FILE__) . '/';
require_once($SITE_DIR . 'env.php');
require_once($SITE_DIR . 'Classes/i18n.php');
require_once($SITE_DIR . 'Classes/tg_Bot/tg.class.php');
require_once($SITE_DIR . 'Classes/dbController/db.class.php');
require_once($SITE_DIR . 'Classes/dbController/User.php');
require_once($SITE_DIR . 'Classes/dbController/Note.php');
require_once($SITE_DIR . 'Classes/dbController/Notice.php');

header('Content-Type: text/html; charset=utf-8'); // Выставляем кодировку UTF-8
date_default_timezone_set('Europe/Moscow');

$tgBot = new TgBotClass($BOT_TOKEN);
$db = new DB($DB_SERVER, $DB_USER, $DB_PASSWORD, $DB_NAME);

// Start onece when installing bot (create tables and register webhook)
$users = new User($db->MYSQLI);
$notes = new Note($db->MYSQLI);
$notices = new Notice($db->MYSQLI);


if ($INIT) {
    $url = $_SERVER['REQUEST_SCHEME'] . '://' . $_SERVER['SERVER_NAME'] . $_SERVER['REQUEST_URI'];
    $result = $tgBot->register_web_hook($url);
    $response = json_decode($result);
    echo '<p>' . $response->description . '</p>';
    echo '<p>' . $users->init() . '</p>';
    echo '<p>' . $notes->init() . '</p>';
    echo '<p>' . $notices->init() . '</p>';
    return;
}

// Bot Logic

$dataInput = file_get_contents('php://input'); // весь ввод перенаправляем в $data
$data = json_decode($dataInput, true); // декодируем json-закодированные-текстовые данные в PHP-массив

$tgBot->get_data($dataInput);
$users->msg_save($tgBot->MSG_INFO);

$keyboard = array(
    'menu_search' => $keyboard['menu_search'],
    'main_menu' => $keyboard['menu_search'],
);
// проверяем есть ли в БД такой пользователь, добавляем и возвращаем ID
$uid = $users->checkUser($tgBot->MSG_INFO['user_id']) OR $users->add($tgBot->MSG_INFO['user_id'], $tgBot->MSG_INFO['from_first_name'], $tgBot->MSG_INFO['from_last_name'], $tgBot->MSG_INFO['from_username'] );
$status = $users->getStatus($uid);

// Если введена команда
if ($tgBot->MSG_INFO['command']['is_command'])  {
    // Выходим из всех подменю
    $users->setStatus($uid, 'main_menu');
    // проверяем что за команда прилетела
    if ($tgBot->MSG_INFO['command']['command'] == 'search' || $tgBot->MSG_INFO['command']['command'] == 's') {
        search($tgBot, $uid, $keyboard['menu_search'], $users, $notes);
        return;
    }

    if ($tgBot->MSG_INFO['command']['command'] == 'add_note') {
        $reply = $tgBot->msg_to_tg($tgBot->MSG_INFO['chat_id'], 'Введите текст заметки: ', $keyboard['menu_search']);
        save_reply($users, $reply);
        $users->setStatus($uid,'add_note');
        return;
    }

    if ($tgBot->MSG_INFO['command']['command'] == 'add_notice') {
        $reply = $tgBot->msg_to_tg($tgBot->MSG_INFO['chat_id'], 'Введите текст напоминания:', $keyboard['menu_search']);
        save_reply($users, $reply);
        $users->setStatus($uid,'add_notice');
        return;
    }

    if ($tgBot->MSG_INFO['command']['command'] == 'clear') {
        $reply = $tgBot->msg_to_tg($tgBot->MSG_INFO['chat_id'], 'Очищаем историю. Подождите...', $keyboard['menu_search']);
        save_reply($users, $reply);
        $users->msgs_clear($tgBot, $tgBot->MSG_INFO['chat_id']);
        return;
    }

    $reply = $tgBot->msg_to_tg($tgBot->MSG_INFO['chat_id'], 'Данная команда не поддерживается', $keyboard['menu_search']);
    save_reply($users, $reply);
    return;
}

// Если режим поиска
if ($status->value == 'search') {
    search($tgBot, $uid, $keyboard['menu_search'], $users, $notes, $tgBot->MSG_INFO['text']);
    return;
}

// если режим добавления заметки
if ($status->value == 'add_note') {
    $notes->add($uid, $tgBot->MSG_INFO['text_html']);
    $reply = $tgBot->msg_to_tg($tgBot->MSG_INFO['chat_id'], 'Принято возвращаемся в главное меню: ', $keyboard['menu_search']);
    save_reply($users, $reply);
    $users->setStatus($uid, 'main_menu');
    return;
}

// если режим добавления напоминания
if ($status->value == 'add_notice') {
    $notices->presave($uid, $tgBot->MSG_INFO['text_html']);
    $reply = $tgBot->msg_to_tg($tgBot->MSG_INFO['chat_id'], 'Введите время напоминания в формате YYYY-MM-DD HH-MM или одно число - через сколько минут: ', $keyboard['menu_search']);
    save_reply($users, $reply);
    $users->setStatus($uid, 'add_notice_time');
    return;
}

// если режим добавления напоминания 2
if ($status->value == 'add_notice_time') {
    $notices->add($uid, $tgBot->MSG_INFO['text_html']);
    $reply = $tgBot->msg_to_tg($tgBot->MSG_INFO['chat_id'], 'Сохранено, возвращаемся в главное меню', $keyboard['menu_search']);
    save_reply($users, $reply);
    $users->setStatus($uid, 'main_menu');
    return;
}

// Назначаем действия не по статусу а по тексту сообщения
if ($tgBot->MSG_INFO['msg_type'] == 'message')  {
    if($tgBot->MSG_INFO['text'] == $MENU1['search']) {
        $reply = $tgBot->msg_to_tg($tgBot->MSG_INFO['chat_id'], 'Укажите что ищем: ',$keyboard['menu_search']);
        save_reply($users, $reply);
        $users->setStatus($uid, 'search');
        return;
    };

    if($tgBot->MSG_INFO['text'] == $MENU1['add_note']) {
        $reply = $tgBot->msg_to_tg($tgBot->MSG_INFO['chat_id'], 'Введите текст заметки: ',$keyboard['menu_search']);
        save_reply($users, $reply);
        $users->setStatus($uid,'add_note');
        return;
    };

}


$reply = $tgBot->msg_to_tg($tgBot->MSG_INFO['chat_id'], ' Menu default действие: ', $keyboard['menu_search']);
save_reply($users, $reply);
$tgBot->delete_msg_tg($tgBot->MSG_INFO['chat_id'], $tgBot->MSG_INFO['message_id']);



 /**
  *  functions
  */

function search($tgBot, $uid, $keyboard, $users, $notes, $search_text = '') {
    if ($search_text == '' && $tgBot->MSG_INFO['command']['args'] == '') {
        $reply = $tgBot->msg_to_tg($tgBot->MSG_INFO['chat_id'], 'Укажите что ищем: ', $keyboard);
        save_reply($users, $reply);
        $users->setStatus($uid, 'search');
        return;
    }
    if ($search_text == '') {
        $search_text = $tgBot->MSG_INFO['command']['args'];
    }
    $finded = $notes->search($uid, $search_text);
    // выводим найденные результаты
    if (count($finded) == 0 ) {
        $reply = $tgBot->msg_to_tg($tgBot->MSG_INFO['chat_id'], 'Не удалось ничего найти', $keyboard);
        save_reply($users, $reply);
        return;
    }
    foreach ($finded as $key => $value) {
        $title = '<b>' . $value['title'] . '</b>';
        $content = $value['content'];
        $tags = $value['tags'];
        $date = $value['date'];
        $note_id = $value['note_id'];
        $reply = $tgBot->msg_to_tg($tgBot->MSG_INFO['chat_id'], ($key+1) . ': ' . $title . "\r\n" . $content, $keyboard);
        save_reply($users, $reply);
    }

    return;
}

function save_reply($users, $reply) {
    $replyTgBot = new TgBotClass('');
    $replyTgBot->get_data($reply);
    $users->msg_save($replyTgBot->MSG_INFO);
}

?>