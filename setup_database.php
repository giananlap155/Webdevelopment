<?php
require_once 'config/database.php';

// Read the SQL file
$sql = file_get_contents('setup_database.sql');

// Execute the SQL
if (mysqli_multi_query($conn, $sql)) {
    echo "Database setup completed successfully!";
} else {
    echo "Error setting up database: " . mysqli_error($conn);
}

mysqli_close($conn);
?> 