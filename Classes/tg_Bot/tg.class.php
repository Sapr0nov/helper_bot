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
        $this->MSG_INFO['text'] = $this->DATA['message']['text'];
        if (isset($this->DATA['message']) && isset($this->DATA['message']['voice'])) {
            $this->MSG_INFO['msg_type'] = 'voice';
            
        }
        if (isset($this->DATA['message'])) {
            $this->MSG_INFO['user_id'] = isset($this->DATA['message']['from']['id']) ? $this->DATA['message']['from']['id'] : 0;
            $this->MSG_INFO['chat_id'] = isset($this->DATA['message']['chat']['id']) ? $this->DATA['message']['chat']['id'] : 0;
            $this->MSG_INFO['message_id'] = $this->DATA['message']['message_id'];
            $this->MSG_INFO['from_first_name'] = isset($this->DATA['message']['from']['first_name']) ? $this->DATA['message']['from']['first_name'] : '';
            $this->MSG_INFO['from_last_name'] = isset($this->DATA['message']['from']['last_name']) ? $this->DATA['message']['from']['last_name'] : '';
            $this->MSG_INFO['from_username'] = isset($this->DATA['message']['from']['username']) ? $this->DATA['message']['from']['username'] : '';
            $this->MSG_INFO['type'] = $this->DATA['message']['chat']['type'];
            $this->MSG_INFO['text'] = isset($this->DATA['message']['text']) ? $this->DATA['message']['text'] :'';    
            if (isset($this->DATA['message']['voice'])) {
                $this->MSG_INFO['msg_type'] = 'voice';
                $this->MSG_INFO['voice'] = isset($this->DATA['message']['voice']) ? $this->DATA['message']['voice'] :'';
                $apiUrl = 'https://api.telegram.org/bot' . $this->BOT_TOKEN . '/getFile?file_id=' . $this->MSG_INFO['voice']['file_id'];
                $response = file_get_contents($apiUrl);
                $responseArray = json_decode($response, true);
                $filePath = $responseArray['result']['file_path'];
                $url = 'https://api.telegram.org/file/bot' . $this->BOT_TOKEN . '/' . $filePath;
                $file = dirname(dirname(__DIR__)) . '/files/' . $this->MSG_INFO['voice']['file_id'] . '.oga';
                copy($url, $file);
                $fileSystemIterator = new FilesystemIterator(dirname(dirname(__DIR__)) . '/files');
                $now = time();
                foreach ($fileSystemIterator as $file) {
                    if ($now - $file->getCTime() >= 60 * 60 * 2) // 2 часа 
                        unlink('files/'.$file->getFilename());
                }
                $this->MSG_INFO['voice']['rel_url'] = '/files/' . $this->MSG_INFO['voice']['file_id'] . '.oga';
            }else{
                $this->MSG_INFO['msg_type'] = 'message';
            }
            //если прислали стикер, а не текст
            if (isset($this->DATA['message']['sticker'])) {
                $this->MSG_INFO['text'] = 'sticker';
                $this->MSG_INFO['sticker'] = array();
                $this->MSG_INFO['sticker']['emoji'] = isset($this->DATA['message']['sticker']['emoji']) ? $this->DATA['message']['sticker']['emoji'] : '';
                $this->MSG_INFO['sticker']['name'] = isset($this->DATA['message']['sticker']['set_name']) ? $this->DATA['message']['sticker']['set_name'] : '';                
            }

            $this->MSG_INFO['date'] = $this->DATA['message']['date'];
            $this->MSG_INFO['name'] = ($this->MSG_INFO['from_first_name'] !== '') ? $this->MSG_INFO['from_first_name'] . ' ' . $this->MSG_INFO['from_last_name'] : $this->MSG_INFO['from_username'];

            // проверяем если передана команда
            if (isset($this->DATA['message']['text']) && isset($this->DATA['message']['entities'])) {
                $this->MSG_INFO['command'] = $this->getCommand($this->DATA['message']['text'], $this->DATA['message']['entities']);
            }
            $this->MSG_INFO['entities'] = (isset($this->DATA['message']['entities'])) ? $this->DATA['message']['entities'] : '';
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
    function msg_to_tg($chat_id, $text, $reply_markup = '', $reply_id = false, $silent = false) {

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
                'reply_to_message_id' => $reply_id,
                'disable_notification' => $silent,
            ]
        ];

        curl_setopt_array($ch, $ch_post);
        $reply_txt = curl_exec($ch);
        curl_close($ch);        

        return $reply_txt;
    }


    function send_audio_tg($msg_id, $chat_id, $reply_id, $audio, $caption = 'audio') {
        
        $request_url = "https://api.telegram.org/bot{$this->BOT_TOKEN}/sendAudio";

        $data = [
            'chat_id' => $chat_id,
            'audio' => new \CURLFile($audio),
            'caption' => $caption,
            'reply_to_message_id' => $reply_id,
        ];
        
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $request_url);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

        $reply_txt = curl_exec($ch);
        curl_close($ch);

        return $reply_txt;
    }
    
    
    function update_msg_tg($msg_id, $chat_id, $text, $reply_markup = '', $silent = false) {

        $request_url = "https://api.telegram.org/bot{$this->BOT_TOKEN}/editMessageText?chat_id={$chat_id}&message_id={$msg_id}&text=" . urlencode($text);
        $ch = curl_init();
        $ch_post = [
            CURLOPT_URL => $request_url,
            CURLOPT_POST => TRUE,
            CURLOPT_RETURNTRANSFER => TRUE,
            CURLOPT_TIMEOUT => 10,
            CURLOPT_POSTFIELDS => [
                'editMessageReplyMarkup' => TRUE,
                'reply_markup' => $reply_markup,
                'chat_id' => $chat_id,
                'parse_mode' => 'HTML'
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

        if (!is_array($arr) || is_null($str)) {
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
        $result_str = $this->filterString($str);
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

    function filterString($input) { 
        $filtered_string = strip_tags($input);
        $filtered_string = htmlspecialchars($filtered_string, ENT_NOQUOTES, 'UTF-8', false);        
     
        return $filtered_string;
    }
}

?>