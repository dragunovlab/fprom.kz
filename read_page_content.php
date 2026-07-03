<?php
$db = new mysqli('localhost', 'p-329887_h-37688_fprom1', '5Ws!p3l6', 'p-329887_h-37688_fprom1');
if ($db->connect_error) { die('Connect Error: '.$db->connect_error); }
$db->set_charset('utf8');

// Get o-kompanii lang body
$result = $db->query("SELECT page_id, description FROM ok_lang_pages WHERE page_id=22 AND lang_id=1");
$row = $result->fetch_assoc();
echo "=== OK_LANG_PAGES page_id=22 ===\n";
echo $row['description'];

echo "\n=== END ===\n";

$db->close();
