<?php
/**
 * WHMCS 8.11.2 to Custom Admin System Migration Script - FIXED VERSION
 * 
 * This standalone PHP script migrates data from WHMCS to your new admin system
 * No Laravel or framework dependencies required
 * 
 * FIXED: MariaDB LIMIT/OFFSET syntax issue
 * 
 * Author: E1 AI Agent
 * Version: 1.1
 * Date: 2024
 */

// =============================================================================
// CONFIGURATION - UPDATE THESE WITH YOUR DATABASE DETAILS
// =============================================================================

// WHMCS Database (Source)
$whmcs_config = [
    'host' => '127.0.0.1',
    'port' => 3306,
    'database' => 'your_whmcs_database',
    'username' => 'your_whmcs_username', 
    'password' => 'your_whmcs_password'
];

// New Admin System Database (Destination)
$laravel_config = [
    'host' => '127.0.0.1',
    'port' => 3306,
    'database' => 'whmcs_admin',
    'username' => 'your_mysql_username',
    'password' => 'your_mysql_password'
];

// Migration Settings
$settings = [
    'batch_size' => 1000,
    'dry_run' => false,  // Set to true to test without making changes
    'verbose' => true    // Set to false to reduce output
];

// =============================================================================
// MIGRATION SCRIPT - DO NOT MODIFY BELOW THIS LINE
// =============================================================================

class WhmcsMigration {
    private $whmcs_pdo;
    private $laravel_pdo;
    private $settings;
    private $stats = [];
    
    public function __construct($whmcs_config, $laravel_config, $settings) {
        $this->settings = $settings;
        $this->connectDatabases($whmcs_config, $laravel_config);
    }
    
    private function connectDatabases($whmcs_config, $laravel_config) {
        try {
            // Connect to WHMCS database
            $whmcs_dsn = "mysql:host={$whmcs_config['host']};port={$whmcs_config['port']};dbname={$whmcs_config['database']};charset=utf8mb4";
            $this->whmcs_pdo = new PDO($whmcs_dsn, $whmcs_config['username'], $whmcs_config['password']);
            $this->whmcs_pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            $this->log("✓ Connected to WHMCS database successfully");
            
            // Connect to Laravel database
            $laravel_dsn = "mysql:host={$laravel_config['host']};port={$laravel_config['port']};dbname={$laravel_config['database']};charset=utf8mb4";
            $this->laravel_pdo = new PDO($laravel_dsn, $laravel_config['username'], $laravel_config['password']);
            $this->laravel_pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            $this->log("✓ Connected to destination database successfully");
            
        } catch (PDOException $e) {
            die("Database connection failed: " . $e->getMessage() . "\n");
        }
    }
    
    public function run() {
        $this->log("\n=== WHMCS 8.11.2 Data Migration Started ===");
        $this->log("Dry Run Mode: " . ($this->settings['dry_run'] ? 'YES' : 'NO'));
        $this->log("Batch Size: " . $this->settings['batch_size']);
        $this->log("\n");
        
        try {
            // Create tables if they don't exist
            $this->createTablesIfNotExist();
            
            // Migrate data in order (respecting foreign keys)
            $this->migrateClients();
            $this->migrateServices();
            $this->migrateDomains();
            $this->migrateInvoices();
            $this->migrateTickets();
            $this->migrateTicketReplies();
            $this->updateClientStats();
            
            // Display final statistics
            $this->displayStats();
            $this->log("\n✓ Migration completed successfully!");
            
        } catch (Exception $e) {
            $this->log("✗ Migration failed: " . $e->getMessage());
            throw $e;
        }
    }
    
    private function createTablesIfNotExist() {
        $this->log("Creating database tables if they don't exist...");
        
        $tables = [
            'clients' => "
                CREATE TABLE IF NOT EXISTS `clients` (
                    `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
                    `first_name` varchar(255) NOT NULL,
                    `last_name` varchar(255) NOT NULL,
                    `email` varchar(255) NOT NULL,
                    `company` varchar(255) DEFAULT NULL,
                    `phone` varchar(255) DEFAULT NULL,
                    `address` text DEFAULT NULL,
                    `status` enum('Active','Suspended','Inactive') NOT NULL DEFAULT 'Active',
                    `registration_date` timestamp NOT NULL DEFAULT current_timestamp(),
                    `last_login` timestamp NULL DEFAULT NULL,
                    `total_spent` decimal(12,2) NOT NULL DEFAULT 0.00,
                    `active_services` int(11) NOT NULL DEFAULT 0,
                    `created_at` timestamp NULL DEFAULT NULL,
                    `updated_at` timestamp NULL DEFAULT NULL,
                    `deleted_at` timestamp NULL DEFAULT NULL,
                    PRIMARY KEY (`id`),
                    UNIQUE KEY `clients_email_unique` (`email`),
                    KEY `clients_status_index` (`status`)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
            ",
            
            'services' => "
                CREATE TABLE IF NOT EXISTS `services` (
                    `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
                    `client_id` bigint(20) UNSIGNED NOT NULL,
                    `product_name` varchar(255) NOT NULL,
                    `domain` varchar(255) DEFAULT NULL,
                    `status` enum('Active','Suspended','Pending','Terminated') NOT NULL DEFAULT 'Pending',
                    `next_due_date` date NOT NULL,
                    `recurring_amount` decimal(10,2) NOT NULL,
                    `billing_cycle` enum('Monthly','Annually','Quarterly','Biannually') NOT NULL DEFAULT 'Monthly',
                    `registration_date` timestamp NOT NULL DEFAULT current_timestamp(),
                    `created_at` timestamp NULL DEFAULT NULL,
                    `updated_at` timestamp NULL DEFAULT NULL,
                    `deleted_at` timestamp NULL DEFAULT NULL,
                    PRIMARY KEY (`id`),
                    KEY `services_client_id_foreign` (`client_id`),
                    KEY `services_status_index` (`status`),
                    KEY `services_next_due_date_index` (`next_due_date`),
                    CONSTRAINT `services_client_id_foreign` FOREIGN KEY (`client_id`) REFERENCES `clients` (`id`) ON DELETE CASCADE
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
            ",
            
            'domains' => "
                CREATE TABLE IF NOT EXISTS `domains` (
                    `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
                    `client_id` bigint(20) UNSIGNED NOT NULL,
                    `domain` varchar(255) NOT NULL,
                    `registrar` varchar(255) NOT NULL,
                    `status` enum('Active','Expired','Pending','Cancelled') NOT NULL DEFAULT 'Pending',
                    `registration_date` date NOT NULL,
                    `expiry_date` date NOT NULL,
                    `auto_renew` tinyint(1) NOT NULL DEFAULT 1,
                    `nameservers` json DEFAULT NULL,
                    `created_at` timestamp NULL DEFAULT NULL,
                    `updated_at` timestamp NULL DEFAULT NULL,
                    `deleted_at` timestamp NULL DEFAULT NULL,
                    PRIMARY KEY (`id`),
                    UNIQUE KEY `domains_domain_unique` (`domain`),
                    KEY `domains_client_id_foreign` (`client_id`),
                    KEY `domains_status_index` (`status`),
                    KEY `domains_expiry_date_index` (`expiry_date`),
                    CONSTRAINT `domains_client_id_foreign` FOREIGN KEY (`client_id`) REFERENCES `clients` (`id`) ON DELETE CASCADE
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
            ",
            
            'invoices' => "
                CREATE TABLE IF NOT EXISTS `invoices` (
                    `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
                    `invoice_id` varchar(255) NOT NULL,
                    `client_id` bigint(20) UNSIGNED NOT NULL,
                    `amount` decimal(12,2) NOT NULL,
                    `status` enum('Paid','Pending','Overdue','Cancelled') NOT NULL DEFAULT 'Pending',
                    `due_date` date NOT NULL,
                    `issue_date` timestamp NOT NULL DEFAULT current_timestamp(),
                    `description` text NOT NULL,
                    `payment_method` varchar(255) DEFAULT NULL,
                    `items` json DEFAULT NULL,
                    `created_at` timestamp NULL DEFAULT NULL,
                    `updated_at` timestamp NULL DEFAULT NULL,
                    `deleted_at` timestamp NULL DEFAULT NULL,
                    PRIMARY KEY (`id`),
                    UNIQUE KEY `invoices_invoice_id_unique` (`invoice_id`),
                    KEY `invoices_client_id_foreign` (`client_id`),
                    KEY `invoices_status_index` (`status`),
                    KEY `invoices_due_date_index` (`due_date`),
                    CONSTRAINT `invoices_client_id_foreign` FOREIGN KEY (`client_id`) REFERENCES `clients` (`id`) ON DELETE CASCADE
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
            ",
            
            'tickets' => "
                CREATE TABLE IF NOT EXISTS `tickets` (
                    `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
                    `ticket_id` varchar(255) NOT NULL,
                    `client_id` bigint(20) UNSIGNED NOT NULL,
                    `subject` varchar(255) NOT NULL,
                    `status` enum('Open','In Progress','Resolved','Closed') NOT NULL DEFAULT 'Open',
                    `priority` enum('Low','Medium','High','Urgent') NOT NULL DEFAULT 'Medium',
                    `department` varchar(255) NOT NULL,
                    `category` varchar(255) DEFAULT NULL,
                    `assigned_to` varchar(255) DEFAULT NULL,
                    `created_at` timestamp NULL DEFAULT NULL,
                    `updated_at` timestamp NULL DEFAULT NULL,
                    `deleted_at` timestamp NULL DEFAULT NULL,
                    PRIMARY KEY (`id`),
                    UNIQUE KEY `tickets_ticket_id_unique` (`ticket_id`),
                    KEY `tickets_client_id_foreign` (`client_id`),
                    KEY `tickets_status_index` (`status`),
                    KEY `tickets_priority_index` (`priority`),
                    CONSTRAINT `tickets_client_id_foreign` FOREIGN KEY (`client_id`) REFERENCES `clients` (`id`) ON DELETE CASCADE
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
            ",
            
            'ticket_replies' => "
                CREATE TABLE IF NOT EXISTS `ticket_replies` (
                    `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
                    `ticket_id` bigint(20) UNSIGNED NOT NULL,
                    `message` text NOT NULL,
                    `sender` varchar(255) NOT NULL,
                    `is_admin` tinyint(1) NOT NULL DEFAULT 0,
                    `created_at` timestamp NULL DEFAULT NULL,
                    `updated_at` timestamp NULL DEFAULT NULL,
                    `deleted_at` timestamp NULL DEFAULT NULL,
                    PRIMARY KEY (`id`),
                    KEY `ticket_replies_ticket_id_foreign` (`ticket_id`),
                    KEY `ticket_replies_is_admin_index` (`is_admin`),
                    CONSTRAINT `ticket_replies_ticket_id_foreign` FOREIGN KEY (`ticket_id`) REFERENCES `tickets` (`id`) ON DELETE CASCADE
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
            "
        ];
        
        foreach ($tables as $tableName => $sql) {
            $this->laravel_pdo->exec($sql);
            $this->log("✓ Table '{$tableName}' ready");
        }
    }
    
    private function migrateClients() {
        $this->log("\n--- Migrating Clients ---");
        
        // Get total count
        $stmt = $this->whmcs_pdo->query("SELECT COUNT(*) FROM tblclients");
        $total = $stmt->fetchColumn();
        $this->log("Total clients to migrate: $total");
        
        if ($total == 0) {
            $this->log("No clients to migrate");
            return;
        }
        
        $migrated = 0;
        $errors = 0;
        $offset = 0;
        $batchSize = (int)$this->settings['batch_size'];
        
        while ($offset < $total) {
            // FIXED: Use string concatenation instead of parameter binding for LIMIT/OFFSET
            $sql = "SELECT * FROM tblclients ORDER BY id LIMIT $batchSize OFFSET $offset";
            $stmt = $this->whmcs_pdo->query($sql);
            $clients = $stmt->fetchAll(PDO::FETCH_OBJ);
            
            foreach ($clients as $client) {
                try {
                    $clientData = $this->transformClient($client);
                    
                    if (!$this->settings['dry_run']) {
                        $this->insertOrUpdateClient($clientData);
                    }
                    
                    $migrated++;
                } catch (Exception $e) {
                    $this->log("Error migrating client ID {$client->id}: " . $e->getMessage());
                    $errors++;
                }
            }
            
            $offset += $batchSize;
            $this->log("Processed $offset / $total clients...");
        }
        
        $this->stats['clients'] = ['migrated' => $migrated, 'errors' => $errors];
        $this->log("✓ Clients migration completed: $migrated migrated, $errors errors");
    }
    
    private function transformClient($client) {
        // Combine address fields
        $address = trim(implode(', ', array_filter([
            $client->address1 ?? '',
            $client->address2 ?? '',
            $client->city ?? '',
            $client->state ?? '',
            $client->postcode ?? '',
            $client->country ?? ''
        ])));
        
        // Map status
        $statusMap = [
            'Active' => 'Active',
            'Inactive' => 'Inactive',
            'Closed' => 'Suspended'
        ];
        
        return [
            'first_name' => $client->firstname ?? '',
            'last_name' => $client->lastname ?? '',
            'email' => $client->email,
            'company' => $client->companyname ?? null,
            'phone' => $client->phonenumber ?? null,
            'address' => $address ?: null,
            'status' => $statusMap[$client->status ?? 'Active'] ?? 'Active',
            'registration_date' => $client->datecreated ?? date('Y-m-d H:i:s'),
            'last_login' => !empty($client->lastlogin) ? $client->lastlogin : null,
            'total_spent' => 0,
            'active_services' => 0,
            'created_at' => $client->datecreated ?? date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s')
        ];
    }
    
    private function insertOrUpdateClient($data) {
        $sql = "
            INSERT INTO clients (
                first_name, last_name, email, company, phone, address, status,
                registration_date, last_login, total_spent, active_services,
                created_at, updated_at
            ) VALUES (
                :first_name, :last_name, :email, :company, :phone, :address, :status,
                :registration_date, :last_login, :total_spent, :active_services,
                :created_at, :updated_at
            ) ON DUPLICATE KEY UPDATE
                first_name = VALUES(first_name),
                last_name = VALUES(last_name),
                company = VALUES(company),
                phone = VALUES(phone),
                address = VALUES(address),
                status = VALUES(status),
                updated_at = VALUES(updated_at)
        ";
        
        $stmt = $this->laravel_pdo->prepare($sql);
        $stmt->execute($data);
    }
    
    private function migrateServices() {
        $this->log("\n--- Migrating Services ---");
        
        // Get total count with product names
        $stmt = $this->whmcs_pdo->query("SELECT COUNT(*) FROM tblhosting");
        $total = $stmt->fetchColumn();
        $this->log("Total services to migrate: $total");
        
        if ($total == 0) {
            $this->log("No services to migrate");
            return;
        }
        
        $migrated = 0;
        $errors = 0;
        $offset = 0;
        $batchSize = (int)$this->settings['batch_size'];
        
        while ($offset < $total) {
            // FIXED: Use string concatenation for LIMIT/OFFSET
            $sql = "
                SELECT h.*, p.name as product_name 
                FROM tblhosting h 
                LEFT JOIN tblproducts p ON h.packageid = p.id 
                ORDER BY h.id 
                LIMIT $batchSize OFFSET $offset
            ";
            $stmt = $this->whmcs_pdo->query($sql);
            $services = $stmt->fetchAll(PDO::FETCH_OBJ);
            
            foreach ($services as $service) {
                try {
                    // Check if client exists
                    if (!$this->clientExists($service->userid)) {
                        $this->log("Skipping service {$service->id} - client {$service->userid} not found");
                        continue;
                    }
                    
                    $serviceData = $this->transformService($service);
                    
                    if (!$this->settings['dry_run']) {
                        $this->insertOrUpdateService($serviceData, $service->id);
                    }
                    
                    $migrated++;
                } catch (Exception $e) {
                    $this->log("Error migrating service ID {$service->id}: " . $e->getMessage());
                    $errors++;
                }
            }
            
            $offset += $batchSize;
            $this->log("Processed $offset / $total services...");
        }
        
        $this->stats['services'] = ['migrated' => $migrated, 'errors' => $errors];
        $this->log("✓ Services migration completed: $migrated migrated, $errors errors");
    }
    
    private function transformService($service) {
        // Map status
        $statusMap = [
            'Active' => 'Active',
            'Suspended' => 'Suspended',
            'Pending' => 'Pending',
            'Terminated' => 'Terminated',
            'Cancelled' => 'Terminated'
        ];
        
        // Map billing cycle
        $billingCycleMap = [
            'Monthly' => 'Monthly',
            'Quarterly' => 'Quarterly',
            'Semi-Annually' => 'Biannually',
            'Annually' => 'Annually',
            'Biennially' => 'Annually',
            'Triennially' => 'Annually'
        ];
        
        return [
            'client_id' => $service->userid,
            'product_name' => $service->product_name ?: 'Unknown Product',
            'domain' => $service->domain ?? null,
            'status' => $statusMap[$service->domainstatus ?? 'Pending'] ?? 'Pending',
            'next_due_date' => $service->nextduedate ?? date('Y-m-d'),
            'recurring_amount' => $service->amount ?? 0,
            'billing_cycle' => $billingCycleMap[$service->billingcycle ?? 'Monthly'] ?? 'Monthly',
            'registration_date' => $service->regdate ?? date('Y-m-d H:i:s'),
            'created_at' => $service->regdate ?? date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s')
        ];
    }
    
    private function insertOrUpdateService($data, $originalId) {
        $sql = "
            INSERT INTO services (
                client_id, product_name, domain, status, next_due_date,
                recurring_amount, billing_cycle, registration_date,
                created_at, updated_at
            ) VALUES (
                :client_id, :product_name, :domain, :status, :next_due_date,
                :recurring_amount, :billing_cycle, :registration_date,
                :created_at, :updated_at
            ) ON DUPLICATE KEY UPDATE
                product_name = VALUES(product_name),
                domain = VALUES(domain),
                status = VALUES(status),
                next_due_date = VALUES(next_due_date),
                recurring_amount = VALUES(recurring_amount),
                billing_cycle = VALUES(billing_cycle),
                updated_at = VALUES(updated_at)
        ";
        
        $stmt = $this->laravel_pdo->prepare($sql);
        $stmt->execute($data);
    }
    
    private function migrateDomains() {
        $this->log("\n--- Migrating Domains ---");
        
        $stmt = $this->whmcs_pdo->query("SELECT COUNT(*) FROM tbldomains");
        $total = $stmt->fetchColumn();
        $this->log("Total domains to migrate: $total");
        
        if ($total == 0) {
            $this->log("No domains to migrate");
            return;
        }
        
        $migrated = 0;
        $errors = 0;
        $offset = 0;
        $batchSize = (int)$this->settings['batch_size'];
        
        while ($offset < $total) {
            // FIXED: Use string concatenation for LIMIT/OFFSET
            $sql = "SELECT * FROM tbldomains ORDER BY id LIMIT $batchSize OFFSET $offset";
            $stmt = $this->whmcs_pdo->query($sql);
            $domains = $stmt->fetchAll(PDO::FETCH_OBJ);
            
            foreach ($domains as $domain) {
                try {
                    if (!$this->clientExists($domain->userid)) {
                        $this->log("Skipping domain {$domain->domain} - client {$domain->userid} not found");
                        continue;
                    }
                    
                    $domainData = $this->transformDomain($domain);
                    
                    if (!$this->settings['dry_run']) {
                        $this->insertOrUpdateDomain($domainData);
                    }
                    
                    $migrated++;
                } catch (Exception $e) {
                    $this->log("Error migrating domain {$domain->domain}: " . $e->getMessage());
                    $errors++;
                }
            }
            
            $offset += $batchSize;
            $this->log("Processed $offset / $total domains...");
        }
        
        $this->stats['domains'] = ['migrated' => $migrated, 'errors' => $errors];
        $this->log("✓ Domains migration completed: $migrated migrated, $errors errors");
    }
    
    private function transformDomain($domain) {
        $statusMap = [
            'Active' => 'Active',
            'Expired' => 'Expired',
            'Pending' => 'Pending',
            'Cancelled' => 'Cancelled',
            'Transferred Away' => 'Cancelled'
        ];
        
        return [
            'client_id' => $domain->userid,
            'domain' => $domain->domain,
            'registrar' => $domain->registrar ?? 'Unknown',
            'status' => $statusMap[$domain->status ?? 'Pending'] ?? 'Pending',
            'registration_date' => $domain->registrationdate ?? date('Y-m-d'),
            'expiry_date' => $domain->expirydate ?? date('Y-m-d'),
            'auto_renew' => !empty($domain->recurringamount) ? 1 : 0,
            'nameservers' => json_encode(['ns1.hosting.com', 'ns2.hosting.com']),
            'created_at' => $domain->registrationdate ?? date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s')
        ];
    }
    
    private function insertOrUpdateDomain($data) {
        $sql = "
            INSERT INTO domains (
                client_id, domain, registrar, status, registration_date,
                expiry_date, auto_renew, nameservers, created_at, updated_at
            ) VALUES (
                :client_id, :domain, :registrar, :status, :registration_date,
                :expiry_date, :auto_renew, :nameservers, :created_at, :updated_at
            ) ON DUPLICATE KEY UPDATE
                client_id = VALUES(client_id),
                registrar = VALUES(registrar),
                status = VALUES(status),
                registration_date = VALUES(registration_date),
                expiry_date = VALUES(expiry_date),
                auto_renew = VALUES(auto_renew),
                nameservers = VALUES(nameservers),
                updated_at = VALUES(updated_at)
        ";
        
        $stmt = $this->laravel_pdo->prepare($sql);
        $stmt->execute($data);
    }
    
    private function migrateInvoices() {
        $this->log("\n--- Migrating Invoices ---");
        
        $stmt = $this->whmcs_pdo->query("SELECT COUNT(*) FROM tblinvoices");
        $total = $stmt->fetchColumn();
        $this->log("Total invoices to migrate: $total");
        
        if ($total == 0) {
            $this->log("No invoices to migrate");
            return;
        }
        
        $migrated = 0;
        $errors = 0;
        $offset = 0;
        $batchSize = (int)$this->settings['batch_size'];
        
        while ($offset < $total) {
            // FIXED: Use string concatenation for LIMIT/OFFSET
            $sql = "SELECT * FROM tblinvoices ORDER BY id LIMIT $batchSize OFFSET $offset";
            $stmt = $this->whmcs_pdo->query($sql);
            $invoices = $stmt->fetchAll(PDO::FETCH_OBJ);
            
            foreach ($invoices as $invoice) {
                try {
                    if (!$this->clientExists($invoice->userid)) {
                        $this->log("Skipping invoice {$invoice->invoicenum} - client {$invoice->userid} not found");
                        continue;
                    }
                    
                    $invoiceData = $this->transformInvoice($invoice);
                    
                    if (!$this->settings['dry_run']) {
                        $this->insertOrUpdateInvoice($invoiceData);
                    }
                    
                    $migrated++;
                } catch (Exception $e) {
                    $this->log("Error migrating invoice {$invoice->invoicenum}: " . $e->getMessage());
                    $errors++;
                }
            }
            
            $offset += $batchSize;
            $this->log("Processed $offset / $total invoices...");
        }
        
        $this->stats['invoices'] = ['migrated' => $migrated, 'errors' => $errors];
        $this->log("✓ Invoices migration completed: $migrated migrated, $errors errors");
    }
    
    private function transformInvoice($invoice) {
        $statusMap = [
            'Paid' => 'Paid',
            'Unpaid' => 'Pending',
            'Overdue' => 'Overdue',
            'Cancelled' => 'Cancelled',
            'Collections' => 'Overdue'
        ];
        
        $invoiceId = $invoice->invoicenum ?: ('INV-' . date('Y') . '-' . str_pad($invoice->id, 6, '0', STR_PAD_LEFT));
        
        return [
            'invoice_id' => $invoiceId,
            'client_id' => $invoice->userid,
            'amount' => $invoice->total ?? 0,
            'status' => $statusMap[$invoice->status ?? 'Pending'] ?? 'Pending',
            'due_date' => $invoice->duedate ?? date('Y-m-d'),
            'issue_date' => $invoice->date ?? date('Y-m-d H:i:s'),
            'description' => $invoice->notes ?? 'Migrated from WHMCS',
            'payment_method' => $invoice->paymentmethod ?? null,
            'items' => json_encode([]),
            'created_at' => $invoice->date ?? date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s')
        ];
    }
    
    private function insertOrUpdateInvoice($data) {
        $sql = "
            INSERT INTO invoices (
                invoice_id, client_id, amount, status, due_date, issue_date,
                description, payment_method, items, created_at, updated_at
            ) VALUES (
                :invoice_id, :client_id, :amount, :status, :due_date, :issue_date,
                :description, :payment_method, :items, :created_at, :updated_at
            ) ON DUPLICATE KEY UPDATE
                client_id = VALUES(client_id),
                amount = VALUES(amount),
                status = VALUES(status),
                due_date = VALUES(due_date),
                description = VALUES(description),
                payment_method = VALUES(payment_method),
                updated_at = VALUES(updated_at)
        ";
        
        $stmt = $this->laravel_pdo->prepare($sql);
        $stmt->execute($data);
    }
    
    private function migrateTickets() {
        $this->log("\n--- Migrating Tickets ---");
        
        $stmt = $this->whmcs_pdo->query("SELECT COUNT(*) FROM tbltickets");
        $total = $stmt->fetchColumn();
        $this->log("Total tickets to migrate: $total");
        
        if ($total == 0) {
            $this->log("No tickets to migrate");
            return;
        }
        
        $migrated = 0;
        $errors = 0;
        $offset = 0;
        $batchSize = (int)$this->settings['batch_size'];
        
        while ($offset < $total) {
            // FIXED: Use string concatenation for LIMIT/OFFSET
            $sql = "
                SELECT t.*, a.username as admin_name 
                FROM tbltickets t 
                LEFT JOIN tbladmins a ON t.admin = a.id 
                ORDER BY t.id 
                LIMIT $batchSize OFFSET $offset
            ";
            $stmt = $this->whmcs_pdo->query($sql);
            $tickets = $stmt->fetchAll(PDO::FETCH_OBJ);
            
            foreach ($tickets as $ticket) {
                try {
                    if ($ticket->userid && !$this->clientExists($ticket->userid)) {
                        $this->log("Skipping ticket {$ticket->tid} - client {$ticket->userid} not found");
                        continue;
                    }
                    
                    $ticketData = $this->transformTicket($ticket);
                    
                    if (!$this->settings['dry_run']) {
                        $this->insertOrUpdateTicket($ticketData);
                    }
                    
                    $migrated++;
                } catch (Exception $e) {
                    $this->log("Error migrating ticket {$ticket->tid}: " . $e->getMessage());
                    $errors++;
                }
            }
            
            $offset += $batchSize;
            $this->log("Processed $offset / $total tickets...");
        }
        
        $this->stats['tickets'] = ['migrated' => $migrated, 'errors' => $errors];
        $this->log("✓ Tickets migration completed: $migrated migrated, $errors errors");
    }
    
    private function transformTicket($ticket) {
        $statusMap = [
            'Open' => 'Open',
            'Answered' => 'In Progress',
            'Customer-Reply' => 'Open',
            'In Progress' => 'In Progress',
            'On Hold' => 'In Progress',
            'Closed' => 'Closed'
        ];
        
        $priorityMap = [
            'Low' => 'Low',
            'Medium' => 'Medium',
            'High' => 'High'
        ];
        
        return [
            'ticket_id' => $ticket->tid ?: ('TKT-' . strtoupper(substr(md5($ticket->id), 0, 6))),
            'client_id' => $ticket->userid ?: 1, // Default to first client if no client
            'subject' => $ticket->subject ?? 'No Subject',
            'status' => $statusMap[$ticket->status ?? 'Open'] ?? 'Open',
            'priority' => $priorityMap[$ticket->urgency ?? 'Medium'] ?? 'Medium',
            'department' => $ticket->deptname ?? 'General',
            'category' => null,
            'assigned_to' => $ticket->admin_name ?? null,
            'created_at' => $ticket->date ?? date('Y-m-d H:i:s'),
            'updated_at' => $ticket->lastreply ?? $ticket->date ?? date('Y-m-d H:i:s')
        ];
    }
    
    private function insertOrUpdateTicket($data) {
        $sql = "
            INSERT INTO tickets (
                ticket_id, client_id, subject, status, priority, department,
                category, assigned_to, created_at, updated_at
            ) VALUES (
                :ticket_id, :client_id, :subject, :status, :priority, :department,
                :category, :assigned_to, :created_at, :updated_at
            ) ON DUPLICATE KEY UPDATE
                client_id = VALUES(client_id),
                subject = VALUES(subject),
                status = VALUES(status),
                priority = VALUES(priority),
                department = VALUES(department),
                assigned_to = VALUES(assigned_to),
                updated_at = VALUES(updated_at)
        ";
        
        $stmt = $this->laravel_pdo->prepare($sql);
        $stmt->execute($data);
    }
    
    private function migrateTicketReplies() {
        $this->log("\n--- Migrating Ticket Replies ---");
        
        $stmt = $this->whmcs_pdo->query("SELECT COUNT(*) FROM tblticketreplies");
        $total = $stmt->fetchColumn();
        $this->log("Total ticket replies to migrate: $total");
        
        if ($total == 0) {
            $this->log("No ticket replies to migrate");
            return;
        }
        
        $migrated = 0;
        $errors = 0;
        $offset = 0;
        $batchSize = (int)$this->settings['batch_size'];
        
        while ($offset < $total) {
            // FIXED: Use string concatenation for LIMIT/OFFSET
            $sql = "SELECT * FROM tblticketreplies ORDER BY id LIMIT $batchSize OFFSET $offset";
            $stmt = $this->whmcs_pdo->query($sql);
            $replies = $stmt->fetchAll(PDO::FETCH_OBJ);
            
            foreach ($replies as $reply) {
                try {
                    $ticketId = $this->getTicketIdByTicketId($reply->tid);
                    if (!$ticketId) {
                        $this->log("Skipping reply for ticket {$reply->tid} - ticket not found");
                        continue;
                    }
                    
                    $replyData = $this->transformTicketReply($reply, $ticketId);
                    
                    if (!$this->settings['dry_run']) {
                        $this->insertTicketReply($replyData);
                    }
                    
                    $migrated++;
                } catch (Exception $e) {
                    $this->log("Error migrating ticket reply ID {$reply->id}: " . $e->getMessage());
                    $errors++;
                }
            }
            
            $offset += $batchSize;
            $this->log("Processed $offset / $total ticket replies...");
        }
        
        $this->stats['ticket_replies'] = ['migrated' => $migrated, 'errors' => $errors];
        $this->log("✓ Ticket replies migration completed: $migrated migrated, $errors errors");
    }
    
    private function transformTicketReply($reply, $ticketId) {
        return [
            'ticket_id' => $ticketId,
            'message' => $reply->message ?? '',
            'sender' => $reply->name ?? $reply->admin ?? 'Unknown',
            'is_admin' => !empty($reply->admin) ? 1 : 0,
            'created_at' => $reply->date ?? date('Y-m-d H:i:s'),
            'updated_at' => $reply->date ?? date('Y-m-d H:i:s')
        ];
    }
    
    private function insertTicketReply($data) {
        $sql = "
            INSERT INTO ticket_replies (
                ticket_id, message, sender, is_admin, created_at, updated_at
            ) VALUES (
                :ticket_id, :message, :sender, :is_admin, :created_at, :updated_at
            )
        ";
        
        $stmt = $this->laravel_pdo->prepare($sql);
        $stmt->execute($data);
    }
    
    private function updateClientStats() {
        if ($this->settings['dry_run']) {
            $this->log("\n--- Skipping Client Stats Update (Dry Run) ---");
            return;
        }
        
        $this->log("\n--- Updating Client Statistics ---");
        
        // Update total spent from paid invoices
        $sql = "
            UPDATE clients c 
            SET total_spent = (
                SELECT COALESCE(SUM(amount), 0) 
                FROM invoices i 
                WHERE i.client_id = c.id AND i.status = 'Paid'
            )
        ";
        $this->laravel_pdo->exec($sql);
        
        // Update active services count
        $sql = "
            UPDATE clients c 
            SET active_services = (
                SELECT COUNT(*) 
                FROM services s 
                WHERE s.client_id = c.id AND s.status = 'Active'
            )
        ";
        $this->laravel_pdo->exec($sql);
        
        $this->log("✓ Client statistics updated successfully");
    }
    
    // Helper methods
    private function clientExists($clientId) {
        $stmt = $this->laravel_pdo->prepare("SELECT COUNT(*) FROM clients WHERE id = ?");
        $stmt->execute([$clientId]);
        return $stmt->fetchColumn() > 0;
    }
    
    private function getTicketIdByTicketId($ticketId) {
        $stmt = $this->laravel_pdo->prepare("SELECT id FROM tickets WHERE ticket_id = ?");
        $stmt->execute([$ticketId]);
        return $stmt->fetchColumn();
    }
    
    private function log($message) {
        if ($this->settings['verbose']) {
            echo $message . "\n";
        }
    }
    
    private function displayStats() {
        $this->log("\n=== Migration Statistics ===");
        foreach ($this->stats as $table => $stats) {
            $this->log(sprintf(
                "%s: %d migrated, %d errors",
                ucfirst($table),
                $stats['migrated'],
                $stats['errors']
            ));
        }
        $this->log("\n");
    }
}

// =============================================================================
// RUN MIGRATION
// =============================================================================

try {
    $migration = new WhmcsMigration($whmcs_config, $laravel_config, $settings);
    $migration->run();
} catch (Exception $e) {
    echo "Migration failed: " . $e->getMessage() . "\n";
    exit(1);
}

echo "\n🎉 Migration completed successfully!\n";
?>