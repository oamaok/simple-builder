<?php

if (validate_token($_GET['token'], $payload)) die('Invalid token');

exec('kill ' . $payload->pid);
