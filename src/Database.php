<?php
/**
 * Created by PhpStorm.
 * User: Muhammad Iqmal
 * Date: 10/12/2018
 * Time: 11:35 AM
 */

namespace Dumper;

//error_reporting(E_ERROR | E_PARSE);
/**
 * A basic database interface using MySQLi
 */
class Database
{
    /**
     * @var mixed
     */
    private $sql;
    private $mysql;
    private $result;
    private $result_rows;
    private $database_name;
    private static $instance;

    /**
     * Query history
     *
     * @var array
     */
    static $queries = array();

    /**
     * Database() constructor
     *
     * @param string $database_name
     * @param string $username
     * @param string $password
     * @param string $host
     * @throws DatabaseException
     */
    public function __construct($username = DB_USER, $password = DB_PASSWORD, $host = DB_HOST, $database_name = null)
    {
        self::$instance      = $this;
        $this->database_name = $database_name;
        $this->mysql         = mysqli_connect($host, $username, $password, $database_name);

        if (!$this->mysql) {
            throw new DatabaseException('Database connection error: ' . mysqli_connect_error());
        }
        $this->mysql->set_charset('utf8');

    }

    /**
     * Get instance
     *
     * @param string $database_name
     * @param string $username
     * @param string $password
     * @param string $host
     * @return Database
     */
    public static function instance($database_name = DB_NAME, $username = DB_USER, $password = DB_PASSWORD, $host = DB_HOST)
    {
        if (!isset(self::$instance)) {
            self::$instance = new Database($database_name, $username, $password, $host);
        }

        return self::$instance;
    }

    /**
     * Helper for throwing exceptions
     *
     * @param $error
     * @throws Exception
     */
    private function _error($error)
    {
        throw new DatabaseException('Database error: ' . $error);
    }

    /**
     * Check if a table with a specific name exists
     *
     * @param $name
     * @return bool
     */
    public function table_exists($name)
    {
        $res = mysqli_query($this->mysql, "SELECT COUNT(*) AS count FROM information_schema.tables WHERE table_schema = '" . $this->escape($this->database_name) . "' AND table_name = '" . $this->escape($name) . "'");
        return ($this->mysqli_result($res, 0) == 1);
    }

    /**
     * Show All DB From Connection
     * @return array
     */
    public function showDB()
    {
        $stack = [];
        $result = mysqli_query($this->mysql, 'SHOW DATABASES');

        while($row = mysqli_fetch_row($result)){
            if (($row[0]!="information_schema") && ($row[0]!="mysql") && ($row[0]!="sys") && ($row[0]!="phpmyadmin")) {
                array_push($stack, $row[0]);
            }
        }

        mysqli_close($this->mysql);
        return $stack;
    }

    /**
     * Get All Table Listing
     * @return array
     */
    public function showTables()
    {
        $stack = [];
        $result = mysqli_query($this->mysql, 'SHOW TABLES');

        while($row = mysqli_fetch_row($result)){
            array_push($stack, $row[0]);
        }

        return $stack;
    }

    /**
     * Get Number of Data
     * @param $table
     * @return int
     */
    public function getNumberOfRows($table)
    {
        $result = mysqli_query($this->mysql, 'SELECT * FROM ' . $table);

        return mysqli_num_rows($result);
    }
}

