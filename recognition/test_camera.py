import asyncio
import json
import aiohttp

SERVICE_URL = "http://127.0.0.1:5000"
CAMERA_ID = 7  # change to an existing camera_id in your Laravel DB

# Dummy SDP offer (minimal valid offer)
DUMMY_OFFER = {
    "type": "offer",
    "sdp": "v=0\r\n"  # minimal placeholder; WebRTC client normally generates full SDP
}

async def test_offer():
    async with aiohttp.ClientSession() as session:
        # POST /offer/{camera_id}
        offer_url = f"{SERVICE_URL}/offer/{CAMERA_ID}"
        async with session.post(offer_url, json=DUMMY_OFFER) as resp:
            data = await resp.json()
            print("Offer response:", json.dumps(data, indent=2))

        # GET /status
        status_url = f"{SERVICE_URL}/status"
        async with session.get(status_url) as resp:
            data = await resp.json()
            print("Status response:", json.dumps(data, indent=2))

        # GET /health
        health_url = f"{SERVICE_URL}/health"
        async with session.get(health_url) as resp:
            data = await resp.json()
            print("Health response:", json.dumps(data, indent=2))

if __name__ == "__main__":
    asyncio.run(test_offer())
