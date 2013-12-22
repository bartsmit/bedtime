<?php
/*
This script validates the parent name and password
and sets up the session across secured pages
*/

# Create a database connection
include "dbconn.php";
# Pick up the name and password posted by the login page
$parent = $_POST['parent'];
$passwd = $_POST['password'];
# Check the database for hits
$res = $mysqli->query("select * from parent where name='$parent' and password=md5('$passwd')");
# If ther is at least one
if (($res->num_rows) > 0) {
   # Start the secured session with the parent name
   session_start();
   $_SESSION["name"] = $parent;
   # And go to the main page
   header("location:index.php");
}?>
<!-- If there are no hits, ask to login again-->
<html><head><title>Failed Login</title>
<link rel="stylesheet" type="text/css" href="desktop.css"></head><body>
<h2>Login incorrect</h2>
The parent name and password did not match any on record, please <a href="login.php">try again</a></body></html>
