<?php
/**
 * Generate SEO descriptions for all categories with empty description
 * Backup first, then update
 */

$db = new mysqli('localhost', 'p-329887_h-37688_fprom1', '5Ws!p3l6', 'p-329887_h-37688_fprom1');
if ($db->connect_error) { die('Connect Error: '.$db->connect_error); }
$db->set_charset('utf8');

$backup_dir = __DIR__ . '/backups';

// ====== 1. BACKUP ======
echo "=== BACKUP ===\n";
$bfile = "$backup_dir/categories_before_desc_" . date('Ymd_His') . ".sql";
$fp = fopen($bfile, 'w');
fwrite($fp, "-- Backup ok_lang_categories description fields\n");

$result = $db->query("SELECT lc.* FROM ok_lang_categories lc 
    JOIN ok_categories c ON lc.category_id = c.id 
    WHERE c.visible=1 AND lc.lang_id=1 AND (lc.description IS NULL OR lc.description = '')");
$count_backup = 0;
while ($row = $result->fetch_assoc()) {
    $e = function($v) use ($db) { return "'" . $db->real_escape_string($v ?? '') . "'"; };
    $keys = implode(", ", array_keys($row));
    $vals = implode(", ", array_map($e, array_values($row)));
    fwrite($fp, "INSERT INTO ok_lang_categories ($keys) VALUES ($vals);\n");
    $count_backup++;
}
fclose($fp);
echo "Backed up $count_backup empty descriptions to $bfile\n";

// ====== 2. GENERATE DESCRIPTIONS ======
echo "\n=== GENERATING DESCRIPTIONS ===\n";

$result = $db->query("
    SELECT c.id, c.parent_id, lc.name, lc.name_h1,
           (SELECT COUNT(*) FROM ok_products_categories pc WHERE pc.category_id = c.id) as pcount
    FROM ok_categories c
    LEFT JOIN ok_lang_categories lc ON c.id = lc.category_id AND lc.lang_id=1
    WHERE c.visible=1 AND (lc.description IS NULL OR lc.description = '')
    ORDER BY c.parent_id, c.position
");

$updated = 0;
$skipped = 0;
$errors = [];

while ($row = $result->fetch_assoc()) {
    $name = trim($row['name'] ?? '');
    $id = $row['id'];
    $pcount = $row['pcount'];
    
    if (empty($name)) { $skipped++; continue; }
    
    // Try to get parent category name
    $parent_name = '';
    if ($row['parent_id'] > 0) {
        $pr = $db->query("SELECT lc.name FROM ok_categories c 
            LEFT JOIN ok_lang_categories lc ON c.id = lc.category_id AND lc.lang_id=1 
            WHERE c.id = {$row['parent_id']}");
        if ($pr && $prow = $pr->fetch_assoc()) {
            $parent_name = trim($prow['name'] ?? '');
        }
    }
    
    // Clean up broken name - remove obvious duplicates within the name
    $clean_name = preg_replace('/(\w+)\s+\1/iu', '$1', $name);
    $clean_name = preg_replace('/\s*;\s*/u', ' — ', $clean_name);
    if (mb_strlen($clean_name) > 80) {
        $clean_name = mb_substr($clean_name, 0, 80);
        // Try to break at a word boundary
        $last_space = mb_strrpos($clean_name, ' ');
        if ($last_space > 20) $clean_name = mb_substr($clean_name, 0, $last_space);
    }
    
    // Generate description with variation
    $intros = [
        "<h2>Купить $clean_name в Казахстане</h2>",
        "<h2>$clean_name — продажа в Казахстане</h2>",
        "<h2>$clean_name с доставкой по Казахстану</h2>",
    ];
    $intro = $intros[$id % count($intros)];
    
    $desc = "$intro\n<p>";
    $desc .= "Компания <strong>Fortune PROM</strong> осуществляет продажу $clean_name в Казахстане";
    if ($pcount > 0) {
        $action = ($id % 2 == 0) ? "представлено" : "доступно";
        $desc .= ". В каталоге $action <strong>$pcount</strong> позиций";
    }
    if (!empty($parent_name)) {
        $desc .= " в разделе &laquo;$parent_name&raquo;";
    }
    $desc .= ".</p>\n";
    
    $outros = [
        "<p>Поставки промышленного оборудования по Алматы и всему Казахстану. Широкий ассортимент, конкурентные цены, гарантия качества. Для консультации: <strong>+7 (727) 384-43-13</strong>.</p>",
        "<p>Осуществляем доставку по Алматы и РК. Высокое качество, надёжность, выгодные цены. Звоните: <strong>+7 (727) 384-43-13</strong>.</p>",
        "<p>Продажа и доставка по Казахстану. Вся продукция сертифицирована. Подбор и консультация по телефону: <strong>+7 (727) 384-43-13</strong>.</p>",
    ];
    $desc .= $outros[$id % count($outros)];
    $desc .= "\n<p>&nbsp;</p>\n";
    
    // Update ok_lang_categories
    $stmt = $db->prepare("UPDATE ok_lang_categories SET description=? WHERE category_id=? AND lang_id=1");
    $stmt->bind_param('si', $desc, $id);
    if ($stmt->execute()) {
        $updated++;
    } else {
        $errors[] = "ID:$id - " . $db->error;
    }
    
    // Also update ok_categories
    $stmt = $db->prepare("UPDATE ok_categories SET description=? WHERE id=?");
    $stmt->bind_param('si', $desc, $id);
    $stmt->execute();
}

echo "Updated: $updated categories\n";
echo "Skipped: $skipped (empty name)\n";
if (!empty($errors)) {
    echo "Errors: " . count($errors) . "\n";
    foreach (array_slice($errors, 0, 5) as $e) echo "  $e\n";
}

$db->close();
echo "\n=== DONE ===\n";
