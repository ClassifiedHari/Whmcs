from typing import List, Optional, Dict, Any
from datetime import datetime
from bson import ObjectId
from motor.motor_asyncio import AsyncIOMotorDatabase
from models.client import Client, ClientCreate, ClientUpdate, ClientResponse, ClientsResponse
from services.base_service import BaseService
import math
import csv
import io

class ClientService(BaseService):
    def __init__(self, db: AsyncIOMotorDatabase):
        super().__init__(db, "clients")
        self.db = db

    async def create_client(self, client_data: ClientCreate) -> ClientResponse:
        """Create a new client"""
        # Check if email already exists
        existing = await self.collection.find_one({"email": client_data.email})
        if existing:
            raise ValueError(f"Client with email {client_data.email} already exists")
        
        # Create client document
        client_dict = client_data.dict()
        client_dict["registrationDate"] = datetime.utcnow()
        client_dict["createdAt"] = datetime.utcnow()
        client_dict["updatedAt"] = datetime.utcnow()
        client_dict["totalSpent"] = 0.0
        client_dict["activeServices"] = 0
        
        result = await self.collection.insert_one(client_dict)
        client_dict["_id"] = result.inserted_id
        return ClientResponse(
            id=str(result.inserted_id),
            **{k: v for k, v in client_dict.items() if k != "_id"}
        )

    async def get_clients(self, page: int = 1, limit: int = 20, search: str = None, status: str = None) -> ClientsResponse:
        """Get clients with pagination and filtering"""
        skip = (page - 1) * limit
        query = {}
        
        # Add search functionality
        if search:
            query["$or"] = [
                {"firstName": {"$regex": search, "$options": "i"}},
                {"lastName": {"$regex": search, "$options": "i"}},
                {"email": {"$regex": search, "$options": "i"}},
                {"company": {"$regex": search, "$options": "i"}}
            ]
        
        # Add status filter
        if status and status != "all":
            query["status"] = status
        
        # Get total count
        total = await self.collection.count_documents(query)
        
        # Get clients
        cursor = self.collection.find(query).skip(skip).limit(limit).sort("createdAt", -1)
        clients = await cursor.to_list(length=limit)
        
        # Convert to response format
        client_responses = [
            ClientResponse(
                id=str(client["_id"]),
                **{k: v for k, v in client.items() if k != "_id"}
            )
            for client in clients
        ]
        
        return ClientsResponse(
            clients=client_responses,
            total=total,
            page=page,
            limit=limit,
            totalPages=math.ceil(total / limit)
        )

    async def get_client(self, client_id: str) -> Optional[ClientResponse]:
        """Get client by ID"""
        try:
            client = await self.collection.find_one({"_id": ObjectId(client_id)})
            if not client:
                return None
            
            return ClientResponse(
                id=str(client["_id"]),
                **{k: v for k, v in client.items() if k != "_id"}
            )
        except Exception:
            return None

    async def update_client(self, client_id: str, client_data: ClientUpdate) -> Optional[ClientResponse]:
        """Update client"""
        try:
            # Check if email is being updated and already exists
            if client_data.email:
                existing = await self.collection.find_one({
                    "email": client_data.email,
                    "_id": {"$ne": ObjectId(client_id)}
                })
                if existing:
                    raise ValueError(f"Client with email {client_data.email} already exists")
            
            update_data = {k: v for k, v in client_data.dict().items() if v is not None}
            update_data["updatedAt"] = datetime.utcnow()
            
            result = await self.collection.update_one(
                {"_id": ObjectId(client_id)},
                {"$set": update_data}
            )
            
            if result.modified_count == 0:
                return None
            
            return await self.get_client(client_id)
        except Exception:
            return None

    async def delete_client(self, client_id: str) -> bool:
        """Delete client"""
        try:
            result = await self.collection.delete_one({"_id": ObjectId(client_id)})
            return result.deleted_count > 0
        except Exception:
            return False

    async def update_client_stats(self, client_id: str):
        """Update client statistics (total spent, active services)"""
        try:
            # Get total spent from invoices
            pipeline = [
                {"$match": {"clientId": client_id, "status": "Paid"}},
                {"$group": {"_id": None, "total": {"$sum": "$amount"}}}
            ]
            result = await self.db.invoices.aggregate(pipeline).to_list(1)
            total_spent = result[0]["total"] if result else 0.0
            
            # Get active services count
            active_services = await self.db.services.count_documents({
                "clientId": client_id,
                "status": "Active"
            })
            
            # Update client
            await self.collection.update_one(
                {"_id": ObjectId(client_id)},
                {"$set": {
                    "totalSpent": total_spent,
                    "activeServices": active_services,
                    "updatedAt": datetime.utcnow()
                }}
            )
        except Exception:
            pass

    async def import_from_csv(self, csv_content: str) -> Dict[str, Any]:
        """Import clients from CSV"""
        results = {
            "total": 0,
            "created": 0,
            "updated": 0,
            "errors": []
        }
        
        try:
            csv_reader = csv.DictReader(io.StringIO(csv_content))
            
            for row_num, row in enumerate(csv_reader, start=2):
                results["total"] += 1
                
                try:
                    # Validate required fields
                    if not row.get('email'):
                        results["errors"].append(f"Row {row_num}: Email is required")
                        continue
                    
                    # Check if client exists
                    existing = await self.collection.find_one({"email": row['email']})
                    
                    client_data = {
                        "firstName": row.get('firstName', ''),
                        "lastName": row.get('lastName', ''),
                        "email": row['email'],
                        "company": row.get('company', ''),
                        "phone": row.get('phone', ''),
                        "address": row.get('address', ''),
                        "status": row.get('status', 'Active')
                    }
                    
                    if existing:
                        # Update existing client
                        client_data["updatedAt"] = datetime.utcnow()
                        await self.collection.update_one(
                            {"_id": existing["_id"]},
                            {"$set": client_data}
                        )
                        results["updated"] += 1
                    else:
                        # Create new client
                        client_data.update({
                            "registrationDate": datetime.utcnow(),
                            "createdAt": datetime.utcnow(),
                            "updatedAt": datetime.utcnow(),
                            "totalSpent": 0.0,
                            "activeServices": 0
                        })
                        await self.collection.insert_one(client_data)
                        results["created"] += 1
                        
                except Exception as e:
                    results["errors"].append(f"Row {row_num}: {str(e)}")
                    
        except Exception as e:
            results["errors"].append(f"CSV parsing error: {str(e)}")
        
        return results

    async def get_client_by_email(self, email: str) -> Optional[ClientResponse]:
        """Get client by email"""
        client = await self.collection.find_one({"email": email})
        if not client:
            return None
        
        return ClientResponse(
            id=str(client["_id"]),
            **{k: v for k, v in client.items() if k != "_id"}
        )