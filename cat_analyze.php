<?php
if (!isset($_GET['key']) || $_GET['key'] !== 'fprom_backup_2026_secret') { die('403'); }
$conn = new mysqli('localhost', 'p-329887_h-37688_fprom1', '5Ws!p3l6', 'p-329887_h-37688_fprom1');
$conn->set_charset('utf8mb4');

$result = $conn->query("
    SELECT c.id, c.url, c.parent_id, c.position, c.visible,
           lc.name, lc.name_h1, lc.meta_title
    FROM ok_categories c
    JOIN ok_lang_categories lc ON c.id=lc.category_id AND lc.lang_id=1
    ORDER BY c.parent_id, c.position
");

$cats = [];
while ($row = $result->fetch_assoc()) {
    $cats[] = $row;
}

// Analyze URLs that have repeated segments
$badCount = 0;
$fixes = [];
foreach ($cats as $c) {
    // Detect repeated concatenated words (patterns like word1word1word2 or word1word1word1)
    $url = $c['url'];
    $orig = $url;
    
    // Split by common Russian words to detect duplicates
    // Strategy: remove duplicate adjacent segments
    $parts = preg_split('/[-]/', $url);
    $unique = [];
    $hasDup = false;
    foreach ($parts as $p) {
        if (in_array($p, $unique)) {
            $hasDup = true;
        } else {
            $unique[] = $p;
        }
    }
    
    if ($hasDup) {
        $newUrl = implode('-', $unique);
        // Clean trailing numbers
        $newUrl = preg_replace('/-?\d+$/', '', $newUrl);
        $newUrl = rtrim($newUrl, '-');
        
        $badCount++;
        $fixes[] = [
            'id' => $c['id'],
            'old' => $orig,
            'new' => $newUrl,
            'name' => $c['name']
        ];
    }
}

// Also check for URLs ending with numbers (like oborudovanie2) - they might be duplicates from import
$numCount = 0;
foreach ($cats as $c) {
    if (preg_match('/\d+$/', $c['url']) && !preg_match('/^p-\d/', $c['url'])) {
        $numCount++;
        // Check if parent category has a similar name
        $base = rtrim(preg_replace('/\d+$/', '', $c['url']), '-');
        // Check if base URL already exists
        $exists = false;
        foreach ($cats as $c2) {
            if ($c2['id'] != $c['id'] && $c2['url'] === $base) {
                $exists = true;
                break;
            }
        }
        if (!$exists) {
            $fixes[] = [
                'id' => $c['id'],
                'old' => $c['url'],
                'new' => $base,
                'name' => $c['name'] . ' (end number)'
            ];
        }
    }
}

echo "Total categories: " . count($cats) . "\n";
echo "Bad URLs found: " . $badCount . "\n";
echo "URLs with trailing numbers: " . $numCount . "\n";
echo "Total fixes needed: " . count($fixes) . "\n\n";
echo "=== FIXES ===\n";
foreach ($fixes as $f) {
    echo "{$f['id']}|{$f['old']}|{$f['new']}|{$f['name']}\n";
}
$conn->close();
