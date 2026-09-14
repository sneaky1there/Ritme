<?php
require __DIR__.'/../../includes/bootstrap.php'; require_admin(); require_post();
$data=weekly_input($_POST);
upsert('weekly_checkins', $data);
flash('Je weekcheck is opgeslagen.');
redirect('weekly.php?date='.rawurlencode($data['week_start']));
