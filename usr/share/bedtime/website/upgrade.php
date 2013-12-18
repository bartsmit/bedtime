<?php
session_start(); if (!isset($_SESSION["name"])) { header("location:login.php"); }
include "dbconn.php";
$res = squery("select value from settings where variable='latest_url'",$mysqli);
$url = $res['value'];
$res = squery("select value from settings where variable='latest'",$mysqli);
$latest = $res['value'];
file_put_contents("/tmp/bedtime-$latest.tgz", fopen($url,'r'));
$sock = socket_create(AF_INET,SOCK_STREAM,SOL_TCP);
$result = socket_connect($sock,'127.0.0.1',5000);
$buf = "n\n";
socket_write($sock,$buf,strlen($buf));
?>
<html><head><title>Upgrade</title>
<link rel="stylesheet" type="text/css" href="desktop.css">
</head><body><h1>Upgrade</h1>
The upgrade has downloaded and is running in the background<br>
Go <a href="index.php">back</a> and scroll down to the version number<br>
Refresh the page until it shows:<br><br>
<?php
echo "<div class=\"version\">\n";
echo "<p>Bedtime version $latest</p>\n";
echo "</div>\n";
?>
