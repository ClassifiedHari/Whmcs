from fastapi import APIRouter, HTTPException, Depends, Query
from typing import Optional, List, Dict, Any
from motor.motor_asyncio import AsyncIOMotorDatabase
from services.dashboard_service import DashboardService
from dependencies import get_database

router = APIRouter(prefix="/dashboard", tags=["dashboard"])

@router.get("/stats")
async def get_dashboard_stats(
    db: AsyncIOMotorDatabase = Depends(get_database)
) -> Dict[str, Any]:
    """Get dashboard statistics"""
    try:
        dashboard_service = DashboardService(db)
        return await dashboard_service.get_dashboard_stats()
    except Exception as e:
        raise HTTPException(status_code=500, detail="Internal server error")

@router.get("/activities")
async def get_recent_activities(
    limit: int = Query(10, ge=1, le=50),
    db: AsyncIOMotorDatabase = Depends(get_database)
) -> List[Dict[str, Any]]:
    """Get recent activities"""
    try:
        dashboard_service = DashboardService(db)
        return await dashboard_service.get_recent_activities(limit)
    except Exception as e:
        raise HTTPException(status_code=500, detail="Internal server error")

@router.get("/tasks")
async def get_admin_tasks(
    db: AsyncIOMotorDatabase = Depends(get_database)
) -> List[Dict[str, Any]]:
    """Get admin tasks"""
    try:
        dashboard_service = DashboardService(db)
        return await dashboard_service.get_admin_tasks()
    except Exception as e:
        raise HTTPException(status_code=500, detail="Internal server error")

@router.put("/tasks/{task_id}")
async def update_task_status(
    task_id: str,
    completed: bool,
    db: AsyncIOMotorDatabase = Depends(get_database)
):
    """Update task completion status"""
    try:
        dashboard_service = DashboardService(db)
        success = await dashboard_service.update_task_status(task_id, completed)
        if not success:
            raise HTTPException(status_code=404, detail="Task not found")
        return {"message": "Task updated successfully"}
    except HTTPException:
        raise
    except Exception as e:
        raise HTTPException(status_code=500, detail="Internal server error")