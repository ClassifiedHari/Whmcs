from fastapi import APIRouter, HTTPException, Depends, Query
from typing import Optional
from motor.motor_asyncio import AsyncIOMotorDatabase
from models.invoice import InvoiceCreate, InvoiceUpdate, InvoiceResponse, InvoicesResponse, BillingStats
from services.invoice_service import InvoiceService
from dependencies import get_database

router = APIRouter(prefix="/billing", tags=["billing"])

# Invoice endpoints
@router.post("/invoices", response_model=InvoiceResponse)
async def create_invoice(
    invoice_data: InvoiceCreate,
    db: AsyncIOMotorDatabase = Depends(get_database)
):
    """Create a new invoice"""
    try:
        invoice_service = InvoiceService(db)
        return await invoice_service.create_invoice(invoice_data)
    except ValueError as e:
        raise HTTPException(status_code=400, detail=str(e))
    except Exception as e:
        raise HTTPException(status_code=500, detail="Internal server error")

@router.get("/invoices", response_model=InvoicesResponse)
async def get_invoices(
    page: int = Query(1, ge=1),
    limit: int = Query(20, ge=1, le=100),
    search: Optional[str] = Query(None),
    status: Optional[str] = Query(None),
    client_id: Optional[str] = Query(None),
    db: AsyncIOMotorDatabase = Depends(get_database)
):
    """Get invoices with pagination and filtering"""
    try:
        invoice_service = InvoiceService(db)
        return await invoice_service.get_invoices(page, limit, search, status, client_id)
    except Exception as e:
        raise HTTPException(status_code=500, detail="Internal server error")

@router.get("/invoices/{invoice_id}", response_model=InvoiceResponse)
async def get_invoice(
    invoice_id: str,
    db: AsyncIOMotorDatabase = Depends(get_database)
):
    """Get invoice by ID"""
    try:
        invoice_service = InvoiceService(db)
        invoice = await invoice_service.get_invoice(invoice_id)
        if not invoice:
            raise HTTPException(status_code=404, detail="Invoice not found")
        return invoice
    except HTTPException:
        raise
    except Exception as e:
        raise HTTPException(status_code=500, detail="Internal server error")

@router.put("/invoices/{invoice_id}", response_model=InvoiceResponse)
async def update_invoice(
    invoice_id: str,
    invoice_data: InvoiceUpdate,
    db: AsyncIOMotorDatabase = Depends(get_database)
):
    """Update invoice"""
    try:
        invoice_service = InvoiceService(db)
        invoice = await invoice_service.update_invoice(invoice_id, invoice_data)
        if not invoice:
            raise HTTPException(status_code=404, detail="Invoice not found")
        return invoice
    except HTTPException:
        raise
    except Exception as e:
        raise HTTPException(status_code=500, detail="Internal server error")

@router.get("/stats", response_model=BillingStats)
async def get_billing_stats(
    db: AsyncIOMotorDatabase = Depends(get_database)
):
    """Get billing statistics"""
    try:
        invoice_service = InvoiceService(db)
        return await invoice_service.get_billing_stats()
    except Exception as e:
        raise HTTPException(status_code=500, detail="Internal server error")

# Payment endpoints (simplified for now)
@router.get("/payments")
async def get_payments(
    page: int = Query(1, ge=1),
    limit: int = Query(20, ge=1, le=100),
    search: Optional[str] = Query(None),
    status: Optional[str] = Query(None),
    db: AsyncIOMotorDatabase = Depends(get_database)
):
    """Get payments with pagination and filtering"""
    try:
        # For now, return mock data - can be expanded later
        return {
            "payments": [],
            "total": 0,
            "page": page,
            "limit": limit,
            "totalPages": 0
        }
    except Exception as e:
        raise HTTPException(status_code=500, detail="Internal server error")

# Transaction endpoints (simplified for now)
@router.get("/transactions")
async def get_transactions(
    page: int = Query(1, ge=1),
    limit: int = Query(20, ge=1, le=100),
    search: Optional[str] = Query(None),
    transaction_type: Optional[str] = Query(None),
    db: AsyncIOMotorDatabase = Depends(get_database)
):
    """Get transactions with pagination and filtering"""
    try:
        # For now, return mock data - can be expanded later
        return {
            "transactions": [],
            "total": 0,
            "page": page,
            "limit": limit,
            "totalPages": 0
        }
    except Exception as e:
        raise HTTPException(status_code=500, detail="Internal server error")

# Mark overdue invoices (background task endpoint)
@router.post("/mark-overdue")
async def mark_overdue_invoices(
    db: AsyncIOMotorDatabase = Depends(get_database)
):
    """Mark overdue invoices"""
    try:
        invoice_service = InvoiceService(db)
        await invoice_service.mark_overdue_invoices()
        return {"message": "Overdue invoices marked successfully"}
    except Exception as e:
        raise HTTPException(status_code=500, detail="Internal server error")