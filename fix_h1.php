<?php
/**
 * Fix H1 issues: missing H1, duplicate H1
 * Usage: ?key=fprom_backup_2026_secret&apply=1
 */

if (!isset($_GET['key']) || $_GET['key'] !== 'fprom_backup_2026_secret') { die('403'); }
header("Content-Type: text/plain; charset=utf-8");

$db = new mysqli('localhost', 'p-329887_h-37688_fprom1', '5Ws!p3l6', 'p-329887_h-37688_fprom1');
if ($db->connect_error) die('DB error: ' . $db->connect_error);
$db->set_charset('utf8');
$db->query("SET NAMES utf8");

$apply = isset($_GET['apply']) && $_GET['apply'] == '1';
$backup_dir = __DIR__ . '/backups';
if (!is_dir($backup_dir)) mkdir($backup_dir, 0755, true);

function backup_table($db, $fp, $table, $where) {
    $r = $db->query("SELECT * FROM $table WHERE $where");
    $e = function($v) use ($db) { return "'" . $db->real_escape_string($v ?? '') . "'"; };
    $c = 0;
    while ($row = $r->fetch_assoc()) {
        fwrite($fp, "INSERT INTO $table VALUES (" . implode(",", array_map($e, array_values($row))) . ");\n");
        $c++;
    }
    return $c;
}

// Get parent name for context
function getParentName($db, $parent_id) {
    if ($parent_id <= 0) return '';
    $r = $db->query("SELECT lc.name FROM ok_categories c JOIN ok_lang_categories lc ON c.id=lc.category_id WHERE c.id=$parent_id AND lc.lang_id=1");
    if ($r && $row = $r->fetch_assoc()) return trim($row['name']);
    return '';
}

// ===== 1. CATEGORIES — check name_h1 =====
echo "=== CATEGORY H1 ==> name_h1 FIELD ===\n";
$r = $db->query("SELECT COUNT(*) as c FROM ok_lang_categories WHERE lang_id=1 AND (name_h1 IS NULL OR name_h1 = '')");
$row = $r->fetch_assoc();
$empty_h1 = $row['c'];
echo "Categories with empty name_h1: $empty_h1\n";

// For the duplicate H1 issue, let's see which categories have the same name
$r = $db->query("SELECT c.id, c.url, c.parent_id, lc.name, lc.name_h1
    FROM ok_lang_categories lc 
    JOIN ok_categories c ON lc.category_id=c.id 
    WHERE lc.lang_id=1 AND c.visible=1
    ORDER BY lc.name, c.parent_id, c.position");

$cat_names = [];
$fixes = [];
while ($row = $r->fetch_assoc()) {
    $id = $row['id'];
    $name = trim($row['name'] ?? '');
    $name_h1 = trim($row['name_h1'] ?? '');
    $url = $row['url'];
    $parent_id = $row['parent_id'];
    
    if (empty($name)) continue;
    
    $cat_names[$id] = ['name' => $name, 'name_h1' => $name_h1, 'url' => $url, 'parent_id' => $parent_id];
}

// Find duplicates by name
$name_groups = [];
foreach ($cat_names as $id => $info) {
    $name_groups[$info['name']][] = $id;
}

echo "\nCategories with duplicate names (same name, different IDs):\n";
$dup_count = 0;
foreach ($name_groups as $name => $ids) {
    if (count($ids) > 1) {
        $dup_count++;
        echo "  '$name' — " . count($ids) . "x: IDs " . implode(', ', $ids) . "\n";
        
        // For each duplicate, generate a unique H1
        foreach ($ids as $i => $id) {
            $info = $cat_names[$id];
            $existing_h1 = $info['name_h1'];
            $url = $info['url'];
            $parent_name = getParentName($db, $info['parent_id']);
            
            // Current H1 (what's actually shown)
            $current_h1 = !empty($existing_h1) ? $existing_h1 : $name;
            
            // Generate unique H1
            if (!empty($parent_name)) {
                $new_h1 = "$name — $parent_name";
            } else {
                $new_h1 = $name;
            }
            
            // Don't fix if H1 already unique (has name_h1 set different from name)
            if ($current_h1 !== $name) {
                echo "    ID $id: already has custom name_h1, skipping\n";
                continue;
            }
            
            if ($new_h1 !== $current_h1) {
                $fixes[] = [$id, 'category', $current_h1, $new_h1, $url];
                echo "    ID $id ($url): '$current_h1' → '$new_h1'\n";
            }
        }
    }
}

echo "\n=== MISSING H1 PAGES ===\n";
// These are the pages with no H1 at all — check each type
$missing_h1_urls = [
    'dizelnaya-portovaya-tehnika',
    'ekskavatory-pogruzchiki',
    'dizelnye-pogruzchiki',
    'pogruzchiki-serii-zl',
    'pogruzchiki-serii-xc',
    'vilochnye-pogruzchiki-xcmg',
    'pogruzchiki-serii-lw',
    'vilochnye-pogruzchiki-hangcha',
];

foreach ($missing_h1_urls as $url) {
    $esc = $db->real_escape_string($url);
    $r = $db->query("SELECT c.id, c.url, c.parent_id, lc.name, lc.name_h1 
        FROM ok_categories c 
        LEFT JOIN ok_lang_categories lc ON c.id=lc.category_id AND lc.lang_id=1 
        WHERE c.url='$esc'");
    if ($rw = $r->fetch_assoc()) {
        $name = trim($rw['name'] ?? '');
        $name_h1 = trim($rw['name_h1'] ?? '');
        printf("  CATEGORY ID %d '%s': name='%s', name_h1='%s'\n", $rw['id'], $url, $name, $name_h1);
        if (empty($name_h1) && !empty($name)) {
            $fixes[] = [$rw['id'], 'category', '', $name, $url];
            echo "    → will set name_h1 to '$name'\n";
        }
    } else {
        // Check if it's a product
        $r2 = $db->query("SELECT p.id, p.url, lp.name, lp.meta_title 
            FROM ok_products p 
            LEFT JOIN ok_lang_products lp ON p.id=lp.product_id AND lp.lang_id=1 
            WHERE p.url='$esc'");
        if ($rw2 = $r2->fetch_assoc()) {
            printf("  PRODUCT ID %d '%s': name='%s'\n", $rw2['id'], $url, $rw2['name'] ?? '');
            // Products in OKay CMS might not use name_h1 — the H1 comes from template
            echo "    → product page, H1 source unknown\n";
        } else {
            // Check static pages
            $r3 = $db->query("SELECT p.id, p.url, lp.name FROM ok_pages p 
                LEFT JOIN ok_lang_pages lp ON p.id=lp.page_id AND lp.lang_id=1 
                WHERE p.url='$esc'");
            if ($rw3 = $r3->fetch_assoc()) {
                printf("  PAGE ID %d '%s': name='%s'\n", $rw3['id'], $url, $rw3['name'] ?? '');
            } else {
                echo "  NOT IN DB: $url\n";
            }
        }
    }
}

// ===== APPLY =====
echo "\n=== TOTAL FIXES: " . count($fixes) . " ===\n";

if ($apply && count($fixes) > 0) {
    $bfile = "$backup_dir/h1_fixes_" . date('Ymd_His') . ".sql";
    $fp = fopen($bfile, 'w');
    fwrite($fp, "-- Backup name_h1 fields BEFORE fix\n");
    
    $cat_ids = [];
    foreach ($fixes as $f) {
        if ($f[1] == 'category') $cat_ids[] = $f[0];
    }
    if (count($cat_ids) > 0) {
        $b = backup_table($db, $fp, 'ok_lang_categories', 'lang_id=1 AND category_id IN (' . implode(',', $cat_ids) . ')');
        echo "Backed up $b category records\n";
    }
    fclose($fp);
    
    $stmt = $db->prepare("UPDATE ok_lang_categories SET name_h1=? WHERE category_id=? AND lang_id=1");
    $db->query("START TRANSACTION");
    $count = 0;
    foreach ($fixes as $f) {
        if ($f[1] == 'category') {
            $stmt->bind_param('si', $f[3], $f[0]);
            $stmt->execute();
            $count++;
            echo "  OK ID {$f[0]}: '{$f[2]}' → '{$f[3]}'\n";
        }
    }
    $db->query("COMMIT");
    echo "\nUpdated $count categories.\n";
    echo "Backup: $bfile\n";
} else {
    echo "Add &apply=1 to execute.\n";
}

$db->close();
echo "\n=== DONE ===\n";
