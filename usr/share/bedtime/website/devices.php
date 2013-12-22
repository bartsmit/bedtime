<style>
table, td, th
{
padding: 1px 5px;
}
</style>
<?php
/*
This script lists the devices as picked up by
the DHCP service on the bedtime server.
It presents the Manufacturer, MAC and IP,
and the time it first appeared on the network.

Parents can assign child and description to
each device. Devices can be sorted by all
their attributes.
*/

# This script needs parent security
session_start(); if (!isset($_SESSION["name"])) { header("location:login.php"); }
# and a database connection
include "dbconn.php";
# On load, send a request to update the device table
$sock = socket_create(AF_INET,SOCK_STREAM,SOL_TCP);
$result = socket_connect($sock,'127.0.0.1',5000);
$buf = "d\n";
socket_write($sock,$buf,strlen($buf));
# Also update the rules to account for new device assignments
$sock = socket_create(AF_INET,SOCK_STREAM,SOL_TCP);
$result = socket_connect($sock,'127.0.0.1',5000);
$buf = "u\n";
socket_write($sock,$buf,strlen($buf));
# Create an array matching child ID to name
$res = $mysqli->query("select user_id, name from child order by name");
$numrows = $res->num_rows;
# Check if any children are in the database
if ($numrows > 0) {
   $children = array();
   while ($obj = $res->fetch_object()) {
      $id = $obj->user_id;
      $cn = $obj->name;
      $children[$id] = $cn;
   }
# If not, redirect to the add child page
} else {
   header("Location: addchild.php");
}
# Check that there is a sort attribute set
if (isset($_GET['sortdev'])) {
   $sortdev = $_GET["sortdev"];
   # Store it in the settings table
   $mysqli->query("replace into settings values('sortdev','$sortdev')");
} 
# Check for the sort direction
if (isset($_GET['dirdev'])) {
   $dirdev = $_GET['dirdev'];
   # Which also goes into settings
   $mysqli->query("replace into settings values('dirdev','$dirdev')");
}
# Set the array of sortable attributes
$sortopts = array(
'owner' => 'owner',
'make'  => 'make',
'descr' => 'description',
'mac'   => 'mac address',
'ip'    => 'ip address',
'first' => 'first seen');
# Render the page
echo "<html><head><title>Manage Devices</title>\n";
echo "<link rel=\"stylesheet\" type=\"text/css\" href=\"desktop.css\"></head><body><h1>Devices</h1>\n";
echo "Assign a child to each device from the drop-down list. Delete devices that are no longer around\n";
echo "<br><br><form name=\"devices\">\n";
echo "Sort device list by <select name=\"sortdev\">\n";
# Pick up the sort attribute
$res = $mysqli->query("select value from settings where variable='sortdev'");
$row = $res->fetch_assoc();
$sortdev = $row['value'];
# And fill the drop down list from the array
foreach ($sortopts as $key => $opt) {
   $sel = ($key == $sortdev) ? ' selected ' : '';
   echo "<option value=\"$key\"$sel>$opt</option>\n";
}
echo "</select>\n";
# Pick up the sort direction
$res = $mysqli->query("select value from settings where variable='dirdev'");
$row = $res->fetch_assoc();
$dirdev = $row['value'];
# And set the radio buttons with it
echo "<input type=\"radio\" name=\"dirdev\" value=\"ascending\"";
if ($dirdev == 'ascending') { echo " checked=\"checked\" "; }
echo "/> Ascending ";
echo "<input type=\"radio\" name=\"dirdev\" value=\"descending\"";
if ($dirdev == 'descending') { echo " checked=\"checked\" "; }
echo "/> Descending<br>\n";
# Add the modifier for the select statement
if ((isset($sortdev)) && (isset($dirdev))) {
   $dir  = (substr($dirdev,0,4) == 'desc') ? ' desc' : '';
   $sort = " order by $sortdev".$dir;
} else {
   $sort = '';
}
# Craft the select statement for the device attributes
$sql = "select inet_ntoa(ip) as ip,
        (select name from child where device.user_id = child.user_id) as owner,
        manu as make,
        lpad(hex(mac),12,'0') as mac,
        first_seen  as first,
        description as descr,
        user_id as id from device $sort;";
# Run the query
$res = $mysqli->query($sql);
echo "<table borders=\"0\"><th>Owner</th><th>Make</th><th>Description</th>";
echo "<th>MAC</th><th>IP address</th><th>First seen</th><th>Delete</th>\n";
# Set up a list of devices to be deleted
$kill_list = '';
# For each device, pick up the results
while($obj = $res->fetch_object()) {
   $did    = $obj->id;
   $owner  = $obj->owner;
   $mac    = $obj->mac;
   $ip     = $obj->ip;
   $label  = preg_replace('/\|/',',',$obj->make);
   $first  = $obj->first;
   $descr  = $obj->descr;
   # Check if a user id for a child has been selected
   $newid  = (isset($_GET["o_$mac"])) ? $_GET["o_$mac"] : '';
   # See if the delete checkbox is ticked
   $remove = (isset($_GET["d_$mac"])) ? $_GET["d_$mac"] : '';
   # Or if there is a new description
   $newdsc = (isset($_GET["l_$mac"])) ? $_GET["l_$mac"] : '';
   # The manufacturer description is delimited by |
   $bits   = explode("|",$obj->make);
   # Replace those with commas
   $vendor = substr(preg_replace('/\s+|,/','',$bits[0]),0,8);
   # If the ID is set and not same as the old then...
   if (($newid <> '') && ($newid <> $did)) {
      # The new owner is non-zero and different from the one in the database
      $mysqli->query("update device set user_id=$newid where lpad(hex(mac),12,'0') = '$mac'");
      $did = $newid;
   }
   if (($newdsc <> '') && ($newdsc <> $descr)) {
      # Ditto for the description
      $mysqli->query("update device set description='$newdsc' where lpad(hex(mac),12,'0') = '$mac'");
      $descr = $newdsc;
   }
   # The delete checkbox is not ticked
   if ($remove == '') {
      # Render a line for the device starting with the owner drop-down
      echo "<tr><td><select name=\"o_$mac\">\n";
      # Show all children from the table
      echo "<option value=\"0\"> </option>\n";
      foreach ($children as $id => $name) {
         echo "<option value=\"$id\""; 
         # Show the present owner as the default
         if ($did == $id) { echo " selected"; }
         echo ">$name</option>\n";
      }
      # Display the MAC address with hyphens
      $dis_mac = rtrim(strtolower(chunk_split($mac,2,'-')),'-');
      # Display the short manufacturer description
      # And the long description on hover
      echo "</select></td><td><div title=\"$label\">$vendor</div></td>";
      # Render the description field as set by the parents
      echo "<td><input type=\"text\" value=\"$descr\" name=\"l_$mac\"></td>";
      # And the MAC and IP address
      echo "<td>$dis_mac</td><td>$ip</td><td>$first</td>\n";
      # Finish up with the delete checkbox
      echo "<td><input type=\"checkbox\" name=\"d_$mac\" value=\"d_$mac\"></td></tr>\n";
   } else {
      # The delete checkbox is ticked. Add the MAC to the kill list
      $kill_list .= "x'$mac',";
      # Get the mac out of the leases file through the perl kill script
      $sock = socket_create(AF_INET,SOCK_STREAM,SOL_TCP);
      $result = socket_connect($sock,'127.0.0.1',5000);
      $buf = "k$mac\n";
      socket_write($sock,$buf,strlen($buf));
   }
}
# Remove the deleted devices from the database
$kill_list = rtrim($kill_list,',');
$res = $mysqli->query("delete from device where mac in ($kill_list)");
?>
</table><br>Press submit to update devices.<hr>
<input type="submit" value="submit">
</form>
<a href="index.php">return</a><br>
Or <a href="logout.html">log out</a> of Bedtime<br><br>
<?php
# Finish the page with the version label
$res = $mysqli->query("select value from settings where variable='version'");
$row = $res->fetch_assoc(); $ver = $row['value'];
echo "<div class=\"version\">\n";
echo "<p>Bedtime version $ver</p>\n";
echo "</div>\n";
?>
</body></html>
