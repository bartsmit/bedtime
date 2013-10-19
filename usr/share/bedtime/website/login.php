<?php
include "dbconn.php";
$client = $_SERVER["REMOTE_ADDR"];
$res = $mysqli->query("select lpad(hex(mac),12,'0') as mac from devices where inet_ntoa(ip)='$client'");
$numrows = $res->num_rows;
if ($numrows > 0) {
   $obj = $res->fetch_object();
   $mac = $obj->mac;
   $msg = "Your IP address is $client and your MAC address is $mac.";
#   $res = $mysqli->query("select user_id from device where lpad(hex(mac),12,'0')='$mac'");
#   $numrows = $res->num_rows;
#   if ($numrows > 0) {
#      $obj = $res->fetch_object();
#      $uid = $obj->user_id;
#      $msg = $msg . "<br>This device is assigned to 
#   }
} else {
   $msg = "Your IP address was not issued by bedtime.<br>Please restart your device or network<br>";
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
<tr><td>&nbsp;</td><td>&nbsp;</td><td><input type="submit" name="Submit" value="Login"></td></tr>
</table></td></form></tr></table>
<?php
echo "<br>$msg\n";
?></body></html>
