<?php

$config = json_decode(file_get_contents(dirname(__file__) . '/../config.json'));
$token_file = dirname(__file__) . "/../.token";
$hmac_key_file = dirname(__file__) . "/../.hmac_keys";

require_once(dirname(__file__) . '/../inc/hmac.php');

if (isset($_GET['build'])) {
  require_once(dirname(__file__) . '/../inc/build.php');
  exit(0);
}

if (isset($_GET['status'])) {
  require_once(dirname(__file__) . '/../inc/status.php');
  exit(0);
}

if (isset($_GET['kill'])) {
  require_once(dirname(__file__) . '/../inc/kill.php');
  exit(0);
}

require_once(dirname(__file__) . '/../inc/index.php');