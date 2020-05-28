<?php


if (!file_exists($hmac_key_file)) { touch($hmac_key_file); }
$file_contents = file_get_contents($hmac_key_file);

if (strlen($file_contents) == 0) {
  $file_contents = base64_encode(openssl_random_pseudo_bytes(256));
  file_put_contents($hmac_key_file, $file_contents);
}

$keys = explode("\n", $file_contents);

// Yes, this is not the safest method as no key has a predetermined TTL
if (filemtime($hmac_key_file) < time() - 60 * 60 * 1) {
  array_unshift($keys, base64_encode(openssl_random_pseudo_bytes(256)));
  $keys = array_slice($keys, 0, 4);
  file_put_contents($hmac_key_file, implode("\n", $keys));
}

$current_key = $keys[0];

function create_token($data) {
  global $current_key;

  $res = new stdClass;
  $res->payload = $data;

  $res->hash = hash_hmac("sha512", json_encode($data), $current_key);
  return json_encode($res);
}

function validate_token($str, &$output) {
  global $keys;

  $decoded = json_decode($str);

  if (!$decoded) return 2;
  if (!$decoded->hash) return 3;
  if (!$decoded->payload) return 4;
 
  for ($i = 0; $i < count($keys); $i++) {
    if($decoded->hash == hash_hmac("sha512", json_encode($decoded->payload), $keys[$i])) {
      $output = $decoded->payload;
      return 0;
    }
  }

  return 1;
}
