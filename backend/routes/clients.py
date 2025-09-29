from fastapi import APIRouter, HTTPException, Depends, Query, UploadFile, File
from typing import Optional
from motor.motor_asyncio import AsyncIOMotorDatabase
from models.client import ClientCreate, ClientUpdate, ClientResponse, ClientsResponse
from services.client_service import ClientService
from dependencies import get_database

router = APIRouter(prefix="/clients", tags=["clients"])

@router.post("/", response_model=ClientResponse)
async def create_client(
    client_data: ClientCreate,
    db: AsyncIOMotorDatabase = Depends(get_database)
):
    """Create a new client"""
    try:
        client_service = ClientService(db)
        return await client_service.create_client(client_data)
    except ValueError as e:
        raise HTTPException(status_code=400, detail=str(e))
    except Exception as e:
        raise HTTPException(status_code=500, detail="Internal server error")

@router.get("/", response_model=ClientsResponse)
async def get_clients(
    page: int = Query(1, ge=1),
    limit: int = Query(20, ge=1, le=100),
    search: Optional[str] = Query(None),
    status: Optional[str] = Query(None),
    db: AsyncIOMotorDatabase = Depends(get_database)
):
    """Get clients with pagination and filtering"""
    try:
        client_service = ClientService(db)
        return await client_service.get_clients(page, limit, search, status)
    except Exception as e:
        raise HTTPException(status_code=500, detail="Internal server error")

@router.get("/{client_id}", response_model=ClientResponse)
async def get_client(
    client_id: str,
    db: AsyncIOMotorDatabase = Depends(get_database)
):
    """Get client by ID"""
    try:
        client_service = ClientService(db)
        client = await client_service.get_client(client_id)
        if not client:
            raise HTTPException(status_code=404, detail="Client not found")
        return client
    except HTTPException:
        raise
    except Exception as e:
        raise HTTPException(status_code=500, detail="Internal server error")

@router.put("/{client_id}", response_model=ClientResponse)
async def update_client(
    client_id: str,
    client_data: ClientUpdate,
    db: AsyncIOMotorDatabase = Depends(get_database)
):
    """Update client"""
    try:
        client_service = ClientService(db)
        client = await client_service.update_client(client_id, client_data)
        if not client:
            raise HTTPException(status_code=404, detail="Client not found")
        return client
    except ValueError as e:
        raise HTTPException(status_code=400, detail=str(e))
    except HTTPException:
        raise
    except Exception as e:
        raise HTTPException(status_code=500, detail="Internal server error")

@router.delete("/{client_id}")
async def delete_client(
    client_id: str,
    db: AsyncIOMotorDatabase = Depends(get_database)
):
    """Delete client"""
    try:
        client_service = ClientService(db)
        success = await client_service.delete_client(client_id)
        if not success:
            raise HTTPException(status_code=404, detail="Client not found")
        return {"message": "Client deleted successfully"}
    except HTTPException:
        raise
    except Exception as e:
        raise HTTPException(status_code=500, detail="Internal server error")

@router.post("/import")
async def import_clients(
    file: UploadFile = File(...),
    db: AsyncIOMotorDatabase = Depends(get_database)
):
    """Import clients from CSV file"""
    try:
        if not file.filename.endswith('.csv'):
            raise HTTPException(status_code=400, detail="File must be a CSV")
        
        content = await file.read()
        csv_content = content.decode('utf-8')
        
        client_service = ClientService(db)
        results = await client_service.import_from_csv(csv_content)
        
        return {
            "message": "Import completed",
            "results": results
        }
    except HTTPException:
        raise
    except Exception as e:
        raise HTTPException(status_code=500, detail=f"Import failed: {str(e)}")

@router.put("/{client_id}/stats")
async def update_client_stats(
    client_id: str,
    db: AsyncIOMotorDatabase = Depends(get_database)
):
    """Update client statistics"""
    try:
        client_service = ClientService(db)
        await client_service.update_client_stats(client_id)
        return {"message": "Client stats updated successfully"}
    except Exception as e:
        raise HTTPException(status_code=500, detail="Internal server error")