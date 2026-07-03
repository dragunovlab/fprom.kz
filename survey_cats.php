<?php
$db = new mysqli('localhost', 'p-329887_h-37688_fprom1', '5Ws!p3l6', 'p-329887_h-37688_fprom1');
if ($db->connect_error) { die('Connect Error: '.$db->connect_error); }
$db->set_charset('utf8');

// Survey categories
$result = $db->query("
    SELECT c.id, c.parent_id, c.url, c.visible,
           lc.name, lc.name_h1, lc.description,
           lc.meta_title, lc.meta_description,
           lc.auto_meta_title, lc.auto_meta_desc, lc.auto_description,
           (SELECT COUNT(*) FROM ok_products_categories pc WHERE pc.category_id = c.id) as products_count
    FROM ok_categories c
    LEFT JOIN ok_lang_categories lc ON c.id = lc.category_id AND lc.lang_id = 1
    WHERE c.visible = 1
    ORDER BY c.parent_id, c.position
");

$total = 0;
$empty_desc = 0;
$empty_meta_title = 0;
$empty_meta_desc = 0;
$broken_names = 0;
$needs_fix = [];

while ($row = $result->fetch_assoc()) {
    $total++;
    $name = trim($row['name']);
    $desc = trim($row['description']);
    $meta_title = trim($row['meta_title']);
    $meta_desc = trim($row['meta_description']);
    
    $issues = [];
    
    if (empty($name) || strlen($name) > 80 || preg_match('/(\S)\1{3,}/', $name)) {
        $issues[] = 'BROKEN_NAME';
        $broken_names++;
    }
    if (empty($desc)) $issues[] = 'NO_DESCRIPTION';
    if (empty($meta_title)) $issues[] = 'NO_META_TITLE';
    if (empty($meta_desc)) $issues[] = 'NO_META_DESC';
    
    if (!empty($issues)) {
        $needs_fix[] = [
            'id' => $row['id'],
            'url' => $row['url'],
            'name' => $name,
            'h1' => $row['name_h1'],
            'issues' => implode(', ', $issues),
            'products' => $row['products_count']
        ];
    }
    
    if (empty($desc)) $empty_desc++;
    if (empty($meta_title)) $empty_meta_title++;
    if (empty($meta_desc)) $empty_meta_desc++;
}

echo "=== CATEGORY SURVEY ===\n";
echo "Total visible categories: $total\n";
echo "Empty description: $empty_desc\n";
echo "Empty meta_title: $empty_meta_title\n";
echo "Empty meta_description: $empty_meta_desc\n";
echo "Broken names: $broken_names\n";
echo "Categories needing fix: " . count($needs_fix) . "\n";

echo "\n=== CATEGORIES NEEDING ATTENTION ===\n";
foreach ($needs_fix as $n) {
    $name_display = mb_substr($n['name'], 0, 60);
    echo "ID:{$n['id']} | Products:{$n['products']} | Issues: {$n['issues']}\n";
    echo "  Name: " . ($name_display ?: '(empty)') . "\n";
    echo "  URL: {$n['url']}\n";
    echo "  H1: " . ($n['h1'] ?: '(empty)') . "\n";
    echo "---\n";
}

$db->close();
