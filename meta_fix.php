<?php
if (!isset($_GET['key']) || $_GET['key'] !== 'fprom_backup_2026_secret') { die('403'); }
$conn = new mysqli('localhost', 'p-329887_h-37688_fprom1', '5Ws!p3l6', 'p-329887_h-37688_fprom1');
$conn->set_charset('utf8mb4');

$title = 'Промышленное оборудование в Казахстане — поставки от Fortune PROM';
$desc = 'ТОО «Fortune PROM» — комплексные поставки промышленного оборудования по Казахстану и СНГ. Насосы, дробилки, краны, редукторы, электрооборудование. Более 10 лет на рынке.';

$stmt = $conn->prepare("UPDATE ok_lang_pages SET meta_title=?, meta_description=? WHERE lang_id=1 AND page_id=1");
$stmt->bind_param('ss', $title, $desc);
$stmt->execute();
echo 'OK:' . $stmt->affected_rows;
$stmt->close();
$conn->close();
