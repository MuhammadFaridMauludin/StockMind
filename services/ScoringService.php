<?php
require_once __DIR__ . "/../config/Database.php";

class ScoringService {

    /*
    function analisisKeputusan($harga, $eps, $per, $roe, $der, $div_yield, $rsi, $ma20, $ma50, $support, $volume_signal) {

        $score = 50; // base netral
        $notes = [];

        // ======================
        // FUNDAMENTAL
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
        // TEKNIKAL
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
    */
    public function hitungSAW($data) {

        $harga = $data['price'];
        $eps = $data['eps'];
        $per = $data['per'];
        $roe = $data['roe'];
        $der = $data['der'];
        $div = $data['div_yield'];
        $rsi = $data['rsi'];
        $ma20 = $data['ma20'];
        $ma50 = $data['ma50'];
        $support = $data['support'];
        $volume = $data['volume_signal'];

        $notes = [];

        // ======================
        // NORMALISASI
        // ======================

        $nilai_per =
            ($per > 0 && $per < 10) ? 1 :
            ($per < 20 ? 0.7 :
            ($per < 40 ? 0.4 : 0.1));

        $nilai_roe = min($roe / 0.2, 1);

        if ($der == 0) $nilai_der = 0.5;
        elseif ($der < 1) $nilai_der = 1;
        elseif ($der < 2) $nilai_der = 0.6;
        else {
            $nilai_der = 0.2;
            $notes[] = "Utang tinggi";
        }

        if ($div >= 0.02 && $div <= 0.06) $nilai_div = 1;
        elseif ($div > 0.06) $nilai_div = 0.7;
        elseif ($div > 0) $nilai_div = 0.5;
        else $nilai_div = 0.3;

        $nilai_rsi =
            ($rsi < 30) ? 1 :
            ($rsi < 50 ? 0.6 :
            ($rsi < 70 ? 0.3 : 0));

        if ($rsi > 70) $notes[] = "Jenuh beli";

        if ($harga > $ma20 && $ma20 > $ma50) $nilai_trend = 1;
        elseif ($harga < $ma20 && $ma20 < $ma50) {
            $nilai_trend = 0;
            $notes[] = "Masih downtrend";
        } else $nilai_trend = 0.5;

        $distance = ($harga - $support) / $support;
        if ($distance <= 0.02) $nilai_support = 1;
        elseif ($distance <= 0.05) $nilai_support = 0.7;
        else $nilai_support = 0.3;

        $nilai_volume = ($volume == "high") ? 1 : 0.5;

        // ======================
        // BOBOT
        // ======================
        $bobot = [
            "per" => 0.20,
            "roe" => 0.25,
            "der" => 0.10,
            "div" => 0.05,
            "rsi" => 0.10,
            "trend" => 0.15,
            "support" => 0.10,
            "volume" => 0.05
        ];

        // ======================
        // HITUNG
        // ======================
        $score =
            ($nilai_per * $bobot['per']) +
            ($nilai_roe * $bobot['roe']) +
            ($nilai_der * $bobot['der']) +
            ($nilai_div * $bobot['div']) +
            ($nilai_rsi * $bobot['rsi']) +
            ($nilai_trend * $bobot['trend']) +
            ($nilai_support * $bobot['support']) +
            ($nilai_volume * $bobot['volume']);

        $score *= 100;

        // ======================
        // REKOMENDASI
        // ======================
        if ($score >= 85) $rekom = "Strong Buy 🚀";
        elseif ($score >= 70) $rekom = "Buy 👍";
        elseif ($score >= 55) $rekom = "Buy on Weakness";
        elseif ($score >= 40) $rekom = "Wait & See";
        else $rekom = "Avoid ⚠️";

        // NILAI WAJAR
        $hargawajar = ($eps > 0) ? $eps * 8 : 0;
        $valuasi = ($harga < $hargawajar) ? "Undervalued ✅" : "Overvalued ❌";

        return [
            "score" => round($score, 2),
            "rekom" => $rekom,
            "notes" => $notes,
            "valuasi" => $valuasi,
            "hargawajar" => $hargawajar
        ];
    }
public function rankingSektor($namaSektor) {

    require_once __DIR__ . "/../config/Database.php";
    require_once __DIR__ . "/StockService.php";

    $db = new Database();
    $conn = $db->getConnection();

    // 🔹 Ambil saham dari DB
    $stmt = $conn->prepare("
        SELECT s.kode 
        FROM saham s
        JOIN sektor sec ON s.sektor_id = sec.id
        WHERE LOWER(sec.nama) = ?
    ");

    $stmt->bind_param("s", $namaSektor);
    $stmt->execute();

    $result = $stmt->get_result();

    if ($result->num_rows == 0) return [];

    $hasil = [];
    $stockService = new StockService();

    while ($row = $result->fetch_assoc()) {
        $kode = $row['kode'];

        // 🔹 Ambil data dari DB dulu
        $data = $this->getDataDariDB($conn, $kode);

if (!$data || !$this->isFresh($data['updated_at'])) {

    file_put_contents("storage/log2.txt", "REFRESH API: $kode\n", FILE_APPEND);

    $data = $stockService->getDataSaham($kode);

    if (!$data || isset($data["error"])) continue;

    $this->simpanKeDB($conn, $kode, $data);
}
        $score = $this->hitungSAW($data);

        $hasil[] = [
            "kode" => $kode,
            "score" => $score["score"]
        ];
    }

    usort($hasil, fn($a, $b) => $b['score'] <=> $a['score']);

    return $hasil;
}
private function getDataDariDB($conn, $kode) {
    $stmt = $conn->prepare("SELECT * FROM data_saham WHERE kode = ?");
    $stmt->bind_param("s", $kode);
    $stmt->execute();

    return $stmt->get_result()->fetch_assoc();
}
private function isFresh($updated_at) {
    if (!$updated_at) return false;

    return (time() - strtotime($updated_at)) < 300; // 5 menit
}
private function simpanKeDB($conn, $kode, $data) {

    $stmt = $conn->prepare("
        INSERT INTO data_saham 
        (kode, harga, eps, per, roe, der, div_yield, rsi, ma20, ma50, support, volume_signal, updated_at)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())
        ON DUPLICATE KEY UPDATE
        harga=VALUES(harga),
        eps=VALUES(eps),
        per=VALUES(per),
        roe=VALUES(roe),
        der=VALUES(der),
        div_yield=VALUES(div_yield),
        rsi=VALUES(rsi),
        ma20=VALUES(ma20),
        ma50=VALUES(ma50),
        support=VALUES(support),
        volume_signal=VALUES(volume_signal),
        updated_at=NOW()
    ");

    $stmt->bind_param(
        "sdddddddddds",
        $kode,
        $data['price'],
        $data['eps'],
        $data['per'],
        $data['roe'],
        $data['der'],
        $data['div_yield'],
        $data['rsi'],
        $data['ma20'],
        $data['ma50'],
        $data['support'],
        $data['volume_signal']
    );

    $stmt->execute();
}
public function formatRanking($namaSektor, $ranking) {
    if (empty($ranking)) {
        return "❌ Data sektor tidak ditemukan.";
    }

    $text = "📊 RANKING SAHAM SEKTOR " . strtoupper($namaSektor) . "\n\n";

    foreach ($ranking as $i => $item) {
        $text .= ($i+1) . ". {$item['kode']} - Score: {$item['score']}\n";
    }

    return $text;
}
}