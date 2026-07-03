<?php
if (!isset($_GET['key']) || $_GET['key'] !== 'fprom_backup_2026_secret') { die('403'); }
$conn = new mysqli('localhost', 'p-329887_h-37688_fprom1', '5Ws!p3l6', 'p-329887_h-37688_fprom1');
$conn->set_charset('utf8mb4');
$r = $conn->query("SHOW CREATE TABLE ok_lang_pages");
if ($r) {
    $row = $r->fetch_row();
    echo $row[1] . "\n\n";
}
// Also get current meta for main page
$r2 = $conn->query("SELECT p.id, p.url, lp.name, lp.meta_title, lp.meta_description FROM ok_pages p JOIN ok_lang_pages lp ON p.id=lp.page_id WHERE p.id=1");
if ($r2) {
    $row2 = $r2->fetch_assoc();
    print_r($row2);
}
$conn->close();
