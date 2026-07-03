<?php
// Daily backup trigger for fprom.kz
$result = file_get_contents('https://fprom.kz/backup.php');
echo date('Y-m-d H:i:s') . " - " . $result;
file_put_contents(__DIR__ . '/backups/cron.log', date('Y-m-d H:i:s') . " - " . $result . "\n", FILE_APPEND);
