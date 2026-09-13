<?php
if(PHP_SAPI!=='cli') {http_response_code(404);exit;}
// Read via STDIN so a secret never needs to appear in shell history or process arguments.
$password=rtrim(stream_get_contents(STDIN),"\r\n");
if(strlen($password)<12 || strlen($password)>72) {fwrite(STDERR,"Gebruik 12 t/m 72 bytes.\n");exit(1);}
echo password_hash($password,PASSWORD_DEFAULT).PHP_EOL;
