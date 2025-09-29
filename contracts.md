# WHMCS Admin Area - API Contracts & Implementation Plan

## Mock Data to Replace

### Current Mock Data (frontend/src/data/mockData.js):
- `mockClients` - Client information and contact details
- `mockInvoices` - Billing and invoice data
- `mockTickets` - Support tickets and communications
- `mockServices` - Hosting services and products
- `mockDomains` - Domain registrations and DNS management
- `dashboardStats` - Statistics and metrics
- `recentActivities` - System activity logs
- `adminTasks` - Admin to-do items

## API Contracts

### 1. Clients Management
**Base URL:** `/api/clients`

| Method | Endpoint | Description | Request Body | Response |
|--------|----------|-------------|--------------|----------|
| GET | `/api/clients` | Get all clients with pagination | Query: search, page, limit, status | Array of clients |
| POST | `/api/clients` | Create new client | Client object | Created client |
| GET | `/api/clients/:id` | Get client by ID | - | Client object |
| PUT | `/api/clients/:id` | Update client | Updated client data | Updated client |
| DELETE | `/api/clients/:id` | Delete client | - | Success message |
| POST | `/api/clients/import` | Import clients from CSV | CSV file | Import results |

### 2. Billing Management
**Base URL:** `/api/billing`

| Method | Endpoint | Description | Request Body | Response |
|--------|----------|-------------|--------------|----------|
| GET | `/api/billing/invoices` | Get all invoices | Query: search, status, client_id | Array of invoices |
| POST | `/api/billing/invoices` | Create new invoice | Invoice object | Created invoice |
| PUT | `/api/billing/invoices/:id` | Update invoice | Updated invoice data | Updated invoice |
| GET | `/api/billing/payments` | Get all payments | Query: search, status | Array of payments |
| POST | `/api/billing/payments` | Record payment | Payment object | Created payment |
| GET | `/api/billing/transactions` | Get transactions | Query: search, type | Array of transactions |
| GET | `/api/billing/stats` | Get billing statistics | - | Stats object |

### 3. Services Management
**Base URL:** `/api/services`

| Method | Endpoint | Description | Request Body | Response |
|--------|----------|-------------|--------------|----------|
| GET | `/api/services` | Get all services | Query: search, status, client_id | Array of services |
| POST | `/api/services` | Create new service | Service object | Created service |
| PUT | `/api/services/:id` | Update service | Updated service data | Updated service |
| PUT | `/api/services/:id/status` | Change service status | {status: 'Active/Suspended'} | Updated service |
| DELETE | `/api/services/:id` | Terminate service | - | Success message |

### 4. Domains Management
**Base URL:** `/api/domains`

| Method | Endpoint | Description | Request Body | Response |
|--------|----------|-------------|--------------|----------|
| GET | `/api/domains` | Get all domains | Query: search, status, expiring | Array of domains |
| POST | `/api/domains` | Register new domain | Domain object | Created domain |
| PUT | `/api/domains/:id` | Update domain | Updated domain data | Updated domain |
| PUT | `/api/domains/:id/renew` | Renew domain | {years: number} | Updated domain |
| PUT | `/api/domains/:id/auto-renew` | Toggle auto-renew | {autoRenew: boolean} | Updated domain |

### 5. Support Management
**Base URL:** `/api/support`

| Method | Endpoint | Description | Request Body | Response |
|--------|----------|-------------|--------------|----------|
| GET | `/api/support/tickets` | Get all tickets | Query: search, status, priority | Array of tickets |
| POST | `/api/support/tickets` | Create new ticket | Ticket object | Created ticket |
| PUT | `/api/support/tickets/:id` | Update ticket | Updated ticket data | Updated ticket |
| POST | `/api/support/tickets/:id/reply` | Add reply to ticket | Reply object | Updated ticket |
| GET | `/api/support/kb` | Get KB articles | Query: search, category | Array of articles |

### 6. Dashboard & Analytics
**Base URL:** `/api/dashboard`

| Method | Endpoint | Description | Request Body | Response |
|--------|----------|-------------|--------------|----------|
| GET | `/api/dashboard/stats` | Get dashboard statistics | - | Stats object |
| GET | `/api/dashboard/activities` | Get recent activities | Query: limit | Array of activities |
| GET | `/api/dashboard/tasks` | Get admin tasks | - | Array of tasks |
| PUT | `/api/dashboard/tasks/:id` | Update task status | {completed: boolean} | Updated task |

## Database Models

### Client Model
```javascript
{
  _id: ObjectId,
  firstName: String,
  lastName: String,
  email: String (unique),
  company: String,
  phone: String,
  address: String,
  status: String (Active, Suspended, Inactive),
  registrationDate: Date,
  lastLogin: Date,
  totalSpent: Number,
  activeServices: Number,
  createdAt: Date,
  updatedAt: Date
}
```

### Invoice Model
```javascript
{
  _id: ObjectId,
  invoiceId: String (unique),
  clientId: ObjectId (ref: Client),
  amount: Number,
  status: String (Paid, Pending, Overdue),
  dueDate: Date,
  issueDate: Date,
  description: String,
  paymentMethod: String,
  items: [{
    description: String,
    amount: Number,
    quantity: Number
  }],
  createdAt: Date,
  updatedAt: Date
}
```

### Service Model
```javascript
{
  _id: ObjectId,
  clientId: ObjectId (ref: Client),
  productName: String,
  domain: String,
  status: String (Active, Suspended, Pending, Terminated),
  nextDueDate: Date,
  recurringAmount: Number,
  billingCycle: String (Monthly, Annually),
  registrationDate: Date,
  createdAt: Date,
  updatedAt: Date
}
```

### Domain Model
```javascript
{
  _id: ObjectId,
  clientId: ObjectId (ref: Client),
  domain: String (unique),
  registrar: String,
  status: String (Active, Expired, Pending),
  registrationDate: Date,
  expiryDate: Date,
  autoRenew: Boolean,
  nameservers: [String],
  createdAt: Date,
  updatedAt: Date
}
```

### Ticket Model
```javascript
{
  _id: ObjectId,
  ticketId: String (unique),
  clientId: ObjectId (ref: Client),
  subject: String,
  status: String (Open, In Progress, Resolved, Closed),
  priority: String (Low, Medium, High),
  department: String,
  category: String,
  assignedTo: String,
  replies: [{
    message: String,
    sender: String,
    timestamp: Date,
    isAdmin: Boolean
  }],
  createdAt: Date,
  updatedAt: Date
}
```

## Frontend Integration Plan

### Step 1: Replace Mock Data Imports
- Remove imports from `mockData.js` in all pages
- Replace with API calls using axios
- Update components to handle loading states

### Step 2: Add Loading States
- Implement skeleton loaders for tables
- Add loading spinners for forms
- Handle error states gracefully

### Step 3: Form Submissions
- Connect all forms to actual API endpoints
- Add proper validation and error handling
- Show success/error toasts

### Step 4: Real-time Updates
- Implement optimistic updates for better UX
- Add data refetching after mutations
- Handle concurrent modifications

## CSV Import Implementation

### CSV Structure Expected:
```
Clients CSV: firstName,lastName,email,company,phone,address,status
Invoices CSV: invoiceId,clientEmail,amount,status,dueDate,description
Services CSV: clientEmail,productName,domain,status,recurringAmount,billingCycle
Domains CSV: clientEmail,domain,registrar,registrationDate,expiryDate,autoRenew
```

### Import Process:
1. Validate CSV format and headers
2. Check for existing records (prevent duplicates)
3. Create missing clients first
4. Import related records (invoices, services, domains)
5. Generate import report with success/error counts
6. Send notification to admin with results

## Error Handling Strategy

### Backend:
- Consistent error response format
- Input validation using schemas
- Database transaction rollbacks
- Detailed error logging

### Frontend:
- Global error boundary
- Toast notifications for user feedback
- Retry mechanisms for failed requests
- Offline state handling

## Performance Considerations

### Backend:
- Database indexing on frequently queried fields
- Pagination for large datasets
- Caching for dashboard statistics
- Background jobs for heavy imports

### Frontend:
- Lazy loading of pages
- Debounced search functionality
- Virtual scrolling for large tables
- Optimized re-renders with React.memo

This contract ensures seamless integration between frontend mock data and the actual backend implementation.