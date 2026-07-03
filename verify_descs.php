<?php
$db = new mysqli('localhost', 'p-329887_h-37688_fprom1', '5Ws!p3l6', 'p-329887_h-37688_fprom1');
if ($db->connect_error) { die('Connect Error: '.$db->connect_error); }
$db->set_charset('utf8');

header('Content-Type: text/plain; charset=utf-8');

// Stats
$r = $db->query("SELECT 
    SUM(CASE WHEN lc.description IS NULL OR lc.description='' THEN 1 ELSE 0 END) as empty_desc,
    SUM(CASE WHEN lc.description != '' AND lc.description IS NOT NULL THEN 1 ELSE 0 END) as filled_desc
FROM ok_lang_categories lc 
JOIN ok_categories c ON lc.category_id=c.id 
WHERE c.visible=1 AND lc.lang_id=1");
$row = $r->fetch_assoc();
echo "Empty descriptions: {$row['empty_desc']}\n";
echo "Filled descriptions: {$row['filled_desc']}\n\n";

// Sample generated descriptions
$r = $db->query("SELECT lc.category_id, c.url, lc.name, LEFT(lc.description, 300) as snippet
    FROM ok_lang_categories lc 
    JOIN ok_categories c ON lc.category_id=c.id 
    WHERE c.visible=1 AND lc.lang_id=1 AND lc.description != ''
    ORDER BY RAND() LIMIT 5");
while ($row = $r->fetch_assoc()) {
    echo "--- ID:{$row['category_id']} ({$row['url']}) ---\n";
    echo "Name: {$row['name']}\n";
    echo strip_tags($row['snippet']) . "...\n\n";
}

$db->close();
