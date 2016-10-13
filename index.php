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
            if (!$db->set('users', 'anuv', [
                'bob' => 'hello',
                'joe' => 'hi',
                'mum' => 'ay',
                'yo' => 'YO',
                'go' => 'go',
                'ho' => 'hi'
            ])) echo $db->error(0);

            if (!$db->set('joe', 'bob')) echo $db->error(0);

            if (!$db->set('users', 'bob')) echo $db->error(0);

        ?>
    </body>
</html>
