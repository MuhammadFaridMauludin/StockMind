import pandas as pd
import joblib

from sklearn.ensemble import RandomForestClassifier
from sklearn.metrics import accuracy_score
import numpy as np

# ======================
# LOAD DATA
# ======================
df = pd.read_csv("data/dataset.csv")

# ======================
# FEATURES & TARGET
# ======================
features = [
    'body_ratio','upper_ratio','lower_ratio',
    'atr','return_1d','return_5d','return_10d','return_20d',
    'dist_ma20','dist_ma50','volatility',
    'volume_spike','rsi','rsi_change'
]

X = df[features]
y = df['target']

# ======================
# SPLIT DATA (TIME SERIES)
# ======================
split = int(len(df) * 0.8)

X_train, X_test = X.iloc[:split], X.iloc[split:]
y_train, y_test = y.iloc[:split], y.iloc[split:]

# ======================
# TRAIN MODEL
# ======================
model = RandomForestClassifier(
    n_estimators=200,
    max_depth=10,
    random_state=42
)

model.fit(X_train, y_train)

# ======================
# EVALUASI
# ======================

# --- basic accuracy
pred_basic = model.predict(X_test)
acc = accuracy_score(y_test, pred_basic)

# --- probability (confidence)
proba = model.predict_proba(X_test)[:, 1]

# threshold (sesuai Laravel kamu)
threshold = 0.6
pred = (proba > threshold).astype(int)

# --- winrate after threshold
winrate = accuracy_score(y_test, pred)

# ======================
# WINRATE KHUSUS BUY
# ======================
buy_idx = pred == 1

if np.sum(buy_idx) > 0:
    winrate_buy = np.mean(y_test[buy_idx] == 1)
else:
    winrate_buy = 0

# ======================
# PRINT RESULT
# ======================
print("===== HASIL TRAINING =====")
print("Total Data      :", len(df))
print("Train Data      :", len(X_train))
print("Test Data       :", len(X_test))
print("--------------------------")
print("Accuracy (all)  :", round(acc * 100, 2), "%")
print("Winrate (>=60%) :", round(winrate * 100, 2), "%")
print("Winrate BUY     :", round(winrate_buy * 100, 2), "%")

# ======================
# SAVE MODEL
# ======================
joblib.dump(model, "model.pkl")
print("✅ Model disimpan ke model.pkl")