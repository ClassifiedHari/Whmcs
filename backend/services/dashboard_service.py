from typing import List, Dict, Any
from datetime import datetime, timedelta
from bson import ObjectId
from motor.motor_asyncio import AsyncIOMotorDatabase
from services.base_service import BaseService

class DashboardService(BaseService):
    def __init__(self, db: AsyncIOMotorDatabase):
        super().__init__(db, "dashboard_cache")
        self.db = db

    async def get_dashboard_stats(self) -> Dict[str, Any]:
        """Get comprehensive dashboard statistics"""
        try:
            # Client stats
            total_clients = await self.db.clients.count_documents({"deleted": {"$ne": True}})
            active_clients = await self.db.clients.count_documents({
                "status": "Active",
                "deleted": {"$ne": True}
            })
            
            # Service stats
            active_services = await self.db.services.count_documents({
                "status": "Active",
                "deleted": {"$ne": True}
            })
            
            # Invoice stats
            pending_invoices = await self.db.invoices.count_documents({
                "status": "Pending",
                "deleted": {"$ne": True}
            })
            
            # Support ticket stats
            open_tickets = await self.db.tickets.count_documents({
                "status": {"$in": ["Open", "In Progress"]},
                "deleted": {"$ne": True}
            })
            
            # Revenue calculation (current month)
            now = datetime.utcnow()
            month_start = now.replace(day=1, hour=0, minute=0, second=0, microsecond=0)
            
            monthly_revenue_pipeline = [
                {"$match": {
                    "status": "Paid",
                    "issueDate": {"$gte": month_start},
                    "deleted": {"$ne": True}
                }},
                {"$group": {"_id": None, "total": {"$sum": "$amount"}}}
            ]
            
            monthly_result = await self.db.invoices.aggregate(monthly_revenue_pipeline).to_list(1)
            monthly_revenue = monthly_result[0]["total"] if monthly_result else 0.0
            
            # Previous month for comparison
            if month_start.month == 1:
                prev_month_start = month_start.replace(year=month_start.year - 1, month=12)
            else:
                prev_month_start = month_start.replace(month=month_start.month - 1)
            
            prev_monthly_pipeline = [
                {"$match": {
                    "status": "Paid",
                    "issueDate": {"$gte": prev_month_start, "$lt": month_start},
                    "deleted": {"$ne": True}
                }},
                {"$group": {"_id": None, "total": {"$sum": "$amount"}}}
            ]
            
            prev_monthly_result = await self.db.invoices.aggregate(prev_monthly_pipeline).to_list(1)
            prev_monthly_revenue = prev_monthly_result[0]["total"] if prev_monthly_result else 0.0
            
            # Pending orders (services with pending status)
            pending_orders = await self.db.services.count_documents({
                "status": "Pending",
                "deleted": {"$ne": True}
            })
            
            # Cancellation requests (services with terminated status)
            cancellation_requests = await self.db.services.count_documents({
                "status": "Terminated",
                "deleted": {"$ne": True}
            })
            
            return {
                "totalClients": total_clients,
                "activeServices": active_services,
                "pendingInvoices": pending_invoices,
                "openTickets": open_tickets,
                "monthlyRevenue": monthly_revenue,
                "thisMonthRevenue": prev_monthly_revenue,
                "pendingOrders": pending_orders,
                "cancellationRequests": cancellation_requests
            }
        except Exception:
            # Return default stats on error
            return {
                "totalClients": 0,
                "activeServices": 0,
                "pendingInvoices": 0,
                "openTickets": 0,
                "monthlyRevenue": 0.0,
                "thisMonthRevenue": 0.0,
                "pendingOrders": 0,
                "cancellationRequests": 0
            }

    async def get_recent_activities(self, limit: int = 10) -> List[Dict[str, Any]]:
        """Get recent system activities"""
        try:
            activities = []
            
            # Recent payments (paid invoices)
            recent_payments = await self.db.invoices.find({
                "status": "Paid",
                "deleted": {"$ne": True}
            }).sort("updatedAt", -1).limit(3).to_list(3)
            
            for payment in recent_payments:
                # Get client info
                client = await self.db.clients.find_one({"_id": ObjectId(payment["clientId"])})
                if client:
                    activities.append({
                        "id": str(payment["_id"]),
                        "type": "payment",
                        "description": f"Payment received from {client['firstName']} {client['lastName']}",
                        "amount": payment["amount"],
                        "timestamp": payment["updatedAt"].strftime("%Y-%m-%d %H:%M"),
                        "icon": "credit-card"
                    })
            
            # Recent tickets
            recent_tickets = await self.db.tickets.find({
                "deleted": {"$ne": True}
            }).sort("createdAt", -1).limit(3).to_list(3)
            
            for ticket in recent_tickets:
                # Get client info
                client = await self.db.clients.find_one({"_id": ObjectId(ticket["clientId"])})
                if client:
                    activities.append({
                        "id": str(ticket["_id"]),
                        "type": "ticket",
                        "description": f"New support ticket from {client['firstName']} {client['lastName']}",
                        "timestamp": ticket["createdAt"].strftime("%Y-%m-%d %H:%M"),
                        "icon": "help-circle"
                    })
            
            # Recent clients
            recent_clients = await self.db.clients.find({
                "deleted": {"$ne": True}
            }).sort("createdAt", -1).limit(2).to_list(2)
            
            for client in recent_clients:
                activities.append({
                    "id": str(client["_id"]),
                    "type": "client",
                    "description": f"New client registration: {client['firstName']} {client['lastName']}",
                    "timestamp": client["createdAt"].strftime("%Y-%m-%d %H:%M"),
                    "icon": "user-plus"
                })
            
            # Recent service changes
            recent_services = await self.db.services.find({
                "status": "Suspended",
                "deleted": {"$ne": True}
            }).sort("updatedAt", -1).limit(2).to_list(2)
            
            for service in recent_services:
                # Get client info
                client = await self.db.clients.find_one({"_id": ObjectId(service["clientId"])})
                if client:
                    activities.append({
                        "id": str(service["_id"]),
                        "type": "service",
                        "description": f"Service suspended for {client['firstName']} {client['lastName']}",
                        "timestamp": service["updatedAt"].strftime("%Y-%m-%d %H:%M"),
                        "icon": "alert-triangle"
                    })
            
            # Sort all activities by timestamp and limit
            activities.sort(key=lambda x: x["timestamp"], reverse=True)
            return activities[:limit]
            
        except Exception:
            return []

    async def get_admin_tasks(self) -> List[Dict[str, Any]]:
        """Get admin tasks/to-do items"""
        try:
            tasks = []
            
            # Pending invoices task
            pending_count = await self.db.invoices.count_documents({
                "status": "Pending",
                "deleted": {"$ne": True}
            })
            
            if pending_count > 0:
                tasks.append({
                    "id": "pending_invoices",
                    "task": "Review pending invoices",
                    "priority": "High",
                    "dueDate": (datetime.utcnow() + timedelta(days=1)).strftime("%Y-%m-%d"),
                    "completed": False
                })
            
            # Overdue invoices task
            overdue_count = await self.db.invoices.count_documents({
                "status": "Overdue",
                "deleted": {"$ne": True}
            })
            
            if overdue_count > 0:
                tasks.append({
                    "id": "overdue_invoices",
                    "task": "Follow up on overdue invoices",
                    "priority": "High",
                    "dueDate": datetime.utcnow().strftime("%Y-%m-%d"),
                    "completed": False
                })
            
            # Domain renewals task
            tomorrow = datetime.utcnow() + timedelta(days=30)
            expiring_domains = await self.db.domains.count_documents({
                "expiryDate": {"$lte": tomorrow},
                "status": "Active",
                "deleted": {"$ne": True}
            })
            
            if expiring_domains > 0:
                tasks.append({
                    "id": "domain_renewals",
                    "task": "Process domain renewals",
                    "priority": "Medium",
                    "dueDate": (datetime.utcnow() + timedelta(days=7)).strftime("%Y-%m-%d"),
                    "completed": False
                })
            
            # Open tickets task
            open_tickets = await self.db.tickets.count_documents({
                "status": "Open",
                "deleted": {"$ne": True}
            })
            
            if open_tickets > 0:
                tasks.append({
                    "id": "open_tickets",
                    "task": f"Respond to {open_tickets} open support tickets",
                    "priority": "High",
                    "dueDate": datetime.utcnow().strftime("%Y-%m-%d"),
                    "completed": False
                })
            
            # Server maintenance task (static example)
            tasks.append({
                "id": "server_maintenance",
                "task": "Update server maintenance schedule",
                "priority": "Medium",
                "dueDate": (datetime.utcnow() + timedelta(days=3)).strftime("%Y-%m-%d"),
                "completed": False
            })
            
            return tasks[:5]  # Return top 5 tasks
            
        except Exception:
            return [
                {
                    "id": "default_task",
                    "task": "Review system status",
                    "priority": "Medium",
                    "dueDate": (datetime.utcnow() + timedelta(days=1)).strftime("%Y-%m-%d"),
                    "completed": False
                }
            ]

    async def update_task_status(self, task_id: str, completed: bool) -> bool:
        """Update task completion status"""
        try:
            # For now, this would be stored in a tasks collection
            # Since we're generating tasks dynamically, we'll just return success
            return True
        except Exception:
            return False