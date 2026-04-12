<?php
require_once __DIR__ . "/ScoringService.php";
require_once __DIR__ . "/StockService.php";
require_once __DIR__ . "/DataService.php";

class AnalysisService {

    private DataService $dataService; // ✅ Fix bug #2

    public function __construct() {
        $this->dataService = new DataService();
    }

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

        if ($resistance <= 0 || $support <= 0) {
            $sr_text = "Data support/resistance tidak tersedia";
        } elseif ($harga <= $support * 1.02) {
            $sr_text = "Dekat support → potensi pantulan";
        } elseif ($harga >= $resistance * 0.98) {
            $sr_text = "Dekat resistance → rawan turun";
        } else {
            $sr_text = "Area netral";
        }

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

        $eps_ket = ($eps === null || $eps == 0)
            ? "Data tidak tersedia"
            : ($eps > 0 ? "Perusahaan menghasilkan laba positif" : "Perusahaan belum menghasilkan laba");

        if ($per === null || $per <= 0) {
            $per_status = "Tidak tersedia";
            $per_ket    = "Data PER tidak tersedia";
        } elseif ($per < 5) {
            $per_status = "Sangat murah 🟢";
            $per_ket    = "Valuasi sangat rendah, bisa undervalued atau ada risiko bisnis";
        } elseif ($per < 10) {
            $per_status = "Murah ✅";
            $per_ket    = "Valuasi lebih rendah dari rata-rata pasar";
        } elseif ($per <= 20) {
            $per_status = "Wajar";
            $per_ket    = "Harga sesuai dengan kinerja perusahaan";
        } else {
            $per_status = "Mahal ❌";
            $per_ket    = "Harga relatif tinggi dibanding laba";
        }

        if ($pbv === null || $pbv == 0) {
            $pbv_status = "Tidak tersedia";
            $pbv_ket    = "Data PBV tidak tersedia";
        } elseif ($pbv < 1) {
            $pbv_status = "Murah ✅";
            $pbv_ket    = "Harga di bawah nilai buku (undervalued)";
        } elseif ($pbv <= 2) {
            $pbv_status = "Wajar";
            $pbv_ket    = "Masih dalam batas normal";
        } else {
            $pbv_status = "Tinggi ❌";
            $pbv_ket    = "Harga cukup mahal dibanding nilai aset";
        }

        if ($roe === null || $roe == 0) {
            $roe_status  = "Tidak tersedia";
            $roe_ket     = "Data ROE tidak tersedia";
            $roe_percent = 0;
        } else {
            $roe_percent = $roe * 100;
            if ($roe > 0.15) {
                $roe_status = "Bagus ✅";
                $roe_ket    = "Perusahaan efisien menghasilkan laba";
            } elseif ($roe > 0.08) {
                $roe_status = "Cukup";
                $roe_ket    = "Profitabilitas cukup stabil";
            } else {
                $roe_status = "Kurang ❌";
                $roe_ket    = "Efisiensi menghasilkan laba masih rendah";
            }
        }

        if ($der === null || $der == 0) {
            $der_status  = "Tidak tersedia";
            $der_ket     = "Data DER tidak tersedia";
            $der_display = "-";
        } elseif ($der < 1) {
            $der_status  = "Sehat ✅";
            $der_ket     = "Struktur modal aman, utang rendah";
            $der_display = $der;
        } elseif ($der <= 2) {
            $der_status  = "Cukup";
            $der_ket     = "Utang masih dalam batas wajar";
            $der_display = $der;
        } else {
            $der_status  = "Tinggi ❌";
            $der_ket     = "Risiko utang tinggi, perlu hati-hati";
            $der_display = $der;
        }

        if ($div_yield === null || $div_yield == 0) {
            $div_status = "Tidak tersedia";
            $div_ket    = "Data dividen tidak tersedia";
            $div_pct    = 0;
        } else {
            $div_pct = $div_yield * 100;
            if ($div_yield < 0.03) {
                $div_status = "Rendah";
                $div_ket    = "Yield kecil, fokus ekspansi/growth";
            } elseif ($div_yield <= 0.06) {
                $div_status = "Menarik ✅";
                $div_ket    = "Yield sehat dan cukup stabil";
            } elseif ($div_yield <= 0.09) {
                $div_status = "Tinggi";
                $div_ket    = "Yield menarik, masih batas wajar";
            } elseif ($div_yield <= 0.14) {
                $div_status = "Sangat Tinggi ⚠️";
                $div_ket    = "Perlu cek keberlanjutan dividen";
            } else {
                $div_status = "Ekstrem 🚨";
                $div_ket    = "Yield tidak wajar, waspadai anomali";
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

    function generateInsight($per, $roe, $rsi, $trend, $ml) { // ✅ hapus $insight yang tidak terpakai
        $text = "";

        if ($per < 10 && $roe > 0.15)
            $text = "Valuasi murah + profitabilitas tinggi → menarik";
        elseif ($trend == "Downtrend ❌" && $rsi < 30)
            $text = "Oversold di downtrend → potensi rebound";
        elseif ($trend == "Uptrend kuat ✅" && $rsi > 60)
            $text = "Trend naik kuat → momentum bullish";
        else
            $text = "Kondisi relatif netral";

        if ($ml && $ml['confidence'] > 60) {
            $text .= "\n🤖 ML melihat potensi " . ($ml['pred'] ? "kenaikan" : "penurunan");
        }

        return $text;
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

        if ($rsi < 30)     $narasi .= "Harga sudah di area jenuh jual, berpotensi rebound. ";
        elseif ($rsi > 70) $narasi .= "Harga sudah cukup tinggi dan rawan koreksi. ";

        if ($harga <= $support * 1.02) $narasi .= "Posisi dekat support membuka peluang entry yang lebih aman. ";

        if ($score >= 75)     $narasi .= "Secara keseluruhan, saham ini menarik untuk diakumulasi secara bertahap.";
        elseif ($score >= 60) $narasi .= "Cukup menarik, namun tunggu konfirmasi sebelum masuk lebih besar.";
        else                  $narasi .= "Sebaiknya menunggu hingga kondisi menjadi lebih jelas.";

        return $narasi;
    }

    private function getMLPrediction($kode) {
        $cmd    = "\"C:/Users/Muhammad Farid M/AppData/Local/Programs/Python/Python311/python.exe\" C:/laragon/www/bot/python/predict.py $kode 2>&1";
        $output = shell_exec($cmd);

        file_put_contents("C:/laragon/www/bot/storage/log2.txt", "ML RAW: " . $output . PHP_EOL, FILE_APPEND);

        if (!$output) return null;

        $data = json_decode($output, true);

        if (!$data || isset($data['error'])) return null;

        return $data;
    }

    private function hitungATR($harga, $support, $resistance) {
        $range = abs($resistance - $support);
        return ($range <= 0) ? $harga * 0.03 : $range / 2;
    }

    // ✅ Fix bug #1: tambah parameter $ml
    private function formatOutputByMode($mode, $kode, $harga, $fund, $tek, $dec, $insight, $narasi, $support, $resistance, $volume_signal, $rsi, $ml) {

        if ($mode === 'trader') {

            // ✅ Tidak perlu fetch ulang, gunakan $ml yang sudah di-pass
            if ($ml) {
                if ($ml['pred'] == 0) {
                    $dec['rekom'] = ($ml['confidence'] >= 65) ? "SELL / AVOID ❌" : "WAIT ⚠️";
                } elseif ($ml['pred'] == 1) {
                    if ($ml['confidence'] >= 80 && $tek['trend'] != "Downtrend ❌") {
                        $dec['rekom'] = "STRONG BUY 🚀";
                    } elseif ($ml['confidence'] >= 65 && $tek['trend'] == "Downtrend ❌") {
                        $dec['rekom'] = "BUY (EARLY REVERSAL) ⚠️";
                    } elseif ($ml['confidence'] >= 60) {
                        $dec['rekom'] = "BUY (SPECULATIVE) 🤔";
                    }
                }
            }

            $confidenceLabel = $ml
                ? ($ml['confidence'] >= 80 ? "Sangat Tinggi 🔥" : ($ml['confidence'] >= 65 ? "Kuat 💪" : "Lemah ⚠️"))
                : "-";

            $mlText = "";
            if ($ml) {
                $arah   = ($ml['pred'] == 1) ? "Naik 📈" : "Turun 📉";
                $mlText = "\n🔮 PREDIKSI ML\nArah: $arah\nConfidence: {$ml['confidence']}%";
            }

            $targetText = "";
            if ($ml) {
                if ($ml['pred'] == 0) {
                    $targetText = "
⚠️ TIDAK DISARANKAN BUY

📉 Skenario:
- Potensi turun masih dominan
- Hindari entry

🎯 Strategi:
- Tunggu di area support: Rp $support
- Atau tunggu reversal signal
";
                } elseif ($ml['pred'] == 1 && $ml['confidence'] < 65) {
                    $targetText = "
⚠️ SINYAL LEMAH

- Confidence masih rendah ({$ml['confidence']}%)
- Sebaiknya tunggu konfirmasi tambahan
";
                } else {
                    $atr = $this->hitungATR($harga, $support, $resistance);
                    $tp1 = round($harga + $atr);
                    $tp2 = round($harga + ($atr * 2));
                    $sl  = round($harga - $atr);

                    $targetText = "
🎯 TARGET TRADING
Entry: Rp $harga
TP1  : Rp $tp1
TP2  : Rp $tp2
SL   : Rp $sl
";
                }
            }

            return "
📊 ANALISIS SAHAM (TRADER): $kode

💰 Harga: Rp $harga

📉 TEKNIKAL
RSI: " . round($rsi, 2) . "
→ {$tek['rsi_text']}

Trend: {$tek['trend']}

Support: $support | Resistance: $resistance
→ {$tek['sr_text']}

Volume: $volume_signal
→ {$tek['vol_text']}

📌 Kesimpulan
→ {$tek['summary']}

🎯 SCORE: {$dec['score']}
📌 REKOMENDASI: {$dec['rekom']}

🧠 INSIGHT
$insight

$mlText
$confidenceLabel
$targetText
";
        }

        // default investor
        return "
📊 ANALISIS SAHAM (INVESTOR): $kode

💰 Harga: Rp $harga

📊 FUNDAMENTAL
EPS: {$fund['eps_ket']}

PER: {$fund['per_status']}
→ {$fund['per_ket']}

ROE: {$fund['roe_status']}
→ {$fund['roe_ket']}

DIVIDEN: {$fund['div_status']}
→ {$fund['div_ket']}

💰 Harga Wajar
Rp {$dec['hargawajar']} ({$dec['valuasi']})

🎯 SCORE: {$dec['score']}
📌 REKOMENDASI: {$dec['rekom']}

🧠 INSIGHT
$insight

🗣️ NARASI
$narasi
";
    }

    public function getBestTrade() {
        $listSaham = ["BBRI", "BBCA", "BMRI", "TLKM", "ASII", "ADRO"];
        $results   = [];
        $scoring   = new ScoringService(); // ✅ Fix bug #3

        foreach ($listSaham as $kode) {
            $data = $this->dataService->getData($kode); // ✅ Fix bug #2
            if (!$data) continue;

            $harga = $data['price'];

            // ✅ Fix bug #4: pass parameter dengan benar
            $fund = $this->analisisFundamental(
                $data['per']    ?? 0,
                $data['pbv']    ?? 0,
                $data['roe']    ?? 0,
                $data['eps']    ?? 0,
                $data['der']    ?? 0,
                $data['div_yield'] ?? 0
            );

            $tek = $this->analisisTeknikal(
                $harga,
                $data['rsi']          ?? 0,
                $data['ma20']         ?? 0,
                $data['ma50']         ?? 0,
                $data['support']      ?? 0,
                $data['resistance']   ?? 0,
                $data['volume_signal'] ?? 'low'
            );

            $dec = $scoring->hitungSAW($data); // ✅ Fix bug #3
            $ml  = $this->getMLPrediction($kode);

            if (!$ml) continue;
            $atr = $this->hitungATR($harga, $data['support'], $data['resistance']);

$tp1 = round($harga + $atr);
$tp2 = round($harga + ($atr * 2));
$sl  = round($harga - $atr);
            $results[] = [
                "kode"       => $kode,
                "harga"      => $harga,
                "confidence" => $ml['confidence'],
                "score"      => $dec['score'],
                "pred" => $ml['pred'],
                "support" => $data['support'],
                "resistance" => $data['resistance'],
                "tp1" => $tp1,
                "tp2" => $tp2,
                "sl" => $sl
            ];
        }

        usort($results, function($a, $b){
            return($b['confidence'] + $b['score']) <=> ($a['confidence'] + $a['score']);
    });

        return array_slice($results, 0, 3);
    }

public function formatBestTrade($data) {

    if (empty($data)) {
        return "❌ Tidak ada peluang trading hari ini";
    }

    $text = "🔥 BEST TRADE HARI INI\n";

    foreach ($data as $i => $d) {

        $no = $i + 1;

        // STATUS
        if ($d['pred'] == 0) {
            $status = "⚠️ Hindari (Bearish)";
        }
        elseif ($d['confidence'] < 60) {
            $status = "⚠️ Lemah";
        }
        else {
            $status = "🔥 Potensial";
        }

        // LABEL
        if ($d['pred'] == 1) {

            $totalScore = $d['confidence'] + $d['score'];

            if ($d['confidence'] >= 75 && $d['score'] >= 70) {
                $label = "🚀 HIGH PROBABILITY";
            }
            elseif ($totalScore >= 130) {
                $label = "🔥 STRONG SETUP";
            }
            elseif ($totalScore >= 110) {
                $label = "👍 GOOD SETUP";
            }
            else {
                $label = "⚠️ SPECULATIVE";
            }

        } else {
            $label = "❌ NO SETUP";
        }

        // TARGET
        if ($d['pred'] == 1 && $d['confidence'] >= 65) {

            $target = "
Entry: Rp {$d['harga']}
TP1  : Rp {$d['tp1']}
TP2  : Rp {$d['tp2']}
SL   : Rp {$d['sl']}
";

        } else {

            $target = "
Entry: Rp {$d['harga']}
⚠️ Tidak ada setup trading
";
        }

        // RISK NOTE
        if ($d['confidence'] >= 75) {
            $riskNote = "💎 Confidence tinggi → bisa dipertimbangkan entry";
        }
        elseif ($d['confidence'] >= 65) {
            $riskNote = "⚖️ Setup cukup baik → tetap perhatikan risk";
        }
        else {
            $riskNote = "⚠️ Belum cukup kuat → sebaiknya tunggu";
        }

        // OUTPUT
        $text .= "\n$no. {$d['kode']}
$status | $label

Confidence: {$d['confidence']}%
Score: {$d['score']}

$target

$riskNote
";
    }

    return $text;
}

    // =====================
    // MAIN
    // =====================
    public function analisisSaham($kode, $mode = 'investor') {
        $data = $this->dataService->getData($kode); // ✅ gunakan property, bukan instantiate ulang

        if (!$data || isset($data["error"])) {
            return "❌ Gagal mengambil data untuk saham $kode. Pastikan kode saham benar.";
        }

        $scoring = new ScoringService();

        $harga         = $data["price"]          ?? 0;
        $eps           = $data["eps"]            ?? 0;
        $per           = $data["per"]            ?? 0;
        $pbv           = $data["pbv"]            ?? 0;
        $roe           = $data["roe"]            ?? 0;
        $der           = $data["der"]            ?? 0;
        $div_yield     = $data["div_yield"]      ?? 0;
        $rsi           = $data["rsi"]            ?? 0;
        $ma20          = $data["ma20"]           ?? 0;
        $ma50          = $data["ma50"]           ?? 0;
        $support       = $data["support"]        ?? 0;
        $resistance    = $data["resistance"]     ?? 0;
        $volume_signal = $data["volume_signal"]  ?? "low";

        $tek     = $this->analisisTeknikal($harga, $rsi, $ma20, $ma50, $support, $resistance, $volume_signal);
        $fund    = $this->analisisFundamental($per, $pbv, $roe, $eps, $der, $div_yield);
        $dec     = $scoring->hitungSAW($data);
        $ml      = $this->getMLPrediction($kode); // ✅ fetch sekali saja di sini
        $insight = $this->generateInsight($per, $roe, $rsi, $tek['trend'], $ml); // ✅ hapus param $insight
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

        return $this->formatOutputByMode(
            $mode, $kode, $harga, $fund, $tek, $dec,
            $insight, $narasi, $support, $resistance,
            $volume_signal, $rsi, $ml 
        );
    }
}