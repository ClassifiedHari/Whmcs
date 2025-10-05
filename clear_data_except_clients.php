<?php
/**
 * Clear All Data EXCEPT Clients
 * Run this before CSV import
 */

$config = [
    'host' => 'jgcb.your-database.de',
    'port' => 3306,
    'database' => 'claredigm_db1',
    'username' => 'classigm_1',
    'password' => 'e1§Ky§MuhxoV'
];

try {
    $dsn = "mysql:host={$config['host']};port={$config['port']};dbname={$config['database']};charset=utf8mb4";
    $pdo = new PDO($dsn, $config['username'], $config['password']);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    echo "Connected to database\n\n";
    
    // Get counts before deletion
    echo "=== CURRENT DATA ===" . PHP_EOL;
    $stmt = $pdo->query("SELECT COUNT(*) FROM clients");
    echo "Clients: " . $stmt->fetchColumn() . " (will be KEPT)\n";
    
    $stmt = $pdo->query("SELECT COUNT(*) FROM services");
    echo "Services: " . $stmt->fetchColumn() . " (will be DELETED)\n";
    
    $stmt = $pdo->query("SELECT COUNT(*) FROM domains");
    echo "Domains: " . $stmt->fetchColumn() . " (will be DELETED)\n";
    
    $stmt = $pdo->query("SELECT COUNT(*) FROM invoices");
    echo "Invoices: " . $stmt->fetchColumn() . " (will be DELETED)\n";
    
    $stmt = $pdo->query("SELECT COUNT(*) FROM tickets");
    echo "Tickets: " . $stmt->fetchColumn() . " (will be DELETED)\n";
    
    $stmt = $pdo->query("SELECT COUNT(*) FROM ticket_replies");
    echo "Ticket Replies: " . $stmt->fetchColumn() . " (will be DELETED)\n";
    
    echo "\n";
    echo "Press ENTER to continue or Ctrl+C to cancel...\n";
    readline();
    
    echo "\n=== CLEARING DATA ===" . PHP_EOL;
    
    // Disable foreign key checks temporarily
    $pdo->exec("SET FOREIGN_KEY_CHECKS=0");
    
    // Delete in correct order (child tables first)
    $pdo->exec("DELETE FROM ticket_replies");
    echo "✓ Cleared ticket_replies\n";
    
    $pdo->exec("DELETE FROM tickets");
    echo "✓ Cleared tickets\n";
    
    $pdo->exec("DELETE FROM invoices");
    echo "✓ Cleared invoices\n";
    
    $pdo->exec("DELETE FROM domains");
    echo "✓ Cleared domains\n";
    
    $pdo->exec("DELETE FROM services");
    echo "✓ Cleared services\n";
    
    // Re-enable foreign key checks
    $pdo->exec("SET FOREIGN_KEY_CHECKS=1");
    
    // Reset client statistics
    $pdo->exec("UPDATE clients SET active_services = 0, total_spent = 0");
    echo "✓ Reset client statistics\n";
    
    echo "\n=== FINAL COUNTS ===" . PHP_EOL;
    $stmt = $pdo->query("SELECT COUNT(*) FROM clients");
    echo "Clients: " . $stmt->fetchColumn() . " (KEPT)\n";
    
    $stmt = $pdo->query("SELECT COUNT(*) FROM services");
    echo "Services: " . $stmt->fetchColumn() . "\n";
    
    $stmt = $pdo->query("SELECT COUNT(*) FROM domains");
    echo "Domains: " . $stmt->fetchColumn() . "\n";
    
    $stmt = $pdo->query("SELECT COUNT(*) FROM invoices");
    echo "Invoices: " . $stmt->fetchColumn() . "\n";
    
    echo "\n✓ Data cleared successfully! Ready for CSV import.\n";
    
} catch (PDOException $e) {
    die("Error: " . $e->getMessage() . "\n");
}
