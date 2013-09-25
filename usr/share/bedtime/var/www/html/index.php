<?php
include("/bedtime/dbconn.php");
if ($mysqli->connect_errno) {
   header ("Location: /install/");
} else {
   header ("Location: /bedtime/");
}
?>
