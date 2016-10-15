<?php

require('database.php');
require('dolphin.php');
session_start();

$db = new Dolphin($dbinfo);

if (!isset($_POST['target']) || !is_string($_POST['target'])) {
    $db->connect();
    if ($_POST['target'] == 'signin') {

    } elseif ($_POST['target'] == 'signup') {

    }
}

?>
