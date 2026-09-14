<?php
require __DIR__.'/../includes/bootstrap.php';require __DIR__.'/../includes/mobile.php';
set_exception_handler(function(Throwable $e):void{fwrite(STDERR,$e->getMessage().PHP_EOL);exit(1);});
$count=0;
function verify_mobile(bool $ok,string $name):void{global $count;if(!$ok)throw new RuntimeException('FAIL '.$name);$count++;echo "PASS $name\n";}
function invalid_mobile(array $p,string $name):void{try{validate_mobile_snapshot($p);}catch(InvalidArgumentException $e){verify_mobile(true,$name);return;}verify_mobile(false,$name);}
$today=date('Y-m-d');
$base=['protocol'=>1,'timezone'=>config('TIMEZONE'),'from'=>$today,'to'=>$today,'metrics'=>['steps','weight','sleep_minutes','resting_hr','total_calories_kcal','body_fat_percentage'],'days'=>[['date'=>$today,'steps'=>0,'weight'=>82.45,'sleep_minutes'=>null,'resting_hr'=>58,'total_calories_kcal'=>2450.5,'body_fat_percentage'=>18.2,'origins'=>['steps'=>['com.example.fitness'],'weight'=>[],'sleep_minutes'=>[],'resting_hr'=>['com.example.watch'],'total_calories_kcal'=>['com.example.watch'],'body_fat_percentage'=>['com.example.scale']]]]];
$r=validate_mobile_snapshot($base);verify_mobile($r['days'][0]['steps']===0&&$r['days'][0]['sleep_minutes']===null,'zero and null retained');
verify_mobile($r['days'][0]['weight']===82.45,'decimal weight');
verify_mobile($r['days'][0]['steps_sources']==='["com.example.fitness"]','source preserved');
verify_mobile($r['days'][0]['resting_hr']===58&&$r['days'][0]['total_calories_kcal']===2450.5&&$r['days'][0]['body_fat_percentage']===18.2,'extended health values retained');
$p=$base;$p['days'][0]['steps']='42';invalid_mobile($p,'reject string number');
$p=$base;$p['days'][0]['steps']=1.5;invalid_mobile($p,'reject fractional steps');
$p=$base;$p['days'][0]['steps']=-1;invalid_mobile($p,'reject negative steps');
$p=$base;$p['days'][0]['weight']=1000;invalid_mobile($p,'reject out of range weight');
$p=$base;$p['days'][0]['body_fat_percentage']=101;invalid_mobile($p,'reject out of range body fat');
$p=$base;$p['days'][0]['resting_hr']=58.5;invalid_mobile($p,'reject fractional resting heart rate');
$p=$base;$p['metrics']=['unknown'];invalid_mobile($p,'reject unapproved field');
$p=$base;$p['timezone']='UTC';invalid_mobile($p,'reject timezone mismatch');
$p=$base;$p['days']=[];invalid_mobile($p,'reject partial snapshot');
$p=$base;$p['days'][0]['date']='2000-01-01';invalid_mobile($p,'reject out of order date');
$p=$base;unset($p['days'][0]['steps']);invalid_mobile($p,'missing field cannot clear silently');
$p=$base;$p['days'][0]['origins']['steps']=['<script>'];invalid_mobile($p,'reject malicious origin');
$p=$base;$p['metrics']=['steps'];$r=validate_mobile_snapshot($p);verify_mobile(!array_key_exists('weight',$r['days'][0]),'denied fields excluded from persistence');
$p=$base;$p['days'][0]['sleep_minutes']=1500;verify_mobile(validate_mobile_snapshot($p)['days'][0]['sleep_minutes']===1500,'25 hour DST day allowed');
$p=$base;$p['from']=(new DateTimeImmutable('today'))->modify('-28 days')->format('Y-m-d');invalid_mobile($p,'reject over 28 days');
echo "$count mobile checks passed.\n";
