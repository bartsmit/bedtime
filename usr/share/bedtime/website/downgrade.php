<?php
session_start(); if (!isset($_SESSION["name"])) { header("location:login.php"); }
include "dbconn.php";
$res = squery("select value from settings where variable='rpm'",$mysqli);
$rpm = $res['value'];
$sock = socket_create(AF_INET,SOCK_STREAM,SOL_TCP);
$result = socket_connect($sock,'127.0.0.1',5000);
$buf = "o\n";
socket_write($sock,$buf,strlen($buf));
?>
<html><head><title>Upgrade</title>
<link rel="stylesheet" type="text/css" href="desktop.css">
</head><body><h1>Downgrade</h1>
The downgrade is running in the background<br>
Go <a href="index.php">back</a> and scroll down to the version number<br>
Refresh the page until it shows:<br><br>
<?php
echo "<div class=\"version\">\n";
echo "<p>Bedtime version $rpm</p>\n";
echo "</div>\n";
?>
