<?php
$_SERVER['REQUEST_METHOD'] = 'GET';
$_SERVER['REQUEST_URI'] = '/';
require 'public/index.php';
try {
    Database::execute("ALTER TABLE sales_channels ADD COLUMN channel_type ENUM('WHATSAPP', 'WEBSITE', 'OTHER') DEFAULT 'OTHER'");
    echo 'success';
} catch (Exception $e) {
    if (strpos($e->getMessage(), 'Duplicate column name') !== false) {
        echo 'already exists';
    } else {
        echo 'Error: ' . $e->getMessage();
    }
}
