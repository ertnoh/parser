<?php

/**
 * Created by PhpStorm.
 * User: ADrushka
 * Date: 04.11.2017
 * Time: 20:49
 */
class DB
{
    private static $_instance;
    private $connection;
    private $dbHost = "localhost";
    private $dbName = "parser";
    private $dbUser = "root";
    private $dbPass = "";


    public function __construct()
    {
        $dbHost = $this->dbHost;
        $dbName = $this->dbName;
        $dbUser = $this->dbUser;
        $dbPass = $this->dbPass;
        try {
            $this->connection = new PDO("mysql:host=$dbHost;dbname=$dbName", $dbUser, $dbPass);
        } catch (PDOException $e) {
            echo $e->getMessage();
        }
    }

    /**
     * @return mixed
     */
    public static function getInstance()
    {
        if (self::$_instance === null) {
            self::$_instance = new self();
        }
        return self::$_instance;
    }

    public function save(string $table_name, array $rows)
    {
        $func = function($val) {
            return "'" . $val . "'";
        };
        $conn = $this->connection;
        $field_names = null;
        foreach ($rows as $row) {
            if (!$field_names) {
                $field_names = implode(", ", array_keys($row));
            }
            $quot_vals = array_map($func, $row);
            $dbVal[] = "(".implode(",", $quot_vals).")";
        }
        $val = implode(", ", $dbVal);
        $query = $conn->prepare("INSERT INTO $table_name ($field_names) VALUES $val");
        $aff_rows = $query->execute();
//        var_dump($query);
//        die();
        return $aff_rows;
    }

    /**
     * Простое получение записей из таблиц
     *
     * @param $fields
     * @param $table_name
     * @param $where
     * @return array
     */
    public function getData(string $fields, string $table_name, $where = 1){
        return $this->connection->query("SELECT $fields FROM $table_name WHERE $where")->fetchAll(PDO::FETCH_ASSOC);
    }

    public function truncate(string $table_name){
        $conn = $this->connection;
        $conn->exec("TRUNCATE TABLE $table_name");
    }

    private function __clone()
    {
    }

    private function __wakeup()
    {
    }
}