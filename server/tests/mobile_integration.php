<?php
require __DIR__.'/../includes/bootstrap.php';require __DIR__.'/../includes/mobile.php';
if(!str_ends_with((string)config('DB_NAME'),'_test')){fwrite(STDERR,"Use a disposable database ending in _test.\n");exit(1);}
set_exception_handler(function(Throwable $e):void{fwrite(STDERR,$e->getMessage().PHP_EOL);exit(1);});
db()->exec(file_get_contents(__DIR__.'/../sql/install.sql'));
function ck(bool $ok,string $name):void{if(!$ok)throw new RuntimeException('FAIL '.$name);echo "PASS $name\n";}
$token=bin2hex(random_bytes(32));query('INSERT INTO mobile_devices(token_hash,name)VALUES(?,?)',[hash('sha256',$token),'Test']);$id=(int)db()->lastInsertId();$date=date('Y-m-d');
try {
 mobile_transaction(function()use($id,$date){upsert('health_connect_daily',['date'=>$date,'device_id'=>$id,'steps'=>4000,'weight'=>82.4,'sleep_minutes'=>420]);});
 $r=query('SELECT * FROM effective_daily_metrics WHERE date=?',[$date])->fetch();ck((int)$r['steps']===4000&&$r['steps_source']==='Health Connect','import available in dashboard');
 upsert('daily_metrics',['date'=>$date,'steps'=>123,'weight'=>80.0,'sleep_minutes'=>480]);
 $r=query('SELECT * FROM effective_daily_metrics WHERE date=?',[$date])->fetch();ck((int)$r['steps']===123&&(float)$r['weight']===80.0,'manual values win');
 ck((int)$r['sleep_minutes']===480&&(int)$r['hc_sleep_minutes']===420,'sleep remains separate from time in bed');
 mobile_transaction(function()use($id,$date){upsert('health_connect_daily',['date'=>$date,'device_id'=>$id,'steps'=>4500]);});
 $r=query('SELECT * FROM health_connect_daily WHERE date=?',[$date])->fetch();ck((float)$r['weight']===82.4&&(int)$r['steps']===4500,'partial permissions preserve other fields');
 ck((int)query('SELECT COUNT(*) FROM health_connect_daily WHERE date=?',[$date])->fetchColumn()===1,'repeat import does not duplicate');
 try{mobile_transaction(function()use($date){query('UPDATE health_connect_daily SET steps=999 WHERE date=?',[$date]);throw new RuntimeException('fixture rollback');});}catch(RuntimeException $e){}
 ck((int)query('SELECT steps FROM health_connect_daily WHERE date=?',[$date])->fetchColumn()===4500,'failed transaction rolls back');
 mobile_transaction(function()use($date){query('UPDATE health_connect_daily SET weight=NULL WHERE date=?',[$date]);});
 ck(query('SELECT weight FROM health_connect_daily WHERE date=?',[$date])->fetch()['weight']===null,'upstream deletion clears imported value');
 ck((float)query('SELECT weight FROM daily_metrics WHERE date=?',[$date])->fetchColumn()===80.0,'upstream deletion preserves manual value');
 query('UPDATE mobile_devices SET active=0 WHERE id=?',[$id]);ck((int)query('SELECT active FROM mobile_devices WHERE id=?',[$id])->fetchColumn()===0,'revocation persisted');
} finally {
 query('DELETE FROM health_connect_daily WHERE date=?',[$date]);query('DELETE FROM daily_metrics WHERE date=?',[$date]);query('DELETE FROM mobile_devices WHERE id=?',[$id]);
}
echo "Mobile database integration passed.\n";
