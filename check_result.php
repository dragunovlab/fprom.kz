<?php
$db = new mysqli('localhost', 'p-329887_h-37688_fprom1', '5Ws!p3l6', 'p-329887_h-37688_fprom1');
if ($db->connect_error) { die('Connect Error: '.$db->connect_error); }
$db->set_charset('utf8');

header('Content-Type: text/plain; charset=utf-8');

// Count still empty
$r = $db->query("SELECT COUNT(*) as cnt FROM ok_lang_categories lc 
    JOIN ok_categories c ON lc.category_id=c.id 
    WHERE c.visible=1 AND lc.lang_id=1 AND (lc.description IS NULL OR lc.description='')");
$row = $r->fetch_assoc();
echo "Still empty descriptions: {$row['cnt']}\n";

$r = $db->query("SELECT COUNT(*) as cnt FROM ok_lang_categories lc 
    JOIN ok_categories c ON lc.category_id=c.id 
    WHERE c.visible=1 AND lc.lang_id=1 AND lc.description != '' AND lc.description IS NOT NULL");
$row = $r->fetch_assoc();
echo "With descriptions: {$row['cnt']}\n";

// Sample
$r = $db->query("SELECT lc.category_id, c.url, lc.name, SUBSTRING(lc.description, 1, 200) as desc_start 
    FROM ok_lang_categories lc 
    JOIN ok_categories c ON lc.category_id=c.id 
    WHERE c.visible=1 AND lc.lang_id=1 AND lc.description != ''
    LIMIT 3");
while ($row = $r->fetch_assoc()) {
    echo "\nID:{$row['category_id']} {$row['url']}\n";
    echo "Name: {$row['name']}\n";
    echo strip_tags($row['desc_start']) . "...\n";
}

$db->close();
