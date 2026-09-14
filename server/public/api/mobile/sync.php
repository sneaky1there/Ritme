<?php
require __DIR__.'/../../../includes/mobile_bootstrap.php';
$token=mobile_token();mobile_device($token);$data=mobile_body();
if(($data['timezone']??null)!==config('TIMEZONE')) mobile_json(['error'=>'timezone_changed'],409);
$snapshot=validate_mobile_snapshot($data);
$result=mobile_transaction(function()use($token,$snapshot):array {
    $device=mobile_device($token);
    // Rate limit inside the same lock as the mutation, not a racy read before writing.
    if($device['last_sync_at'] && time()-strtotime($device['last_sync_at'].' UTC')<30) return ['error'=>'rate_limit'];
    $now=gmdate('Y-m-d H:i:s');
    foreach($snapshot['days'] as $day) {
        $row=['device_id'=>$device['id']]+$day;
        foreach($snapshot['metrics'] as $metric)$row[$metric.'_synced_at']=$now;
        // Only permitted fields are changed; null explicitly clears a missing/deleted upstream value.
        upsert('health_connect_daily',$row);
    }
    query('UPDATE mobile_devices SET last_sync_at=? WHERE id=?',[$now,$device['id']]);
    return ['ok'=>true,'days'=>count($snapshot['days']),'received_at'=>$now];
});
mobile_json($result,isset($result['error'])?429:200);
