<?php
require __DIR__.'/../includes/bootstrap.php';require_coach_or_admin('nutrition');require __DIR__.'/../includes/layout.php';
$date=valid_date(scalar($_GET,'date',date('Y-m-d')));
if($_SERVER['REQUEST_METHOD']==='POST'){
    if(!admin()){http_response_code(403);exit('Alleen de eigenaar kan voeding wijzigen.');}
    require_post();$date=valid_date(scalar($_POST,'date'));
    $data=['date'=>$date,'calories_kcal'=>number_value($_POST,'calories_kcal',0,20000),'protein_g'=>number_value($_POST,'protein_g',0,2000),'carbohydrates_g'=>number_value($_POST,'carbohydrates_g',0,3000),'fat_g'=>number_value($_POST,'fat_g',0,2000)];
    if(count(array_filter(array_slice($data,1),fn($value)=>$value!==null))===0){query('DELETE FROM nutrition_daily WHERE date=?',[$date]);flash('Voedingswaarden voor deze dag zijn gewist.');}
    else{upsert('nutrition_daily',$data);flash('Voedingswaarden opgeslagen.');}
    redirect('nutrition.php?date='.rawurlencode($date));
}
$entry=query('SELECT * FROM nutrition_daily WHERE date=?',[$date])->fetch()?:[];
$diaryRows=query('SELECT meal,product_name,brand,amount_g,calories_kcal,protein_g,carbohydrates_g,fat_g FROM food_diary_entries WHERE date=? ORDER BY FIELD(meal,"breakfast","lunch","dinner","other"),id',[$date])->fetchAll();
$mealNames=['breakfast'=>'Ontbijt','lunch'=>'Lunch','dinner'=>'Diner','other'=>'Tussendoor'];$diaryByMeal=[];
foreach($diaryRows as $row)$diaryByMeal[$row['meal']][]=$row;
$weekStart=monday($date);$weekEnd=(new DateTimeImmutable($weekStart))->modify('+6 days')->format('Y-m-d');
$weekRows=query('SELECT * FROM nutrition_daily WHERE date BETWEEN ? AND ? ORDER BY date',[$weekStart,$weekEnd])->fetchAll();
$monthStart=substr($date,0,7).'-01';$monthEnd=(new DateTimeImmutable($monthStart))->modify('last day of this month')->format('Y-m-d');
$monthRows=query('SELECT date,calories_kcal,protein_g,carbohydrates_g,fat_g FROM nutrition_daily WHERE date BETWEEN ? AND ? ORDER BY date',[$monthStart,$monthEnd])->fetchAll();
$fields=['calories_kcal'=>['Calorieën','kcal',0],'protein_g'=>['Eiwitten','g',1],'carbohydrates_g'=>['Koolhydraten','g',1],'fat_g'=>['Vetten','g',1]];
$averages=[];
foreach($fields as $key=>$definition){$values=array_values(array_filter(array_column($weekRows,$key),fn($value)=>$value!==null));$averages[$key]=$values?array_sum(array_map('floatval',$values))/count($values):null;}
$prev=(new DateTimeImmutable($date))->modify('-1 day')->format('Y-m-d');$next=(new DateTimeImmutable($date))->modify('+1 day')->format('Y-m-d');
page_start('Voeding');?>
<div class="heading"><div><p class="eyebrow">VOEDINGSDAGBOEK</p><h1>Calorieën en macro’s.</h1><p class="muted">Dagwaarden in je eigen database, zonder externe voedingsdienst.</p></div><span class="badge"><?=admin()?'Persoonlijk overzicht':'Coach · alleen lezen'?></span></div>
<form class="card range" method="get"><a class="secondary" href="?date=<?=e($prev)?>">← Vorige</a><label>Datum<input type="date" name="date" value="<?=e($date)?>" max="<?=date('Y-m-d')?>"></label><button class="primary">Tonen</button><?php if($next<=date('Y-m-d')):?><a class="secondary" href="?date=<?=e($next)?>">Volgende →</a><?php endif;?></form>
<section class="stats nutrition-stats"><?php foreach($fields as $key=>$definition):?><article class="stat <?=$key==='calories_kcal'?'accent':''?>"><span><?=e($definition[0])?></span><strong><?=display_number($entry[$key]??null,$definition[2])?><small> <?=e($definition[1])?></small></strong><p>Weekgemiddelde: <?=display_number($averages[$key],$definition[2])?> <?=e($definition[1])?></p></article><?php endforeach;?></section>
<section class="card food-diary"><div class="section-heading"><div><h2>Gegeten op <?=e((new DateTimeImmutable($date))->format('d-m-Y'))?></h2><p class="muted">Producten die via de Ritme-app zijn geregistreerd.</p></div></div>
<?php if(!$diaryRows):?><p class="empty">Op deze dag zijn nog geen producten geregistreerd.</p><?php else:?>
<?php foreach($mealNames as $mealKey=>$mealName):if(empty($diaryByMeal[$mealKey]))continue;?><section class="meal"><h3><?=e($mealName)?></h3><div class="table-wrap"><table><thead><tr><th>Product</th><th>Hoeveelheid</th><th>Calorieën</th><th>Eiwit</th><th>Koolhydraten</th><th>Vet</th></tr></thead><tbody><?php foreach($diaryByMeal[$mealKey] as $food):?><tr><td><strong><?=e($food['product_name'])?></strong><?php if($food['brand']):?><small><?=e($food['brand'])?></small><?php endif;?></td><td><?=display_number($food['amount_g'],1)?> g</td><td><?=display_number($food['calories_kcal'],0)?> kcal</td><td><?=display_number($food['protein_g'],1)?> g</td><td><?=display_number($food['carbohydrates_g'],1)?> g</td><td><?=display_number($food['fat_g'],1)?> g</td></tr><?php endforeach;?></tbody></table></div></section><?php endforeach;?>
<?php endif;?></section>
<?php if(admin()):?><form method="post" class="card entry"><div class="section-heading"><div><h2>Waarden voor <?=e((new DateTimeImmutable($date))->format('d-m-Y'))?></h2><p class="muted">Neem de dagtotalen over uit je huidige voedingsapp of berekening.</p></div></div><?php csrf_field();?><input type="hidden" name="date" value="<?=e($date)?>"><div class="form-grid"><?php foreach($fields as $key=>$definition):?><label><?=e($definition[0])?> <span class="unit"><?=e($definition[1])?></span><input type="number" name="<?=e($key)?>" min="0" max="<?=$key==='calories_kcal'?'20000':($key==='carbohydrates_g'?'3000':'2000')?>" step="<?=$key==='calories_kcal'?'1':'0.1'?>" value="<?=e($entry[$key]??'')?>" inputmode="decimal"></label><?php endforeach;?></div><div class="form-footer"><span class="muted">Laat alle velden leeg om deze dag te wissen.</span><button class="primary">Opslaan</button></div></form><?php endif;?>
<section class="card"><h2>Week van <?=e((new DateTimeImmutable($weekStart))->format('d-m-Y'))?></h2><div class="table-wrap"><table><thead><tr><th>Dag</th><?php foreach($fields as $definition):?><th><?=e($definition[0])?></th><?php endforeach;?></tr></thead><tbody><?php $byDate=array_column($weekRows,null,'date');for($i=0;$i<7;$i++):$day=(new DateTimeImmutable($weekStart))->modify('+'.$i.' days');$d=$byDate[$day->format('Y-m-d')]??[];?><tr><td><?=e(['zo','ma','di','wo','do','vr','za'][(int)$day->format('w')].' '.$day->format('d-m'))?></td><?php foreach($fields as $key=>$definition):?><td><?=display_number($d[$key]??null,$definition[2])?> <?=e($definition[1])?></td><?php endforeach;?></tr><?php endfor;?><tr><th>Gemiddeld</th><?php foreach($fields as $key=>$definition):?><th><?=display_number($averages[$key],$definition[2])?> <?=e($definition[1])?></th><?php endforeach;?></tr></tbody></table></div></section>
<section class="card chart-card" data-nutrition-chart="<?=e(json_encode($monthRows,JSON_THROW_ON_ERROR))?>"><h2>Deze maand</h2><div class="chart-box"><canvas id="nutrition-chart" role="img" aria-label="Calorieën en macronutriënten per dag"></canvas></div></section>
<script src="<?=e(url('assets/js/chart.umd.min.js'))?>" defer></script><script src="<?=e(url('assets/js/nutrition.js'))?>" defer></script>
<?php page_end();
