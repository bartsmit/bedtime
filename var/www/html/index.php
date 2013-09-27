<?php
include("/usr/share/bedtime/website/dbconn.php");
if ($mysqli->connect_errno) {
   header ("Location: /install/");
} else {
   header ("Location: /bedtime/");
}
?>
