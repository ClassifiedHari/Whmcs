from typing import Optional, Dict, Any
from bson import ObjectId
from motor.motor_asyncio import AsyncIOMotorDatabase, AsyncIOMotorCollection
from datetime import datetime

class BaseService:
    """Base service class with common database operations"""
    
    def __init__(self, db: AsyncIOMotorDatabase, collection_name: str):
        self.db = db
        self.collection: AsyncIOMotorCollection = db[collection_name]
        self.collection_name = collection_name
    
    async def ensure_indexes(self):
        """Ensure required indexes exist - to be overridden by subclasses"""
        pass
    
    def validate_object_id(self, id_str: str) -> bool:
        """Validate if string is a valid ObjectId"""
        try:
            ObjectId(id_str)
            return True
        except Exception:
            return False
    
    async def update_timestamps(self, document_id: str) -> bool:
        """Update the updatedAt timestamp for a document"""
        try:
            result = await self.collection.update_one(
                {"_id": ObjectId(document_id)},
                {"$set": {"updatedAt": datetime.utcnow()}}
            )
            return result.modified_count > 0
        except Exception:
            return False
    
    async def soft_delete(self, document_id: str) -> bool:
        """Soft delete a document by setting deleted flag"""
        try:
            result = await self.collection.update_one(
                {"_id": ObjectId(document_id)},
                {"$set": {
                    "deleted": True,
                    "deletedAt": datetime.utcnow(),
                    "updatedAt": datetime.utcnow()
                }}
            )
            return result.modified_count > 0
        except Exception:
            return False
    
    async def restore(self, document_id: str) -> bool:
        """Restore a soft-deleted document"""
        try:
            result = await self.collection.update_one(
                {"_id": ObjectId(document_id)},
                {"$unset": {"deleted": "", "deletedAt": ""},
                 "$set": {"updatedAt": datetime.utcnow()}}
            )
            return result.modified_count > 0
        except Exception:
            return False
    
    def get_base_query(self, include_deleted: bool = False) -> Dict[str, Any]:
        """Get base query that excludes soft-deleted items by default"""
        if include_deleted:
            return {}
        return {"deleted": {"$ne": True}}
    
    async def count_documents(self, query: Dict[str, Any] = None, include_deleted: bool = False) -> int:
        """Count documents with base filtering"""
        base_query = self.get_base_query(include_deleted)
        if query:
            base_query.update(query)
        return await self.collection.count_documents(base_query)
    
    async def health_check(self) -> Dict[str, Any]:
        """Check service health"""
        try:
            count = await self.collection.count_documents({})
            return {
                "service": self.__class__.__name__,
                "collection": self.collection_name,
                "status": "healthy",
                "document_count": count
            }
        except Exception as e:
            return {
                "service": self.__class__.__name__,
                "collection": self.collection_name,
                "status": "unhealthy",
                "error": str(e)
            }