<?php
require "config.php";
require "api.php";

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
    file_put_contents("response.txt", $result);
    }

# =====================
# TEKNIKAL
# =====================
function analisisTeknikal($harga, $rsi, $ma50) {

    $trend = ($harga > $ma50) ? "Uptrend ✅" : "Downtrend ❌";

    if ($rsi < 30 && $harga > $ma50) {
        $summary = "Momentum naik mulai terbentuk (potensi reversal)";
    } elseif ($rsi > 70) {
        $summary = "Harga jenuh beli, rawan koreksi";
    } else {
        $summary = "Pergerakan masih normal";
    }

    return [
        "trend" => $trend,
        "summary" => $summary
    ];
}

# =====================
# FUNDAMENTAL
# =====================
function analisisFundamental($per, $pbv, $roe, $eps) {

    // PER
    if ($per < 5 && $per > 0) {
        $per_status = "Sangat murah 🟢";
        $per_ket = "Valuasi sangat rendah, bisa undervalued atau ada risiko bisnis";
    } elseif ($per < 10) {
        $per_status = "Murah ✅";
        $per_ket = "Valuasi lebih rendah dari rata-rata pasar";
    } elseif ($per <= 20) {
        $per_status = "Wajar";
        $per_ket = "Harga sesuai dengan kinerja perusahaan";
    } else {
        $per_status = "Mahal ❌";
        $per_ket = "Harga relatif tinggi dibanding laba";
    }

    // PBV 
    if ($pbv < 1) {
        $pbv_status = "Murah ✅";
        $pbv_ket = "Harga di bawah nilai buku (undervalued)";
    } elseif ($pbv <= 2) {
        $pbv_status = "Wajar";
        $pbv_ket = "Masih dalam batas normal";
    } else {
        $pbv_status = "Tinggi ❌";
        $pbv_ket = "Harga cukup mahal dibanding nilai aset";
    }

    // ROE
    $roe_percent = $roe * 100;

    if ($roe > 0.15) {
        $roe_status = "Bagus ✅";
        $roe_ket = "Perusahaan efisien menghasilkan laba";
    } elseif ($roe > 0.08) {
        $roe_status = "Cukup";
        $roe_ket = "Profitabilitas cukup stabil";
    } else {
        $roe_status = "Kurang ❌";
        $roe_ket = "Efisiensi menghasilkan laba masih rendah";
    }

    // EPS
    $eps_ket = ($eps > 0)
        ? "Perusahaan menghasilkan laba positif"
        : "Perusahaan belum menghasilkan laba";

    return compact(
        "per_status", "per_ket",
        "pbv_status", "pbv_ket",
        "roe_status", "roe_ket",
        "roe_percent",
        "eps_ket"
    );
}

# =====================
# VALUASI & SCORE
# =====================
function analisisKeputusan($harga, $eps, $per, $roe, $rsi, $ma50) {

    $hargawajar = ($eps > 0) ? $eps * 8 : 0;

    $valuasi = ($harga > 0 && $eps > 0)
        ? ($harga < $hargawajar ? "Undervalued ✅" : "Overvalued ❌")
        : "Tidak diketahui";

    $score = 0;
    $turnaround_note = "";

    // =====================
    // FUNDAMENTAL (PRIORITAS)
    // =====================
    if ($per < 10) {
        $score += 35;
    } elseif ($per < 20) {
        $score += 25;
    } elseif ($per < 50) {
        $score += 15;
    }

    if ($roe > 0.15) {
        $score += 35;
    } elseif ($roe > 0.08) {
        $score += 20;
    } elseif ($roe > 0) {
        $score += 10;
    }

    // Bonus kualitas tinggi
    if ($roe > 0.18) {
        $score += 10;
    }

    // =====================
    // TEKNIKAL (PENDUKUNG)
    // =====================
    if ($rsi < 30) {
        $score += 10;
    } elseif ($rsi < 50) {
        $score += 5;
    }

    if ($harga > $ma50) {
        $score += 15;
    } else {
        $score -= 5;
    }

    // =====================
    // TURNAROUND
    // =====================
    if ($eps > 0 && $roe < 0.08) {
        $turnaround_note = "Perusahaan dalam fase pemulihan (turnaround)";
        $score += 10;
    }

    // =====================
    // BATAS SCORE (biar rapi)
    // =====================
    if ($score > 100) $score = 100;
    if ($score < 0) $score = 0;

    // =====================
    // REKOMENDASI
    // =====================
    if ($score >= 80) {
        $rekom = "Strong Buy 🚀";
    } elseif ($score >= 60) {
        $rekom = "Buy 👍";
    } elseif ($score >= 40) {
        $rekom = "Hold 🤝";
    } elseif ($score >= 20) {
        $rekom = "Speculative ⚠️";
    } else {
        $rekom = "High Risk ⚠️";
    }

    return compact("hargawajar", "valuasi", "score", "rekom", "turnaround_note");
}
function generateInsight($per, $roe, $rsi, $harga, $ma50) {

    if ($per < 10 && $roe > 0.15) {
        return "Valuasi rendah dengan profitabilitas tinggi → kombinasi sangat menarik";
    }

    if ($per < 10 && $roe < 0.1) {
        return "Valuasi murah namun kualitas laba rendah → berpotensi value trap";
    }

    if ($roe > 0.15 && $harga > $ma50) {
    return "Fundamental kuat didukung tren naik → sinyal bullish kuat";
}

    if ($rsi < 30 && $harga < $ma50) {
        return "Harga sedang oversold dalam tren turun → potensi rebound namun berisiko";
    }

    if ($rsi < 30 && $harga > $ma50) {
        return "Oversold dalam tren naik → peluang entry menarik";
    }

    return "Kondisi saham relatif netral";
}

function generateNarasi($kode, $valuasi, $roe, $score) {

    $narasi = "Saham $kode saat ini ";

    if ($valuasi == "Undervalued ✅") {
        $narasi .= "memiliki valuasi yang menarik";
    } else {
        $narasi .= "cenderung berada pada valuasi tinggi";
    }
    $narasi .= ", dan saham ini ";

    if ($roe > 0.15) {
        $narasi .= " dengan kinerja profitabilitas yang kuat";
    } elseif ($roe > 0.08) {
        $narasi .= " dengan profitabilitas yang cukup stabil";
    } else {
        $narasi .= " namun profitabilitas masih rendah";
    }

    if ($score >= 80) {
        $narasi .= ". Secara keseluruhan saham ini menarik untuk dipertimbangkan.";
    } elseif ($score >= 60) {
        $narasi .= ". Saham ini cukup menarik namun perlu memperhatikan timing.";
    } else {
        $narasi .= ". Saham ini masih berisiko dan perlu kehati-hatian.";
    }

    return $narasi;
}
# =====================
# MAIN
# =====================
function analisisSaham($kode) {

    $data = getDataSaham($kode);
    $harga = $data["price"] ?? 0;
    $rsi = $data["rsi"] ?? 0;
    $ma50 = $data["ma50"] ?? 0;
    $per = $data["per"] ?? 0;
    $pbv = $data["pbv"] ?? 0;
    $roe = $data["roe"] ?? 0;
    $eps = $data["eps"] ?? 0;

    $tek = analisisTeknikal($harga, $rsi, $ma50);
    $fund = analisisFundamental($per, $pbv, $roe, $eps);
    $dec = analisisKeputusan($harga, $eps, $per, $roe, $rsi, $ma50);
    $insight = generateInsight($per, $roe, $rsi, $harga, $ma50);
    $narasi = generateNarasi($kode, $dec['valuasi'], $roe, $dec['score']);
    return "

📊 ANALISIS SAHAM: $kode

💰 Harga: $harga

📉 TEKNIKAL
RSI: " . round($rsi,2) . "
Trend: {$tek['trend']}
→ {$tek['summary']}

📊 FUNDAMENTAL
PER: " . round($per,2) . " ({$fund['per_status']})
→ {$fund['per_ket']}

PBV: " . round($pbv,2) . " ({$fund['pbv_status']})
→ {$fund['pbv_ket']}

ROE: " . round($fund['roe_percent'],2) . "% ({$fund['roe_status']})
→ {$fund['roe_ket']}

EPS: " . round($eps,2) . "
→ {$fund['eps_ket']}

💰 NILAI WAJAR
Rp {$dec['hargawajar']} ({$dec['valuasi']})

🎯 SCORE: {$dec['score']}/100
📌 REKOMENDASI: {$dec['rekom']}

🧠 INSIGHT
$insight

🗣️ NARASI
$narasi

"
;


}