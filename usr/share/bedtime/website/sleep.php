<?php
include "dbconn.php";
$res = squery("select value from settings where variable = 'myip'",$mysqli);
$myip = $res['value'];
?>
<html><head><title>Time to go to sleep</title>
<meta http-equiv="cache-control" content="max-age=0" />
<meta http-equiv="cache-control" content="no-cache" />
<meta http-equiv="expires" content="0" />
<meta http-equiv="expires" content="Tue, 01 Jan 1980 1:00:00 GMT" />
<meta http-equiv="pragma" content="no-cache" />
</head><body>
<img src="http://<?php echo $myip; ?>/bedtime/sleep.png">
</body>
