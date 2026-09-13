<?php
declare(strict_types=1);
if(PHP_SAPI!=='cli') {http_response_code(404);exit;}
require __DIR__.'/../includes/bootstrap.php';require __DIR__.'/../includes/hevy.php';require __DIR__.'/../includes/sync.php';
umask(0077);
$log=__DIR__.'/../logs/hevy.log';
if(!is_writable(dirname($log))) {fwrite(STDERR,"Logmap niet schrijfbaar.\n");exit(1);}
try {
 $count=sync_provider(new HevyProvider((string)config('HEVY_API_KEY')));
 $message=gmdate('c').' OK events='.$count;
 file_put_contents($log,$message.PHP_EOL,FILE_APPEND|LOCK_EX);echo $message.PHP_EOL;
} catch(Throwable $e) {
 $message=gmdate('c').' FAILED '.get_class($e);
 file_put_contents($log,$message.PHP_EOL,FILE_APPEND|LOCK_EX);
 fwrite(STDERR,$message." — Controleer API-key, netwerk en database.\n");exit(1);
}
