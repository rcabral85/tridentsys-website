from fastapi import FastAPI, HTTPException, Body
from pydantic import BaseModel
import os
import requests

OPENAI_API_KEY = os.getenv("OPENAI_API_KEY")
OPENAI_MODEL = "gpt-4o-mini"

app = FastAPI()

class FlowTestInput(BaseModel):
    hydrant_id: str
    municipality: str
    location: str
    test_date: str
    operator: str
    static_pressure_psi: float
    residual_pressure_psi: float
    flow_rate_gpm: float
    available_flow_20psi: float | None = None
    nfpa_291_compliant: bool | None = None

class ReportSummary(BaseModel):
    summary: str
    recommendations: list[str]
    flags: list[str]

def call_openai(prompt: str) -> str:
    if not OPENAI_API_KEY:
        raise HTTPException(status_code=500, detail="OPENAI_API_KEY not set")
    url = "https://api.openai.com/v1/chat/completions"
    headers = {
        "Authorization": f"Bearer {OPENAI_API_KEY}",
        "Content-Type": "application/json",
    }
    body = {
        "model": OPENAI_MODEL,
        "messages": [
            {"role": "system", "content": "You are an expert NFPA 291 hydrant testing report writer for municipal clients in Ontario. Be concise, professional, and action-oriented."},
            {"role": "user", "content": prompt},
        ],
        "temperature": 0.2,
    }
    r = requests.post(url, json=body, headers=headers, timeout=30)
    r.raise_for_status()
    return r.json()["choices"][0]["message"]["content"].strip()

def build_prompt(data: FlowTestInput) -> str:
    return f"""
Hydrant ID: {data.hydrant_id}
Municipality: {data.municipality}
Location: {data.location}
Test Date: {data.test_date}
Operator: {data.operator}

Measurements:
- Static Pressure: {data.static_pressure_psi} psi
- Residual Pressure: {data.residual_pressure_psi} psi
- Flow Rate: {data.flow_rate_gpm} gpm
- Available Flow @20 psi: {data.available_flow_20psi if data.available_flow_20psi is not None else "compute if needed"}
- NFPA 291 compliant: {data.nfpa_291_compliant if data.nfpa_291_compliant is not None else "assess"}

Tasks:
1) Provide a 4-6 sentence executive summary suitable for a client report.
2) List 3-5 recommendations (bullet-point).
3) List any flags (bullets) such as low residual, low 20 psi flow, overdue inspection, suggest re-test, etc.
Keep it Ontario/OTM Book 7 context-aware and professional.
"""

@app.post("/v1/reports/summary", response_model=ReportSummary)
def generate_report_summary(payload: FlowTestInput = Body(...)):
    try:
        prompt = build_prompt(payload)
        content = call_openai(prompt)
        lines = [l.strip() for l in content.splitlines() if l.strip()]
        summary_lines, recommendations, flags = [], [], []
        mode = "summary"
        for l in lines:
            lower = l.lower()
            if lower.startswith("recommendation") or lower.startswith("- [rec") or "recommendation" in lower:
                mode = "recs"
                continue
            if lower.startswith("flag") or "flag" in lower or "issue" in lower:
                mode = "flags"
                continue
            if mode == "summary":
                summary_lines.append(l)
            elif mode == "recs":
                recommendations.append(l.lstrip("-•* ").strip())
            elif mode == "flags":
                flags.append(l.lstrip("-•* ").strip())
        if not summary_lines:
            summary_lines = lines[:5]
        if not recommendations:
            recommendations = [l.lstrip("-•* ").strip() for l in lines if l.startswith(("-", "•", "*"))][:5]
        if not flags:
            flags = [l for l in lines if "flag" in l.lower() or "issue" in l.lower()][:5]
        return ReportSummary(
            summary=" ".join(summary_lines)[:1200],
            recommendations=recommendations[:7],
            flags=flags[:7],
        )
    except requests.HTTPError as e:
        raise HTTPException(status_code=502, detail=f"OpenAI error: {e.response.text}")
    except Exception as e:
        raise HTTPException(status_code=500, detail=str(e))

# Previous hydrant demo endpoint
class FlowTest(BaseModel):
    test_id: str
    test_date: str
    operator: str
    static_pressure_psi: float
    residual_pressure_psi: float
    flow_rate_gpm: float
    available_flow_20psi: float
    nfpa_291_compliant: bool
    classification: str
    next_test_due: str
class Hydrant(BaseModel):
    id: str
    municipality_id: str
    location: dict
    status: dict
    specifications: dict
    latest_flow_test: FlowTest
    maintenance: dict
    emergency_access: dict
hydrants = {
    "HYD-MLT-001": Hydrant(
        id="HYD-MLT-001",
        municipality_id="milton-on",
        location={"latitude": 43.5186, "longitude": -79.8461, "address": "123 Main Street, Milton, ON"},
        status={"operational": "active","last_updated": "2025-10-18T14:30:00Z","maintenance_required": False},
        specifications={"manufacturer":"Mueller", "model":"Centurion"},
        latest_flow_test=FlowTest(
            test_id="TRI-2025-018",
            test_date="2025-09-15",
            operator="Licensed WDO - R.Cabral",
            static_pressure_psi=72.3,
            residual_pressure_psi=48.7,
            flow_rate_gpm=1245,
            available_flow_20psi=1842,
            nfpa_291_compliant=True,
            classification="EXCELLENT",
            next_test_due="2026-09-15"
        ),
        maintenance={"last_inspection":"2025-08-12","next_inspection_due":"2026-08-12"},
        emergency_access={"accessibility_rating":"excellent"}
    )
}
@app.get("/v1/hydrants/{hydrant_id}", response_model=Hydrant)
def get_hydrant(hydrant_id: str):
    if hydrant_id in hydrants:
        return hydrants[hydrant_id]
    raise HTTPException(status_code=404, detail="Hydrant not found")
