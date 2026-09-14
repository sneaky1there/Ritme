<?php
require __DIR__.'/../includes/bootstrap.php'; require __DIR__.'/../includes/layout.php';
if(admin()) redirect('index.php');
$error='';
if($_SERVER['REQUEST_METHOD']==='POST') {
 require_post();
 if(try_login(scalar($_POST,'username'),(is_string($_POST['password']??null)?$_POST['password']:''))) redirect('index.php');
 $error='Inloggen niet gelukt. Controleer je gegevens of probeer het over 15 minuten opnieuw.';
}
page_start('Welkom terug'); ?>
<section class="login card"><p class="eyebrow">JOUW DAGELIJKSE MOMENT</p><h1>Welkom terug.</h1><p class="muted">Even inchecken. Dan weer door.</p>
<?php if($error): ?><p class="error" role="alert"><?=e($error)?></p><?php endif; ?>
<form method="post"><?php csrf_field(); ?><label>Gebruikersnaam<input name="username" autocomplete="username" required maxlength="100"></label><label>Wachtwoord<input name="password" type="password" autocomplete="current-password" required maxlength="1024"></label><button class="primary">Inloggen →</button></form></section>
<?php page_end();
