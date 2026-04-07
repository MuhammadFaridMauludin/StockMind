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

function analisisTeknikal($harga, $rsi, $ma50) {
    $trend = ($harga > $ma50) ? "Uptrend ✅" : "Downtrend ❌";

    if ($rsi < 30 && $harga > $ma50) {
        $summary = "Momentum naik mulai terbentuk (potensi reversal)";
    } elseif ($rsi > 70) {
        $summary = "Harga jenuh beli, rawan koreksi";
    } else {
        $summary = "Pergerakan masih normal";
    }

    return ["trend" => $trend, "summary" => $summary];
}

function analisisFundamental($per, $pbv, $roe, $eps, $der, $div_yield) {

    // EPS
    $eps_ket = ($eps > 0)
        ? "Perusahaan menghasilkan laba positif"
        : "Perusahaan belum menghasilkan laba";

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

    // DER
    if ($der === null || $der === 0) {
        $der_status = "Tidak tersedia";
        $der_ket = "Data DER tidak tersedia";
        $der_display = "-";
    } else {
        $der_display = $der;
        if ($der < 1) {
            $der_status = "Sehat ✅";
            $der_ket = "Struktur modal aman, utang rendah";
        } elseif ($der <= 2) {
            $der_status = "Cukup";
            $der_ket = "Utang masih dalam batas wajar";
        } else {
            $der_status = "Tinggi ❌";
            $der_ket = "Risiko utang tinggi, perlu hati-hati";
        }
    }
    // DIVIDEND YIELD
    $div_pct = $div_yield * 100;

    if ($div_yield == 0) {
        $div_status = "Tidak ada dividen";
        $div_ket = "Perusahaan tidak membagikan dividen (fokus pada pertumbuhan)";
    } elseif ($div_yield < 0.03) {
        $div_status = "Rendah";
        $div_ket = "Yield kecil, biasanya perusahaan fokus pada ekspansi/growth";
    } elseif ($div_yield <= 0.06) {
        $div_status = "Menarik ✅";
        $div_ket = "Yield sehat dan cukup stabil, cocok untuk kombinasi income & growth";
    } elseif ($div_yield <= 0.09) {
        $div_status = "Tinggi";
        $div_ket = "Yield cukup tinggi dan menarik, masih dalam batas wajar";
    } elseif ($div_yield <= 0.14) {
        $div_status = "Sangat Tinggi ⚠️";
        $div_ket = "Yield tinggi, perlu cek keberlanjutan dividen";
    } else {
        $div_status = "Ekstrem 🚨";
        $div_ket = "Yield tidak wajar, kemungkinan ada anomali data atau risiko tinggi";
    }

    return compact(
        "eps_ket",
        "per_status", "per_ket",
        "pbv_status", "pbv_ket",
        "roe_status", "roe_ket", "roe_percent",
        "der_status", "der_ket", "der_display",
        "div_status", "div_ket", "div_pct"
    );
}

function analisisKeputusan($harga, $eps, $per, $roe, $der, $div_yield, $rsi, $ma50) {

    $hargawajar = ($eps > 0) ? $eps * 8 : 0;

    $valuasi = ($harga > 0 && $eps > 0)
        ? ($harga < $hargawajar ? "Undervalued ✅" : "Overvalued ❌")
        : "Tidak diketahui";

    $score = 0;
    $turnaround_note = "";

    // FUNDAMENTAL
    if ($per < 10) {
        $score += 35;
    } elseif ($per < 20) {
        $score += 25;
    } elseif ($per < 50) {
        $score += 15;
    }

    if ($roe > 0.18) {
        $score += 45;
    } elseif ($roe > 0.15) {
        $score += 35;
    } elseif ($roe > 0.08) {
        $score += 20;
    } elseif ($roe > 0) {
        $score += 10;
    }

    // DER
    if ($der !== null && $der > 0) {
        if ($der < 1) {
            $score += 15;
        } elseif ($der < 2) {
            $score += 5;
        } else {
            $score -= 10;
        }
    }
    if ($div_yield >= 0.02 && $div_yield <= 0.05) {
    $score += 10; // zona ideal
    } elseif ($div_yield > 0.05) {
        $score += 3;  // tinggi tapi rawan
    } elseif ($div_yield > 0) {
        $score += 5;  // ada dividen tapi kecil
    }

    // TEKNIKAL
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

    // TURNAROUND
    if ($eps > 0 && $roe < 0.08) {
        $turnaround_note = "Perusahaan dalam fase pemulihan (turnaround)";
        $score += 10;
    }

    if ($score > 100) $score = 100;
    if ($score < 0)   $score = 0;

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
    if ($per < 10 && $roe >= 0.08) {
        return "Valuasi murah dengan profitabilitas cukup → saham menarik untuk dipertimbangkan";  // ← tambahkan
    }
    if ($per < 10 && $roe < 0.08) {
        return "Valuasi murah namun kualitas laba rendah → berpotensi value trap";
    }
    if ($roe > 0.15 && $harga > $ma50) {
        return "Fundamental kuat didukung tren naik → sinyal bullish kuat";
    }
    if ($rsi > 70 && $harga > $ma50) {
        return "Harga jenuh beli dalam tren naik → pertimbangkan tunggu koreksi";  // ← tambahkan untuk kasus INDF
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

function analisisSaham($kode) {
    $data = getDataSaham($kode);

    if (!$data || isset($data["error"])) {
        return "❌ Gagal mengambil data untuk saham $kode. Pastikan kode saham benar.";
    }

    $harga = $data["price"] ?? 0;
    $eps   = $data["eps"]   ?? 0;
    $per   = $data["per"]   ?? 0;
    $pbv   = $data["pbv"]   ?? 0;
    $roe   = $data["roe"]   ?? 0;
    $der   = $data["der"]   ?? 0;
    $div_yield = $data["div_yield"] ?? 0;
    $rsi   = $data["rsi"]   ?? 0;
    $ma50  = $data["ma50"]  ?? 0;

    $tek    = analisisTeknikal($harga, $rsi, $ma50);
    $fund   = analisisFundamental($per, $pbv, $roe, $eps, $der, $div_yield);
    $dec    = analisisKeputusan($harga, $eps, $per, $roe, $der, $div_yield, $rsi, $ma50);
    $insight = generateInsight($per, $roe, $rsi, $harga, $ma50);
    $narasi  = generateNarasi($kode, $dec['valuasi'], $roe, $dec['score']);

    // fix: der_display bisa "-" jadi tidak pakai round()
    $der_tampil = is_numeric($fund['der_display'])
        ? round($fund['der_display'], 2)
        : $fund['der_display'];

    return "
📊 ANALISIS SAHAM: $kode

💰 Harga: Rp $harga

📉 TEKNIKAL
RSI: " . round($rsi, 2) . "
Trend: {$tek['trend']}
→ {$tek['summary']}

📊 FUNDAMENTAL
EPS: " . round($eps, 2) . "
→ {$fund['eps_ket']}

PER: " . round($per, 2) . " ({$fund['per_status']})
→ {$fund['per_ket']}

PBV: " . round($pbv, 2) . " ({$fund['pbv_status']})
→ {$fund['pbv_ket']}

ROE: " . round($fund['roe_percent'], 2) . "% ({$fund['roe_status']})
→ {$fund['roe_ket']}

DER: $der_tampil ({$fund['der_status']})
→ {$fund['der_ket']}

DIV YIELD: " . round($fund['div_pct'], 2) . "% ({$fund['div_status']})
→ {$fund['div_ket']}

💰 NILAI WAJAR
Rp {$dec['hargawajar']} ({$dec['valuasi']})

🎯 SCORE: {$dec['score']}/100
📌 REKOMENDASI: {$dec['rekom']}

🧠 INSIGHT
$insight

🗣️ NARASI
$narasi
";
}