import pandas as pd
import pickle

from sklearn.utils import resample
from sklearn.model_selection import train_test_split
from sklearn.ensemble import RandomForestClassifier
from sklearn.metrics import accuracy_score, classification_report

# ======================
# LOAD DATA
# ======================
df = pd.read_csv("data/dataset.csv")

# ======================
# BALANCING DATA 🔥
# ======================
df_majority = df[df.target == 0]
df_minority = df[df.target == 1]

df_minority_upsampled = resample(
    df_minority,
    replace=True,
    n_samples=len(df_majority),
    random_state=42
)

df = pd.concat([df_majority, df_minority_upsampled])

# ======================
# SHUFFLE (WAJIB setelah balancing)
# ======================
df = df.sample(frac=1, random_state=42)

# ======================
# FITUR
# ======================
features = [
    "rsi",
    "rsi_change",
    "return_1d",
    "return_5d",
    "return_10d",
    "return_20d",
    "dist_ma20",
    "dist_ma50",
    "volume_spike",
    "volatility",
    "body_ratio",
    "upper_ratio",
    "lower_ratio",
    "atr"
]

X = df[features]
y = df["target"]

# ======================
# SPLIT DATA
# ======================
X_train, X_test, y_train, y_test = train_test_split(
    X, y, test_size=0.2, random_state=42
)

# ======================
# MODEL (UPGRADE 🔥)
# ======================
model = RandomForestClassifier(
    n_estimators=200,
    max_depth=10,
    class_weight='balanced',
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