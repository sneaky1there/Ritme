<?php
declare(strict_types=1);
function e($value): string { return htmlspecialchars((string)($value ?? ''), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'); }
function scalar(array $input, string $key, string $default = ''): string {
    if (!isset($input[$key])) return $default;
    if (!is_string($input[$key])) throw new InvalidArgumentException('Ongeldige invoer.');
    return trim($input[$key]);
}
function valid_date(string $value): string {
    $date = DateTimeImmutable::createFromFormat('!Y-m-d', $value);
    if (!$date || $date->format('Y-m-d') !== $value || $value < '2000-01-01' || $value > date('Y-m-d')) {
        throw new InvalidArgumentException('Kies een geldige datum tussen 2000 en vandaag.');
    }
    return $value;
}
function monday(string $date): string { return (new DateTimeImmutable($date))->modify('monday this week')->format('Y-m-d'); }
function number_value(array $input, string $key, float $min, float $max, bool $integer = false): ?float {
    $value = str_replace(',', '.', scalar($input, $key));
    if ($value === '') return null;
    if (!preg_match('/^\d+(?:\.\d+)?$/D', $value) || !is_finite((float)$value) || (float)$value < $min || (float)$value > $max || ($integer && floor((float)$value) != (float)$value)) {
        throw new InvalidArgumentException('Controleer '.$key.': toegestaan '.$min.' t/m '.$max.($integer ? ' (heel getal).' : '.'));
    }
    return (float)$value;
}
function text_value(array $input, string $key, int $max = 5000): ?string {
    $value = scalar($input, $key);
    if (strlen($value) > $max) throw new InvalidArgumentException('Tekst is te lang (maximaal '.$max.' bytes).');
    return $value === '' ? null : $value;
}
function daily_fields(): array {
    return [
        'navel_cm'=>['Navelomtrek', 'cm', 20, 300, '0.1'],
        'hip_cm'=>['Heupomtrek', 'cm', 20, 300, '0.1'],
        'blood_pressure_sys'=>['Bovendruk', 'mmHg', 40, 300, '1'],
        'blood_pressure_dia'=>['Onderdruk', 'mmHg', 20, 200, '1'],
    ];
}
function daily_input(array $input): array {
    $data = ['date'=>valid_date(scalar($input,'date'))];
    foreach (daily_fields() as $key=>$field) $data[$key] = number_value($input,$key,$field[2],$field[3],$field[4]==='1');
    $data['sleep_quality'] = number_value($input,'sleep_quality',1,5,true);
    if (($data['blood_pressure_sys'] === null) !== ($data['blood_pressure_dia'] === null)) throw new InvalidArgumentException('Vul beide bloeddrukwaarden in, of laat beide leeg.');
    if ($data['blood_pressure_sys'] !== null && $data['blood_pressure_sys'] <= $data['blood_pressure_dia']) throw new InvalidArgumentException('Bovendruk moet hoger zijn dan onderdruk.');
    return $data;
}
function weekly_input(array $input): array {
    $data = ['week_start'=>monday(valid_date(scalar($input,'week_start')))];
    foreach (['libido','cravings','stress'] as $key) $data[$key] = number_value($input,$key,1,5,true);
    foreach (['bowel_movement','libido_details','stress_details'] as $key) $data[$key] = text_value($input,$key);
    return $data;
}
function display_number($n, int $decimals = 1): string { return $n === null ? '—' : number_format((float)$n,$decimals,',','.'); }
function sleep_label($minutes): string { return $minutes === null ? '—' : intdiv((int)round((float)$minutes),60).'u '.str_pad((string)((int)round((float)$minutes)%60),2,'0',STR_PAD_LEFT); }
function redirect(string $path): void { header('Location: '.url($path),true,303); exit; }
function url(string $path): string { return rtrim((string)parse_url(config('APP_URL'), PHP_URL_PATH),'/').'/'.ltrim($path,'/'); }
function flash(string $message): void { $_SESSION['flash'] = $message; }
