<?php
require __DIR__.'/../includes/bootstrap.php';

// Temporary installation helper. It never writes configuration or stores the password.
$passwordHash = null;
$error = null;
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_post();
    $password = is_string($_POST['password'] ?? null) ? $_POST['password'] : '';
    $confirmation = is_string($_POST['confirmation'] ?? null) ? $_POST['confirmation'] : '';
    if ($password !== $confirmation) {
        $error = 'De wachtwoorden zijn niet gelijk.';
    } elseif (strlen($password) < 12 || strlen($password) > 72) {
        $error = 'Gebruik een wachtwoord van 12 t/m 72 tekens.';
    } else {
        $passwordHash = password_hash($password, PASSWORD_DEFAULT);
        // Discard submitted secrets before rendering the response.
        $password = $confirmation = '';
    }
}
?><!doctype html>
<html lang="nl">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<meta name="robots" content="noindex,nofollow">
<title>Tijdelijk wachtwoordhulpmiddel · Ritme</title>
<link rel="stylesheet" href="<?=e(url('assets/css/app.css'))?>">
</head>
<body><main>
<section class="login card">
<p class="eyebrow">TIJDELIJKE INSTALLATIEHULP</p>
<h1>Maak je wachtwoordhash.</h1>
<p class="muted">Deze pagina slaat je wachtwoord niet op. Gebruik HTTPS en verwijder <strong>password-tool.php</strong> direct nadat je de hash in je private configuratie hebt gezet.</p>
<?php if ($error): ?><p class="error" role="alert"><?=e($error)?></p><?php endif; ?>
<?php if ($passwordHash): ?>
<label>Gegenereerde hash
<textarea readonly rows="3" autocomplete="off"><?=e($passwordHash)?></textarea>
</label>
<ol>
<li>Kopieer de volledige hash, inclusief alle dollartekens.</li>
<li>Plak hem in <code>config/config.php</code> bij <code>ADMIN_PASSWORD_HASH</code>, tussen enkele aanhalingstekens.</li>
<li>Verwijder daarna <code>public/password-tool.php</code>.</li>
<li>Open <a href="<?=e(url('login.php'))?>">de loginpagina</a> en test je wachtwoord.</li>
</ol>
<?php else: ?>
<form method="post" autocomplete="off">
<?php csrf_field(); ?>
<label>Nieuw wachtwoord
<input type="password" name="password" minlength="12" maxlength="72" autocomplete="new-password" required>
</label>
<label>Herhaal het wachtwoord
<input type="password" name="confirmation" minlength="12" maxlength="72" autocomplete="new-password" required>
</label>
<button class="primary" type="submit">Hash maken</button>
</form>
<?php endif; ?>
</section>
</main></body></html>
