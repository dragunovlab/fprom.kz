<?php
if (!isset($_GET['key']) || $_GET['key'] !== 'fprom_backup_2026_secret') {
    header('HTTP/1.0 403 Forbidden');
    die('Access denied');
}

define('BACKUP_DIR', __DIR__ . '/backups/');
define('DB_HOST', 'localhost');
define('DB_USER', 'p-329887_h-37688_fprom1');
define('DB_PASS', '5Ws!p3l6');
define('DB_NAME', 'p-329887_h-37688_fprom1');
define('MAX_BACKUPS', 14);

if (!file_exists(BACKUP_DIR)) {
    mkdir(BACKUP_DIR, 0755, true);
}

$files = glob(BACKUP_DIR . '*.sql');
usort($files, function($a, $b) { return filemtime($a) - filemtime($b); });
while (count($files) >= MAX_BACKUPS) {
    unlink(array_shift($files));
}

$timestamp = date('Y-m-d_H-i-s');
$sqlFile = BACKUP_DIR . "fprom_db_{$timestamp}.sql";

$cmd = "mysqldump --opt -h " . DB_HOST . " -u " . DB_USER . " -p'" . DB_PASS . "' " . DB_NAME . " 2>/dev/null";
$output = shell_exec($cmd);
if ($output === null || trim($output) === '') {
    $conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
    if (!$conn->connect_error) {
        $conn->set_charset('utf8mb4');
        $output = "-- fprom.kz DB Backup (" . date('Y-m-d H:i:s') . ")\n\n";
        $tables = $conn->query("SHOW TABLES");
        while ($row = $tables->fetch_row()) {
            $table = $row[0];
            $create = $conn->query("SHOW CREATE TABLE `$table`")->fetch_row();
            $output .= "DROP TABLE IF EXISTS `$table`;\n";
            $output .= $create[1] . ";\n\n";
            $rows = $conn->query("SELECT * FROM `$table`");
            if ($rows && $rows->num_rows > 0) {
                $output .= "INSERT INTO `$table` VALUES\n";
                $chunk = [];
                while ($r = $rows->fetch_row()) {
                    $escaped = array_map(function($v) use ($conn) {
                        return $v === null ? 'NULL' : "'" . $conn->real_escape_string((string)$v) . "'";
                    }, $r);
                    $chunk[] = "(" . implode(',', $escaped) . ")";
                    if (count($chunk) >= 200) {
                        $output .= implode(",\n", $chunk) . ";\n\nINSERT INTO `$table` VALUES\n";
                        $chunk = [];
                    }
                }
                if (count($chunk) > 0) {
                    $output .= implode(",\n", $chunk) . ";\n\n";
                }
            }
        }
        $conn->close();
    }
}
file_put_contents($sqlFile, $output);
// Protect backups directory
file_put_contents(BACKUP_DIR . 'index.html', '');
echo "BACKUP_OK:" . basename($sqlFile) . ":" . filesize($sqlFile);
