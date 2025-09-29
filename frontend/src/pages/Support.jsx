import React, { useState } from 'react';
import { Card, CardContent, CardHeader, CardTitle } from '../components/ui/card';
import { Button } from '../components/ui/button';
import { Input } from '../components/ui/input';
import { Badge } from '../components/ui/badge';
import { Textarea } from '../components/ui/textarea';
import { Tabs, TabsContent, TabsList, TabsTrigger } from '../components/ui/tabs';
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
  Dialog,
  DialogContent,
  DialogDescription,
  DialogHeader,
  DialogTitle,
  DialogTrigger,
} from '../components/ui/dialog';
import { Label } from '../components/ui/label';
import {
  HelpCircle,
  Search,
  Plus,
  MoreHorizontal,
  Eye,
  MessageSquare,
  Clock,
  AlertCircle,
  CheckCircle,
  User,
  Calendar,
  Tag,
  Send
} from 'lucide-react';
import { mockTickets } from '../data/mockData';

const Support = () => {
  const [searchTerm, setSearchTerm] = useState('');
  const [selectedTicket, setSelectedTicket] = useState(null);
  const [isNewTicketOpen, setIsNewTicketOpen] = useState(false);
  const [activeTab, setActiveTab] = useState('tickets');

  const filteredTickets = mockTickets.filter(ticket =>
    ticket.id.toLowerCase().includes(searchTerm.toLowerCase()) ||
    ticket.clientName.toLowerCase().includes(searchTerm.toLowerCase()) ||
    ticket.subject.toLowerCase().includes(searchTerm.toLowerCase())
  );

  const getStatusBadge = (status) => {
    switch (status) {
      case 'Open':
        return (
          <Badge className="bg-red-100 text-red-700 hover:bg-red-100">
            <AlertCircle className="w-3 h-3 mr-1" />
            Open
          </Badge>
        );
      case 'In Progress':
        return (
          <Badge className="bg-yellow-100 text-yellow-700 hover:bg-yellow-100">
            <Clock className="w-3 h-3 mr-1" />
            In Progress
          </Badge>
        );
      case 'Resolved':
        return (
          <Badge className="bg-green-100 text-green-700 hover:bg-green-100">
            <CheckCircle className="w-3 h-3 mr-1" />
            Resolved
          </Badge>
        );
      case 'Closed':
        return (
          <Badge className="bg-gray-100 text-gray-700 hover:bg-gray-100">
            <CheckCircle className="w-3 h-3 mr-1" />
            Closed
          </Badge>
        );
      default:
        return <Badge variant="secondary">{status}</Badge>;
    }
  };

  const getPriorityBadge = (priority) => {
    switch (priority) {
      case 'High':
        return <Badge className="bg-red-100 text-red-700 hover:bg-red-100">High</Badge>;
      case 'Medium':
        return <Badge className="bg-yellow-100 text-yellow-700 hover:bg-yellow-100">Medium</Badge>;
      case 'Low':
        return <Badge className="bg-gray-100 text-gray-700 hover:bg-gray-100">Low</Badge>;
      default:
        return <Badge variant="secondary">{priority}</Badge>;
    }
  };

  const supportStats = [
    {
      title: 'Open Tickets',
      value: mockTickets.filter(t => t.status === 'Open').length,
      icon: AlertCircle,
      color: 'text-red-600'
    },
    {
      title: 'In Progress',
      value: mockTickets.filter(t => t.status === 'In Progress').length,
      icon: Clock,
      color: 'text-yellow-600'
    },
    {
      title: 'Resolved Today',
      value: mockTickets.filter(t => t.status === 'Resolved').length,
      icon: CheckCircle,
      color: 'text-green-600'
    },
    {
      title: 'Avg Response Time',
      value: '2.3h',
      icon: HelpCircle,
      color: 'text-blue-600'
    }
  ];

  // Mock knowledge base articles
  const mockKBArticles = [
    {
      id: 1,
      title: 'How to Reset Your Password',
      category: 'Account Management',
      views: 1247,
      lastUpdated: '2024-07-15',
      status: 'Published'
    },
    {
      id: 2,
      title: 'Setting Up Email Accounts',
      category: 'Email',
      views: 892,
      lastUpdated: '2024-07-18',
      status: 'Published'
    },
    {
      id: 3,
      title: 'Domain Name System (DNS) Basics',
      category: 'Domains',
      views: 634,
      lastUpdated: '2024-07-20',
      status: 'Draft'
    }
  ];

  return (
    <div className="space-y-6">
      {/* Header */}
      <div className="flex items-center justify-between">
        <div className="flex items-center space-x-3">
          <HelpCircle className="w-8 h-8 text-blue-600" />
          <div>
            <h1 className="text-3xl font-bold text-gray-900">Support</h1>
            <p className="text-gray-600">Manage customer support tickets and knowledge base</p>
          </div>
        </div>
        <Dialog open={isNewTicketOpen} onOpenChange={setIsNewTicketOpen}>
          <DialogTrigger asChild>
            <Button>
              <Plus className="w-4 h-4 mr-2" />
              New Ticket
            </Button>
          </DialogTrigger>
          <DialogContent className="max-w-2xl">
            <DialogHeader>
              <DialogTitle>Create New Support Ticket</DialogTitle>
              <DialogDescription>
                Create a support ticket on behalf of a client.
              </DialogDescription>
            </DialogHeader>
            <div className="space-y-4 py-4">
              <div className="grid grid-cols-2 gap-4">
                <div className="space-y-2">
                  <Label htmlFor="client">Client</Label>
                  <select className="w-full px-3 py-2 border border-gray-300 rounded-md">
                    <option value="">Select Client</option>
                    <option value="1">John Smith</option>
                    <option value="2">Sarah Johnson</option>
                    <option value="3">Michael Brown</option>
                  </select>
                </div>
                <div className="space-y-2">
                  <Label htmlFor="priority">Priority</Label>
                  <select className="w-full px-3 py-2 border border-gray-300 rounded-md">
                    <option value="Low">Low</option>
                    <option value="Medium">Medium</option>
                    <option value="High">High</option>
                  </select>
                </div>
              </div>
              <div className="grid grid-cols-2 gap-4">
                <div className="space-y-2">
                  <Label htmlFor="department">Department</Label>
                  <select className="w-full px-3 py-2 border border-gray-300 rounded-md">
                    <option value="Technical Support">Technical Support</option>
                    <option value="Billing">Billing</option>
                    <option value="Sales">Sales</option>
                    <option value="Domain Services">Domain Services</option>
                  </select>
                </div>
                <div className="space-y-2">
                  <Label htmlFor="category">Category</Label>
                  <Input id="category" placeholder="e.g., Performance, SSL, etc." />
                </div>
              </div>
              <div className="space-y-2">
                <Label htmlFor="subject">Subject</Label>
                <Input id="subject" placeholder="Brief description of the issue" />
              </div>
              <div className="space-y-2">
                <Label htmlFor="message">Message</Label>
                <Textarea id="message" rows={4} placeholder="Detailed description of the issue..." />
              </div>
            </div>
            <div className="flex justify-end space-x-2">
              <Button variant="outline" onClick={() => setIsNewTicketOpen(false)}>
                Cancel
              </Button>
              <Button onClick={() => setIsNewTicketOpen(false)}>
                Create Ticket
              </Button>
            </div>
          </DialogContent>
        </Dialog>
      </div>

      {/* Support Stats */}
      <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
        {supportStats.map((stat, index) => (
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

      {/* Main Content */}
      <Card>
        <CardContent className="p-6">
          <Tabs value={activeTab} onValueChange={setActiveTab}>
            <div className="flex items-center justify-between mb-6">
              <TabsList>
                <TabsTrigger value="tickets">Support Tickets</TabsTrigger>
                <TabsTrigger value="knowledge-base">Knowledge Base</TabsTrigger>
              </TabsList>
              <div className="flex items-center space-x-4">
                <div className="relative">
                  <Search className="absolute left-3 top-1/2 transform -translate-y-1/2 text-gray-400 w-4 h-4" />
                  <Input
                    placeholder="Search..."
                    value={searchTerm}
                    onChange={(e) => setSearchTerm(e.target.value)}
                    className="pl-10 w-64"
                  />
                </div>
                <Button variant="outline">Filter</Button>
                <Button variant="outline">Export</Button>
              </div>
            </div>

            <TabsContent value="tickets">
              <Table>
                <TableHeader>
                  <TableRow>
                    <TableHead>Ticket ID</TableHead>
                    <TableHead>Client</TableHead>
                    <TableHead>Subject</TableHead>
                    <TableHead>Status</TableHead>
                    <TableHead>Priority</TableHead>
                    <TableHead>Assigned To</TableHead>
                    <TableHead>Last Reply</TableHead>
                    <TableHead className="text-right">Actions</TableHead>
                  </TableRow>
                </TableHeader>
                <TableBody>
                  {filteredTickets.map((ticket) => (
                    <TableRow key={ticket.id} className="hover:bg-gray-50">
                      <TableCell className="font-medium">{ticket.id}</TableCell>
                      <TableCell>{ticket.clientName}</TableCell>
                      <TableCell className="max-w-xs truncate">{ticket.subject}</TableCell>
                      <TableCell>{getStatusBadge(ticket.status)}</TableCell>
                      <TableCell>{getPriorityBadge(ticket.priority)}</TableCell>
                      <TableCell>
                        <div className="flex items-center space-x-2">
                          <div className="w-6 h-6 bg-blue-600 rounded-full flex items-center justify-center">
                            <User className="w-3 h-3 text-white" />
                          </div>
                          <span className="text-sm">{ticket.assignedTo}</span>
                        </div>
                      </TableCell>
                      <TableCell className="text-sm text-gray-500">{ticket.lastReply}</TableCell>
                      <TableCell className="text-right">
                        <DropdownMenu>
                          <DropdownMenuTrigger asChild>
                            <Button variant="ghost" size="sm">
                              <MoreHorizontal className="w-4 h-4" />
                            </Button>
                          </DropdownMenuTrigger>
                          <DropdownMenuContent align="end">
                            <DropdownMenuItem onClick={() => setSelectedTicket(ticket)}>
                              <Eye className="w-4 h-4 mr-2" />
                              View Ticket
                            </DropdownMenuItem>
                            <DropdownMenuItem>
                              <MessageSquare className="w-4 h-4 mr-2" />
                              Reply
                            </DropdownMenuItem>
                            <DropdownMenuSeparator />
                            <DropdownMenuItem>
                              Change Status
                            </DropdownMenuItem>
                            <DropdownMenuItem>
                              Assign to Agent
                            </DropdownMenuItem>
                          </DropdownMenuContent>
                        </DropdownMenu>
                      </TableCell>
                    </TableRow>
                  ))}
                </TableBody>
              </Table>
            </TabsContent>

            <TabsContent value="knowledge-base">
              <div className="space-y-4">
                <div className="flex justify-between items-center">
                  <h3 className="text-lg font-medium">Knowledge Base Articles</h3>
                  <Button>
                    <Plus className="w-4 h-4 mr-2" />
                    New Article
                  </Button>
                </div>
                <Table>
                  <TableHeader>
                    <TableRow>
                      <TableHead>Title</TableHead>
                      <TableHead>Category</TableHead>
                      <TableHead>Views</TableHead>
                      <TableHead>Status</TableHead>
                      <TableHead>Last Updated</TableHead>
                      <TableHead className="text-right">Actions</TableHead>
                    </TableRow>
                  </TableHeader>
                  <TableBody>
                    {mockKBArticles.map((article) => (
                      <TableRow key={article.id} className="hover:bg-gray-50">
                        <TableCell className="font-medium">{article.title}</TableCell>
                        <TableCell>
                          <Badge variant="outline">{article.category}</Badge>
                        </TableCell>
                        <TableCell>{article.views.toLocaleString()}</TableCell>
                        <TableCell>
                          <Badge className={article.status === 'Published' 
                            ? 'bg-green-100 text-green-700 hover:bg-green-100' 
                            : 'bg-gray-100 text-gray-700 hover:bg-gray-100'
                          }>
                            {article.status}
                          </Badge>
                        </TableCell>
                        <TableCell className="text-sm text-gray-500">{article.lastUpdated}</TableCell>
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
                                View Article
                              </DropdownMenuItem>
                              <DropdownMenuItem>
                                Edit Article
                              </DropdownMenuItem>
                              <DropdownMenuSeparator />
                              <DropdownMenuItem>
                                {article.status === 'Published' ? 'Unpublish' : 'Publish'}
                              </DropdownMenuItem>
                            </DropdownMenuContent>
                          </DropdownMenu>
                        </TableCell>
                      </TableRow>
                    ))}
                  </TableBody>
                </Table>
              </div>
            </TabsContent>
          </Tabs>
        </CardContent>
      </Card>

      {/* Ticket Details Dialog */}
      {selectedTicket && (
        <Dialog open={!!selectedTicket} onOpenChange={() => setSelectedTicket(null)}>
          <DialogContent className="max-w-4xl max-h-[80vh] overflow-y-auto">
            <DialogHeader>
              <DialogTitle className="flex items-center space-x-3">
                <span>{selectedTicket.id}: {selectedTicket.subject}</span>
                {getStatusBadge(selectedTicket.status)}
                {getPriorityBadge(selectedTicket.priority)}
              </DialogTitle>
              <DialogDescription>
                Ticket from {selectedTicket.clientName} • {selectedTicket.department} • {selectedTicket.category}
              </DialogDescription>
            </DialogHeader>
            <div className="space-y-6">
              {/* Ticket Info */}
              <div className="grid grid-cols-2 gap-6">
                <div className="space-y-3">
                  <div className="flex items-center space-x-2">
                    <User className="w-4 h-4 text-gray-400" />
                    <span className="text-sm"><strong>Client:</strong> {selectedTicket.clientName}</span>
                  </div>
                  <div className="flex items-center space-x-2">
                    <Calendar className="w-4 h-4 text-gray-400" />
                    <span className="text-sm"><strong>Created:</strong> {selectedTicket.created}</span>
                  </div>
                  <div className="flex items-center space-x-2">
                    <Tag className="w-4 h-4 text-gray-400" />
                    <span className="text-sm"><strong>Category:</strong> {selectedTicket.category}</span>
                  </div>
                </div>
                <div className="space-y-3">
                  <div className="flex items-center space-x-2">
                    <User className="w-4 h-4 text-gray-400" />
                    <span className="text-sm"><strong>Assigned to:</strong> {selectedTicket.assignedTo}</span>
                  </div>
                  <div className="flex items-center space-x-2">
                    <Clock className="w-4 h-4 text-gray-400" />
                    <span className="text-sm"><strong>Last Reply:</strong> {selectedTicket.lastReply}</span>
                  </div>
                  <div className="flex items-center space-x-2">
                    <HelpCircle className="w-4 h-4 text-gray-400" />
                    <span className="text-sm"><strong>Department:</strong> {selectedTicket.department}</span>
                  </div>
                </div>
              </div>

              {/* Reply Form */}
              <div className="border-t pt-6">
                <h4 className="font-medium text-gray-900 mb-3">Add Reply</h4>
                <Textarea 
                  placeholder="Type your reply here..."
                  rows={4}
                  className="mb-3"
                />
                <div className="flex items-center justify-between">
                  <div className="flex items-center space-x-4">
                    <select className="px-3 py-1 border border-gray-300 rounded text-sm">
                      <option value="Open">Open</option>
                      <option value="In Progress">In Progress</option>
                      <option value="Resolved">Resolved</option>
                      <option value="Closed">Closed</option>
                    </select>
                    <Button variant="outline" size="sm">Attach File</Button>
                  </div>
                  <Button>
                    <Send className="w-4 h-4 mr-2" />
                    Send Reply
                  </Button>
                </div>
              </div>
            </div>
          </DialogContent>
        </Dialog>
      )}
    </div>
  );
};

export default Support;