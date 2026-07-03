<?php
if (!isset($_GET['key']) || $_GET['key'] !== 'fprom_backup_2026_secret') {
    header('HTTP/1.0 403 Forbidden');
    die('Access denied');
}

$conn = new mysqli('localhost', 'p-329887_h-37688_fprom1', '5Ws!p3l6', 'p-329887_h-37688_fprom1');
if ($conn->connect_error) { die("DB error"); }
$conn->set_charset('utf8mb4');

$results = [];

// 1. Update main page meta
$mainTitle = 'Промышленное оборудование — поставки в Казахстане | Fortune PROM';
$mainDesc = 'ТОО «Fortune PROM» — комплексные поставки промышленного оборудования по Казахстану. Насосы, дробилки, краны, редукторы, электрооборудование, буровая техника. Более 10 лет на рынке.';
$conn->query("UPDATE ok_pages SET meta_title='$mainTitle', meta_description='$mainDesc', meta_keywords='промышленное оборудование, поставки оборудования Казахстан, насосы, дробилки, краны, редукторы, Fortune PROM' WHERE id=1");
$results[] = "Main page meta updated";

// 2. Fix contact page duplicate
$conn->query("UPDATE ok_pages SET visible=0 WHERE url='kontakty2'");
$results[] = "kontakty2 hidden";

echo "OK:" . implode("|", $results);
$conn->close();
