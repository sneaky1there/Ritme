<?php
declare(strict_types=1);
require __DIR__.'/../includes/bootstrap.php';
set_exception_handler(function(Throwable $e):void{fwrite(STDERR,$e->getMessage().PHP_EOL);exit(1);});require __DIR__.'/../includes/statistics.php';require __DIR__.'/../includes/progress.php';require __DIR__.'/../includes/hevy.php';
$count=0;
function check(bool $condition,string $name): void {global $count;if(!$condition) throw new RuntimeException('FAIL: '.$name);$count++;echo "PASS $name\n";}
function rejects(callable $test,string $name): void {try{$test();}catch(InvalidArgumentException $e){check(true,$name);return;}check(false,$name);}
check(monday('2026-09-06')==='2026-08-31','Sunday belongs to preceding Monday');
check(monday('2026-01-01')==='2025-12-29','ISO year boundary');
rejects(fn()=>valid_date('2026-02-30'),'invalid calendar date');
rejects(fn()=>valid_date('2099-01-01'),'future dates');
rejects(fn()=>daily_input(['date'=>'2026-01-01','steps'=>'2.2']),'fractional steps');
rejects(fn()=>daily_input(['date'=>'2026-01-01','steps'=>'-1']),'negative steps');
rejects(fn()=>daily_input(['date'=>'2026-01-01','weight'=>['12']]),'array payload');
rejects(fn()=>daily_input(['date'=>'2026-01-01','blood_pressure_sys'=>'120']),'incomplete blood pressure');
rejects(fn()=>daily_input(['date'=>'2026-01-01','blood_pressure_sys'=>'80','blood_pressure_dia'=>'120']),'reversed blood pressure');
rejects(fn()=>weekly_input(['week_start'=>'2026-01-01','cravings'=>'6']),'out of range scale');
$d=daily_input(['date'=>'2026-01-01','weight'=>'82,45','steps'=>'0','meals_not_homemade'=>'0']);
check($d['weight']===82.45 && $d['steps']===0.0 && $d['sleep_minutes']===null,'decimal comma, zero and missing values');
check(weekly_input(['week_start'=>'2026-01-01','cravings'=>'1'])['cravings']===1.0,'preserve Excel cravings direction');
check(average([['x'=>null],['x'=>0],['x'=>10]],'x')===5.0,'average ignores missing but includes zero');
check(total([['x'=>null]],'x')===null,'missing totals stay missing');
$series=chart_series([['date'=>'2026-01-01','weight'=>80],['date'=>'2026-01-07','weight'=>90]],[],'2026-01-07','2026-01-08');
check($series['days'][0]['rolling']===85.0 && $series['days'][1]['rolling']===90.0,'rolling average uses seven calendar days');
check(compare_sets(['weight_kg'=>65,'reps'=>10],['weight_kg'=>67.5,'reps'=>10])==='progressie ↑','progress by weight');
check(compare_sets(['weight_kg'=>65,'reps'=>10],['weight_kg'=>65,'reps'=>12])==='progressie ↑','progress by reps');
check(compare_sets(['weight_kg'=>65,'reps'=>10],['weight_kg'=>67.5,'reps'=>8])==='andere belasting','mixed change not falsely positive');
check(compare_sets(['weight_kg'=>null,'reps'=>10],['weight_kg'=>65,'reps'=>12])==='onvoldoende data','missing weight not compared');
$w=['id'=>'test-workout','title'=>'Test','start_time'=>'2026-01-01T23:30:00Z','end_time'=>'2026-01-02T00:30:00Z','exercises'=>[
 ['exercise_template_id'=>'press','title'=>'Press','sets'=>[['weight_kg'=>100,'reps'=>5,'type'=>'warmup'],['weight_kg'=>65,'reps'=>10,'type'=>'normal']]],
 ['exercise_template_id'=>'run','title'=>'Run','sets'=>[['distance_meters'=>1000,'duration_seconds'=>300]]]
]];
$n=normalize_workout($w);
check($n['workout']['date']==='2026-01-02','UTC workout date converted to Amsterdam');
check($n['workout']['duration_minutes']===60 && $n['workout']['total_volume']===650.0,'duration and volume exclude warmup');
check($n['sets'][2]['distance']===1000 && $n['sets'][2]['weight_kg']===null,'cardio nulls and meters preserved');
$calls=[];$provider=new HevyProvider('fixture-key',function($path,$params)use(&$calls,$w){$calls[]=[$path,$params];return ['page_count'=>2,'workouts'=>$params['page']===1?[$w]:[array_replace($w,['id'=>'second'])]];});
check(count(iterator_to_array($provider->events(null)))===2 && $calls[1][1]['page']===2,'initial import follows pagination');
$calls=[];$provider=new HevyProvider('fixture-key',function($path,$params)use(&$calls,$w){$calls[]=[$path,$params];return ['page_count'=>2,'events'=>$params['page']===1?[['type'=>'deleted','id'=>'test-workout']]:[['type'=>'updated','workout'=>$w]]];});
$events=iterator_to_array($provider->events('2026-01-01T00:00:00Z'));
check(count($events)===1 && $events[0]['type']==='deleted','newest deletion wins across pages');
check($calls[0][0]==='workouts/events' && isset($calls[0][1]['since']),'incremental API endpoint and cursor');
check(e('<script>"&')==='&lt;script&gt;&quot;&amp;','HTML escaping');
echo "\n$count checks passed.\n";
