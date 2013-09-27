<?php
$empt = 0;
# An IP address is seven characters or more
if (filesize("/usr/share/bedtime/network") < 7) {
   $myip = $_SERVER['SERVER_ADDR'];
   # Run survey_net to complete the network survey
   $sock = socket_create(AF_INET,SOCK_STREAM,SOL_TCP);
   $result = socket_connect($sock,'127.0.0.1',5000);
   $buf = "s$myip\n";
   socket_write($sock,$buf,strlen($buf));
}
if (isset($_GET['sqlrootpw'])) {
   $rootpass = $_GET['sqlrootpw'];
   $mysqli = new mysqli ('localhost', 'root', $rootpass, 'mysql');
   if ($mysqli->connect_errno) {
      # Root pass doesn't work - maybe a reset?
      if (isset($_GET['confirm'])) {
         # Reset the MySQL root password
         if ($_GET['confirm'] == $_GET['sqlrootpw']) {
            # Two identical strings
            $mysqli = new mysqli('localhost','root','','mysql');
            $mysqli->query("update mysql.user set password=password('$rootpass') where user='root'");
            $mysqli->query("flush privileges");
            mysqli_close($mysqli);
            header("Location: finish.php");
         } else {
            # Typo in confirm
            $mesg = "Passwords don't match, try again?";
            $empt = 1;
         }
      } else {
         # No reset attempt, so plain wrong
         $mesg = "Wrong password, try again?";
      }
   } else {
      # We're in with the right root pass, set permission
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
   # MySQL root password prompt returned empty
   $mysqli = new mysqli('localhost','root','','mysql');
   if ($mysqli->connect_errno) {
      # MySQL root pass is not empty
      $mesg = "Welcome to the Bedtime installation<br><br>\nYou set the MySQL root password during the installation of mysql server\n";
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
      $empt = 1;
      $mesg = "Your MySQL root password is empty. Please enter a new password twice";
   }
}
echo "<html><head><title>Bedtime Install</title></head><body>\n";
echo "<form name=\"install\">\n$mesg\n";
echo "<br><br>";
echo "<table border=\"0\"><tr><td>";
echo "Please enter the MySQL root password:</td><td><input type=\"password\" name=\"sqlrootpw\"></td></tr>\n";
if ($empt == 1) { echo "<tr><td>Second time for verification:</td><td><input type=\"password\" name=\"confirm\"></td></tr>\n"; }
echo "</table><br><br>\n";
echo "Which DNS service would you like to use:<br>\n";
echo "<input type=\"radio\" name=\"dns\" value=\"isp\">my current service<br>\n";
echo "<input type=\"radio\" name=\"dns\" value=\"opendns\" checked><a href=\"http://opendns.com\" target=\"blank\">OpenDNS</a><br><br>\n";
echo "<input type=\"submit\" value=\"submit\"></form><br>\n";
echo "Cancel and <a href=\"index.php\">reset</a></body></html>";
?>
