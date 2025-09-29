from fastapi import FastAPI, APIRouter, HTTPException
from dotenv import load_dotenv
from starlette.middleware.cors import CORSMiddleware
from motor.motor_asyncio import AsyncIOMotorClient
import os
import logging
from pathlib import Path

# Import route modules
from routes.clients import router as clients_router
from routes.billing import router as billing_router
from routes.dashboard import router as dashboard_router

ROOT_DIR = Path(__file__).parent
load_dotenv(ROOT_DIR / '.env')

# MongoDB connection
mongo_url = os.environ['MONGO_URL']
client = AsyncIOMotorClient(mongo_url)
db = client[os.environ.get('DB_NAME', 'whmcs_admin')]

# Create the main app without a prefix
app = FastAPI(title="WHMCS Admin API", version="1.0.0")

# Create a router with the /api prefix
api_router = APIRouter(prefix="/api")

# Health check endpoint
@api_router.get("/")
async def root():
    return {"message": "WHMCS Admin API is running", "version": "1.0.0"}

@api_router.get("/health")
async def health_check():
    """Health check endpoint"""
    try:
        # Test database connection
        await db.command("ping")
        return {
            "status": "healthy",
            "database": "connected",
            "timestamp": "2024-07-21T12:00:00Z"
        }
    except Exception as e:
        raise HTTPException(status_code=503, detail={
            "status": "unhealthy",
            "database": "disconnected",
            "error": str(e)
        })

# Include routers
api_router.include_router(clients_router)
api_router.include_router(billing_router)
api_router.include_router(dashboard_router)

# Placeholder routes for other modules (to be implemented)
@api_router.get("/services")
async def get_services():
    """Get services - placeholder"""
    return {
        "services": [],
        "total": 0,
        "message": "Services endpoint - to be implemented"
    }

@api_router.get("/domains")
async def get_domains():
    """Get domains - placeholder"""
    return {
        "domains": [],
        "total": 0,
        "message": "Domains endpoint - to be implemented"
    }

@api_router.get("/support/tickets")
async def get_tickets():
    """Get support tickets - placeholder"""
    return {
        "tickets": [],
        "total": 0,
        "message": "Support tickets endpoint - to be implemented"
    }

# Include the router in the main app
app.include_router(api_router)

app.add_middleware(
    CORSMiddleware,
    allow_credentials=True,
    allow_origins=["*"],
    allow_methods=["*"],
    allow_headers=["*"],
)

# Configure logging
logging.basicConfig(
    level=logging.INFO,
    format='%(asctime)s - %(name)s - %(levelname)s - %(message)s'
)
logger = logging.getLogger(__name__)

@app.on_event("startup")
async def startup_event():
    """Initialize application on startup"""
    try:
        # Test database connection
        await db.command("ping")
        logger.info("Connected to MongoDB successfully")
        
        # Create indexes for better performance
        await db.clients.create_index("email", unique=True)
        await db.clients.create_index("status")
        await db.invoices.create_index("invoiceId", unique=True)
        await db.invoices.create_index(["clientId", "status"])
        await db.services.create_index(["clientId", "status"])
        await db.domains.create_index(["clientId", "domain"])
        await db.tickets.create_index(["clientId", "status"])
        
        logger.info("Database indexes created successfully")
        
    except Exception as e:
        logger.error(f"Failed to initialize application: {str(e)}")
        raise

@app.on_event("shutdown")
async def shutdown_db_client():
    """Close database connection on shutdown"""
    try:
        client.close()
        logger.info("Database connection closed")
    except Exception as e:
        logger.error(f"Error closing database connection: {str(e)}")

# Error handlers
from fastapi.responses import JSONResponse

@app.exception_handler(404)
async def not_found_handler(request, exc):
    return JSONResponse(
        status_code=404,
        content={"error": "Endpoint not found", "status_code": 404}
    )

@app.exception_handler(500)
async def internal_server_error_handler(request, exc):
    logger.error(f"Internal server error: {str(exc)}")
    return JSONResponse(
        status_code=500,
        content={"error": "Internal server error", "status_code": 500}
    )

if __name__ == "__main__":
    import uvicorn
    uvicorn.run(app, host="0.0.0.0", port=8001)