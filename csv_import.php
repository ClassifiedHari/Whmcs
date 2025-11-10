<?php
/**
 * CSV Import Script for WHMCS Data
 * Imports services, domains, and invoices from CSV files
 */

$config = [
    'host' => 'jgcb.your-database.de',
    'port' => 3306,
    'database' => 'claredigm_db1',
    'username' => 'classigm_1',
    'password' => 'e1§Ky§MuhxoV'
];

// CSV file paths - UPDATE THESE
$csv_files = [
    'services' => '/path/to/services.csv',
    'domains' => '/path/to/domains.csv',
    'invoices' => '/path/to/invoices.csv',
];

try {
    $dsn = "mysql:host={$config['host']};port={$config['port']};dbname={$config['database']};charset=utf8mb4";
    $pdo = new PDO($dsn, $config['username'], $config['password']);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    echo "Connected to database\n\n";
    
    // ========== IMPORT SERVICES ==========
    if (file_exists($csv_files['services'])) {
        echo "=== IMPORTING SERVICES ===" . PHP_EOL;
        $file = fopen($csv_files['services'], 'r');
        $header = fgetcsv($file); // Skip header row
        
        $imported = 0;
        $skipped = 0;
        
        $stmt = $pdo->prepare("
            INSERT INTO services (
                id, client_id, product_name, domain, status, next_due_date,
                recurring_amount, billing_cycle, registration_date, created_at, updated_at
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, NOW(), NOW())
            ON DUPLICATE KEY UPDATE
                status = VALUES(status),
                next_due_date = VALUES(next_due_date),
                recurring_amount = VALUES(recurring_amount),
                updated_at = NOW()
        ");
        
        while (($row = fgetcsv($file)) !== false) {
            try {
                // Map CSV columns (adjust indices based on your CSV structure)
                // Expected: id, userid, producttype, domain, domainstatus, nextduedate, amount, billingcycle, regdate
                $status = in_array($row[4] ?? '', ['Active', 'Pending', 'Suspended', 'Terminated', 'Cancelled']) 
                    ? $row[4] : 'Pending';
                
                $stmt->execute([
                    $row[0],  // id
                    $row[1],  // client_id (userid)
                    $row[2] ?? 'Service',  // product_name (producttype)
                    $row[3] ?? null,  // domain
                    $status,
                    $row[5] ?? null,  // next_due_date (nextduedate)
                    $row[6] ?? 0,  // recurring_amount (amount)
                    $row[7] ?? 'Monthly',  // billing_cycle (billingcycle)
                    $row[8] ?? null,  // registration_date (regdate)
                ]);
                $imported++;
            } catch (Exception $e) {
                $skipped++;
                echo "Skipped service row: " . $e->getMessage() . "\n";
            }
        }
        fclose($file);
        echo "✓ Services: $imported imported, $skipped skipped\n\n";
    }
    
    // ========== IMPORT DOMAINS ==========
    if (file_exists($csv_files['domains'])) {
        echo "=== IMPORTING DOMAINS ===" . PHP_EOL;
        $file = fopen($csv_files['domains'], 'r');
        $header = fgetcsv($file);
        
        $imported = 0;
        $skipped = 0;
        
        $stmt = $pdo->prepare("
            INSERT INTO domains (
                id, client_id, domain, registrar, status, registration_date, expiry_date,
                auto_renew, nameservers, created_at, updated_at
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, NOW(), NOW())
            ON DUPLICATE KEY UPDATE
                status = VALUES(status),
                expiry_date = VALUES(expiry_date),
                updated_at = NOW()
        ");
        
        while (($row = fgetcsv($file)) !== false) {
            try {
                // Expected: id, userid, domain, registrar, status, registrationdate, expirydate
                $status = in_array($row[4] ?? '', ['Active', 'Pending', 'Expired', 'Cancelled', 'Transferred']) 
                    ? $row[4] : 'Pending';
                
                $stmt->execute([
                    $row[0],  // id
                    $row[1],  // client_id (userid)
                    $row[2] ?? '',  // domain
                    $row[3] ?? 'Unknown',  // registrar
                    $status,
                    $row[5] ?? null,  // registration_date
                    $row[6] ?? null,  // expiry_date
                    0,  // auto_renew
                    null,  // nameservers
                ]);
                $imported++;
            } catch (Exception $e) {
                $skipped++;
                echo "Skipped domain row: " . $e->getMessage() . "\n";
            }
        }
        fclose($file);
        echo "✓ Domains: $imported imported, $skipped skipped\n\n";
    }
    
    // ========== IMPORT INVOICES ==========
    if (file_exists($csv_files['invoices'])) {
        echo "=== IMPORTING INVOICES ===" . PHP_EOL;
        $file = fopen($csv_files['invoices'], 'r');
        $header = fgetcsv($file);
        
        $imported = 0;
        $skipped = 0;
        
        $stmt = $pdo->prepare("
            INSERT INTO invoices (
                id, invoice_id, client_id, amount, status, due_date, issue_date,
                description, payment_method, items, created_at, updated_at
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW(), NOW())
            ON DUPLICATE KEY UPDATE
                status = VALUES(status),
                amount = VALUES(amount),
                updated_at = NOW()
        ");
        
        while (($row = fgetcsv($file)) !== false) {
            try {
                // Expected: id, invoicenum, userid, total, status, duedate, date, notes, paymentmethod
                $status = in_array($row[4] ?? '', ['Paid', 'Pending', 'Overdue', 'Cancelled']) 
                    ? $row[4] : 'Pending';
                
                $stmt->execute([
                    $row[0],  // id
                    $row[1] ?? $row[0],  // invoice_id (invoicenum)
                    $row[2],  // client_id (userid)
                    $row[3] ?? 0,  // amount (total)
                    $status,
                    $row[5] ?? null,  // due_date (duedate)
                    $row[6] ?? null,  // issue_date (date)
                    $row[7] ?? null,  // description (notes)
                    $row[8] ?? null,  // payment_method (paymentmethod)
                    null,  // items
                ]);
                $imported++;
            } catch (Exception $e) {
                $skipped++;
                echo "Skipped invoice row: " . $e->getMessage() . "\n";
            }
        }
        fclose($file);
        echo "✓ Invoices: $imported imported, $skipped skipped\n\n";
    }
    
    // Update client statistics
    echo "=== UPDATING CLIENT STATISTICS ===" . PHP_EOL;
    $pdo->exec("
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
    
    // Final counts
    echo "=== FINAL COUNTS ===" . PHP_EOL;
    $stmt = $pdo->query("SELECT COUNT(*) FROM services");
    echo "Services: " . $stmt->fetchColumn() . "\n";
    
    $stmt = $pdo->query("SELECT COUNT(*) FROM domains");
    echo "Domains: " . $stmt->fetchColumn() . "\n";
    
    $stmt = $pdo->query("SELECT COUNT(*) FROM invoices");
    echo "Invoices: " . $stmt->fetchColumn() . "\n";
    
    $stmt = $pdo->query("SELECT COUNT(*) FROM invoices WHERE status = 'Paid'");
    echo "Paid Invoices: " . $stmt->fetchColumn() . "\n";
    
    echo "\n✓ CSV import completed successfully!\n";
    
} catch (PDOException $e) {
    die("Error: " . $e->getMessage() . "\n");
}
