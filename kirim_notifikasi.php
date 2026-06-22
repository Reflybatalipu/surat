<?php
// Fungsi untuk membuat Token Akses dari Google (OAuth 2.0)
function getAccessToken($keyFilePath) {
    $keyFile = json_decode(file_get_contents($keyFilePath), true);
    $clientEmail = $keyFile['client_email'];
    $privateKey = $keyFile['private_key'];
    
    $header = json_encode(['alg' => 'RS256', 'typ' => 'JWT']);
    $now = time();
    $payload = json_encode([
        'iss' => $clientEmail,
        'scope' => 'https://www.googleapis.com/auth/firebase.messaging',
        'aud' => 'https://oauth2.googleapis.com/token',
        'exp' => $now + 3600,
        'iat' => $now
    ]);
    
    $base64UrlHeader = str_replace(['+', '/', '='], ['-', '_', ''], base64_encode($header));
    $base64UrlPayload = str_replace(['+', '/', '='], ['-', '_', ''], base64_encode($payload));
    
    $signature = '';
    openssl_sign($base64UrlHeader . "." . $base64UrlPayload, $signature, $privateKey, OPENSSL_ALGO_SHA256);
    $base64UrlSignature = str_replace(['+', '/', '='], ['-', '_', ''], base64_encode($signature));
    
    $jwt = $base64UrlHeader . "." . $base64UrlPayload . "." . $base64UrlSignature;
    
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, 'https://oauth2.googleapis.com/token');
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query([
        'grant_type' => 'urn:ietf:params:oauth:grant-type:jwt-bearer',
        'assertion' => $jwt
    ]));
    
    $response = curl_exec($ch);
    curl_close($ch);
    
    $responseData = json_decode($response, true);
    return $responseData['access_token'] ?? null;
}

// Fungsi Utama untuk Menembakkan Notifikasi ke HP
function kirimNotifikasiFCM($fcmToken_Tujuan, $judul_notif, $isi_notif) {
    // ⚠️ PASTIKAN NAMA FILE JSON INI BENAR DAN LOKASINYA TEPAT
    $keyFilePath = __DIR__ . '/firebase_credentials.json'; 
    
    if (!file_exists($keyFilePath)) {
        return "Error: File kredensial Firebase tidak ditemukan!";
    }

    $projectId = json_decode(file_get_contents($keyFilePath))->project_id;
    $accessToken = getAccessToken($keyFilePath);
    
    if (!$accessToken) {
        return "Error: Gagal mendapatkan Access Token dari Google.";
    }

    $url = 'https://fcm.googleapis.com/v1/projects/' . $projectId . '/messages:send';
    
    $message = [
        'message' => [
            'token' => $fcmToken_Tujuan,
            'notification' => [
                'title' => $judul_notif,
                'body' => $isi_notif
            ]
        ]
    ];
    
    $headers = [
        'Authorization: Bearer ' . $accessToken,
        'Content-Type: application/json'
    ];
    
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($message));
    
    $result = curl_exec($ch);
    curl_close($ch);
    
    return $result;
}
?>