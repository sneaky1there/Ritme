<?php
require __DIR__.'/../includes/bootstrap.php'; require_post();
$_SESSION=[]; session_destroy();
setcookie(session_name(),'', ['expires'=>time()-3600,'path'=>url(''),'secure'=>!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS']!=='off','httponly'=>true,'samesite'=>'Lax']);
redirect('login.php');
