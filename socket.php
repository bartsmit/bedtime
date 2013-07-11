<?php
$sock = socket_create(AF_INET,SOCK_STREAM,SOL_TCP);
if ($sock === false) { 
   echo "socket_create failed. reason: ". socket_error(socke_last_error()) . "\n";
} else {
   echo "socket created OK\n";
}
echo "Attempting to connect to localhost on 5000...";
$result = socket_connect($sock,'127.0.0.1',5000);
if ($result === false) {
   echo "socket_connect failed. reason: ".socket_error(socket_last_error($socket)) . "\n";
} else {
  echo "socket connect OK\n";
}

$out = socket_read($sock,2048);
echo "I got me some message: " . $out . "\n";
echo "Bout time I sent back hello\n";
$buf = "howdy\n";
socket_write($sock,$buf,strlen($buf));
echo "Sent and all yippee!\n";
?>
