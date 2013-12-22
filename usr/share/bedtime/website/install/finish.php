<?php
/*
All the heavy lifting is done by the main install php script.
The user just needs to disable DHCP on the ISP router and
login into Bedtime with the default admin/admin credentials.
The ISP router is identified by its issue of our DHCP lease.
*/
$net = file('/usr/share/bedtime/network');
foreach ($net as $line) {
   # Split each line by the forward slash delimiter
   $bits = preg_split ("/\//",rtrim($line));
   # Set the DHCP server variable
   if ($bits[1] === "dhcp") {
      $dhcp = $bits[0];
   }
   # And the default gateway variable
   if ($bits[1] === "dg") {
      $dg = $bits[0];
  }
}
# If the survey has not found a DHCP server somehow
# Then fall back to the default gateway as the most 
# likely address for the ISP router.
if ((!isset($dhcp)) or ($dhcp === "")) { $dhcp = $dg; }
print "<html><head><title>Finish Install</title>\n";
print "<link rel=\"stylesheet\" type=\"text/css\" href=\"../desktop.css\"></head><body>\n";
print "<h1>Installation Finished</h1>\n";
print "Congratulations, Bedtime is set up.<br><br>Next, you will need to disable the DHCP server<br>\n";
print "Click <a href=\"http://$dhcp\" target=\"_blank\">here</a> to manage your router in a new tab/window<br><br>\n";
print "You can use this shortcut if you have a <a href=\"http://$dhcp/index.cgi?active_page=9137\" target=\"_blank\">BT Home Hub 3</a><br>\n";
print "There are instructions for other routers in the <a href=\"http://sf.net/p/bedtime/wiki/Routers\" target=\"_blank\">Wiki</a><br>\n";
print "<h2>Log into Bedtime</h2>\n";
print "Once the DHCP service has been switched off, click <a href=\"/bedtime\">here</a><br>\n";
print "and log in with name admin and password admin</body></html>\n";
?>
