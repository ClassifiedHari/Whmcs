from pydantic import BaseModel, Field
from typing import Optional, List
from datetime import datetime
from bson import ObjectId
from enum import Enum

class InvoiceStatus(str, Enum):
    PAID = "Paid"
    PENDING = "Pending"
    OVERDUE = "Overdue"
    CANCELLED = "Cancelled"

class InvoiceItem(BaseModel):
    description: str = Field(..., min_length=1, max_length=200)
    amount: float = Field(..., gt=0)
    quantity: int = Field(default=1, gt=0)

class InvoiceBase(BaseModel):
    clientId: str = Field(..., description="Client ID")
    amount: float = Field(..., gt=0)
    status: InvoiceStatus = InvoiceStatus.PENDING
    dueDate: datetime
    description: str = Field(..., min_length=1, max_length=500)
    paymentMethod: Optional[str] = Field(None, max_length=50)
    items: List[InvoiceItem] = Field(default=[])

class InvoiceCreate(InvoiceBase):
    pass

class InvoiceUpdate(BaseModel):
    amount: Optional[float] = Field(None, gt=0)
    status: Optional[InvoiceStatus] = None
    dueDate: Optional[datetime] = None
    description: Optional[str] = Field(None, min_length=1, max_length=500)
    paymentMethod: Optional[str] = Field(None, max_length=50)
    items: Optional[List[InvoiceItem]] = None

class Invoice(InvoiceBase):
    id: str = Field(alias="_id")
    invoiceId: str = Field(..., description="Unique invoice identifier")
    issueDate: datetime = Field(default_factory=datetime.utcnow)
    createdAt: datetime = Field(default_factory=datetime.utcnow)
    updatedAt: datetime = Field(default_factory=datetime.utcnow)

    class Config:
        populate_by_name = True
        json_encoders = {
            ObjectId: str
        }

class InvoiceResponse(BaseModel):
    id: str
    invoiceId: str
    clientId: str
    clientName: Optional[str] = None
    amount: float
    status: str
    dueDate: datetime
    issueDate: datetime
    description: str
    paymentMethod: Optional[str]
    items: List[InvoiceItem]

class InvoicesResponse(BaseModel):
    invoices: List[InvoiceResponse]
    total: int
    page: int
    limit: int
    totalPages: int

class BillingStats(BaseModel):
    monthlyRevenue: float
    thisMonthRevenue: float
    pendingInvoices: int
    overdueAmount: float
    collectionsRate: float