<?php
// src/Config/Database.php - fix config path
namespace App\Config;

use PDO;
use PDOException;

class Database {
    private static ?PDO $instance = null;
    
    public static function getInstance(): PDO {
        if (self::$instance === null) {
            $config = require dirname(__DIR__, 2) . '/config.php';
            $db = $config['db'];
            
            $dsn = "mysql:host={$db['host']};dbname={$db['name']};charset={$db['charset']}";
            
            try {
                self::$instance = new PDO($dsn, $db['user'], $db['pass'], [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                    PDO::ATTR_EMULATE_PREPARES => false
                ]);
            } catch (PDOException $e) {
                error_log($e->getMessage());
                die('Database connection failed');
            }
        }
        
        return self::$instance;
    }
}