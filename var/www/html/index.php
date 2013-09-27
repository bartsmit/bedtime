<?php
include("/usr/share/bedtime/website/dbconn.php");
if ($mysqli->connect_errno) {
   header ("Location: /bedtime/install/");
} else {
   header ("Location: /bedtime/");
}
?>
