<?php
/*
This script creates a file stream containing a ZIP file
of the mysqldump for the stateful tables:
 - parent
 - child
 - device
 - rules
 - holidays
 - ground
 - reward
The user downloads this file to their workstation.
*/

# There is potentially sensitive information in the backup
# So make sure the user is authenticated before downloading
session_start(); if (!isset($_SESSION["name"])) { header("location:login.php"); }
# Database connection is not required, but the credentials are set by dbconn.php
include "dbconn.php";
$user = $vals["dbuser"];
$pass = $vals["dbpass"];
# The filename includes the date and time. PHP moans if the time is ambiguous
# Since it is used to create unique files only, it makes no odds what zone is
# picked. We'll just stick with UTC for simplicity.
date_default_timezone_set('UTC');
$dt = date('Ymd-His');
# Run a mysqldump with the dbconn.php credentials, sending the sql script to /tmp
# Note that with the new systemd private tmp settings, this will not be the real /tmp
exec("mysqldump --compact --no-create-db -u $user -p$pass bedtime parent child device rules holiday ground reward > /tmp/dump-$dt.sql");
$zip = new ZipArchive;
# Create the zip from the sql file in /tmp (wherever that may be)
$res = $zip->open("/tmp/dump-$dt.zip", ZipArchive::CREATE);
$zip->addFile("/tmp/dump-$dt.sql","dump-$dt.sql");
$zip->close();
# Time to stream the zip file to the web client
header("Content-type: application/zip");
header("Content-Disposition: attachment; filename=\"backup-$dt.zip\"");
readfile("/tmp/dump-$dt.zip");
# We are done with the zip and the sql files, so delete them
unlink("/tmp/dump-$dt.zip");
unlink("/tmp/dump-$dt.sql");
?>
