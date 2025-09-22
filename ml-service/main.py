from fastapi import FastAPI
from fastapi.middleware.cors import CORSMiddleware
from pydantic import BaseModel
from typing import Dict, Any
import re

import numpy as np  # Optional; kept if needed elsewhere

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
    # Combine cv_text and skills for richer signal
    combined_text = ((payload.cv_text or "") + "\n" + (payload.skills or "")).strip()
    doc = nlp(combined_text[:20000])

    tokens = [t.lemma_.lower() for t in doc if not t.is_stop and t.is_alpha]
    text = " ".join(tokens)

    # Keyword counts with simple stemming/aliases
    keyword_defs = {
        "python": ["python"],
        "javascript": ["javascript", "node", "nodejs", "react", "vue"],
        "sql": ["sql", "mysql", "postgres", "postgresql", "mssql", "oracle"],
        "nlp": ["nlp", "spacy", "transformer", "bert", "huggingface"],
        "ml": ["sklearn", "scikit", "pytorch", "tensorflow", "machine", "ml"],
    }

    keywords = {}
    for k, aliases in keyword_defs.items():
        keywords[k] = sum(text.count(a) for a in aliases)

    # Infer years of experience from text as a fallback/boost
    years = payload.years_experience or 0
    inferred_years = 0
    years_patterns = [
        r"(\d{1,2})\s*\+?\s*(years|year|yrs|yr|tahun)",
        r"experience\s*(?:of|:)?\s*(\d{1,2})",
    ]
    for pat in years_patterns:
        matches = re.findall(pat, combined_text.lower())
        for m in matches:
            try:
                # m can be tuple or str depending on pattern
                val = int(m[0] if isinstance(m, tuple) else m)
                inferred_years = max(inferred_years, val)
            except Exception:
                continue
    years = max(years, inferred_years)

    # Education score mapping (Indonesian terms included)
    edu_map = {"sma": 0, "diploma": 1, "sarjana": 2, "s1": 2, "magister": 3, "s2": 3, "doktor": 4, "s3": 4, "phd": 4, "bachelor": 2, "master": 3, "doctor": 4}
    edu = (payload.education_level or "").strip().lower()
    edu_score = edu_map.get(edu, 1 if edu else 0)

    # Position match: count of relevant tokens in position title
    pos = (payload.position_applied or "").lower()
    pos_keywords = sum(1 for k in ["data", "engineer", "analyst", "developer", "nlp", "scientist", "ml"] if k in pos)

    features = {
        **keywords,
        "years_experience": years,
        "education_score": edu_score,
        "position_match": pos_keywords,
        "tokens_len": len(tokens),
    }
    return features

def score_features(features: Dict[str, Any]) -> float:
    # Deterministic weighted scoring with normalization and caps
    caps = {
        "python": 30,
        "javascript": 30,
        "sql": 30,
        "nlp": 20,
        "ml": 30,
        "years_experience": 15,
        "education_score": 4,
        "position_match": 3,
        "tokens_len": 2000,
    }

    weights = {
        "python": 12.0,
        "javascript": 8.0,
        "sql": 10.0,
        "nlp": 10.0,
        "ml": 12.0,
        "years_experience": 20.0,
        "education_score": 12.0,
        "position_match": 10.0,
        "tokens_len": 6.0,
    }

    # Ensure weights sum to 100 (tolerance)
    total_weight = sum(weights.values())
    if abs(total_weight - 100.0) > 1e-6:
        # Normalize weights to sum to 100
        weights = {k: v * (100.0 / total_weight) for k, v in weights.items()}

    score = 0.0
    for name, cap in caps.items():
        value = float(features.get(name, 0))
        # Soft cap using min; tokens_len uses log compression
        if name == "tokens_len":
            norm = min(1.0, (np.log1p(value) / np.log1p(cap)))
        else:
            norm = max(0.0, min(1.0, value / cap))
        score += weights.get(name, 0.0) * norm

    # Final clamp
    score = max(0.0, min(100.0, score))
    return float(score)

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