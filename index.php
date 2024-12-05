<?php
// إعدادات عرض الأخطاء
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

define("API_KEY", '7361470544:AAEitqyfPIq2BFP33Hq38D6J3MxYxV40Q2I');

class TelegramBot {
    private $apiKey;

    public function __construct($apiKey) {
        $this->apiKey = $apiKey;
    }

    public function sendMessage($chatId, $text, $markup = null) {
        $url = "https://api.telegram.org/bot" . $this->apiKey . "/sendMessage";
        $data = [
            'chat_id' => $chatId,
            'text' => $text,
            'parse_mode' => 'Markdown',
            'reply_markup' => $markup ? json_encode($markup) : null
        ];
        return $this->makeRequest($url, $data);
    }

    public function sendPhoto($chatId, $photo, $caption, $markup = null) {
        $url = "https://api.telegram.org/bot" . $this->apiKey . "/sendPhoto";
        $data = [
            'chat_id' => $chatId,
            'photo' => $photo,
            'caption' => $caption,
            'parse_mode' => 'Markdown',
            'reply_markup' => $markup ? json_encode($markup) : null
        ];
        return $this->makeRequest($url, $data);
    }

    private function makeRequest($url, $data) {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
        $res = curl_exec($ch);
        if (curl_errno($ch)) {
            error_log('Curl error: ' . curl_error($ch));
            return null;
        }
        curl_close($ch);
        return json_decode($res, true);
    }
}

$bot = new TelegramBot(API_KEY);
$input = file_get_contents("php://input");
$update = json_decode($input, true);

$chatId = $update['message']['chat']['id'];
$text = $update['message']['text'];

if ($text == '/start') {
    $markup = [
        'inline_keyboard' => [
            [['text' => "• مطور البوت •", 'url' => "https://t.me/T_0_M0"], ['text' => "• قناة البوت •", 'url' => "https://t.me/izeoe"]]
        ]
    ];
    $welcomeMessage = "*• اهلا بك عزيزي في بوت الذكاء الاصطناعي
• أنا GPT AI ، تم تدريبه باستخدام تقنية الذكاء الاصطناعي 
• لتوفير الإجابات والمحادثات للمستخدمين 
• في مختلف المواضيع والمجالات
• اكدر اساعدك بشيء ؟ 
• لصنع صور بالذكاء الاصطناعي ارسل /image بعدها النص الذي تريده *
```مثال
/image cat```
*• لكتابه على ورقه بيضاء نص ارسل /Write بعدها النص الذي تريد كتابته في ورقه*
```مثال
/Write cat```";
    $bot->sendMessage($chatId, $welcomeMessage, $markup);
    return;
}

if (strpos($text, '/image ') === 0) {
    $description = substr($text, 7);
    $markup = [
        'inline_keyboard' => [
            [['text' => "• مطور البوت •", 'url' => "https://t.me/T_0_M0"], ['text' => "• قناة البوت •", 'url' => "https://t.me/izeoe"]]
        ]
    ];

    $waitingMessage = $bot->sendMessage($chatId, "*• جار انشاء صورة حسب وصفك ↤ $description*", $markup);

    $api = 'http://art.nowtechai.com/art?name=' . urlencode($description);
    $headers = [
        "User-Agent: okhttp/5.0.0-alpha.9",
        "Connection: Keep-Alive",
        "Accept: application/json"
    ];

    $context = stream_context_create([
        "http" => [
            "header" => implode("\r\n", $headers)
        ]
    ]);

    $response = @file_get_contents($api, false, $context);
    if ($response !== false) {
        $data = json_decode($response, true);
        if ($data && isset($data['code']) && $data['code'] == 200 && !empty($data['data']) && isset($data['data'][0]['img_url'])) {
            $imageData = $data['data'][0];
            sleep(1);

            $bot->sendPhoto($chatId, $imageData['img_url'], "*• تم انشاء الصورة بنجاح ✅ .\n• وصف الصورة ↤ $description*", $markup);
            $bot->sendMessage($chatId, "*• الصورة تم إنشاؤها بنجاح.*", $markup);
            exit;
        } else {
            $bot->sendMessage($chatId, "*• لم يتم العثور على الصورة.*", $markup);
            exit;
        }
    } else {
        $bot->sendMessage($chatId, "*• حدث خطأ أثناء إنشاء الصورة ، الرجاء إدخال وصف واضح ومفهوم.*", $markup);
        exit;
    }
}

if (strpos($text, '/Write ') === 0) {
    $textToWrite = substr($text, 7);
    $photoUrl = "https://apis.xditya.me/write?text=" . urlencode($textToWrite);
    $markup = [
        'inline_keyboard' => [
            [['text' => "• مطور البوت •", 'url' => "https://t.me/T_0_M0"], ['text' => "• قناة البوت •", 'url' => "https://t.me/izeoe"]]
        ]
    ];
    $bot->sendPhoto($chatId, $photoUrl, "`$textToWrite`", $markup);
    exit;
}

// إرسال استجابة API خارجية
function sendApiResponse($chatId, $message) {
    $apiUrl = "https://chatai.aritek.app/stream";
    $payload = json_encode([
        "machineId" => substr(uniqid(), 0, 16) . "." . substr((string)rand(), 0, 18),
        "msg" => [["content" => $message, "role" => "user"]],
        "token" => "eyJzdWIiOiIyMzQyZmczNHJ0MzR0MzQiLCJuYW1lIjoiSm9objM0NTM0NT",
        "type" => 0
    ]);

    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $apiUrl);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Content-Type: application/x-www-form-urlencoded',
        'User-Agent: Dalvik/2.1.0 (Linux; U; Android 9; G011A Build/PI)'
    ]);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $payload);
    $apiResponse = curl_exec($ch);
    curl_close($ch);
    $response_parts = '';
    $result = explode("\n", $apiResponse);
    foreach ($result as $line) {
        if (strpos($line, "data:") === 0) {
            $data_line = trim(substr($line, 5));
            if (!empty($data_line)) {
                $response_json = json_decode($data_line, true);
                if (isset($response_json['choices'][0]['delta']['content'])) {
                    $response_parts .= $response_json['choices'][0]['delta']['content'];
                }
            }
        }
    }
    $waitingMessage = $bot->sendMessage($chatId, "*• انتظر من فضلك ⏱️*", ['parse_mode' => 'Markdown']);
    $message_id = $waitingMessage['result']['message_id'];
    $bot->sendMessage($chatId, !empty($response_parts) ? $response_parts : 'لم أتمكن من الحصول على رد.', ['parse_mode' => "markdown"]);
}
?>
