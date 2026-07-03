<?php
/**
 * Fix short title tags (<20 chars) for all page types
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

// ===== 1. CATEGORIES =====
echo "=== CATEGORIES ===\n";

$r = $db->query("SELECT COUNT(*) as cnt FROM ok_lang_categories lc 
    JOIN ok_categories c ON lc.category_id=c.id 
    WHERE lc.lang_id=1 AND c.visible=1 
    AND (lc.meta_title IS NULL OR lc.meta_title = '' OR LENGTH(lc.meta_title) < 20)");
$row = $r->fetch_assoc();
$cat_total = $row['cnt'];
echo "Categories with short/empty title (<20 chars): $cat_total\n";

$r = $db->query("SELECT c.id, c.url, lc.name, lc.meta_title, lc.meta_description
    FROM ok_lang_categories lc 
    JOIN ok_categories c ON lc.category_id=c.id 
    WHERE lc.lang_id=1 AND c.visible=1 
    AND (lc.meta_title IS NULL OR lc.meta_title = '' OR LENGTH(lc.meta_title) < 20)
    ORDER BY c.parent_id, c.position");

$cat_fixes = [];
while ($row = $r->fetch_assoc()) {
    $name = trim($row['name'] ?? '');
    if (empty($name)) continue;
    $id = $row['id'];
    $old = $row['meta_title'] ?? '';
    
    $patterns = [
        "Купить $name в Казахстане — продажа с доставкой | Fortune PROM",
        "$name в Казахстане — продажа от Fortune PROM",
        "$name — каталог, цены, продажа в Казахстане",
    ];
    $new = $patterns[$id % count($patterns)];
    
    if (mb_strlen($new) > 70) {
        $new = mb_substr($new, 0, 67);
        $last_space = mb_strrpos($new, ' ');
        if ($last_space > 30) $new = mb_substr($new, 0, $last_space);
    }
    
    $cat_fixes[] = [$id, $name, $old, $new];
}

echo "Categories to update: " . count($cat_fixes) . "\n";
foreach (array_slice($cat_fixes, 0, 10) as $f) {
    printf("  ID:%-4d | old:'%s' | new:'%s'\n", $f[0], mb_substr($f[2], 0, 20), mb_substr($f[3], 0, 60));
}

// ===== 2. PRODUCTS =====
echo "\n=== PRODUCTS ===\n";

$r = $db->query("SHOW TABLES LIKE 'ok_lang_products'");
$has_products = ($r && $r->num_rows > 0);

if ($has_products) {
    $r = $db->query("SELECT COUNT(*) as cnt FROM ok_lang_products lp 
        JOIN ok_products p ON lp.product_id=p.id 
        WHERE lp.lang_id=1 AND p.visible=1 
        AND (lp.meta_title IS NULL OR lp.meta_title = '' OR LENGTH(lp.meta_title) < 20)");
    $row = $r->fetch_assoc();
    $prod_total = $row['cnt'];
    echo "Products with short/empty title: $prod_total\n";
    
    if ($prod_total > 0) {
        $r = $db->query("SELECT p.id, p.url, lp.name, lp.meta_title
            FROM ok_lang_products lp 
            JOIN ok_products p ON lp.product_id=p.id 
            WHERE lp.lang_id=1 AND p.visible=1 
            AND (lp.meta_title IS NULL OR lp.meta_title = '' OR LENGTH(lp.meta_title) < 20)");
        
        $prod_fixes = [];
        while ($row = $r->fetch_assoc()) {
            $name = trim($row['name'] ?? '');
            if (empty($name)) continue;
            $id = $row['id'];
            $old = $row['meta_title'] ?? '';
            
            $patterns = [
                "$name — купить в Казахстане | Fortune PROM",
                "$name — продажа в Казахстане | Fortune PROM",
                "$name — цена, купить в Казахстане",
            ];
            $new = $patterns[$id % count($patterns)];
            
            if (mb_strlen($new) > 70) {
                $new = mb_substr($new, 0, 67);
                $last_space = mb_strrpos($new, ' ');
                if ($last_space > 30) $new = mb_substr($new, 0, $last_space);
            }
            
            $prod_fixes[] = [$id, $name, $old, $new];
        }
        echo "Products to update: " . count($prod_fixes) . "\n";
    }
}

// ===== 3. STATIC PAGES =====
echo "\n=== STATIC PAGES ===\n";

$r = $db->query("SHOW TABLES LIKE 'ok_lang_pages'");
if ($r && $r->num_rows > 0) {
    $r = $db->query("SELECT lp.page_id as id, p.url, lp.name, lp.meta_title, lp.meta_description
        FROM ok_lang_pages lp 
        JOIN ok_pages p ON lp.page_id=p.id 
        WHERE lp.lang_id=1 
        AND (lp.meta_title IS NULL OR lp.meta_title = '' OR LENGTH(lp.meta_title) < 20)");
    
    $page_fixes = [];
    $page_templates = [
        'o-kompanii' => "О компании — Fortune PROM | Промышленное оборудование в Казахстане",
        'oplata' => "Оплата — Fortune PROM | Способы оплаты промышленного оборудования",
        'dostavka' => "Доставка — Fortune PROM | Доставка оборудования по Казахстану",
        'sertifikaty' => "Сертификаты — Fortune PROM | Сертификаты на промышленное оборудование",
        'faq' => "FAQ — Fortune PROM | Часто задаваемые вопросы",
        'contact' => "Контакты — Fortune PROM | Адрес, телефон в Алматы",
    ];
    
    while ($row = $r->fetch_assoc()) {
        $url = $row['url'];
        $name = trim($row['name'] ?? '');
        $id = $row['id'];
        $old = $row['meta_title'] ?? '';
        
        if (isset($page_templates[$url])) {
            $new = $page_templates[$url];
        } elseif (!empty($name)) {
            $patterns = [
                "$name | Fortune PROM — промышленное оборудование в Казахстане",
                "$name — Fortune PROM | Продажа промышленного оборудования",
            ];
            $new = $patterns[$id % count($patterns)];
        } else {
            continue;
        }
        
        $page_fixes[] = [$id, $url, $old, $new];
    }
    
    echo "Pages to update: " . count($page_fixes) . "\n";
    foreach ($page_fixes as $f) {
        printf("  %-25s | old:'%s' | new:'%s'\n", $f[1], mb_substr($f[2], 0, 20), $f[3]);
    }
}

// ===== APPLY =====
if ($apply) {
    echo "\n=== APPLYING ===\n";
    $bfile = "$backup_dir/all_meta_titles_" . date('Ymd_His') . ".sql";
    $fp = fopen($bfile, 'w');
    fwrite($fp, "-- Backup meta_title fields BEFORE update\n");
    fwrite($fp, "-- " . date('Y-m-d H:i:s') . "\n\n");
    
    $total = 0;
    
    if (count($cat_fixes) > 0) {
        $ids = array_column($cat_fixes, 0);
        $b = backup_table($db, $fp, 'ok_lang_categories', 'lang_id=1 AND category_id IN (' . implode(',', $ids) . ')');
        echo "  Backed up $b category records\n";
        
        $stmt = $db->prepare("UPDATE ok_lang_categories SET meta_title=? WHERE category_id=? AND lang_id=1");
        $db->query("START TRANSACTION");
        foreach ($cat_fixes as $f) {
            $stmt->bind_param('si', $f[3], $f[0]);
            $stmt->execute();
            $total++;
        }
        $db->query("COMMIT");
        echo "  Updated " . count($cat_fixes) . " category titles\n";
    }
    
    if (isset($prod_fixes) && count($prod_fixes) > 0) {
        $ids = array_column($prod_fixes, 0);
        $b = backup_table($db, $fp, 'ok_lang_products', 'lang_id=1 AND product_id IN (' . implode(',', $ids) . ')');
        echo "  Backed up $b product records\n";
        
        $stmt = $db->prepare("UPDATE ok_lang_products SET meta_title=? WHERE product_id=? AND lang_id=1");
        $db->query("START TRANSACTION");
        foreach ($prod_fixes as $f) {
            $stmt->bind_param('si', $f[3], $f[0]);
            $stmt->execute();
            $total++;
        }
        $db->query("COMMIT");
        echo "  Updated " . count($prod_fixes) . " product titles\n";
    }
    
    if (isset($page_fixes) && count($page_fixes) > 0) {
        $ids = array_column($page_fixes, 0);
        $b = backup_table($db, $fp, 'ok_lang_pages', 'lang_id=1 AND page_id IN (' . implode(',', $ids) . ')');
        echo "  Backed up $b page records\n";
        
        $stmt = $db->prepare("UPDATE ok_lang_pages SET meta_title=? WHERE page_id=? AND lang_id=1");
        $db->query("START TRANSACTION");
        foreach ($page_fixes as $f) {
            $stmt->bind_param('si', $f[3], $f[0]);
            $stmt->execute();
            $total++;
        }
        $db->query("COMMIT");
        echo "  Updated " . count($page_fixes) . " page titles\n";
    }
    
    fclose($fp);
    echo "\nTotal updated: $total\n";
    echo "Backup: $bfile\n";
} else {
    echo "\n=== DRY RUN ===\nAdd &apply=1 to execute.\n";
}

$db->close();
echo "\n=== DONE ===\n";
