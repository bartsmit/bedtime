<?php
include "dbconn.php";
$res = squery("select value from settings where variable='myip'",$mysqli);
$myip = $res['value'];
if ($myip != $_SERVER['SERVER_ADDR']) {
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
$yourip = $_SERVER['REMOTE_ADDR'];
$res = squery("select lpad(hex(mac),12,'0') as mac from device where inet_ntoa(ip)='$yourip'",$mysqli);
$mac = $res['mac'];
echo "<tr><td>Your IP</td><td>:</td><td>".$yourip."</td></tr>\n";
if (strlen($mac) > 2) {
   echo "<tr><td>And MAC</td><td>:</td><td>".rtrim(strtolower(chunk_split($mac,2,'-')),'-')."</td></tr>\n";
   $res = squery("select name from child inner join device on child.user_id = device.user_id where inet_ntoa(device.ip)='$yourip'",$mysqli);
   $name = $res['name'];
   if (strlen($name) > 0) {
      echo "<tr><td>Owner</td><td>:</td><td>".$name."</td></tr>\n";
   }
}
?>
<tr><td>&nbsp;</td><td>&nbsp;</td><td><input type="submit" name="Submit" value="Login"></td></tr>
</table></td></form></tr></table></body></html>
