<?php
require __DIR__.'/../../../includes/mobile_bootstrap.php';
$token=mobile_token();mobile_device($token);$data=mobile_body();$action=scalar($data,'action');

function nutrition_number(array $data,string $key,float $max): float {
    $value=$data[$key]??null;
    if((!is_int($value)&&!is_float($value))||!is_finite((float)$value)||(float)$value<0||(float)$value>$max) throw new InvalidArgumentException('Ongeldige voedingswaarde.');
    return round((float)$value,2);
}
function nutrition_id(array $data,string $key): int {
    $value=$data[$key]??null;
    if(!is_int($value)||$value<1) throw new InvalidArgumentException('Ongeldige identifier.');
    return $value;
}
function nutrition_product(array $row): array {
    return ['id'=>(int)$row['id'],'barcode'=>$row['barcode'],'name'=>$row['name'],'brand'=>$row['brand'],'calories_per_100g'=>(float)$row['calories_per_100g'],'protein_per_100g'=>(float)$row['protein_per_100g'],'carbohydrates_per_100g'=>(float)$row['carbohydrates_per_100g'],'fat_per_100g'=>(float)$row['fat_per_100g']];
}
function nutrition_open_food_facts(string $barcode): ?array {
    if(!function_exists('curl_init')) return null;
    $url='https://world.openfoodfacts.org/api/v2/product/'.rawurlencode($barcode).'.json?fields=product_name,brands,nutriments';
    $curl=curl_init($url);
    curl_setopt_array($curl,[CURLOPT_RETURNTRANSFER=>true,CURLOPT_FOLLOWLOCATION=>false,CURLOPT_CONNECTTIMEOUT=>4,CURLOPT_TIMEOUT=>8,CURLOPT_PROTOCOLS=>CURLPROTO_HTTPS,CURLOPT_HTTPHEADER=>['Accept: application/json','User-Agent: Ritme/1.1.2 (self-hosted nutrition tracker)'],CURLOPT_SSL_VERIFYPEER=>true,CURLOPT_SSL_VERIFYHOST=>2]);
    $body=curl_exec($curl);$status=(int)curl_getinfo($curl,CURLINFO_HTTP_CODE);curl_close($curl);
    if($body===false||$status!==200) return null;
    try{$data=json_decode($body,true,128,JSON_THROW_ON_ERROR);}catch(JsonException){return null;}
    $product=$data['product']??null;$nutriments=is_array($product)?($product['nutriments']??null):null;
    if(($data['status']??0)!==1||!is_array($product)||!is_array($nutriments)) return null;
    $name=trim((string)($product['product_name']??''));if($name==='') return null;
    $keys=['energy-kcal_100g','proteins_100g','carbohydrates_100g','fat_100g'];
    foreach($keys as $key) if(!isset($nutriments[$key])||!is_numeric($nutriments[$key])||!is_finite((float)$nutriments[$key])||(float)$nutriments[$key]<0) return null;
    return ['barcode'=>$barcode,'name'=>mb_substr($name,0,160),'brand'=>mb_substr(trim((string)($product['brands']??'')),0,120),'calories_per_100g'=>min(1000,round((float)$nutriments['energy-kcal_100g'],2)),'protein_per_100g'=>min(100,round((float)$nutriments['proteins_100g'],2)),'carbohydrates_per_100g'=>min(100,round((float)$nutriments['carbohydrates_100g'],2)),'fat_per_100g'=>min(100,round((float)$nutriments['fat_100g'],2))];
}
function nutrition_recalculate(string $date): void {
    $sum=query('SELECT SUM(calories_kcal) calories_kcal,SUM(protein_g) protein_g,SUM(carbohydrates_g) carbohydrates_g,SUM(fat_g) fat_g,COUNT(*) amount FROM food_diary_entries WHERE date=?',[$date])->fetch();
    if(!(int)$sum['amount']){query('DELETE FROM nutrition_daily WHERE date=?',[$date]);return;}
    upsert('nutrition_daily',['date'=>$date,'calories_kcal'=>round((float)$sum['calories_kcal'],2),'protein_g'=>round((float)$sum['protein_g'],2),'carbohydrates_g'=>round((float)$sum['carbohydrates_g'],2),'fat_g'=>round((float)$sum['fat_g'],2)]);
}
function nutrition_day(string $date): array {
    $entries=query('SELECT id,meal,product_name,brand,amount_g,calories_kcal,protein_g,carbohydrates_g,fat_g FROM food_diary_entries WHERE date=? ORDER BY FIELD(meal,"breakfast","lunch","dinner","other"),id',[$date])->fetchAll();
    $total=query('SELECT calories_kcal,protein_g,carbohydrates_g,fat_g FROM nutrition_daily WHERE date=?',[$date])->fetch()?:['calories_kcal'=>null,'protein_g'=>null,'carbohydrates_g'=>null,'fat_g'=>null];
    foreach($entries as &$entry){$entry['id']=(int)$entry['id'];foreach(['amount_g','calories_kcal','protein_g','carbohydrates_g','fat_g'] as $key)$entry[$key]=(float)$entry[$key];}unset($entry);
    foreach($total as $key=>$value)$total[$key]=$value===null?null:(float)$value;
    return ['ok'=>true,'date'=>$date,'totals'=>$total,'entries'=>$entries];
}

try {
    if($action==='day') mobile_json(nutrition_day(valid_date(scalar($data,'date'))));
    if($action==='find_barcode'){
        $barcode=scalar($data,'barcode');if(!preg_match('/^[0-9]{6,32}$/D',$barcode))throw new InvalidArgumentException('Ongeldige barcode.');
        $row=query('SELECT * FROM food_products WHERE barcode=?',[$barcode])->fetch();
        if(!$row){$external=nutrition_open_food_facts($barcode);if($external){$row=mobile_transaction(function()use($external,$barcode){query('INSERT INTO food_products(barcode,name,brand,calories_per_100g,protein_per_100g,carbohydrates_per_100g,fat_per_100g) VALUES (?,?,?,?,?,?,?) ON DUPLICATE KEY UPDATE id=LAST_INSERT_ID(id)',array_values($external));return query('SELECT * FROM food_products WHERE barcode=?',[$barcode])->fetch();});}}
        mobile_json(['ok'=>true,'product'=>$row?nutrition_product($row):null]);
    }
    if($action==='search'){
        $term=scalar($data,'query');if(strlen($term)<2||strlen($term)>100)throw new InvalidArgumentException('Gebruik minimaal twee zoektekens.');
        $rows=query('SELECT * FROM food_products WHERE name LIKE ? OR brand LIKE ? ORDER BY name LIMIT 30',['%'.$term.'%','%'.$term.'%'])->fetchAll();mobile_json(['ok'=>true,'products'=>array_map('nutrition_product',$rows)]);
    }
    if($action==='create_product'){
        $barcode=scalar($data,'barcode');if($barcode!==''&&!preg_match('/^[0-9]{6,32}$/D',$barcode))throw new InvalidArgumentException('Ongeldige barcode.');
        $name=text_value($data,'name',160);$brand=text_value($data,'brand',120);if(!$name)throw new InvalidArgumentException('Productnaam ontbreekt.');
        $product=['barcode'=>$barcode===''?null:$barcode,'name'=>$name,'brand'=>$brand,'calories_per_100g'=>nutrition_number($data,'calories_per_100g',1000),'protein_per_100g'=>nutrition_number($data,'protein_per_100g',100),'carbohydrates_per_100g'=>nutrition_number($data,'carbohydrates_per_100g',100),'fat_per_100g'=>nutrition_number($data,'fat_per_100g',100)];
        $result=mobile_transaction(function()use($product){query('INSERT INTO food_products(barcode,name,brand,calories_per_100g,protein_per_100g,carbohydrates_per_100g,fat_per_100g) VALUES (?,?,?,?,?,?,?)',array_values($product));$row=query('SELECT * FROM food_products WHERE id=?',[db()->lastInsertId()])->fetch();return nutrition_product($row);});
        mobile_json(['ok'=>true,'product'=>$result]);
    }
    if($action==='add_entry'){
        $date=valid_date(scalar($data,'date'));$id=nutrition_id($data,'product_id');$grams=nutrition_number($data,'amount_g',10000);if($grams<=0)throw new InvalidArgumentException('Hoeveelheid moet groter zijn dan nul.');
        $meal=scalar($data,'meal');if(!in_array($meal,['breakfast','lunch','dinner','other'],true))throw new InvalidArgumentException('Ongeldige maaltijd.');
        $result=mobile_transaction(function()use($date,$id,$grams,$meal){$p=query('SELECT * FROM food_products WHERE id=?',[$id])->fetch();if(!$p)throw new InvalidArgumentException('Product niet gevonden.');$factor=$grams/100;query('INSERT INTO food_diary_entries(date,meal,product_id,amount_g,product_name,brand,calories_kcal,protein_g,carbohydrates_g,fat_g) VALUES (?,?,?,?,?,?,?,?,?,?)',[$date,$meal,$id,$grams,$p['name'],$p['brand'],round((float)$p['calories_per_100g']*$factor,2),round((float)$p['protein_per_100g']*$factor,2),round((float)$p['carbohydrates_per_100g']*$factor,2),round((float)$p['fat_per_100g']*$factor,2)]);nutrition_recalculate($date);return nutrition_day($date);});
        mobile_json($result);
    }
    if($action==='delete_entry'){
        $date=valid_date(scalar($data,'date'));$id=nutrition_id($data,'entry_id');
        $result=mobile_transaction(function()use($date,$id){query('DELETE FROM food_diary_entries WHERE id=? AND date=?',[$id,$date]);nutrition_recalculate($date);return nutrition_day($date);});mobile_json($result);
    }
    throw new InvalidArgumentException('Onbekende actie.');
} catch(InvalidArgumentException $e){mobile_json(['error'=>'validation','message'=>$e->getMessage()],422);} catch(PDOException $e){if(($e->errorInfo[1]??0)===1062)mobile_json(['error'=>'duplicate','message'=>'Deze barcode bestaat al.'],409);throw $e;}
