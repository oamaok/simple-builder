<?php

require_once('../inc/hmac.php');

$config = json_decode(file_get_contents('../config.json'));

if (isset($_GET['build'])) {
  require_once('../inc/build.php');
  exit(0);
}

if (isset($_GET['status'])) {
  require_once('../inc/status.php');
  exit(0);
}

require_once('../inc/index.php');