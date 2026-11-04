<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Artisan::command('db:create', function () {
    $database = config('database.connections.mysql.database', 'offlinebridge');
    $host = config('database.connections.mysql.host', '127.0.0.1');
    $port = config('database.connections.mysql.port', '3306');
    $username = config('database.connections.mysql.username', 'root');
    $password = config('database.connections.mysql.password', '');

    try {
        $pdo = new PDO("mysql:host={$host};port={$port}", $username, $password);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $pdo->exec("CREATE DATABASE IF NOT EXISTS `{$database}` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;");
        $this->info("MySQL database '{$database}' created or already exists on {$host}:{$port}!");
    } catch (\PDOException $e) {
        $this->error("Failed to connect to MySQL: " . $e->getMessage());
        $this->warn("Make sure MySQL is running in XAMPP (Port {$port}) before executing this command.");
        return 1;
    }
    return 0;
})->purpose('Create the MySQL database if it does not exist');
