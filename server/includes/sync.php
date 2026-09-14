<?php
declare(strict_types=1);
function save_workout(string $source,array $normalized): void {
    // Caller owns transaction; replacing sets and updating the workout must be atomic.
    upsert('workouts',['source'=>$source]+$normalized['workout']);
    $id=query('SELECT id FROM workouts WHERE source=? AND external_id=?',[$source,$normalized['workout']['external_id']])->fetchColumn();
    query('DELETE FROM workout_sets WHERE workout_id=?',[$id]);
    foreach($normalized['sets'] as $set) upsert('workout_sets',['workout_id'=>$id]+$set);
}
function sync_provider(WorkoutProvider $provider): int {
    $source=$provider->source();$lock='fitness_sync_'.$source;
    $cutoff=null;
    if($source==='hevy') {
        $cutoff=(string)config('HEVY_IMPORT_FROM_DATE');
        $parsed=DateTimeImmutable::createFromFormat('!Y-m-d',$cutoff);
        if(!$parsed || $parsed->format('Y-m-d')!==$cutoff) throw new RuntimeException('Ongeldige HEVY_IMPORT_FROM_DATE.');
    }
    if((int)query('SELECT GET_LOCK(?,0)',[$lock])->fetchColumn()!==1) throw new RuntimeException('Synchronisatie is al actief.');
    try {
        $last=query('SELECT last_success_at FROM sync_state WHERE source=?',[$source])->fetchColumn();
        $started=gmdate('Y-m-d H:i:s');
        $since=$last ? (new DateTimeImmutable($last,new DateTimeZone('UTC')))->modify('-5 minutes')->format('Y-m-d\TH:i:s\Z') : null;
        $count=0;
        // Fetch all pages before changing the database: failed pagination cannot partially import.
        $events=iterator_to_array($provider->events($since),false);
        db()->beginTransaction();
        foreach($events as $event) {
            if($event['type']==='deleted') query('DELETE FROM workouts WHERE source=? AND external_id=?',[$source,$event['id']]);
            elseif($cutoff!==null && $event['workout']['workout']['date']<$cutoff) query('DELETE FROM workouts WHERE source=? AND external_id=?',[$source,$event['workout']['workout']['external_id']]);
            else save_workout($source,$event['workout']);
            $count++;
        }
        upsert('sync_state',['source'=>$source,'last_success_at'=>$started,'imported_count'=>$count]);
        db()->commit();return $count;
    } catch(Throwable $e) { if(db()->inTransaction()) db()->rollBack();throw $e; }
    finally {query('SELECT RELEASE_LOCK(?)',[$lock]);}
}
