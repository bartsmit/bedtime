<?php
include("/usr/share/bedtime/website/dbconn.php");
if ($mysqli->connect_errno) {
   header ("Location: /bedtime/install/");
} else {
   $res = squery("select value from settings where variable='myip'",$mysqli);
   $myip = $res['value'];
   if (strlen($myip) > 7) {
      header ("Location: http://$myip/bedtime/");
   } else {
      header ("Location: /bedtime/");
   }
}
?>
