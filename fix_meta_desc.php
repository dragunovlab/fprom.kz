<?php
$db = new mysqli('localhost', 'p-329887_h-37688_fprom1', '5Ws!p3l6', 'p-329887_h-37688_fprom1');
if ($db->connect_error) { die('Connect Error: '.$db->connect_error); }
$db->set_charset('utf8');

header('Content-Type: text/plain; charset=utf-8');

// Backup first
$bfile = __DIR__ . '/backups/meta_desc_backup_' . date('Ymd_His') . '.sql';
$fp = fopen($bfile, 'w');
$r = $db->query("SELECT lc.* FROM ok_lang_categories lc 
    JOIN ok_categories c ON lc.category_id = c.id 
    WHERE c.visible=1 AND lc.lang_id=1 AND (lc.meta_description IS NULL OR lc.meta_description = '')");
$count = 0;
while ($row = $r->fetch_assoc()) {
    $e = function($v) use ($db) { return "'" . $db->real_escape_string($v ?? '') . "'"; };
    $keys = implode(", ", array_keys($row));
    $vals = implode(", ", array_map($e, array_values($row)));
    fwrite($fp, "INSERT INTO ok_lang_categories ($keys) VALUES ($vals);\n");
    $count++;
}
fclose($fp);
echo "Backup: $count rows to $bfile\n";

// Generate meta_descriptions
$r = $db->query("SELECT c.id, c.url, lc.name, lc.meta_title, 
    (SELECT COUNT(*) FROM ok_products_categories pc WHERE pc.category_id = c.id) as pcount
    FROM ok_categories c
    JOIN ok_lang_categories lc ON c.id = lc.category_id AND lc.lang_id=1
    WHERE c.visible=1 AND (lc.meta_description IS NULL OR lc.meta_description = '')");

$updated = 0;
while ($row = $r->fetch_assoc()) {
    $name = trim($row['name'] ?? '');
    $pcount = $row['pcount'];
    $id = $row['id'];
    if (empty($name)) continue;
    
    // Generate meta_description
    $patterns = [
        "Купить $name в Казахстане. $pcount позиций в каталоге. Доставка по Алматы и РК. Звоните: +7 (727) 384-43-13.",
        "$name в Казахстане от Fortune PROM. $pcount наименований. Высокое качество, конкурентные цены. Доставка по Алматы и РК.",
        "Продажа $name в Казахстане. Широкий ассортимент — $pcount позиций. Сертифицированное оборудование. Доставка по Алматы и Казахстану.",
    ];
    $meta = $patterns[$id % count($patterns)];
    
    // Truncate to ~155 chars
    if (mb_strlen($meta) > 155) {
        $meta = mb_substr($meta, 0, 152) . '...';
    }
    
    // Update
    $stmt = $db->prepare("UPDATE ok_lang_categories SET meta_description=? WHERE category_id=? AND lang_id=1");
    $stmt->bind_param('si', $meta, $id);
    $stmt->execute();
    
    $stmt = $db->prepare("UPDATE ok_categories SET meta_description=? WHERE id=?");
    $stmt->bind_param('si', $meta, $id);
    $stmt->execute();
    
    $updated++;
}

echo "Updated meta_descriptions: $updated\n";

// Verify
$r = $db->query("SELECT COUNT(*) as cnt FROM ok_lang_categories lc 
    JOIN ok_categories c ON lc.category_id=c.id 
    WHERE c.visible=1 AND lc.lang_id=1 AND (lc.meta_description IS NULL OR lc.meta_description='')");
$row = $r->fetch_assoc();
echo "Still empty: {$row['cnt']}\n";

$db->close();
echo "=== DONE ===\n";
