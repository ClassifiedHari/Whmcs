from pydantic import BaseModel, Field
from typing import Optional, List
from datetime import datetime
from bson import ObjectId
from enum import Enum

class ServiceStatus(str, Enum):
    ACTIVE = "Active"
    SUSPENDED = "Suspended"
    PENDING = "Pending"
    TERMINATED = "Terminated"

class BillingCycle(str, Enum):
    MONTHLY = "Monthly"
    ANNUALLY = "Annually"
    QUARTERLY = "Quarterly"
    BIANNUALLY = "Biannually"

class ServiceBase(BaseModel):
    clientId: str = Field(..., description="Client ID")
    productName: str = Field(..., min_length=1, max_length=100)
    domain: Optional[str] = Field(None, max_length=100)
    status: ServiceStatus = ServiceStatus.PENDING
    nextDueDate: datetime
    recurringAmount: float = Field(..., gt=0)
    billingCycle: BillingCycle = BillingCycle.MONTHLY

class ServiceCreate(ServiceBase):
    pass

class ServiceUpdate(BaseModel):
    productName: Optional[str] = Field(None, min_length=1, max_length=100)
    domain: Optional[str] = Field(None, max_length=100)
    status: Optional[ServiceStatus] = None
    nextDueDate: Optional[datetime] = None
    recurringAmount: Optional[float] = Field(None, gt=0)
    billingCycle: Optional[BillingCycle] = None

class ServiceStatusUpdate(BaseModel):
    status: ServiceStatus

class Service(ServiceBase):
    id: str = Field(alias="_id")
    registrationDate: datetime = Field(default_factory=datetime.utcnow)
    createdAt: datetime = Field(default_factory=datetime.utcnow)
    updatedAt: datetime = Field(default_factory=datetime.utcnow)

    class Config:
        populate_by_name = True
        json_encoders = {
            ObjectId: str
        }

class ServiceResponse(BaseModel):
    id: str
    clientId: str
    clientName: Optional[str] = None
    productName: str
    domain: Optional[str]
    status: str
    nextDueDate: datetime
    recurringAmount: float
    billingCycle: str
    registrationDate: datetime

class ServicesResponse(BaseModel):
    services: List[ServiceResponse]
    total: int
    page: int
    limit: int
    totalPages: int

class ServiceStats(BaseModel):
    totalServices: int
    activeServices: int
    suspendedServices: int
    monthlyRecurring: float
    annualRecurring: float