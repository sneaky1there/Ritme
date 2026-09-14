<?php
declare(strict_types=1);
function average(array $rows, string $key): ?float {
    $values=array_values(array_filter(array_column($rows,$key),fn($v)=>$v!==null));
    return $values ? array_sum($values)/count($values) : null;
}
function total(array $rows, string $key): ?float {
    $values=array_values(array_filter(array_column($rows,$key),fn($v)=>$v!==null));
    return $values ? (float)array_sum($values) : null;
}
function week_summary(array $days,array $workouts): array {
    return ['weight'=>average($days,'weight'),'steps'=>total($days,'steps'),'steps_avg'=>average($days,'steps'),
      'sleep'=>average($days,'sleep_minutes'),'quality'=>average($days,'sleep_quality'),'hr'=>average($days,'resting_hr'),
      'calories_burned'=>total($days,'total_calories_kcal'),'body_fat'=>average($days,'body_fat_percentage'),
      'workouts'=>count($workouts),'volume'=>total($workouts,'total_volume'),
      'weight_days'=>count(array_filter($days,fn($d)=>$d['weight']!==null)),
      'step_days'=>count(array_filter($days,fn($d)=>$d['steps']!==null)),
      'sleep_days'=>count(array_filter($days,fn($d)=>$d['sleep_minutes']!==null))];
}
function chart_series(array $rows,array $workouts,string $from,string $to): array {
    $byDate=array_column($rows,null,'date'); $days=[]; $weeks=[];
    for($d=new DateTimeImmutable($from);$d<=new DateTimeImmutable($to);$d=$d->modify('+1 day')) {
      $date=$d->format('Y-m-d');$row=$byDate[$date]??[];$weights=[];
      for($i=0;$i<7;$i++) { $v=$byDate[$d->modify('-'.$i.' days')->format('Y-m-d')]['weight']??null; if($v!==null) $weights[]=(float)$v; }
      $days[]=['date'=>$date,'weight'=>isset($row['weight'])?(float)$row['weight']:null,'navel'=>isset($row['navel_cm'])?(float)$row['navel_cm']:null,
        'body_fat'=>isset($row['body_fat_percentage'])?(float)$row['body_fat_percentage']:null,'resting_hr'=>isset($row['resting_hr'])?(float)$row['resting_hr']:null,
        'rolling'=>$weights?array_sum($weights)/count($weights):null];
      $week=monday($date);if(!isset($weeks[$week])) $weeks[$week]=['week'=>$week,'rows'=>[],'workouts'=>0];
      if($row) $weeks[$week]['rows'][]=$row;
    }
    foreach($workouts as $w) { $week=monday($w['date']);if(isset($weeks[$week])) $weeks[$week]['workouts']++; }
    return ['days'=>$days,'weeks'=>array_values(array_map(fn($w)=>['week'=>$w['week'],'steps'=>total($w['rows'],'steps'),'sleep'=>($v=average($w['rows'],'sleep_minutes'))===null?null:$v/60,
      'calories_burned'=>total($w['rows'],'total_calories_kcal'),'workouts'=>$w['workouts'],'hc_sleep'=>($hc=average($w['rows'],'hc_sleep_minutes'))===null?null:$hc/60],$weeks))];
}
