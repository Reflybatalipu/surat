<?php
require_once 'config.php';

// Baca input mentah
$rawInput = file_get_contents('php://input');
$input    = json_decode($rawInput, true);

// JIKA ANDROID MENGIRIM SEBAGAI FORM DATA (BUKAN JSON RAW)
if (empty($input)) {
    $tokens = isset($_POST['tokens']) ? $_POST['tokens'] : [];
    $title  = $_POST['title'] ?? '';
    $body   = $_POST['body'] ?? '';
    
    // Jika berupa string teks terpisah koma, ubah ke array
    if (!is_array($tokens) && !empty($tokens)) {
        $tokens = explode(',', $tokens);
    }
} else {
    // JIKA ANDROID MENGIRIM SEBAGAI JSON RAW
    $tokens   = $input['tokens']   ?? [];   
    $title    = $input['title']    ?? '';
    $body     = $input['body']     ?? '';
}

// Validasi akhir dengan log respons yang lebih detail
if (empty($tokens)) {
    echo json_encode([
        'status' => 'error',
        'message' => 'Tidak ada token',
        'debug_raw_input' => $rawInput, // Akan memperlihatkan apa isi kiriman Android
        'debug_post' => $_POST
    ]); 
    exit;
}
