<?php
declare(strict_types=1);
function admin(): bool { return ($_SESSION['admin'] ?? false) === true; }
function mobile_overview(): bool {
    if(($_SESSION['mobile_overview']??false)!==true||empty($_SESSION['mobile_device_id']))return false;
    static $active=null;
    if($active===null)$active=(bool)query('SELECT 1 FROM mobile_devices WHERE id=? AND active=1',[(int)$_SESSION['mobile_device_id']])->fetchColumn();
    if(!$active)unset($_SESSION['mobile_overview'],$_SESSION['mobile_device_id']);
    return $active;
}
function require_admin(): void { if (!admin()) redirect('login.php'); }
function csrf(): string { return $_SESSION['csrf'] ??= bin2hex(random_bytes(32)); }
function csrf_field(): void { echo '<input type="hidden" name="csrf" value="'.e(csrf()).'">'; }
function require_post(): void {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') { http_response_code(405); header('Allow: POST'); exit('Alleen POST toegestaan.'); }
    if (!hash_equals(csrf(),scalar($_POST,'csrf'))) { http_response_code(403); exit('Formulier verlopen. Herlaad de pagina.'); }
}
function coach_access(): array {
    if(admin()) return ['overview'=>true,'training'=>true,'nutrition'=>true];
    if(mobile_overview()) return ['overview'=>true,'training'=>false,'nutrition'=>false];
    static $access=null;
    if($access!==null) return $access;
    $id=$_SESSION['coach_id']??0;
    $row=$id?query('SELECT can_view_overview,can_view_training,can_view_nutrition FROM coach_tokens WHERE id=? AND active=1',[$id])->fetch():false;
    if(!$row) return $access=['overview'=>false,'training'=>false,'nutrition'=>false];
    return $access=['overview'=>(bool)$row['can_view_overview'],'training'=>(bool)$row['can_view_training'],'nutrition'=>(bool)$row['can_view_nutrition']];
}
function coach_can(string $view): bool {return admin()||in_array($view,['overview','training','nutrition'],true)&&coach_access()[$view];}
function coach_home(): string {return coach_can('overview')?'coach.php':(coach_can('training')?'training.php':(coach_can('nutrition')?'nutrition.php':'login.php'));}
function require_coach_or_admin(string $view='overview'): void {
    if(!in_array($view,['overview','training','nutrition'],true)) throw new LogicException('Onbekend coachrecht.');
    // An explicit token is always checked and removed from the URL immediately.
    if(isset($_GET['token'])) {
        $token=scalar($_GET,'token');
        $row=preg_match('/^[a-f0-9]{64}$/D',$token)?query('SELECT id,can_view_overview,can_view_training,can_view_nutrition FROM coach_tokens WHERE token_hash=? AND active=1',[hash('sha256',$token)])->fetch():false;
        if(!$row||(!(bool)$row['can_view_overview']&&!(bool)$row['can_view_training']&&!(bool)$row['can_view_nutrition'])) {unset($_SESSION['coach_id']);http_response_code(403);exit('Deze coachlink is ongeldig of ingetrokken.');}
        session_regenerate_id(true);$_SESSION['coach_id']=(int)$row['id'];
        query('UPDATE coach_tokens SET last_used_at=NOW() WHERE id=?',[$row['id']]);
        redirect((bool)$row['can_view_overview']?'coach.php':((bool)$row['can_view_training']?'training.php':'nutrition.php'));
    }
    if(admin()||($view==='overview'&&mobile_overview()))return;
    if(!coach_can($view)) {
        if(!coach_can('overview')&&!coach_can('training')&&!coach_can('nutrition'))unset($_SESSION['coach_id']);
        http_response_code(403);exit('Deze coachlink heeft geen toegang tot dit overzicht.');
    }
}
function try_login(string $username, string $password): bool {
    // Atomic, per-IP fixed window; deliberately ignore untrusted forwarding headers.
    $bucket=hash('sha256',($_SERVER['REMOTE_ADDR'] ?? 'unknown'));
    query('DELETE FROM login_attempts WHERE window_start < ?',[time()-86400]);
    query('INSERT INTO login_attempts (bucket,attempts,window_start) VALUES (?,1,?) ON DUPLICATE KEY UPDATE attempts=IF(window_start < ?,1,attempts+1), window_start=IF(window_start < ?,VALUES(window_start),window_start)',[$bucket,time(),time()-900,time()-900]);
    $attempts=query('SELECT attempts FROM login_attempts WHERE bucket=?',[$bucket])->fetchColumn();
    if ((int)$attempts > 10) { http_response_code(429); return false; }
    $hash=(string)config('ADMIN_PASSWORD_HASH');
    $valid = password_verify($password, $hash ?: '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2uheWG/igi.');
    if ($hash !== '' && hash_equals((string)config('ADMIN_USERNAME'),$username) && $valid) {
        session_regenerate_id(true); $_SESSION=['admin'=>true,'last_activity'=>time()];
        query('DELETE FROM login_attempts WHERE bucket=?',[$bucket]); return true;
    }
    return false;
}
