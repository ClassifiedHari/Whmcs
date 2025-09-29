// Mock data for WHMCS Admin Area Clone

// Dashboard Statistics
export const dashboardStats = {
  totalClients: 1247,
  activeServices: 3456,
  pendingInvoices: 23,
  openTickets: 12,
  monthlyRevenue: 45680.50,
  thisMonthRevenue: 38920.30,
  pendingOrders: 8,
  cancellationRequests: 3
};

// Client Data
export const mockClients = [
  {
    id: 1,
    firstName: 'John',
    lastName: 'Smith',
    email: 'john.smith@example.com',
    company: 'TechCorp Inc.',
    phone: '+1-555-0123',
    address: '123 Main St, New York, NY 10001',
    status: 'Active',
    registrationDate: '2023-01-15',
    lastLogin: '2024-07-20 14:30',
    totalSpent: 5240.50,
    activeServices: 3
  },
  {
    id: 2,
    firstName: 'Sarah',
    lastName: 'Johnson',
    email: 'sarah@digitalagency.com',
    company: 'Digital Agency LLC',
    phone: '+1-555-0456',
    address: '456 Oak Ave, Los Angeles, CA 90210',
    status: 'Active',
    registrationDate: '2023-03-22',
    lastLogin: '2024-07-21 09:15',
    totalSpent: 8950.00,
    activeServices: 5
  },
  {
    id: 3,
    firstName: 'Michael',
    lastName: 'Brown',
    email: 'mike.brown@startup.io',
    company: 'StartupTech',
    phone: '+1-555-0789',
    address: '789 Pine St, Austin, TX 78701',
    status: 'Suspended',
    registrationDate: '2023-06-10',
    lastLogin: '2024-07-18 16:45',
    totalSpent: 2340.75,
    activeServices: 1
  },
  {
    id: 4,
    firstName: 'Emily',
    lastName: 'Davis',
    email: 'emily@ecommerce.shop',
    company: 'E-Commerce Solutions',
    phone: '+1-555-0321',
    address: '321 Elm St, Seattle, WA 98101',
    status: 'Active',
    registrationDate: '2023-09-05',
    lastLogin: '2024-07-21 11:20',
    totalSpent: 12450.25,
    activeServices: 8
  }
];

// Invoice Data
export const mockInvoices = [
  {
    id: 'INV-2024-001',
    clientId: 1,
    clientName: 'John Smith',
    amount: 299.99,
    status: 'Paid',
    dueDate: '2024-07-15',
    issueDate: '2024-06-15',
    description: 'Web Hosting - Annual Plan',
    paymentMethod: 'Credit Card'
  },
  {
    id: 'INV-2024-002',
    clientId: 2,
    clientName: 'Sarah Johnson',
    amount: 599.99,
    status: 'Pending',
    dueDate: '2024-07-25',
    issueDate: '2024-07-10',
    description: 'VPS Hosting + SSL Certificate',
    paymentMethod: 'Bank Transfer'
  },
  {
    id: 'INV-2024-003',
    clientId: 3,
    clientName: 'Michael Brown',
    amount: 149.99,
    status: 'Overdue',
    dueDate: '2024-07-01',
    issueDate: '2024-06-01',
    description: 'Domain Registration + Basic Hosting',
    paymentMethod: 'PayPal'
  },
  {
    id: 'INV-2024-004',
    clientId: 4,
    clientName: 'Emily Davis',
    amount: 899.99,
    status: 'Paid',
    dueDate: '2024-07-20',
    issueDate: '2024-06-20',
    description: 'Dedicated Server - Monthly',
    paymentMethod: 'Credit Card'
  }
];

// Support Tickets Data
export const mockTickets = [
  {
    id: 'TKT-001',
    clientId: 1,
    clientName: 'John Smith',
    subject: 'Website Loading Slowly',
    status: 'Open',
    priority: 'Medium',
    department: 'Technical Support',
    created: '2024-07-21 10:30',
    lastReply: '2024-07-21 14:15',
    assignedTo: 'Alex Thompson',
    category: 'Performance'
  },
  {
    id: 'TKT-002',
    clientId: 2,
    clientName: 'Sarah Johnson',
    subject: 'Domain Transfer Issue',
    status: 'In Progress',
    priority: 'High',
    department: 'Domain Services',
    created: '2024-07-20 16:45',
    lastReply: '2024-07-21 09:30',
    assignedTo: 'Maria Garcia',
    category: 'Domain'
  },
  {
    id: 'TKT-003',
    clientId: 4,
    clientName: 'Emily Davis',
    subject: 'SSL Certificate Installation',
    status: 'Resolved',
    priority: 'Low',
    department: 'Technical Support',
    created: '2024-07-19 11:20',
    lastReply: '2024-07-20 15:45',
    assignedTo: 'David Wilson',
    category: 'SSL'
  }
];

// Services/Products Data
export const mockServices = [
  {
    id: 1,
    clientId: 1,
    clientName: 'John Smith',
    productName: 'Shared Hosting Pro',
    domain: 'techcorp.com',
    status: 'Active',
    nextDueDate: '2025-01-15',
    recurringAmount: 299.99,
    billingCycle: 'Annually',
    registrationDate: '2024-01-15'
  },
  {
    id: 2,
    clientId: 2,
    clientName: 'Sarah Johnson',
    productName: 'VPS Linux',
    domain: 'digitalagency.com',
    status: 'Active',
    nextDueDate: '2024-08-10',
    recurringAmount: 49.99,
    billingCycle: 'Monthly',
    registrationDate: '2023-08-10'
  },
  {
    id: 3,
    clientId: 3,
    clientName: 'Michael Brown',
    productName: 'Basic Hosting',
    domain: 'startup.io',
    status: 'Suspended',
    nextDueDate: '2024-07-01',
    recurringAmount: 149.99,
    billingCycle: 'Annually',
    registrationDate: '2023-07-01'
  },
  {
    id: 4,
    clientId: 4,
    clientName: 'Emily Davis',
    productName: 'Dedicated Server',
    domain: 'ecommerce.shop',
    status: 'Active',
    nextDueDate: '2024-08-20',
    recurringAmount: 899.99,
    billingCycle: 'Monthly',
    registrationDate: '2023-08-20'
  }
];

// Domains Data
export const mockDomains = [
  {
    id: 1,
    clientId: 1,
    domain: 'techcorp.com',
    registrar: 'NameCheap',
    status: 'Active',
    registrationDate: '2023-01-15',
    expiryDate: '2025-01-15',
    autoRenew: true,
    nameservers: ['ns1.hosting.com', 'ns2.hosting.com']
  },
  {
    id: 2,
    clientId: 2,
    domain: 'digitalagency.com',
    registrar: 'GoDaddy',
    status: 'Active',
    registrationDate: '2023-03-22',
    expiryDate: '2025-03-22',
    autoRenew: true,
    nameservers: ['ns1.hosting.com', 'ns2.hosting.com']
  },
  {
    id: 3,
    clientId: 3,
    domain: 'startup.io',
    registrar: 'Namecheap',
    status: 'Expired',
    registrationDate: '2023-06-10',
    expiryDate: '2024-06-10',
    autoRenew: false,
    nameservers: ['ns1.hosting.com', 'ns2.hosting.com']
  }
];

// Recent Activities
export const recentActivities = [
  {
    id: 1,
    type: 'payment',
    description: 'Payment received from Emily Davis',
    amount: 899.99,
    timestamp: '2024-07-21 15:30',
    icon: 'credit-card'
  },
  {
    id: 2,
    type: 'ticket',
    description: 'New support ticket from John Smith',
    timestamp: '2024-07-21 10:30',
    icon: 'help-circle'
  },
  {
    id: 3,
    type: 'client',
    description: 'New client registration: Alex Wilson',
    timestamp: '2024-07-21 09:45',
    icon: 'user-plus'
  },
  {
    id: 4,
    type: 'service',
    description: 'Service suspended for Michael Brown',
    timestamp: '2024-07-20 16:20',
    icon: 'alert-triangle'
  }
];

// Admin Tasks/To-Do
export const adminTasks = [
  {
    id: 1,
    task: 'Review pending invoices',
    priority: 'High',
    dueDate: '2024-07-22',
    completed: false
  },
  {
    id: 2,
    task: 'Update server maintenance schedule',
    priority: 'Medium',
    dueDate: '2024-07-25',
    completed: false
  },
  {
    id: 3,
    task: 'Process domain renewals',
    priority: 'High',
    dueDate: '2024-07-23',
    completed: true
  }
];