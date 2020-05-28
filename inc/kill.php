<?php

if (validate_token($_GET['token'], $payload)) die('Invalid token');

exec('kill ' . $payload->pid);

$f = fopen($config->log_dir . '/' . $payload->logfile, 'a');
fwrite($f, "\n[BUILD KILLED]");
fclose($f);
