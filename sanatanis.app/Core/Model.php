<?php

namespace Core;

use PDO;


abstract class Model
{
    protected static function getDB()
    {
        $dotenv = \Dotenv\Dotenv::createImmutable(dirname(__DIR__));
        $dotenv->safeLoad();
        static $dbh = NULL;
        if ($dbh === NULL) {
            $host = $_ENV['host'];
            $dbname = $_ENV['dbname'];
            $username = $_ENV['username'];
            $password = $_ENV['password'];
            try {
                $dbh = new PDO("mysql:host=$host;dbname=$dbname", $username, $password);
                $dbh->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION); // <== add this line
            } catch (\PDOException $e) {
                echo $e->getMessage();
            }
        }
        return $dbh;
    }

    public static function execute($sql, $array)
    {
        try {
            $dbh = static::getDB();
            $stmt = $dbh->prepare($sql);
            $stmt->execute($array);
            $count = $stmt->rowCount();
            if ($count > 0) {
                return true;
            } else {
                return false;
            }
        } catch (\PDOException $e) {
            echo $e->getMessage();
        }
    }
    public static function insertgetid($sql, $array)
    {
        try {
            $dbh = static::getDB();
            $stmt = $dbh->prepare($sql);
            $stmt->execute($array);
            $count = $stmt->rowCount();
            if ($count > 0) {
                $id = $dbh->lastInsertId();
                return $id;
            } else {
                return false;
            }
        } catch (\PDOException $e) {
            echo $e->getMessage();
        }
    }
    public static function executeSelect($sql, $array, $function)
    {
        try {
            $dbh = static::getDB();
            $stmt = $dbh->prepare($sql);
            $stmt->execute($array);
            $count = $stmt->rowCount();
            if ($count > 0) {
                $fs = $function[0];
                if (isset($function[1])) {
                    $fss = (int)$function[1];
                    $response = $stmt->$fs($fss);
                } else {
                    $response = $stmt->$fs();
                }
                return $response;
            } else {
                return false;
            }
        } catch (\PDOException $e) {
            echo $e->getMessage();
        }
    }
}
