<?php

require('src/dolphin.php');
use Dolphin\Dolphin as Dolphin;

$dolphin = new Dolphin([
    'host' => '10.0.1.95',
    'user' => 'dolphin',
    'pass' => 'XjN8SKqUWL5jANxC',
    'name' => 'dolphin'
]);
$dolphin->connect();
// print_r($dolphin->get('users', [
//     'age' => 18
// ]));
$dolphin->push('users', [
    'name' => 'john'
]);
// echo $dolphin->error(true) . PHP_EOL;

?>
