<?php
/**
 * WHMCS Complete Migration Script - Handles ID Gaps
 * Fixes the issue where clients with high IDs were being skipped
 */

// Database configurations (UPDATE THESE!)
$whmcs_config = [
    'host' => 'jgcb.your-database.de',
    'port' => 3306,
    'database' => 'claredigm_db1',  // Your WHMCS database
    'username' => 'classigm_1',
    'password' => 'e1§Ky§MuhxoV'
];

$destination_config = [
    'host' => 'jgcb.your-database.de',
    'port' => 3306,
    'database' => 'claredigm_db1',  // Same database (different tables)
    'username' => 'classigm_1',
    'password' => 'e1§Ky§MuhxoV'
];

// Connect to databases
try {
    $whmcs_dsn = "mysql:host={$whmcs_config['host']};port={$whmcs_config['port']};dbname={$whmcs_config['database']};charset=utf8mb4";
    $whmcs = new PDO($whmcs_dsn, $whmcs_config['username'], $whmcs_config['password']);
    $whmcs->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    echo "✓ Connected to WHMCS database\n";
    
    $dest_dsn = "mysql:host={$destination_config['host']};port={$destination_config['port']};dbname={$destination_config['database']};charset=utf8mb4";
    $dest = new PDO($dest_dsn, $destination_config['username'], $destination_config['password']);
    $dest->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    echo "✓ Connected to destination database\n\n";
    
} catch (PDOException $e) {
    die("Connection failed: " . $e->getMessage() . "\n");
}

echo "=== COMPLETE MIGRATION (Fixing Missing Clients) ===\n\n";

// 1. MIGRATE ALL CLIENTS (using WHERE instead of OFFSET to avoid gaps)
echo "--- Migrating Clients ---\n";
$clientStmt = $whmcs->query("SELECT * FROM tblclients ORDER BY id");
$migrated = 0;
$skipped = 0;

while ($client = $clientStmt->fetch(PDO::FETCH_OBJ)) {
    try {
        $address = trim(implode(', ', array_filter([
            $client->address1 ?? '',
            $client->address2 ?? '',
            $client->city ?? '',
            $client->state ?? '',
            $client->postcode ?? '',
            $client->country ?? ''
        ])));
        
        $status = in_array($client->status ?? '', ['Active', 'Inactive', 'Closed']) ? $client->status : 'Inactive';
        
        $insertSql = "INSERT INTO clients (
            id, first_name, last_name, email, company, phone, address, status,
            registration_date, last_login, total_spent, active_services, created_at, updated_at
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW(), NOW())
        ON DUPLICATE KEY UPDATE
            first_name = VALUES(first_name),
            last_name = VALUES(last_name),
            email = VALUES(email),
            company = VALUES(company),
            phone = VALUES(phone),
            address = VALUES(address),
            status = VALUES(status),
            updated_at = NOW()";
        
        $insertStmt = $dest->prepare($insertSql);
        $insertStmt->execute([
            $client->id,
            $client->firstname ?? '',
            $client->lastname ?? '',
            $client->email ?? '',
            $client->companyname ?? null,
            $client->phonenumber ?? null,
            $address ?: null,
            $status,
            $client->datecreated ?? null,
            null, // last_login
            0, // total_spent - will update later
            0  // active_services - will update later
        ]);
        
        $migrated++;
    } catch (Exception $e) {
        echo "Error with client {$client->id}: " . $e->getMessage() . "\n";
        $skipped++;
    }
}
echo "✓ Clients: $migrated migrated, $skipped errors\n\n";

// 2. MIGRATE SERVICES
echo "--- Migrating Services ---\n";
$serviceStmt = $whmcs->query("SELECT * FROM tblhosting ORDER BY id");
$migrated = 0;
$skipped = 0;

while ($service = $serviceStmt->fetch(PDO::FETCH_OBJ)) {
    try {
        // Check if client exists
        $checkClient = $dest->prepare("SELECT id FROM clients WHERE id = ?");
        $checkClient->execute([$service->userid]);
        
        if (!$checkClient->fetch()) {
            $skipped++;
            continue; // Skip if client not found
        }
        
        $status = in_array($service->domainstatus ?? '', ['Active', 'Pending', 'Suspended', 'Terminated', 'Cancelled', 'Fraud']) 
            ? $service->domainstatus : 'Pending';
        
        $insertSql = "INSERT INTO services (
            id, client_id, product_name, domain, status, next_due_date, 
            recurring_amount, billing_cycle, registration_date, created_at, updated_at
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, NOW(), NOW())
        ON DUPLICATE KEY UPDATE
            status = VALUES(status),
            next_due_date = VALUES(next_due_date),
            updated_at = NOW()";
        
        $insertStmt = $dest->prepare($insertSql);
        $insertStmt->execute([
            $service->id,
            $service->userid,
            $service->producttype ?? 'Service',
            $service->domain ?? null,
            $status,
            $service->nextduedate ?? null,
            $service->amount ?? 0,
            $service->billingcycle ?? 'Monthly',
            $service->regdate ?? null
        ]);
        
        $migrated++;
    } catch (Exception $e) {
        echo "Error with service {$service->id}: " . $e->getMessage() . "\n";
        $skipped++;
    }
}
echo "✓ Services: $migrated migrated, $skipped skipped\n\n";

// 3. MIGRATE DOMAINS
echo "--- Migrating Domains ---\n";
$domainStmt = $whmcs->query("SELECT * FROM tbldomains ORDER BY id");
$migrated = 0;
$skipped = 0;

while ($domain = $domainStmt->fetch(PDO::FETCH_OBJ)) {
    try {
        // Check if client exists
        $checkClient = $dest->prepare("SELECT id FROM clients WHERE id = ?");
        $checkClient->execute([$domain->userid]);
        
        if (!$checkClient->fetch()) {
            $skipped++;
            continue;
        }
        
        $status = in_array($domain->status ?? '', ['Active', 'Pending', 'Expired', 'Cancelled', 'Transferred', 'Grace', 'Redemption']) 
            ? $domain->status : 'Pending';
        
        $insertSql = "INSERT INTO domains (
            id, client_id, domain, registrar, status, registration_date, expiry_date, 
            auto_renew, nameservers, created_at, updated_at
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, NOW(), NOW())
        ON DUPLICATE KEY UPDATE
            status = VALUES(status),
            expiry_date = VALUES(expiry_date),
            updated_at = NOW()";
        
        $insertStmt = $dest->prepare($insertSql);
        $insertStmt->execute([
            $domain->id,
            $domain->userid,
            $domain->domain ?? '',
            $domain->registrar ?? 'Unknown',
            $status,
            $domain->registrationdate ?? null,
            $domain->expirydate ?? null,
            ($domain->recurringamount ?? 0) > 0 ? 1 : 0,
            null
        ]);
        
        $migrated++;
    } catch (Exception $e) {
        echo "Error with domain {$domain->id}: " . $e->getMessage() . "\n";
        $skipped++;
    }
}
echo "✓ Domains: $migrated migrated, $skipped skipped\n\n";

// 4. MIGRATE INVOICES
echo "--- Migrating Invoices ---\n";
$invoiceStmt = $whmcs->query("SELECT * FROM tblinvoices ORDER BY id");
$migrated = 0;
$skipped = 0;

while ($invoice = $invoiceStmt->fetch(PDO::FETCH_OBJ)) {
    try {
        // Check if client exists
        $checkClient = $dest->prepare("SELECT id FROM clients WHERE id = ?");
        $checkClient->execute([$invoice->userid]);
        
        if (!$checkClient->fetch()) {
            $skipped++;
            continue;
        }
        
        $status = in_array($invoice->status ?? '', ['Paid', 'Pending', 'Overdue', 'Cancelled']) 
            ? $invoice->status : 'Pending';
        
        $insertSql = "INSERT INTO invoices (
            id, invoice_id, client_id, amount, status, due_date, issue_date,
            description, payment_method, items, created_at, updated_at
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW(), NOW())
        ON DUPLICATE KEY UPDATE
            status = VALUES(status),
            amount = VALUES(amount),
            updated_at = NOW()";
        
        $insertStmt = $dest->prepare($insertSql);
        $insertStmt->execute([
            $invoice->id,
            $invoice->invoicenum ?? $invoice->id,
            $invoice->userid,
            $invoice->total ?? 0,
            $status,
            $invoice->duedate ?? null,
            $invoice->date ?? null,
            $invoice->notes ?? null,
            $invoice->paymentmethod ?? null,
            null
        ]);
        
        $migrated++;
    } catch (Exception $e) {
        echo "Error with invoice {$invoice->id}: " . $e->getMessage() . "\n";
        $skipped++;
    }
}
echo "✓ Invoices: $migrated migrated, $skipped skipped\n\n";

// 5. Update client statistics
echo "--- Updating Client Statistics ---\n";
$dest->exec("
    UPDATE clients c
    LEFT JOIN (
        SELECT client_id, COUNT(*) as count
        FROM services
        WHERE status = 'Active'
        GROUP BY client_id
    ) s ON c.id = s.client_id
    LEFT JOIN (
        SELECT client_id, SUM(amount) as total
        FROM invoices
        WHERE status = 'Paid'
        GROUP BY client_id
    ) i ON c.id = i.client_id
    SET 
        c.active_services = COALESCE(s.count, 0),
        c.total_spent = COALESCE(i.total, 0)
");
echo "✓ Client statistics updated\n\n";

echo "=== MIGRATION COMPLETE ===\n";
echo "Please refresh your Laravel dashboard to see the updated data.\n";
