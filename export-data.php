<?php
// export-data.php - Export SQLite data to SQL for MySQL
require_once 'config.php';

// Set headers for download
header('Content-Type: text/plain');
header('Content-Disposition: attachment; filename="export_data.sql"');

$output = "-- Family Debt Manager Data Export\n";
$output .= "-- Generated: " . date('Y-m-d H:i:s') . "\n\n";

// Get all tables from SQLite
$tables = ['users', 'debts', 'payments', 'debt_history'];

foreach ($tables as $table) {
    try {
        $stmt = $pdo->query("SELECT * FROM $table");
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        if (count($rows) > 0) {
            $output .= "-- Table: $table\n";
            $columns = array_keys($rows[0]);
            $cols = implode('`, `', $columns);
            
            foreach ($rows as $row) {
                $values = [];
                foreach ($row as $key => $value) {
                    if ($value === null) {
                        $values[] = 'NULL';
                    } else {
                        $values[] = "'" . addslashes($value) . "'";
                    }
                }
                $vals = implode(', ', $values);
                $output .= "INSERT INTO `$table` (`$cols`) VALUES ($vals);\n";
            }
            $output .= "\n";
        }
    } catch (PDOException $e) {
        // Skip if table doesn't exist
        continue;
    }
}

echo $output;
?>
