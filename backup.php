<?php
    // deepcode ignore TooPermissiveCorsHeader: This is a backup script so can be run by anyone. It doesn't send any data.
    header("Access-Control-Allow-Origin: *");
    header("Access-Control-Allow-Methods: GET, OPTIONS");
    header("Access-Control-Allow-Headers: Origin, X-Requested-With, Content-Type, Accept");
    header("Content-Type: application/sql; charset=UTF-8");
    // deepcode ignore TooPermissiveXFrameOptions: False Positive
    header("X-Frame-Options: DENY");
    header("Strict-Transport-Security: max-age=15552000; preload");
    header("Content-Security-Policy: default-src 'self'");
    header("Referrer-Policy: no-referrer");
    header("X-Content-Type-Options: nosniff");
    require_once __DIR__ . '/vendor/autoload.php';
    $dotenv = Dotenv\Dotenv::createImmutable(__DIR__);
    $dotenv->load();
    $dotenv->required(['DATABASE_HOST', 'DATABASE_NAME', 'DATABASE_USER'])->notEmpty();
    //Enter your database information here and the name of the backup file
    $mysqlDatabaseName = $_ENV['DATABASE_NAME'];
    $mysqlUserName = $_ENV['DATABASE_USER'];
    $mysqlPassword = $_ENV['DATABASE_PASS'];
    $mysqlHostName = $_ENV['DATABASE_HOST'];
    $hour = date('H');
    $minute = date('i');
    $mysqlExportPath = "Galexia_Backup-$hour-$minute.sql";

    // Please do not change the following points
    // Export of the database and output of the status
    $command='mysqldump --opt -h' .$mysqlHostName .' -u' .$mysqlUserName .' -p' .$mysqlPassword .' ' .$mysqlDatabaseName .' > ' .$mysqlExportPath;
    $output=array();
    exec($command, $output, $worked);
    switch($worked) {
        case 0:
            $size = filesize($mysqlExportPath);
            header("Content-length: $size");
            http_response_code( 200 );
            print_r(file_get_contents($mysqlExportPath));
            unlink($mysqlExportPath);
            return;
        case 1:
            return http_response_code( 500 );
        case 2:
            return http_response_code( 500 );
    }
?>