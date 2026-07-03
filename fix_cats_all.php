<?php
/**
 * Comprehensive fix for ALL category URL issues:
 * 1. Concatenated segments (bytovyenasosy → bytovye-nasosy)
 * 2. Repeated words in URL (nasosy-nasosy → nasosy)
 * 3. Semicolons in names (Горно-шахтные;Карьерный → Горно-шахтные — Карьерный)
 * 4. Tripled segments (nasosy-...-nasosy-nasosy → single)
 *
 * Usage: ?key=fprom_backup_2026_secret&apply=1
 */

if (!isset($_GET['key']) || $_GET['key'] !== 'fprom_backup_2026_secret') { die('403'); }
header("Content-Type: text/plain; charset=utf-8");

$db = new mysqli('localhost', 'p-329887_h-37688_fprom1', '5Ws!p3l6', 'p-329887_h-37688_fprom1');
if ($db->connect_error) die('DB error: ' . $db->connect_error);
$db->set_charset('utf8');
$db->query("SET NAMES utf8");

$apply = isset($_GET['apply']) && $_GET['apply'] == '1';

function translit($s) {
    $a = array(
        "\xD0\x90"=>"A","\xD0\x91"=>"B","\xD0\x92"=>"V","\xD0\x93"=>"G","\xD0\x94"=>"D",
        "\xD0\x95"=>"E","\xD0\x81"=>"Yo","\xD0\x96"=>"Zh","\xD0\x97"=>"Z","\xD0\x98"=>"I",
        "\xD0\x99"=>"Y","\xD0\x9A"=>"K","\xD0\x9B"=>"L","\xD0\x9C"=>"M","\xD0\x9D"=>"N",
        "\xD0\x9E"=>"O","\xD0\x9F"=>"P","\xD0\xA0"=>"R","\xD0\xA1"=>"S","\xD0\xA2"=>"T",
        "\xD0\xA3"=>"U","\xD0\xA4"=>"F","\xD0\xA5"=>"Kh","\xD0\xA6"=>"Ts","\xD0\xA7"=>"Ch",
        "\xD0\xA8"=>"Sh","\xD0\x89"=>"Shch","\xD0\xAA"=>"","\xD0\xAB"=>"Y","\xD0\xAC"=>"",
        "\xD0\xAD"=>"E","\xD0\xAE"=>"Yu","\xD0\xAF"=>"Ya",
        "\xD0\xB0"=>"a","\xD0\xB1"=>"b","\xD0\xB2"=>"v","\xD0\xB3"=>"g","\xD0\xB4"=>"d",
        "\xD0\xB5"=>"e","\xD1\x91"=>"yo","\xD0\xB6"=>"zh","\xD0\xB7"=>"z","\xD0\xB8"=>"i",
        "\xD0\xB9"=>"y","\xD0\xBA"=>"k","\xD0\xBB"=>"l","\xD0\xBC"=>"m","\xD0\xBD"=>"n",
        "\xD0\xBE"=>"o","\xD0\xBF"=>"p","\xD1\x80"=>"r","\xD1\x81"=>"s","\xD1\x82"=>"t",
        "\xD1\x83"=>"u","\xD1\x84"=>"f","\xD1\x85"=>"kh","\xD1\x86"=>"ts","\xD1\x87"=>"ch",
        "\xD1\x88"=>"sh","\xD1\x89"=>"shch","\xD1\x8A"=>"","\xD1\x8B"=>"y","\xD1\x8C"=>"",
        "\xD1\x8D"=>"e","\xD1\x8E"=>"yu","\xD1\x8F"=>"ya",
        "\x20"=>"-","\x2F"=>"-","\x2C"=>"","\x28"=>"","\x29"=>"",
        "\x2E"=>"","\x27"=>"","\x22"=>"","\x60"=>"","\xAB"=>"","\xBB"=>"",
    );
    $s = strtr($s, $a);
    $s = preg_replace('/[–—\;]/u', '-', $s);
    $s = preg_replace("/[^a-zA-Z0-9\-]/", "", $s);
    $s = preg_replace("/-+/", "-", $s);
    $s = trim($s, "-");
    $s = strtolower($s);
    return $s;
}

// Clean URL: remove consecutive duplicate segments
function cleanUrl($url) {
    $parts = explode('-', $url);
    $clean = [];
    foreach ($parts as $p) {
        if (end($clean) !== $p) {
            $clean[] = $p;
        }
    }
    $res = implode('-', $clean);
    $res = preg_replace("/-+/", "-", $res);
    $res = trim($res, "-");
    return $res;
}

// ===== 1. Get all categories =====
$result = $db->query("SELECT c.id, c.url, c.parent_id, COALESCE(lc.name, '') as name
    FROM ok_categories c
    LEFT JOIN ok_lang_categories lc ON c.id = lc.category_id AND lc.lang_id=1
    ORDER BY c.parent_id, c.position");

$allCats = [];
$usedUrls = [];
$badPatterns = [];

while ($row = $result->fetch_assoc()) {
    $allCats[$row['id']] = $row;
    $usedUrls[$row['url']] = $row['id'];
}

echo "Total categories: " . count($allCats) . "\n\n";

// ===== 2. Detect issues =====
$fixes = [];

foreach ($allCats as $id => $cat) {
    $url = $cat['url'];
    $name = trim($cat['name']);
    $nameClean = preg_replace('/\s*[;]\s*/u', ' — ', $name); // semicolon → dash
    $parentId = $cat['parent_id'];
    
    $issues = [];
    $suggested = '';
    
    // Check 1: Name has semicolons → name/description issue (not URL fix)
    // Check 2: URL has concatenated words (no dash between known words)
    if (preg_match('/(nasosy|oborudovanie|ustanovki|bytovye|pogruzhnye|skvazhinnye|vintovye|gorizontalno|vertikalnye|mnogostupenchatye|kanalizatsionnye|izmelchiteli|samovsasyvayuschie|vikhrevye|karernyj|gidravlicheskij)(?=[a-z])/i', $url)) {
        $issues[] = 'concat_segments';
    }
    
    // Check 3: URL has repeated words (tripled + doubled)
    $segments = explode('-', $url);
    $seen = [];
    foreach ($segments as $s) {
        if (strlen($s) > 3) {
            if (isset($seen[$s])) $seen[$s]++;
            else $seen[$s] = 1;
        }
    }
    $repeated = array_filter($seen, function($v) { return $v > 1; });
    if (count($repeated) > 0) {
        $issues[] = 'dup_words';
    }
    
    // Check 4: URL has consecutive duplicate segments (nasosy-nasosy)
    for ($i = 0; $i < count($segments) - 1; $i++) {
        if ($segments[$i] === $segments[$i+1] && strlen($segments[$i]) > 2) {
            $issues[] = 'dup_segments';
            break;
        }
    }
    
    if (count($issues) > 0) {
        // Generate fix from name
        if (!empty($name)) {
            $suggested = translit($name);
            $suggested = cleanUrl($suggested);
        } else {
            $suggested = cleanUrl($url);
        }
        
        // Ensure uniqueness
        $tmp = $suggested;
        $counter = 0;
        $usedInBatch = [];
        foreach ($fixes as $fx) $usedInBatch[$fx[2]] = true;
        while ((isset($usedUrls[$tmp]) && $usedUrls[$tmp] != $id) || isset($usedInBatch[$tmp])) {
            $counter++;
            $tmp = $suggested . '-' . $counter;
        }
        
        if ($tmp !== $url) {
            $fixes[] = [$id, $url, $tmp, $name, implode(',', $issues)];
        }
    }
}

echo "=== FIXES NEEDED: " . count($fixes) . " ===\n\n";
printf("%-5s %-50s %-50s %s\n", "ID", "OLD URL", "NEW URL", "ISSUES");
echo str_repeat("-", 150) . "\n";
foreach ($fixes as $f) {
    printf("%-5d %-50s %-50s %s\n", $f[0], $f[1], $f[2], $f[4]);
}

// ===== 3. APPLY =====
if ($apply && count($fixes) > 0) {
    echo "\n=== APPLYING ===\n";
    
    // Backup
    $backupDir = __DIR__ . '/backups';
    if (!is_dir($backupDir)) mkdir($backupDir, 0755, true);
    $bfile = "$backupDir/cats_all_" . date('Ymd_His') . ".sql";
    $fp = fopen($bfile, 'w');
    fwrite($fp, "-- Backup BEFORE fixing ALL category URLs\n");
    fwrite($fp, "-- " . date('Y-m-d H:i:s') . "\n\n");
    
    foreach ($fixes as $f) {
        $id = $f[0];
        $rowR = $db->query("SELECT * FROM ok_categories WHERE id=$id");
        if ($rowR && $row = $rowR->fetch_assoc()) {
            $e_url = $db->real_escape_string($row['url']);
            fwrite($fp, "UPDATE ok_categories SET url='$e_url' WHERE id=$id;\n");
        }
    }
    fclose($fp);
    echo "Backup: $bfile\n";
    
    // Update DB
    $stmt = $db->prepare("UPDATE ok_categories SET url=? WHERE id=?");
    $db->query("START TRANSACTION");
    $count = 0;
    foreach ($fixes as $f) {
        $stmt->bind_param('si', $f[2], $f[0]);
        if ($stmt->execute() && $stmt->affected_rows > 0) {
            echo "  OK [{$f[0]}] {$f[1]} → {$f[2]}\n";
            $count++;
        }
    }
    $stmt->close();
    $db->query("COMMIT");
    echo "\nApplied $count fixes.\n";
    
    // ===== 4. Update cat_redirect_map.php =====
    $mapFile = __DIR__ . '/cat_redirect_map.php';
    $mapContent = file_get_contents($mapFile);
    $addCount = 0;
    
    foreach ($fixes as $f) {
        list($id, $oldUrl, $newUrl) = $f;
        if (strpos($mapContent, "'$oldUrl' =>") === false) {
            // Add before closing ];
            $insert = "\n    '$oldUrl' => '$newUrl',";
            $mapContent = str_replace("];", $insert . "\n];", $mapContent);
            $addCount++;
        }
    }
    
    if ($addCount > 0) {
        file_put_contents($mapFile, $mapContent);
        echo "Added $addCount redirect entries.\n";
    }
    
} else {
    echo "\nDRY RUN. Add &apply=1 to execute.\n";
}

$db->close();
echo "\n=== DONE ===\n";
