# CSV Import Instructions

## Step 1: Export CSV from WHMCS

### Option A: Using phpMyAdmin (Recommended)
1. Log into phpMyAdmin for your WHMCS database
2. Export each table as CSV:

#### Services Export
```sql
SELECT 
    id,
    userid,
    producttype,
    domain,
    domainstatus,
    nextduedate,
    amount,
    billingcycle,
    regdate
FROM tblhosting
ORDER BY id
```
- Click "Export" → Format: CSV
- Save as: `services.csv`

#### Domains Export
```sql
SELECT 
    id,
    userid,
    domain,
    registrar,
    status,
    registrationdate,
    expirydate
FROM tbldomains
ORDER BY id
```
- Save as: `domains.csv`

#### Invoices Export
```sql
SELECT 
    id,
    invoicenum,
    userid,
    total,
    status,
    duedate,
    date,
    notes,
    paymentmethod
FROM tblinvoices
ORDER BY id
```
- Save as: `invoices.csv`

### Option B: Using MySQL Command Line
```bash
# Services
mysql -h YOUR_HOST -u YOUR_USER -p YOUR_DB -e "SELECT id, userid, producttype, domain, domainstatus, nextduedate, amount, billingcycle, regdate FROM tblhosting ORDER BY id" > services.csv

# Domains
mysql -h YOUR_HOST -u YOUR_USER -p YOUR_DB -e "SELECT id, userid, domain, registrar, status, registrationdate, expirydate FROM tbldomains ORDER BY id" > domains.csv

# Invoices
mysql -h YOUR_HOST -u YOUR_USER -p YOUR_DB -e "SELECT id, invoicenum, userid, total, status, duedate, date, notes, paymentmethod FROM tblinvoices ORDER BY id" > invoices.csv
```

## Step 2: Clear Existing Data (Keep Clients)

```bash
php clear_data_except_clients.php
```

This will:
- ✓ Keep all 890 clients
- ✗ Delete all services
- ✗ Delete all domains
- ✗ Delete all invoices
- ✗ Delete all tickets

## Step 3: Upload CSV Files

Upload your exported CSV files to the server:
- `services.csv`
- `domains.csv`
- `invoices.csv`

## Step 4: Update CSV Import Script

Edit `/app/csv_import.php` (lines 11-15):
```php
$csv_files = [
    'services' => '/full/path/to/services.csv',
    'domains' => '/full/path/to/domains.csv',
    'invoices' => '/full/path/to/invoices.csv',
];
```

## Step 5: Run CSV Import

```bash
php csv_import.php
```

## Step 6: Verify Data

The import script will show:
- Number of records imported
- Number of records skipped
- Final counts

Then refresh your Laravel dashboard to see all data!

## Notes

### About Skipped Records
- Services/domains/invoices with non-existent client IDs will be skipped
- This is expected behavior (orphaned data)
- The script will report how many were skipped

### CSV Format Requirements
- **No header row** should be included in the query export
- If your CSV has headers, delete the first line before import
- Make sure date format is: YYYY-MM-DD
- All columns should match the order in the SQL queries above

### Troubleshooting

**If import fails:**
1. Check CSV file paths are correct
2. Ensure CSV files don't have header rows
3. Check for special characters in data
4. Look at the error messages for specific rows

**After import, if counts are still wrong:**
1. Re-export from WHMCS with exact queries above
2. Check for hidden characters or encoding issues
3. Try importing one table at a time to identify issues

## Benefits of CSV Import
✅ Bypasses orphaned data issues
✅ Direct control over what data is imported
✅ Can verify CSV data before import
✅ Can edit CSV to clean data if needed
✅ Faster than row-by-row migration
