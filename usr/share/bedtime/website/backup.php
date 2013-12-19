<?php
session_start(); if (!isset($_SESSION["name"])) { header("location:login.php"); }
include "dbconn.php";
$user = $vals["dbuser"];
$pass = $vals["dbpass"];
date_default_timezone_set('UTC');
$dt = date('Ymd-His');
exec("mysqldump --compact --no-create-db -u $user -p$pass bedtime parent child device rules holiday ground reward > /tmp/dump-$dt.sql");
$zip = new ZipArchive;
$res = $zip->open("/tmp/dump-$dt.zip", ZipArchive::CREATE);
$zip->addFile("/tmp/dump-$dt.sql","dump-$dt.sql");
$zip->close();
header("Content-type: application/zip");
header("Content-Disposition: attachment; filename=\"backup-$dt.zip\"");
readfile("/tmp/dump-$dt.zip");
unlink("/tmp/dump-$dt.zip");
unlink("/tmp/dump-$dt.sql");
?>
