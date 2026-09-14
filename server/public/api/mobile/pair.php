<?php
require __DIR__.'/../../../includes/mobile_bootstrap.php';
$data=mobile_body();$code=scalar($data,'code');
if(!preg_match('/^[a-f0-9]{64}$/D',$code)) mobile_json(['error'=>'invalid_code'],401);
$result=mobile_transaction(function()use($code,$data):array {
    $pair=query('SELECT id FROM mobile_pairings WHERE code_hash=? AND used_at IS NULL AND expires_at>UTC_TIMESTAMP()',[hash('sha256',$code)])->fetch();
    if(!$pair) return ['error'=>'expired'];
    $name=text_value($data,'name',100)??'Android-telefoon';
    $token=bin2hex(random_bytes(32));
    query('UPDATE mobile_devices SET active=0 WHERE active=1');
    query('INSERT INTO mobile_devices(token_hash,name) VALUES (?,?)',[hash('sha256',$token),$name]);
    query('UPDATE mobile_pairings SET used_at=UTC_TIMESTAMP() WHERE id=?',[$pair['id']]);
    return ['token'=>$token,'timezone'=>config('TIMEZONE'),'protocol'=>1];
});
mobile_json($result,isset($result['error'])?409:200);
