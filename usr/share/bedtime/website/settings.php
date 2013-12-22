<?php
/*
The settings script is where miscellaneous things end up.

Parents allows for create, modify and delete of logins.
Holidays are entered, changed and deleted. When they are
out of date (end date is in the past) they are deleted.
The initial settings are days of the weekend and timezone.
Backup/restore and upgrade/downgrade are last.
*/

# This is a restricted page, so require a login
session_start(); if (!isset($_SESSION["name"])) { header("location:login.php"); }
# And open a database connection
include "dbconn.php";
# Pick up the region and zone if set
$myreg = (isset($_GET['region'])) ? $_GET['region'] : '';
$mytwn = (isset($_GET['town']))   ? $_GET['town']   : '';
# If the timezone is fully set
if ($mytwn != '') {
   # Send it to the daemon to change it
   $sock = socket_create(AF_INET,SOCK_STREAM,SOL_TCP);
   $result = socket_connect($sock,'127.0.0.1',5000);
   $buf = "t$mytwn\n";
   socket_write($sock,$buf,strlen($buf));
}
# First query the database for all parents
$res = $mysqli->query("select parent_id from parent");
# Set an empty kill list
$kill = '';
# Check that at least one parent has a password
$pass_set = 0;
while ($row = $res->fetch_assoc()) {
   # For each parent, get the ID
   $id = $row['parent_id'];
   # And the password
   $pass = (isset($_GET["p_$id"])) ? $_GET["p_$id"] : '';
   # If a new password is entered
   if (!($pass == '')) {
      # Update the parent row
      $mysqli->query("update parent set password=md5('$pass') where parent_id=$id");
      $pass_set = 1;
   }
   # If the parent is for the chop
   if (isset($_GET["d_$id"])) {
      # Then add their ID to the kill list
      $kill = $kill."$id,";
   }
}
# Chop off the trailing comma
$kill = rtrim($kill,',');
# Then delete the ex-parents from the database
$mysqli->query("delete from parent where parent_id in ($kill)");
# See if there is a new parent
$name = (isset($_GET['name'])) ? $_GET['name'] : '';
$desc = (isset($_GET['desc'])) ? $_GET['desc'] : '';
$pass = (isset($_GET['pass'])) ? $_GET['pass'] : '';
# And if so, enter their particulars into the database
if (! ($name == '')) {
   $mysqli->query("insert into parent (name,description,password) values('$name','$desc',md5('$pass'))");
   $pass_set = 1;
}
# Count the parents
$res = $mysqli->query("select count(*) as num from parent");
$row = $res->fetch_assoc();
if ($row['num']==0) {
   # If there are none left, put the default admin/admin back to avoid lock-outs
   $mysqli->query("insert into parent (name,password) values('admin',md5('admin'))");
}
# List all the holidays
$res = $mysqli->query("select hol_id from holiday");
# Set up a kill list for cancelled ones
$kill = '';
# For each holiday
while ($row = $res->fetch_assoc()) {
   $id = $row['hol_id'];
   # Check if it is to be killed
   if (isset($_GET["hd_$id"])) {
      $kill = $kill."$id,";
   }
}
# Get rid of the final comma
$kill = rtrim($kill,',');
# And delete the wrong holidays from the table
$mysqli->query("delete from holiday where hol_id in($kill)");
# See if any of the new holiday details are set
$newhol = (isset($_GET['hname'])) ? $_GET['hname'] : '';
$start  = (isset($_GET['hstrt'])) ? $_GET['hstrt'] : '';
$stop   = (isset($_GET['hstop'])) ? $_GET['hstop'] : '';
# If the new holiday is valid
if ((! ($newhol == '')) && (! ($start == '')) && (! ($stop == ''))) {
   # Enter it into the database
   $mysqli->query("insert into holiday (name,start,stop) values('$newhol','$start','$stop')");
}
# Set the weekend start from the drop-down
if (isset($_GET['we_start'])) {
    $we_st = $_GET['we_start'];
    # And update the database if it is set
    $mysqli->query("update settings set value='$we_st' where variable='weekend'");
}
# If there is a new password, return to the index
if ($pass_set) {
   header("Location: index.php");
}
# If not, render the page
echo "<html><head><title>Settings</title>\n";
echo "<link rel=\"stylesheet\" type=\"text/css\" href=\"desktop.css\"></head><body><h1>Parents and Settings</h1>\n";
echo "<h2>Parents</h2>\n";
echo "<form name=\"settings\"><table borders=\"0\">\n";
echo "<th>Name</th><th>Description</th><th>Password</th><th>Delete</th>\n";
# Get all parents
$parents = array();
$res = $mysqli->query("select name, description, parent_id, password from parent");
# And for each of them
while($obj = $res->fetch_object()) {
   $id = $obj->parent_id;
   $cn = $obj->name;
   $ds = $obj->description;
   $pw = $obj->password;
   # see if there is a new description
   $nl = (isset($_GET["l_$id"])) ? $_GET["l_$id"] : '';
   if (($nl <> '') && ($nl <> $ds)) {
      # If there is and it's different, update the table
      $mysqli->query("update parent set description='$nl' where parent_id=$id");
      $ds = $nl;
   }
   # Render the name, description, passowrd and delete checkbox
   echo "<tr><td>$cn</td><td><input type=\"text\" value=\"$ds\" name=\"l_$id\"></td>";
   echo "<td><input type=\"password\" name=\"p_$id\"></td>";
   echo "<td><input type=\"checkbox\" name=\"d_$id\" value=\"d_$id\"></td></tr>\n";
}
# Show the add parent fields
echo "</table><br>Add Parent<br>Name <input type=\"text\" name=\"name\"> Description ";
echo "<input type=\"text\" name=\"desc\"> Password <input type=\"password\" name=\"pass\"><hr>\n";
echo "<h2>School Holidays</h2><table borders=\"0\">\n";
echo "<th>Holiday</th><th>Start date</th><th>End date</th><th>Delete</th>\n";
# And display the holidays
$hols = array();
# Discarding those that ended in the past
$res = $mysqli->query("delete from holiday where stop < date(now())");
$res = $mysqli->query("select name, start, stop, hol_id from holiday");
while($obj = $res->fetch_object()) {
   $h_id  = $obj->hol_id;
   $name  = $obj->name;
   $strt  = $obj->start;
   $stop  = $obj->stop;
   # See if there is a new start or finish date
   $nstrt = (isset($_GET["hs_$id"])) ? $_GET["hs_$id"] : '';
   $nstop = (isset($_GET["hf_$id"])) ? $_GET["hf_$id"] : '';
   if (($nstrt <> '') && ($nstrt <> $strt)) {
      # Update the start date
      $mysqli->query("update holiday set start='$nstrt' where hol_id=$h_id");
   }
   if (($nstop <> '') && ($nstop <> $stop)) {
      # And/or the stop date if there is
      $mysqli->query("update holiday set stop='$nstop' where hol_id=$h_id");
   }
   # Then just render the holiday with dates and delete checkbox
   echo "<tr><td>$name</td><td><input type=\"text\" value=\"$strt\" name=\"hs_$h_id\" size=\"10\"></td>";
   echo "<td><input type=\"text\" value=\"$stop\" name=\"hf_$h_id\" size=\"10\"></td>";
   echo "<td><input type=\"checkbox\" name=\"hd_$h_id\" value=\"hd_$h_id\"></td></tr>\n";
}
echo "</table><br>Add Holiday<br>Name <input type=\"text\" name=\"hname\"> Start date (yyyy-mm-dd)";
echo "<input type=\"text\" name=\"hstrt\"> End date (yyyy-mm-dd)<input type=\"text\" name=\"hstop\"><hr>\n";
# Set the weekdays array for the weekend start selection
$weekdays = array(
130 => "Monday",
192 => "Tuesday",
96  => "Wednesday",
48  => "Thursday",
24  => "Friday",
12  => "Saturday",
6   => "Sunday");
# Pick up the currently selected value
$res  = $mysqli->query("select value from settings where variable='weekend'");
$row  = $res->fetch_assoc();
$mask = $row['value'];
echo "<h2>Settings</h2>First day of the weekend: <select name=\"we_start\">\n";
# Fill the drop-down list
foreach ($weekdays as $key => $day) {
   # Defaulting to the current value
   $sel = ($key == $mask) ? ' selected' : '';
   echo "<option value=\"$key\"$sel>$day</option>\n";
}
echo "</select><br>";
# See if the region and town have been set. If not, UTC is the default
# Note that the selection is split between region and timezone to avoid
# scrolling through a long list of zones
if ($myreg == '') {
   # None entered, see if there is a region in settings
   $res = $mysqli->query("select value from settings where variable='region'");
   if ($res->num_rows > 0) {
      $row = $res->fetch_assoc();
      $myreg = $row['value'];
   }
} else {
   # New region entered. Store it in settings
   $res = $mysqli->query("replace into settings values('region','$myreg')");
}
# Same for timezone ($town)
if ($mytwn == '') {
   $res = $mysqli->query("select value from settings where variable='town'");
   if ($res->num_rows > 0) {
      $row = $res->fetch_assoc();
      $mytwn = $row['value'];
   }
} else {
   # Update the timezone in the settings table
   $res = $mysqli->query("replace into settings values('town','$mytwn')");
}
# Take only the regions as unique strings before the /
$res = $mysqli->query("select left(Name,locate('/',Name)-1) as region from time_zone_name group by region order by region");
# If there are no regions in the database
if ($res->num_rows == 0) {
   # Enter it manually
   echo "Enter timezone: <input type=\"text\" name=\"man_region\">\n";
} else {
   # Normally just get the region through selection
   echo "Region: <select name=\"region\">\n";
   while ($row = $res->fetch_assoc()) {
      $reg = $row['region'];
      # Default to the region from the database
      $sel = ($reg == $myreg) ? ' selected ' : '';
      echo "<option value=\"$reg\"$sel>$reg</option>\n";
   }
   echo "</select><br>\n";
}
# Check if there is a region set
if ($myreg != '') {
   # Get all the towns (time zones) from a region
   $res = $mysqli->query("select Name as town from time_zone_name where Name like '$myreg/%' order by town");
   echo "Timezone: <select name=\"town\">\n";
   # And display them as a drop-down
   while ($row = $res->fetch_assoc()) {
      $twn = $row['town'];
      # Default to the current one
      $sel = ($twn == $mytwn) ? ' selected ' : '';
      echo "<option value\"$twn\"$sel>$twn</option>\n";
   }
   echo "</select><br>\n";
}
# Check if the time zone selection is complete
if (($mytwn != '') && (strpos($mytwn, $myreg)!== false)) {
   # And show a confirmation if this is so
   echo "The timezone is set to $mytwn<br>\n";
}
# Show the submit button for conventional changes
?>
<br><br><input type="submit" value="Submit"> parent, holiday and settings changes.
<!-- End the form for normal values and start the backup/restore section-->
</form><br><hr><h2>Backup/Restore</h2>
<!--Backup is a simple link-->
<a href="backup.php">backup</a><br>
<!--Restore is a file selector that is sent to the restore.php script-->
<form action="restore.php" method="post" enctype="multipart/form-data">
Restore from: <input type="file" name="dump" size="40" />
<input type="submit" name="submit" value="Restore" /></form>
<?php
# Get the current version for upgrade/downgrade comparison
$res = $mysqli->query("select value from settings where variable='version'");
$row = $res->fetch_assoc(); $ver = $row['value'];
# Get the RPM version for downgrades
$res = $mysqli->query("select value from settings where variable='rpm'");
$row = $res->fetch_assoc(); $rpm = $row['value'];
# Read the SF download page for links to the tarball
$filepage = file_get_contents("http://sourceforge.net/projects/bedtime/files/");
preg_match('/bedtime([0-9]|\.|-)+tgz/',$filepage,$matches);
# And pick up the tarball name from that
$latest = $matches[0];
# Get the URL from any link with the name in it
preg_match("/href=\"http:\/\/(sourceforge\.net|sf\.net)\/projects\/bedtime\/files\/$latest\/download\"/",$filepage,$matches);
# Get rid of the bedtime- prefix and the tgz suffix to leave only the version string
$latest = preg_replace('/bedtime-|\.tgz/','',$latest);
# The url is the match for the link
$url = $matches[0];
# Remove the href=" and last " to leave just the URL
$url = preg_replace('/^href=\"|\"$/','',$url);
# Update the latest value in the database
$res = $mysqli->query("replace into settings (variable,value) values('latest','$latest'), ('latest_url','$url')");
# If we're not the latest, then we can upgrade
if ($latest <> $ver) {
   echo "<hr><h2>Upgrade</h2>There is a newer version of bedtime ($latest).<br>\n";
   echo "Make sure you back up before you <a href=\"upgrade.php\">upgrade.</a>\n";
}
# If the RPM version is not the current one, we can downgrade
if ($rpm <> $ver) {
   echo "<hr><h2>Downgrade</h2>If you are having trouble with this version($ver).<br>\n";
   echo "You can <a href=\"downgrade.php\">downgrade</a> to version $rpm.\n";
   echo "Make a backup before you do so.\n";
}
?>
<hr>
<a href="index.php">return</a><br>
Or <a href="logout.html">log out</a> of Bedtime<br><br>
<?php
# Customary version footer
echo "<div class=\"version\">\n";
echo "<p>Bedtime version $ver</p>\n";
echo "</div>\n";
?>
</body></html>
