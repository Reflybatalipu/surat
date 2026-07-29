<?php
declare(strict_types=1);
$bot_token = 'TOKEN-BOT';

function telegram_log(string $message): void
{
    $dir = __DIR__ . '/logs';

    if (!is_dir($dir)) {
        mkdir($dir, 0755, true);
    }
    $logFile = $dir . '/telegram_worker.log';

    file_put_contents(
        $logFile,
        '[' . date('Y-m-d H:i:s') . '] ' . $message . PHP_EOL,
        FILE_APPEND | LOCK_EX
    );
}
function telegram_request(string $method, array $data): bool
{
    global $bot_token;

    $url = "https://api.telegram.org/bot{$bot_token}/{$method}";

    $ch = curl_init($url);

    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => $data,
        CURLOPT_CONNECTTIMEOUT => 10,
        CURLOPT_TIMEOUT => 30,
        CURLOPT_SSL_VERIFYPEER => true,
        CURLOPT_SSL_VERIFYHOST => 2,
        CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
        CURLOPT_USERAGENT => 'SIMPERS Telegram Worker/1.0'
    ]);

    $response = curl_exec($ch);

    if ($response === false) {

        telegram_log(
            "CURL ERROR : " . curl_error($ch)
        );

        curl_close($ch);

        return false;
    }
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    if ($httpCode != 200) {

        telegram_log(
            "HTTP ERROR : {$httpCode}"
        );

        return false;
    }
    $json = json_decode($response, true);

    if (!is_array($json)) {

        telegram_log(
            "INVALID JSON : {$response}"
        );
        return false;
    }
    if (!isset($json['ok']) || $json['ok'] !== true) {

        telegram_log(
            "TELEGRAM ERROR : " .
            ($json['description'] ?? 'Unknown Error')
        );
        return false;
    }
    return true;
}
function kirim_telegram(string $telegram_id, string $pesan): bool
{
    telegram_log(
        "Mengirim pesan ke {$telegram_id}"
    );
    $hasil = telegram_request(
        'sendMessage',
        [
            'chat_id' => $telegram_id,
            'text' => $pesan,
            'parse_mode' => 'Markdown'
        ]
    );
    if ($hasil) {
        telegram_log(
            "SUKSES kirim pesan ke {$telegram_id}"
        );
    } else {
        telegram_log(
            "GAGAL kirim pesan ke {$telegram_id}"
        );
    }
    return $hasil;
}
function kirim_dokumen_telegram(
    string $chat_id,
    string $file_path,
    string $caption = ''
): bool {
    if (!file_exists($file_path)) {
        telegram_log(
            "FILE TIDAK DITEMUKAN : {$file_path}"
        );
        return false;
    }
    $file = new CURLFile(realpath($file_path));
    telegram_log(
        "Mengirim dokumen ke {$chat_id}"
    );

    $hasil = telegram_request(
        'sendDocument',
        [
            'chat_id' => $chat_id,
            'document' => $file,
            'caption' => $caption,
            'parse_mode' => 'Markdown'
        ]
    );
    if ($hasil) {
        telegram_log(
            "SUKSES kirim dokumen ke {$chat_id}"
        );
    } else {
        telegram_log(
            "GAGAL kirim dokumen ke {$chat_id}"
        );
    }
    return $hasil;
}

?>
