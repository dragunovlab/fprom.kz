<?php
$db = new mysqli('localhost', 'p-329887_h-37688_fprom1', '5Ws!p3l6', 'p-329887_h-37688_fprom1');
if ($db->connect_error) { die('Connect Error: '.$db->connect_error); }
$db->set_charset('utf8');

header('Content-Type: text/html; charset=utf-8');

$ids = [924, 994, 940, 111, 146, 917, 1091, 1092, 1095, 1100, 1082, 1083, 1090, 1123, 1133, 1186, 1008, 983, 999];
foreach ($ids as $id) {
    $r = $db->query("SELECT c.id, c.url, lc.name, lc.name_h1 FROM ok_categories c LEFT JOIN ok_lang_categories lc ON c.id=lc.category_id AND lc.lang_id=1 WHERE c.id=$id");
    $row = $r->fetch_assoc();
    if ($row) {
        echo "ID:{$row['id']} | URL:{$row['url']}<br>\n";
        echo "Name: " . htmlspecialchars($row['name']??'(null)') . "<br>\n";
        echo "H1: " . htmlspecialchars($row['name_h1']??'(null)') . "<br><br>\n";
    }
}
$db->close();
