import yfinance as yf
import pandas as pd

kode_list = [
    "BBRI.JK",
    "BBCA.JK",
    "BMRI.JK",
    "TLKM.JK",
    "ASII.JK",
    "ADRO.JK",
    "UNVR.JK",
    "ICBP.JK",
    "ANTM.JK",
    "INDF.JK"
]

all_data = []

for kode in kode_list:

    print("Ambil:", kode)

    stock = yf.Ticker(kode)
    hist = stock.history(period="2y")  # 🔥 upgrade data

    if hist.empty:
        print("❌ Skip:", kode)
        continue

    # ======================
    # FEATURE
    # ======================

    # RETURN
    hist['return_1d'] = hist['Close'].pct_change()
    hist['return_5d'] = hist['Close'].pct_change(5)
    hist['return_10d'] = hist['Close'].pct_change(10)
    hist['return_20d'] = hist['Close'].pct_change(20)

    # MA
    hist['ma20'] = hist['Close'].rolling(20).mean()
    hist['ma50'] = hist['Close'].rolling(50).mean()

    # DISTANCE
    hist['dist_ma20'] = (hist['Close'] - hist['ma20']) / hist['ma20']
    hist['dist_ma50'] = (hist['Close'] - hist['ma50']) / hist['ma50']

    # VOLATILITY
    hist['volatility'] = hist['Close'].rolling(20).std()

    # VOLUME
    hist['volume_ma20'] = hist['Volume'].rolling(20).mean()
    hist['volume_spike'] = hist['Volume'] / hist['volume_ma20']
    hist['volume_ratio'] = hist['Volume'] / hist['volume_ma20']

    # ======================
    # RSI
    # ======================
    delta = hist['Close'].diff()
    gain = delta.clip(lower=0).rolling(14).mean()
    loss = (-delta.clip(upper=0)).rolling(14).mean()
    rs = gain / loss
    hist['rsi'] = 100 - (100 / (1 + rs))

    hist['rsi_change'] = hist['rsi'].diff()

    # ======================
    # TARGET
    # ======================
    hist['target'] = (hist['Close'].shift(-3) > hist['Close']).astype(int)

    # ======================
    # CLEAN
    # ======================
    hist = hist.dropna()

    # simpan kode saham
    hist['kode'] = kode

    all_data.append(hist)

# ======================
# GABUNG SEMUA DATA
# ======================
df = pd.concat(all_data)

# ======================
# SAVE
# ======================
df.to_csv("data/dataset.csv", index=False)

print("✅ Total dataset:", len(df))