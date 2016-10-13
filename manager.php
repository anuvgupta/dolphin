<?php

// if (!isset($eol))
//     $eol = '<br/>';

class Manager {
    // attributes
    protected $database;
    protected $dbinfo;
    protected $errors;
    // constructor
    public function __construct($dbinfo) {
        if (!extension_loaded('mysqli'))
            return $this->fail("MySQLi extension is not loaded");
        $this->dbinfo = $dbinfo;
        $this->errors = [];
    }

    // method for connecting to mysql database
    public function connect() {
        $this->database = new mysqli(
            $this->dbinfo['host'],
            $this->dbinfo['user'],
            $this->dbinfo['pass'],
            $this->dbinfo['name']
        );
        $db = $this->database;
        if ($db->connect_errno > 0)
            return $this->fail("Could not connect to database: [$db->connect_error]");
    }

    // method for setting keys with values
    public function set($table, $child, $data) {
        // sanitize input/error handle
        $db = $this->database;
        if (!is_string($table))
            $this->fail("Function set requires first parameter (table name) to be of type string");
        if (!is_string($child))
            $this->fail("Function set requires second parameter (child ID) to be of type string");
        if (!is_array($data))
            $this->fail("Function set requires third parameter (data) to be of type array");
        $table = $db->real_escape_string($table);
        $child = $db->real_escape_string($child);
        // check if table exists
        if (!$sql = $db->prepare("SHOW TABLES LIKE '$table'"))
            return $this->fail("Could not prepare statement [$db->error]");
        if (!$sql->execute())
            return $this->fail("Could not run query [$sql->error]");
        $result = $sql->get_result();
        $num_rows = $result->num_rows;
        $result->free();
        // if table does not exist
        if ($num_rows <= 0) {
            // create table with new column
            if (($sql = $db->prepare("CREATE TABLE IF NOT EXISTS `$table` (id varchar(255))")) === false)
                return $this->fail("Could not prepare statement [$db->error]");
            if ($sql->execute() === false)
                return $this->fail("Could not run query [$sql->error]");
        }
        // check if table has column
        foreach ($data as $attribute => $value) {
            $attribute = $db->real_escape_string($attribute);
            $value = $db->real_escape_string($value);
            if (!$sql = $db->prepare("SHOW COLUMNS FROM '$table' LIKE '$attribute'"))
                return $this->fail("Could not prepare statement [$db->error]");
            if (!$sql->execute())
                return $this->fail("Could not run query [$sql->error]");
            $result = $sql->get_result();
            $num_rows = $result->num_rows;
            $result->free();
            if ($num_rows <= 0) {

            }
        }

        // if (($sql = $db->prepare("INSERT INTO `main` (node, content) VALUES (?, ?)")) === false)
        //     return $this->fail("Could not prepare statement [$db->error]");
        // $sql->bind_param('ss', $node, $value);
        // if ($sql->execute() === false)
        //     return $this->fail("Could not run query [$sql->error]");
    }

    // method for getting values from keys
    public function get($key) {

    }

    // method for getting logged errors
    public function error($num) {
        $numErrors = count($this->errors);
        if ($num === true) return $numErrors;
        elseif ($num < 0 || $num >= $numErrors)
            return false;
        return $this->errors[$numErrors - 1 - $num];
    }

    private function id($length = 10) {
        $key = '';
        $chars = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
        for ($i = 0; $i < $length; $i++)
            $key .= $chars[rand(0, strlen($chars) - 1)];
        return $key;
    }

    // private convenience method for logging errors
    private function fail($message) {
        array_push($this->errors, "[MANAGER] Error - $message");
        return false;
    }
}

?>
