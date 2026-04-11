import pandas as pd
import pickle

from sklearn.model_selection import train_test_split
from sklearn.ensemble import RandomForestClassifier
from sklearn.metrics import accuracy_score, classification_report

# ======================
# LOAD DATA
# ======================
df = pd.read_csv("data/dataset.csv")

# ======================
# PILIH FITUR
# ======================
features = [
    "rsi",
    "return_1d",
    "return_5d",
    #"macd",
    #"macd_hist",
    "dist_ma20",
    #"dist_ma50",
    #"dist_support",
    #"dist_resistance",
    "volume_ratio",
    
    #"return_20d"
]

X = df[features]
y = df["target"]

# ======================
# SPLIT DATA
# ======================
X_train, X_test, y_train, y_test = train_test_split(
    X, y, test_size=0.2, shuffle=False
)

# ======================
# TRAIN MODEL
# ======================
model = RandomForestClassifier(
    n_estimators=100,
    max_depth=5,
    random_state=42
)

model.fit(X_train, y_train)

# ======================
# EVALUASI
# ======================
y_pred = model.predict(X_test)

print("Accuracy:", accuracy_score(y_test, y_pred))
print(classification_report(y_test, y_pred))

# ======================
# SAVE MODEL
# ======================
with open("models/model.pkl", "wb") as f:
    pickle.dump(model, f)

print("✅ Model berhasil disimpan")