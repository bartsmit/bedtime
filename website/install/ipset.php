<?php
$myip  = $_SERVER['SERVER_ADDR'];
$nwip = (isset($_GET['newip'])) ? $_GET['newip'] : '';
$mesg  = "Congratulations, the database is set up.";
if ($nwip != '') {
   if ($nwip == $myip) {
      header("Location: finish.php?ip=$nwip");
   } else {
      exec("bin/ping -c 1 $nwip",&$out,&$ret);
      if ($ret == 0) {
           #header("Location: finish.php?ip=$nwip");
$mesg = "letsa go to $nwip";
      } else {
          $mesg = "IP $nwip is already in use.";
      }
   }
} else {
   $nwip = $myip;
}
echo "<html><head><title>IP Address</title></head><body><form name=\"ipsetup\">\n";
echo "$mesg<br><br>\n";
echo "Server IP $myip - New IP $nwip - ping result $ret<br>\n";
print_r (&$out);
echo "<br>Please enter the new IP address of this server: <input type=\"textbox\" value=\"$nwip\" name=\"newip\">\n";
echo "<br><br><input type=\"submit\" value=\"submit\"></form><br></body></html>\n";
?>
