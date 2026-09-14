<?php
require __DIR__.'/../includes/bootstrap.php'; require_admin(); require __DIR__.'/../includes/layout.php';
if($_SERVER['REQUEST_METHOD']==='POST') {
 require_post();
 if(scalar($_POST,'action')==='create') {
  $description=text_value($_POST,'description',100);
  if(!$description) throw new InvalidArgumentException('Geef de coachlink een naam.');
  $overview=($_POST['can_view_overview']??null)==='1';$training=($_POST['can_view_training']??null)==='1';$nutrition=($_POST['can_view_nutrition']??null)==='1';
  if(!$overview&&!$training&&!$nutrition) throw new InvalidArgumentException('Kies minimaal één overzicht.');
  $token=bin2hex(random_bytes(32));
  query('INSERT INTO coach_tokens (token_hash,description,can_view_overview,can_view_training,can_view_nutrition) VALUES (?,?,?,?,?)',[hash('sha256',$token),$description,(int)$overview,(int)$training,(int)$nutrition]);
  $_SESSION['new_link']=rtrim(config('APP_URL'),'/').'/coach.php?token='.$token;
 } elseif(scalar($_POST,'action')==='revoke') {
  query('UPDATE coach_tokens SET active=0 WHERE id=?',[scalar($_POST,'id')]); flash('Coachlink ingetrokken. Ook bestaande toegang is geblokkeerd.');
 } else throw new InvalidArgumentException('Ongeldige actie.');
 redirect('tokens.php');
}
$links=query('SELECT id,description,active,created_at,last_used_at,can_view_overview,can_view_training,can_view_nutrition FROM coach_tokens ORDER BY id DESC')->fetchAll();
page_start('Delen'); ?>
<p class="eyebrow">SAMEN INZICHT</p><h1>Deel met je coach.</h1><p class="muted">Kies per ontvanger welk overzicht beschikbaar is. Een link kan later altijd worden ingetrokken.</p>
<?php if(isset($_SESSION['new_link'])): ?><section class="notice"><label>Kopieer deze link nu. Hij wordt maar één keer getoond.<input readonly value="<?=e($_SESSION['new_link'])?>"></label></section><?php unset($_SESSION['new_link']); endif; ?>
<form method="post" class="card compact"><?php csrf_field(); ?><input type="hidden" name="action" value="create"><label>Voor wie?<input name="description" placeholder="Bijv. coach 1" maxlength="100" required></label><fieldset class="permissions"><legend>Welke overzichten?</legend><label><input type="checkbox" name="can_view_overview" value="1" checked> Weekoverzicht en ruwe metingen</label><label><input type="checkbox" name="can_view_training" value="1"> Training, oefeningen en sets</label><label><input type="checkbox" name="can_view_nutrition" value="1"> Voeding, calorieën en macro’s</label></fieldset><button class="primary">Coachlink maken</button></form>
<div class="card table-wrap"><table><thead><tr><th>Ontvanger</th><th>Status</th><th>Laatst geopend</th><th>Overzichten</th><th>Toegang</th></tr></thead><tbody><?php foreach($links as $link): ?><tr><td><?=e($link['description'])?></td><td><?=$link['active']?'Actief':'Ingetrokken'?></td><td><?=e($link['last_used_at']??'Nog niet geopend')?></td><td><?php $rights=[];if($link['can_view_overview'])$rights[]='Overzicht';if($link['can_view_training'])$rights[]='Training';if($link['can_view_nutrition'])$rights[]='Voeding';?><?=e(implode(' + ',$rights)?:'Geen')?></td><td><?php if($link['active']): ?><form method="post"><?php csrf_field(); ?><input type="hidden" name="action" value="revoke"><input type="hidden" name="id" value="<?=$link['id']?>"><button class="secondary">Intrekken</button></form><?php endif; ?></td></tr><?php endforeach; ?><?php if(!$links): ?><tr><td colspan="5">Je hebt nog geen coachlinks gemaakt.</td></tr><?php endif; ?></tbody></table></div>
<?php page_end();
