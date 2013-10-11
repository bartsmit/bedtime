<?php
$net = file('/usr/share/bedtime/network');
foreach ($net as $line) {
   $bits = preg_split ("/\//",rtrim($line));
   if ($bits[1] === "dhcp") {
      $dhcp = $bits[0];
   }
   if ($bits[1] === "dg") {
      $dg = $bits[0];
  }
}
if ((!isset($dhcp)) or ($dhcp === "")) { $dhcp = $dg; }
print "<html><title>Finish Install</title><body>\n";
print "Congratulations, Bedtime is set up.<br><br>Next, you will need to disable the DHCP server<br>\n";
print "Click <a href=\"http://$dhcp\" target=\"_blank\">here</a> to manage your router in a new tab/window<br><br>\n";
print "Once the DHCP service has been switched off, click <a href=\"/bedtime\">here</a><br>\n";
print "and log in with name admin and password admin</body></html>\n";
?>
