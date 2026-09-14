<?php
require __DIR__.'/../../includes/bootstrap.php'; require_admin(); require_post();
$data=daily_input($_POST);
upsert('daily_metrics', $data);
flash('Je check-in is opgeslagen.');
redirect('index.php?date='.rawurlencode($data['date']));
