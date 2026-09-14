<?php
declare(strict_types=1);
function mobile_json(array $value,int $status=200): void {
    http_response_code($status);header('Content-Type: application/json; charset=utf-8');
    echo json_encode($value,JSON_THROW_ON_ERROR);exit;
}
function mobile_body(): array {
    if($_SERVER['REQUEST_METHOD']!=='POST') {header('Allow: POST');mobile_json(['error'=>'method'],405);}
    if(strtolower(trim(explode(';',$_SERVER['CONTENT_TYPE']??'')[0]))!=='application/json') mobile_json(['error'=>'content_type'],415);
    $raw=file_get_contents('php://input',false,null,0,131073);
    if(strlen($raw)>131072) mobile_json(['error'=>'payload_too_large'],413);
    try{$data=json_decode($raw,true,32,JSON_THROW_ON_ERROR);}catch(JsonException $e){mobile_json(['error'=>'invalid_json'],422);}
    if(!is_array($data)) mobile_json(['error'=>'invalid_json'],422);
    return $data;
}
function mobile_token(): string {
    $header=$_SERVER['HTTP_AUTHORIZATION']??$_SERVER['REDIRECT_HTTP_AUTHORIZATION']??'';
    if(!preg_match('/^Bearer ([a-f0-9]{64})$/D',$header,$matches)) mobile_json(['error'=>'unauthorized'],401);
    return $matches[1];
}
function mobile_device(string $token): array {
    $row=query('SELECT id,last_sync_at FROM mobile_devices WHERE token_hash=? AND active=1',[hash('sha256',$token)])->fetch();
    if(!$row) mobile_json(['error'=>'unauthorized'],401);
    return $row;
}
function mobile_transaction(callable $action) {
    db()->beginTransaction();
    try {
        // Serialize pairing/revocation/deletion/import so an in-flight upload cannot restore deleted data.
        query('SELECT id FROM mobile_guard WHERE id=1 FOR UPDATE');
        $result=$action();db()->commit();return $result;
    } catch(Throwable $e) {if(db()->inTransaction())db()->rollBack();throw $e;}
}
function validate_mobile_snapshot(array $data): array {
    if(($data['protocol']??null)!==1 || ($data['timezone']??null)!==config('TIMEZONE')) throw new InvalidArgumentException('Protocol of tijdzone ongeldig.');
    $from=valid_date(scalar($data,'from'));$to=valid_date(scalar($data,'to'));
    $length=(new DateTimeImmutable($from))->diff(new DateTimeImmutable($to))->days+1;
    if($from>$to || $length>28 || $from<(new DateTimeImmutable('today'))->modify('-30 days')->format('Y-m-d')) throw new InvalidArgumentException('Ongeldige periode.');
    $metrics=$data['metrics']??null;$rows=$data['days']??null;
    if(!is_array($metrics)||!count($metrics)||count($metrics)>6||array_values($metrics)!==$metrics) throw new InvalidArgumentException('Ongeldige meetvelden.');
    foreach($metrics as $metric) if(!is_string($metric)||!in_array($metric,['steps','weight','sleep_minutes','resting_hr','total_calories_kcal','body_fat_percentage'],true)) throw new InvalidArgumentException('Ongeldig meetveld.');
    if(count(array_unique($metrics))!==count($metrics)||!is_array($rows)||count($rows)!==$length||array_values($rows)!==$rows) throw new InvalidArgumentException('Onvolledige periode.');
    $clean=[];
    foreach($rows as $index=>$row) {
        if(!is_array($row)||($row['date']??null)!==(new DateTimeImmutable($from))->modify('+'.$index.' days')->format('Y-m-d')) throw new InvalidArgumentException('Ongeldige volgorde.');
        $item=['date'=>$row['date']];
        foreach($metrics as $metric) {
            if(!array_key_exists($metric,$row)) throw new InvalidArgumentException('Meetveld ontbreekt.');
            $v=$row[$metric];$ranges=['steps'=>[0,200000,true],'weight'=>[20,500,false],'sleep_minutes'=>[0,1500,true],'resting_hr'=>[1,300,true],'total_calories_kcal'=>[0,1000000,false],'body_fat_percentage'=>[0,100,false]];
            [$min,$max,$integer]=$ranges[$metric]; // 1500 minutes allows a 25-hour daylight-saving day.
            if($v!==null && ((!is_int($v)&&!is_float($v))||!is_finite((float)$v)||$v<$min||$v>$max||($integer&&floor((float)$v)!=(float)$v))) throw new InvalidArgumentException('Ongeldige waarde.');
            $item[$metric]=$v;
            $sources=$row['origins'][$metric]??[];
            if(!is_array($sources)||count($sources)>100||array_values($sources)!==$sources) throw new InvalidArgumentException('Ongeldige bronnen.');
            foreach($sources as $source) if(!is_string($source)||strlen($source)>255||!preg_match('/^[A-Za-z0-9_.]+$/D',$source)) throw new InvalidArgumentException('Ongeldige bron.');
            $item[$metric.'_sources']=json_encode(array_values(array_unique($sources)),JSON_THROW_ON_ERROR);
        }
        $clean[]=$item;
    }
    return ['metrics'=>$metrics,'days'=>$clean];
}
