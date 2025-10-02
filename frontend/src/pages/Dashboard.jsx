import React, { useState, useEffect } from 'react';
import { Card, CardContent, CardHeader, CardTitle } from '../components/ui/card';
import { Badge } from '../components/ui/badge';
import { Button } from '../components/ui/button';
import {
  Users,
  CreditCard,
  Package,
  HelpCircle,
  DollarSign,
  TrendingUp,
  AlertCircle,
  CheckCircle,
  Clock,
  ArrowRight,
  RefreshCw
} from 'lucide-react';
import axios from 'axios';

const BACKEND_URL = process.env.REACT_APP_BACKEND_URL;
const API = `${BACKEND_URL}/api`;

const Dashboard = () => {
  const [dashboardStats, setDashboardStats] = useState(null);
  const [recentActivities, setRecentActivities] = useState([]);
  const [adminTasks, setAdminTasks] = useState([]);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState(null);

  const fetchDashboardData = async () => {
    try {
      setLoading(true);
      const response = await axios.get(`${API}/dashboard`);
      
      // Laravel returns { stats, recent_clients, recent_invoices, recent_tickets }
      const { stats, recent_clients, recent_invoices, recent_tickets } = response.data;
      
      // Transform Laravel response to match frontend expectations
      setDashboardStats({
        totalClients: stats.total_clients || 0,
        activeServices: stats.active_services || 0,
        monthlyRevenue: parseFloat(stats.total_revenue || 0),
        openTickets: stats.pending_tickets || 0,
        pendingInvoices: stats.unpaid_invoices || 0,
        cancellationRequests: 0, // Not in current API
        pendingOrders: 0 // Not in current API
      });
      
      // Transform recent activities from recent_clients and recent_invoices
      const activities = [
        ...(recent_clients || []).slice(0, 3).map((client, idx) => ({
          id: `client-${client.id}`,
          type: 'client',
          description: `New client registered: ${client.first_name} ${client.last_name}`,
          timestamp: new Date(client.created_at).toLocaleString(),
        })),
        ...(recent_invoices || []).slice(0, 3).map((invoice, idx) => ({
          id: `invoice-${invoice.id}`,
          type: 'payment',
          description: `Invoice #${invoice.invoice_id} - ${invoice.status}`,
          timestamp: new Date(invoice.created_at).toLocaleString(),
          amount: parseFloat(invoice.amount)
        }))
      ];
      
      setRecentActivities(activities);
      
      // Mock admin tasks for now
      setAdminTasks([
        { id: 1, task: 'Review pending invoices', dueDate: 'Today', priority: 'High', completed: false },
        { id: 2, task: 'Update server maintenance', dueDate: 'Tomorrow', priority: 'Medium', completed: false },
        { id: 3, task: 'Client support follow-up', dueDate: 'This week', priority: 'Low', completed: true }
      ]);
      
      setError(null);
    } catch (err) {
      console.error('Error fetching dashboard data:', err);
      setError('Failed to load dashboard data');
    } finally {
      setLoading(false);
    }
  };

  useEffect(() => {
    fetchDashboardData();
  }, []);

  if (loading) {
    return (
      <div className="flex items-center justify-center min-h-screen">
        <div className="flex items-center space-x-2">
          <RefreshCw className="w-6 h-6 animate-spin" />
          <span>Loading dashboard...</span>
        </div>
      </div>
    );
  }

  if (error) {
    return (
      <div className="flex items-center justify-center min-h-screen">
        <div className="text-center">
          <AlertCircle className="w-12 h-12 text-red-500 mx-auto mb-4" />
          <p className="text-red-600 mb-4">{error}</p>
          <Button onClick={fetchDashboardData}>
            <RefreshCw className="w-4 h-4 mr-2" />
            Retry
          </Button>
        </div>
      </div>
    );
  }

  const stats = dashboardStats ? [
    {
      title: 'Total Clients',
      value: dashboardStats.totalClients,
      icon: Users,
      color: 'bg-blue-500',
      change: '+12%',
      changeType: 'positive'
    },
    {
      title: 'Active Services',
      value: dashboardStats.activeServices,
      icon: Package,
      color: 'bg-green-500',
      change: '+8%',
      changeType: 'positive'
    },
    {
      title: 'Monthly Revenue',
      value: `$${dashboardStats.monthlyRevenue.toLocaleString()}`,
      icon: DollarSign,
      color: 'bg-purple-500',
      change: '+15%',
      changeType: 'positive'
    },
    {
      title: 'Open Tickets',
      value: dashboardStats.openTickets,
      icon: HelpCircle,
      color: 'bg-orange-500',
      change: '-5%',
      changeType: 'negative'
    }
  ] : [];

  const alerts = dashboardStats ? [
    {
      id: 1,
      type: 'warning',
      title: 'Pending Invoices',
      message: `${dashboardStats.pendingInvoices} invoices require attention`,
      action: 'View Invoices'
    },
    {
      id: 2,
      type: 'error',
      title: 'Cancellation Requests',
      message: `${dashboardStats.cancellationRequests} services pending cancellation`,
      action: 'Review Requests'
    },
    {
      id: 3,
      type: 'info',
      title: 'Pending Orders',
      message: `${dashboardStats.pendingOrders} orders awaiting processing`,
      action: 'Process Orders'
    }
  ] : [];

  const getAlertIcon = (type) => {
    switch (type) {
      case 'warning':
        return <AlertCircle className="w-5 h-5 text-yellow-600" />;
      case 'error':
        return <AlertCircle className="w-5 h-5 text-red-600" />;
      case 'info':
        return <Clock className="w-5 h-5 text-blue-600" />;
      default:
        return <AlertCircle className="w-5 h-5 text-gray-600" />;
    }
  };

  const getActivityIcon = (type) => {
    switch (type) {
      case 'payment':
        return <CreditCard className="w-4 h-4 text-green-600" />;
      case 'ticket':
        return <HelpCircle className="w-4 h-4 text-orange-600" />;
      case 'client':
        return <Users className="w-4 h-4 text-blue-600" />;
      case 'service':
        return <Package className="w-4 h-4 text-purple-600" />;
      default:
        return <Clock className="w-4 h-4 text-gray-600" />;
    }
  };

  return (
    <div className="space-y-6">
      {/* Header */}
      <div className="flex items-center justify-between">
        <div>
          <h1 className="text-3xl font-bold text-gray-900">Dashboard</h1>
          <p className="text-gray-600 mt-1">Welcome back! Here's what's happening with your business.</p>
        </div>
        <div className="flex space-x-3">
          <Button variant="outline">Export Report</Button>
          <Button>View Analytics</Button>
        </div>
      </div>

      {/* Stats Cards */}
      <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
        {stats.map((stat, index) => (
          <Card key={index} className="relative overflow-hidden">
            <CardContent className="p-6">
              <div className="flex items-center justify-between">
                <div>
                  <p className="text-sm font-medium text-gray-600">{stat.title}</p>
                  <p className="text-3xl font-bold text-gray-900 mt-2">{stat.value}</p>
                  <div className="flex items-center mt-2">
                    <TrendingUp className={`w-4 h-4 mr-1 ${
                      stat.changeType === 'positive' ? 'text-green-600' : 'text-red-600'
                    }`} />
                    <span className={`text-sm font-medium ${
                      stat.changeType === 'positive' ? 'text-green-600' : 'text-red-600'
                    }`}>
                      {stat.change}
                    </span>
                    <span className="text-sm text-gray-500 ml-1">vs last month</span>
                  </div>
                </div>
                <div className={`p-3 rounded-lg ${stat.color}`}>
                  <stat.icon className="w-6 h-6 text-white" />
                </div>
              </div>
            </CardContent>
          </Card>
        ))}
      </div>

      {/* Alerts & Quick Actions */}
      <div className="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <div className="lg:col-span-2">
          <Card>
            <CardHeader>
              <CardTitle className="flex items-center">
                <AlertCircle className="w-5 h-5 mr-2 text-orange-600" />
                Alerts & Actions Required
              </CardTitle>
            </CardHeader>
            <CardContent>
              <div className="space-y-4">
                {alerts.map((alert) => (
                  <div key={alert.id} className="flex items-center justify-between p-4 bg-gray-50 rounded-lg">
                    <div className="flex items-center space-x-3">
                      {getAlertIcon(alert.type)}
                      <div>
                        <h4 className="font-medium text-gray-900">{alert.title}</h4>
                        <p className="text-sm text-gray-600">{alert.message}</p>
                      </div>
                    </div>
                    <Button variant="outline" size="sm">
                      {alert.action}
                      <ArrowRight className="w-4 h-4 ml-2" />
                    </Button>
                  </div>
                ))}
              </div>
            </CardContent>
          </Card>
        </div>

        {/* Admin Tasks */}
        <Card>
          <CardHeader>
            <CardTitle>Admin Tasks</CardTitle>
          </CardHeader>
          <CardContent>
            <div className="space-y-3">
              {adminTasks.map((task) => (
                <div key={task.id} className="flex items-center space-x-3">
                  {task.completed ? (
                    <CheckCircle className="w-5 h-5 text-green-600" />
                  ) : (
                    <Clock className="w-5 h-5 text-gray-400" />
                  )}
                  <div className="flex-1 min-w-0">
                    <p className={`text-sm font-medium ${
                      task.completed ? 'text-gray-500 line-through' : 'text-gray-900'
                    }`}>
                      {task.task}
                    </p>
                    <p className="text-xs text-gray-500">Due: {task.dueDate}</p>
                  </div>
                  <Badge variant={task.priority === 'High' ? 'destructive' : 'secondary'}>
                    {task.priority}
                  </Badge>
                </div>
              ))}
            </div>
            <Button variant="outline" className="w-full mt-4">
              View All Tasks
            </Button>
          </CardContent>
        </Card>
      </div>

      {/* Recent Activities */}
      <Card>
        <CardHeader>
          <CardTitle>Recent Activities</CardTitle>
        </CardHeader>
        <CardContent>
          <div className="space-y-4">
            {recentActivities.map((activity) => (
              <div key={activity.id} className="flex items-center space-x-4 p-3 hover:bg-gray-50 rounded-lg transition-colors">
                <div className="p-2 bg-gray-100 rounded-lg">
                  {getActivityIcon(activity.type)}
                </div>
                <div className="flex-1 min-w-0">
                  <p className="text-sm font-medium text-gray-900">{activity.description}</p>
                  <p className="text-xs text-gray-500">{activity.timestamp}</p>
                </div>
                {activity.amount && (
                  <div className="text-right">
                    <p className="text-sm font-medium text-green-600">+${activity.amount}</p>
                  </div>
                )}
              </div>
            ))}
          </div>
          <Button variant="outline" className="w-full mt-4">
            View All Activities
          </Button>
        </CardContent>
      </Card>
    </div>
  );
};

export default Dashboard;