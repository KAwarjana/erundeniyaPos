<?php

class Database{

    public static $connection;

    public static function setUpConnection(){
        if(!isset(Database::$connection)){
            Database::$connection = 
            new mysqli("localhost","root","Kawi@#$123","pharmacy_pos","3306");
        }
    }

    public static function iud($q){
        Database::setUpConnection();
        Database::$connection->query($q);
    }

    public static function search($q){
        Database::setUpConnection();
        $resultset = Database::$connection->query($q);
        return $resultset;
    }

    public static function getConnection() {
        Database::setUpConnection();
        return Database::$connection;
    }

    // Add the missing escape method
    public static function escape($string) {
        Database::setUpConnection();
        return Database::$connection->real_escape_string($string);
    }

}

?>