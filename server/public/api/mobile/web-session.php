<?php
require __DIR__.'/../../../includes/bootstrap.php';
require __DIR__.'/../../../includes/mobile.php';
if($_SERVER['REQUEST_METHOD']!=='POST'){header('Allow: POST');http_response_code(405);exit('Alleen POST toegestaan.');}
$token=scalar($_POST,'token');
if(!preg_match('/^[a-f0-9]{64}$/D',$token)){http_response_code(401);exit('Koppeling ongeldig.');}
$device=mobile_device($token);
session_regenerate_id(true);
$_SESSION=['mobile_overview'=>true,'mobile_device_id'=>(int)$device['id'],'last_activity'=>time()];
redirect('coach.php');
