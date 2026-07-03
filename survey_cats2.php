<?php
$db = new mysqli('localhost', 'p-329887_h-37688_fprom1', '5Ws!p3l6', 'p-329887_h-37688_fprom1');
if ($db->connect_error) { die('Connect Error: '.$db->connect_error); }
$db->set_charset('utf8');

$broken = [94,1181,1169,975,1266,136,924,940,111,146,917,919,922,923,967,989,946,996,1007,1003,1006,1091,1092,1095,118,128,151,920,938,953,943,964,1016,994,934,133,944,947,126,107,1100,1082,1083,1090,1186,1008,1134,1135,1015,1123,1133,983,999,1239];

foreach ($broken as $id) {
    $r = $db->query("
        SELECT c.id, c.name as c_name, c.url, lc.name as lc_name, lc.name_h1,
               (SELECT COUNT(*) FROM ok_products_categories pc WHERE pc.category_id = c.id) as pcount
        FROM ok_categories c 
        LEFT JOIN ok_lang_categories lc ON c.id = lc.category_id AND lc.lang_id=1
        WHERE c.id = $id
    ");
    $row = $r->fetch_assoc();
    $name = $row['lc_name'] ?: $row['c_name'];
    printf("ID:%-4d | URL:%-30s | Name: %s | Products:%s\n", 
        $row['id'], $row['url'], mb_substr($name, 0, 60), $row['pcount']);
}

$db->close();
