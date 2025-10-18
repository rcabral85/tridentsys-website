from fastapi import FastAPI, HTTPException
from pydantic import BaseModel
from typing import List
import datetime

app = FastAPI()

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

# Example in-memory “database”
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

# You can add further endpoints here (e.g., /v1/hydrants, /v1/hydrants/{hydrant_id}/flow-tests)
