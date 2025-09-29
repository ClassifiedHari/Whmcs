import os
from motor.motor_asyncio import AsyncIOMotorClient
from fastapi import Depends

# MongoDB connection
mongo_url = os.environ['MONGO_URL']
client = AsyncIOMotorClient(mongo_url)
db = client[os.environ.get('DB_NAME', 'whmcs_admin')]

async def get_database():
    """Dependency to get database instance"""
    return db

async def get_client():
    """Dependency to get MongoDB client"""
    return client