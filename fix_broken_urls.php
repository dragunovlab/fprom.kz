<?php
/**
 * Fix 30 broken category URLs (concatenated segments)
 * Usage: http://fprom.kz/fix_broken_urls.php        (dry-run)
 *        http://fprom.kz/fix_broken_urls.php?apply=1 (apply fixes)
 */

if (!isset($_GET['key']) || $_GET['key'] !== 'fprom_backup_2026_secret') { 
    if (PHP_SAPI !== 'cli') { header("Content-Type: text/plain; charset=utf-8"); }
}
$apply = isset($_GET['apply']) && $_GET['apply'] == '1';

$db = new mysqli('localhost', 'p-329887_h-37688_fprom1', '5Ws!p3l6', 'p-329887_h-37688_fprom1');
if ($db->connect_error) die('DB connect error: ' . $db->connect_error);
$db->set_charset('utf8');
$db->query("SET NAMES utf8");

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
    $s = preg_replace('/[–—]/u', '-', $s);
    $s = preg_replace("/[^a-zA-Z0-9\-]/", "", $s);
    $s = preg_replace("/-+/", "-", $s);
    $s = trim($s, "-");
    $s = strtolower($s);
    return $s;
}

$broken_urls = [
    'nasosy-bytovyenasosy-pogruzhnye-dlya-chistoj-i-slabo-zagryaznennoj-vodynasosy-pogruzhnye-dlya-chistoj-i-slabo-zagryaznennoj-vody',
    'gorno-shahtnye-oborudovaniekarernyj-ekskavator',
    'nasosy-mnogostupenchatye-gorizontalnyenasosy-samovsasyvayuschie',
    'tsirkulyatsionnye-nasosy-nasosy-bytovye',
    'gorno-shahtnye-oborudovaniekarernyj-samosval',
    'tsirkulyatsionnye-nasosy-nasosy-bytovye-nasosy-tsirkulyatsionnye',
    'nasosy-pogruzhnye-dlya-gryaznoj-vodynasosy-izmelchiteli-stantsii-kanalizatsionnyenasosy-pogruzhnye-kanalizatsionnyenasosy-pogruzhnye-dlya-gryaznoj-vody',
    'nasosy-zhidkostno-koltsevyenasosy-samovsasyvayuschie',
    'nasosy-bytovyenasosy-skvazhinnyenasosy-skvazhinnye',
    'avtomaticheskie-nasosy-izmelchitelinasosy-izmelchiteli-stantsii-kanalizatsionnyenasosy-pogruzhnye-kanalizatsionnye',
    'nasosy-mnogostupenchatye-gorizontalnyenasosy-samovsasyvayuschienasosy-samovsasyvayuschie',
    'nasosy-bytovyenasosy-vihrevyenasosy-samovsasyvayuschienasosy-vihrevyenasosy-samovsasyvayuschie',
    'burovye-ustanovkigorizontalno-napravlennyj-bur',
    'nasosy-mnogostupenchatye-vertikalnyenasosy-mnogostupenchatye-vertikalnye',
    'nasosy-mnogostupenchatye-vertikalnyenasosy-mnogostupenchatye-vertikalnyenasosy-mnogostupenchatye-vertikalnye',
    'nasosy-bytovyenasosy-rotorno-shibernye',
    'nasosy-pogruzhnye-kanalizatsionnyenasosy-pogruzhnye-dlya-gryaznoj-vody',
    'nasosy-pogruzhnye-dlya-gryaznoj-vodynasosy-pogruzhnye-dlya-chistoj-i-slabo-zagryaznennoj-vodynasosy-pogruzhnye-dlya-gryaznoj-vodynasosy-pogruzhnye-dlya-chistoj-i-slabo-zagryaznennoj-vody',
    'nasosy-bytovyenasosy-pogruzhnye-dlya-chistoj-i-slabo-zagryaznennoj-vody',
    'tsirkulyatsionnye-nasosy-nasosy-tsirkulyatsionnye',
    'nasosy-pogruzhnye-dlya-chistoj-i-slabo-zagryaznennoj-vodynasosy-pogruzhnye-dlya-chistoj-i-slabo-zagryaznennoj-vody',
    'nasosy-dlya-povysheniya-davleniya-vody-v-domenasosy-samovsasyvayuschie',
    'nasosy-bytovyenasosy-vintovye',
    'nasosy-bytovyenasosy-vihrevyenasosy-vihrevye',
    'nasosy-pogruzhnye-dlya-gryaznoj-vodynasosy-pogruzhnye-dlya-chistoj-i-slabo-zagryaznennoj-vodynasosy-pogruzhnye-dlya-chistoj-i-slabo-zagryaznennoj-vody',
    'nasosy-pogruzhnye-dlya-gryaznoj-vodynasosy-pogruzhnye-kanalizatsionnyenasosy-pogruzhnye-dlya-gryaznoj-vody',
    'nasosy-vihrevyenasosy-vihrevye',
    'nasosy-vihrevyenasosy-pogruzhnye-dlya-chistoj-i-slabo-zagryaznennoj-vody',
    'nasosy-samovsasyvayuschienasosy-samovsasyvayuschie',
    'nasosy-skvazhinnyenasosy-vintovyenasosy-skvazhinnye',
];

echo "=== FINDING BROKEN URLS IN DB ===\n\n";

// Find categories with broken URLs — use simple loop because no get_result()
$found = [];
$not_found = [];
$used_urls = [];

// Get all existing URLs for uniqueness check
$all_res = $db->query("SELECT id, url FROM ok_categories");
while ($r = $all_res->fetch_assoc()) {
    $used_urls[$r['url']] = $r['id'];
}

// Find each broken URL in DB
foreach ($broken_urls as $u) {
    $esc = $db->real_escape_string($u);
    $res = $db->query("SELECT c.id, c.url, c.parent_id, COALESCE(lc.name, '') as name 
        FROM ok_categories c 
        LEFT JOIN ok_lang_categories lc ON c.id = lc.category_id AND lc.lang_id=1
        WHERE c.url = '$esc'");
    if ($row = $res->fetch_assoc()) {
        $found[$u] = $row;
    } else {
        $not_found[] = $u;
    }
}

echo "Found in DB: " . count($found) . "/30\n";
echo "Not found: " . count($not_found) . "\n";
if (count($not_found) > 0) {
    echo "\nMissing URLs (not in DB — may have been renamed or deleted):\n";
    foreach ($not_found as $u) echo "  $u\n";
}

if (count($found) == 0) {
    echo "\nNothing to fix. Exiting.\n";
    $db->close();
    exit;
}

echo "\n=== MATCHED CATEGORIES ===\n";
printf("%-5s %-55s %-55s %s\n", "ID", "CURRENT URL", "NEW URL", "NAME");
echo str_repeat("-", 170) . "\n";

$fixes = [];

foreach ($found as $old_url => $cat) {
    $id = $cat['id'];
    $name = trim($cat['name']);
    
    // Generate clean URL from name
    if (!empty($name)) {
        $suggested = translit($name);
    } else {
        $suggested = $old_url;
        // Try to recover the URL by splitting concatenated words
        $suggested = preg_replace('/([a-z]{4,})(bytovye|pogruzhnye|skvazhinnye|vihrevye|vintovye|nasosy|karernyj|gorizontalno|kanalizatsionnye|izmelchiteli|samovsasyvayuschie|vertikalnye|mnogostupenchatye|gorizontalnye|rotorno|shibernye)/', '$1-$2', $suggested);
        $suggested = preg_replace('/-+/', '-', $suggested);
        $suggested = trim($suggested, '-');
    }
    
    if (empty($suggested)) {
        printf("%-5d %-55s %-55s %s\n", $id, $old_url, "(SKIP)", mb_substr($name, 0, 30));
        continue;
    }
    
    // Ensure uniqueness against existing URLs AND previously fixed URLs in this batch
    $tmp = $suggested;
    $counter = 0;
    $used_in_fixes = [];
    foreach ($fixes as $fx) $used_in_fixes[$fx[2]] = true;
    while ((isset($used_urls[$tmp]) && $used_urls[$tmp] != $id) || isset($used_in_fixes[$tmp])) {
        $counter++;
        $tmp = $suggested . '-' . $counter;
    }
    $suggested = $tmp;
    
    if ($suggested !== $old_url) {
        $fixes[] = [$id, $old_url, $suggested, $name];
        printf("%-5d %-55s %-55s %s\n", $id, $old_url, $suggested, mb_substr($name, 0, 35));
    } else {
        printf("%-5d %-55s %-55s %s\n", $id, $old_url, "(same)", mb_substr($name, 0, 30));
    }
}

echo "\nTotal fixes to apply: " . count($fixes) . "\n";

// Check redirect map
$redirect_map_file = __DIR__ . '/cat_redirect_map.php';
$redirect_map_content = file_get_contents($redirect_map_file);

// Apply?
if ($apply && count($fixes) > 0) {
    echo "\n=== APPLYING FIXES ===\n";
    
    // Backup
    $backup_dir = __DIR__ . '/backups';
    if (!is_dir($backup_dir)) mkdir($backup_dir, 0755, true);
    $bfile = "$backup_dir/categories_broken_urls_" . date('Ymd_His') . ".sql";
    $fp = fopen($bfile, 'w');
    fwrite($fp, "-- Backup BEFORE fixing broken category URLs\n");
    fwrite($fp, "-- " . date('Y-m-d H:i:s') . "\n\n");
    
    foreach ($fixes as $f) {
        $id = $f[0];
        $row_r = $db->query("SELECT * FROM ok_categories WHERE id=$id");
        if ($row_r && $row = $row_r->fetch_assoc()) {
            $e_url = $db->real_escape_string($row['url']);
            $e_parent = (int)$row['parent_id'];
            $e_pos = (int)$row['position'];
            fwrite($fp, "UPDATE ok_categories SET url='$e_url', parent_id=$e_parent, position=$e_pos WHERE id=$id;\n");
        }
    }
    fclose($fp);
    echo "  Backup saved: $bfile\n";
    
    // Update DB
    $db->query("START TRANSACTION");
    $stmt = $db->prepare("UPDATE ok_categories SET url=? WHERE id=?");
    $count = 0;
    foreach ($fixes as $f) {
        list($id, $old_url, $new_url) = $f;
        $stmt->bind_param('si', $new_url, $id);
        if ($stmt->execute() && $stmt->affected_rows > 0) {
            echo "  OK [{$id}] {$old_url} → {$new_url}\n";
            $count++;
        } elseif ($stmt->error) {
            echo "  FAIL [{$id}]: " . $stmt->error . "\n";
        } else {
            echo "  SKIP [{$id}] (no actual change)\n";
        }
    }
    $stmt->close();
    $db->query("COMMIT");
    echo "\nApplied $count URL updates.\n";
    
    // Update redirect map
    echo "\n=== UPDATING cat_redirect_map.php ===\n";
    $map_content = $redirect_map_content;
    $changes = 0;
    
    foreach ($fixes as $f) {
        list($id, $old_url, $new_url) = $f;
        
        // Update as value (target) — "=> 'old_url'"
        $map_content = str_replace("=> '" . $old_url . "'", "=> '" . $new_url . "'", $map_content, $c1);
        // Update as key (source) — "'old_url' =>"
        $map_content = str_replace("'" . $old_url . "' =>", "'" . $new_url . "' =>", $map_content, $c2);
        $changes += $c1 + $c2;
        if ($c1 > 0) echo "  Updated target: '{$old_url}' → '{$new_url}'\n";
        if ($c2 > 0) echo "  Updated source: '{$old_url}' → '{$new_url}'\n";
    }
    
    // Add new redirect entries for any broken URLs not already keys
    $add_lines = [];
    foreach ($fixes as $f) {
        list($id, $old_url, $new_url) = $f;
        if (strpos($map_content, "'$old_url' =>") === false) {
            $add_lines[] = "    '$old_url' => '$new_url',";
        }
    }
    if (count($add_lines) > 0) {
        $insert = "\n" . implode("\n", $add_lines) . "\n";
        $map_content = str_replace("];", $insert . "];", $map_content);
        echo "\nAdded " . count($add_lines) . " new redirect entries.\n";
        $changes++;
    }
    
    if ($changes > 0) {
        file_put_contents($redirect_map_file, $map_content);
        echo "Redirect map saved.\n";
    } else {
        echo "No redirect map changes needed.\n";
    }
    
} elseif (count($fixes) > 0) {
    echo "\n=== DRY RUN — no changes made ===\n";
    echo "Run with ?apply=1 to execute.\n";
}

// Output redirect entries for manual addition
echo "\n=== REDIRECT ENTRIES TO ADD ===\n";
foreach ($fixes as $f) {
    echo "    '{$f[1]}' => '{$f[2]}',\n";
}

$db->close();
echo "\n=== DONE ===\n";
