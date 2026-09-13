<?php
require __DIR__.'/../includes/bootstrap.php'; require_coach_or_admin('overview'); require __DIR__.'/../includes/layout.php'; require __DIR__.'/../includes/statistics.php';
$today=date('Y-m-d'); $week=monday($today);$previous=(new DateTimeImmutable($week))->modify('-7 days')->format('Y-m-d');
$from=(string)config('DASHBOARD_FROM_DATE');
$fromDate=DateTimeImmutable::createFromFormat('!Y-m-d',$from);
if(!$fromDate||$fromDate->format('Y-m-d')!==$from||$from>$today) throw new RuntimeException('Ongeldige DASHBOARD_FROM_DATE.');
$lookback=$fromDate->modify('-6 days')->format('Y-m-d');
$days=query('SELECT * FROM effective_daily_metrics WHERE date BETWEEN ? AND ? ORDER BY date',[$lookback,$today])->fetchAll();
$workouts=query('SELECT id,date,workout_name,duration_minutes,total_volume FROM workouts WHERE date BETWEEN ? AND ? ORDER BY date DESC,id DESC',[$from,$today])->fetchAll();
$filter=fn($rows,$start,$end)=>array_values(array_filter($rows,fn($r)=>$r['date']>=$start && $r['date']<$end));
$current=week_summary($filter($days,$week,'9999-12-31'),$filter($workouts,$week,'9999-12-31'));
$last=week_summary($filter($days,$previous,$week),$filter($workouts,$previous,$week));
$delta=($current['weight']!==null && $last['weight']!==null)?$current['weight']-$last['weight']:null;
$checkin=query('SELECT * FROM weekly_checkins WHERE week_start<=? ORDER BY week_start DESC LIMIT 1',[$week])->fetch();
$sync=query("SELECT * FROM sync_state WHERE source='hevy'")->fetch();
$mobileSync=query('SELECT MAX(last_sync_at) FROM mobile_devices')->fetchColumn();
$hcSleep=average($filter($days,$week,'9999-12-31'),'hc_sleep_minutes');
$chart=chart_series($days,$workouts,$from,$today);
if(!$sync) foreach($chart['weeks'] as &$chartWeek) $chartWeek['workouts']=null;
unset($chartWeek);
page_start('Weekoverzicht'); ?>
<div class="heading"><div><p class="eyebrow">WEEK <?=date('W')?> · <?=e((new DateTimeImmutable($week))->format('d-m'))?> — <?=date('d-m')?></p><h1>De week in beeld.</h1><p class="muted">Deze week tot nu toe. Gemiddelden gebruiken alleen ingevulde dagen.</p></div><span class="badge"><?=admin()?'Persoonlijk overzicht':'Coach · alleen lezen'?></span></div>
<div class="stats">
<article class="stat accent"><span>Gemiddeld gewicht</span><strong><?=display_number($current['weight'])?><small> kg</small></strong><p><?= $delta===null ? 'Nog geen vergelijking beschikbaar' : ($delta>0?'+':'').display_number($delta).' kg t.o.v. vorige week' ?></p><small><?=$current['weight_days']?> meetdagen · vorige week <?=$last['weight_days']?></small></article>
<article class="stat"><span>Stappen deze week</span><strong><?=display_number($current['steps'],0)?></strong><p><?=display_number($current['steps_avg'],0)?> gemiddeld / ingevulde dag</p><small><?=$current['step_days']?> ingevulde dagen</small></article>
<article class="stat"><span>Gemiddelde tijd in bed</span><strong><?=sleep_label($current['sleep'])?></strong><p>Kwaliteit <?=display_number($current['quality'])?> / 5</p><small><?=$current['sleep_days']?> ingevulde nachten</small></article>
<article class="stat"><span>Hevy-trainingen</span><strong><?=$sync ? $current['workouts'] : '—'?></strong><p><?=display_number($current['volume'],0)?> kg trainingsvolume</p><small>Rusthartslag <?=display_number($current['hr'],0)?> bpm</small></article>
</div>
<details class="card"><summary><strong>Specificatie van deze week</strong> · bekijk de dagwaarden achter de totalen</summary><div class="table-wrap"><table><thead><tr><th>Dag</th><th>Gewicht</th><th>Stappen</th><th>Tijd in bed</th><th>Slaapkwaliteit</th><th>Rusthartslag</th><th>Bloeddruk</th><th>Navel</th><th>Heup</th><th>Hevy-training</th></tr></thead><tbody>
<?php $currentByDate=array_column($filter($days,$week,'9999-12-31'),null,'date');$workoutsByDate=[];foreach($filter($workouts,$week,'9999-12-31') as $workout)$workoutsByDate[$workout['date']][]=$workout['workout_name'];for($i=0;$i<7;$i++):$day=(new DateTimeImmutable($week))->modify('+'.$i.' days');$date=$day->format('Y-m-d');$d=$currentByDate[$date]??[];?><tr><td><?=e(['zo','ma','di','wo','do','vr','za'][(int)$day->format('w')].' '.$day->format('d-m'))?></td><td><?=display_number($d['weight']??null)?> kg</td><td><?=display_number($d['steps']??null,0)?></td><td><?=sleep_label($d['sleep_minutes']??null)?></td><td><?=display_number($d['sleep_quality']??null,0)?></td><td><?=display_number($d['resting_hr']??null,0)?></td><td><?=isset($d['blood_pressure_sys'])?e($d['blood_pressure_sys'].' / '.$d['blood_pressure_dia']):'—'?></td><td><?=display_number($d['navel_cm']??null)?> cm</td><td><?=display_number($d['hip_cm']??null)?> cm</td><td><?=e(implode(', ',$workoutsByDate[$date]??[]))?:'—'?></td></tr><?php endfor;?></tbody></table></div><p class="muted">Lege dagen tellen niet mee in gemiddelden. Handmatige stappen en gewichten krijgen voorrang op Health Connect.</p></details>
<p class="sync muted"><?= $sync ? 'Hevy bijgewerkt: '.e($sync['last_success_at']).' UTC. Trainingen omvatten alle geïmporteerde Hevy-sessies.' : 'Hevy is nog niet gesynchroniseerd. Trainingscijfers zijn nog niet beschikbaar.' ?></p>
<p class="muted">Health Connect: <?= $mobileSync?'laatst bijgewerkt '.e($mobileSync).' UTC':'nog niet bijgewerkt' ?> · Gemiddelde slaapregistratie deze week: <?=sleep_label($hcSleep)?>. Handmatige stappen en gewichten krijgen voorrang; slaapregistratie staat apart van tijd in bed.</p>
<section class="chart-grid" data-chart="<?=e(json_encode($chart,JSON_THROW_ON_ERROR))?>">
<?php foreach(['weight'=>'Gewicht & 7-daags gemiddelde','navel'=>'Navelomtrek','steps'=>'Stappen per week','sleep'=>'Tijd in bed per week','training'=>'Trainingen per week','hc_sleep'=>'Slaapregistratie · Health Connect'] as $id=>$label): ?><article class="card chart-card <?= $id==='weight'?'wide':'' ?>"><h2><?=e($label)?></h2><div class="chart-box"><canvas id="chart-<?=e($id)?>" role="img" aria-label="<?=e($label)?>; waarden staan in de ruwe data"></canvas></div></article><?php endforeach; ?>
</section><p class="muted">Vanaf <?=e($fromDate->format('d-m-Y'))?>, inclusief de lopende week. Het 7-daags gemiddelde gebruikt beschikbare metingen in de voorgaande 7 kalenderdagen.</p>
<section class="card"><div class="section-heading"><h2>Laatste weekcheck</h2><span class="badge"><?= $checkin ? 'Week van '.e($checkin['week_start']) : 'Nog niet ingevuld' ?></span></div>
<?php if($checkin): ?><div class="checkin-scores"><?php foreach(['libido'=>'Libido','cravings'=>'Cravings','stress'=>'Stress'] as $k=>$label): ?><div><span><?=e($label)?></span><strong><?=display_number($checkin[$k],0)?> <small>/ 5</small></strong></div><?php endforeach; ?></div><p class="muted">Cravings: 1 = veel zoete trek, 5 = geen. Libido en stress: 1 = laag, 5 = hoog.</p><dl><?php foreach(['libido_details'=>'Libido — toelichting','stress_details'=>'Stress — toelichting','bowel_movement'=>'Ontlasting'] as $k=>$label): ?><dt><?=e($label)?></dt><dd><?=nl2br(e($checkin[$k]??'Niet ingevuld'))?></dd><?php endforeach; ?></dl><?php else: ?><p class="muted">De eerste weekcheck verschijnt hier zodra deze is opgeslagen.</p><?php endif; ?></section>
<?php if(coach_can('training')): ?><section class="card"><div class="section-heading"><h2>Krachttraining</h2><a class="secondary" href="<?=e(url('training.php'))?>">Open trainingsoverzicht →</a></div><p class="muted">Workouts, oefeningen, sets en ontwikkeling vanaf 31 augustus staan in een afzonderlijk overzicht.</p></section><?php endif; ?>
<section class="card"><div class="section-heading"><h2>Ruwe data</h2><a class="secondary" href="<?=e(url('raw.php'))?>">Alle historie →</a></div><p class="muted">Dagelijkse metingen, weekchecks, trainingen en sets. Leeg betekent niet gemeten.</p></section>
<script src="<?=e(url('assets/js/chart.umd.min.js'))?>" defer></script><script src="<?=e(url('assets/js/dashboard.js'))?>" defer></script>
<?php page_end();
