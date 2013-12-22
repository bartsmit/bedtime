<?php
/*
This script is the start of the installation procedure

The user will be directed here if the MySQL credentials
from the bedtime configuration file /etc/bedtime.conf
fail to connect to the bedtime database.

In most cases, the script will run on a fresh install.
The untouched MySQL server has no password set, so the
script prompts for a new MySQL root password with a
confirmation. This is also made the OS root password. 
*/

# The $empt variable tracks if the MySQL root pass is empty 
$empt = 0;
# An IP address is seven characters or more
if (filesize("/usr/share/bedtime/network") < 7) {
   # The SERVER_ADDR variable holds the IP the client connects to
   # This identifies the client facing interface.
   # Since first connections are on Zeroconf, this will most likely
   # be an IPv6 address.
   $myip = $_SERVER['SERVER_ADDR'];
   # Run survey_net to complete the network survey. Send my IP as argument.
   $sock = socket_create(AF_INET,SOCK_STREAM,SOL_TCP);
   $result = socket_connect($sock,'127.0.0.1',5000);
   $buf = "s$myip\n";
   socket_write($sock,$buf,strlen($buf));
   # Wait for the network survey perl script to finish
   while (filesize("/usr/share/bedtime/network") < 7) {
      sleep(1);
   }
}
# We now have a network survey in the /usr/share/bedtime/network file.
if (isset($_GET['sqlrootpw'])) {
   # The sqlrootpw is the first input field
   $rootpass = $_GET['sqlrootpw'];
   # Check if it works:
   $mysqli = new mysqli ('localhost', 'root', $rootpass, 'mysql');
   if ($mysqli->connect_errno) {
      # Root pass doesn't work - maybe a reset?
      if (isset($_GET['confirm'])) {
         # Reset the MySQL root password
         if ($_GET['confirm'] == $_GET['sqlrootpw']) {
            # Two identical strings. Set the new password
            $mysqli = new mysqli('localhost','root','','mysql');
            $mysqli->query("update mysql.user set password=password('$rootpass') where user='root'");
            # Make sure the mysql time zone table is copied
            $mysqli->query("create table if not exists bedtime.time_zone_name select * from mysql.time_zone_name");
            # Send the new password to the perl daemon with the 'c' switch.
            # This will set the OS root password to the same as the MySQL one.
            $sock = socket_create(AF_INET,SOCK_STREAM,SOL_TCP);
            $result = socket_connect($sock,'127.0.0.1',5000);
            $buf = "c$rootpass\n";
            socket_write($sock,$buf,strlen($buf));
            # Next, create a reandom string for perl/php script access to the database
            $pass = substr(md5(uniqid()), 0, 12);
            # Set this to the sleepy user and give it full access to the bedtime database.
            $mysqli->query("delete from mysql.user where user='sleepy'");
            $mysqli->query("grant all on bedtime.* to 'sleepy'@'localhost' identified by '$pass'");
            $mysqli->query("flush privileges");
            # Set the DNS preference in the settings table.
            $mysqli->query("replace into bedtime.settings (variable,value) values('dns','".$_GET['dns']."')");
            # Pass the random password to the perl setup script through the daemon
            $sock = socket_create(AF_INET,SOCK_STREAM,SOL_TCP);
            $result = socket_connect($sock,'127.0.0.1',5000);
            $buf = "p$pass\n";
            socket_write($sock,$buf,strlen($buf));
            mysqli_close($mysqli);
            # Pass the user to the finish page where they disable DHCP on the router and log into bedtime
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
      # We're in with the right MySQL root password.
      # Copy the time zone table from mysql if not there
      $mysqli->query("create table if not exists bedtime.time_zone_name select * from mysql.time_zone_name");
      # set a random password for the sleepy user as per above
      $pass = substr(md5(uniqid()), 0, 12);
      $mysqli->query("delete from mysql.user where user='sleepy'");
      $mysqli->query("grant all on bedtime.* to 'sleepy'@'localhost' identified by '$pass'");
      $mysqli->query("flush privileges");
      # and set the DNS preference
      $mysqli->query("replace into bedtime.settings (variable,value) values('dns','".$_GET['dns']."')");
      # Run perl setup with the new password
      $sock = socket_create(AF_INET,SOCK_STREAM,SOL_TCP);
      $result = socket_connect($sock,'127.0.0.1',5000);
      $buf = "p$pass\n";
      socket_write($sock,$buf,strlen($buf));
      mysqli_close($mysqli);
      # All done, time to finish the install
      header("Location: finish.php");
   }
} else {
   # MySQL root password prompt returned empty
   $mysqli = new mysqli('localhost','root','','mysql');
   if ($mysqli->connect_errno) {
      # MySQL root pass is not empty. Prompt for the one set up before bedtime install was ran.
      $mesg = "Welcome to the Bedtime installation<br><br>\nYou set the MySQL root password during the installation of mysql server\n";
   } else {
      # Empty MySQL root password. Prompt for a new one, with confirmation. On submit this script will run again.
      $empt = 1;
      $mesg = "Your MySQL root password is empty. Please enter a new password twice\nThis will also be your Linux root password";
   }
}
echo "<html><head><title>Bedtime Install</title>";
echo "<link rel=\"stylesheet\" type=\"text/css\" href=\"../desktop.css\">";
echo "</head><body>\n";
echo "<h1>Bedtime Installation</h1>\n";
echo "<form name=\"install\">\n$mesg\n";
echo "<br><br>";
echo "<table border=\"0\"><tr><td>";
echo "Please enter the MySQL root password:</td><td><input type=\"password\" name=\"sqlrootpw\"></td></tr>\n";
if ($empt == 1) { echo "<tr><td>Second time for verification:</td><td><input type=\"password\" name=\"confirm\"></td></tr>\n"; }
echo "</table><br><br>\n";
echo "<h2>DNS selection</h2>\n";
echo "Which DNS service would you like to use:<br>\n";
echo "<input type=\"radio\" name=\"dns\" value=\"isp\">my current service<br>\n";
echo "<input type=\"radio\" name=\"dns\" value=\"opendns\" checked><a href=\"http://opendns.com\" target=\"blank\">OpenDNS</a><br><br>\n";
echo "<input type=\"submit\" value=\"submit\"></form><br>\n";
echo "Cancel and <a href=\"index.php\">reset</a></body></html>";
?>
