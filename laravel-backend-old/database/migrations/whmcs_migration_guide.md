# WHMCS 8.11.2 to Laravel Migration Guide

## Database Table Mapping

### WHMCS → Laravel Table Mapping

| WHMCS Table | Laravel Table | Primary Fields |
|-------------|---------------|----------------|
| `tblclients` | `clients` | Client information |
| `tblinvoices` | `invoices` | Invoice data |
| `tblhosting` | `services` | Hosting/Service products |
| `tbldomains` | `domains` | Domain registrations |
| `tbltickets` | `tickets` | Support tickets |
| `tblticketreplies` | `ticket_replies` | Ticket responses |

## Field Mapping Details

### Clients Migration
**WHMCS `tblclients` → Laravel `clients`**

| WHMCS Field | Laravel Field | Transformation |
|-------------|---------------|----------------|
| `id` | `id` | Direct mapping |
| `firstname` | `first_name` | Direct mapping |
| `lastname` | `last_name` | Direct mapping |
| `email` | `email` | Direct mapping |
| `companyname` | `company` | Direct mapping |
| `phonenumber` | `phone` | Direct mapping |
| `address1`, `address2`, `city`, `state`, `postcode`, `country` | `address` | Concatenate all address fields |
| `status` | `status` | Map: Active→Active, Inactive→Inactive, Closed→Suspended |
| `datecreated` | `registration_date` | Date conversion |
| `lastlogin` | `last_login` | Date conversion |
| - | `total_spent` | Calculate from paid invoices |
| - | `active_services` | Count from active services |

### Invoices Migration
**WHMCS `tblinvoices` → Laravel `invoices`**

| WHMCS Field | Laravel Field | Transformation |
|-------------|---------------|----------------|
| `id` | `id` | Direct mapping |
| `invoicenum` | `invoice_id` | Use invoicenum or generate INV-YEAR-ID |
| `userid` | `client_id` | Direct mapping |
| `total` | `amount` | Direct mapping |
| `status` | `status` | Map: Paid→Paid, Unpaid→Pending, Overdue→Overdue, Cancelled→Cancelled |
| `duedate` | `due_date` | Date conversion |
| `date` | `issue_date` | Date conversion |
| `notes` | `description` | Direct mapping |
| `paymentmethod` | `payment_method` | Direct mapping |
| - | `items` | Build from tblinvoiceitems |

### Services Migration
**WHMCS `tblhosting` → Laravel `services`**

| WHMCS Field | Laravel Field | Transformation |
|-------------|---------------|----------------|
| `id` | `id` | Direct mapping |
| `userid` | `client_id` | Direct mapping |
| `packageid` | - | Map to product name via tblproducts |
| `domain` | `domain` | Direct mapping |
| `domainstatus` | `status` | Map: Active→Active, Suspended→Suspended, Pending→Pending, Terminated→Terminated |
| `nextduedate` | `next_due_date` | Date conversion |
| `amount` | `recurring_amount` | Direct mapping |
| `billingcycle` | `billing_cycle` | Map: Monthly→Monthly, Annually→Annually, etc. |
| `regdate` | `registration_date` | Date conversion |

### Domains Migration
**WHMCS `tbldomains` → Laravel `domains`**

| WHMCS Field | Laravel Field | Transformation |
|-------------|---------------|----------------|
| `id` | `id` | Direct mapping |
| `userid` | `client_id` | Direct mapping |
| `domain` | `domain` | Direct mapping |
| `registrar` | `registrar` | Direct mapping |
| `status` | `status` | Map: Active→Active, Expired→Expired, Pending→Pending, Cancelled→Cancelled |
| `registrationdate` | `registration_date` | Date conversion |
| `expirydate` | `expiry_date` | Date conversion |
| `recurringamount` | - | Not needed in domains table |
| `autorenew` | `auto_renew` | Convert 1/0 to true/false |
| - | `nameservers` | Get from tbldomainsadditionalfields or default |

### Tickets Migration
**WHMCS `tbltickets` → Laravel `tickets`**

| WHMCS Field | Laravel Field | Transformation |
|-------------|---------------|----------------|
| `id` | `id` | Direct mapping |
| `tid` | `ticket_id` | Direct mapping or generate TKT-XXXXXX |
| `userid` | `client_id` | Direct mapping |
| `subject` | `subject` | Direct mapping |
| `status` | `status` | Map status codes to text |
| `urgency` | `priority` | Map: High→High, Medium→Medium, Low→Low |
| `department` | `department` | Get from tbladmins or static mapping |
| `category` | `category` | Direct mapping |
| `admin` | `assigned_to` | Get admin name from tbladmins |
| `date` | `created_at` | Date conversion |
| `lastreply` | - | Calculate from replies |

## Migration Process

### Phase 1: Preparation
1. **Backup Current Data**: Full backup of both WHMCS and Laravel databases
2. **Schema Verification**: Ensure Laravel migrations are run
3. **Data Validation**: Check WHMCS data integrity
4. **Mapping Configuration**: Configure field mappings

### Phase 2: Data Migration
1. **Clients First**: Migrate all client data
2. **Products/Services**: Migrate hosting services
3. **Domains**: Migrate domain registrations
4. **Financial Data**: Migrate invoices and payments
5. **Support Data**: Migrate tickets and replies

### Phase 3: Validation
1. **Count Verification**: Ensure record counts match
2. **Data Integrity**: Verify relationships and constraints
3. **Sample Testing**: Test random records for accuracy
4. **Business Logic**: Verify calculations (totals, stats, etc.)

### Phase 4: Post-Migration
1. **Update Statistics**: Recalculate client stats
2. **Index Optimization**: Rebuild database indexes
3. **Cache Warming**: Pre-populate any caches
4. **API Testing**: Verify all endpoints work correctly

## Migration Commands

### Laravel Migration Commands
```bash
# Run Laravel migrations
php artisan migrate

# Run WHMCS data migration
php artisan migrate:whmcs --source=[whmcs_db] --batch=1000

# Verify migration results
php artisan migrate:verify --whmcs

# Update statistics
php artisan whmcs:update-stats
```

## Data Transformation Rules

### Status Mapping
```php
// Client Status
$clientStatusMap = [
    'Active' => 'Active',
    'Inactive' => 'Inactive', 
    'Closed' => 'Suspended'
];

// Invoice Status
$invoiceStatusMap = [
    'Paid' => 'Paid',
    'Unpaid' => 'Pending',
    'Overdue' => 'Overdue',
    'Cancelled' => 'Cancelled',
    'Collections' => 'Overdue'
];

// Service Status  
$serviceStatusMap = [
    'Active' => 'Active',
    'Suspended' => 'Suspended',
    'Pending' => 'Pending',
    'Terminated' => 'Terminated',
    'Cancelled' => 'Terminated'
];
```

### Date Conversion
```php
// WHMCS uses YYYY-MM-DD HH:MM:SS format
// Laravel expects Carbon instances
$laravelDate = Carbon::createFromFormat('Y-m-d H:i:s', $whmcsDate);
```

### Address Concatenation
```php
// Combine WHMCS address fields
$address = trim(implode(', ', array_filter([
    $whmcs->address1,
    $whmcs->address2, 
    $whmcs->city,
    $whmcs->state,
    $whmcs->postcode,
    $whmcs->country
])));
```

## Special Considerations

### Custom Fields
- WHMCS custom fields in `tblcustomfields` and `tblcustomfieldsvalues`
- Map important custom fields to JSON columns or separate tables

### Encrypted Data
- WHMCS encrypts sensitive data (CC info, passwords)
- Migration scripts should handle or skip encrypted fields

### Relationships
- Maintain foreign key relationships during migration
- Handle orphaned records appropriately

### Large Datasets
- Use batch processing for large tables (1000+ records)
- Implement progress tracking and resume capability
- Consider memory usage and execution time limits

## Rollback Strategy

### Pre-Migration Backup
```sql
-- Create backup tables
CREATE TABLE clients_backup AS SELECT * FROM clients;
CREATE TABLE invoices_backup AS SELECT * FROM invoices;
-- ... repeat for all tables
```

### Rollback Commands
```bash
# Rollback migration
php artisan migrate:rollback-whmcs

# Restore from backup
php artisan whmcs:restore-backup
```
