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
  Globe,
  Search,
  Plus,
  MoreHorizontal,
  Eye,
  Edit,
  RefreshCw,
  Calendar,
  AlertTriangle,
  CheckCircle,
  Clock,
  ExternalLink,
  Settings,
  AlertCircle
} from 'lucide-react';

const BACKEND_URL = process.env.REACT_APP_BACKEND_URL;
const API = `${BACKEND_URL}/api`;

const Domains = () => {
  const [searchTerm, setSearchTerm] = useState('');
  const [domains, setDomains] = useState([]);
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
    expiringSoon: 0,
    autoRenew: 0
  });

  const fetchDomains = async (page = 1) => {
    try {
      setLoading(true);
      const response = await axios.get(`${API}/domains`, {
        params: { page }
      });
      
      // Laravel pagination format
      const { data, current_page, total, per_page, last_page } = response.data;
      
      // Transform Laravel snake_case to camelCase
      const transformedDomains = data.map(domain => ({
        id: domain.id,
        clientId: domain.client_id,
        domain: domain.domain,
        registrar: domain.registrar || 'N/A',
        status: domain.status,
        registrationDate: domain.registration_date,
        expiryDate: domain.expiry_date,
        autoRenew: domain.auto_renew || false,
        nameservers: domain.nameservers,
        client: domain.client ? {
          firstName: domain.client.first_name,
          lastName: domain.client.last_name,
          email: domain.client.email
        } : null
      }));
      
      setDomains(transformedDomains);
      setPagination({
        page: current_page,
        limit: per_page,
        total: total,
        totalPages: last_page
      });
      
      // Calculate stats
      const activeCount = transformedDomains.filter(d => d.status === 'Active').length;
      const expiringSoonCount = transformedDomains.filter(d => {
        const days = getDaysUntilExpiry(d.expiryDate);
        return days <= 30 && days > 0;
      }).length;
      const autoRenewCount = transformedDomains.filter(d => d.autoRenew).length;
      
      setStats({
        total: total,
        active: activeCount,
        expiringSoon: expiringSoonCount,
        autoRenew: autoRenewCount
      });
      
      setError(null);
    } catch (err) {
      console.error('Error fetching domains:', err);
      setError('Failed to load domains');
      setDomains([]);
    } finally {
      setLoading(false);
    }
  };

  useEffect(() => {
    fetchDomains();
  }, []);

  const filteredDomains = domains.filter(domain =>
    domain.domain?.toLowerCase().includes(searchTerm.toLowerCase()) ||
    domain.registrar?.toLowerCase().includes(searchTerm.toLowerCase())
  );

  const getStatusBadge = (status, expiryDate) => {
    const today = new Date();
    const expiry = new Date(expiryDate);
    const daysUntilExpiry = Math.ceil((expiry - today) / (1000 * 60 * 60 * 24));

    if (status === 'Expired') {
      return <Badge className="bg-red-100 text-red-700 hover:bg-red-100">Expired</Badge>;
    } else if (daysUntilExpiry <= 30) {
      return <Badge className="bg-yellow-100 text-yellow-700 hover:bg-yellow-100">Expiring Soon</Badge>;
    } else if (status === 'Active') {
      return <Badge className="bg-green-100 text-green-700 hover:bg-green-100">Active</Badge>;
    }
    return <Badge variant="secondary">{status}</Badge>;
  };

  const getDaysUntilExpiry = (expiryDate) => {
    const today = new Date();
    const expiry = new Date(expiryDate);
    const days = Math.ceil((expiry - today) / (1000 * 60 * 60 * 24));
    return days;
  };

  const domainStats = [
    {
      title: 'Total Domains',
      value: stats.total,
      icon: Globe,
      color: 'text-blue-600'
    },
    {
      title: 'Active Domains',
      value: stats.active,
      icon: CheckCircle,
      color: 'text-green-600'
    },
    {
      title: 'Expiring Soon',
      value: stats.expiringSoon,
      icon: AlertTriangle,
      color: 'text-yellow-600'
    },
    {
      title: 'Auto-Renew Enabled',
      value: stats.autoRenew,
      icon: RefreshCw,
      color: 'text-purple-600'
    }
  ];

  return (
    <div className="space-y-6">
      {/* Header */}
      <div className="flex items-center justify-between">
        <div className="flex items-center space-x-3">
          <Globe className="w-8 h-8 text-blue-600" />
          <div>
            <h1 className="text-3xl font-bold text-gray-900">Domains</h1>
            <p className="text-gray-600">Manage domain registrations and renewals</p>
          </div>
        </div>
        <div className="flex space-x-3">
          <Button variant="outline">Bulk Renew</Button>
          <Button>
            <Plus className="w-4 h-4 mr-2" />
            Register Domain
          </Button>
        </div>
      </div>

      {/* Domain Stats */}
      <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
        {domainStats.map((stat, index) => (
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
                placeholder="Search domains by name or registrar..."
                value={searchTerm}
                onChange={(e) => setSearchTerm(e.target.value)}
                className="pl-10"
              />
            </div>
            <Button variant="outline">Filter by Status</Button>
            <Button variant="outline">Filter by Registrar</Button>
            <Button variant="outline">Export</Button>
          </div>
        </CardContent>
      </Card>

      {/* Domains Table */}
      <Card>
        <CardHeader>
          <CardTitle className="flex items-center justify-between">
            <span>All Domains ({pagination.total})</span>
            {loading && <RefreshCw className="w-4 h-4 animate-spin" />}
          </CardTitle>
        </CardHeader>
        <CardContent>
          {error ? (
            <div className="text-center py-8">
              <AlertCircle className="w-12 h-12 text-red-500 mx-auto mb-4" />
              <p className="text-red-600 mb-4">{error}</p>
              <Button onClick={() => fetchDomains(pagination.page)}>
                <RefreshCw className="w-4 h-4 mr-2" />
                Retry
              </Button>
            </div>
          ) : (
            <Table>
              <TableHeader>
                <TableRow>
                  <TableHead>Domain</TableHead>
                  <TableHead>Registrar</TableHead>
                  <TableHead>Status</TableHead>
                  <TableHead>Registration Date</TableHead>
                  <TableHead>Expiry Date</TableHead>
                  <TableHead>Auto-Renew</TableHead>
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
                ) : filteredDomains.length === 0 ? (
                  <TableRow>
                    <TableCell colSpan={7} className="text-center py-8 text-gray-500">
                      No domains found
                    </TableCell>
                  </TableRow>
                ) : (
                  filteredDomains.map((domain) => {
                    const daysUntilExpiry = getDaysUntilExpiry(domain.expiryDate);
                    return (
                  <TableRow key={domain.id} className="hover:bg-gray-50">
                    <TableCell>
                      <div className="flex items-center space-x-3">
                        <div className="p-2 bg-blue-100 rounded-lg">
                          <Globe className="w-4 h-4 text-blue-600" />
                        </div>
                        <div>
                          <div className="font-medium text-gray-900">{domain.domain}</div>
                          <div className="text-sm text-gray-500">ID: {domain.id}</div>
                        </div>
                      </div>
                    </TableCell>
                    <TableCell>
                      <div className="font-medium text-gray-900">{domain.registrar}</div>
                    </TableCell>
                    <TableCell>
                      {getStatusBadge(domain.status, domain.expiryDate)}
                    </TableCell>
                    <TableCell>
                      <div className="text-sm text-gray-600">{domain.registrationDate}</div>
                    </TableCell>
                    <TableCell>
                      <div className={`text-sm ${
                        daysUntilExpiry <= 30 && daysUntilExpiry > 0
                          ? 'text-yellow-600 font-medium'
                          : daysUntilExpiry <= 0
                          ? 'text-red-600 font-medium'
                          : 'text-gray-600'
                      }`}>
                        {domain.expiryDate}
                        {daysUntilExpiry > 0 && daysUntilExpiry <= 30 && (
                          <div className="text-xs">({daysUntilExpiry} days left)</div>
                        )}
                        {daysUntilExpiry <= 0 && (
                          <div className="text-xs">(Expired)</div>
                        )}
                      </div>
                    </TableCell>
                    <TableCell>
                      <div className="flex items-center space-x-2">
                        {domain.autoRenew ? (
                          <>
                            <CheckCircle className="w-4 h-4 text-green-600" />
                            <span className="text-sm text-green-600 font-medium">Enabled</span>
                          </>
                        ) : (
                          <>
                            <Clock className="w-4 h-4 text-gray-400" />
                            <span className="text-sm text-gray-500">Disabled</span>
                          </>
                        )}
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
                            Edit Domain
                          </DropdownMenuItem>
                          <DropdownMenuItem>
                            <Settings className="w-4 h-4 mr-2" />
                            DNS Management
                          </DropdownMenuItem>
                          <DropdownMenuSeparator />
                          <DropdownMenuItem>
                            <RefreshCw className="w-4 h-4 mr-2" />
                            Renew Domain
                          </DropdownMenuItem>
                          <DropdownMenuItem>
                            <Calendar className="w-4 h-4 mr-2" />
                            Change Auto-Renew
                          </DropdownMenuItem>
                          <DropdownMenuItem>
                            <ExternalLink className="w-4 h-4 mr-2" />
                            Transfer Domain
                          </DropdownMenuItem>
                          <DropdownMenuSeparator />
                          <DropdownMenuItem className="text-red-600">
                            <AlertTriangle className="w-4 h-4 mr-2" />
                            Cancel Domain
                          </DropdownMenuItem>
                        </DropdownMenuContent>
                      </DropdownMenu>
                    </TableCell>
                  </TableRow>
                );
              })
                )}
            </TableBody>
          </Table>
          )}
        </CardContent>
      </Card>
    </div>
  );
};

export default Domains;