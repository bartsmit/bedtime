<?php
/*
This script handles the input part of parent login.
After entry, the values are checked by validate.php
*/

# Open a connection to the database
include "dbconn.php";
# Look for the server IPv4 address
$res = $mysqli->query("select value from settings where variable='myip'");
$row = $res->fetch_assoc(); $myip = $row['value'];
# If the login page is reached on its IPv6 address
if ($myip != $_SERVER['SERVER_ADDR']) {
   # Redirect to the server IPv4 address, so that the client IP is IPv4
   header("Location: http://$myip/bedtime/login.php");
}
?>
<html><head><title>Parent Login</title>
<link rel="stylesheet" type="text/css" href="desktop.css"></head><body>
<table width="300" border="0" align="center" cellpadding="0" cellspacing="1" bgcolor="#CCCCCC">
<tr><form name="login" method="post" action="validate.php"><td>
<table width="100%" border="0" cellpadding="3" cellspacing="1" bgcolor="#FFFFFF">
<tr><td colspan="3"><strong>Bedtime Parent Login </strong></td></tr><tr>
<td width="78">Name</td><td width="6">:</td>
<td width="294"><input name="parent" type="text" id="parent"></td></tr>
<tr><td>Password</td><td>:</td><td><input name="password" type="password" id="password"></td></tr>
<?php
# Thanks to the redirect above the client IP has a chance of being in the database
$yourip = $_SERVER['REMOTE_ADDR'];
# If it is, find the matching MAC from DHCP
$res = $mysqli->query("select lpad(hex(mac),12,'0') as mac from device where inet_ntoa(ip)='$yourip'");
$row = $res->fetch_assoc(); $mac = $row['mac'];
# Show the IPv4 in the login dialog
echo "<tr><td>Your IP</td><td>:</td><td>".$yourip."</td></tr>\n";
if (strlen($mac) > 2) {
   # And the MAC if there is one. This helps in the client identification
   echo "<tr><td>And MAC</td><td>:</td><td>".rtrim(strtolower(chunk_split($mac,2,'-')),'-')."</td></tr>\n";
   # Show the owning child if it is set
   $res = $mysqli->query("select name from child inner join device on child.user_id = device.user_id where inet_ntoa(device.ip)='$yourip'");
   $row = $res->fetch_assoc(); $name = $row['name'];
   if (strlen($name) > 0) {
      # The name is not null
      echo "<tr><td>Owner</td><td>:</td><td>".$name."</td></tr>\n";
   }
}
?>
<tr><td>&nbsp;</td><td>&nbsp;</td><td><input type="submit" name="Submit" value="Login"></td></tr>
</table></td></form></tr></table><br><br>
<?php
# Show the version footer
$res = $mysqli->query("select value from settings where variable='version'");
$row = $res->fetch_assoc(); $ver = $row['value'];
echo "<div class=\"version\">\n";
echo "<p>Bedtime version $ver</p>\n";
echo "</div>\n";
?>
</body></html>
