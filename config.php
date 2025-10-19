<?php

// PDO config for XAMPP (adjust if needed)
class Database
{
    private static ?\PDO $pdo = null;

    public static function getConnection(): \PDO
    {
        if (self::$pdo === null) {
            $host = '127.0.0.1';
            $db = 'car_showroom';
            $user = 'root';
            $pass = '';
            $dsn = "mysql:host=$host;dbname=$db;charset=utf8mb4";
            $options = [
                \PDO::ATTR_ERRMODE => \PDO::ERRMODE_EXCEPTION,
                \PDO::ATTR_DEFAULT_FETCH_MODE => \PDO::FETCH_ASSOC,
                \PDO::ATTR_EMULATE_PREPARES => false,
            ];
            self::$pdo = new \PDO($dsn, $user, $pass, $options);
        }
        return self::$pdo;
    }
}

?>


