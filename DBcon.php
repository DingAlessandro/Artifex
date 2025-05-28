<?php
require_once('functions.php');
class DBcon
{
    private static PDO $db;
    public static function getDB(array $config){
        //rende piu robusta
        IF(!isset(self::$db)) { //non fa new multiple svolge la new solo una volta
            try {
                self::$db = new PDO($config['dsn'], $config['username'], $config['password'], $config['options']);
            }catch (PDOException $e){
                logError($e);
            }
        }
        return self::$db;
    }
}