<?php
require __DIR__.'/../includes/bootstrap.php'; require_admin(); require __DIR__.'/../includes/layout.php';
$week=monday(valid_date(scalar($_GET,'date',date('Y-m-d'))));
$data=query('SELECT * FROM weekly_checkins WHERE week_start=?',[$week])->fetch() ?: [];
page_start('Weekcheck'); ?>
<div class="heading"><div><p class="eyebrow">EVEN TERUGKIJKEN</p><h1>Jouw week in woorden.</h1><p class="muted">Week van <?=e((new DateTimeImmutable($week))->format('d-m-Y'))?> · maandag t/m zondag</p></div><form class="date-picker" method="get"><label>Kies een dag in de week<input type="date" name="date" value="<?=e($week)?>" min="2000-01-01" max="<?=date('Y-m-d')?>" required></label><button class="secondary">Laden</button></form></div>
<form class="card entry" method="post" action="<?=e(url('api/save_weekly.php'))?>"><?php csrf_field(); ?><input type="hidden" name="week_start" value="<?=e($week)?>">
<div class="form-grid"><?php foreach(['libido'=>'Libido','cravings'=>'Cravings','stress'=>'Stressniveau'] as $key=>$label) scale($key,$label,$data[$key]??null,$key==='cravings'?'1 = veel zoete trek · 5 = geen zoete trek':'1 = laag · 5 = hoog'); ?></div>
<?php foreach(['libido_details'=>'Libido: veranderingen / aantal ochtenderecties deze week','stress_details'=>'Stress: word je uitgerust wakker en heb je zin in de dag?','bowel_movement'=>'Ontlasting: dagelijkse frequentie, opgeblazen buik of gasvorming'] as $key=>$label): ?><label><?=e($label)?><textarea name="<?=e($key)?>" rows="3" maxlength="5000"><?=e($data[$key]??'')?></textarea></label><?php endforeach; ?>
<div class="form-footer"><span class="muted">Je kunt deze weekcheck later aanpassen.</span><button class="primary">Weekcheck opslaan →</button></div></form>
<?php page_end();
