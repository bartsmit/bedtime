<?php
/*
This script is called when there is a new version
and a parent has requested an upgrade to it from
the settings page.

Note that a backup and rebuild is still the more
preferred upgrade method.
*/

# Secured page by parent login
session_start(); if (!isset($_SESSION["name"])) { header("location:login.php"); }
# And link to the database
include "dbconn.php";
# Get the URL of the new version
$res = $mysqli->query("select value from settings where variable='latest_url'");
$row = $res->fetch_assoc(); $url = $row['value'];
# And its version
$res = $mysqli->query("select value from settings where variable='latest'");
$row = $res->fetch_assoc(); $latest = $row['value'];
# Donwload the new tarball to /tmp
file_put_contents("/tmp/bedtime-$latest.tgz", fopen($url,'r'));
# And signal the upgrade perl script through the daemon
$sock = socket_create(AF_INET,SOCK_STREAM,SOL_TCP);
$result = socket_connect($sock,'127.0.0.1',5000);
$buf = "n\n";
socket_write($sock,$buf,strlen($buf));
# Give a sweetener message while this is running
?>
<html><head><title>Upgrade</title>
<link rel="stylesheet" type="text/css" href="desktop.css">
</head><body><h1>Upgrade</h1>
The upgrade has downloaded and is running in the background<br>
Go <a href="index.php">back</a> and scroll down to the version number<br>
Refresh the page until it shows:<br><br>
<?php
# Version footer as per usual
echo "<div class=\"version\">\n";
echo "<p>Bedtime version $latest</p>\n";
echo "</div>\n";
?>
