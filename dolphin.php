<?php

/*
  dolphin v1.2
  Copyright: (c) 2018 Anuv Gupta
  File: dolphin.php (dolphin master)
  Source: [https://github.com/anuvgupta/dolphin]
  License: MIT [https://github.com/anuvgupta/dolphin/blob/master/LICENSE.md]
*/

namespace Dolphin;

/**
  * Dolphin main class
  *
  * This class encapsulates the connection to a MySQL database and provides methods for interacting more easily with the database.
  *
  */
class Dolphin {

    // attributes
    /** @var mysqli MySQL database connection */
    private $database;
    /** @var array Database info/credentials */
    private $dbinfo;
    /** @var array All errors logged */
    private $errors;

    /**
     * Constructor for a Dolphin
     *
     * Checks if the MySQL extension is enabled and saves the provided database information.<br/>
     * If the extension is not loaded, this error will be logged and can be checked with [Dolphin::error](#method_error).
     *
     * @param array $dbinfo An associative array containing the MySQL database credentials and information
     * ```php
     * $dbinfo_example = [
     *     'host' => '127.0.0.1',  // the host on which the database server is running
     *     'user' => 'username',   // username for a user that has access to the chosen database
     *     'pass' => 'password',   // password for a user that has access to the chosen database
     *     'name' => 'database'    // name of the chosen database
     * ];
     * ```
     *
     * @return void
     */
    public function __construct($dbinfo) {
        if (!extension_loaded('mysqli'))
            return $this->fail("MySQLi extension is not loaded");
        $this->dbinfo = $dbinfo;
        $this->errors = [];
    }

    /**
     * Destructor for a Dolphin
     *
     * Calls [Dolphin::disconnect](#method_disconnect).
     *
     * @return void
     */
    public function __destruct() {
        $this->disconnect();
    }


    // PUBLIC METHODS

    /**
     * Connect to MySQL
     *
     * Uses saved credentials/info to connect to MySQL database.<br/>
     * **Must** be called before any other methods (besides [Dolphin::error](#method_error)).
     *
     * @return bool Indicates the success of connecting to MySQL. If `false`, use [Dolphin::error](#method_error) to get the exact error.
     */
    public function connect() {
        $this->database = new \mysqli(
            $this->dbinfo['host'],
            $this->dbinfo['user'],
            $this->dbinfo['pass'],
            $this->dbinfo['name']
        );
        if ($this->database->connect_errno > 0)
            return $this->fail("Could not connect to database: [{$this->database->connect_error}]");
    }

    /**
     * Disconnect from MySQL
     *
     * Disconnects from MySQL database, if connected. Fails silently.
     *
     * @return void
     */
    public function disconnect() {
        if (isset($this->database))
            @$this->database->close();
    }

    /**
     * Modify or create a child within a table
     *
     * This method sets column values for a child row (specified by ID) within a table. <br/>
     * If the table does not exist, it is created. If the child row does not exist within the table, it is created. <br/>
     * If any one column does not exist within the table, it is added to the table (the type can be specified). <br/>
     *
     * <br/>**Uses of set**<br/><br/>
     *
     * <b>Use 1:</b> _Setting child data in a table with columns_ <br/>
     * Each key in this associative array represents a table column, and each value is the value to be set in that column for the given child's row.
     * ```php
     * $data_example_1 = [
     *      'username'   =>  'joesmith123',
     *      'firstname'  =>  'Joe',
     *      'lastname'   =>  'Smith',
     *      'desc'       =>  'Hi! I like long walks on the beach.',
     *      'age'        =>  26
     * ];
     * $dolphin->set('users', 'd6h45', $data_example_1);
     * ```
     * If the columns exist, the child's columns are updated accordingly. <br/>
     * <br/>
     * However, in some cases, the columns specified may not exist. If a column does not exist, the table is first `ALTER`ed, adding the column with type `varchar(255)`.<br/>
     * You can specify the type of a column in case it does not already exist and you want it to have a different type than `varchar(255)`.
     * <br/><br/>
     *
     * <b>Use 2:</b> _Setting child data in a table while creating new columns with different data types_ <br/>
     * Each key in this associative array represents a table column, and each value is another associative array.<br/>
     * &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;The value mapped to `val` is the value to be set in that column for the given child's row.<br/>
     * &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;The value mapped to `type` is the MySQL type of the column, and if the column does not exist in the table, it will be created with this type.<br/>
     * ```php
     * $data_example_2 = [
     *      'username'  => [
     *          'val'   => 'joesmith123',
     *          'type'  => 'varchar(100)' // perhaps I want usernames to be restricted to a shorter length
     *      ],
     *      'firstname' => 'Joe', // no specified type, because the default type is `varchar(255)`, or I know this column already exists
     *      'lastname'  => [
     *          'val'   => 'Smith',
     *          'type'  => 'varchar(255)' // this is unnecessary, because the default type is `varchar(255)`
     *      ],
     *      'desc'      => [
     *          'val'   => 'Hi! I like long walks on the beach.',
     *          'type'  => 'text'
     *      ],
     *      'age'       => [
     *          'val'   => 26,
     *          'type'  => 'integer'
     *      ],
     * ];
     * $dolphin->set('users', 'd6h45', $data_example_2);
     * ```
     * <br/>
     * <b>Use 3:</b> _Creating an empty child row_ <br/>
     * `Example 3: ` in some cases, the data parameter is `null`, or simply not passed (in which case it defaults to `null`).
     * In this case, if the child row does not exist, it is created. If it does exist, nothing happens. In future versions of Dolphin, this may change so that if the child row does exist, all of its attributes (columns) are cleared.
     * ```php
     * $dolphin->set('users', 'd6h45', null);
     * $dolphin->set('users', 'd6h45'); // the same thing
     * ```
     *
     * @param string  $table Name of the table to be modified. If the table does not exist, it will be `CREATE`d.
     * @param string  $child ID of the child row of values to be set/modified. If the child does not exist, it will be `INSERT`ed rather than `UPDATE`d.
     * @param array   $data Technically optional. An associative array containing attributes of the child to set/modify, and their corresponding values (as well as their corresponding types, if necessary). See the above method description for details on how to format.
     *
     * @return bool Indicates success of the operation. If any one database operation fails, the method immediately returns `false` and logs the error; in this case, use [Dolphin::error](#method_error) to get the exact error.
     */
    public function set($table, $child, $data = null) {
        // sanitize input/error handle
        $db = $this->database;
        if (!is_string($table))
            return $this->fail("Function set requires first parameter (table name) to be of type string");
        $table = $db->real_escape_string($table);
        if (!is_string($child))
            return $this->fail("Function set requires second parameter (child ID) to be of type string");
        $child = $db->real_escape_string($child);
        if (!is_array($data))
            $this->warn("Function set prefers third parameter (data) to be of type array");
        $nullData = ($data == null || !is_array($data) || count($data) <= 0);

        // create table with id column if it does not exist
        if (($sql = $db->prepare("CREATE TABLE IF NOT EXISTS `$table` (id varchar(255))")) === false)
            return $this->fail("Could not prepare statement [$db->error]");
        if ($sql->execute() === false)
            return $this->fail("Could not run query [$sql->error]");

        // check if each column exists
        $updates = '';
        $typeUpdates = '';
        if (!$nullData) {
            $newColumns = '';
            foreach ($data as $attribute => $value) {
                $attribute = $db->real_escape_string($attribute);
                $type = 'varchar(255)';
                if (is_array($value)) {
                    if (!isset($value['type']))
                        return $this->fail('Invalid value type format');
                    else $type = $value['type'];
                    if (!isset($value['val']))
                        return $this->fail('Invalid value val format');
                    // else $value = $db->real_escape_string($value['val']);
                    $data[$attribute] = $value['val'];
                }
                // if (is_string($value))
                //     $data[$attribute] = $db->real_escape_string($value);
                // else return $this->fail('Invalid value format');
                if (!$sql = $db->prepare("SHOW COLUMNS FROM `$table` LIKE '$attribute'"))
                    return $this->fail("Could not prepare statement [$db->error]");
                if (!$sql->execute())
                    return $this->fail("Could not run query [$sql->error]");
                $result = $sql->get_result();
                $num_rows = $result->num_rows;
                $result->free();
                // prepare to add column if not exists
                if ($num_rows <= 0) $newColumns .= "ADD COLUMN `$attribute` $type, ";
                // prepare update attributes to use later
                $updates .= ",$attribute=?";
                if (is_int($value)) $typeUpdates .= 'i';
                elseif (is_double($value) || is_float($value)) $typeUpdates .= 'd';
                else $typeUpdates .= 's';
            }
            // add missing columns to table
            if (strlen($newColumns) > 1) {
                if (!$sql = $db->prepare("ALTER TABLE `$table` " . substr($newColumns, 0, strlen($newColumns) - 2)))
                    return $this->fail("Could not prepare statement [$db->error]");
                if (!$sql->execute())
                    return $this->fail("Could not run query [$sql->error]");
            }
        }

        // check if entry exists
        if (($sql = $db->prepare("SELECT * FROM `$table` WHERE id=?")) === false)
            return $this->fail("Could not prepare statement [$db->error]");
        $sql->bind_param('s', $child);
        if ($sql->execute() === false)
            return $this->fail("Could not run query [$sql->error]");
        $result = $sql->get_result();
        $num_rows = $result->num_rows;
        $result->free();
        // if entry does not exist, create new entry
        if ($num_rows <= 0) {
            if (($sql = $db->prepare("INSERT INTO `$table` (id) VALUES (?)")) === false)
                return $this->fail("Could not prepare statement [$db->error]");
            $sql->bind_param('s', $child);
            if ($sql->execute() === false)
                return $this->fail("Could not run query [$sql->error]");
        }

        // add values to table if given
        if (!$nullData && strlen($updates) > 0) {
            $updates = substr($updates, 1);
            $types = $typeUpdates . 's';
            $bind_params = array_merge([$types], array_values($data), [$child]);
            for ($i = 0; $i < count($bind_params); $i++) {
                $bind_params[$i] = &$bind_params[$i];
            }
            if (($sql = $db->prepare("UPDATE `$table` SET $updates WHERE id=?")) === false)
                return $this->fail("Could not prepare statement [$db->error]");
            @call_user_func_array([$sql, 'bind_param'], $bind_params);
            if ($sql->execute() === false)
                return $this->fail("Could not run query [$sql->error]");
        }

        return true;
    }

    /**
     * Add a new child with a unique ID to a table
     *
     * This method creates a new child row with a unique ID within a table, setting column values for it as well.<br/>
     * The ID of the child created is random, alphanumeric, and unique within the table; in other words, the new child's ID will be different from the IDs of every other child in the table.<br/>
     * This method uses [Dolphin::set](#method_set), so if the table does not exist, it is created; and if any one column does not exist within the table, it is added to the table (the type can be specified).<br/>
     *
     * @param string  $table Name of the table to be modified. If the table does not exist, it will be `CREATE`d.
     * @param array   $data Technically optional. An associative array containing attributes of the new child, and their corresponding values (as well as their corresponding types, if necessary).<br/>
     *                      See the description of [Dolphin::set](#method_set) for a detailed explanation on how to format the `$data` array.
     * @param integer $length Optional. Specifies the length of the generated ID. Defaults to `10` characters.
     *
     * @return mixed The generated alphanumeric **string** ID of the newly created child; also indicates success of the operation.<br/>
     *               If any one database operation fails, the method immediately returns **bool** `false` (instead of returning the ID) and logs the error; in this case, use [Dolphin::error](#method_error) to get the exact error.
     */
    public function push($table, $data = null, $length = 10) {
        $db = $this->database;
        $id = '';
        $query = "SHOW TABLES LIKE '$table'";
        if (!$sql = $db->prepare($query))
            return $this->fail("Could not prepare statement [$db->error]");
        if (!$sql->execute())
            return $this->fail("Could not run query [$sql->error]");
        $result = $sql->get_result();
        $num_rows = $result->num_rows;
        $result->free();
        if ($num_rows > 0) {
            while (true) {
                $id = $this->id($length);
                $query = "SELECT id FROM `$table` WHERE id=?";
                if (!$sql = $db->prepare($query))
                    return $this->fail("Could not prepare statement [$db->error]");
                $sql->bind_param('s', $id);
                if (!$sql->execute())
                    return $this->fail("Could not run query [$sql->error]");
                $result = $sql->get_result();
                $num_rows = $result->num_rows;
                if ($num_rows <= 0) break;
            }
        } else $id = $this->id($length);
        if ($this->set($table, $id, $data) === true)
            return $id;
        else return false;
    }

    /**
     * Get data from a table based on child or query
     *
     * This method retrieves data from a table. It can retrieve all the data in the table, or the data for a specific child row, or the data of all child rows matching a query.<br/>
     *
     * <br/>**Uses of get**<br/><br/>
     *
     * <b>Use 1:</b> _Get table data_ <br/>
     * Get all the data from a table. There are three options here - get an array the child IDs, get all the child data, or get specific columns from every child.<br/><br/>
     * <u>Option 1</u>: Array of child IDs<br/>
     * Simply pass the table name.
     * ```php
     * $child_ids = $dolphin->get('users');
     * print_r($child_ids);
     * ```
     * Output
     * ```php
     * Array (
     *     [0] => h6a3d
     *     [1] => d8f8g
     *     [2] => Eh8wPe58eN
     *     [3] => efdJspQhY4
     *     [4] => 1aauZTnZ5t
     * )
     * ```
     * <br/>
     * <u>Option 2</u>: Array of all child data<br/>
     * Pass the table name, as well as `true` for the `$child` parameter.
     * ```php
     * $children = $dolphin->get('users', true);
     * print_r($children);
     * ```
     * Output
     * ```php
     * Array (
     *     [h6a3d] => Array (
     *             [id]         =>  h6a3d
     *             [username]   =>  joesmith123
     *             [firstname]  =>  Joe
     *             [lastname]   =>  Smith
     *             [desc]       =>  Hi! I like long walks on the beach.
     *             [age]        =>  26
     *         )
     *     [d8f8g] => Array (
     *             [id]         =>  d8f8g
     *             [username]   =>  johndoe456
     *             [firstname]  =>  John
     *             [lastname]   =>  Doe
     *             [desc]       =>  My hobbies include pizza.
     *             [age]        =>  22
     *         )
     *     [Eh8wPe58eN] => Array (
     *             [id]         =>  Eh8wPe58eN
     *             [username]   =>  rythorne234
     *             [firstname]  =>  Ryan
     *             [lastname]   =>  Thorne
     *             [desc]       =>  Hi, I'm Ryan, let's hang!
     *             [age]        =>  15
     *         )
     *     [efdJspQhY4] => Array (
     *             [id]         =>  efdJspQhY4
     *             [username]   =>  shelbyg789
     *             [firstname]  =>  Shelby
     *             [lastname]   =>  Griffin
     *             [desc]       =>  Always smiling
     *             [age]        =>  18
     *         )
     *     [1aauZTnZ5t] => Array (
     *             [id]         =>  1aauZTnZ5t
     *             [username]   =>  jdub44
     *             [firstname]  =>  John
     *             [lastname]   =>  Washington
     *             [desc]       =>  Chillin' out maxin' relaxin' all cool
     *             [age]        =>  18
     *         )
     * )
     * ```
     * <br/>
     * <u>Option 3</u>: Array of specific columns of every child<br/>
     * Pass the table name, `true` for the `$child` parameter, and an array of column names for `$data`.
     * ```php
     * $children_names = $dolphin->get('users', true, ['firstname', 'lastname']);
     * print_r($children_names);
     * ```
     * Output
     * ```php
     * Array (
     *     [h6a3d] => Array (
     *             [id]         =>  h6a3d
     *             [firstname]  =>  Joe
     *             [lastname]   =>  Smith
     *         )
     *     [d8f8g] => Array (
     *             [id]         =>  d8f8g
     *             [firstname]  =>  John
     *             [lastname]   =>  Doe
     *         )
     *     [Eh8wPe58eN] => Array (
     *             [id]         =>  Eh8wPe58eN
     *             [firstname]  =>  Ryan
     *             [lastname]   =>  Thorne
     *         )
     *     [efdJspQhY4] => Array (
     *             [id]         =>  efdJspQhY4
     *             [firstname]  =>  Shelby
     *             [lastname]   =>  Griffin
     *         )
     *     [1aauZTnZ5t] => Array (
     *             [id]         =>  1aauZTnZ5t
     *             [firstname]  =>  John
     *             [lastname]   =>  Washington
     *         )
     * )
     * ```
     * <br/>
     *
     * <b>Use 2:</b> _Get child data_ <br/>
     * Get a specific child's data from the table. There are two options here - get get all of the child's data, or get specific columns from the child.<br/><br/>
     * <u>Option 1</u>: All of the child's data<br/>
     * Pass the table name, as well as the child ID for the `$child` parameter
     * ```php
     * $child_data = $dolphin->get('users', 'h6a3d');
     * print_r($child_data);
     * ```
     * Output
     * ```php
     * Array
     * (
     *     [id]         =>  h6a3d
     *     [username]   =>  joesmith123
     *     [firstname]  =>  Joe
     *     [lastname]   =>  Smith
     *     [desc]       =>  Hi! I like long walks on the beach.
     *     [age]        =>  26
     * )
     * ```
     * <br/>
     * <u>Option 2</u>: Specific columns of the child's data<br/>
     * Pass the table name, the child ID for `$child`, and an array of column names for `$data`.
     * ```php
     * $child_name = $dolphin->get('users', 'h6a3d', ['firstname', 'lastname']);
     * print_r($child_name);
     * ```
     * Output
     * ```php
     * Array
     * (
     *     [id]         =>  h6a3d
     *     [firstname]  =>  Joe
     *     [lastname]   =>  Smith
     * )
     * ```
     * <br/>
     *
     * <b>Use 3:</b> _Run query_ <br/>
     * The query building capabilities are mediocre and inadequate at best, so it's best to use actual MySQL database functions to run your own queries.<br/>
     * See the code itself if you want to understand how it works.
     * <br/>
     *
     * @param string             $table Name of the table from which to retrieve data.
     * @param string|bool|array  $child Technically optional. **String** ID of the child row of values to be retrieved, or **bool** `true` to get all child data within a table. Or, an array for structuring queries.
     * @param array              $data Technically optional. Array containing names of columns to retrieve.
     *
     * @return mixed The result set, as an associative **array**. See each use case above for details. If the result set is empty, the method returns **null**.<br/>
     *               If any one database operation fails, the method immediately returns **bool** `false` (instead of an array or null) and logs the error; in this case, use [Dolphin::error](#method_error) to get the exact error.
     */
    public function get($table, $child = null, $data = null) {
        // sanitize input/error handle
        $db = $this->database;
        if (!is_string($table))
            return $this->fail("Function get requires first parameter (table name) to be of type string");
        $table = $db->real_escape_string($table);
        if (!is_bool($child)) {
            if (!is_string($child))
                $this->warn("Function get prefers second parameter (child ID) to be of type string or bool");
            else $child = $db->real_escape_string($child);
        }

        if (is_string($data))
            $data = [$data];
        if (!is_array($data))
            $this->warn("Function get prefers third parameter (data) to be of type array");
        $nullChild = ($child == null || is_bool($child) || (is_string($child) && strlen($child) <= 0));
        $nullData = ($data == null || !is_array($data) || count($data) <= 0);
        // check if table exists
        if (!$sql = $db->prepare("SHOW TABLES LIKE '$table'"))
            return $this->fail("Could not prepare statement [$db->error]");
        if (!$sql->execute())
            return $this->fail("Could not run query [$sql->error]");
        $result = $sql->get_result();
        $num_rows = $result->num_rows;
        $result->free();
        // if table does not exist
        if ($num_rows <= 0)
            return $this->fail("Table '$table' does not exist in database");

        // data to return at the end
        $response = [];
        // if child not provided, get table data
        if ($nullChild) {
            // if child true, get all table data (including child data)
            if ($child === true) {
                if ($nullData) $query = "SELECT * FROM `$table`";
                else $query = "SELECT id, " . implode(',', $data) . " FROM `$table`";
            }
            // if child null or false or other, just get IDs of table data
            else $query = "SELECT id FROM `$table`";
            if (!$sql = $db->prepare($query))
                return $this->fail("Could not prepare statement [$db->error]");
            if (!$sql->execute())
                return $this->fail("Could not run query [$sql->error]");
            $result = $sql->get_result();
            $num_rows = $result->num_rows;
            if ($num_rows > 0) {
                if ($child === true) {
                    while (($row = $result->fetch_assoc()) !== null)
                        // array_push($response, $row);
                        $response[$row['id']] = $row;
                } else {
                    while (($row = $result->fetch_assoc()) !== null)
                        array_push($response, $row['id']);
                }
            }
            $result->free();
        }
        // if child provided, get data of specific child in table
        elseif (is_string($child)) {
            // if data not provided, get all child data
            if ($nullData) $query = "SELECT * FROM `$table` WHERE id=?";
            // if data provided, get specified data
            else $query = "SELECT id, " . implode(',', $data) . " FROM `$table` WHERE id=?";
            if (!$sql = $db->prepare($query))
                return $this->fail("Could not prepare statement [$db->error]");
            $sql->bind_param('s', $child);
            if (!$sql->execute())
                return $this->fail("Could not run query [$sql->error]");
            $result = $sql->get_result();
            $num_rows = $result->num_rows;
            if ($num_rows == 1) {
                $result_assoc = $result->fetch_assoc();
                if (count($data) == 1) $response = $result_assoc[$data[0]];
                else $response = $result_assoc;
            } else {
                if (!$nullData && count($data) == 1) {
                    while (($row = $result->fetch_assoc()) !== null)
                        array_push($response, $row[$data[0]]);
                } else {
                    while (($row = $result->fetch_assoc()) !== null)
                        array_push($response, $row);
                }
            }
            $result->free();
        }
        // if child is array, get children with specified data
        elseif (is_array($child)) {
            // loop through desired data
            $where = '';
            $types = '';
            $whereKeyword = 'WHERE';
            $expectedValues = [];
            $lastNextOpLength = 0;
            foreach ($child as $attribute => $value) {
                if ($attribute === 'where') {
                    $whereKeyword = $value;
                    continue;
                }
                if (!is_string($attribute))
                    $attribute = $value['attribute'];
                if (is_array($value)) {
                    // create condition
                    if (isset($value['condition']) && is_string($value['condition'])) {
                        $where .= $attribute . (strlen($value['condition']) >= 1 && substr($value['condition'], 0, 1) == '=' ? '' : ' ') . $value['condition'];
                        $expected = @$value['expected'];
                        if (isset($expected)) {
                            if (!is_array($expected))
                                $expected = [ $expected ];
                            for ($j = 0; $j < count($expected); $j++) {
                                if (is_array($expected[$j])) {
                                    array_push($expectedValues, $expected[$j]['val']);
                                    $types .= $expected[$j]['type'];
                                } else {
                                    array_push($expectedValues, $expected[$j]);
                                    $types .= 's';
                                }
                            }
                        }
                    } else {
                        $where .= $attribute;
                        $expected = @$value['expected'];
                        if (isset($expected)) {
                            $q = '?';
                            if (@$value['prepare'] === false)
                                $q = (is_array($expected)) ? $expected['val'] : $expected;
                            if (is_string($value['whereOperator']))
                                $where .= $value['whereOperator'] . $q;
                            else $where .= "=$q";
                            if ($q == '?') {
                                if (is_array($expected)) {
                                    array_push($expectedValues, $expected['val']);
                                    $types .= $expected['type'];
                                } else {
                                    array_push($expectedValues, $expected[$j]);
                                    $types .= 's';
                                }
                            }
                        }
                    }
                    // create next (joining) operator
                    if (!is_string(@$value['nextOperator']))
                        $value['nextOperator'] = '';
                    $where .= ' ' . $value['nextOperator'] . ' ';
                    $lastNextOpLength = 1;
                } else {
                    $where .= "$attribute=? AND ";
                    $lastNextOpLength = 5;
                    array_push($expectedValues, $value);
                    $types .= 's';
                }
            }
            $where = substr($where, 0, strlen($where) - $lastNextOpLength);
            // if data not provided, get all child data
            if ($nullData) $query = "SELECT * FROM `$table` $whereKeyword $where";
            // if data provided, get specified data
            else $query = "SELECT " . implode(',', $data) . " FROM `$table` $whereKeyword $where";

            $bind_params = array_merge([$types], $expectedValues);

            for ($i = 0; $i < count($bind_params); $i++)
                $bind_params[$i] = &$bind_params[$i];
            if (($sql = $db->prepare($query)) === false)
                return $this->fail("Could not prepare statement [$db->error]");
            call_user_func_array([$sql, 'bind_param'], $bind_params);
            if ($sql->execute() === false)
                return $this->fail("Could not run query [$sql->error]");
            $result = $sql->get_result();
            $num_rows = $result->num_rows;
            if ($num_rows == 1) {
                if (!$nullData && count($data) == 1) $response = $result->fetch_assoc()[$data[0]];
                else $response = $result->fetch_assoc();
            } else {
                if (!$nullData && count($data) == 1) {
                    while (($row = $result->fetch_assoc()) !== null)
                        array_push($response, $row[$data[0]]);
                } else {
                    while (($row = $result->fetch_assoc()) !== null)
                        array_push($response, $row);
                }
            }
            $result->free();
        }

        // final type checking
        if (is_array($response) && count($response) == 0)
            return null;
        return $response;
    }

    /**
     * Get logged errors
     *
     * Returns most recent error, or specified error, or number of errors, depending on the parameter provided.
     *
     * <br/>**Uses of error**<br/><br/>
     *
     * <b>Use 1:</b> _Get most recent error_ <br/>
     * Call the method with no arguments to get the most recent error as a string.
     * The method will return `false` if there are no logged errors.
     * ```php
     * $last_error = $dolphin->error();
     * if ($last_error) echo "The last error: $last_error";
     * else echo "No errors";
     * ```
     * <br/>
     *
     * <b>Use 2:</b> _Get a specific error_ <br/>
     * Pass the integer ID of the desired error (the most recently error logged has an ID of `0`, and each previous error has an incremented ID) to get that error as a string.
     * Returns `false` if the error is not found.
     * ```php
     * $error_6 = $dolphin->error(6);
     * if ($error_6) echo "The sixth error: $error_6";
     * else echo "Error 6 does not exist";
     * ```
     * <br/>
     *
     * <b>Use 3:</b> _Get total number of errors_ <br/>
     * Pass `true` to get the total number of errors. Useful for calculating ID of specific errors.
     * ```php
     * $num_errors = $dolphin->error(true);
     * echo "Dolphin has logged $num_errors error(s)" . PHP_EOL;
     * if ($num_errors > 1) echo "The second-to-last error: " . $dolphin->error($num_errors - 2);
     * else echo "Dolphin has only logged one error";
     * ```
     *
     * @param integer|bool $num Technically optional. Indicates **integer** ID of error to retrieve (leave blank for most recent error), or **bool** `true` to retrieve total number of errors logged.
     *
     * @return mixed Total **integer** number of errors, or error **string** matching the given ID (or of the most recent error), or **bool** `false` if error requested is not found.
     */
    public function error($num = 0) {
        $numErrors = count($this->errors);
        if ($num === true) return $numErrors;
        elseif ($num < 0 || $num >= $numErrors)
            return false;
        return $this->errors[$numErrors - 1 - $num];
    }

    // PRIVATE (CONVENIENCE) METHODS

    /**
     * Generate random ID (convenience)
     *
     * Generates a pseudo-random key/ID of a specified length.
     *
     * @param integer $length Optional. The length of the key to be generated. Defaults to `10` characters.
     *
     * @return string The generated key/ID as a `string`
     */
    private function id($length = 10) {
        $key = '';
        $chars = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
        for ($i = 0; $i < $length; $i++)
            $key .= $chars[rand(0, strlen($chars) - 1)];
        return $key;
    }

    /**
     * Log error (convenience)
     *
     * Logs an error to the error array
     *
     * @return bool Always `false` for convenience
     */
    private function fail($message) {
        $e = new \Exception();
        array_push($this->errors, "[DOLPHIN] Error - $message - (" . $e->getTraceAsString() . ")");
        return false;
    }

    /**
     * Log warning (convenience)
     *
     * Logs a warning to the error array
     *
     * @return bool Always `false` for convenience
     */
    private function warn($message) {
        $e = new \Exception();
        array_push($this->errors, "[DOLPHIN] Warning - $message - (" . $e->getTraceAsString() . ")");
        return false;
    }
}

?>
