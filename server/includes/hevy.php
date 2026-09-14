<?php
declare(strict_types=1);
require_once __DIR__.'/integrations/WorkoutProvider.php';
final class HevyProvider implements WorkoutProvider {
    private string $key;
    private $transport;
    public function __construct(string $key, ?callable $transport=null) {
        if($key==='') throw new RuntimeException('Hevy API-key ontbreekt.');
        $this->key=$key; $this->transport=$transport;
    }
    public function source(): string { return 'hevy'; }
    private function request(string $path,array $params): array {
        if($this->transport) return ($this->transport)($path,$params);
        for($attempt=0;$attempt<4;$attempt++) {
            $curl=curl_init('https://api.hevyapp.com/v1/'.$path.'?'.http_build_query($params));
            curl_setopt_array($curl,[CURLOPT_RETURNTRANSFER=>true,CURLOPT_FOLLOWLOCATION=>false,
                CURLOPT_CONNECTTIMEOUT=>10,CURLOPT_TIMEOUT=>40,CURLOPT_PROTOCOLS=>CURLPROTO_HTTPS,
                CURLOPT_HTTPHEADER=>['api-key: '.$this->key,'Accept: application/json'],
                CURLOPT_SSL_VERIFYPEER=>true,CURLOPT_SSL_VERIFYHOST=>2]);
            $body=curl_exec($curl);$status=(int)curl_getinfo($curl,CURLINFO_HTTP_CODE);curl_close($curl);
            if($body!==false && $status===200) {
                $data=json_decode($body,true,512,JSON_THROW_ON_ERROR);
                if(!is_array($data)) throw new RuntimeException('Ongeldige Hevy-response.');
                return $data;
            }
            if($status===429 || $status>=500 || $body===false) { if($attempt<3) {sleep(2**$attempt);continue;} }
            // No URL, response body or key is included in the error.
            throw new RuntimeException('Hevy HTTP-status '.$status.'.');
        }
        throw new RuntimeException('Hevy niet bereikbaar.');
    }
    public function events(?string $since): iterable {
        $path=$since===null?'workouts':'workouts/events';$page=1;$seen=[];
        do {
            $params=['page'=>$page,'pageSize'=>10]; if($since!==null) $params['since']=$since;
            $data=$this->request($path,$params);
            $items=$data[$since===null?'workouts':'events']??null;
            if(!is_array($items) || !isset($data['page_count']) || !is_numeric($data['page_count']) || (int)$data['page_count']<0) throw new RuntimeException('Hevy-paginering gewijzigd.');
            $count=(int)$data['page_count'];
            if($count>10000) throw new RuntimeException('Hevy-paginalimiet overschreden.');
            foreach($items as $item) {
                $event=$since===null?['type'=>'updated','workout'=>$item]:$item;
                $id=$event['type']==='updated'?($event['workout']['id']??null):($event['id']??null);
                if(!is_string($id) || $id==='' || !in_array($event['type'],['updated','deleted'],true)) throw new RuntimeException('Ongeldig Hevy-event.');
                // API orders newest first. Do not replay an older event over a newer update/deletion.
                if(isset($seen[$id])) continue;$seen[$id]=true;
                if($event['type']==='updated') $event['workout']=normalize_workout($event['workout']);
                yield $event;
            }
            $page++;
        } while($page<=$count);
    }
}
function normalize_workout(array $w): array {
    foreach(['id','title','start_time','end_time'] as $key) if(!isset($w[$key]) || !is_string($w[$key]) || $w[$key]==='') throw new RuntimeException('Onvolledige workout.');
    if(!isset($w['exercises']) || !is_array($w['exercises'])) throw new RuntimeException('Oefeningen ontbreken.');
    $start=new DateTimeImmutable($w['start_time']);$end=new DateTimeImmutable($w['end_time']);
    if($end<$start) throw new RuntimeException('Ongeldige trainingsduur.');
    $start=$start->setTimezone(new DateTimeZone(config('TIMEZONE')));
    $sets=[];$volume=0;$hasVolume=false;
    foreach($w['exercises'] as $ei=>$exercise) {
        if(!isset($exercise['exercise_template_id'],$exercise['title'],$exercise['sets']) || !is_array($exercise['sets'])) throw new RuntimeException('Onvolledige oefening.');
        foreach($exercise['sets'] as $si=>$set) {
            foreach(['weight_kg','reps','distance_meters','duration_seconds','rpe'] as $key) {
                if(isset($set[$key]) && (!is_numeric($set[$key]) || !is_finite((float)$set[$key]))) throw new RuntimeException('Ongeldige setwaarde.');
            }
            if(isset($set['reps']) && ((float)$set['reps']<0 || floor((float)$set['reps'])!=(float)$set['reps'])) throw new RuntimeException('Ongeldige herhalingen.');
            foreach(['distance_meters','duration_seconds','rpe'] as $key) if(isset($set[$key]) && (float)$set[$key]<0) throw new RuntimeException('Negatieve setwaarde.');
            if(isset($set['weight_kg'],$set['reps']) && (float)$set['weight_kg']>=0 && ($set['type']??'normal')!=='warmup') {
                $volume+=(float)$set['weight_kg']*(int)$set['reps'];$hasVolume=true;
            }
            $sets[]=['exercise_external_id'=>$exercise['exercise_template_id'],'exercise_name'=>$exercise['title'],
              'exercise_index'=>$exercise['index']??$ei,'set_index'=>$set['index']??$si,'set_type'=>$set['type']??'normal',
              'weight_kg'=>$set['weight_kg']??null,'reps'=>$set['reps']??null,'distance'=>$set['distance_meters']??null,
              'duration_seconds'=>$set['duration_seconds']??null,'rpe'=>$set['rpe']??null];
        }
    }
    return ['workout'=>['external_id'=>$w['id'],'date'=>$start->format('Y-m-d'),'started_at'=>$start->format('Y-m-d H:i:s'),
        'workout_name'=>$w['title'],'duration_minutes'=>($end->getTimestamp()-$start->getTimestamp())/60,
        'total_volume'=>$hasVolume?$volume:null,'raw_json'=>json_encode($w,JSON_THROW_ON_ERROR)],'sets'=>$sets];
}
