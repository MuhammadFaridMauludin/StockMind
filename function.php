<?php
require "config.php";

function kirimPesan($chat_id, $text) {
    global $BOT_TOKEN;

    $url = "https://api.telegram.org/bot$BOT_TOKEN/sendMessage";

    $data = [
        'chat_id' => $chat_id,
        'text' => $text
    ];

    $options = [
        'http' => [
            'header'  => "Content-type: application/x-www-form-urlencoded",
            'method'  => 'POST',
            'content' => http_build_query($data),
        ]
    ];

    $context  = stream_context_create($options);
    $result = file_get_contents($url, false, $context);

    // DEBUG
    file_put_contents("response.txt", $result);
}
require "api.php";

function analisisSaham($kode) {
    $data = getDataSaham($kode);

    // =====================
    // TEKNIKAL
    // =====================
    if ($data["rsi"] > 70) {
        $rsi = "Overbought ❌";
    } elseif ($data["rsi"] < 30) {
        $rsi = "Oversold ✅";
    } else {
        $rsi = "Normal";
    }

    if ($data["harga"] > $data["ma50"]) {
        $trend = "Uptrend ✅";
    } else {
        $trend = "Downtrend ❌";
    }

    // =====================
    // FUNDAMENTAL
    // =====================
    $per = ($data["per"] < 10) ? "Murah ✅" : "Mahal ❌";
    $pbv = ($data["pbv"] < 1.5) ? "Wajar ✅" : "Tinggi ❌";
    $roe = ($data["roe"] > 15) ? "Bagus ✅" : "Kurang ❌";

    // =====================
    // NILAI WAJAR
    // =====================
    $hargawajar = $data["eps"] * 8; // asumsi PER industri = 8

    // =====================
    // KESIMPULAN
    // =====================
    if ($data["per"] < 10 && $data["roe"] > 15) {
        $kesimpulan = "Layak dibeli (Fundamental kuat)";
    } else {
        $kesimpulan = "Perlu analisis lebih lanjut";
    }

    // =====================
    // OUTPUT
    // =====================
    $hasil = "

    
📊 ANALISIS SAHAM: $kode

💰 Harga: {$data["harga"]}

📉 TEKNIKAL
RSI: $rsi
Trend: $trend

📊 FUNDAMENTAL
PER: $per
PBV: $pbv
ROE: $roe

💰 NILAI WAJAR
Rp $hargawajar

🧠 KESIMPULAN
$kesimpulan
";

    return $hasil;
}