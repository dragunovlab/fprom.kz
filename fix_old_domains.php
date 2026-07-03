<?php
$db = new mysqli('localhost', 'p-329887_h-37688_fprom1', '5Ws!p3l6', 'p-329887_h-37688_fprom1');
if ($db->connect_error) { die('Connect Error: '.$db->connect_error); }
$db->set_charset('utf8');

$backup_dir = __DIR__ . '/backups';
if (!is_dir($backup_dir)) mkdir($backup_dir, 0755, true);

// Restore page 2 - read current content and save backup first
$result = $db->query("SELECT description FROM ok_lang_pages WHERE page_id=2 AND lang_id=1");
$row = $result->fetch_assoc();
$body_oplata = $row['description'];
$result->free();
file_put_contents("$backup_dir/page_2_before_fix_v2.html", $body_oplata);

// Check ok_pages structure
$result = $db->query("SHOW COLUMNS FROM ok_pages LIKE 'description'");
$has_description = ($result->num_rows > 0);
$result->free();
echo "ok_pages has description column: " . ($has_description ? 'yes' : 'no') . "\n";

$pages = [2 => 'oplata', 22 => 'o-kompanii'];
$domains = ['fortuneprom\.kz', 'fortuneprom\.all\.biz', 'fortune-prom\.kz'];

foreach ($pages as $page_id => $url) {
    echo "\n=== Page $page_id ($url) ===\n";

    $result = $db->query("SELECT description FROM ok_lang_pages WHERE page_id=$page_id AND lang_id=1");
    $row = $result->fetch_assoc();
    $body = $row['description'];
    $result->free();
    echo "Before: " . strlen($body) . " bytes\n";

    file_put_contents("$backup_dir/page_{$page_id}_before.html", $body);

    foreach ($domains as $dom) {
        $body = preg_replace("/<li[^>]*>(?:(?!<\/li>).)*?$dom(?:(?!<\/li>).)*?<\/li>\s*/is", '', $body);
    }

    $body = preg_replace('/<a href="\.\.\/"/', '<a href="https://fprom.kz"', $body);

    foreach ($domains as $dom) {
        $body = preg_replace("/<p[^>]*>(?:(?!<\/p>).)*?$dom(?:(?!<\/p>).)*?<\/p>\s*/is", '', $body);
    }

    echo "After: " . strlen($body) . " bytes\n";
    file_put_contents("$backup_dir/page_{$page_id}_after.html", $body);

    $stmt = $db->prepare("UPDATE ok_lang_pages SET description=? WHERE page_id=? AND lang_id=1");
    if ($stmt) {
        $stmt->bind_param('si', $body, $page_id);
        $stmt->execute();
        $aff = $stmt->affected_rows;
        $stmt->close();
        echo "ok_lang_pages: $aff rows\n";
    } else {
        echo "ok_lang_pages prepare failed: " . $db->error . "\n";
    }

    if ($has_description) {
        $stmt = $db->prepare("UPDATE ok_pages SET description=? WHERE id=?");
        if ($stmt) {
            $stmt->bind_param('si', $body, $page_id);
            $stmt->execute();
            $aff = $stmt->affected_rows;
            $stmt->close();
            echo "ok_pages: $aff rows\n";
        } else {
            echo "ok_pages prepare failed: " . $db->error . "\n";
        }
    }
}

$db->close();
echo "\n=== DONE ===\n";
