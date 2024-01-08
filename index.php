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
require_once($SITE_DIR . 'Classes/chatGPT/chatGPT.class.php');

header('Content-Type: text/html; charset=utf-8'); // Выставляем кодировку UTF-8
date_default_timezone_set('Europe/Moscow');

$tgBot = new TgBotClass($BOT_TOKEN);
$db = new DB($DB_SERVER, $DB_USER, $DB_PASSWORD, $DB_NAME);

// Start onece when installing bot (create tables and register webhook)
$users = new User($db->MYSQLI);
$notes = new Note($db->MYSQLI);
$notices = new Notice($db->MYSQLI);

// webhook for external apps
if ($_GET['key'] == $APP_PASSWORD) {
    if ($_GET['msg'] != '') {
        $reply = $tgBot->msg_to_tg($ADMIN_ID, $_GET['msg']);
        User::save_reply($users, $reply);
        echo'{"status":"ok"}';
        return;
    }
    echo'{"status":"no messages"}';
    return;
}

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
    'menu_search' => $tgBot->keyboard([[$MENU1['search'], $MENU1['add_note'], $MENU_CALENDAR['show']]] ),
    'main_menu' => $tgBot->keyboard([[$MENU_CALENDAR['show']]]),
    'cal_days' => $tgBot->keyboard([
        ['-','1','2','3','4','5','6'],
        ['7','8','[9*]','10*','11','12','13'],
        ['14','15','16','17','18','19','20'],
        ['21','22','23','24','25','26','27'],
        ['28','29','30','-','-','-','-']]),
    );
// проверяем есть ли в БД такой пользователь, добавляем и возвращаем ID
$uid = $users->checkUser($tgBot->MSG_INFO['user_id']) OR $users->add($tgBot->MSG_INFO['user_id'], $tgBot->MSG_INFO['from_first_name'], $tgBot->MSG_INFO['from_last_name'], $tgBot->MSG_INFO['from_username'] );
$status = $users->getStatus($uid);


// Если был callback
if ($tgBot->MSG_INFO['msg_type'] == 'callback') {
    if (stripos($tgBot->MSG_INFO['text'],'txt2speach') !== false) {
        [$command, $arg] = explode(' ', $tgBot->MSG_INFO['text']);
        $MSGINFO = $tgBot->MSG_INFO;
        $MSGINFO['message_id'] = $arg;
        $msg = $users->msg_find($MSGINFO);
        $options = new stdClass;
        $options->token = $GPT_TOKEN;
        $options->model = 'tts-1';
        $options->endPoint = '/audio/speech';
        $options->voice = 'nova';
        $GPT = new ChatGPT($options);
        speachGPT($tgBot, $GPT, $users, $msg->text);        
        return;
    }

    if (stripos($tgBot->MSG_INFO['text'],'note_edit') !== false) {
        [$command, $arg] = explode(' ', $tgBot->MSG_INFO['text']);
//        $notes->edit();
        $reply = $tgBot->msg_to_tg($tgBot->MSG_INFO['chat_id'], 'callback pressed: ' . $command . ' arg: ' . $arg, $keyboard['menu_search']);
        User::save_reply($users, $reply);
        return;
    }
    if (stripos($tgBot->MSG_INFO['text'],'note_delete') !== false) {
        [$command, $arg] = explode(' ', $tgBot->MSG_INFO['text']);
        $result = $notes->delete($uid, $arg);
        $response = ($result) ? 'Заметка удалена ' : 'Не удалось удалить заметку'; 
        $reply = $tgBot->msg_to_tg($tgBot->MSG_INFO['chat_id'], $response, $keyboard['menu_search']);
        User::save_reply($users, $reply);
        return;
    }

    $reply = $tgBot->msg_to_tg($tgBot->MSG_INFO['chat_id'], 'callback pressed: ' . $tgBot->MSG_INFO['text'], $keyboard['menu_search']);
    User::save_reply($users, $reply);
    return;
}

// Если введена команда
if ($tgBot->MSG_INFO['command']['is_command'])  {
    // Выходим из всех подменю
    $users->setStatus($uid, 'main_menu');
    // проверяем что за команда прилетела
    if ($tgBot->MSG_INFO['command']['command'] == 'chatGPT' || $tgBot->MSG_INFO['command']['command'] == 'gpt') {
        $options = new stdClass;
        $options->token = $GPT_TOKEN;
        $GPT = new ChatGPT($options);
        searchGPT($tgBot, $GPT, $users, $tgBot->MSG_INFO['command']['args']);
        return;
    }

    if ($tgBot->MSG_INFO['command']['command'] == 'search' || $tgBot->MSG_INFO['command']['command'] == 's') {
        search($tgBot, $uid, $keyboard['inline_notes'], $users, $notes);
        return;
    }

    if ($tgBot->MSG_INFO['command']['command'] == 'add_note') {
        $reply = $tgBot->msg_to_tg($tgBot->MSG_INFO['chat_id'], 'Введите текст заметки: ', $keyboard['menu_search']);
        User::save_reply($users, $reply);
        $users->setStatus($uid,'add_note');
        return;
    }

    if ($tgBot->MSG_INFO['command']['command'] == 'add_notice') {
        $reply = $tgBot->msg_to_tg($tgBot->MSG_INFO['chat_id'], 'Введите текст напоминания:', $keyboard['menu_search']);
        User::save_reply($users, $reply);
        $users->setStatus($uid,'add_notice');
        return;
    }

    if ($tgBot->MSG_INFO['command']['command'] == 'clear') {
        $reply = $tgBot->msg_to_tg($tgBot->MSG_INFO['chat_id'], 'Очищаем историю. Подождите..'. json_encode($tgBot->MSG_INFO), $keyboard['menu_search']);
        
        User::save_reply($users, $reply);
        $users->msgs_clear($tgBot, $tgBot->MSG_INFO['chat_id']);
        return;
    }

    $reply = $tgBot->msg_to_tg($tgBot->MSG_INFO['chat_id'], 'Данная команда не поддерживается', $keyboard['menu_search']);
    User::save_reply($users, $reply);
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
    User::save_reply($users, $reply);
    $users->setStatus($uid, 'main_menu');
    return;
}

// если режим добавления напоминания
if ($status->value == 'add_notice') {
    $notices->presave($uid, $tgBot->MSG_INFO['text_html']);
    $reply = $tgBot->msg_to_tg($tgBot->MSG_INFO['chat_id'], 'Введите время напоминания в формате YYYY-MM-DD HH-MM или одно число - через сколько минут (5/n - повторить n раз): ', $keyboard['menu_search']);
    User::save_reply($users, $reply);
    $users->setStatus($uid, 'add_notice_time');
    return;
}

// если режим добавления напоминания 2
if ($status->value == 'add_notice_time') {
    $notices->add($uid, $tgBot->MSG_INFO['text_html']);
    $reply = $tgBot->msg_to_tg($tgBot->MSG_INFO['chat_id'], 'Сохранено, возвращаемся в главное меню', $keyboard['menu_search']);
    User::save_reply($users, $reply);
    $users->setStatus($uid, 'main_menu');
    return;
}

// Назначаем действия не по статусу а по тексту сообщения
if ($tgBot->MSG_INFO['msg_type'] == 'message')  {
    if($tgBot->MSG_INFO['text'] == $MENU1['search']) {
        $reply = $tgBot->msg_to_tg($tgBot->MSG_INFO['chat_id'], 'Укажите что ищем: ',$keyboard['menu_search']);
        User::save_reply($users, $reply);
        $users->setStatus($uid, 'search');
        return;
    };

    if($tgBot->MSG_INFO['text'] == $MENU1['add_note']) {
        $reply = $tgBot->msg_to_tg($tgBot->MSG_INFO['chat_id'], 'Введите текст заметки: ',$keyboard['menu_search']);
        User::save_reply($users, $reply);
        $users->setStatus($uid,'add_note');
        return;
    };

    if($tgBot->MSG_INFO['text'] == $MENU_CALENDAR['show']) {
        $reply = $tgBot->msg_to_tg($tgBot->MSG_INFO['chat_id'], 'Выберите день ', $keyboard['cal_days']);
        User::save_reply($users, $reply);
//        $users->setStatus($uid,'add_note');
        return;
    };

}

// аудио сообщения
if ($tgBot->MSG_INFO['msg_type'] == 'voice') {
    $options = new stdClass;
    $options->token = $GPT_TOKEN;
    $options->model = 'whisper-1';
    $options->endPoint = '/audio/transcriptions';
    $GPT = new ChatGPT($options);
    transcribeGPT($tgBot, $GPT, $users, $tgBot->MSG_INFO['voice']['rel_url']);
    return;
}

//дефолтное сообщение тоже в chatGPT
$options = new stdClass;
$options->token = $GPT_TOKEN;
$GPT = new ChatGPT($options);
searchGPT($tgBot, $GPT, $users, $tgBot->MSG_INFO['text']);
return;


 /**
  *  functions
  */
  function searchGPT($tgBot, $GPT, $users, $question) {
    if ($question == '') {
        $reply = $tgBot->msg_to_tg($tgBot->MSG_INFO['chat_id'], 'Вы не задали вопрос');
        User::save_reply($users, $reply);
        return;
    }

    $reply = $tgBot->msg_to_tg($tgBot->MSG_INFO['chat_id'], "⌛ loading...");
    $msgID = User::save_reply($users, $reply);
    
    $response = $GPT->ask($question);
    $answer = json_decode($response);
    $reply = $tgBot->update_msg_tg($msgID, $tgBot->MSG_INFO['chat_id'], 'finished');
    if ($answer) {
        $answ = $answer->choices[0]->message->content;
        $regEx = '/```(\w+)(.+?)```/is';
        $regEx2 = '/```(.+?)```/is';
        $answ = htmlspecialchars($answ, ENT_QUOTES);
        $answ = preg_replace($regEx, '<pre><code language="$1">$2</code></pre>', $answ);
        $answ = preg_replace($regEx2, '<code>$1</code>', $answ);

    }else {
        $answ = "не найдено";
    }
    $reply = $tgBot->update_msg_tg($msgID, $tgBot->MSG_INFO['chat_id'], $answ, $tgBot->inline_keyboard([[ [ "text"=> "озвучить", "callback_data"=> "txt2speach " . $msgID ], ]]) );
    User::save_reply($users, $reply);
    return;
}

function transcribeGPT($tgBot, $GPT, $users, $file) {
    if ($file == '') {
        $reply = $tgBot->msg_to_tg($tgBot->MSG_INFO['chat_id'], 'Что-то пошло не так.. Не вижу аудио-файла');
        User::save_reply($users, $reply);
        return;
    }

    $reply = $tgBot->msg_to_tg($tgBot->MSG_INFO['chat_id'], "⌛ loading...", replyID: $tgBot->MSG_INFO['message_id']);
    $msgID = User::save_reply($users, $reply);

    $response = $GPT->transcribe(__DIR__.$file);
    $answer = json_decode($response);
    $reply = $tgBot->update_msg_tg($msgID, $tgBot->MSG_INFO['chat_id'], 'finished');
    if ($answer) {
        $answ = $answer->text;
    }else {
        $answ = "не найдено";
    }

    $reply = $tgBot->update_msg_tg($msgID, $tgBot->MSG_INFO['chat_id'], $answ);
    User::save_reply($users, $reply);

    $GPT->MODEL = 'gpt-3.5-turbo';
    $GPT->CHAT_END_POINT = '/chat/completions';
    searchGPT($tgBot, $GPT, $users, $answ);

    return;
}
function speachGPT($tgBot, $GPT, $users, $text) {
    if ($text == '') {
        $reply = $tgBot->msg_to_tg($tgBot->MSG_INFO['chat_id'], 'Что-то пошло не так.. Не вижу текста');
        User::save_reply($users, $reply);
        return;
    }

    $reply = $tgBot->msg_to_tg($tgBot->MSG_INFO['chat_id'], "⌛ loading...", replyID: $tgBot->MSG_INFO['message_id']);
    $msgID = User::save_reply($users, $reply);
    $response = $GPT->ask($text);

    $reply = $tgBot->delete_msg_tg($tgBot->MSG_INFO['chat_id'],$msgID);

    if ($response !== false) {
        $savePath = __DIR__.'/files/speach'.$msgID.'.mp3';
        file_put_contents($savePath, $response);
        $file = "https://stacksite.ru/assets/projects3/tg_helper/files/speach".$msgID.".mp3";
        $reply = $tgBot->send_audio_tg($msgID, $tgBot->MSG_INFO['chat_id'], $tgBot->MSG_INFO['message_id'], $file, "Сообщение озвучено");
    }else{
        $reply = $tgBot->update_msg_tg($msgID, $tgBot->MSG_INFO['chat_id'], 'не удалось озвучить файл');
    }
     User::save_reply($users, $reply);
    return;
}

function search($tgBot, $uid, $keyboard, $users, $notes, $search_text = '') {
    if ($search_text == '' && $tgBot->MSG_INFO['command']['args'] == '') {
        $reply = $tgBot->msg_to_tg($tgBot->MSG_INFO['chat_id'], 'Укажите что ищем: ');
        User::save_reply($users, $reply);
        $users->setStatus($uid, 'search');
        return;
    }
    if ($search_text == '') {
        $search_text = $tgBot->MSG_INFO['command']['args'];
    }
    $finded = $notes->search($uid, $search_text);
    // выводим найденные результаты
    if (count($finded) == 0 ) {
        $reply = $tgBot->msg_to_tg($tgBot->MSG_INFO['chat_id'], 'Не удалось ничего найти');
        User::save_reply($users, $reply);
        return;
    }
    foreach ($finded as $key => $value) {
        $title = '<b>' . $value['title'] . '</b>';
        $content = $value['content'];
        $tags = $value['tags'];
        $date = $value['date'];
        $note_id = $value['note_id'];
        $keyboard = $tgBot->inline_keyboard([[
            [
                "text"=> "теги",
                "callback_data"=> "note_tags " . $note_id
            ], 
            [
                "text"=> "изменить",
                "callback_data"=> "note_edit " . $note_id
            ], 
            [
                "text"=> "удалить",
                "callback_data"=> "note_delete " . $note_id
            ] 
            ]]);
        $reply = $tgBot->msg_to_tg($tgBot->MSG_INFO['chat_id'], ($key+1) . ': ' . $title . "\r\n" . $content, $keyboard);
        User::save_reply($users, $reply);
    }

    return;
}

?>