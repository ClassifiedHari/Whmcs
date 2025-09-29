<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use App\Models\Client;
use App\Models\Invoice;
use App\Models\Service;
use App\Models\Domain;
use App\Models\Ticket;
use App\Models\TicketReply;
use Carbon\Carbon;

class MigrateWhmcsData extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'migrate:whmcs 
                            {--source=whmcs : Source database name}
                            {--batch=1000 : Batch size for processing}
                            {--dry-run : Run without making changes}
                            {--table= : Migrate specific table only}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Migrate data from WHMCS 8.11.2 to Laravel backend';

    private $sourceDb;
    private $batchSize;
    private $dryRun;
    private $stats = [];

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->sourceDb = $this->option('source');
        $this->batchSize = (int) $this->option('batch');
        $this->dryRun = $this->option('dry-run');
        
        $this->info('Starting WHMCS 8.11.2 Data Migration');
        $this->info('Source Database: ' . $this->sourceDb);
        $this->info('Batch Size: ' . $this->batchSize);
        
        if ($this->dryRun) {
            $this->warn('DRY RUN MODE - No data will be modified');
        }

        try {
            // Test source database connection
            $this->testSourceConnection();
            
            $specificTable = $this->option('table');
            
            if ($specificTable) {
                $this->migrateTable($specificTable);
            } else {
                // Migrate all tables in order (respecting foreign keys)
                $this->migrateClients();
                $this->migrateServices();
                $this->migrateDomains();
                $this->migrateInvoices();
                $this->migrateTickets();
                $this->migrateTicketReplies();
                $this->updateClientStats();
            }
            
            $this->displayStats();
            $this->info('Migration completed successfully!');
            
        } catch (\Exception $e) {
            $this->error('Migration failed: ' . $e->getMessage());
            return 1;
        }

        return 0;
    }

    private function testSourceConnection()
    {
        try {
            DB::connection('whmcs')->getPdo();
            $this->info('✓ Source database connection successful');
        } catch (\Exception $e) {
            throw new \Exception('Cannot connect to WHMCS database: ' . $e->getMessage());
        }
    }

    private function migrateTable($table)
    {
        $method = 'migrate' . ucfirst($table);
        if (method_exists($this, $method)) {
            $this->$method();
        } else {
            $this->error("Migration method for table '{$table}' not found");
        }
    }

    private function migrateClients()
    {
        $this->info('Migrating clients...');
        
        $totalRecords = DB::connection('whmcs')
            ->table('tblclients')
            ->count();
            
        $this->info("Total clients to migrate: {$totalRecords}");
        
        $progressBar = $this->output->createProgressBar($totalRecords);
        $migrated = 0;
        $errors = 0;
        
        DB::connection('whmcs')
            ->table('tblclients')
            ->orderBy('id')
            ->chunk($this->batchSize, function ($clients) use (&$migrated, &$errors, $progressBar) {
                foreach ($clients as $whmcsClient) {
                    try {
                        $clientData = $this->transformClient($whmcsClient);
                        
                        if (!$this->dryRun) {
                            Client::updateOrCreate(
                                ['email' => $clientData['email']],
                                $clientData
                            );
                        }
                        
                        $migrated++;
                    } catch (\Exception $e) {
                        $this->error("Error migrating client ID {$whmcsClient->id}: " . $e->getMessage());
                        $errors++;
                    }
                    
                    $progressBar->advance();
                }
            });
            
        $progressBar->finish();
        $this->newLine();
        
        $this->stats['clients'] = ['migrated' => $migrated, 'errors' => $errors];
        $this->info("Clients migration completed: {$migrated} migrated, {$errors} errors");
    }

    private function transformClient($whmcsClient)
    {
        // Combine address fields
        $address = trim(implode(', ', array_filter([
            $whmcsClient->address1,
            $whmcsClient->address2,
            $whmcsClient->city,
            $whmcsClient->state,
            $whmcsClient->postcode,
            $whmcsClient->country
        ])));
        
        // Map status
        $statusMap = [
            'Active' => 'Active',
            'Inactive' => 'Inactive',
            'Closed' => 'Suspended'
        ];
        
        return [
            'first_name' => $whmcsClient->firstname,
            'last_name' => $whmcsClient->lastname,
            'email' => $whmcsClient->email,
            'company' => $whmcsClient->companyname,
            'phone' => $whmcsClient->phonenumber,
            'address' => $address ?: null,
            'status' => $statusMap[$whmcsClient->status] ?? 'Active',
            'registration_date' => Carbon::parse($whmcsClient->datecreated),
            'last_login' => $whmcsClient->lastlogin ? Carbon::parse($whmcsClient->lastlogin) : null,
            'total_spent' => 0, // Will be calculated later
            'active_services' => 0, // Will be calculated later
            'created_at' => Carbon::parse($whmcsClient->datecreated),
            'updated_at' => now(),
        ];
    }

    private function migrateServices()
    {
        $this->info('Migrating services...');
        
        $totalRecords = DB::connection('whmcs')
            ->table('tblhosting')
            ->count();
            
        $this->info("Total services to migrate: {$totalRecords}");
        
        $progressBar = $this->output->createProgressBar($totalRecords);
        $migrated = 0;
        $errors = 0;
        
        DB::connection('whmcs')
            ->table('tblhosting')
            ->leftJoin('tblproducts', 'tblhosting.packageid', '=', 'tblproducts.id')
            ->select('tblhosting.*', 'tblproducts.name as product_name')
            ->orderBy('tblhosting.id')
            ->chunk($this->batchSize, function ($services) use (&$migrated, &$errors, $progressBar) {
                foreach ($services as $whmcsService) {
                    try {
                        // Check if client exists
                        $client = Client::where('id', $whmcsService->userid)->first();
                        if (!$client) {
                            $this->warn("Skipping service {$whmcsService->id} - client not found");
                            continue;
                        }
                        
                        $serviceData = $this->transformService($whmcsService);
                        
                        if (!$this->dryRun) {
                            Service::updateOrCreate(
                                ['id' => $whmcsService->id],
                                $serviceData
                            );
                        }
                        
                        $migrated++;
                    } catch (\Exception $e) {
                        $this->error("Error migrating service ID {$whmcsService->id}: " . $e->getMessage());
                        $errors++;
                    }
                    
                    $progressBar->advance();
                }
            });
            
        $progressBar->finish();
        $this->newLine();
        
        $this->stats['services'] = ['migrated' => $migrated, 'errors' => $errors];
        $this->info("Services migration completed: {$migrated} migrated, {$errors} errors");
    }

    private function transformService($whmcsService)
    {
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
            'Biennially' => 'Annually', // Map to annually as closest match
            'Triennially' => 'Annually'
        ];
        
        return [
            'client_id' => $whmcsService->userid,
            'product_name' => $whmcsService->product_name ?: 'Unknown Product',
            'domain' => $whmcsService->domain,
            'status' => $statusMap[$whmcsService->domainstatus] ?? 'Pending',
            'next_due_date' => Carbon::parse($whmcsService->nextduedate),
            'recurring_amount' => $whmcsService->amount,
            'billing_cycle' => $billingCycleMap[$whmcsService->billingcycle] ?? 'Monthly',
            'registration_date' => Carbon::parse($whmcsService->regdate),
            'created_at' => Carbon::parse($whmcsService->regdate),
            'updated_at' => now(),
        ];
    }

    private function migrateDomains()
    {
        $this->info('Migrating domains...');
        
        $totalRecords = DB::connection('whmcs')
            ->table('tbldomains')
            ->count();
            
        $this->info("Total domains to migrate: {$totalRecords}");
        
        $progressBar = $this->output->createProgressBar($totalRecords);
        $migrated = 0;
        $errors = 0;
        
        DB::connection('whmcs')
            ->table('tbldomains')
            ->orderBy('id')
            ->chunk($this->batchSize, function ($domains) use (&$migrated, &$errors, $progressBar) {
                foreach ($domains as $whmcsDomain) {
                    try {
                        // Check if client exists
                        $client = Client::where('id', $whmcsDomain->userid)->first();
                        if (!$client) {
                            $this->warn("Skipping domain {$whmcsDomain->domain} - client not found");
                            continue;
                        }
                        
                        $domainData = $this->transformDomain($whmcsDomain);
                        
                        if (!$this->dryRun) {
                            Domain::updateOrCreate(
                                ['domain' => $domainData['domain']],
                                $domainData
                            );
                        }
                        
                        $migrated++;
                    } catch (\Exception $e) {
                        $this->error("Error migrating domain {$whmcsDomain->domain}: " . $e->getMessage());
                        $errors++;
                    }
                    
                    $progressBar->advance();
                }
            });
            
        $progressBar->finish();
        $this->newLine();
        
        $this->stats['domains'] = ['migrated' => $migrated, 'errors' => $errors];
        $this->info("Domains migration completed: {$migrated} migrated, {$errors} errors");
    }

    private function transformDomain($whmcsDomain)
    {
        // Map status
        $statusMap = [
            'Active' => 'Active',
            'Expired' => 'Expired',
            'Pending' => 'Pending',
            'Cancelled' => 'Cancelled',
            'Transferred Away' => 'Cancelled'
        ];
        
        return [
            'client_id' => $whmcsDomain->userid,
            'domain' => $whmcsDomain->domain,
            'registrar' => $whmcsDomain->registrar,
            'status' => $statusMap[$whmcsDomain->status] ?? 'Pending',
            'registration_date' => Carbon::parse($whmcsDomain->registrationdate),
            'expiry_date' => Carbon::parse($whmcsDomain->expirydate),
            'auto_renew' => (bool) $whmcsDomain->recurringamount,
            'nameservers' => ['ns1.hosting.com', 'ns2.hosting.com'], // Default nameservers
            'created_at' => Carbon::parse($whmcsDomain->registrationdate),
            'updated_at' => now(),
        ];
    }

    private function migrateInvoices()
    {
        $this->info('Migrating invoices...');
        
        $totalRecords = DB::connection('whmcs')
            ->table('tblinvoices')
            ->count();
            
        $this->info("Total invoices to migrate: {$totalRecords}");
        
        $progressBar = $this->output->createProgressBar($totalRecords);
        $migrated = 0;
        $errors = 0;
        
        DB::connection('whmcs')
            ->table('tblinvoices')
            ->orderBy('id')
            ->chunk($this->batchSize, function ($invoices) use (&$migrated, &$errors, $progressBar) {
                foreach ($invoices as $whmcsInvoice) {
                    try {
                        // Check if client exists
                        $client = Client::where('id', $whmcsInvoice->userid)->first();
                        if (!$client) {
                            $this->warn("Skipping invoice {$whmcsInvoice->invoicenum} - client not found");
                            continue;
                        }
                        
                        $invoiceData = $this->transformInvoice($whmcsInvoice);
                        
                        if (!$this->dryRun) {
                            Invoice::updateOrCreate(
                                ['invoice_id' => $invoiceData['invoice_id']],
                                $invoiceData
                            );
                        }
                        
                        $migrated++;
                    } catch (\Exception $e) {
                        $this->error("Error migrating invoice {$whmcsInvoice->invoicenum}: " . $e->getMessage());
                        $errors++;
                    }
                    
                    $progressBar->advance();
                }
            });
            
        $progressBar->finish();
        $this->newLine();
        
        $this->stats['invoices'] = ['migrated' => $migrated, 'errors' => $errors];
        $this->info("Invoices migration completed: {$migrated} migrated, {$errors} errors");
    }

    private function transformInvoice($whmcsInvoice)
    {
        // Map status
        $statusMap = [
            'Paid' => 'Paid',
            'Unpaid' => 'Pending',
            'Overdue' => 'Overdue',
            'Cancelled' => 'Cancelled',
            'Collections' => 'Overdue'
        ];
        
        $invoiceId = $whmcsInvoice->invoicenum ?: ('INV-' . date('Y') . '-' . str_pad($whmcsInvoice->id, 6, '0', STR_PAD_LEFT));
        
        return [
            'invoice_id' => $invoiceId,
            'client_id' => $whmcsInvoice->userid,
            'amount' => $whmcsInvoice->total,
            'status' => $statusMap[$whmcsInvoice->status] ?? 'Pending',
            'due_date' => Carbon::parse($whmcsInvoice->duedate),
            'issue_date' => Carbon::parse($whmcsInvoice->date),
            'description' => $whmcsInvoice->notes ?: 'Migrated from WHMCS',
            'payment_method' => $whmcsInvoice->paymentmethod,
            'items' => [], // Could be populated from tblinvoiceitems if needed
            'created_at' => Carbon::parse($whmcsInvoice->date),
            'updated_at' => now(),
        ];
    }

    private function migrateTickets()
    {
        $this->info('Migrating tickets...');
        
        $totalRecords = DB::connection('whmcs')
            ->table('tbltickets')
            ->count();
            
        $this->info("Total tickets to migrate: {$totalRecords}");
        
        $progressBar = $this->output->createProgressBar($totalRecords);
        $migrated = 0;
        $errors = 0;
        
        DB::connection('whmcs')
            ->table('tbltickets')
            ->leftJoin('tbladmins', 'tbltickets.admin', '=', 'tbladmins.id')
            ->select('tbltickets.*', 'tbladmins.username as admin_name')
            ->orderBy('tbltickets.id')
            ->chunk($this->batchSize, function ($tickets) use (&$migrated, &$errors, $progressBar) {
                foreach ($tickets as $whmcsTicket) {
                    try {
                        // Check if client exists
                        if ($whmcsTicket->userid) {
                            $client = Client::where('id', $whmcsTicket->userid)->first();
                            if (!$client) {
                                $this->warn("Skipping ticket {$whmcsTicket->tid} - client not found");
                                continue;
                            }
                        }
                        
                        $ticketData = $this->transformTicket($whmcsTicket);
                        
                        if (!$this->dryRun) {
                            Ticket::updateOrCreate(
                                ['ticket_id' => $ticketData['ticket_id']],
                                $ticketData
                            );
                        }
                        
                        $migrated++;
                    } catch (\Exception $e) {
                        $this->error("Error migrating ticket {$whmcsTicket->tid}: " . $e->getMessage());
                        $errors++;
                    }
                    
                    $progressBar->advance();
                }
            });
            
        $progressBar->finish();
        $this->newLine();
        
        $this->stats['tickets'] = ['migrated' => $migrated, 'errors' => $errors];
        $this->info("Tickets migration completed: {$migrated} migrated, {$errors} errors");
    }

    private function transformTicket($whmcsTicket)
    {
        // Map status
        $statusMap = [
            'Open' => 'Open',
            'Answered' => 'In Progress',
            'Customer-Reply' => 'Open',
            'In Progress' => 'In Progress',
            'On Hold' => 'In Progress',
            'Closed' => 'Closed'
        ];
        
        // Map priority
        $priorityMap = [
            'Low' => 'Low',
            'Medium' => 'Medium', 
            'High' => 'High'
        ];
        
        return [
            'ticket_id' => $whmcsTicket->tid ?: ('TKT-' . strtoupper(substr(md5($whmcsTicket->id), 0, 6))),
            'client_id' => $whmcsTicket->userid,
            'subject' => $whmcsTicket->subject,
            'status' => $statusMap[$whmcsTicket->status] ?? 'Open',
            'priority' => $priorityMap[$whmcsTicket->urgency] ?? 'Medium',
            'department' => $whmcsTicket->deptname ?: 'General',
            'category' => null, // WHMCS doesn't have this field by default
            'assigned_to' => $whmcsTicket->admin_name,
            'created_at' => Carbon::parse($whmcsTicket->date),
            'updated_at' => $whmcsTicket->lastreply ? Carbon::parse($whmcsTicket->lastreply) : Carbon::parse($whmcsTicket->date),
        ];
    }

    private function migrateTicketReplies()
    {
        $this->info('Migrating ticket replies...');
        
        $totalRecords = DB::connection('whmcs')
            ->table('tblticketreplies')
            ->count();
            
        if ($totalRecords == 0) {
            $this->info('No ticket replies to migrate');
            return;
        }
            
        $this->info("Total ticket replies to migrate: {$totalRecords}");
        
        $progressBar = $this->output->createProgressBar($totalRecords);
        $migrated = 0;
        $errors = 0;
        
        DB::connection('whmcs')
            ->table('tblticketreplies')
            ->orderBy('id')
            ->chunk($this->batchSize, function ($replies) use (&$migrated, &$errors, $progressBar) {
                foreach ($replies as $whmcsReply) {
                    try {
                        // Find corresponding ticket
                        $ticket = Ticket::where('ticket_id', $whmcsReply->tid)->first();
                        if (!$ticket) {
                            $this->warn("Skipping reply for ticket {$whmcsReply->tid} - ticket not found");
                            continue;
                        }
                        
                        $replyData = $this->transformTicketReply($whmcsReply, $ticket->id);
                        
                        if (!$this->dryRun) {
                            TicketReply::create($replyData);
                        }
                        
                        $migrated++;
                    } catch (\Exception $e) {
                        $this->error("Error migrating ticket reply ID {$whmcsReply->id}: " . $e->getMessage());
                        $errors++;
                    }
                    
                    $progressBar->advance();
                }
            });
            
        $progressBar->finish();
        $this->newLine();
        
        $this->stats['ticket_replies'] = ['migrated' => $migrated, 'errors' => $errors];
        $this->info("Ticket replies migration completed: {$migrated} migrated, {$errors} errors");
    }

    private function transformTicketReply($whmcsReply, $ticketId)
    {
        return [
            'ticket_id' => $ticketId,
            'message' => $whmcsReply->message,
            'sender' => $whmcsReply->name ?: $whmcsReply->admin,
            'is_admin' => !empty($whmcsReply->admin),
            'created_at' => Carbon::parse($whmcsReply->date),
            'updated_at' => Carbon::parse($whmcsReply->date),
        ];
    }

    private function updateClientStats()
    {
        if ($this->dryRun) {
            $this->info('Skipping client stats update (dry run mode)');
            return;
        }
        
        $this->info('Updating client statistics...');
        
        $clients = Client::all();
        $progressBar = $this->output->createProgressBar($clients->count());
        
        foreach ($clients as $client) {
            $client->updateStats();
            $progressBar->advance();
        }
        
        $progressBar->finish();
        $this->newLine();
        
        $this->info('Client statistics updated successfully');
    }

    private function displayStats()
    {
        $this->newLine();
        $this->info('=== Migration Statistics ===');
        
        foreach ($this->stats as $table => $stats) {
            $this->info(sprintf(
                '%s: %d migrated, %d errors',
                ucfirst($table),
                $stats['migrated'],
                $stats['errors']
            ));
        }
        
        $this->newLine();
    }
}
