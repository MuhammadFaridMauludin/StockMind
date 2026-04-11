import pickle

model = pickle.load(open("model/model.pkl", "rb"))

def predict(data):
    return model.predict([data])