<?php
class TgBotClass
{
    public $BOT_TOKEN;
    public $DATA;
    public $MSG_INFO;

    function __construct($token){
        $this->BOT_TOKEN = $token; 
    }

    // use only once for set webhook - $path = https://your_site.org/your_bot_path.php
    public function register_web_hook($path) {
        $ch = curl_init();
        $ch_post = [
            CURLOPT_URL => 'https://api.telegram.org/bot' . $this->BOT_TOKEN . '/setWebhook?url=' . $path,
            CURLOPT_POST => TRUE,
            CURLOPT_RETURNTRANSFER => TRUE,
            CURLOPT_TIMEOUT => 10,
        ];

        curl_setopt_array($ch, $ch_post);
        $result = curl_exec($ch);
        curl_close($ch);        

        return $result;

    }


    public function get_data($dataInput) {
        $this->DATA = json_decode($dataInput, true);
        $this->MSG_INFO['update_id'] = $this->DATA['update_id'];
        $this->MSG_INFO['msg_type'] = 'message';
        if (isset($this->DATA['message'])) {
            $this->MSG_INFO['user_id'] = isset($this->DATA['message']['from']['id']) ? $this->DATA['message']['from']['id'] : 0;
            $this->MSG_INFO['chat_id'] = isset($this->DATA['message']['chat']['id']) ? $this->DATA['message']['chat']['id'] : 0;
            $this->MSG_INFO['message_id'] = $this->DATA["message"]["message_id"];
            $this->MSG_INFO['from_first_name'] = isset($this->DATA["message"]["from"]['first_name']) ? $this->DATA["message"]["from"]['first_name'] : "";
            $this->MSG_INFO['from_last_name'] = isset($this->DATA["message"]["from"]['last_name']) ? $this->DATA["message"]["from"]['last_name'] : "";
            $this->MSG_INFO['from_username'] = isset($this->DATA["message"]["from"]['username']) ? $this->DATA["message"]["from"]['username'] : "";
            $this->MSG_INFO['type'] = $this->DATA["message"]["chat"]['type'];
            $this->MSG_INFO['text'] = $this->DATA['message']["text"];
            $this->MSG_INFO['date'] = $this->DATA['message']["date"];       
        }
        // если был ответ под кнопкой
        if (isset($this->DATA['callback_query'])) {
            $this->MSG_INFO['msg_type'] = 'callback';
            $this->MSG_INFO['user_id'] = isset($this->DATA['callback_query']['from']['id']) ? $this->DATA['callback_query']['from']['id'] : 0;
            $this->MSG_INFO['chat_id'] = isset($this->DATA['callback_query']["message"]['chat']['id']) ? $this->DATA['callback_query']["message"]['chat']['id'] : 0;
            $this->MSG_INFO['message_id'] = $this->DATA["callback_query"]["message"]["message_id"];
            $this->MSG_INFO['from_first_name'] = isset($this->DATA["callback_query"]["from"]['first_name']) ? $this->DATA["callback_query"]["from"]['first_name'] : "";
            $this->MSG_INFO['from_last_name'] = isset($this->DATA["callback_query"]["from"]['last_name']) ? $this->DATA["callback_query"]["from"]['last_name'] : "";
            $this->MSG_INFO['from_username'] = isset($this->DATA["callback_query"]["from"]['username']) ? $this->DATA["callback_query"]["from"]['username'] : "";
            $this->MSG_INFO['type'] = $this->DATA["callback_query"]["chat"]['type'];
            $this->MSG_INFO['text'] = $this->DATA["callback_query"]["data"];
            $this->MSG_INFO['date'] = $this->DATA["callback_query"]["date"];
        }
        $this->MSG_INFO['name'] = ($this->MSG_INFO['from_first_name'] !== "") ? $this->MSG_INFO['from_first_name'] . " " . $this->MSG_INFO['from_last_name'] : $this->MSG_INFO['from_username'];

    }


    // функция отправки сообщени от бота в диалог с юзером
    function msg_to_tg($chat_id, $text, $reply_markup = '') {

        $ch = curl_init();
        $ch_post = [
            CURLOPT_URL => 'https://api.telegram.org/bot' . $this->BOT_TOKEN . '/sendMessage',
            CURLOPT_POST => TRUE,
            CURLOPT_RETURNTRANSFER => TRUE,
            CURLOPT_TIMEOUT => 10,
            CURLOPT_POSTFIELDS => [
                'chat_id' => $chat_id,
                'parse_mode' => 'HTML',
                'text' => $text,
                'reply_markup' => $reply_markup,
            ]
        ];

        curl_setopt_array($ch, $ch_post);
        $reply_txt = curl_exec($ch);
        curl_close($ch);        

        return $reply_txt;
    }


    public function delete_msg_tg($chat_id, $msg_id) {
        $ch = curl_init();
        $ch_post = [
            CURLOPT_URL => 'https://api.telegram.org/bot' . $this->BOT_TOKEN . '/deleteMessage?chat_id=' . $chat_id . '&message_id=' . $msg_id,
            CURLOPT_POST => TRUE,
            CURLOPT_RETURNTRANSFER => TRUE,
            CURLOPT_TIMEOUT => 10,
            CURLOPT_POSTFIELDS => [
                'chat_id' => $chat_id,
                'parse_mode' => 'HTML'
            ]
        ];

        curl_setopt_array($ch, $ch_post);
        curl_exec($ch);
        curl_close($ch);
    }


    public function debug($output) {
        $SITE_DIR = dirname(__FILE__) . "/";
        $file_message = file_get_contents($SITE_DIR . 'debug.txt');
        file_put_contents($SITE_DIR . 'debug.txt',  $file_message . PHP_EOL . "output = " . $output);
    }


    public function keyboard($arr) {
        return json_encode(array(
            'keyboard' => $arr,
            'resize_keyboard' => true, 
            'one_time_keyboard' => false
            )
        );
    }


    public function inline_keyboard($arr) {
        return json_encode(array(
            'inline_keyboard' => $arr,
        ));
    }
}
?>