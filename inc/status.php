<?php

if (validate_token($_GET['token'], $payload)) die('Invalid token');

exec('ps -p ' . $payload->pid, $output);

$res = new stdClass;
$res->running = isset($output[1]);

header('Content-type: application/json');
echo json_encode($res);

?>