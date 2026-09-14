<?php
declare(strict_types=1);
// Pareto comparison: both weight and reps must be at least equal; one must increase.
// Mixed weight/reps changes are intentionally not treated as a reliable progression.
function compare_sets(array $previous,array $current): string {
    if(!isset($previous['weight_kg'],$previous['reps'],$current['weight_kg'],$current['reps'])) return 'onvoldoende data';
    $w=(float)$current['weight_kg']<=>(float)$previous['weight_kg'];
    $r=(int)$current['reps']<=>(int)$previous['reps'];
    if($w>=0 && $r>=0 && ($w>0 || $r>0)) return 'progressie ↑';
    if($w===0 && $r===0) return 'gelijk';
    if($w<=0 && $r<=0) return 'lager';
    return 'andere belasting';
}
function recent_progress(string $fromDate, ?string $workoutName = null): array {
    $params=[$fromDate];
    $workoutFilter='';
    if($workoutName!==null) {$workoutFilter=' AND w.workout_name=?';$params[]=$workoutName;}
    $sets=query("SELECT s.*,w.started_at,w.workout_name FROM workout_sets s JOIN workouts w ON w.id=s.workout_id WHERE w.source='hevy' AND s.set_type <> 'warmup' AND s.weight_kg >= 0 AND s.reps > 0 AND w.date >= ?".$workoutFilter." ORDER BY w.started_at DESC,w.id DESC,s.weight_kg DESC,s.reps DESC",$params)->fetchAll();
    $groups=[];
    foreach($sets as $set) { $key=$set['exercise_external_id'];$id=$set['workout_id'];if(!isset($groups[$key][$id]) && count($groups[$key]??[])<2) $groups[$key][$id]=$set; }
    $out=[];
    foreach($groups as $group) { $pair=array_values($group);if(count($pair)===2) $out[]=['name'=>$pair[0]['exercise_name'],'current'=>$pair[0],'previous'=>$pair[1],'result'=>compare_sets($pair[1],$pair[0])]; }
    usort($out,fn($a,$b)=>strnatcasecmp($a['name'],$b['name']));
    return $out;
}
