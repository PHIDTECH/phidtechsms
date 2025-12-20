<?php
require_once 'vendor/autoload.php';

echo "Checking vendor SQLite file for user data:\n";
echo "==========================================\n";

$vendorDbPath = 'vendor/league/csv/test_files/users.sqlite';

if (file_exists($vendorDbPath)) {
    echo "Vendor SQLite file found: " . $vendorDbPath . "\n";
    echo "File size: " . filesize($vendorDbPath) . " bytes\n";
    echo "Last modified: " . date('Y-m-d H:i:s', filemtime($vendorDbPath)) . "\n\n";
    
    try {
        $pdo = new PDO('sqlite:' . $vendorDbPath);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        
        // Get all tables
        $stmt = $pdo->query("SELECT name FROM sqlite_master WHERE type='table'");
        $tables = $stmt->fetchAll(PDO::FETCH_COLUMN);
        
        echo "Tables found in vendor database:\n";
        foreach ($tables as $table) {
            echo "- " . $table . "\n";
            
            // Check if it's a users table
            if (strpos(strtolower($table), 'user') !== false) {
                $countStmt = $pdo->query("SELECT COUNT(*) FROM `$table`");
                $count = $countStmt->fetchColumn();
                echo "  Records: " . $count . "\n";
                
                if ($count > 0) {
                    // Show first few records
                    $dataStmt = $pdo->query("SELECT * FROM `$table` LIMIT 3");
                    $records = $dataStmt->fetchAll(PDO::FETCH_ASSOC);
                    echo "  Sample data:\n";
                    foreach ($records as $record) {
                        echo "    " . json_encode($record) . "\n";
                    }
                }
            }
        }
        
        // Check for sender_ids table
        if (in_array('sender_ids', $tables)) {
            echo "\nChecking sender_ids table:\n";
            $stmt = $pdo->query("SELECT COUNT(*) FROM sender_ids");
            $count = $stmt->fetchColumn();
            echo "Records in sender_ids: " . $count . "\n";
            
            if ($count > 0) {
                $stmt = $pdo->query("SELECT * FROM sender_ids WHERE sender_id = 'RodlineHost'");
                $rodlineHost = $stmt->fetch(PDO::FETCH_ASSOC);
                if ($rodlineHost) {
                    echo "Found RodlineHost in vendor database!\n";
                    echo json_encode($rodlineHost, JSON_PRETTY_PRINT) . "\n";
                } else {
                    echo "RodlineHost not found in vendor database.\n";
                }
            }
        }
        
    } catch (Exception $e) {
        echo "Error accessing vendor database: " . $e->getMessage() . "\n";
    }
} else {
    echo "Vendor SQLite file not found.\n";
}

echo "\nChecking current database file info:\n";
echo "====================================\n";
$currentDbPath = 'database/database.sqlite';
if (file_exists($currentDbPath)) {
    echo "Current database file: " . $currentDbPath . "\n";
    echo "File size: " . filesize($currentDbPath) . " bytes\n";
    echo "Last modified: " . date('Y-m-d H:i:s', filemtime($currentDbPath)) . "\n";
} else {
    echo "Current database file not found.\n";
}