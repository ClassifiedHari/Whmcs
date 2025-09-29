from pydantic import BaseModel, Field, EmailStr
from typing import Optional, List
from datetime import datetime
from bson import ObjectId
from enum import Enum

class ClientStatus(str, Enum):
    ACTIVE = "Active"
    SUSPENDED = "Suspended"
    INACTIVE = "Inactive"

class ClientBase(BaseModel):
    firstName: str = Field(..., min_length=1, max_length=50)
    lastName: str = Field(..., min_length=1, max_length=50)
    email: EmailStr
    company: Optional[str] = Field(None, max_length=100)
    phone: Optional[str] = Field(None, max_length=20)
    address: Optional[str] = Field(None, max_length=500)
    status: ClientStatus = ClientStatus.ACTIVE

class ClientCreate(ClientBase):
    pass

class ClientUpdate(BaseModel):
    firstName: Optional[str] = Field(None, min_length=1, max_length=50)
    lastName: Optional[str] = Field(None, min_length=1, max_length=50)
    email: Optional[EmailStr] = None
    company: Optional[str] = Field(None, max_length=100)
    phone: Optional[str] = Field(None, max_length=20)
    address: Optional[str] = Field(None, max_length=500)
    status: Optional[ClientStatus] = None

class Client(ClientBase):
    id: str = Field(alias="_id")
    registrationDate: datetime = Field(default_factory=datetime.utcnow)
    lastLogin: Optional[datetime] = None
    totalSpent: float = Field(default=0.0)
    activeServices: int = Field(default=0)
    createdAt: datetime = Field(default_factory=datetime.utcnow)
    updatedAt: datetime = Field(default_factory=datetime.utcnow)

    class Config:
        populate_by_name = True
        json_encoders = {
            ObjectId: str
        }

class ClientResponse(BaseModel):
    id: str
    firstName: str
    lastName: str
    email: str
    company: Optional[str]
    phone: Optional[str]
    address: Optional[str]
    status: str
    registrationDate: datetime
    lastLogin: Optional[datetime]
    totalSpent: float
    activeServices: int

class ClientsResponse(BaseModel):
    clients: List[ClientResponse]
    total: int
    page: int
    limit: int
    totalPages: int