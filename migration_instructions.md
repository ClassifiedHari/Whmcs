# WHMCS Migration Instructions

## Quick Setup Guide

### Step 1: Download the Migration Script
Download the `whmcs_migration_standalone.php` file to your server.

### Step 2: Configure Database Connections
Open the script and update the database configurations at the top:

```php
// WHMCS Database (Source)
$whmcs_config = [
    'host' => '127.0.0.1',           // Your WHMCS database host
    'port' => 3306,                  // Your WHMCS database port
    'database' => 'your_whmcs_db',   // Your WHMCS database name
    'username' => 'your_username',   // Your WHMCS database username
    'password' => 'your_password'    // Your WHMCS database password
];

// New Admin System Database (Destination)
$laravel_config = [
    'host' => '127.0.0.1',           // Your new database host
    'port' => 3306,                  // Your new database port
    'database' => 'whmcs_admin',     // Your new database name
    'username' => 'your_username',   // Your new database username
    'password' => 'your_password'    // Your new database password
];
```

### Step 3: Test the Migration (Dry Run)
```bash
# Test migration without making changes
php whmcs_migration_standalone.php
```

Set `'dry_run' => true` in the script for testing.

### Step 4: Run the Actual Migration
Once you're satisfied with the dry run:

1. Set `'dry_run' => false` in the script
2. Run the migration:

```bash
php whmcs_migration_standalone.php
```

## What Gets Migrated

### ✅ Clients
- All client information (name, email, company, etc.)
- Address fields combined into single field
- Status mapping (Active, Inactive, Suspended)
- Registration dates and login history

### ✅ Services
- Hosting services with product names
- Service status and billing cycles
- Domain associations
- Due dates and recurring amounts

### ✅ Domains
- Domain names and registrars
- Registration and expiry dates
- Auto-renewal settings
- Status tracking

### ✅ Invoices
- Invoice numbers and amounts
- Payment status and due dates
- Client associations
- Payment methods

### ✅ Support Tickets
- Ticket subjects and status
- Priority levels and departments
- Client associations
- Admin assignments

### ✅ Ticket Replies
- All ticket conversations
- Admin vs client reply tracking
- Timestamps and sender information

## Migration Features

### 🛡️ Safety Features
- **Dry Run Mode**: Test without making changes
- **Batch Processing**: Handles large datasets
- **Error Handling**: Continues on errors, logs issues
- **Progress Tracking**: Shows migration progress
- **Data Validation**: Checks relationships

### 📊 Automatic Updates
- **Client Statistics**: Calculates total spent and active services
- **Relationship Mapping**: Maintains foreign key relationships
- **Status Normalization**: Maps WHMCS statuses to new system
- **Date Formatting**: Converts date formats properly

## Troubleshooting

### Common Issues

**1. Database Connection Failed**
```
Solution: Check your database credentials and ensure both databases are accessible
```

**2. Table Already Exists Error**
```
Solution: The script creates tables automatically. If you have existing tables, 
they will be updated, not replaced.
```

**3. Foreign Key Constraint Failed**
```
Solution: This happens when a service/invoice references a non-existent client.
The script will skip these records and log the errors.
```

**4. Memory Limit Exceeded**
```
Solution: Reduce the batch_size in the script configuration:
'batch_size' => 500,  // Reduce from 1000 to 500
```

### Performance Tips

**For Large Databases (10,000+ records):**
- Set `'verbose' => false` to reduce output
- Increase `'batch_size'` to 2000-5000
- Run during off-peak hours
- Consider running on the database server directly

**For Small Databases (< 1,000 records):**
- Keep default settings
- Monitor progress with `'verbose' => true`

## Post-Migration Steps

### 1. Verify Data
```sql
-- Check record counts
SELECT 'clients' as table_name, COUNT(*) as count FROM clients
UNION ALL
SELECT 'services', COUNT(*) FROM services
UNION ALL
SELECT 'domains', COUNT(*) FROM domains
UNION ALL
SELECT 'invoices', COUNT(*) FROM invoices
UNION ALL
SELECT 'tickets', COUNT(*) FROM tickets;
```

### 2. Test Your Laravel API
```bash
# Test the API endpoints
curl http://localhost:8002/api/clients
curl http://localhost:8002/api/dashboard/stats
```

### 3. Update Frontend Configuration
Update your React frontend to point to the Laravel backend:

```javascript
// In your .env file
REACT_APP_BACKEND_URL=http://localhost:8002
```

## Data Security

### ⚠️ Important Security Notes
- **Backup First**: Always backup both databases before migration
- **Secure Credentials**: Never commit database credentials to version control
- **Test Environment**: Run migration in test environment first
- **Access Control**: Limit database access during migration

### 🔒 Best Practices
- Run migration during maintenance window
- Monitor migration progress and logs
- Verify data integrity after migration
- Keep WHMCS database as backup until system is stable

## Support

If you encounter issues:
1. Check the migration logs for specific error messages
2. Verify database connectivity and credentials
3. Ensure proper PHP version (7.4+ recommended)
4. Check MySQL version compatibility

## Migration Checklist

- [ ] Backup WHMCS database
- [ ] Create new database for admin system
- [ ] Configure database credentials in script
- [ ] Run dry-run migration
- [ ] Review dry-run results
- [ ] Run actual migration
- [ ] Verify data integrity
- [ ] Update frontend configuration
- [ ] Test API endpoints
- [ ] Update client statistics
- [ ] Perform user acceptance testing

---

**Ready to migrate your WHMCS data! 🚀**
