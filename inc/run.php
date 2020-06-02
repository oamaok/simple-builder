<?php

if (file_exists($config->log_dir) && !is_dir($config->log_dir)) die($config->log_dir . ' exists, but is not a directory.');
if (!file_exists($config->log_dir)) mkdir($config->log_dir);

$job_id = intval($_GET['job']);

if (!array_key_exists($job_id, $config->jobs)) die('Invalid job ID.');

$job = $config->jobs[$job_id];

$log_file =  $job->log_prefix . '_' . date("Ymd_His") . '.' . substr(md5(microtime()), 0, 4) . '.txt';
$log_file_path = $config->log_dir . '/' . $log_file;

if (!touch($log_file_path)) die('Failed to create log file ' . $log_file_path);

$pid = exec('nohup ' . $job->command . ' > ' . $log_file_path . ' 2>&1 & echo $!');

header('Content-type: application/json');

$res = new stdClass;
$res->pid = $pid;
$res->logfile = $log_file;

$token = create_token($res);

file_put_contents($token_file, $token);

echo $token;

?>