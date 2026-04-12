<?php
error_reporting(E_ALL);
ini_set('log_errors', 1);
ini_set('error_log', 'C:/laragon/www/bot/storage/error.txt');

require_once "C:/laragon/www/bot/config/config.php";
require_once "C:/laragon/www/bot/services/StockService.php";
require_once "C:/laragon/www/bot/services/AnalysisService.php";
require_once "C:/laragon/www/bot/services/TelegramService.php";
require_once "C:/laragon/www/bot/services/ScoringService.php";

$update = json_decode(file_get_contents("php://input"), true);

if (!$update) exit("No data");

// ✅ Balas Telegram dulu agar tidak timeout & tidak retry
ob_start();
header("HTTP/1.1 200 OK");
header("Content-Type: application/json");
header("Connection: close");
echo json_encode(["ok" => true]); // ✅ kasih response 200 ke Telegram
$size = ob_get_length();
header("Content-Length: $size");
ob_end_flush();
flush();

// ✅ Lanjut proses di background setelah Telegram puas
$chat_id = $update["message"]["chat"]["id"] ?? null;
$text    = strtoupper(trim($update["message"]["text"] ?? ""));

file_put_contents("C:/laragon/www/bot/storage/log2.txt", "TEXT: $text" . PHP_EOL, FILE_APPEND);

// ... sisa kode webhook seperti biasa

// ======================
// MENU UTAMA
// ======================
function menuUtama($chat_id) {
    $keyboard = [
        "keyboard" => [
            ["Analisis Saham", "Ranking Sektor"],
            ["Ranking Indeks", "BEST TRADE"] // ✅ rapikan jadi 2 kolom
        ],
        "resize_keyboard" => true
    ];

    kirimPesan($chat_id, "🤖 STOCKMIND BOT\nPilih menu:", $keyboard);
}


// ======================
// BACK BUTTON
// ======================
if ($text == "⬅️ MENU") {
    menuUtama($chat_id);
    return;
}


// ======================
// START / HELP / MENU
// ======================
if (in_array($text, ["MENU", "HELP", "/START"])) { // ✅ lebih ringkas
    menuUtama($chat_id);
    return;
}


// ======================
// ANALISIS SAHAM (tombol menu)
// ======================
if ($text == "ANALISIS SAHAM") {
    kirimPesan($chat_id,
        "Ketik perintah berikut:\n\n" .
        "📌 Mode Investor:\nanalisis saham BBRI\n\n" .
        "📌 Mode Trader:\nanalisis saham BBRI trader",
        [
            "keyboard"        => [["⬅️ MENU"]],
            "resize_keyboard" => true
        ]
    );
    return;
}


// ======================
// ANALISIS SAHAM (input kode)
// ======================
if (strpos($text, "ANALISIS SAHAM") === 0) { // ✅ pastikan diawali "ANALISIS SAHAM", bukan hanya mengandung

    $input = explode(" ", $text);

    $kode = $input[2] ?? null;
    $mode = strtolower($input[3] ?? 'investor');

    // ✅ Validasi kode dulu SEBELUM memanggil service
    if (!$kode) {
        kirimPesan($chat_id,
            "❌ Format salah.\n\nContoh:\nanalisis saham BBRI\nanalisis saham BBRI trader",
            [
                "keyboard"        => [["⬅️ MENU"]],
                "resize_keyboard" => true
            ]
        );
        return;
    }

    if (!in_array($mode, ['investor', 'trader'])) {
        $mode = 'investor';
    }

    $analysis = new AnalysisService();
    $hasil    = $analysis->analisisSaham($kode, $mode); // ✅ baru dipanggil setelah validasi

    kirimPesan($chat_id, $hasil, [
        "keyboard"        => [["⬅️ MENU"]],
        "resize_keyboard" => true
    ]);
    return;
}


// ======================
// RANKING SEKTOR (tombol menu)
// ======================
if ($text == "RANKING SEKTOR") {
    $keyboard = [
        "keyboard" => [
            ["KEUANGAN",         "ENERGI"],
            ["BARANG BAKU",      "INDUSTRI"],
            ["INFRASTRUKTUR",    "KESEHATAN"],
            ["TEKNOLOGI",        "PROPERTI & REAL ESTATE"],
            ["KONSUMEN PRIMER",  "KONSUMEN NON-PRIMER"],
            ["TRANSPORTASI & LOGISTIK"],
            ["⬅️ MENU"]
        ],
        "resize_keyboard" => true
    ];

    kirimPesan($chat_id, "📊 Pilih sektor:", $keyboard);
    return;
}


// ======================
// HANDLE KLIK SEKTOR
// ======================
$daftarSektor = [
    "KEUANGAN",
    "ENERGI",
    "BARANG BAKU",
    "INDUSTRI",
    "INFRASTRUKTUR",
    "KESEHATAN",
    "TEKNOLOGI",
    "KONSUMEN PRIMER",
    "KONSUMEN NON-PRIMER",
    "PROPERTI & REAL ESTATE",
    "TRANSPORTASI & LOGISTIK"
];

if (in_array($text, $daftarSektor)) {
    $namaSektor = strtolower($text);

    $scoring  = new ScoringService();
    $ranking  = $scoring->rankingSektor($namaSektor);
    $response = $scoring->formatRanking($namaSektor, $ranking);

    kirimPesan($chat_id, $response, [
        "keyboard"        => [["⬅️ MENU"]],
        "resize_keyboard" => true
    ]);
    return;
}


// ======================
// RANKING INDEKS (tombol menu)
// ======================
if ($text == "RANKING INDEKS") {
    $keyboard = [
        "keyboard" => [
            ["LQ45",  "IDX30"],
            ["IDX80", "JII"],
            ["ISSI",  "ESG"],
            ["⬅️ MENU"]
        ],
        "resize_keyboard" => true
    ];

    kirimPesan($chat_id, "📊 Pilih indeks:", $keyboard);
    return;
}


// ======================
// HANDLE KLIK INDEKS
// ======================
$daftarIndeks = ["LQ45", "IDX30", "IDX80", "JII", "ISSI", "ESG"];

if (in_array($text, $daftarIndeks)) {
    $namaIndeks = strtolower($text);

    if ($namaIndeks == "esg") {
        $namaIndeks = "esg leaders";
    }

    $scoring  = new ScoringService();
    $ranking  = $scoring->rankingIndeks($namaIndeks);
    $response = $scoring->formatRanking("INDEKS " . strtoupper($namaIndeks), $ranking); // ✅ uppercase label

    kirimPesan($chat_id, $response, [
        "keyboard"        => [["⬅️ MENU"]],
        "resize_keyboard" => true
    ]);
    return;
}


// ======================
// BEST TRADE
// ======================
if ($text == "BEST TRADE") {
    $analysis = new AnalysisService();

     kirimPesan($chat_id, "⏳ Sedang menganalisis saham terbaik, mohon tunggu...");

    $analysis = new AnalysisService();
    $data     = $analysis->getBestTrade();
    $response = $analysis->formatBestTrade($data);

    kirimPesan($chat_id, $response, [
        "keyboard"        => [["⬅️ MENU"]],
        "resize_keyboard" => true
    ]);

    return;
}


// ======================
// FALLBACK
// ======================
kirimPesan($chat_id, "❌ Perintah tidak dikenali.\nKetik MENU untuk mulai.", [
    "keyboard"        => [["⬅️ MENU"]],
    "resize_keyboard" => true
]);