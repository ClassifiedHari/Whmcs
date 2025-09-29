from pydantic import BaseModel, Field
from typing import Optional, List
from datetime import datetime
from bson import ObjectId
from enum import Enum

class TicketStatus(str, Enum):
    OPEN = "Open"
    IN_PROGRESS = "In Progress"
    RESOLVED = "Resolved"
    CLOSED = "Closed"

class TicketPriority(str, Enum):
    LOW = "Low"
    MEDIUM = "Medium"
    HIGH = "High"
    URGENT = "Urgent"

class TicketReply(BaseModel):
    message: str = Field(..., min_length=1)
    sender: str = Field(..., min_length=1, max_length=100)
    timestamp: datetime = Field(default_factory=datetime.utcnow)
    isAdmin: bool = Field(default=False)

class TicketBase(BaseModel):
    clientId: str = Field(..., description="Client ID")
    subject: str = Field(..., min_length=1, max_length=200)
    status: TicketStatus = TicketStatus.OPEN
    priority: TicketPriority = TicketPriority.MEDIUM
    department: str = Field(..., min_length=1, max_length=50)
    category: Optional[str] = Field(None, max_length=50)
    assignedTo: Optional[str] = Field(None, max_length=100)

class TicketCreate(TicketBase):
    message: str = Field(..., min_length=1, description="Initial message")

class TicketUpdate(BaseModel):
    subject: Optional[str] = Field(None, min_length=1, max_length=200)
    status: Optional[TicketStatus] = None
    priority: Optional[TicketPriority] = None
    department: Optional[str] = Field(None, min_length=1, max_length=50)
    category: Optional[str] = Field(None, max_length=50)
    assignedTo: Optional[str] = Field(None, max_length=100)

class TicketReplyCreate(BaseModel):
    message: str = Field(..., min_length=1)
    status: Optional[TicketStatus] = None

class Ticket(TicketBase):
    id: str = Field(alias="_id")
    ticketId: str = Field(..., description="Unique ticket identifier")
    replies: List[TicketReply] = Field(default=[])
    createdAt: datetime = Field(default_factory=datetime.utcnow)
    updatedAt: datetime = Field(default_factory=datetime.utcnow)

    class Config:
        populate_by_name = True
        json_encoders = {
            ObjectId: str
        }

class TicketResponse(BaseModel):
    id: str
    ticketId: str
    clientId: str
    clientName: Optional[str] = None
    subject: str
    status: str
    priority: str
    department: str
    category: Optional[str]
    assignedTo: Optional[str]
    created: datetime
    lastReply: Optional[datetime]
    replyCount: int

class TicketDetailResponse(TicketResponse):
    replies: List[TicketReply]

class TicketsResponse(BaseModel):
    tickets: List[TicketResponse]
    total: int
    page: int
    limit: int
    totalPages: int

class SupportStats(BaseModel):
    openTickets: int
    inProgressTickets: int
    resolvedToday: int
    avgResponseTime: str
    ticketsByPriority: dict
    ticketsByDepartment: dict