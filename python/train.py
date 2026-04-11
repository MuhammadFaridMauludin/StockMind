from sklearn.ensemble import RandomForestClassifier
import pickle

# ambil data dari DB / CSV
X, y = load_data()

model = RandomForestClassifier()
model.fit(X, y)

pickle.dump(model, open("model/model.pkl", "wb"))