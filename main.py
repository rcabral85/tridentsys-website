from fastapi import FastAPI, HTTPException, Body
from pydantic import BaseModel
import os
import requests

OPENAI_API_KEY = os.getenv("OPENAI_API_KEY")
OPENAI_MODEL = "gpt-4o-mini"

app = FastAPI()

# --- AI Customer Support Chatbot ---
class ChatRequest(BaseModel):
    question: str
    role: str | None = "client"  # for possible expansion ("client", "contractor", etc)

class ChatResponse(BaseModel):
    answer: str

def chat_openai(question: str, role: str = "client") -> str:
    if not OPENAI_API_KEY:
        raise HTTPException(status_code=500, detail="OPENAI_API_KEY not set")
    url = "https://api.openai.com/v1/chat/completions"
    headers = {
        "Authorization": f"Bearer {OPENAI_API_KEY}",
        "Content-Type": "application/json",
    }
    system_message = (
        "You are a friendly and professional fire hydrant testing support assistant for Trident Systems, Ontario."
        " Provide accurate, plain-English answers about flow testing, scheduling, regulations, and business services as of 2025."
        " If answering for non-clients, keep details generic; for Trident clients, explain service policies and local compliance requirements."
        " Focus on answers relevant to the Ontario context (NFPA 291, OTM Book 7, municipal regulations)."
    )
    body = {
        "model": OPENAI_MODEL,
        "messages": [
            {"role": "system", "content": system_message},
            {"role": "user", "content": question},
        ],
        "temperature": 0.3,
    }
    r = requests.post(url, json=body, headers=headers, timeout=30)
    r.raise_for_status()
    return r.json()["choices"][0]["message"]["content"].strip()

@app.post("/v1/chat", response_model=ChatResponse)
def chat_endpoint(request: ChatRequest = Body(...)):
    try:
        answer = chat_openai(request.question, request.role)
        return ChatResponse(answer=answer)
    except requests.HTTPError as e:
        raise HTTPException(status_code=502, detail=f"OpenAI error: {e.response.text}")
    except Exception as e:
        raise HTTPException(status_code=500, detail=str(e))

# --- Previous endpoints omitted here for brevity ---
# Keep your other report summary and hydrant endpoints below (or earlier in the file)
