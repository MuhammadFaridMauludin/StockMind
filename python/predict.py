import sys
import os
import json
import pickle
import pandas as pd
import yfinance as yf

import warnings
warnings.filterwarnings("ignore")

# ======================
# LOAD MODEL
# ======================
BASE_DIR   = os.path.dirname(os.path.abspath(__file__))
MODEL_PATH = os.path.join(BASE_DIR, "models", "model.pkl")

model = pickle.load(open(MODEL_PATH, "rb"))

# ======================
# INPUT
# ======================
kode = sys.argv[1].strip().upper()

if not kode.endswith(".JK"):
    kode += ".JK"

# ======================
# FETCH DATA
# ======================
stock = yf.Ticker(kode)
hist  = stock.history(period="6mo") # ✅ naikkan ke 6mo agar ATR & MA50 cukup data

if hist is None or hist.empty:
    print(json.dumps({"error": "Data tidak tersedia untuk " + kode}))
    sys.exit()

# ======================
# PRICE ACTION
# ======================
hist['body']         = hist['Close'] - hist['Open']
hist['range']        = hist['High'] - hist['Low']
hist['upper_shadow'] = hist['High'] - hist[['Close', 'Open']].max(axis=1)
hist['lower_shadow'] = hist[['Close', 'Open']].min(axis=1) - hist['Low']

# ✅ Hindari division by zero SEBELUM normalisasi
hist['range'] = hist['range'].replace(0, 1)

hist['body_ratio']  = hist['body']  / hist['range']
hist['upper_ratio'] = hist['upper_shadow'] / hist['range']
hist['lower_ratio'] = hist['lower_shadow'] / hist['range']

# ======================
# ATR
# ======================
hist['tr'] = hist[['High', 'Low', 'Close']].apply(
    lambda x: max(
        x['High'] - x['Low'],
        abs(x['High'] - x['Close']),
        abs(x['Low']  - x['Close'])
    ), axis=1
)
hist['atr'] = hist['tr'].rolling(14).mean()

# ======================
# RETURN
# ======================
hist['return_1d']  = hist['Close'].pct_change()
hist['return_5d']  = hist['Close'].pct_change(5)
hist['return_10d'] = hist['Close'].pct_change(10)
hist['return_20d'] = hist['Close'].pct_change(20)

# ======================
# MOVING AVERAGE
# ======================
hist['ma20'] = hist['Close'].rolling(20).mean()
hist['ma50'] = hist['Close'].rolling(50).mean()

hist['dist_ma20'] = (hist['Close'] - hist['ma20']) / hist['ma20']
hist['dist_ma50'] = (hist['Close'] - hist['ma50']) / hist['ma50']

# ======================
# VOLATILITY
# ======================
hist['volatility'] = hist['Close'].rolling(20).std()

# ======================
# VOLUME
# ======================
hist['volume_ma20'] = hist['Volume'].rolling(20).mean()
hist['volume_spike'] = hist['Volume'] / hist['volume_ma20']
hist['volume_ratio'] = hist['volume_spike'] # ✅ samakan, tidak duplikat hitung

# ======================
# RSI
# ======================
delta = hist['Close'].diff()
gain  = delta.clip(lower=0).rolling(14).mean()
loss  = (-delta.clip(upper=0)).rolling(14).mean()
rs    = gain / loss
hist['rsi']        = 100 - (100 / (1 + rs))
hist['rsi_change'] = hist['rsi'].diff()

# ✅ dropna SETELAH semua fitur dihitung
hist = hist.dropna()

if hist.empty:
    print(json.dumps({"error": "Data tidak cukup setelah kalkulasi fitur"}))
    sys.exit()

# ======================
# AMBIL BARIS TERAKHIR
# ======================
last = hist.iloc[-1]

# ✅ Features harus IDENTIK dengan train.py
features = pd.DataFrame([{
    "rsi":          last['rsi'],
    "rsi_change":   last['rsi_change'],
    "return_1d":    last['return_1d'],
    "return_5d":    last['return_5d'],
    "return_10d":   last['return_10d'],
    "return_20d":   last['return_20d'],
    "dist_ma20":    last['dist_ma20'],
    "dist_ma50":    last['dist_ma50'],
    "volume_spike": last['volume_spike'],
    "volatility":   last['volatility'],
    "body_ratio":   last['body_ratio'],
    "upper_ratio":  last['upper_ratio'],
    "lower_ratio":  last['lower_ratio'],
    "atr":          last['atr']
}])

# ======================
# PREDICT
# ======================
pred       = model.predict(features)[0]
prob       = model.predict_proba(features)[0]
confidence = round(max(prob) * 100, 2)

print(json.dumps({
    "pred":       int(pred),
    "confidence": confidence
}))