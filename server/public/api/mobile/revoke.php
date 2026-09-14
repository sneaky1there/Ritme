<?php
require __DIR__.'/../../../includes/mobile_bootstrap.php';
mobile_body();$token=mobile_token();
mobile_transaction(function()use($token):void {
    query('UPDATE mobile_devices SET active=0 WHERE token_hash=?',[hash('sha256',$token)]);
});
mobile_json(['ok'=>true]);
