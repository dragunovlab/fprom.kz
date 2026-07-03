<?php
if (!isset($_GET['key']) || $_GET['key'] !== 'fprom_backup_2026_secret') { die('403'); }
header("Content-Type: text/plain; charset=utf-8");

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

$conn = new mysqli("localhost", "p-329887_h-37688_fprom1", "5Ws!p3l6", "p-329887_h-37688_fprom1");
$conn->set_charset("utf8");
$conn->query("SET NAMES utf8");

$result = $conn->query("SELECT id, url, parent_id FROM ok_categories ORDER BY parent_id, position");
$allCats = array();
while ($r = $result->fetch_assoc()) $allCats[$r["id"]] = $r;

// Get names
$testRes = $conn->query("SELECT category_id, name FROM ok_lang_categories WHERE lang_id=1 LIMIT 1");
$testRow = $testRes->fetch_assoc();
$isCp1251 = (strlen($testRow["name"]) > 0 && ord($testRow["name"][0]) >= 0x80);

$conn->set_charset($isCp1251 ? "cp1251" : "utf8");
$conn->query("SET NAMES " . ($isCp1251 ? "cp1251" : "utf8"));
$nameRes = $conn->query("SELECT category_id, name FROM ok_lang_categories WHERE lang_id=1");
$names = array();
while ($nr = $nameRes->fetch_assoc()) {
    $n = $nr["name"];
    if ($isCp1251) $n = @mb_convert_encoding($n, "UTF-8", "Windows-1251");
    $names[$nr["category_id"]] = trim($n);
}

$conn->set_charset("utf8");
$conn->query("SET NAMES utf8");

// Build existing URL map
$existingUrls = array();
foreach ($allCats as $c) $existingUrls[$c["url"]] = $c["id"];

$fixes = array();

foreach ($allCats as $id => $cat) {
    $url = $cat["url"];
    $name = isset($names[$id]) ? $names[$id] : "";
    if (empty($name)) continue;
    
    $isBad = false;
    $reason = "";
    $suggested = "";
    
    // Check for trailing 1-2 digit number (duplicate from import)
    // Only flag if it ends with a bare digit suffix like *2, *3, *12 etc.
    // But NOT if it's a model/number (vr-300-45, sn-62-sn-56)
    if (preg_match("/^(.+?)(\d{1,2})$/", $url, $m)) {
        $baseUrl = $m[1];
        $suffix = $m[2];
        // Only flag if: base exists as another category's URL, 
        // OR suffix is 1-2 digits and base > 5 chars
        if (strlen($baseUrl) > 5 && strlen($suffix) >= 1 && strlen($suffix) <= 2) {
            // Skip if URL looks like a model number (contains numbers throughout)
            $digitCount = preg_match_all('/\d/', $url);
            if ($digitCount <= 2 || $digitCount == strlen($suffix)) {
                $t = translit($name);
                if (!empty($t) && $t !== $url) {
                    $isBad = true;
                    $reason = "trailing_num";
                    $suggested = $t;
                }
            }
        }
    }
    
    // Check for concatenated duplicates (the worst cases)
    if (!$isBad) {
        $segments = explode("-", $url);
        foreach ($segments as $seg) {
            if (strlen($seg) < 6) continue;
            // Check if segment contains repeated substrings >= 4 chars
            $l = strlen($seg);
            for ($i = 4; $i <= $l / 2; $i++) {
                $pref = substr($seg, 0, $i);
                if (strpos(substr($seg, $i), $pref) !== false) {
                    $isBad = true;
                    $reason = "dup_concat";
                    $suggested = translit($name);
                    break 2;
                }
            }
        }
    }
    
    if ($isBad && !empty($suggested)) {
        // Ensure uniqueness against existing URLs AND previously fixed URLs in this batch
        $tmpUrl = $suggested;
        $counter = 0;
        $usedInBatch = array();
        foreach ($fixes as $fx) $usedInBatch[$fx[2]] = true;
        while (
            (isset($existingUrls[$tmpUrl]) && $existingUrls[$tmpUrl] != $id) ||
            isset($usedInBatch[$tmpUrl])
        ) {
            $counter++;
            $tmpUrl = $suggested . "-" . $counter;
        }
        $suggested = $tmpUrl;
        
        if ($suggested !== $url) {
            $fixes[] = array($id, $url, $suggested, $name, $reason);
        }
    }
}

echo "Total categories: " . count($allCats) . "\n";
echo "Fixes needed: " . count($fixes) . "\n\n";

printf("%-5s %-55s %-55s %-20s %s\n", "ID", "OLD URL", "NEW URL", "REASON", "NAME");
echo str_repeat("-", 160) . "\n";
foreach ($fixes as $f) {
    printf("%-5d %-55s %-55s %-20s %s\n", $f[0], $f[1], $f[2], $f[4], mb_substr($f[3], 0, 30));
}

if (isset($_GET['apply']) && $_GET['apply'] == '1') {
    echo "\n\n=== APPLYING FIXES ===\n";
    $conn->query("START TRANSACTION");
    $stmt = $conn->prepare("UPDATE ok_categories SET url=? WHERE id=?");
    $count = 0;
    foreach ($fixes as $f) {
        $stmt->bind_param('si', $f[2], $f[0]);
        if ($stmt->execute()) {
            echo "  OK [{$f[0]}] {$f[1]} → {$f[2]}\n";
            $count++;
        } else {
            echo "  FAIL [{$f[0]}]: " . $stmt->error . "\n";
        }
    }
    $stmt->close();
    $conn->query("COMMIT");
    echo "\nApplied $count fixes.\n";
}

$conn->close();
