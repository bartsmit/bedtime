<?php
$hour = '21'; $min='60'; $sec='59';
$time =  preg_match("/(2[0-3]|[01][0-9]):[0-5][0-9]:[0-5][0-9]/", "$hour:$min:$sec") ? "true" : "false"; 
print $time;



#preg_match("[0-1][0-9]|2[0-3] [0-5][0-9] [0-5][0-9]", "$hour $min $sec")
?>
