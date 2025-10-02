import React, { useState, useEffect } from 'react';
import { Card, CardContent, CardHeader, CardTitle } from '../components/ui/card';
import { Button } from '../components/ui/button';
import { Input } from '../components/ui/input';
import { Badge } from '../components/ui/badge';
import axios from 'axios';
import {
  Table,
  TableBody,
  TableCell,
  TableHead,
  TableHeader,
  TableRow,
} from '../components/ui/table';
import {
  DropdownMenu,
  DropdownMenuContent,
  DropdownMenuItem,
  DropdownMenuSeparator,
  DropdownMenuTrigger,
} from '../components/ui/dropdown-menu';
import {
  Package,
  Search,
  Plus,
  MoreHorizontal,
  Eye,
  Edit,
  Power,
  RotateCcw,
  Trash2,
  Calendar,
  DollarSign,
  Server,
  Globe,
  Shield,
  RefreshCw,
  AlertCircle
} from 'lucide-react';

const BACKEND_URL = process.env.REACT_APP_BACKEND_URL;
const API = `${BACKEND_URL}/api`;

const Services = () => {
  const [searchTerm, setSearchTerm] = useState('');
  const [services, setServices] = useState([]);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState(null);
  const [pagination, setPagination] = useState({
    page: 1,
    limit: 20,
    total: 0,
    totalPages: 0
  });
  const [stats, setStats] = useState({
    total: 0,
    active: 0,
    suspended: 0,
    monthlyRecurring: 0
  });

  const fetchServices = async (page = 1) => {
    try {
      setLoading(true);
      const response = await axios.get(`${API}/services`, {
        params: { page }
      });
      
      // Laravel pagination format
      const { data, current_page, total, per_page, last_page } = response.data;
      
      // Transform Laravel snake_case to camelCase
      const transformedServices = data.map(service => ({
        id: service.id,
        clientId: service.client_id,
        productName: service.product_name || 'Service',
        domain: service.domain || 'N/A',
        status: service.status,
        nextDueDate: service.next_due_date,
        recurringAmount: parseFloat(service.recurring_amount || 0),
        billingCycle: service.billing_cycle || 'Monthly',
        registrationDate: service.registration_date,
        client: service.client ? {
          firstName: service.client.first_name,
          lastName: service.client.last_name,
          email: service.client.email
        } : null
      }));
      
      setServices(transformedServices);
      setPagination({
        page: current_page,
        limit: per_page,
        total: total,
        totalPages: last_page
      });
      
      // Calculate stats
      const activeCount = transformedServices.filter(s => s.status === 'Active').length;
      const suspendedCount = transformedServices.filter(s => s.status === 'Suspended').length;
      const monthlySum = transformedServices
        .filter(s => s.billingCycle === 'Monthly')
        .reduce((sum, s) => sum + s.recurringAmount, 0);
      
      setStats({
        total: total,
        active: activeCount,
        suspended: suspendedCount,
        monthlyRecurring: monthlySum
      });
      
      setError(null);
    } catch (err) {
      console.error('Error fetching services:', err);
      setError('Failed to load services');
      setServices([]);
    } finally {
      setLoading(false);
    }
  };

  useEffect(() => {
    fetchServices();
  }, []);

  const filteredServices = services.filter(service =>
    (service.productName?.toLowerCase().includes(searchTerm.toLowerCase()) ||
    service.domain?.toLowerCase().includes(searchTerm.toLowerCase()) ||
    service.client?.firstName?.toLowerCase().includes(searchTerm.toLowerCase()) ||
    service.client?.lastName?.toLowerCase().includes(searchTerm.toLowerCase()))
  );

  const getStatusBadge = (status) => {
    switch (status) {
      case 'Active':
        return <Badge className="bg-green-100 text-green-700 hover:bg-green-100">Active</Badge>;
      case 'Suspended':
        return <Badge className="bg-red-100 text-red-700 hover:bg-red-100">Suspended</Badge>;
      case 'Pending':
        return <Badge className="bg-yellow-100 text-yellow-700 hover:bg-yellow-100">Pending</Badge>;
      case 'Terminated':
        return <Badge className="bg-gray-100 text-gray-700 hover:bg-gray-100">Terminated</Badge>;
      default:
        return <Badge variant="secondary">{status}</Badge>;
    }
  };

  const getProductIcon = (productName) => {
    if (productName.toLowerCase().includes('hosting')) {
      return <Server className="w-4 h-4" />;
    } else if (productName.toLowerCase().includes('domain')) {
      return <Globe className="w-4 h-4" />;
    } else if (productName.toLowerCase().includes('ssl')) {
      return <Shield className="w-4 h-4" />;
    }
    return <Package className="w-4 h-4" />;
  };

  const serviceStats = [
    {
      title: 'Total Services',
      value: stats.total,
      icon: Package,
      color: 'text-blue-600'
    },
    {
      title: 'Active Services',
      value: stats.active,
      icon: Server,
      color: 'text-green-600'
    },
    {
      title: 'Suspended',
      value: stats.suspended,
      icon: Power,
      color: 'text-red-600'
    },
    {
      title: 'Monthly Recurring',
      value: `$${stats.monthlyRecurring.toLocaleString()}`,
      icon: DollarSign,
      color: 'text-purple-600'
    }
  ];

  return (
    <div className="space-y-6">
      {/* Header */}
      <div className="flex items-center justify-between">
        <div className="flex items-center space-x-3">
          <Package className="w-8 h-8 text-blue-600" />
          <div>
            <h1 className="text-3xl font-bold text-gray-900">Services</h1>
            <p className="text-gray-600">Manage client hosting services and products</p>
          </div>
        </div>
        <div className="flex space-x-3">
          <Button variant="outline">Bulk Actions</Button>
          <Button>
            <Plus className="w-4 h-4 mr-2" />
            Add Service
          </Button>
        </div>
      </div>

      {/* Service Stats */}
      <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
        {serviceStats.map((stat, index) => (
          <Card key={index}>
            <CardContent className="p-6">
              <div className="flex items-center justify-between">
                <div>
                  <p className="text-sm font-medium text-gray-600">{stat.title}</p>
                  <p className="text-2xl font-bold text-gray-900 mt-2">{stat.value}</p>
                </div>
                <div className="p-3 rounded-lg bg-gray-100">
                  <stat.icon className={`w-6 h-6 ${stat.color}`} />
                </div>
              </div>
            </CardContent>
          </Card>
        ))}
      </div>

      {/* Search and Filters */}
      <Card>
        <CardContent className="p-6">
          <div className="flex items-center space-x-4">
            <div className="relative flex-1">
              <Search className="absolute left-3 top-1/2 transform -translate-y-1/2 text-gray-400 w-4 h-4" />
              <Input
                placeholder="Search services by product, client, or domain..."
                value={searchTerm}
                onChange={(e) => setSearchTerm(e.target.value)}
                className="pl-10"
              />
            </div>
            <Button variant="outline">Filter by Status</Button>
            <Button variant="outline">Filter by Product</Button>
            <Button variant="outline">Export</Button>
          </div>
        </CardContent>
      </Card>

      {/* Services Table */}
      <Card>
        <CardHeader>
          <CardTitle className="flex items-center justify-between">
            <span>All Services ({pagination.total})</span>
            {loading && <RefreshCw className="w-4 h-4 animate-spin" />}
          </CardTitle>
        </CardHeader>
        <CardContent>
          {error ? (
            <div className="text-center py-8">
              <AlertCircle className="w-12 h-12 text-red-500 mx-auto mb-4" />
              <p className="text-red-600 mb-4">{error}</p>
              <Button onClick={() => fetchServices(pagination.page)}>
                <RefreshCw className="w-4 h-4 mr-2" />
                Retry
              </Button>
            </div>
          ) : (
            <Table>
              <TableHeader>
                <TableRow>
                  <TableHead>Service</TableHead>
                  <TableHead>Client</TableHead>
                  <TableHead>Domain</TableHead>
                  <TableHead>Status</TableHead>
                  <TableHead>Billing</TableHead>
                  <TableHead>Next Due</TableHead>
                  <TableHead className="text-right">Actions</TableHead>
                </TableRow>
              </TableHeader>
              <TableBody>
                {loading ? (
                  Array.from({ length: 5 }).map((_, index) => (
                    <TableRow key={index}>
                      <TableCell><div className="h-4 bg-gray-200 rounded animate-pulse"></div></TableCell>
                      <TableCell><div className="h-4 bg-gray-200 rounded animate-pulse"></div></TableCell>
                      <TableCell><div className="h-4 bg-gray-200 rounded animate-pulse"></div></TableCell>
                      <TableCell><div className="h-4 bg-gray-200 rounded animate-pulse"></div></TableCell>
                      <TableCell><div className="h-4 bg-gray-200 rounded animate-pulse"></div></TableCell>
                      <TableCell><div className="h-4 bg-gray-200 rounded animate-pulse"></div></TableCell>
                      <TableCell><div className="h-4 bg-gray-200 rounded animate-pulse"></div></TableCell>
                    </TableRow>
                  ))
                ) : filteredServices.length === 0 ? (
                  <TableRow>
                    <TableCell colSpan={7} className="text-center py-8 text-gray-500">
                      No services found
                    </TableCell>
                  </TableRow>
                ) : (
                  filteredServices.map((service) => (
                <TableRow key={service.id} className="hover:bg-gray-50">
                  <TableCell>
                    <div className="flex items-center space-x-3">
                      <div className="p-2 bg-gray-100 rounded-lg">
                        {getProductIcon(service.productName)}
                      </div>
                      <div>
                        <div className="font-medium text-gray-900">{service.productName}</div>
                        <div className="text-sm text-gray-500">ID: {service.id}</div>
                      </div>
                    </div>
                  </TableCell>
                  <TableCell>
                    <div className="font-medium text-gray-900">
                      {service.client ? `${service.client.firstName} ${service.client.lastName}` : 'N/A'}
                    </div>
                  </TableCell>
                  <TableCell>
                    <div className="flex items-center space-x-2">
                      <Globe className="w-4 h-4 text-gray-400" />
                      <span className="font-medium">{service.domain}</span>
                    </div>
                  </TableCell>
                  <TableCell>
                    {getStatusBadge(service.status)}
                  </TableCell>
                  <TableCell>
                    <div>
                      <div className="font-medium">₹{service.recurringAmount.toLocaleString('en-IN')}</div>
                      <div className="text-sm text-gray-500">{service.billingCycle}</div>
                    </div>
                  </TableCell>
                  <TableCell>
                    <div className={`text-sm ${
                      new Date(service.nextDueDate) < new Date() 
                        ? 'text-red-600 font-medium' 
                        : 'text-gray-600'
                    }`}>
                      {service.nextDueDate}
                    </div>
                  </TableCell>
                  <TableCell className="text-right">
                    <DropdownMenu>
                      <DropdownMenuTrigger asChild>
                        <Button variant="ghost" size="sm">
                          <MoreHorizontal className="w-4 h-4" />
                        </Button>
                      </DropdownMenuTrigger>
                      <DropdownMenuContent align="end">
                        <DropdownMenuItem>
                          <Eye className="w-4 h-4 mr-2" />
                          View Details
                        </DropdownMenuItem>
                        <DropdownMenuItem>
                          <Edit className="w-4 h-4 mr-2" />
                          Edit Service
                        </DropdownMenuItem>
                        <DropdownMenuSeparator />
                        {service.status === 'Active' ? (
                          <DropdownMenuItem className="text-orange-600">
                            <Power className="w-4 h-4 mr-2" />
                            Suspend Service
                          </DropdownMenuItem>
                        ) : (
                          <DropdownMenuItem className="text-green-600">
                            <RotateCcw className="w-4 h-4 mr-2" />
                            Reactivate Service
                          </DropdownMenuItem>
                        )}
                        <DropdownMenuItem>
                          <Calendar className="w-4 h-4 mr-2" />
                          Change Due Date
                        </DropdownMenuItem>
                        <DropdownMenuItem>
                          <DollarSign className="w-4 h-4 mr-2" />
                          Generate Invoice
                        </DropdownMenuItem>
                        <DropdownMenuSeparator />
                        <DropdownMenuItem className="text-red-600">
                          <Trash2 className="w-4 h-4 mr-2" />
                          Terminate Service
                        </DropdownMenuItem>
                      </DropdownMenuContent>
                    </DropdownMenu>
                  </TableCell>
                </TableRow>
              ))
                )}
            </TableBody>
          </Table>
          )}
        </CardContent>
      </Card>
    </div>
  );
};

export default Services;