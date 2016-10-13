<?php

require('database.php');
require('manager.php');

$db = new Manager($dbinfo);
$db->connect();

?>

<!DOCTYPE html>
<html>
    <head>
        <title>mysql-manager</title>
    </head>
    <body>
        <?php
            if (!$db->set('users', 'anuv', 'username', 'hello'))
                echo $db->error(0);
        ?>
    </body>
</html>
