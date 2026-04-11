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
BASE_DIR = os.path.dirname(os.path.abspath(__file__))
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
hist = stock.history(period="3mo")

if hist.empty:
    print(json.dumps({"error": "Data tidak tersedia"}))
    sys.exit()

# ======================
# FEATURE (HARUS SAMA)
# ======================
hist['return_1d'] = hist['Close'].pct_change()
hist['return_5d'] = hist['Close'].pct_change(5)

hist['ma20'] = hist['Close'].rolling(20).mean()
hist['dist_ma20'] = (hist['Close'] - hist['ma20']) / hist['ma20']

delta = hist['Close'].diff()
gain = delta.clip(lower=0).rolling(14).mean()
loss = (-delta.clip(upper=0)).rolling(14).mean()
rs = gain / loss
hist['rsi'] = 100 - (100 / (1 + rs))

hist['volume_ratio'] = hist['Volume'] / hist['Volume'].rolling(20).mean()

hist = hist.dropna()

# ======================
# AMBIL BARIS TERAKHIR
# ======================
last = hist.iloc[-1]

features = pd.DataFrame([{
    "rsi": last['rsi'],
    "return_1d": last['return_1d'],
    "return_5d": last['return_5d'],
    "dist_ma20": last['dist_ma20'],
    "volume_ratio": last['volume_ratio']
}])
features = features[[
    "rsi",
    "return_1d",
    "return_5d",
    "dist_ma20",
    "volume_ratio"
]]

# ======================
# PREDICT
# ======================
pred = model.predict(features)[0]
prob = model.predict_proba(features)[0]

confidence = round(max(prob) * 100, 2)

result = {
    "pred": int(pred),
    "confidence": confidence
}

print(json.dumps(result))