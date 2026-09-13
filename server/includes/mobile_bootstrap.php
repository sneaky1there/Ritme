<?php
// Mobile endpoints authenticate by a scoped Bearer credential, never by admin/coach cookies.
define('MOBILE_API',true);
require __DIR__.'/bootstrap.php';require __DIR__.'/mobile.php';
set_exception_handler(function(Throwable $e):void {
    error_log('Fitness mobile error: '.get_class($e));
    mobile_json(['error'=>$e instanceof InvalidArgumentException?'invalid_payload':'unavailable'],$e instanceof InvalidArgumentException?422:500);
});
