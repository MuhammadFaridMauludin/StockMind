<?php
require_once __DIR__ . "/../services/StockService.php";

function analisisTeknikal($harga, $rsi, $ma20, $ma50, $support, $resistance, $volume_signal) {
    $trend = ($harga > $ma50) ? "Uptrend ✅" : "Downtrend ❌";

    // TREND
    if ($harga > $ma20 && $ma20 > $ma50) {
        $trend = "Uptrend kuat ✅";
    } elseif ($harga < $ma20 && $ma20 < $ma50) {
        $trend = "Downtrend ❌";
    } else {
        $trend = "Sideways ⚖️";
    }
        // RSI INTERPRETASI
    if ($rsi < 30) {
        $rsi_text = "Oversold → potensi rebound";
    } elseif ($rsi < 50) {
        $rsi_text = "Momentum lemah";
    } elseif ($rsi < 70) {
        $rsi_text = "Momentum cukup kuat";
    } else {
        $rsi_text = "Overbought → rawan koreksi";
    }
    // SUPPORT RESISTANCE
    if ($harga <= $support * 1.02) {
        $sr_text = "Dekat support → potensi pantulan";
    } elseif ($harga >= $resistance * 0.98) {
        $sr_text = "Dekat resistance → rawan turun";
    } else {
        $sr_text = "Area netral";
    }
    if ($volume_signal == "high") {
        $vol_text = "Didukung volume besar (valid)";
    } else {
        $vol_text = "Volume lemah (belum ada konfirmasi)";
    }
        // SUMMARY LOGIC
    if ($trend == "Downtrend ❌" && $rsi < 30) {
        $summary = "Harga oversold dalam downtrend → potensi rebound tapi berisiko";
    } elseif ($trend == "Uptrend kuat ✅" && $volume_signal == "high") {
        $summary = "Trend naik kuat didukung volume → sinyal bullish";
    } elseif ($harga <= $support * 1.02) {
        $summary = "Harga dekat support → area menarik untuk akumulasi";
    } else {
        $summary = "Pergerakan masih normal";
    }

    return [
        "trend" => $trend,
        "rsi_text" => $rsi_text,
        "sr_text" => $sr_text,
        "volume_text" => $vol_text,
        "summary" => $summary
    ];
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
    if (empty($der) || $der == 0) {
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

function analisisKeputusan($harga, $eps, $per, $roe, $der, $div_yield, $rsi, $ma20, $ma50, $support, $volume_signal) {

    $score = 50; // base netral
    $notes = [];

    // ======================
    // FUNDAMENTAL (max +30)
    // ======================

    // PER
    if ($per > 0 && $per < 10) {
        $score += 10;
    } elseif ($per < 20) {
        $score += 6;
    } elseif ($per > 40) {
        $score -= 8;
        $notes[] = "Valuasi mahal";
    }

    // ROE
    if ($roe > 0.18) {
        $score += 12;
    } elseif ($roe > 0.12) {
        $score += 8;
    } elseif ($roe < 0.08) {
        $score -= 8;
        $notes[] = "Profitabilitas rendah";
    }

    // DER
    if ($der > 0) {
        if ($der < 1) {
            $score += 5;
        } elseif ($der > 2) {
            $score -= 6;
            $notes[] = "Utang tinggi";
        }
    }

    // DIVIDEN
    if ($div_yield >= 0.02 && $div_yield <= 0.06) {
        $score += 4;
    }

    // ======================
    // TEKNIKAL (max +30)
    // ======================

    // Trend
    if ($harga > $ma20 && $ma20 > $ma50) {
        $score += 12;
    } elseif ($harga < $ma20 && $ma20 < $ma50) {
        $score -= 10;
        $notes[] = "Masih downtrend";
    }

    // RSI
    if ($rsi < 30) {
        $score += 8;
    } elseif ($rsi > 70) {
        $score -= 6;
        $notes[] = "Jenuh beli";
    }

    // Support proximity
    if ($harga <= $support * 1.02) {
        $score += 6;
    }

    // Volume
    if ($volume_signal == "high") {
        $score += 4;
    }

    // ======================
    // NORMALISASI
    // ======================
    if ($score > 100) $score = 100;
    if ($score < 0)   $score = 0;

    // ======================
    // REKOMENDASI
    // ======================
    if ($score >= 85) {
        $rekom = "Strong Buy 🚀";
    } elseif ($score >= 70) {
        $rekom = "Buy 👍";
    } elseif ($score >= 55) {
        $rekom = "Buy on Weakness";
    } elseif ($score >= 40) {
        $rekom = "Wait & See";
    } else {
        $rekom = "Avoid ⚠️";
    }
    $hargawajar = ($eps > 0) ? $eps * 8 : 0;

    if ($harga > 0 && $eps > 0) {
        $valuasi = ($harga < $hargawajar)
            ? "Undervalued ✅"
            : "Overvalued ❌";
    } else {
        $valuasi = "Tidak diketahui";
    }

    return [
        "score" => $score,
        "rekom" => $rekom,
        "notes" => $notes,
        "hargawajar"=>$hargawajar,
        "valuasi"=>$valuasi
    ];
}

function generateInsight($per, $roe, $rsi, $trend) {

    if ($per < 10 && $roe > 0.15) {
        return "Valuasi murah + profitabilitas tinggi → menarik";
    }

    if ($trend == "Downtrend ❌" && $rsi < 30) {
        return "Oversold di downtrend → potensi rebound";
    }

    if ($trend == "Uptrend kuat ✅" && $rsi > 60) {
        return "Trend naik kuat → momentum bullish";
    }

    return "Kondisi relatif netral";
}

function generateNarasiAI($kode, $data) {

    $trend = $data['trend'];
    $rsi   = $data['rsi'];
    $score = $data['score'];
    $valuasi = $data['valuasi'];
    $roe = $data['roe'];
    $support = $data['support'];
    $harga = $data['harga'];

    $narasi = "Saham $kode saat ini ";

    // ======================
    // 1. KONDISI TEKNIKAL
    // ======================
    if ($trend == "Downtrend ❌") {
        $narasi .= "masih berada dalam tren turun sehingga tekanan jual masih cukup dominan. ";
    } elseif ($trend == "Uptrend kuat ✅") {
        $narasi .= "sedang bergerak dalam tren naik yang cukup solid. ";
    } else {
        $narasi .= "sedang berada dalam fase konsolidasi. ";
    }

    // ======================
    // 2. FUNDAMENTAL
    // ======================
    if ($roe > 0.15) {
        $narasi .= "Dari sisi fundamental, perusahaan memiliki profitabilitas yang kuat. ";
    } elseif ($roe < 0.08) {
        $narasi .= "Namun dari sisi fundamental, profitabilitas masih tergolong rendah. ";
    } else {
        $narasi .= "Fundamental perusahaan tergolong cukup stabil. ";
    }

    // ======================
    // 3. VALUASI
    // ======================
    if ($data['per'] > 0 && $data['per'] < 10)  {
        $narasi .= "Valuasi saat ini tergolong menarik dibanding kinerjanya. ";
    } else {
        $narasi .= "Valuasi saham saat ini cenderung tidak murah. ";
    }

    // ======================
    // 4. TIMING (RSI + SUPPORT)
    // ======================
    if ($rsi < 30) {
        $narasi .= "Secara teknikal, harga sudah berada di area jenuh jual sehingga berpotensi terjadi rebound. ";
    } elseif ($rsi > 70) {
        $narasi .= "Saat ini harga sudah cukup tinggi dan rawan mengalami koreksi. ";
    }

    if ($harga <= $support * 1.02) {
        $narasi .= "Posisi harga yang dekat dengan area support juga membuka peluang entry yang lebih aman. ";
    }

    // ======================
    // 5. PENUTUP (AKSI)
    // ======================
    if ($score >= 75) {
        $narasi .= "Secara keseluruhan, saham ini menarik untuk mulai diakumulasi secara bertahap.";
    } elseif ($score >= 60) {
        $narasi .= "Saham ini cukup menarik, namun sebaiknya menunggu konfirmasi tambahan sebelum masuk lebih besar.";
    } else {
        $narasi .= "Untuk saat ini, sebaiknya menunggu hingga kondisi menjadi lebih jelas.";
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
    $ma20 = $data["ma20"] ?? 0;
    $ma50 = $data["ma50"] ?? 0;
    $support = $data["support"] ?? 0;
    $volume_signal = $data["volume_signal"] ?? "low";

    $tek    = analisisTeknikal($harga, $rsi, $data["ma20"] ?? 0, $data["ma50"] ?? 0, $data["support"] ?? 0, $data["resistance"] ?? 0, $data["volume_signal"] ?? "low");
    $fund   = analisisFundamental($per, $pbv, $roe, $eps, $der, $div_yield);
    $dec    = analisisKeputusan($harga, $eps, $per, $roe, $der, $div_yield, $rsi, $ma20, $ma50, $support, $volume_signal);
    $insight = generateInsight($per, $roe, $rsi, $harga, $ma50);
    $narasi = generateNarasiAI($kode, [
    "trend" => $tek['trend'],
    "rsi" => $rsi,
    "score" => $dec['score'],
    "valuasi" => $dec['valuasi'],
    "roe" => $roe,
    "harga" => $harga,
    "support" => $data['support'],
    "per" => $per,
    "notes" => $dec['notes'] ?? []
]);

    $der_tampil = is_numeric($fund['der_display'])
        ? round($fund['der_display'], 2)
        : $fund['der_display'];

    $risk_note = !empty($dec['notes'])
    ? "⚠️ Risiko: " . implode(", ", $dec['notes'])
    : "";

    return "
📊 ANALISIS SAHAM: $kode

💰 Harga Sekarang: Rp $harga

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

💰 Harga Wajar
Rp {$dec['hargawajar']} ({$dec['valuasi']})

📉 TEKNIKAL
RSI: " . round($rsi, 2) . "
→ {$tek['rsi_text']}

Trend: {$tek['trend']}

Support: {$data['support']} | Resistance: {$data['resistance']}
→ {$tek['sr_text']}

Volume: {$data['volume_signal']}
→ {$tek['volume_text']}

📌 Kesimpulan Teknikal
→ {$tek['summary']}

🎯 SCORE: {$dec['score']}/100
📌 REKOMENDASI: {$dec['rekom']}
$risk_note

🧠 INSIGHT
$insight

🗣️ NARASI
$narasi


";
}