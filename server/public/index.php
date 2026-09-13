<?php
require __DIR__.'/../includes/bootstrap.php'; if(!empty($_SERVER['PATH_INFO'])) {http_response_code(404);exit('Pagina niet gevonden.');} require_admin(); require __DIR__.'/../includes/layout.php';
$date=valid_date(scalar($_GET,'date',date('Y-m-d')));
$data=query('SELECT * FROM daily_metrics WHERE date=?',[$date])->fetch() ?: [];
page_start('Dagelijkse check-in'); ?>
<div class="heading"><div><p class="eyebrow">DAGELIJKSE CHECK-IN</p><h1>Hoe gaat het vandaag?</h1><p class="muted">Vul in wat je weet. Lege velden zijn ook oké.</p></div><form class="date-picker" method="get"><label>Jouw dag<input type="date" name="date" value="<?=e($date)?>" min="2000-01-01" max="<?=date('Y-m-d')?>" required></label><button class="secondary">Laden</button></form></div>
<p class="muted">Health Connect vult ontbrekende stappen en gewichten in je overzicht aan. Handmatig ingevulde waarden krijgen voorrang. <a href="<?=e(url('devices.php'))?>">Telefoon koppelen</a></p>
<form method="post" action="<?=e(url('api/save_daily.php'))?>" class="card entry"><?php csrf_field(); ?><input type="hidden" name="date" value="<?=e($date)?>"><div class="section-heading"><h2><?=e((new DateTimeImmutable($date))->format('d-m-Y'))?></h2><span class="badge"><?= $data ? 'Eerder ingevuld' : 'Nieuwe check-in' ?></span></div>
<div class="form-grid"><?php foreach(daily_fields() as $key=>$f): ?><label><?=e($f[0])?><span class="unit"><?=e($f[1])?></span><input type="number" inputmode="<?= $f[4]==='1'?'numeric':'decimal' ?>" name="<?=e($key)?>" min="<?=$f[2]?>" max="<?=$f[3]?>" step="<?=$f[4]?>" value="<?=e($data[$key]??'')?>" placeholder="—"></label><?php endforeach; ?></div>
<?php scale('sleep_quality','Slaapkwaliteit',$data['sleep_quality']??null,'1 = slecht · 5 = heerlijk geslapen'); ?>
<div class="form-footer"><span class="muted">Hevy-trainingen verschijnen vanzelf na synchronisatie.</span><button class="primary">Check-in opslaan →</button></div></form>
<?php page_end();
