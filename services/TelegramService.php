<?php
require __DIR__ . "/../config/config.php";

function kirimPesan($chat_id, $text) {
    global $BOT_TOKEN;

    $url = "https://api.telegram.org/bot$BOT_TOKEN/sendMessage";

    $data = [
        'chat_id' => $chat_id,
        'text' => $text
    ];

    $context = stream_context_create([
        'http' => [
            'header'  => "Content-type: application/x-www-form-urlencoded",
            'method'  => 'POST',
            'content' => http_build_query($data),
        ]
    ]);

    $result = file_get_contents($url, false, $context);
    if ($result === false) {
        error_log("Gagal mengirim pesan ke Telegram: chat_id $chat_id");
    }
    file_put_contents("../storage/response.txt", $result);
}