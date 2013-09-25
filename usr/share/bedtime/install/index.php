<?php
if (filesize("/usr/share/bedtime/network") < 7) {
   file_put_contents("/usr/share/bedtime/network",$_SERVER['SERVER_ADDR']."/m");
}
# Run survey_net to complete the network survey
$sock = socket_create(AF_INET,SOCK_STREAM,SOL_TCP);
$result = socket_connect($sock,'127.0.0.1',5000);
$buf = "s\n";
socket_write($sock,$buf,strlen($buf));
if (isset($_GET['sqlrootpw'])) {
   $rootpass = $_GET['sqlrootpw'];
   $mysqli = new mysqli ('localhost', 'root', $rootpass, 'mysql');
   if ($mysqli->connect_errno) {
      $mesg = "Wrong password, try again?";
   } else {
      $mysqli->query("create database if not exists bedtime");
      $pass = substr(md5(uniqid()), 0, 12);
      $mysqli->query("delete from mysql.user where user='sleepy'");
      $mysqli->query("grant all on bedtime.* to 'sleepy'@'localhost' identified by '$pass'");
      $mysqli->query("flush privileges");
      $mysqli->query("replace into bedtime.settings (variable,value) values('dns','".$_GET['dns']."')");
      # Run btsetup with the new password
      $sock = socket_create(AF_INET,SOCK_STREAM,SOL_TCP);
      $result = socket_connect($sock,'127.0.0.1',5000);
      $buf = "p$pass\n";
      socket_write($sock,$buf,strlen($buf));
      mysqli_close($mysqli);
      header("Location: finish.php");
   }
} else {
   $mysqli = new mysqli('localhost','root','','mysql');
   if ($mysqli->connect_errno) {
      # MySQL root pass is not empty
   } else {
      $mysqli->query("create database if not exists bedtime");
      $pass = substr(md5(uniqid()), 0, 12);
      $mysqli->query("delete from mysql.user where user='sleepy'");
      $mysqli->query("grant all on bedtime.* to 'sleepy'@'localhost' identified by '$pass'");
      $mysqli->query("flush privileges");
      $sock = socket_create(AF_INET,SOCK_STREAM,SOL_TCP);
      $result = socket_connect($sock,'127.0.0.1',5000);
      $buf = "p$pass\n";
      socket_write($sock,$buf,strlen($buf));
      mysqli_close($mysqli);
      header("Location: finish.php");
   }
}
echo "<html><head><title>Bedtime Install</title></head><body>\n";
echo "<form name=\"install\">\n";
if (isset($mesg)) { 
   echo "$mesg\n";
} else { 
   echo "Welcome to the Bedtime installation\n";
}
echo "<br><br>";
echo "Please enter the MySQL root password: <input type=\"password\" name=\"sqlrootpw\"><br>\n";
echo "This is the password you set when MySQL server was first installed.<br><br>\n";
echo "Which DNS service would you like to use:<br>\n";
echo "<input type=\"radio\" name=\"dns\" value=\"isp\">my current service<br>\n";
echo "<input type=\"radio\" name=\"dns\" value=\"opendns\" checked><a href=\"http://opendns.com\" target=\"blank\">OpenDNS</a><br><br>\n";
echo "<input type=\"submit\" value=\"submit\"></form><br>\n";
echo "Cancel and <a href=\"index.php\">reset</a></body></html>";
?>
