## Layanan ML (FastAPI)

Layanan Python untuk ekstraksi fitur NLP dan penilaian Random Forest Regression.

### Menjalankan
```
cd ml-service
python -m venv .venv
.venv\Scripts\activate
pip install -r requirements.txt
uvicorn main:app --host 127.0.0.1 --port 8001 --reload
```

Laravel akan memanggil endpoint `POST /score` dengan payload kandidat dan menerima `features`, `score`, dan `recommendation`.

