<?php
require __DIR__.'/../includes/bootstrap.php';require_admin();require __DIR__.'/../includes/layout.php';require __DIR__.'/../includes/mobile.php';
if($_SERVER['REQUEST_METHOD']==='POST') {
    require_post();$action=scalar($_POST,'action');
    mobile_transaction(function()use($action):void {
        if($action==='pair') {
            $address=(string)config('APP_URL');
            if(parse_url($address,PHP_URL_SCHEME)!=='https') throw new InvalidArgumentException('Stel eerst je definitieve HTTPS-adres in APP_URL in.');
            query('DELETE FROM mobile_pairings WHERE used_at IS NOT NULL OR expires_at<=UTC_TIMESTAMP()');
            query('UPDATE mobile_pairings SET used_at=UTC_TIMESTAMP() WHERE used_at IS NULL');
            $code=bin2hex(random_bytes(32));
            query('INSERT INTO mobile_pairings(code_hash,expires_at) VALUES (?,DATE_ADD(UTC_TIMESTAMP(),INTERVAL 10 MINUTE))',[hash('sha256',$code)]);
            $_SESSION['mobile_pair_link']='ritme://pair?'.http_build_query(['server'=>rtrim($address,'/'),'code'=>$code],'','&',PHP_QUERY_RFC3986);
        } elseif($action==='revoke') {
            query('UPDATE mobile_devices SET active=0 WHERE id=?',[scalar($_POST,'id')]);flash('Telefoontoegang ingetrokken.');
        } elseif($action==='delete') {
            if(scalar($_POST,'confirm')!=='VERWIJDER') throw new InvalidArgumentException('Typ VERWIJDER om te bevestigen.');
            query('UPDATE mobile_devices SET active=0');query('UPDATE mobile_pairings SET used_at=UTC_TIMESTAMP() WHERE used_at IS NULL');
            query('DELETE FROM health_connect_daily');flash('Health Connect-data verwijderd en alle telefoonkoppelingen ingetrokken. Handmatige invoer en Hevy zijn behouden.');
            unset($_SESSION['mobile_pair_link']);
        }else throw new InvalidArgumentException('Ongeldige actie.');
    });redirect('devices.php');
}
$devices=query('SELECT id,name,active,last_sync_at,created_at FROM mobile_devices ORDER BY id DESC')->fetchAll();
page_start('Telefoon koppelen'); ?>
<p class="eyebrow">HEALTH CONNECT</p><h1>Je telefoon doet het invulwerk.</h1><p class="muted">Stappen, gewicht, lichaamsvet, slaap, rusthartslag en totaal verbrande calorieën via de Ritme Android-app. Er kan één telefoon tegelijk synchroniseren.</p>
<section class="card"><h2>Koppelen in drie stappen</h2><ol><li>Installeer de Ritme-APK op je Android-telefoon.</li><li>Maak hieronder een tijdelijke koppellink en scan de QR-code in de app.</li><li>Controleer het websiteadres, geef Health Connect-toestemming en kies Nu synchroniseren.</li></ol><form method="post"><?php csrf_field();?><input type="hidden" name="action" value="pair"><button class="primary">Koppellink maken</button></form><p class="muted">De link werkt één keer en verloopt na 10 minuten. Een nieuwe koppeling trekt de vorige telefoon in.</p>
<?php if(isset($_SESSION['mobile_pair_link'])): ?><div id="pair-qr" data-link="<?=e($_SESSION['mobile_pair_link'])?>" aria-label="QR-code voor telefoonkoppeling"></div><label>Of kopieer deze koppellink<input readonly value="<?=e($_SESSION['mobile_pair_link'])?>"></label><p class="muted">Bewaar of deel deze tijdelijke toegangscode niet met anderen.</p><?php unset($_SESSION['mobile_pair_link']);endif; ?></section>
<section class="card"><h2>Gekoppelde telefoons</h2><div class="table-wrap"><table><thead><tr><th>Telefoon</th><th>Status</th><th>Laatst bijgewerkt (UTC)</th><th>Toegang</th></tr></thead><tbody><?php foreach($devices as $device):?><tr><td><?=e($device['name'])?></td><td><?=$device['active']?'Actief':'Ingetrokken'?></td><td><?=e($device['last_sync_at']??'Nog niet bijgewerkt')?></td><td><?php if($device['active']):?><form method="post"><?php csrf_field();?><input type="hidden" name="action" value="revoke"><input type="hidden" name="id" value="<?=$device['id']?>"><button class="secondary">Intrekken</button></form><?php endif;?></td></tr><?php endforeach;?><?php if(!$devices):?><tr><td colspan="4">Nog geen telefoon gekoppeld.</td></tr><?php endif;?></tbody></table></div></section>
<section class="card"><h2>Health Connect-data verwijderen</h2><p class="muted">Verwijdert alle geïmporteerde Health Connect-dagwaarden en trekt alle telefoontoegang in. Handmatige gegevens en Hevy-trainingen blijven staan. Typ VERWIJDER om te bevestigen.</p><form method="post"><?php csrf_field();?><input type="hidden" name="action" value="delete"><label>Bevestiging<input name="confirm" autocomplete="off" required pattern="VERWIJDER"></label><button class="secondary">Verwijderen en ontkoppelen</button></form></section>
<script src="<?=e(url('assets/js/qrcode.min.js'))?>" defer></script><script src="<?=e(url('assets/js/pairing.js'))?>" defer></script>
<?php page_end();
