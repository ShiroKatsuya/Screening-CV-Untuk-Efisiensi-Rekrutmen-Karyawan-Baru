from fastapi import FastAPI
from fastapi.middleware.cors import CORSMiddleware
from pydantic import BaseModel
from typing import Dict, Any

import numpy as np
from sklearn.ensemble import RandomForestRegressor

import spacy
from spacy.util import is_package, get_package_path
import subprocess
import sys
import logging

app = FastAPI(title="CV Screening ML Service")

# Add CORS middleware
app.add_middleware(
    CORSMiddleware,
    allow_origins=["*"],  # In production, restrict this to your Laravel app domain
    allow_credentials=True,
    allow_methods=["*"],
    allow_headers=["*"],
)

class CandidatePayload(BaseModel):
    name: str
    position_applied: str
    skills: str | None = None
    years_experience: int | None = 0
    education_level: str | None = None
    cv_text: str | None = None

# Load spaCy model lazily
_nlp = None

def ensure_spacy_model(model_name: str = "en_core_web_sm"):
    """
    Ensure the spaCy model is installed. If not, attempt to download it.
    """
    try:
        # Try to load the model to check if it's available
        spacy.load(model_name)
    except OSError:
        logging.warning(f"spaCy model '{model_name}' not found. Attempting to download...")
        try:
            subprocess.run(
                [sys.executable, "-m", "spacy", "download", model_name],
                check=True,
                capture_output=True,
            )
            logging.info(f"spaCy model '{model_name}' downloaded successfully.")
        except Exception as e:
            logging.error(f"Failed to download spaCy model '{model_name}': {e}")
            raise RuntimeError(
                f"spaCy model '{model_name}' is required but could not be installed automatically. "
                f"Please run 'python -m spacy download {model_name}' manually."
            )

def get_nlp():
    global _nlp
    if _nlp is None:
        ensure_spacy_model("en_core_web_sm")
        _nlp = spacy.load("en_core_web_sm")
    return _nlp

def extract_features(payload: CandidatePayload) -> Dict[str, Any]:
    nlp = get_nlp()
    doc = nlp((payload.cv_text or "")[:20000])

    tokens = [t.lemma_.lower() for t in doc if not t.is_stop and t.is_alpha]
    text = " ".join(tokens)

    keywords = {
        "python": text.count("python"),
        "javascript": text.count("javascript") + text.count("node"),
        "sql": text.count("sql") + text.count("mysql") + text.count("postgres"),
        "nlp": text.count("nlp") + text.count("spacy") + text.count("transformer"),
        "ml": text.count("sklearn") + text.count("pytorch") + text.count("tensorflow") + text.count("machine learning"),
    }

    years = payload.years_experience or 0
    edu_map = {"sma": 0, "diploma": 1, "sarjana": 2, "magister": 3, "doktor": 4}
    edu = (payload.education_level or "").strip().lower()
    edu_score = edu_map.get(edu, 1 if edu else 0)

    pos = (payload.position_applied or "").lower()
    pos_keywords = sum(1 for k in ["data", "engineer", "analyst", "developer", "nlp"] if k in pos)

    features = {
        **keywords,
        "years_experience": years,
        "education_score": edu_score,
        "position_match": pos_keywords,
        "tokens_len": len(tokens),
    }
    return features

def score_features(features: Dict[str, Any]) -> float:
    # Build a tiny synthetic model per request (for demo). In production, load a persisted model.
    X = []
    y = []
    rng = np.random.default_rng(42)
    for i in range(100):
        f = {
            "python": rng.integers(0, 20),
            "javascript": rng.integers(0, 20),
            "sql": rng.integers(0, 20),
            "nlp": rng.integers(0, 20),
            "ml": rng.integers(0, 20),
            "years_experience": rng.integers(0, 15),
            "education_score": rng.integers(0, 4),
            "position_match": rng.integers(0, 3),
            "tokens_len": rng.integers(50, 1000),
        }
        X.append(list(f.values()))
        # Synthetic score roughly correlated with features
        y.append(
            0.4 * f["python"] +
            0.3 * f["ml"] +
            0.2 * f["nlp"] +
            2.0 * f["years_experience"] +
            3.0 * f["education_score"] +
            1.0 * f["position_match"] +
            0.01 * f["tokens_len"] +
            rng.normal(0, 2)
        )

    model = RandomForestRegressor(n_estimators=50, random_state=42)
    model.fit(np.array(X), np.array(y))

    x = np.array([list(features.values())]).astype(float)
    pred = float(model.predict(x)[0])
    # Normalize to 0-100 range (heuristic)
    score = max(0.0, min(100.0, pred))
    return score

@app.get("/")
def root():
    return {"message": "CV Screening ML Service is running", "version": "1.0.0"}

@app.post("/score")
def score(payload: CandidatePayload):
    print(f"Received scoring request for: {payload.name}")
    print(f"Position: {payload.position_applied}")
    print(f"CV text length: {len(payload.cv_text or '')}")
    
    features = extract_features(payload)
    score_value = score_features(features)
    recommendation = (
        "Sangat Direkomendasikan" if score_value >= 80 else
        "Direkomendasikan" if score_value >= 65 else
        "Pertimbangkan" if score_value >= 50 else
        "Tidak Direkomendasikan"
    )
    
    result = {
        "features": features,
        "score": round(score_value, 2),
        "recommendation": recommendation,
    }
    
    print(f"Scoring result: {result}")
    return result
