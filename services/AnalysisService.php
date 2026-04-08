<?php
require_once __DIR__ . "/ScoringService.php";
require_once __DIR__ . "/StockService.php";
require_once __DIR__ . "/DataService.php";

class AnalysisService{
function analisisTeknikal($harga, $rsi, $ma20, $ma50, $support, $resistance, $volume_signal) {

    if ($harga > $ma20 && $ma20 > $ma50) {
        $trend = "Uptrend kuat ✅";
    } elseif ($harga < $ma20 && $ma20 < $ma50) {
        $trend = "Downtrend ❌";
    } else {
        $trend = "Sideways ⚖️";
    }

    if ($rsi < 30)      $rsi_text = "Oversold → potensi rebound";
    elseif ($rsi < 50)  $rsi_text = "Momentum lemah";
    elseif ($rsi < 70)  $rsi_text = "Momentum cukup kuat";
    else                $rsi_text = "Overbought → rawan koreksi";

    if ($harga <= $support * 1.02)        $sr_text = "Dekat support → potensi pantulan";
    elseif ($harga >= $resistance * 0.98) $sr_text = "Dekat resistance → rawan turun";
    else                                   $sr_text = "Area netral";

    $vol_text = ($volume_signal == "high")
        ? "Didukung volume besar (valid)"
        : "Volume lemah (belum ada konfirmasi)";

    if ($trend == "Downtrend ❌" && $rsi < 30) {
        $summary = "Harga oversold dalam downtrend → potensi rebound tapi berisiko";
    } elseif ($trend == "Uptrend kuat ✅" && $volume_signal == "high") {
        $summary = "Trend naik kuat didukung volume → sinyal bullish";
    } elseif ($harga <= $support * 1.02) {
        $summary = "Harga dekat support → area menarik untuk akumulasi";
    } else {
        $summary = "Pergerakan masih normal";
    }

    return compact("trend", "rsi_text", "sr_text", "vol_text", "summary");
}

function analisisFundamental($per, $pbv, $roe, $eps, $der, $div_yield) {

    if ($eps === null || $eps == 0) {
        $eps_ket = "Data tidak tersedia";
    } else {
        $eps_ket = ($eps > 0)
            ? "Perusahaan menghasilkan laba positif"
            : "Perusahaan belum menghasilkan laba";
    }


    if ($per === null || $per <= 0) {
        $per_status = "Tidak tersedia";
        $per_ket = "Data PER tidak tersedia";
    } elseif ($per < 5) {
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

    if ($pbv === null || $pbv == 0) {
        $pbv_status = "Tidak tersedia";
        $pbv_ket = "Data PBV tidak tersedia";
    } elseif ($pbv < 1) {
        $pbv_status = "Murah ✅";
        $pbv_ket = "Harga di bawah nilai buku (undervalued)";
    } elseif ($pbv <= 2) {
        $pbv_status = "Wajar";
        $pbv_ket = "Masih dalam batas normal";
    } else {
        $pbv_status = "Tinggi ❌";
        $pbv_ket = "Harga cukup mahal dibanding nilai aset";
    }

    if ($roe === null || $roe == 0) {
        $roe_status = "Tidak tersedia";
        $roe_ket = "Data ROE tidak tersedia";
        $roe_percent = 0;
    } else {
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
    }

    if ($der === null || $der == 0) {
        $der_status = "Tidak tersedia";
        $der_ket = "Data DER tidak tersedia";
        $der_display = "-";
    } elseif ($der < 1) {
        $der_status = "Sehat ✅";
        $der_ket = "Struktur modal aman, utang rendah";
        $der_display = $der;
    } elseif ($der <= 2) {
        $der_status = "Cukup";
        $der_ket = "Utang masih dalam batas wajar";
        $der_display = $der;
    } else {
        $der_status = "Tinggi ❌";
        $der_ket = "Risiko utang tinggi, perlu hati-hati";
        $der_display = $der;
    }

    if ($div_yield === null || $div_yield == 0) {
        $div_status = "Tidak tersedia";
        $div_ket = "Data dividen tidak tersedia";
        $div_pct = 0;
    } else {
        $div_pct = $div_yield * 100;

        if ($div_yield < 0.03) {
            $div_status = "Rendah";
            $div_ket = "Yield kecil, fokus ekspansi/growth";
        } elseif ($div_yield <= 0.06) {
            $div_status = "Menarik ✅";
            $div_ket = "Yield sehat dan cukup stabil";
        } elseif ($div_yield <= 0.09) {
            $div_status = "Tinggi";
            $div_ket = "Yield menarik, masih batas wajar";
        } elseif ($div_yield <= 0.14) {
            $div_status = "Sangat Tinggi ⚠️";
            $div_ket = "Perlu cek keberlanjutan dividen";
        } else {
            $div_status = "Ekstrem 🚨";
            $div_ket = "Yield tidak wajar, waspadai anomali";
        }
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

function generateInsight($per, $roe, $rsi, $trend) {
    if ($per < 10 && $roe > 0.15)              return "Valuasi murah + profitabilitas tinggi → menarik";
    if ($trend == "Downtrend ❌" && $rsi < 30) return "Oversold di downtrend → potensi rebound";
    if ($trend == "Uptrend kuat ✅" && $rsi > 60) return "Trend naik kuat → momentum bullish";
    return "Kondisi relatif netral";
}

function generateNarasiAI($kode, $data) {
    $trend   = $data['trend'];
    $rsi     = $data['rsi'];
    $score   = $data['score'];
    $roe     = $data['roe'];
    $support = $data['support'];
    $harga   = $data['harga'];
    $per     = $data['per'];

    $narasi = "Saham $kode saat ini ";

    if ($trend == "Downtrend ❌")       $narasi .= "masih berada dalam tren turun, tekanan jual masih dominan. ";
    elseif ($trend == "Uptrend kuat ✅") $narasi .= "sedang bergerak dalam tren naik yang cukup solid. ";
    else                                 $narasi .= "sedang berada dalam fase konsolidasi. ";

    if ($roe > 0.15)     $narasi .= "Dari sisi fundamental, perusahaan memiliki profitabilitas yang kuat. ";
    elseif ($roe < 0.08) $narasi .= "Namun dari sisi fundamental, profitabilitas masih tergolong rendah. ";
    else                 $narasi .= "Fundamental perusahaan tergolong cukup stabil. ";

    if ($per > 0 && $per < 10) $narasi .= "Valuasi saat ini tergolong menarik dibanding kinerjanya. ";
    else                        $narasi .= "Valuasi saham saat ini cenderung tidak murah. ";

    if ($rsi < 30)       $narasi .= "Harga sudah di area jenuh jual, berpotensi rebound. ";
    elseif ($rsi > 70)   $narasi .= "Harga sudah cukup tinggi dan rawan koreksi. ";

    if ($harga <= $support * 1.02) $narasi .= "Posisi dekat support membuka peluang entry yang lebih aman. ";

    if ($score >= 75)    $narasi .= "Secara keseluruhan, saham ini menarik untuk diakumulasi secara bertahap.";
    elseif ($score >= 60) $narasi .= "Cukup menarik, namun tunggu konfirmasi sebelum masuk lebih besar.";
    else                  $narasi .= "Sebaiknya menunggu hingga kondisi menjadi lebih jelas.";

    return $narasi;
}

// =====================
// MAIN
// =====================
function analisisSaham($kode) {
    $dataService = new DataService();
    $data = $dataService->getData($kode);

    if (!$data || isset($data["error"])) {
        return "❌ Gagal mengambil data untuk saham $kode. Pastikan kode saham benar.";
    }
    $scoring = new ScoringService();

    $harga        = $data["price"]         ?? 0;
    $eps          = $data["eps"]           ?? 0;
    $per          = $data["per"]           ?? 0;
    $pbv          = $data["pbv"]           ?? 0;
    $roe          = $data["roe"]           ?? 0;
    $der          = $data["der"]           ?? 0;
    $div_yield    = $data["div_yield"]     ?? 0;
    $rsi          = $data["rsi"]           ?? 0;
    $ma20         = $data["ma20"]          ?? 0;
    $ma50         = $data["ma50"]          ?? 0;
    $support      = $data["support"]       ?? 0;
    $resistance   = $data["resistance"]    ?? 0;
    $volume_signal = $data["volume_signal"] ?? "low";

    $tek    = $this->analisisTeknikal($harga, $rsi, $ma20, $ma50, $support, $resistance, $volume_signal);
    $fund   = $this->analisisFundamental($per, $pbv, $roe, $eps, $der, $div_yield);
    $dec    = $scoring->hitungSAW($data); // ← fix: urutan benar, pakai ->
    $insight = $this->generateInsight($per, $roe, $rsi, $tek['trend']); // ← fix: 4 parameter
    $narasi  = $this->generateNarasiAI($kode, [
        "trend"   => $tek['trend'],
        "rsi"     => $rsi,
        "score"   => $dec['score'],
        "valuasi" => $dec['valuasi'],
        "roe"     => $roe,
        "harga"   => $harga,
        "support" => $support,
        "per"     => $per,
    ]);

    $der_tampil = is_numeric($fund['der_display'])
        ? round($fund['der_display'], 2)
        : $fund['der_display'];

    $risk_note = !empty($dec['notes'])
        ? "\n⚠️ Risiko: " . implode(", ", $dec['notes'])
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

Support: $support | Resistance: $resistance
→ {$tek['sr_text']}

Volume: $volume_signal
→ {$tek['vol_text']}

📌 Kesimpulan Teknikal
→ {$tek['summary']}

🎯 SCORE (SAW): {$dec['score']}/100
📌 REKOMENDASI: {$dec['rekom']}
$risk_note

🧠 INSIGHT
$insight

🗣️ NARASI
$narasi
";
}
}