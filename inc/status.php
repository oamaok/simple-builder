<?php

if (!is_numeric($_GET['pid'])) die('Invalid PID');

exec('ps -p ' . $_GET['pid'], $output);

$res = new stdClass;
$res->running = isset($output[1]);

header('Content-type: application/json');
echo json_encode($res);

?>