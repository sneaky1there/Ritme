<?php
// Run ONLY with a disposable database ending in _test. Never touches a production DB.
require __DIR__.'/../includes/bootstrap.php';require __DIR__.'/../includes/hevy.php';require __DIR__.'/../includes/sync.php';
if(!str_ends_with((string)config('DB_NAME'),'_test')) {fwrite(STDERR,"DB_NAME must end in _test.\n");exit(1);}
function verify(bool $ok,string $name): void {if(!$ok) throw new RuntimeException($name);echo "PASS $name\n";}
// Schema installation can be run twice safely.
$sql=file_get_contents(__DIR__.'/../sql/install.sql');db()->exec($sql);db()->exec($sql);
$day=daily_input(['date'=>'2026-01-01','navel_cm'=>'80']);upsert('daily_metrics',$day);$day['navel_cm']=81;upsert('daily_metrics',$day);
verify((int)query('SELECT COUNT(*) FROM daily_metrics WHERE date=?',[$day['date']])->fetchColumn()===1,'daily idempotency');
verify((float)query('SELECT navel_cm FROM daily_metrics WHERE date=?',[$day['date']])->fetchColumn()===81.0,'daily update');
$week=weekly_input(['week_start'=>'2026-01-01','comments'=>'<script>test</script>']);upsert('weekly_checkins',$week);upsert('weekly_checkins',$week);
verify((int)query('SELECT COUNT(*) FROM weekly_checkins WHERE week_start=?',[$week['week_start']])->fetchColumn()===1,'weekly idempotency');
$fixture=['id'=>'integration-fixture','title'=>'Test','start_time'=>'2026-01-01T12:00:00Z','end_time'=>'2026-01-01T13:00:00Z','exercises'=>[['exercise_template_id'=>'press','title'=>'Press','sets'=>[['type'=>'normal','weight_kg'=>65,'reps'=>10]]]]];
$provider=new class($fixture) implements WorkoutProvider {
 public array $items;public function __construct($f){$this->items=[['type'=>'updated','workout'=>$f]];}
 public function source():string{return 'fixture';} public function events(?string $since):iterable {foreach($this->items as $i){if($i['type']==='updated')$i['workout']=normalize_workout($i['workout']);yield $i;}}
};
sync_provider($provider);sync_provider($provider);
verify((int)query("SELECT COUNT(*) FROM workouts WHERE source='fixture'")->fetchColumn()===1,'sync idempotency');
verify((int)query("SELECT COUNT(*) FROM workout_sets s JOIN workouts w ON w.id=s.workout_id WHERE w.source='fixture'")->fetchColumn()===1,'sets replaced without duplication');
$provider->items[0]['workout']['exercises'][0]['sets'][0]['weight_kg']=70;sync_provider($provider);
verify((float)query("SELECT total_volume FROM workouts WHERE source='fixture'")->fetchColumn()===700.0,'updated set updates volume');
$before=query("SELECT last_success_at FROM sync_state WHERE source='fixture'")->fetchColumn();
$provider->items[]=['type'=>'updated','workout'=>['id'=>'broken']];
$failed=false;try{sync_provider($provider);}catch(Throwable $e){$failed=true;}
verify($failed && query("SELECT last_success_at FROM sync_state WHERE source='fixture'")->fetchColumn()===$before,'failure preserves checkpoint');
$provider->items=[['type'=>'deleted','id'=>'integration-fixture']];sync_provider($provider);
verify((int)query("SELECT COUNT(*) FROM workouts WHERE source='fixture'")->fetchColumn()===0,'remote deletion applied');
verify((int)query('SELECT COUNT(*) FROM workout_sets s LEFT JOIN workouts w ON w.id=s.workout_id WHERE w.id IS NULL')->fetchColumn()===0,'set cascade deletion');
$token=bin2hex(random_bytes(32));query('INSERT INTO coach_tokens(token_hash,description)VALUES(?,?)',[hash('sha256',$token),'Integration']);
$id=db()->lastInsertId();query('UPDATE coach_tokens SET active=0 WHERE id=?',[$id]);
verify((int)query('SELECT active FROM coach_tokens WHERE id=?',[$id])->fetchColumn()===0,'token revocation');
query('DELETE FROM coach_tokens WHERE id=?',[$id]);query('DELETE FROM daily_metrics WHERE date=?',[$day['date']]);query('DELETE FROM weekly_checkins WHERE week_start=?',[$week['week_start']]);query("DELETE FROM sync_state WHERE source='fixture'");
echo "Database integration checks passed.\n";
