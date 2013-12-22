<?php
# This script is used as the target for the rewrite rule in Squid. 
# After bedtime, this page renders the sleep jpeg
# Connect to the database
include "dbconn.php";
# Then pick up the IP of the server
$res = $mysqli->query("select value from settings where variable = 'myip'");
$row = $res->fetch_assoc(); $myip = $row['value'];
# Render the image with as little caching as possible
?>
<html><head><title>Time to go to sleep</title>
<meta http-equiv="cache-control" content="max-age=0" />
<meta http-equiv="cache-control" content="no-cache" />
<meta http-equiv="expires" content="0" />
<meta http-equiv="expires" content="Tue, 01 Jan 1980 1:00:00 GMT" />
<meta http-equiv="pragma" content="no-cache" />
</head><body>
<img src="http://<?php echo $myip; ?>/bedtime/sleep.jpg">
</body>
