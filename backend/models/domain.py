from pydantic import BaseModel, Field
from typing import Optional, List
from datetime import datetime
from bson import ObjectId
from enum import Enum

class DomainStatus(str, Enum):
    ACTIVE = "Active"
    EXPIRED = "Expired"
    PENDING = "Pending"
    CANCELLED = "Cancelled"

class DomainBase(BaseModel):
    clientId: str = Field(..., description="Client ID")
    domain: str = Field(..., min_length=1, max_length=100)
    registrar: str = Field(..., min_length=1, max_length=50)
    status: DomainStatus = DomainStatus.PENDING
    registrationDate: datetime
    expiryDate: datetime
    autoRenew: bool = Field(default=True)
    nameservers: List[str] = Field(default=[])

class DomainCreate(DomainBase):
    pass

class DomainUpdate(BaseModel):
    registrar: Optional[str] = Field(None, min_length=1, max_length=50)
    status: Optional[DomainStatus] = None
    expiryDate: Optional[datetime] = None
    autoRenew: Optional[bool] = None
    nameservers: Optional[List[str]] = None

class DomainRenew(BaseModel):
    years: int = Field(..., ge=1, le=10)

class DomainAutoRenewToggle(BaseModel):
    autoRenew: bool

class Domain(DomainBase):
    id: str = Field(alias="_id")
    createdAt: datetime = Field(default_factory=datetime.utcnow)
    updatedAt: datetime = Field(default_factory=datetime.utcnow)

    class Config:
        populate_by_name = True
        json_encoders = {
            ObjectId: str
        }

class DomainResponse(BaseModel):
    id: str
    clientId: str
    clientName: Optional[str] = None
    domain: str
    registrar: str
    status: str
    registrationDate: datetime
    expiryDate: datetime
    autoRenew: bool
    nameservers: List[str]
    daysUntilExpiry: Optional[int] = None

class DomainsResponse(BaseModel):
    domains: List[DomainResponse]
    total: int
    page: int
    limit: int
    totalPages: int

class DomainStats(BaseModel):
    totalDomains: int
    activeDomains: int
    expiringSoon: int
    expiredDomains: int
    autoRenewEnabled: int