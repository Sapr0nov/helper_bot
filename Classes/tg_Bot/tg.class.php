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
        if (isset($this->DATA['update_id'])) {
            $this->MSG_INFO['update_id'] = $this->DATA['update_id'];
        };
        if (isset($this->DATA['message'])) {
            $this->MSG_INFO['msg_type'] = 'message';
            $this->MSG_INFO['user_id'] = isset($this->DATA['message']['from']['id']) ? $this->DATA['message']['from']['id'] : 0;
            $this->MSG_INFO['chat_id'] = isset($this->DATA['message']['chat']['id']) ? $this->DATA['message']['chat']['id'] : 0;
            $this->MSG_INFO['message_id'] = $this->DATA['message']['message_id'];
            $this->MSG_INFO['from_first_name'] = isset($this->DATA['message']['from']['first_name']) ? $this->DATA['message']['from']['first_name'] : '';
            $this->MSG_INFO['from_last_name'] = isset($this->DATA['message']['from']['last_name']) ? $this->DATA['message']['from']['last_name'] : '';
            $this->MSG_INFO['from_username'] = isset($this->DATA['message']['from']['username']) ? $this->DATA['message']['from']['username'] : '';
            $this->MSG_INFO['type'] = $this->DATA['message']['chat']['type'];
            $this->MSG_INFO['text'] = $this->DATA['message']['text'];
            $this->MSG_INFO['date'] = $this->DATA['message']['date'];
            $this->MSG_INFO['name'] = ($this->MSG_INFO['from_first_name'] !== '') ? $this->MSG_INFO['from_first_name'] . ' ' . $this->MSG_INFO['from_last_name'] : $this->MSG_INFO['from_username'];
            // проверяем если передана команда
            $this->MSG_INFO['command'] = $this->getCommand($this->DATA['message']['text'], $this->DATA['message']['entities']);
            $this->MSG_INFO['entities'] = $this->DATA['message']['entities'];
            // если есть спец разметка приводим ее в виде html
            if (is_null($this->DATA['message']['entities'])) {
                $this->MSG_INFO['text_html'] = $this->DATA['message']['text'];
            }else{
                $this->MSG_INFO['text_html'] = $this->convertEntities($this->DATA['message']['text'], $this->DATA['message']['entities']); 
            }
        }
        // если был ответ под кнопкой
        if (isset($this->DATA['callback_query'])) {
            $this->MSG_INFO['msg_type'] = 'callback';
            $this->MSG_INFO['user_id'] = isset($this->DATA['callback_query']['from']['id']) ? $this->DATA['callback_query']['from']['id'] : 0;
            $this->MSG_INFO['chat_id'] = isset($this->DATA['callback_query']['message']['chat']['id']) ? $this->DATA['callback_query']['message']['chat']['id'] : 0;
            $this->MSG_INFO['message_id'] = $this->DATA['callback_query']['message']['message_id'];
            $this->MSG_INFO['from_first_name'] = isset($this->DATA['callback_query']['from']['first_name']) ? $this->DATA['callback_query']['from']['first_name'] : '';
            $this->MSG_INFO['from_last_name'] = isset($this->DATA['callback_query']['from']['last_name']) ? $this->DATA['callback_query']['from']['last_name'] : '';
            $this->MSG_INFO['from_username'] = isset($this->DATA['callback_query']['from']['username']) ? $this->DATA['callback_query']['from']['username'] : '';
            $this->MSG_INFO['type'] = $this->DATA['callback_query']['chat']['type'];
            $this->MSG_INFO['text'] = $this->DATA['callback_query']['data'];
            $this->MSG_INFO['date'] = $this->DATA['callback_query']['date'];
            $this->MSG_INFO['name'] = ($this->MSG_INFO['from_first_name'] !== '') ? $this->MSG_INFO['from_first_name'] . ' ' . $this->MSG_INFO['from_last_name'] : $this->MSG_INFO['from_username'];
        }
        // если сообщение написано самим ботом
        if (isset($this->DATA['result']) && isset($this->DATA['result']['from']) && $this->DATA['result']['from']['is_bot']) {
            $this->MSG_INFO['msg_type'] = 'bot_message';
            $this->MSG_INFO['chat_id'] = isset($this->DATA['result']['chat']['id']) ? $this->DATA['result']['chat']['id'] : 0;
            $this->MSG_INFO['user_id'] = isset($this->DATA['result']['chat']['id']) ? $this->DATA['result']['chat']['id'] : 0;
            $this->MSG_INFO['text'] = isset($this->DATA['result']['text']) ? $this->DATA['result']['text'] : 0;
            $this->MSG_INFO['message_id'] = $this->DATA['result']['message_id'];
            $this->MSG_INFO['name'] = 'bot';

        }
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
        $SITE_DIR = dirname(__FILE__) . '/';
        $file_message = file_get_contents($SITE_DIR . 'debug.txt');
        $output = json_encode($output);
        file_put_contents($SITE_DIR . 'debug.txt',  $file_message . PHP_EOL . 'output = ' . $output);
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


    private function getCommand(string $str, $arr = null): array {
        $result = array(
        'is_command' => false,
        'command' => null,
        'args' => null
        );

        if (!is_array($arr)) {
            return $result;
        }
        foreach ($arr as $value) {
            if ($value['type'] == 'bot_command') {
                $offset = $value['offset'];
                $length = $value['length'];
                $result['is_command'] = true;
                $result['command'] = trim(mb_substr($str, ($offset + 1), $length));
                $result['args'] = trim(mb_substr($str, $offset + $length, strlen($str)));
            }
        }
        return $result;
    }    


    private function convertEntities(string $str, array $arr): string {
        if (!is_array($arr)) {
            return $str;
        }
        $result_str = $str;
        $arr_string = mb_str_split($str, 1);

        $arr = array_reverse($arr);
        foreach ($arr as $value) {
            $offset = $value['offset'];
            $length = $value['length'];
            $type_switch = $value['type'];
            $type = match ($type_switch) {
                'bold' => array('<b>','</b>'),
                'italic' => array('<i>','</i>'),
                'code' => array('<code>','</code>'),
                'pre' => array('<pre>','</pre>'),
                'underline' => array('<u>','</u>'),
                'strikethrough' => array('<s>','</s>'),
                'spoiler' => array('<span class=\'tg-spoiler\'>','</span>'),
                'url' => array('<a>','</a>'), 
                // 'email' => array('<span>','</span>'),
                // 'hashtag' => array('<span>','</span>'),
                // 'bot_command' => array('<span>','</span>'),
                // 'mention' => array('<span>','</span>'),
                // 'CustomEmoji' => array('<span>','</span>'),
                default => null,
            };
            if (!is_null($type)) {
                array_splice($arr_string, $offset + $length, 0, $type[1]);
                array_splice($arr_string, $offset, 0, $type[0]);
            }
        }
        $result_str = implode('', $arr_string);

        return $result_str;
    }
}
?>