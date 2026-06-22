<?php
$bot_token = "8750324075:AAGI3fu1xFQjf4bzzYKn6QuAbJlxUqY9xRA";

function kirim_telegram($telegram_id, $pesan) {
    global $bot_token; 
    
    // URL Endpoint API Telegram
    $url = "https://api.telegram.org/bot" . $bot_token . "/sendMessage";
    
    // Data yang akan ditembakkan ke Telegram
    $data = [
        'chat_id' => $telegram_id,
        'text' => $pesan,
        'parse_mode' => 'markdown' 
    ];
    
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
    

    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0); // HARUS ANGKA 0, BUKAN false
    curl_setopt($ch, CURLOPT_TIMEOUT, 10); // Batas waktu tunggu agar website tidak ikut lag
    
    // Eksekusi pengiriman!
    $hasil = curl_exec($ch);
    $error = curl_error($ch);
    curl_close($ch);
    
    // Fitur Log untuk mengecek alasan jika masih gagal
    if ($hasil === false) {
        error_log("Error Telegram: " . $error);
    }
    
    return $hasil;
}


function kirim_dokumen_telegram($chat_id, $file_path, $caption = "") {
    global $bot_token;
    
    if (empty($bot_token)) return false;

    $url = "https://api.telegram.org/bot" . $bot_token . "/sendDocument";
    
    if (!file_exists($file_path)) return false;

    $path_absolut = realpath($file_path);
    if (!$path_absolut) return false;

    $document = new CURLFile($path_absolut);

    $data = [
        'chat_id' => $chat_id,
        'document' => $document,
        'caption' => $caption,
        'parse_mode' => 'Markdown'
    ];

    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
    
    // Samakan perbaikannya di sini
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0); 
    curl_setopt($ch, CURLOPT_TIMEOUT, 30);
    
    $hasil = curl_exec($ch);
    curl_close($ch);
    
    return $hasil;
}
?>