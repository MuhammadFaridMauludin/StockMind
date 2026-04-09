<?php
error_reporting(E_ALL);
ini_set('log_errors', 1);
ini_set('error_log', 'C:/laragon/www/bot/storage/error.txt');

require_once "C:/laragon/www/bot/config/config.php";
require_once "C:/laragon/www/bot/services/StockService.php";
require_once "C:/laragon/www/bot/services/AnalysisService.php";
require_once "C:/laragon/www/bot/services/TelegramService.php";

$update = json_decode(file_get_contents("php://input"), true);

if (!$update) exit("No data");

$chat_id = $update["message"]["chat"]["id"] ?? null;
$text = strtoupper(trim($update["message"]["text"] ?? ""));

file_put_contents("C:/laragon/www/bot/storage/log2.txt", "TEXT: $text" . PHP_EOL, FILE_APPEND);


// ======================
// MENU UTAMA
// ======================
function menuUtama($chat_id) {
    $keyboard = [
        "keyboard" => [
            ["Analisis Saham", "Ranking Sektor"],
            ["Ranking Indeks"]
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
if ($text == "MENU" || $text == "HELP" || $text == "/START") {
    menuUtama($chat_id);
    return;
}


// ======================
// ANALISIS SAHAM
// ======================
if ($text == "ANALISIS SAHAM") {
    kirimPesan($chat_id, "Ketik:\nanalisis saham bbri", [
        "keyboard" => [["⬅️ MENU"]],
        "resize_keyboard" => true
    ]);
    return;
}

if (strpos($text, "ANALISIS SAHAM") !== false) {

    $kode = trim(str_replace("ANALISIS SAHAM", "", $text));

    $analysis = new AnalysisService();
    $hasil = $analysis->analisisSaham($kode);

    kirimPesan($chat_id, $hasil, [
        "keyboard" => [["⬅️ MENU"]],
        "resize_keyboard" => true
    ]);
    return;
}


// ======================
// RANKING SEKTOR (TOMBOL)
// ======================
if ($text == "RANKING SEKTOR") {

    $keyboard = [
    "keyboard" => [
        ["KEUANGAN", "ENERGI"],
        ["BARANG BAKU", "INDUSTRI"],
        ["INFRASTRUKTUR", "KESEHATAN"],
        ["TEKNOLOGI"],
        ["KONSUMEN PRIMER", "KONSUMEN NON-PRIMER"],
        ["PROPERTI & REAL ESTATE"],
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

    $scoring = new ScoringService();
    $ranking = $scoring->rankingSektor($namaSektor);
    $response = $scoring->formatRanking($namaSektor, $ranking);

    kirimPesan($chat_id, $response, [
        "keyboard" => [["⬅️ MENU"]],
        "resize_keyboard" => true
    ]);
    return;
}


// ======================
// RANKING INDEKS (TOMBOL)
// ======================
if ($text == "RANKING INDEKS") {

    $keyboard = [
        "keyboard" => [
            ["LQ45", "IDX30"],
            ["IDX80", "JII"],
            ["ISSI", "ESG"],
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

    $scoring = new ScoringService();
    $ranking = $scoring->rankingIndeks($namaIndeks);
    $response = $scoring->formatRanking("INDEKS " . $namaIndeks, $ranking);

    kirimPesan($chat_id, $response, [
        "keyboard" => [["⬅️ MENU"]],
        "resize_keyboard" => true
    ]);
    return;
}


// ======================
// FALLBACK
// ======================
kirimPesan($chat_id, "❌ Perintah tidak dikenali.\nKetik MENU untuk mulai.", [
    "keyboard" => [["⬅️ MENU"]],
    "resize_keyboard" => true
]);