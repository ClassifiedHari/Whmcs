from typing import List, Optional, Dict, Any
from datetime import datetime, timedelta
from bson import ObjectId
from motor.motor_asyncio import AsyncIOMotorDatabase
from models.invoice import Invoice, InvoiceCreate, InvoiceUpdate, InvoiceResponse, InvoicesResponse, BillingStats
from services.base_service import BaseService
import math
import uuid

class InvoiceService(BaseService):
    def __init__(self, db: AsyncIOMotorDatabase):
        super().__init__(db, "invoices")
        self.db = db

    async def ensure_indexes(self):
        """Create necessary indexes"""
        await self.collection.create_index("invoiceId", unique=True)
        await self.collection.create_index("clientId")
        await self.collection.create_index("status")
        await self.collection.create_index("dueDate")

    def generate_invoice_id(self) -> str:
        """Generate unique invoice ID"""
        year = datetime.now().year
        return f"INV-{year}-{str(uuid.uuid4())[:8].upper()}"

    async def create_invoice(self, invoice_data: InvoiceCreate) -> InvoiceResponse:
        """Create a new invoice"""
        # Generate unique invoice ID
        invoice_id = self.generate_invoice_id()
        while await self.collection.find_one({"invoiceId": invoice_id}):
            invoice_id = self.generate_invoice_id()
        
        # Verify client exists
        client = await self.db.clients.find_one({"_id": ObjectId(invoice_data.clientId)})
        if not client:
            raise ValueError("Client not found")
        
        # Create invoice document
        invoice_dict = invoice_data.dict()
        invoice_dict["invoiceId"] = invoice_id
        invoice_dict["issueDate"] = datetime.utcnow()
        invoice_dict["createdAt"] = datetime.utcnow()
        invoice_dict["updatedAt"] = datetime.utcnow()
        
        result = await self.collection.insert_one(invoice_dict)
        
        # Return response with client name
        return InvoiceResponse(
            id=str(result.inserted_id),
            clientName=f"{client['firstName']} {client['lastName']}",
            **{k: v for k, v in invoice_dict.items() if k != "_id"}
        )

    async def get_invoices(self, page: int = 1, limit: int = 20, search: str = None, 
                          status: str = None, client_id: str = None) -> InvoicesResponse:
        """Get invoices with pagination and filtering"""
        skip = (page - 1) * limit
        query = self.get_base_query()
        
        # Add filters
        if status and status != "all":
            query["status"] = status
        if client_id:
            query["clientId"] = client_id
        
        # Add search functionality
        if search:
            query["$or"] = [
                {"invoiceId": {"$regex": search, "$options": "i"}},
                {"description": {"$regex": search, "$options": "i"}}
            ]
        
        # Get total count
        total = await self.collection.count_documents(query)
        
        # Get invoices with client information
        pipeline = [
            {"$match": query},
            {"$lookup": {
                "from": "clients",
                "localField": "clientId",
                "foreignField": "_id",
                "as": "client"
            }},
            {"$unwind": "$client"},
            {"$sort": {"createdAt": -1}},
            {"$skip": skip},
            {"$limit": limit}
        ]
        
        invoices = await self.collection.aggregate(pipeline).to_list(length=limit)
        
        # Convert to response format
        invoice_responses = [
            InvoiceResponse(
                id=str(invoice["_id"]),
                clientName=f"{invoice['client']['firstName']} {invoice['client']['lastName']}",
                **{k: v for k, v in invoice.items() if k not in ["_id", "client"]}
            )
            for invoice in invoices
        ]
        
        return InvoicesResponse(
            invoices=invoice_responses,
            total=total,
            page=page,
            limit=limit,
            totalPages=math.ceil(total / limit)
        )

    async def get_invoice(self, invoice_id: str) -> Optional[InvoiceResponse]:
        """Get invoice by ID"""
        try:
            pipeline = [
                {"$match": {"_id": ObjectId(invoice_id)}},
                {"$lookup": {
                    "from": "clients",
                    "localField": "clientId",
                    "foreignField": "_id",
                    "as": "client"
                }},
                {"$unwind": "$client"}
            ]
            
            result = await self.collection.aggregate(pipeline).to_list(1)
            if not result:
                return None
            
            invoice = result[0]
            return InvoiceResponse(
                id=str(invoice["_id"]),
                clientName=f"{invoice['client']['firstName']} {invoice['client']['lastName']}",
                **{k: v for k, v in invoice.items() if k not in ["_id", "client"]}
            )
        except Exception:
            return None

    async def update_invoice(self, invoice_id: str, invoice_data: InvoiceUpdate) -> Optional[InvoiceResponse]:
        """Update invoice"""
        try:
            update_data = {k: v for k, v in invoice_data.dict().items() if v is not None}
            update_data["updatedAt"] = datetime.utcnow()
            
            result = await self.collection.update_one(
                {"_id": ObjectId(invoice_id)},
                {"$set": update_data}
            )
            
            if result.modified_count == 0:
                return None
            
            # Update client stats if payment status changed
            if "status" in update_data:
                invoice = await self.collection.find_one({"_id": ObjectId(invoice_id)})
                if invoice:
                    from services.client_service import ClientService
                    client_service = ClientService(self.db)
                    await client_service.update_client_stats(invoice["clientId"])
            
            return await self.get_invoice(invoice_id)
        except Exception:
            return None

    async def get_billing_stats(self) -> BillingStats:
        """Get billing statistics"""
        try:
            # Current month dates
            now = datetime.utcnow()
            month_start = now.replace(day=1, hour=0, minute=0, second=0, microsecond=0)
            
            # Previous month dates
            if month_start.month == 1:
                prev_month_start = month_start.replace(year=month_start.year - 1, month=12)
            else:
                prev_month_start = month_start.replace(month=month_start.month - 1)
            
            # Monthly revenue (current month paid invoices)
            monthly_pipeline = [
                {"$match": {
                    "status": "Paid",
                    "issueDate": {"$gte": month_start}
                }},
                {"$group": {"_id": None, "total": {"$sum": "$amount"}}}
            ]
            monthly_result = await self.collection.aggregate(monthly_pipeline).to_list(1)
            monthly_revenue = monthly_result[0]["total"] if monthly_result else 0.0
            
            # Previous month revenue
            prev_monthly_pipeline = [
                {"$match": {
                    "status": "Paid",
                    "issueDate": {"$gte": prev_month_start, "$lt": month_start}
                }},
                {"$group": {"_id": None, "total": {"$sum": "$amount"}}}
            ]
            prev_monthly_result = await self.collection.aggregate(prev_monthly_pipeline).to_list(1)
            prev_monthly_revenue = prev_monthly_result[0]["total"] if prev_monthly_result else 0.0
            
            # Pending invoices count
            pending_count = await self.collection.count_documents({"status": "Pending"})
            
            # Overdue amount
            overdue_pipeline = [
                {"$match": {
                    "status": "Overdue"
                }},
                {"$group": {"_id": None, "total": {"$sum": "$amount"}}}
            ]
            overdue_result = await self.collection.aggregate(overdue_pipeline).to_list(1)
            overdue_amount = overdue_result[0]["total"] if overdue_result else 0.0
            
            # Collections rate (paid vs total invoices)
            total_invoices = await self.collection.count_documents({})
            paid_invoices = await self.collection.count_documents({"status": "Paid"})
            collections_rate = (paid_invoices / total_invoices * 100) if total_invoices > 0 else 0.0
            
            return BillingStats(
                monthlyRevenue=monthly_revenue,
                thisMonthRevenue=prev_monthly_revenue,
                pendingInvoices=pending_count,
                overdueAmount=overdue_amount,
                collectionsRate=round(collections_rate, 1)
            )
        except Exception:
            return BillingStats(
                monthlyRevenue=0.0,
                thisMonthRevenue=0.0,
                pendingInvoices=0,
                overdueAmount=0.0,
                collectionsRate=0.0
            )

    async def mark_overdue_invoices(self):
        """Mark invoices as overdue if past due date"""
        try:
            await self.collection.update_many(
                {
                    "status": "Pending",
                    "dueDate": {"$lt": datetime.utcnow()}
                },
                {"$set": {
                    "status": "Overdue",
                    "updatedAt": datetime.utcnow()
                }}
            )
        except Exception:
            pass