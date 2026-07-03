<?php
/**
 * Fix meta_descriptions for ALL pages with short/empty descriptions
 * - Categories with meta_description < 100 chars or empty
 * - Products with meta_description < 100 chars or empty  
 * - Static pages with meta_description < 100 chars or empty
 * 
 * Usage: ?key=fprom_backup_2026_secret&apply=1
 */

if (!isset($_GET['key']) || $_GET['key'] !== 'fprom_backup_2026_secret') { die('403'); }
header("Content-Type: text/plain; charset=utf-8");

$db = new mysqli('localhost', 'p-329887_h-37688_fprom1', '5Ws!p3l6', 'p-329887_h-37688_fprom1');
if ($db->connect_error) die('DB error: ' . $db->connect_error);
$db->set_charset('utf8');
$db->query("SET NAMES utf8");

$phone = '+7 (727) 384-43-13';
$apply = isset($_GET['apply']) && $_GET['apply'] == '1';
$backup_dir = __DIR__ . '/backups';
if (!is_dir($backup_dir)) mkdir($backup_dir, 0755, true);

// ===== 1. Backup function =====
function backup_table($db, $fp, $table, $where) {
    $r = $db->query("SELECT * FROM $table WHERE $where");
    $e = function($v) use ($db) { return "'" . $db->real_escape_string($v ?? '') . "'"; };
    $count = 0;
    while ($row = $r->fetch_assoc()) {
        $keys = implode(", ", array_keys($row));
        $vals = implode(", ", array_map($e, array_values($row)));
        fwrite($fp, "INSERT INTO $table ($keys) VALUES ($vals);\n");
        $count++;
    }
    return $count;
}

// ===== 2. CATEGORIES =====
echo "=== CATEGORIES ===\n";

// Count categories with short meta_description (<100 chars or empty)
$r = $db->query("SELECT COUNT(*) as cnt FROM ok_lang_categories lc 
    JOIN ok_categories c ON lc.category_id=c.id 
    WHERE lc.lang_id=1 AND c.visible=1 
    AND (lc.meta_description IS NULL OR lc.meta_description = '' OR LENGTH(lc.meta_description) < 100)");
$row = $r->fetch_assoc();
$cat_total = $row['cnt'];
echo "Categories to fix (empty or <100 chars): $cat_total\n";

// Get categories needing fix
$r = $db->query("SELECT c.id, c.url, lc.name, lc.meta_title, lc.meta_description, lc.description,
    (SELECT COUNT(*) FROM ok_products_categories pc WHERE pc.category_id = c.id) as pcount
    FROM ok_lang_categories lc 
    JOIN ok_categories c ON lc.category_id=c.id 
    WHERE lc.lang_id=1 AND c.visible=1 
    AND (lc.meta_description IS NULL OR lc.meta_description = '' OR LENGTH(lc.meta_description) < 100)
    ORDER BY c.parent_id, c.position");

$cat_fixes = [];
while ($row = $r->fetch_assoc()) {
    $name = trim($row['name'] ?? '');
    if (empty($name)) continue;
    $id = $row['id'];
    $pcount = $row['pcount'];
    $old_desc = $row['meta_description'] ?? '';
    
    // Patterns for descriptions - simple, clear, within 155-160 chars
    $patterns = [
        "Купить $name в Казахстане. Широкий ассортимент — $pcount позиций. Сертифицированное оборудование. Доставка по Алматы и РК. Звоните: $phone.",
        "$name от Fortune PROM в Казахстане. В наличии $pcount наименований. Высокое качество, конкурентные цены. Доставка по Алматы и Казахстану.",
        "Продажа $name в Казахстане. Каталог содержит $pcount товаров. Вся продукция сертифицирована. Работаем по Алматы и всему Казахстану. $phone.",
    ];
    
    $new_desc = $patterns[$id % count($patterns)];
    
    // Truncate to max 160 chars, break at word
    if (mb_strlen($new_desc) > 160) {
        $new_desc = mb_substr($new_desc, 0, 157);
        $last_space = mb_strrpos($new_desc, ' ');
        if ($last_space > 80) $new_desc = mb_substr($new_desc, 0, $last_space);
        $new_desc .= '...';
    }
    
    $cat_fixes[] = [$id, $name, $old_desc, $new_desc, $pcount];
}

echo "Categories to update: " . count($cat_fixes) . "\n";
foreach (array_slice($cat_fixes, 0, 10) as $f) {
    printf("  ID:%-4d | %-35s | new len:%d | pcount:%d\n", $f[0], mb_substr($f[1], 0, 35), mb_strlen($f[3]), $f[4]);
}
if (count($cat_fixes) > 10) echo "  ... and " . (count($cat_fixes) - 10) . " more\n";

// ===== 3. PRODUCTS =====
echo "\n=== PRODUCTS ===\n";

// Check if ok_lang_products exists
$has_products = false;
$r = $db->query("SHOW TABLES LIKE 'ok_lang_products'");
if ($r && $r->num_rows > 0) {
    $has_products = true;
    
    $r = $db->query("SELECT COUNT(*) as cnt FROM ok_lang_products lp 
        JOIN ok_products p ON lp.product_id=p.id 
        WHERE lp.lang_id=1 AND p.visible=1 
        AND (lp.meta_description IS NULL OR lp.meta_description = '' OR LENGTH(lp.meta_description) < 100)");
    $row = $r->fetch_assoc();
    $prod_total = $row['cnt'];
    echo "Products to fix (empty or <100 chars): $prod_total\n";
    
    if ($prod_total > 0) {
        $r = $db->query("SELECT p.id, p.url, lp.name, lp.meta_title, lp.meta_description
            FROM ok_lang_products lp 
            JOIN ok_products p ON lp.product_id=p.id 
            WHERE lp.lang_id=1 AND p.visible=1 
            AND (lp.meta_description IS NULL OR lp.meta_description = '' OR LENGTH(lp.meta_description) < 100)");
        
        $prod_fixes = [];
        while ($row = $r->fetch_assoc()) {
            $name = trim($row['name'] ?? '');
            if (empty($name)) continue;
            $id = $row['id'];
            
            $patterns = [
                "Купить $name в Казахстане. Низкие цены, гарантия, сертификаты. Доставка по Алматы и РК. Звоните: $phone.",
                "$name — продажа в Казахстане от Fortune PROM. Сертифицированное оборудование, гарантия. Доставка по Алматы и РК.",
                "$name с доставкой по Алматы и Казахстану. Высокое качество, конкурентные цены. Звоните: $phone.",
            ];
            
            $new_desc = $patterns[$id % count($patterns)];
            
            if (mb_strlen($new_desc) > 160) {
                $new_desc = mb_substr($new_desc, 0, 157);
                $last_space = mb_strrpos($new_desc, ' ');
                if ($last_space > 80) $new_desc = mb_substr($new_desc, 0, $last_space);
                $new_desc .= '...';
            }
            
            $prod_fixes[] = [$id, $name, $row['meta_description'] ?? '', $new_desc];
        }
        echo "Products to update: " . count($prod_fixes) . "\n";
    }
}

// ===== 4. STATIC PAGES =====
echo "\n=== STATIC PAGES ===\n";

$r = $db->query("SHOW TABLES LIKE 'ok_lang_pages'");
if ($r && $r->num_rows > 0) {
    $r = $db->query("SELECT lp.page_id as id, p.url, lp.name, lp.meta_title, lp.meta_description
        FROM ok_lang_pages lp 
        JOIN ok_pages p ON lp.page_id=p.id 
        WHERE lp.lang_id=1 
        AND (lp.meta_description IS NULL OR lp.meta_description = '' OR LENGTH(lp.meta_description) < 100)");
    
    $page_fixes = [];
    $page_templates = [
        'o-kompanii' => "Fortune PROM — поставщик промышленного оборудования в Казахстане. Более 10 лет на рынке. Широкий ассортимент, гарантия качества. Доставка по Алматы и РК. Звоните: $phone.",
        'oplata' => "Удобные способы оплаты для покупателей промышленного оборудования в Казахстане. Безналичный расчет, оплата по счету. Работаем с НДС. Звоните: $phone.",
        'dostavka' => "Доставка промышленного оборудования по Алматы и всему Казахстану. Собственная логистика, быстрая отправка. Гарантия сохранности груза. Звоните: $phone.",
        'sertifikaty' => "Сертификаты и разрешительная документация на промышленное оборудование Fortune PROM. Вся продукция сертифицирована в РК. Звоните: $phone.",
        'faq' => "Часто задаваемые вопросы о промышленном оборудовании. Консультации по подбору, доставке, оплате. Ответы на популярные вопросы. Звоните: $phone.",
        'contact' => "Контакты компании Fortune PROM в Алматы. Адрес, телефон, email. Доставка промышленного оборудования по Казахстану. Звоните: $phone.",
    ];
    
    while ($row = $r->fetch_assoc()) {
        $url = $row['url'];
        $name = trim($row['name'] ?? '');
        $id = $row['id'];
        
        if (isset($page_templates[$url])) {
            $new_desc = $page_templates[$url];
            $page_fixes[] = [$id, $url, $name, $row['meta_description'] ?? '', $new_desc];
        } elseif (!empty($name)) {
            $patterns = [
                "$name — Fortune PROM в Казахстане. Промышленное оборудование, сертификаты, гарантия. Доставка по Алматы и РК. $phone.",
                "$name от Fortune PROM. Широкий ассортимент промышленного оборудования. Доставка по Казахстану. $phone.",
            ];
            $new_desc = $patterns[$id % count($patterns)];
            $page_fixes[] = [$id, $url, $name, $row['meta_description'] ?? '', $new_desc];
        }
    }
    
    echo "Pages to update: " . count($page_fixes) . "\n";
    foreach ($page_fixes as $f) {
        printf("  ID:%-4d | %-25s | old:%d chars → new:%d chars\n", $f[0], $f[1], mb_strlen($f[3]), mb_strlen($f[4]));
    }
}

// ===== 5. APPLY =====
if ($apply) {
    echo "\n=== APPLYING ===\n";
    $bfile = "$backup_dir/all_meta_descriptions_" . date('Ymd_His') . ".sql";
    $fp = fopen($bfile, 'w');
    fwrite($fp, "-- Backup ALL meta_description fields BEFORE update\n");
    fwrite($fp, "-- " . date('Y-m-d H:i:s') . "\n\n");
    
    // Backup categories
    if (count($cat_fixes) > 0) {
        $ids = array_column($cat_fixes, 0);
        $b = backup_table($db, $fp, 'ok_lang_categories', 'lang_id=1 AND category_id IN (' . implode(',', $ids) . ')');
        echo "  Backed up $b category meta records\n";
    }
    
    // Update categories
    $stmt = $db->prepare("UPDATE ok_lang_categories SET meta_description=? WHERE category_id=? AND lang_id=1");
    $stmt2 = $db->prepare("UPDATE ok_categories SET meta_description=? WHERE id=?");
    $db->query("START TRANSACTION");
    $count = 0;
    foreach ($cat_fixes as $f) {
        $stmt->bind_param('si', $f[3], $f[0]);
        $stmt->execute();
        $stmt2->bind_param('si', $f[3], $f[0]);
        $stmt2->execute();
        $count++;
    }
    $db->query("COMMIT");
    echo "  Updated $count category meta_descriptions\n";
    
    // Update products
    if (isset($prod_fixes) && count($prod_fixes) > 0) {
        $ids = array_column($prod_fixes, 0);
        $b = backup_table($db, $fp, 'ok_lang_products', 'lang_id=1 AND product_id IN (' . implode(',', $ids) . ')');
        echo "  Backed up $b product meta records\n";
        
        $stmt = $db->prepare("UPDATE ok_lang_products SET meta_description=? WHERE product_id=? AND lang_id=1");
        $stmt3 = $db->prepare("UPDATE ok_products SET meta_description=? WHERE id=?");
        $db->query("START TRANSACTION");
        $count = 0;
        foreach ($prod_fixes as $f) {
            $stmt->bind_param('si', $f[3], $f[0]);
            $stmt->execute();
            $stmt3->bind_param('si', $f[3], $f[0]);
            $stmt3->execute();
            $count++;
        }
        $db->query("COMMIT");
        echo "  Updated $count product meta_descriptions\n";
    }
    
    // Update pages
    if (isset($page_fixes) && count($page_fixes) > 0) {
        $ids = array_column($page_fixes, 0);
        $b = backup_table($db, $fp, 'ok_lang_pages', 'lang_id=1 AND page_id IN (' . implode(',', $ids) . ')');
        echo "  Backed up $b page meta records\n";
        
        $stmt = $db->prepare("UPDATE ok_lang_pages SET meta_description=? WHERE page_id=? AND lang_id=1");
        $stmt4 = $db->prepare("UPDATE ok_pages SET meta_description=? WHERE id=?");
        $db->query("START TRANSACTION");
        $count = 0;
        foreach ($page_fixes as $f) {
            $stmt->bind_param('si', $f[4], $f[0]);
            $stmt->execute();
            $stmt4->bind_param('si', $f[4], $f[0]);
            $stmt4->execute();
            $count++;
        }
        $db->query("COMMIT");
        echo "  Updated $count page meta_descriptions\n";
    }
    
    fclose($fp);
    echo "Backup saved: $bfile\n";
} else {
    echo "\n=== DRY RUN — no changes ===\n";
    echo "Add &apply=1 to execute.\n";
}

$db->close();
echo "\n=== DONE ===\n";
